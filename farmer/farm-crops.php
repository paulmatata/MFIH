<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_farmer();

$user_id = authenticated_farmer_id();

$farm_id = trim($_GET['farm_id'] ?? '');

if ($farm_id === '') {
    redirect('/farmer/farm-data.php');
}


/*
|--------------------------------------------------------------------------
| Get farm
|--------------------------------------------------------------------------
*/

$farm_stmt = $pdo->prepare("
    SELECT
        id,
        farm_name,
        farm_size_acres,
        water_source,
        irrigation
    FROM farmer_profiles
    WHERE id = ?
      AND user_id = ?
    LIMIT 1
");

$farm_stmt->execute([
    $farm_id,
    $user_id
]);

$farm = $farm_stmt->fetch(PDO::FETCH_ASSOC);

if (!$farm) {
    http_response_code(404);
    exit('Farm not found.');
}


/*
|--------------------------------------------------------------------------
| Determine current season
|--------------------------------------------------------------------------
|
| We keep this simple for now.
| Later the season can be determined from
| agricultural calendars/data rather than
| being manually selected.
|
*/

$current_month = (int) date('n');

if ($current_month >= 3 && $current_month <= 5) {

    $current_season = 'Long rains';

} elseif ($current_month >= 10 && $current_month <= 12) {

    $current_season = 'Short rains';

} else {

    $current_season = 'Other';

}


/*
|--------------------------------------------------------------------------
| Get current-season crops
|--------------------------------------------------------------------------
*/

$crop_stmt = $pdo->prepare("
    SELECT
        cr.id,
        cr.crop_id,
        cr.season,
        cr.area_planted_acres,
        cr.planting_date,
        cr.expected_harvest_start,
        cr.expected_harvest_end,
        cr.variety,
        cr.observations,

        c.name AS crop_name,
        c.category AS crop_category

    FROM crop_records cr

    INNER JOIN crops c
        ON c.id = cr.crop_id

    WHERE cr.farm_id = ?
      AND cr.season = ?

    ORDER BY cr.planting_date DESC
");

$crop_stmt->execute([
    $farm_id,
    $current_season
]);

$current_crops = $crop_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Calculate allocated land
|--------------------------------------------------------------------------
*/

$allocated_stmt = $pdo->prepare("
    SELECT
        COALESCE(
            SUM(area_planted_acres),
            0
        )
    FROM crop_records
    WHERE farm_id = ?
      AND season = ?
");

$allocated_stmt->execute([
    $farm_id,
    $current_season
]);

$allocated_area =
    (float) $allocated_stmt->fetchColumn();

$farm_size =
    (float) $farm['farm_size_acres'];

$remaining_area =
    max(
        $farm_size - $allocated_area,
        0
    );


/*
|--------------------------------------------------------------------------
| Get all crop records for history
|--------------------------------------------------------------------------
*/

$history_stmt = $pdo->prepare("
    SELECT
        cr.season,
        cr.area_planted_acres,
        cr.planting_date,
        cr.expected_harvest_start,
        cr.expected_harvest_end,
        cr.variety,
        c.name AS crop_name

    FROM crop_records cr

    INNER JOIN crops c
        ON c.id = cr.crop_id

    WHERE cr.farm_id = ?

    ORDER BY cr.planting_date DESC
");

$history_stmt->execute([
    $farm_id
]);

$history =
    $history_stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <?= e($farm['farm_name'] ?: 'My Farm') ?>
        | FIH
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

        .farm-crops-page {
            max-width: 1000px;
            margin: auto;
            padding: 20px 14px 70px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 18px;
            text-decoration: none;
            font-weight: 600;
        }

        .farm-header {
            padding: 20px;
            border-radius: 18px;
            margin-bottom: 18px;
        }

        .farm-header h1 {
            margin: 0 0 6px;
        }

        .farm-header p {
            margin: 0;
            opacity: .7;
        }

        .land-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 18px;
        }

        .land-box {
            padding: 12px 8px;
            border-radius: 12px;
            background: rgba(0,0,0,.04);
            text-align: center;
        }

        .land-box small {
            display: block;
            font-size: .65rem;
            opacity: .6;
            margin-bottom: 4px;
        }

        .land-box strong {
            font-size: .95rem;
        }

        .section-title {
            margin: 28px 0 14px;
        }

        .crop-grid {
            display: grid;
            gap: 14px;
        }

        .crop-card {
            padding: 18px;
            border-radius: 17px;
        }

        .crop-card-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }

        .crop-card h3 {
            margin: 0 0 4px;
        }

        .crop-category {
            font-size: .72rem;
            opacity: .6;
        }

        .crop-area {
            font-size: 1.15rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .crop-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 16px;
        }

        .crop-info div {
            padding: 10px;
            background: rgba(0,0,0,.04);
            border-radius: 10px;
        }

        .crop-info small {
            display: block;
            font-size: .62rem;
            opacity: .6;
            margin-bottom: 3px;
        }

        .crop-info strong {
            font-size: .78rem;
        }

        .empty-state {
            padding: 30px 20px;
            text-align: center;
            border-radius: 17px;
        }

        .history-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            padding: 13px 0;
            border-bottom: 1px solid rgba(0,0,0,.08);
        }

        .history-row:last-child {
            border-bottom: 0;
        }

        .history-row small {
            display: block;
            opacity: .6;
            font-size: .65rem;
        }

        @media (min-width: 700px) {

            .farm-crops-page {
                padding: 35px 25px 70px;
            }

            .crop-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

    </style>

</head>

<body>

<?php
require_once __DIR__ . '/../includes/farmer_sidebar.php';
?>

<div class="farmer-dashboard-layout">

    <main class="farm-crops-page">

        <a
            href="<?= e(FIH_BASE_URL) ?>/farmer/farm-data.php"
            class="back-link"
        >
            ← My Farms
        </a>


        <!-- FARM HEADER -->

        <section class="profile-card farm-header">

            <h1>
                🌾
                <?= e(
                    $farm['farm_name']
                    ?: 'My Farm'
                ) ?>
            </h1>

            <p>
                <?= e(
                    number_format(
                        $farm_size,
                        2
                    )
                ) ?>
                acres
            </p>


            <div class="land-summary">

                <div class="land-box">

                    <small>
                        FARM SIZE
                    </small>

                    <strong>
                        <?= e(
                            number_format(
                                $farm_size,
                                2
                            )
                        ) ?>
                        ac
                    </strong>

                </div>


                <div class="land-box">

                    <small>
                        <?= e($current_season) ?>
                    </small>

                    <strong>
                        <?= e(
                            number_format(
                                $allocated_area,
                                2
                            )
                        ) ?>
                        ac
                    </strong>

                </div>


                <div class="land-box">

                    <small>
                        REMAINING
                    </small>

                    <strong>
                        <?= e(
                            number_format(
                                $remaining_area,
                                2
                            )
                        ) ?>
                        ac
                    </strong>

                </div>

            </div>

        </section>


        <!-- CURRENT CROPS -->

        <h2 class="section-title">

            Current crops

        </h2>


        <?php if (!$current_crops): ?>

            <div class="profile-card empty-state">

                <div style="font-size:2rem;">
                    🌱
                </div>

                <h3>
                    No crops recorded for
                    <?= e($current_season) ?>
                </h3>

                <p>
                    Record a crop so FIH can begin
                    building your farm's agricultural
                    data history.
                </p>

                <a
                    href="<?= e(FIH_BASE_URL) ?>/farmer/crops.php"
                >
                    Add crop
                </a>

            </div>

        <?php else: ?>


            <div class="crop-grid">

                <?php foreach (
                    $current_crops
                    as $crop
                ): ?>

                    <article
                        class="profile-card crop-card"
                    >

                        <div class="crop-card-top">

                            <div>

                                <h3>
                                    🌱
                                    <?= e(
                                        $crop[
                                            'crop_name'
                                        ]
                                    ) ?>
                                </h3>

                                <div
                                    class="crop-category"
                                >
                                    <?= e(
                                        $crop[
                                            'crop_category'
                                        ]
                                    ) ?>
                                </div>

                            </div>


                            <div class="crop-area">

                                <?= e(
                                    number_format(
                                        (float)
                                        $crop[
                                            'area_planted_acres'
                                        ],
                                        2
                                    )
                                ) ?>

                                ac

                            </div>

                        </div>


                        <div class="crop-info">

                            <div>

                                <small>
                                    PLANTED
                                </small>

                                <strong>

                                    <?= e(
                                        $crop[
                                            'planting_date'
                                        ]
                                    ) ?>

                                </strong>

                            </div>


                            <div>

                                <small>
                                    HARVEST
                                </small>

                                <strong>

                                    <?php if (
                                        $crop[
                                            'expected_harvest_start'
                                        ]
                                    ): ?>

                                        <?= e(
                                            $crop[
                                                'expected_harvest_start'
                                            ]
                                        ) ?>

                                    <?php else: ?>

                                        Not set

                                    <?php endif; ?>

                                </strong>

                            </div>


                            <div>

                                <small>
                                    VARIETY
                                </small>

                                <strong>

                                    <?= e(
                                        $crop['variety']
                                        ?: 'Not provided'
                                    ) ?>

                                </strong>

                            </div>


                            <div>

                                <small>
                                    SEASON
                                </small>

                                <strong>
                                    <?= e(
                                        $crop[
                                            'season'
                                        ]
                                    ) ?>
                                </strong>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- HISTORY -->

        <h2 class="section-title">

            Crop history

        </h2>


        <section class="profile-card">

            <?php if (!$history): ?>

                <p>
                    No crop history yet.
                </p>

            <?php else: ?>

                <?php foreach (
                    $history
                    as $item
                ): ?>

                    <div class="history-row">

                        <div>

                            <strong>
                                <?= e(
                                    $item[
                                        'crop_name'
                                    ]
                                ) ?>
                            </strong>

                            <small>
                                <?= e(
                                    $item[
                                        'season'
                                    ]
                                ) ?>
                            </small>

                        </div>


                        <div>

                            <strong>

                                <?= e(
                                    number_format(
                                        (float)
                                        $item[
                                            'area_planted_acres'
                                        ],
                                        2
                                    )
                                ) ?>

                                acres

                            </strong>

                            <small>

                                Planted:
                                <?= e(
                                    $item[
                                        'planting_date'
                                    ]
                                ) ?>

                            </small>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>

</html>