<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_farmer();

$user_id = authenticated_farmer_id();

if (!$user_id) {
    redirect('/login.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Load farmer farms
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        farm_name,
        farm_size_acres,
        water_source,
        irrigation,
        created_at
    FROM farmer_profiles
    WHERE user_id = :user_id
    ORDER BY created_at ASC
");

$stmt->execute([
    'user_id' => $user_id
]);

$farms = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Load farm coordinates
|--------------------------------------------------------------------------
*/

$coordinate_stmt = $pdo->prepare("
    SELECT
        farm_id,
        latitude,
        longitude,
        accuracy_meters
    FROM coordinates
    WHERE user_id = :user_id
      AND source = 'farm_location'
");

$coordinate_stmt->execute([
    'user_id' => $user_id
]);

$farm_coordinates = [];

foreach ($coordinate_stmt->fetchAll(PDO::FETCH_ASSOC) as $coordinate) {

    $farm_coordinates[$coordinate['farm_id']] = $coordinate;
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

    <title>My Farms | FIH</title>


    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/farmer.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | MY FARMS
        |--------------------------------------------------------------------------
        */

        .my-farms-page {

            width: 100%;

            max-width: 1000px;

            margin: 0 auto;

            padding: 20px 14px 50px;

        }


        .my-farms-header {

            margin-bottom: 25px;

        }


        .my-farms-header .eyebrow {

            margin: 0 0 6px;

            font-size: .8rem;

            font-weight: 600;

        }


        .my-farms-header h1 {

            margin: 0 0 8px;

        }


        .my-farms-header p {

            margin: 0;

            line-height: 1.5;

        }


        /*
        |--------------------------------------------------------------------------
        | FARM LIST
        |--------------------------------------------------------------------------
        */

        .farms-list {

            display: flex;

            flex-direction: column;

            gap: 16px;

        }


        /*
        |--------------------------------------------------------------------------
        | FARM CARD
        |--------------------------------------------------------------------------
        */

        .farm-card {

            width: 100%;

            overflow: hidden;

            border-radius: 18px;

        }


        .farm-card-header {

            padding: 18px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

        }


        .farm-title-area {

            min-width: 0;

        }


        .farm-title {

            margin: 0;

            font-size: 1.15rem;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .farm-location-status {

            margin-top: 5px;

            font-size: .8rem;

        }


        .farm-status {

            flex: 0 0 auto;

            padding: 6px 9px;

            border-radius: 20px;

            font-size: .72rem;

            font-weight: 600;

        }


        .farm-status.registered {

            background: rgba(0, 0, 0, .07);

        }


        .farm-status.missing {

            background: rgba(0, 0, 0, .07);

        }


        /*
        |--------------------------------------------------------------------------
        | WEATHER SNAPSHOT
        |--------------------------------------------------------------------------
        */

        .farm-weather {

            padding: 0 18px 18px;

        }


        .weather-loading {

            padding: 16px;

            border-radius: 14px;

            background: rgba(0, 0, 0, .04);

            font-size: .88rem;

        }


        .weather-error {

            padding: 15px;

            border-radius: 14px;

            background: rgba(0, 0, 0, .04);

            font-size: .88rem;

        }


        .weather-current {

            padding: 16px;

            border-radius: 16px;

            background: rgba(0, 0, 0, .04);

        }


        .weather-current-top {

            display: flex;

            align-items: center;

            gap: 15px;

            margin-bottom: 15px;

        }


        .weather-icon {

            font-size: 2.8rem;

            line-height: 1;

        }


        .weather-temperature {

            font-size: 2rem;

            font-weight: 700;

            line-height: 1.1;

        }


        .weather-condition {

            margin-top: 4px;

            font-size: .85rem;

        }


        .weather-details {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 8px;

        }


        .weather-detail {

            padding: 10px;

            border-radius: 10px;

            background: rgba(255, 255, 255, .35);

        }


        .weather-detail span {

            display: block;

            font-size: .7rem;

            margin-bottom: 3px;

        }


        .weather-detail strong {

            font-size: .86rem;

        }


        /*
        |--------------------------------------------------------------------------
        | FORECAST
        |--------------------------------------------------------------------------
        */

        .farm-forecast {

            margin-top: 14px;

        }


        .farm-forecast-title {

            margin: 0 0 9px;

            font-size: .88rem;

        }


        .farm-forecast-list {

            display: flex;

            gap: 9px;

            overflow-x: auto;

            padding-bottom: 5px;

            -webkit-overflow-scrolling: touch;

        }


        .farm-forecast-day {

            flex: 0 0 110px;

            padding: 11px 9px;

            border-radius: 12px;

            background: rgba(0, 0, 0, .04);

            text-align: center;

        }


        .farm-forecast-date {

            font-size: .7rem;

            margin-bottom: 7px;

        }


        .farm-forecast-icon {

            font-size: 1.7rem;

            margin-bottom: 6px;

        }


        .farm-forecast-temp {

            font-size: .76rem;

            font-weight: 600;

            white-space: nowrap;

        }


        .farm-forecast-rain {

            margin-top: 4px;

            font-size: .68rem;

        }


        /*
        |--------------------------------------------------------------------------
        | NO LOCATION
        |--------------------------------------------------------------------------
        */

        .farm-no-location {

            padding: 16px;

            border-radius: 15px;

            background: rgba(0, 0, 0, .04);

        }


        .farm-no-location p {

            margin: 0 0 12px;

            line-height: 1.5;

            font-size: .88rem;

        }


        /*
        |--------------------------------------------------------------------------
        | FARM ACTIONS
        |--------------------------------------------------------------------------
        */

        .farm-actions {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 9px;

            margin-top: 14px;

        }


        .farm-action {

            display: flex;

            align-items: center;

            justify-content: center;

            min-height: 44px;

            padding: 9px;

            border-radius: 10px;

            text-decoration: none;

            font-size: .78rem;

            font-weight: 600;

            text-align: center;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .farms-empty {

            padding: 30px 20px;

            text-align: center;

            border-radius: 16px;

        }


        .farms-empty h2 {

            margin-top: 0;

        }


        /*
        |--------------------------------------------------------------------------
        | DESKTOP
        |--------------------------------------------------------------------------
        */

        @media (min-width: 700px) {

            .my-farms-page {

                padding: 35px 25px 60px;

            }


            .farms-list {

                display: grid;

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

                align-items: start;

            }


            .farm-card-header {

                padding: 20px;

            }


            .farm-weather {

                padding: 0 20px 20px;

            }
}

  /*report weather observations */ 
.farm-weather-report {
    margin-top: 14px;
}


.farm-weather-report .farm-action {
    width: 100%;
}
    </style>

</head>


<body>


<?php

require_once __DIR__ . '/../includes/farmer_sidebar.php';

?>


<main class="farmer-dashboard-layout">


    <section class="my-farms-page">


        <header class="my-farms-header">

            <p class="eyebrow">
                FARM MANAGEMENT
            </p>

            <h1>
                My Farms
            </h1>

            <p>
                Manage your farms and view local weather
                conditions for each registered location.
            </p>

        </header>



        <?php if (empty($farms)): ?>


            <section class="profile-card farms-empty">

                <h2>
                    No farms yet
                </h2>

                <p>
                    Add your first farm to begin receiving
                    farm-specific weather and intelligence.
                </p>

                <a
                    href="<?= e(FIH_BASE_URL) ?>/farmer/add-farm.php"
                    class="primary-button"
                >
                    + Add Farm
                </a>

            </section>


        <?php else: ?>


            <div class="farms-list">


                <?php foreach ($farms as $farm): ?>


                    <?php

                    $farm_id =
                        $farm['id'];

                    $farm_name =
                        $farm['farm_name']
                        ?: 'My Farm';

                    $coordinate =
                        $farm_coordinates[$farm_id]
                        ?? null;

                    ?>


                    <article
                        class="profile-card farm-card"
                    >


                        <!-- ==================================================
                             FARM HEADER
                        =================================================== -->

                        <header class="farm-card-header">


                            <div class="farm-title-area">

                                <h2 class="farm-title">

                                    🌾

                                    <?= e($farm_name) ?>

                                </h2>


                                <?php if ($coordinate): ?>

                                    <div class="farm-location-status">

                                        📍 Farm location registered

                                    </div>

                                <?php else: ?>

                                    <div class="farm-location-status">

                                        📍 Farm location not registered

                                    </div>

                                <?php endif; ?>

                            </div>



                            <?php if ($coordinate): ?>

                                <span class="farm-status registered">
                                    Located
                                </span>

                            <?php else: ?>

                                <span class="farm-status missing">
                                    Location needed
                                </span>

                            <?php endif; ?>


                        </header>



                        <!-- ==================================================
                             WEATHER
                        =================================================== -->

                        <div class="farm-weather">


                            <?php if ($coordinate): ?>


                                <div
                                    class="farm-weather-data"
                                    data-latitude="<?= e($coordinate['latitude']) ?>"
                                    data-longitude="<?= e($coordinate['longitude']) ?>"
                                >

                                    <div class="weather-loading">

                                        🌦️ Loading farm weather...

                                    </div>

                                </div>


                            <?php else: ?>


                                <div class="farm-no-location">

                                    <p>

                                        FIH cannot provide
                                        farm-specific weather yet
                                        because this farm does not
                                        have registered coordinates.

                                    </p>


                                    <a
                                        href="<?= e(FIH_BASE_URL) ?>/farmer/farm-location.php?farm_id=<?= urlencode($farm_id) ?>"
                                        class="primary-button farm-action"
                                    >

                                        📍 Register Farm Location

                                    </a>

                                </div>


                            <?php endif; ?>

<div class="farm-weather-report">

    <a
        href="<?= e(FIH_BASE_URL) ?>/farmer/weather-observation.php?farm_id=<?= urlencode($farm_id) ?>"
        class="secondary-button farm-action"
    >
        👁️ Report Actual Weather
    </a>

</div>
                            <!-- ==================================================
                                 FARM ACTIONS
                            =================================================== -->

                            <div class="farm-actions">


<a
    href="<?= e(FIH_BASE_URL) ?>/farmer/farm-crops.php?farm_id=<?= e($farm['id']) ?>"
>
    View Farm
</a>


                                <a
                                    href="<?= e(FIH_BASE_URL) ?>/farmer/farm-location.php?farm_id=<?= urlencode($farm_id) ?>"
                                    class="secondary-button farm-action"
                                >
                                    📍 Location
                                </a>


                            </div>


                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </section>


</main>



<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const baseUrl =
            <?= json_encode(FIH_BASE_URL) ?>;



        /*
        |--------------------------------------------------------------------------
        | Escape HTML
        |--------------------------------------------------------------------------
        */

        function escapeHtml(value) {

            const element =
                document.createElement('div');

            element.textContent =
                String(value);

            return element.innerHTML;

        }



        /*
        |--------------------------------------------------------------------------
        | Weather API
        |--------------------------------------------------------------------------
        */

        async function getFarmWeather(
            latitude,
            longitude
        ) {


            const controller =
                new AbortController();


            const timeout =
                setTimeout(
                    function () {

                        controller.abort();

                    },
                    15000
                );


            try {


                const response =
                    await fetch(

                        baseUrl +
                        '/api/weather.php?latitude=' +
                        encodeURIComponent(latitude) +
                        '&longitude=' +
                        encodeURIComponent(longitude),

                        {
                            method: 'GET',

                            headers: {
                                'Accept':
                                    'application/json'
                            },

                            signal:
                                controller.signal
                        }

                    );


                if (!response.ok) {

                    throw new Error(
                        'Weather service unavailable.'
                    );

                }


                const text =
                    await response.text();


                let result;


                try {

                    result =
                        JSON.parse(text);

                } catch (error) {

                    console.error(
                        'Invalid weather response:',
                        text
                    );

                    throw new Error(
                        'Weather service returned invalid data.'
                    );

                }


                if (
                    !result ||
                    result.success !== true
                ) {

                    throw new Error(
                        result?.message ||
                        'Unable to load farm weather.'
                    );

                }


                return result.data;


            } catch (error) {


                if (
                    error.name === 'AbortError'
                ) {

                    throw new Error(
                        'Weather request timed out.'
                    );

                }


                throw error;


            } finally {

                clearTimeout(timeout);

            }

        }



        /*
        |--------------------------------------------------------------------------
        | Format date
        |--------------------------------------------------------------------------
        */

        function formatDate(
            value
        ) {

            const date =
                new Date(
                    value + 'T00:00:00'
                );


            return date.toLocaleDateString(
                undefined,
                {
                    weekday: 'short'
                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Render farm weather
        |--------------------------------------------------------------------------
        */

        function renderWeather(
            container,
            weather
        ) {


            const current =
                weather.current || {};

            const daily =
                weather.daily || {};


            const currentIcon =
                current.weather_icon ||
                '🌤️';


            const currentLabel =
                current.weather_label ||
                'Current conditions';



            let forecast =
                '';



            if (
                Array.isArray(daily.time)
            ) {


                forecast =

                    '<div class="farm-forecast">' +

                        '<h3 class="farm-forecast-title">' +

                            'Forecast' +

                        '</h3>' +

                        '<div class="farm-forecast-list">';



                const days =
                    Math.min(
                        daily.time.length,
                        7
                    );


                for (
                    let i = 0;
                    i < days;
                    i++
                ) {


                    const icon =
                        daily.weather_icons?.[i]
                        || '🌤️';


                    const min =
                        Number(
                            daily.temperature_2m_min?.[i]
                        );


                    const max =
                        Number(
                            daily.temperature_2m_max?.[i]
                        );


                    const rain =
                        Number(
                            daily.precipitation_sum?.[i]
                            ?? 0
                        );


                    forecast +=

                        '<div class="farm-forecast-day">' +

                            '<div class="farm-forecast-date">' +

                                escapeHtml(
                                    formatDate(
                                        daily.time[i]
                                    )
                                ) +

                            '</div>' +


                            '<div class="farm-forecast-icon">' +

                                escapeHtml(
                                    icon
                                ) +

                            '</div>' +


                            '<div class="farm-forecast-temp">' +

                                Math.round(min) +

                                '° / ' +

                                Math.round(max) +

                                '°C' +

                            '</div>' +


                            '<div class="farm-forecast-rain">' +

                                'Rain ' +

                                rain.toFixed(1) +

                                ' mm' +

                            '</div>' +


                        '</div>';

                }


                forecast +=

                        '</div>' +

                    '</div>';

            }



            container.innerHTML =


                '<div class="weather-current">' +


                    '<div class="weather-current-top">' +


                        '<div class="weather-icon">' +

                            escapeHtml(
                                currentIcon
                            ) +

                        '</div>' +


                        '<div>' +

                            '<div class="weather-temperature">' +

                                Math.round(
                                    Number(
                                        current.temperature_2m
                                    )
                                ) +

                                '°C' +

                            '</div>' +


                            '<div class="weather-condition">' +

                                escapeHtml(
                                    currentLabel
                                ) +

                            '</div>' +


                        '</div>' +


                    '</div>' +



                    '<div class="weather-details">' +


                        '<div class="weather-detail">' +

                            '<span>Feels like</span>' +

                            '<strong>' +

                                Math.round(
                                    Number(
                                        current.apparent_temperature
                                    )
                                ) +

                                '°C' +

                            '</strong>' +

                        '</div>' +


                        '<div class="weather-detail">' +

                            '<span>Humidity</span>' +

                            '<strong>' +

                                Number(
                                    current.relative_humidity_2m
                                ) +

                                '%' +

                            '</strong>' +

                        '</div>' +


                        '<div class="weather-detail">' +

                            '<span>Rain</span>' +

                            '<strong>' +

                                Number(
                                    current.rain || 0
                                ).toFixed(1) +

                                ' mm' +

                            '</strong>' +

                        '</div>' +


                        '<div class="weather-detail">' +

                            '<span>Wind</span>' +

                            '<strong>' +

                                Math.round(
                                    Number(
                                        current.wind_speed_10m
                                    )
                                ) +

                                ' km/h' +

                            '</strong>' +

                        '</div>' +


                    '</div>' +


                '</div>' +


                forecast;

        }



        /*
        |--------------------------------------------------------------------------
        | Load weather for every farm
        |--------------------------------------------------------------------------
        */

        const farmWeatherBoxes =
            document.querySelectorAll(
                '.farm-weather-data'
            );


        farmWeatherBoxes.forEach(
            async function (container) {


                const latitude =
                    container.dataset.latitude;


                const longitude =
                    container.dataset.longitude;


                if (
                    !latitude ||
                    !longitude
                ) {

                    return;

                }


                try {


                    const weather =
                        await getFarmWeather(
                            latitude,
                            longitude
                        );


                    renderWeather(
                        container,
                        weather
                    );


                } catch (error) {


                    console.error(
                        'Farm weather error:',
                        error
                    );


                    container.innerHTML =

                        '<div class="weather-error">' +

                            '⚠️ ' +

                            escapeHtml(
                                error.message
                                ||
                                'Unable to load weather.'
                            ) +

                        '</div>';

                }

            }
        );

    }

);

</script>


</body>

</html>