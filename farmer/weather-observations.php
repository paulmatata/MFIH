<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/security.php';

require_farmer();

$user_id = authenticated_farmer_id();

if (!$user_id) {
    redirect('/login.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Get farmer's weather observations
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        wo.id,
        wo.farm_id,
        wo.latitude,
        wo.longitude,
        wo.observed_at,
        wo.predicted_condition,
        wo.observed_condition,
        wo.farmer_comment,
        fp.farm_name
    FROM weather_observations wo

    LEFT JOIN farmer_profiles fp
        ON fp.id = wo.farm_id

    WHERE wo.user_id = :user_id

    ORDER BY wo.observed_at DESC
");

$stmt->execute([
    'user_id' => $user_id
]);

$observations = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Simple comparison helper
|--------------------------------------------------------------------------
|
| This is NOT a weather accuracy algorithm.
| It only tells the farmer whether the two
| recorded conditions are identical.
|
|--------------------------------------------------------------------------
*/

function observation_status(
    ?string $predicted,
    ?string $observed
): string {

    if (
        !$predicted ||
        !$observed
    ) {
        return 'No comparison';
    }

    if (
        strtolower(trim($predicted))
        ===
        strtolower(trim($observed))
    ) {
        return 'Match';
    }

    return 'Different';
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

    <title>
        Weather Observations | FIH
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

        .observations-page {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px 14px 60px;
        }


        .observations-header {
            margin-bottom: 22px;
        }


        .observations-header h1 {
            margin: 0 0 8px;
        }


        .observations-header p {
            margin: 0;
            line-height: 1.5;
        }


        .observation-list {
            display: grid;
            gap: 15px;
        }


        .observation-record {
            padding: 18px;
            border-radius: 18px;
        }


        .observation-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
        }


        .observation-farm {
            font-weight: 700;
        }


        .observation-date {
            margin-top: 4px;
            font-size: .78rem;
            opacity: .7;
        }


        .observation-status {
            padding: 6px 10px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            white-space: nowrap;
        }


        .status-match {
            background: rgba(0, 128, 0, .10);
        }


        .status-different {
            background: rgba(255, 165, 0, .15);
        }


        .status-none {
            background: rgba(0, 0, 0, .06);
        }


        .weather-comparison {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 10px;
        }


        .comparison-box {
            padding: 13px;
            border-radius: 12px;
            background: rgba(0, 0, 0, .04);
        }


        .comparison-label {
            display: block;
            margin-bottom: 5px;
            font-size: .72rem;
            opacity: .7;
        }


        .comparison-value {
            font-weight: 700;
            font-size: .92rem;
        }


        .observation-comment {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid rgba(0, 0, 0, .08);
            font-size: .85rem;
            line-height: 1.5;
        }


        .observation-location {
            margin-top: 12px;
            font-size: .72rem;
            opacity: .65;
        }


        .empty-observations {
            text-align: center;
            padding: 35px 20px;
            border-radius: 18px;
        }


        .empty-observations p {
            line-height: 1.5;
        }


        @media (max-width: 480px) {

            .observation-top {
                flex-direction: column;
            }

            .weather-comparison {
                grid-template-columns: 1fr;
            }

        }


        @media (min-width: 700px) {

            .observations-page {
                padding: 35px 25px 70px;
            }

            .observation-record {
                padding: 22px;
            }

        }

    </style>

</head>


<body>


<?php

require_once __DIR__ . '/../includes/farmer_sidebar.php';

?>


<main class="farmer-dashboard-layout">


    <section class="observations-page">


        <header class="observations-header">

            <p class="eyebrow">
                WEATHER INTELLIGENCE DATA
            </p>

            <h1>
                My Weather Observations
            </h1>

            <p>
                These are the weather conditions you have
                reported from your farms. FIH keeps your
                observations so they can be compared with
                predicted conditions over time.
            </p>

        </header>


        <?php if (empty($observations)): ?>


            <section class="profile-card empty-observations">

                <h2>
                    No observations yet
                </h2>

                <p>
                    When you report the weather from one of
                    your farms, your observations will appear
                    here.
                </p>

            </section>


        <?php else: ?>


            <div class="observation-list">


                <?php foreach ($observations as $observation): ?>


                    <?php

                    $status =
                        observation_status(
                            $observation['predicted_condition'],
                            $observation['observed_condition']
                        );

                    $status_class =
                        match ($status) {

                            'Match'
                                => 'status-match',

                            'Different'
                                => 'status-different',

                            default
                                => 'status-none'

                        };

                    ?>


                    <article
                        class="profile-card observation-record"
                    >


                        <div class="observation-top">


                            <div>

                                <div class="observation-farm">

                                    🌾
                                    <?= e(
                                        $observation['farm_name']
                                        ?: 'My Farm'
                                    ) ?>

                                </div>


                                <div class="observation-date">

                                    <?= e(
                                        date(
                                            'd M Y, H:i',
                                            strtotime(
                                                $observation['observed_at']
                                            )
                                        )
                                    ) ?>

                                </div>

                            </div>


                            <span
                                class="observation-status
                                <?= e($status_class) ?>"
                            >

                                <?= e($status) ?>

                            </span>


                        </div>


                        <div class="weather-comparison">


                            <div class="comparison-box">

                                <span
                                    class="comparison-label"
                                >
                                    FIH PREDICTION
                                </span>


                                <span
                                    class="comparison-value"
                                >

                                    🌦️

                                    <?= e(
                                        $observation[
                                            'predicted_condition'
                                        ]
                                        ?: 'Not recorded'
                                    ) ?>

                                </span>

                            </div>


                            <div class="comparison-box">

                                <span
                                    class="comparison-label"
                                >
                                    YOUR OBSERVATION
                                </span>


                                <span
                                    class="comparison-value"
                                >

                                    👁️

                                    <?= e(
                                        $observation[
                                            'observed_condition'
                                        ]
                                    ) ?>

                                </span>

                            </div>


                        </div>


                        <?php if (
                            !empty(
                                $observation['farmer_comment']
                            )
                        ): ?>


                            <div class="observation-comment">

                                <strong>
                                    Your note:
                                </strong>

                                <?= e(
                                    $observation['farmer_comment']
                                ) ?>

                            </div>


                        <?php endif; ?>


                        <div class="observation-location">

                            📍

                            <?= e(
                                $observation['latitude']
                            ) ?>,

                            <?= e(
                                $observation['longitude']
                            ) ?>

                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </section>


</main>


</body>

</html>