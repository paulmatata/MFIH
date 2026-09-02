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
| Load farmer's crop records
|--------------------------------------------------------------------------
*/

$crop_stmt = $pdo->prepare("
    SELECT
        cr.id,
        cr.farm_id,
        cr.crop_id,
        cr.season,
        cr.area_planted_acres,
        cr.planting_date,
        cr.expected_harvest_start,
        cr.expected_harvest_end,

        c.name AS crop_name,

        fp.farm_name

    FROM crop_records cr

    INNER JOIN crops c
        ON c.id = cr.crop_id

    INNER JOIN farmer_profiles fp
        ON fp.id = cr.farm_id

    WHERE fp.user_id = ?

    ORDER BY cr.planting_date DESC
");

$crop_stmt->execute([
    $user_id
]);

$crop_records =
    $crop_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Errors
|--------------------------------------------------------------------------
*/

$errors = [];

$old = [
    'crop_record_id' => '',
    'harvest_date' => date('Y-m-d'),
    'quantity' => '',
    'unit' => 'kg',
    'quality_notes' => '',
    'observations' => ''
];


/*
|--------------------------------------------------------------------------
| Save harvest
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($old as $key => $value) {

        if ($key === 'harvest_date') {
            continue;
        }

        $old[$key] =
            trim($_POST[$key] ?? '');
    }

    $old['harvest_date'] =
        trim(
            $_POST['harvest_date'] ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | Required fields
    |--------------------------------------------------------------------------
    */

    if ($old['crop_record_id'] === '') {
        $errors[] =
            'Please select the crop.';
    }

    if ($old['harvest_date'] === '') {
        $errors[] =
            'Please enter the harvest date.';
    }

    if ($old['quantity'] === '') {
        $errors[] =
            'Please enter the harvested quantity.';
    }

    if (
        $old['quantity'] !== ''
        &&
        (
            !is_numeric($old['quantity'])
            ||
            (float)$old['quantity'] <= 0
        )
    ) {
        $errors[] =
            'Harvest quantity must be greater than zero.';
    }


    /*
    |--------------------------------------------------------------------------
    | Verify crop belongs to farmer
    |--------------------------------------------------------------------------
    */

    $crop_record = null;

    if (
        !$errors &&
        $old['crop_record_id'] !== ''
    ) {

        $verify_stmt = $pdo->prepare("
            SELECT
                cr.id,
                cr.crop_id,
                cr.planting_date,

                c.name AS crop_name,

                fp.farm_name

            FROM crop_records cr

            INNER JOIN crops c
                ON c.id = cr.crop_id

            INNER JOIN farmer_profiles fp
                ON fp.id = cr.farm_id

            WHERE cr.id = ?
              AND fp.user_id = ?

            LIMIT 1
        ");

        $verify_stmt->execute([
            $old['crop_record_id'],
            $user_id
        ]);

        $crop_record =
            $verify_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$crop_record) {

            $errors[] =
                'The selected crop could not be found.';

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate harvest date
    |--------------------------------------------------------------------------
    */

    if (
        !$errors &&
        $crop_record &&
        $old['harvest_date'] <
        $crop_record['planting_date']
    ) {

        $errors[] =
            'Harvest date cannot be before the planting date.';
    }


    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $harvest_id =
            generate_uuid();

        $insert = $pdo->prepare("
            INSERT INTO crop_harvests (
                id,
                crop_record_id,
                harvest_date,
                quantity,
                unit,
                quality_notes,
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
                NOW(),
                NOW()
            )
        ");

        $insert->execute([
            $harvest_id,
            $old['crop_record_id'],
            $old['harvest_date'],
            $old['quantity'],
            $old['unit'],
            $old['quality_notes'] !== ''
                ? $old['quality_notes']
                : null,
            $old['observations'] !== ''
                ? $old['observations']
                : null
        ]);

        redirect('/farmer/harvests.php');
    }
}


/*
|--------------------------------------------------------------------------
| Load harvest history
|--------------------------------------------------------------------------
*/

$harvest_stmt = $pdo->prepare("
    SELECT
        ch.id,
        ch.harvest_date,
        ch.quantity,
        ch.unit,
        ch.quality_notes,
        ch.observations,

        c.name AS crop_name,

        cr.season,
        cr.area_planted_acres,

        fp.farm_name

    FROM crop_harvests ch

    INNER JOIN crop_records cr
        ON cr.id = ch.crop_record_id

    INNER JOIN crops c
        ON c.id = cr.crop_id

    INNER JOIN farmer_profiles fp
        ON fp.id = cr.farm_id

    WHERE fp.user_id = ?

    ORDER BY ch.harvest_date DESC
");

$harvest_stmt->execute([
    $user_id
]);

$harvests =
    $harvest_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Harvest Reports | FIH
    </title>

    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/farmer.css"
    >

    <style>

        .harvest-page {
            max-width: 950px;
            margin: auto;
            padding: 20px 14px 70px;
        }

        .intro {
            margin-bottom: 22px;
        }

        .intro p {
            line-height: 1.6;
            opacity: .7;
        }

        .harvest-form {
            padding: 20px;
            border-radius: 18px;
            margin-bottom: 30px;
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
            font-size: .84rem;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 12px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,.15);
            font: inherit;
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
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

        .submit-btn {
            width: 100%;
            border: 0;
            padding: 13px;
            border-radius: 11px;
            font-weight: 700;
            cursor: pointer;
        }

        .harvest-list {
            display: grid;
            gap: 14px;
        }

        .harvest-card {
            padding: 18px;
            border-radius: 17px;
        }

        .harvest-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .harvest-top h3 {
            margin: 0 0 5px;
        }

        .harvest-farm {
            font-size: .72rem;
            opacity: .6;
        }

        .harvest-quantity {
            font-size: 1.15rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .harvest-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 15px;
        }

        .detail {
            padding: 10px;
            border-radius: 10px;
            background: rgba(0,0,0,.04);
        }

        .detail small {
            display: block;
            font-size: .62rem;
            opacity: .6;
            margin-bottom: 4px;
        }

        .detail strong {
            font-size: .8rem;
        }

        .notes {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid rgba(0,0,0,.08);
            font-size: .8rem;
            line-height: 1.5;
        }

        @media (min-width: 700px) {

            .harvest-page {
                padding: 35px 25px 70px;
            }

            .form-grid {
                grid-template-columns: 1fr 1fr;
            }

            .submit-btn {
                width: auto;
                padding-left: 28px;
                padding-right: 28px;
            }

        }

    </style>

</head>

<body>

<?php
require_once __DIR__ . '/../includes/farmer_sidebar.php';
?>

<div class="farmer-dashboard-layout">

    <main class="harvest-page">

        <section class="intro">

            <h1>
                🌾 Harvest Reports
            </h1>

            <p>
                Record what you actually harvested.
                FIH will use these observations to
                compare real production with future
                production estimates.
            </p>

        </section>


        <section class="profile-card harvest-form">

            <h2>
                Report a Harvest
            </h2>


            <?php if ($errors): ?>

                <div class="error-box">

                    <ul>

                        <?php foreach (
                            $errors
                            as $error
                        ): ?>

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


                    <!-- CROP -->

                    <div class="form-group">

                        <label for="crop_record_id">
                            Crop
                        </label>

                        <select
                            id="crop_record_id"
                            name="crop_record_id"
                            required
                        >

                            <option value="">
                                Select crop
                            </option>

                            <?php foreach (
                                $crop_records
                                as $crop
                            ): ?>

                                <option
                                    value="<?= e(
                                        $crop['id']
                                    ) ?>"
                                    <?= $old[
                                        'crop_record_id'
                                    ]
                                    ===
                                    $crop['id']
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= e(
                                        $crop['crop_name']
                                    ) ?>

                                    -
                                    <?= e(
                                        $crop['farm_name']
                                        ?: 'Farm'
                                    ) ?>

                                    (<?= e(
                                        $crop['season']
                                    ) ?>)

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- DATE -->

                    <div class="form-group">

                        <label for="harvest_date">
                            Harvest date
                        </label>

                        <input
                            type="date"
                            id="harvest_date"
                            name="harvest_date"
                            value="<?= e(
                                $old[
                                    'harvest_date'
                                ]
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- QUANTITY -->

                    <div class="form-group">

                        <label for="quantity">
                            Quantity harvested
                        </label>

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            min="0.01"
                            step="0.01"
                            value="<?= e(
                                $old[
                                    'quantity'
                                ]
                            ) ?>"
                            placeholder="e.g. 450"
                            required
                        >

                    </div>


                    <!-- UNIT -->

                    <div class="form-group">

                        <label for="unit">
                            Unit
                        </label>

                        <select
                            id="unit"
                            name="unit"
                            required
                        >

                            <option
                                value="kg"
                                <?= $old['unit']
                                    === 'kg'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Kilograms (kg)
                            </option>

                            <option
                                value="tonnes"
                                <?= $old['unit']
                                    === 'tonnes'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Tonnes
                            </option>

                            <option
                                value="bags"
                                <?= $old['unit']
                                    === 'bags'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Bags
                            </option>

                            <option
                                value="units"
                                <?= $old['unit']
                                    === 'units'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Units
                            </option>

                        </select>

                    </div>

                </div>


                <!-- QUALITY -->

                <div class="form-group">

                    <label for="quality_notes">
                        Quality notes
                    </label>

                    <textarea
                        id="quality_notes"
                        name="quality_notes"
                        maxlength="2000"
                        placeholder="Optional information about quality, losses, damaged produce, etc."
                    ><?= e(
                        $old[
                            'quality_notes'
                        ]
                    ) ?></textarea>

                </div>


                <!-- OBSERVATIONS -->

                <div class="form-group">

                    <label for="observations">
                        Harvest observations
                    </label>

                    <textarea
                        id="observations"
                        name="observations"
                        maxlength="2000"
                        placeholder="Anything unusual about the harvest?"
                    ><?= e(
                        $old[
                            'observations'
                        ]
                    ) ?></textarea>

                </div>


                <button
                    type="submit"
                    class="submit-btn"
                >
                    Save Harvest Report
                </button>

            </form>

        </section>


        <!-- HISTORY -->

        <section>

            <h2>
                Harvest History
            </h2>


            <?php if (!$harvests): ?>

                <div
                    class="profile-card"
                    style="
                        padding:30px;
                        text-align:center;
                        border-radius:17px;
                    "
                >

                    <div style="font-size:2rem;">
                        🌾
                    </div>

                    <h3>
                        No harvest reports yet
                    </h3>

                    <p>
                        Your actual harvest records
                        will become valuable historical
                        production data for FIH.
                    </p>

                </div>

            <?php else: ?>


                <div class="harvest-list">

                    <?php foreach (
                        $harvests
                        as $harvest
                    ): ?>

                        <article
                            class="profile-card harvest-card"
                        >

                            <div class="harvest-top">

                                <div>

                                    <h3>

                                        🌾
                                        <?= e(
                                            $harvest[
                                                'crop_name'
                                            ]
                                        ) ?>

                                    </h3>

                                    <div
                                        class="harvest-farm"
                                    >

                                        <?= e(
                                            $harvest[
                                                'farm_name'
                                            ]
                                            ?: 'Farm'
                                        ) ?>

                                        ·

                                        <?= e(
                                            $harvest[
                                                'season'
                                            ]
                                        ) ?>

                                    </div>

                                </div>


                                <div
                                    class="harvest-quantity"
                                >

                                    <?= e(
                                        number_format(
                                            (float)
                                            $harvest[
                                                'quantity'
                                            ],
                                            2
                                        )
                                    ) ?>

                                    <?= e(
                                        $harvest[
                                            'unit'
                                        ]
                                    ) ?>

                                </div>

                            </div>


                            <div class="harvest-details">

                                <div class="detail">

                                    <small>
                                        HARVEST DATE
                                    </small>

                                    <strong>
                                        <?= e(
                                            $harvest[
                                                'harvest_date'
                                            ]
                                        ) ?>
                                    </strong>

                                </div>


                                <div class="detail">

                                    <small>
                                        AREA PLANTED
                                    </small>

                                    <strong>

                                        <?= e(
                                            number_format(
                                                (float)
                                                $harvest[
                                                    'area_planted_acres'
                                                ],
                                                2
                                            )
                                        ) ?>

                                        acres

                                    </strong>

                                </div>

                            </div>


                            <?php if (
                                $harvest[
                                    'quality_notes'
                                ]
                                ||
                                $harvest[
                                    'observations'
                                ]
                            ): ?>

                                <div class="notes">

                                    <?php if (
                                        $harvest[
                                            'quality_notes'
                                        ]
                                    ): ?>

                                        <strong>
                                            Quality:
                                        </strong>

                                        <?= e(
                                            $harvest[
                                                'quality_notes'
                                            ]
                                        ) ?>

                                        <br>

                                    <?php endif; ?>


                                    <?php if (
                                        $harvest[
                                            'observations'
                                        ]
                                    ): ?>

                                        <strong>
                                            Observation:
                                        </strong>

                                        <?= e(
                                            $harvest[
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