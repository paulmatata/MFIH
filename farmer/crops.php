<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_farmer();

$user_id = authenticated_farmer_id();

if (!$user_id) {
    redirect('/login.php');
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}


/*
|--------------------------------------------------------------------------
| Load farmer farms
|--------------------------------------------------------------------------
*/

$farm_stmt = $pdo->prepare("
    SELECT
        id,
        farm_name,
        farm_size_acres
    FROM farmer_profiles
    WHERE user_id = ?
    ORDER BY created_at ASC
");

$farm_stmt->execute([$user_id]);

$farms = $farm_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Load crop catalogue
|--------------------------------------------------------------------------
*/

$crop_stmt = $pdo->query("
    SELECT
        id,
        name,
        category
    FROM crops
    WHERE is_active = 1
    ORDER BY category, name
");

$crops = $crop_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Load existing crop records
|--------------------------------------------------------------------------
*/

$records_stmt = $pdo->prepare("
    SELECT
        cr.id,
        cr.farm_id,
        cr.crop_id,
        cr.season,
        cr.area_planted_acres,
        cr.planting_date,
        cr.expected_harvest_start,
        cr.expected_harvest_end,
        cr.previous_crop_id,
        cr.variety,
        cr.observations,

        c.name AS crop_name,
        c.category AS crop_category,

        pc.name AS previous_crop_name,

        fp.farm_name,
        fp.farm_size_acres

    FROM crop_records cr

    INNER JOIN farmer_profiles fp
        ON fp.id = cr.farm_id

    INNER JOIN crops c
        ON c.id = cr.crop_id

    LEFT JOIN crops pc
        ON pc.id = cr.previous_crop_id

    WHERE fp.user_id = ?

    ORDER BY cr.created_at DESC
");

$records_stmt->execute([$user_id]);

$records = $records_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

$errors = [];

$old = [
    'farm_id' => '',
    'crop_id' => '',
    'season' => '',
    'area_planted_acres' => '',
    'planting_date' => '',
    'expected_harvest_start' => '',
    'expected_harvest_end' => '',
    'previous_crop_id' => '',
    'variety' => '',
    'observations' => ''
];


/*
|--------------------------------------------------------------------------
| Save crop
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($old as $key => $value) {
        $old[$key] = trim($_POST[$key] ?? '');
    }


    /*
    |--------------------------------------------------------------------------
    | Required fields
    |--------------------------------------------------------------------------
    */

    if ($old['farm_id'] === '') {
        $errors[] = 'Please select the farm.';
    }

    if ($old['crop_id'] === '') {
        $errors[] = 'Please select the crop.';
    }

    if ($old['season'] === '') {
        $errors[] = 'Please select the season.';
    }

    if ($old['area_planted_acres'] === '') {
        $errors[] = 'Please enter the area planted.';
    }

    if ($old['planting_date'] === '') {
        $errors[] = 'Please enter the planting date.';
    }


    /*
    |--------------------------------------------------------------------------
    | Validate acreage
    |--------------------------------------------------------------------------
    */

    if (
        $old['area_planted_acres'] !== ''
        &&
        (
            !is_numeric($old['area_planted_acres'])
            ||
            (float)$old['area_planted_acres'] <= 0
        )
    ) {
        $errors[] = 'Area planted must be greater than zero.';
    }


    /*
    |--------------------------------------------------------------------------
    | Verify selected farm belongs to farmer
    |--------------------------------------------------------------------------
    */

    $selected_farm = null;

    if ($old['farm_id'] !== '') {

        $farm_check = $pdo->prepare("
            SELECT
                id,
                farm_size_acres
            FROM farmer_profiles
            WHERE id = ?
              AND user_id = ?
            LIMIT 1
        ");

        $farm_check->execute([
            $old['farm_id'],
            $user_id
        ]);

        $selected_farm = $farm_check->fetch(PDO::FETCH_ASSOC);

        if (!$selected_farm) {
            $errors[] = 'Invalid farm selected.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Check total seasonal land allocation
    |--------------------------------------------------------------------------
    |
    | We do NOT simply check:
    |
    | crop area <= farm size
    |
    | because several crops may occupy the same farm.
    |
    | Instead we calculate existing allocation for
    | the same farm and season.
    |
    */

    if (
        !$errors
        &&
        $selected_farm
    ) {

        $allocation_stmt = $pdo->prepare("
            SELECT
                COALESCE(
                    SUM(area_planted_acres),
                    0
                ) AS allocated_area
            FROM crop_records
            WHERE farm_id = ?
              AND season = ?
        ");

        $allocation_stmt->execute([
            $old['farm_id'],
            $old['season']
        ]);

        $allocation =
            (float)$allocation_stmt
                ->fetchColumn();

        $requested =
            (float)$old['area_planted_acres'];

        $farm_size =
            (float)$selected_farm['farm_size_acres'];

        $remaining =
            $farm_size - $allocation;


        if ($requested > $remaining) {

            $errors[] =
                'The selected area is too large. '
                . 'This farm has only '
                . number_format(
                    max($remaining, 0),
                    2
                )
                . ' acres remaining for this season.';

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate crop
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $crop_check = $pdo->prepare("
            SELECT id
            FROM crops
            WHERE id = ?
              AND is_active = 1
            LIMIT 1
        ");

        $crop_check->execute([
            $old['crop_id']
        ]);

        if (!$crop_check->fetch()) {
            $errors[] = 'Invalid crop selected.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate previous crop
    |--------------------------------------------------------------------------
    */

    if (
        !$errors &&
        $old['previous_crop_id'] !== ''
    ) {

        $previous_crop_check =
            $pdo->prepare("
                SELECT id
                FROM crops
                WHERE id = ?
                  AND is_active = 1
                LIMIT 1
            ");

        $previous_crop_check->execute([
            $old['previous_crop_id']
        ]);

        if (!$previous_crop_check->fetch()) {
            $errors[] = 'Invalid previous crop selected.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate harvest period
    |--------------------------------------------------------------------------
    */

    if (
        !$errors &&
        $old['expected_harvest_start'] !== '' &&
        $old['expected_harvest_end'] !== ''
    ) {

        if (
            $old['expected_harvest_end']
            <
            $old['expected_harvest_start']
        ) {

            $errors[] =
                'Expected harvest end date cannot '
                . 'be before the start date.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $id = generate_uuid();

        $insert = $pdo->prepare("
            INSERT INTO crop_records (
                id,
                farm_id,
                crop_id,
                season,
                area_planted_acres,
                planting_date,
                expected_harvest_start,
                expected_harvest_end,
                previous_crop_id,
                variety,
                observations,
                created_at,
                updated_at
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NOW(),
                NOW()
            )
        ");

        $insert->execute([
            $id,
            $old['farm_id'],
            $old['crop_id'],
            $old['season'],
            $old['area_planted_acres'],
            $old['planting_date'],
            $old['expected_harvest_start'] !== ''
                ? $old['expected_harvest_start']
                : null,
            $old['expected_harvest_end'] !== ''
                ? $old['expected_harvest_end']
                : null,
            $old['previous_crop_id'] !== ''
                ? $old['previous_crop_id']
                : null,
            $old['variety'] !== ''
                ? $old['variety']
                : null,
            $old['observations'] !== ''
                ? $old['observations']
                : null
        ]);

        redirect('/farmer/crops.php');
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Crops | FIH</title>

    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/farmer.css"
    >

    <style>

        .crops-page {
            max-width: 1000px;
            margin: auto;
            padding: 20px 14px 70px;
        }

        .page-intro {
            margin-bottom: 25px;
        }

        .page-intro h1 {
            margin-bottom: 8px;
        }

        .page-intro p {
            line-height: 1.6;
            opacity: .75;
        }

        .crop-form {
            padding: 20px;
            border-radius: 18px;
            margin-bottom: 30px;
        }

        .crop-form h2 {
            margin-top: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: .85rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 12px;
            border: 1px solid rgba(0,0,0,.15);
            border-radius: 10px;
            font: inherit;
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        .form-help {
            margin-top: 5px;
            font-size: .72rem;
            opacity: .6;
        }

        .error-box {
            padding: 13px;
            margin-bottom: 18px;
            border-radius: 12px;
            background: rgba(180,0,0,.08);
        }

        .error-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .save-btn {
            width: 100%;
            border: 0;
            padding: 13px;
            border-radius: 11px;
            font-weight: 700;
            cursor: pointer;
        }

        .crop-list {
            display: grid;
            gap: 16px;
        }

        .crop-card {
            padding: 18px;
            border-radius: 18px;
        }

        .crop-card-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .crop-card h3 {
            margin: 0 0 5px;
        }

        .crop-meta {
            font-size: .75rem;
            opacity: .65;
        }

        .crop-badge {
            padding: 6px 9px;
            border-radius: 20px;
            font-size: .7rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .crop-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 9px;
        }

        .crop-detail {
            padding: 11px;
            border-radius: 10px;
            background: rgba(0,0,0,.04);
        }

        .crop-detail small {
            display: block;
            margin-bottom: 4px;
            opacity: .6;
            font-size: .65rem;
        }

        .crop-detail strong {
            font-size: .82rem;
        }

        .crop-observation {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid rgba(0,0,0,.08);
            font-size: .83rem;
            line-height: 1.5;
        }

        .empty-state {
            padding: 35px 20px;
            text-align: center;
            border-radius: 18px;
        }

        @media (min-width: 700px) {

            .crops-page {
                padding: 35px 25px 70px;
            }

            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .save-btn {
                width: auto;
                padding-left: 25px;
                padding-right: 25px;
            }

        }

    </style>

</head>

<body>

<?php
require_once __DIR__ . '/../includes/farmer_sidebar.php';
?>

<div class="farmer-dashboard-layout">

    <main class="crops-page">

        <section class="page-intro">

            <h1>
                🌱 My Crops
            </h1>

            <p>
                Record what is currently being grown on
                your farms. FIH will use this information
                together with weather, soil, water and
                other field information for future
                agricultural intelligence.
            </p>

        </section>


        <!-- ADD CROP -->

        <section class="profile-card crop-form">

            <h2>
                Record a Crop
            </h2>

            <?php if ($errors): ?>

                <div class="error-box">

                    <ul>

                        <?php foreach ($errors as $error): ?>

                            <li>
                                <?= e($error) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <form method="POST">

                <?= csrf_field() ?>


                <div class="form-grid">


                    <!-- FARM -->

                    <div class="form-group">

                        <label for="farm_id">
                            Farm
                        </label>

                        <select
                            id="farm_id"
                            name="farm_id"
                            required
                        >

                            <option value="">
                                Select farm
                            </option>

                            <?php foreach ($farms as $farm): ?>

                                <option
                                    value="<?= e($farm['id']) ?>"
                                    <?= $old['farm_id']
                                        === $farm['id']
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= e(
                                        $farm['farm_name']
                                        ?: 'Unnamed farm'
                                    ) ?>

                                    -
                                    <?= e(
                                        $farm['farm_size_acres']
                                    ) ?>
                                    acres

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- CROP -->

                    <div class="form-group">

                        <label for="crop_id">
                            Crop
                        </label>

                        <select
                            id="crop_id"
                            name="crop_id"
                            required
                        >

                            <option value="">
                                Select crop
                            </option>

                            <?php
                            $category = '';
                            ?>

                            <?php foreach ($crops as $crop): ?>

                                <?php if (
                                    $category
                                    !==
                                    $crop['category']
                                ): ?>

                                    <?php
                                    $category =
                                        $crop['category'];
                                    ?>

                                    <option disabled>
                                        —
                                        <?= e($category) ?>
                                        —
                                    </option>

                                <?php endif; ?>

                                <option
                                    value="<?= e($crop['id']) ?>"
                                    <?= $old['crop_id']
                                        === $crop['id']
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= e(
                                        $crop['name']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- SEASON -->

                    <div class="form-group">

                        <label for="season">
                            Season
                        </label>

                        <select
                            id="season"
                            name="season"
                            required
                        >

                            <option value="">
                                Select season
                            </option>

                            <option
                                value="Long rains"
                                <?= $old['season']
                                    === 'Long rains'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Long rains
                            </option>

                            <option
                                value="Short rains"
                                <?= $old['season']
                                    === 'Short rains'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Short rains
                            </option>

                            <option
                                value="Irrigated"
                                <?= $old['season']
                                    === 'Irrigated'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Irrigated
                            </option>

                            <option
                                value="Other"
                                <?= $old['season']
                                    === 'Other'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Other
                            </option>

                        </select>

                    </div>


                    <!-- AREA -->

                    <div class="form-group">

                        <label for="area_planted_acres">
                            Area planted (acres)
                        </label>

                        <input
                            type="number"
                            id="area_planted_acres"
                            name="area_planted_acres"
                            min="0.01"
                            step="0.01"
                            value="<?= e(
                                $old[
                                    'area_planted_acres'
                                ]
                            ) ?>"
                            required
                        >

                        <div class="form-help">
                            Cannot exceed the remaining
                            usable area of this farm for
                            the selected season.
                        </div>

                    </div>


                    <!-- PLANTING -->

                    <div class="form-group">

                        <label for="planting_date">
                            Planting date
                        </label>

                        <input
                            type="date"
                            id="planting_date"
                            name="planting_date"
                            value="<?= e(
                                $old[
                                    'planting_date'
                                ]
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- HARVEST START -->

                    <div class="form-group">

                        <label for="expected_harvest_start">
                            Expected harvest starts
                        </label>

                        <input
                            type="date"
                            id="expected_harvest_start"
                            name="expected_harvest_start"
                            value="<?= e(
                                $old[
                                    'expected_harvest_start'
                                ]
                            ) ?>"
                        >

                    </div>


                    <!-- HARVEST END -->

                    <div class="form-group">

                        <label for="expected_harvest_end">
                            Expected harvest ends
                        </label>

                        <input
                            type="date"
                            id="expected_harvest_end"
                            name="expected_harvest_end"
                            value="<?= e(
                                $old[
                                    'expected_harvest_end'
                                ]
                            ) ?>"
                        >

                    </div>


                    <!-- PREVIOUS CROP -->

                    <div class="form-group">

                        <label for="previous_crop_id">
                            Previous crop
                        </label>

                        <select
                            id="previous_crop_id"
                            name="previous_crop_id"
                        >

                            <option value="">
                                Not provided
                            </option>

                            <?php foreach ($crops as $crop): ?>

                                <option
                                    value="<?= e(
                                        $crop['id']
                                    ) ?>"
                                    <?= $old[
                                        'previous_crop_id'
                                    ]
                                    ===
                                    $crop['id']
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= e(
                                        $crop['name']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <div class="form-help">
                            Used later for crop rotation
                            intelligence.
                        </div>

                    </div>


                    <!-- VARIETY -->

                    <div class="form-group">

                        <label for="variety">
                            Variety
                            <span>
                                (optional)
                            </span>
                        </label>

                        <input
                            type="text"
                            id="variety"
                            name="variety"
                            value="<?= e(
                                $old['variety']
                            ) ?>"
                            maxlength="100"
                            placeholder="e.g. H614"
                        >

                    </div>

                </div>


                <!-- OBSERVATIONS -->

                <div class="form-group">

                    <label for="observations">
                        Relevant observations
                    </label>

                    <textarea
                        id="observations"
                        name="observations"
                        maxlength="2000"
                        placeholder="Anything relevant about this crop, planting conditions, unusual observations, etc."
                    ><?= e(
                        $old['observations']
                    ) ?></textarea>

                </div>


                <button
                    type="submit"
                    class="save-btn"
                >
                    Save Crop
                </button>

            </form>

        </section>


        <!-- EXISTING CROPS -->

        <section>

            <h2>
                Recorded Crops
            </h2>


            <?php if (!$records): ?>

                <div class="profile-card empty-state">

                    <div style="font-size:2rem;">
                        🌱
                    </div>

                    <h3>
                        No crop records yet
                    </h3>

                    <p>
                        Record what you are growing on
                        your farms.
                    </p>

                </div>

            <?php else: ?>


                <div class="crop-list">


                    <?php foreach ($records as $record): ?>

                        <article
                            class="profile-card crop-card"
                        >

                            <div
                                class="crop-card-header"
                            >

                                <div>

                                    <h3>

                                        🌱
                                        <?= e(
                                            $record[
                                                'crop_name'
                                            ]
                                        ) ?>

                                    </h3>

                                    <div
                                        class="crop-meta"
                                    >

                                        <?= e(
                                            $record[
                                                'crop_category'
                                            ]
                                        ) ?>

                                        ·

                                        <?= e(
                                            $record[
                                                'farm_name'
                                            ]
                                            ?: 'Unnamed farm'
                                        ) ?>

                                    </div>

                                </div>


                                <span
                                    class="crop-badge"
                                >

                                    <?= e(
                                        $record[
                                            'season'
                                        ]
                                    ) ?>

                                </span>

                            </div>


                            <div class="crop-details">


                                <div
                                    class="crop-detail"
                                >

                                    <small>
                                        AREA
                                    </small>

                                    <strong>

                                        <?= e(
                                            $record[
                                                'area_planted_acres'
                                            ]
                                        ) ?>

                                        acres

                                    </strong>

                                </div>


                                <div
                                    class="crop-detail"
                                >

                                    <small>
                                        PLANTED
                                    </small>

                                    <strong>

                                        <?= e(
                                            $record[
                                                'planting_date'
                                            ]
                                        ) ?>

                                    </strong>

                                </div>


                                <div
                                    class="crop-detail"
                                >

                                    <small>
                                        EXPECTED HARVEST
                                    </small>

                                    <strong>

                                        <?php if (
                                            $record[
                                                'expected_harvest_start'
                                            ]
                                        ): ?>

                                            <?= e(
                                                $record[
                                                    'expected_harvest_start'
                                                ]
                                            ) ?>

                                            <?php if (
                                                $record[
                                                    'expected_harvest_end'
                                                ]
                                            ): ?>

                                                →

                                                <?= e(
                                                    $record[
                                                        'expected_harvest_end'
                                                    ]
                                                ) ?>

                                            <?php endif; ?>

                                        <?php else: ?>

                                            Not provided

                                        <?php endif; ?>

                                    </strong>

                                </div>


                                <div
                                    class="crop-detail"
                                >

                                    <small>
                                        PREVIOUS CROP
                                    </small>

                                    <strong>

                                        <?= e(
                                            $record[
                                                'previous_crop_name'
                                            ]
                                            ?: 'Not provided'
                                        ) ?>

                                    </strong>

                                </div>


                            </div>


                            <?php if (
                                $record['variety']
                                ||
                                $record['observations']
                            ): ?>

                                <div
                                    class="crop-observation"
                                >

                                    <?php if (
                                        $record['variety']
                                    ): ?>

                                        <strong>
                                            Variety:
                                        </strong>

                                        <?= e(
                                            $record[
                                                'variety'
                                            ]
                                        ) ?>

                                        <br>

                                    <?php endif; ?>


                                    <?php if (
                                        $record[
                                            'observations'
                                        ]
                                    ): ?>

                                        <strong>
                                            Observation:
                                        </strong>

                                        <?= e(
                                            $record[
                                                'observations'
                                            ]
                                        ) ?>

                                    <?php endif; ?>

                                </div>

                            <?php endif; ?>


                        </article>

                    <?php endforeach; ?>


                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>

</html>