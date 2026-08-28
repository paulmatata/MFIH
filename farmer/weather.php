<?php

/*
|--------------------------------------------------------------------------
| FIH FARMER WEATHER
|--------------------------------------------------------------------------
|
| This page:
|
| 1. Authenticates the farmer
| 2. Loads the farmer's farms
| 3. Loads saved coordinates
| 4. Gets device location through browser JavaScript
| 5. Requests weather through /api/weather.php
|
|--------------------------------------------------------------------------
*/


require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';


/*
|--------------------------------------------------------------------------
| Require farmer authentication
|--------------------------------------------------------------------------
*/

require_farmer();


/*
|--------------------------------------------------------------------------
| Get authenticated farmer/user ID
|--------------------------------------------------------------------------
*/

$farmer_id = authenticated_farmer_id();


if (!$farmer_id) {

    redirect('/login.php');

    exit;
}


/*
|--------------------------------------------------------------------------
| Get farmer farms
|--------------------------------------------------------------------------
|
| Multiple farms are supported.
|
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        farm_name
    FROM farmer_profiles
    WHERE user_id = :user_id
    ORDER BY created_at ASC
");


$stmt->execute([
    'user_id' => $farmer_id
]);


$farms = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get saved farm coordinates
|--------------------------------------------------------------------------
|
| Only coordinates belonging to this authenticated farmer are loaded.
|
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
    'user_id' => $farmer_id
]);


$farm_coordinates = [];


foreach (
    $coordinate_stmt->fetchAll(PDO::FETCH_ASSOC)
    as $coordinate
) {

    $farm_coordinates[
        $coordinate['farm_id']
    ] = $coordinate;
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

    <title>Weather | FIH</title>


    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/style.css"
    >


    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/farmer.css"
    >


    <style>

        /* ==========================================================
           WEATHER PAGE
        ========================================================== */

        .weather-page {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px 16px 40px;
        }


        .weather-page-header {
            margin-bottom: 24px;
        }


        .weather-page-header .eyebrow {
            font-size: 0.82rem;
            margin: 0 0 6px;
        }


        .weather-page-header h1 {
            margin: 0 0 8px;
        }


        .weather-page-header p {
            margin: 0;
            line-height: 1.5;
        }


        /* ==========================================================
           WEATHER CARD
        ========================================================== */

        .weather-card {
            width: 100%;
            padding: 20px;
            margin-bottom: 24px;
        }


        .weather-card-header {
            margin-bottom: 20px;
        }


        .weather-card-header h2 {
            margin: 5px 0 0;
        }


        .weather-label {
            font-size: 0.82rem;
            font-weight: 600;
        }


        /* ==========================================================
           CURRENT WEATHER
        ========================================================== */

        .current-weather {
            width: 100%;
        }


        .weather-main {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 22px;
        }


        .weather-icon {
            flex: 0 0 auto;
            font-size: 3.5rem;
            line-height: 1;
        }


        .temperature {
            font-size: 2.8rem;
            font-weight: 700;
            line-height: 1.1;
        }


        .weather-condition {
            margin-top: 7px;
            font-size: 0.95rem;
        }


        .weather-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }


        .weather-detail {
            min-width: 0;
            padding: 14px;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.04);
        }


        .weather-detail span {
            display: block;
            font-size: 0.76rem;
            margin-bottom: 5px;
        }


        .weather-detail strong {
            display: block;
            font-size: 1rem;
        }


        /* ==========================================================
           WEATHER BUTTON
        ========================================================== */

        .weather-location-button {
            border: 0;
            cursor: pointer;
            padding: 13px 18px;
            border-radius: 10px;
            font: inherit;
            font-weight: 600;
        }


        .weather-location-button:disabled {
            opacity: 0.65;
            cursor: wait;
        }


        /* ==========================================================
           FARM SECTION
        ========================================================== */

        .farm-weather-section {
            margin-top: 30px;
        }


        .section-heading {
            margin-bottom: 15px;
        }


        .section-heading h2 {
            margin: 4px 0;
        }


        .section-heading p {
            margin: 0;
        }


        .farm-weather-item {
            width: 100%;
            padding: 0;
            margin-bottom: 12px;
            overflow: hidden;
        }


        .farm-weather-toggle {
            width: 100%;
            min-height: 60px;

            border: 0;
            background: transparent;

            padding: 16px 18px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            font: inherit;
            font-weight: 600;

            cursor: pointer;
            text-align: left;
        }


        .farm-name {
            min-width: 0;

            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        .expand-icon {
            flex: 0 0 auto;
            font-size: 1.3rem;
        }


        .farm-weather-content {
            padding: 0 18px 20px;
        }


        /* ==========================================================
           FORECAST
        ========================================================== */

        .weather-forecast {
            margin-top: 25px;
        }


        .forecast-heading {
            margin-bottom: 13px;
        }


        .forecast-heading h3 {
            margin: 0;
        }


        .forecast-list {
            display: flex;
            gap: 12px;

            overflow-x: auto;

            padding: 4px 2px 12px;

            scroll-snap-type: x proximity;

            -webkit-overflow-scrolling: touch;
        }


        .forecast-day {
            flex: 0 0 155px;

            min-height: 190px;

            padding: 16px 12px;

            border-radius: 16px;

            background: rgba(0, 0, 0, 0.04);

            text-align: center;

            scroll-snap-align: start;

            display: flex;
            flex-direction: column;
            align-items: center;
        }


        .forecast-date {
            width: 100%;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }


        .forecast-icon {
            font-size: 2.4rem;
            line-height: 1;
            margin: 8px 0;
        }


        .forecast-condition {
            width: 100%;
            min-height: 38px;

            font-size: 0.8rem;
            line-height: 1.3;

            display: flex;
            align-items: center;
            justify-content: center;
        }


        .forecast-temperature {
            width: 100%;

            margin-top: 8px;

            font-size: 1rem;
            font-weight: 700;

            white-space: nowrap;
        }


        .forecast-rain {
            width: 100%;

            margin-top: 8px;

            font-size: 0.78rem;

            white-space: nowrap;
        }


        /* ==========================================================
           ERROR / LOADING
        ========================================================== */

        .weather-message {
            padding: 15px;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.04);
            line-height: 1.5;
        }


        .weather-error {
            padding: 15px;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.04);
        }


        .weather-error p {
            margin: 0 0 12px;
        }


        /* ==========================================================
           SMALL PHONES
        ========================================================== */

        @media (max-width: 480px) {

            .weather-page {
                padding-left: 12px;
                padding-right: 12px;
            }


            .weather-card {
                padding: 16px;
            }


            .weather-main {
                gap: 14px;
            }


            .weather-icon {
                font-size: 3rem;
            }


            .temperature {
                font-size: 2.4rem;
            }


            .forecast-day {
                flex-basis: 145px;
                min-height: 185px;
            }

        }

    </style>

</head>


<body>


<?php

/*
|--------------------------------------------------------------------------
| Farmer Sidebar
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/farmer_sidebar.php';

?>


<main class="farmer-dashboard-layout">


    <section class="weather-page">


        <!-- ==========================================================
             PAGE HEADER
        =========================================================== -->

        <header class="weather-page-header">

            <p class="eyebrow">
                WEATHER INTELLIGENCE
            </p>

            <h1>
                Weather
            </h1>

            <p>
                View weather conditions for your current
                location and your farms.
            </p>

        </header>



        <!-- ==========================================================
             DEVICE WEATHER
        =========================================================== -->

        <section class="profile-card weather-card">


            <div class="weather-card-header">

                <span class="weather-label">
                    📍 CURRENT LOCATION
                </span>

                <h2>
                    Device Location
                </h2>

            </div>


            <div id="device-weather">

                <div class="weather-message">

                    <p>
                        Weather for your current device
                        location will appear here.
                    </p>


                    <button
                        type="button"
                        id="get-device-weather"
                        class="primary-button weather-location-button"
                    >
                        📍 Use My Current Location
                    </button>

                </div>

            </div>


        </section>



        <!-- ==========================================================
             FARM WEATHER
        =========================================================== -->

        <section class="farm-weather-section">


            <div class="section-heading">

                <p class="eyebrow">
                    FARM LOCATIONS
                </p>

                <h2>
                    Your Farms
                </h2>

                <p>
                    Expand a farm to view weather for that
                    specific location.
                </p>

            </div>



            <?php if (empty($farms)): ?>


                <div class="profile-card weather-card">

                    <p>
                        You have not added a farm yet.
                    </p>


                    <a
                        href="<?= e(FIH_BASE_URL) ?>/farmer/farms.php"
                        class="primary-button"
                    >
                        Manage Farms
                    </a>

                </div>


            <?php else: ?>


                <div class="farm-weather-list">


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
                            class="profile-card farm-weather-item"
                        >


                            <button
                                type="button"
                                class="farm-weather-toggle"
                                data-farm-id="<?= e($farm_id) ?>"
                            >

                                <span class="farm-name">

                                    🌾

                                    <?= e($farm_name) ?>

                                </span>


                                <span class="expand-icon">
                                    +
                                </span>

                            </button>



                            <div
                                id="farm-weather-<?= e($farm_id) ?>"
                                class="farm-weather-content"
                                hidden
                            >


                                <?php if (!$coordinate): ?>


                                    <div class="weather-message">

                                        <p>
                                            This farm does not have
                                            saved coordinates yet.
                                        </p>


                                        <a
                                            href="<?= e(FIH_BASE_URL) ?>/farmer/farm-location.php"
                                            class="secondary-button"
                                        >
                                            📍 Add Farm Location
                                        </a>

                                    </div>


                                <?php else: ?>


                                    <div
                                        class="weather-data"
                                        data-latitude="<?= e($coordinate['latitude']) ?>"
                                        data-longitude="<?= e($coordinate['longitude']) ?>"
                                    >

                                        <p>
                                            Loading weather...
                                        </p>

                                    </div>


                                <?php endif; ?>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </section>


    </section>


</main>



<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /*
        |--------------------------------------------------------------------------
        | Base URL
        |--------------------------------------------------------------------------
        */

        const baseUrl =
            <?= json_encode(FIH_BASE_URL) ?>;


        /*
        |--------------------------------------------------------------------------
        | Elements
        |--------------------------------------------------------------------------
        */

        const deviceButton =
            document.getElementById(
                'get-device-weather'
            );


        const deviceWeather =
            document.getElementById(
                'device-weather'
            );


        /*
        |--------------------------------------------------------------------------
        | Escape HTML
        |--------------------------------------------------------------------------
        */

        function escapeHtml(value) {

            const div =
                document.createElement('div');

            div.textContent =
                String(value);

            return div.innerHTML;
        }



        /*
        |--------------------------------------------------------------------------
        | Get browser/device location
        |--------------------------------------------------------------------------
        */

        function getDeviceLocation() {

            return new Promise(
                function (resolve, reject) {


                    if (!navigator.geolocation) {

                        reject(
                            new Error(
                                'Your browser does not support location services.'
                            )
                        );

                        return;
                    }


                    navigator.geolocation.getCurrentPosition(

                        function (position) {

                            resolve({

                                latitude:
                                    position.coords.latitude,

                                longitude:
                                    position.coords.longitude,

                                accuracy:
                                    position.coords.accuracy

                            });

                        },


                        function (error) {

                            let message =
                                'Unable to get your location.';


                            switch (error.code) {

                                case error.PERMISSION_DENIED:

                                    message =
                                        'Location permission was denied. Please allow location access and try again.';

                                    break;


                                case error.POSITION_UNAVAILABLE:

                                    message =
                                        'Your current location is unavailable.';

                                    break;


                                case error.TIMEOUT:

                                    message =
                                        'Getting your location timed out. Please try again.';

                                    break;

                            }


                            reject(
                                new Error(message)
                            );

                        },


                        {

                            enableHighAccuracy: true,

                            timeout: 15000,

                            maximumAge: 300000

                        }

                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Get weather from FIH API
        |--------------------------------------------------------------------------
        */

        async function getWeather(
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


                const url =
                    baseUrl +
                    '/api/weather.php?latitude=' +
                    encodeURIComponent(latitude) +
                    '&longitude=' +
                    encodeURIComponent(longitude);


                const response =
                    await fetch(
                        url,
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
                        'Weather service returned HTTP ' +
                        response.status
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Read as text first
                |--------------------------------------------------------------------------
                |
                | This prevents another mysterious JSON error.
                |
                */

                const text =
                    await response.text();


                let result;


                try {

                    result =
                        JSON.parse(text);

                } catch (jsonError) {

                    console.error(
                        'Invalid weather API response:',
                        text
                    );


                    throw new Error(
                        'The weather server returned invalid data.'
                    );

                }


                if (
                    !result ||
                    result.success !== true
                ) {

                    throw new Error(
                        result?.message ||
                        'Unable to load weather.'
                    );

                }


                return result.data;


            } catch (error) {


                if (
                    error.name === 'AbortError'
                ) {

                    throw new Error(
                        'Weather request timed out. Please try again.'
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
            dateString
        ) {


            const date =
                new Date(
                    dateString + 'T00:00:00'
                );


            return date.toLocaleDateString(
                undefined,
                {

                    weekday: 'short',

                    month: 'short',

                    day: 'numeric'

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Display weather
        |--------------------------------------------------------------------------
        */

        function displayWeather(
            container,
            weather
        ) {


            if (
                !weather ||
                !weather.current
            ) {

                throw new Error(
                    'Weather data is incomplete.'
                );

            }


            const current =
                weather.current;


            const daily =
                weather.daily || {};


            const icon =
                current.weather_icon ||
                '🌡️';


            const label =
                current.weather_label ||
                'Current conditions';



            let forecastHtml = '';



            /*
            |--------------------------------------------------------------------------
            | Seven-day forecast
            |--------------------------------------------------------------------------
            */

            if (
                Array.isArray(daily.time)
            ) {


                forecastHtml =

                    '<div class="weather-forecast">' +

                        '<div class="forecast-heading">' +

                            '<h3>7-Day Forecast</h3>' +

                        '</div>' +

                        '<div class="forecast-list">';



                for (
                    let i = 0;
                    i < daily.time.length;
                    i++
                ) {


                    const dayIcon =
                        daily.weather_icons?.[i]
                        || '🌡️';


                    const dayLabel =
                        daily.weather_labels?.[i]
                        || 'Weather';


                    const min =
                        daily.temperature_2m_min?.[i];


                    const max =
                        daily.temperature_2m_max?.[i];


                    const rain =
                        daily.precipitation_sum?.[i]
                        ?? 0;



                    forecastHtml +=

                        '<div class="forecast-day">' +


                            '<div class="forecast-date">' +

                                '<strong>' +

                                    escapeHtml(
                                        formatDate(
                                            daily.time[i]
                                        )
                                    ) +

                                '</strong>' +

                            '</div>' +



                            '<div class="forecast-icon">' +

                                escapeHtml(
                                    dayIcon
                                ) +

                            '</div>' +



                            '<div class="forecast-condition">' +

                                escapeHtml(
                                    dayLabel
                                ) +

                            '</div>' +



                            '<div class="forecast-temperature">' +

                                Math.round(
                                    Number(min)
                                ) +

                                '° / ' +

                                Math.round(
                                    Number(max)
                                ) +

                                '°C' +

                            '</div>' +



                            '<div class="forecast-rain">' +

                                'Rain: ' +

                                Number(
                                    rain
                                ).toFixed(1) +

                                ' mm' +

                            '</div>' +


                        '</div>';

                }


                forecastHtml +=

                        '</div>' +

                    '</div>';

            }



            /*
            |--------------------------------------------------------------------------
            | Current weather
            |--------------------------------------------------------------------------
            */

            container.innerHTML =


                '<div class="current-weather">' +


                    '<div class="weather-main">' +


                        '<div class="weather-icon">' +

                            escapeHtml(
                                icon
                            ) +

                        '</div>' +



                        '<div>' +

                            '<div class="temperature">' +

                                Math.round(
                                    Number(
                                        current.temperature_2m
                                    )
                                ) +

                                '°C' +

                            '</div>' +



                            '<div class="weather-condition">' +

                                escapeHtml(
                                    label
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

                                escapeHtml(
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


                forecastHtml;

        }



        /*
        |--------------------------------------------------------------------------
        | Device location weather
        |--------------------------------------------------------------------------
        */

        if (
            deviceButton &&
            deviceWeather
        ) {


            deviceButton.addEventListener(
                'click',
                async function () {


                    deviceButton.disabled =
                        true;


                    deviceButton.textContent =
                        'Getting location...';


                    deviceWeather.innerHTML =

                        '<div class="weather-message">' +

                            '<p>' +
                                'Getting your current location...' +
                            '</p>' +

                        '</div>';



                    try {


                        /*
                        |--------------------------------------------------------------------------
                        | Get GPS coordinates
                        |--------------------------------------------------------------------------
                        */

                        const location =
                            await getDeviceLocation();



                        /*
                        |--------------------------------------------------------------------------
                        | Request weather
                        |--------------------------------------------------------------------------
                        */

                        deviceButton.textContent =
                            'Loading weather...';


                        deviceWeather.innerHTML =

                            '<div class="weather-message">' +

                                '<p>' +
                                    'Loading weather data...' +
                                '</p>' +

                            '</div>';



                        const weather =
                            await getWeather(
                                location.latitude,
                                location.longitude
                            );



                        /*
                        |--------------------------------------------------------------------------
                        | Display
                        |--------------------------------------------------------------------------
                        */

                        displayWeather(
                            deviceWeather,
                            weather
                        );


                        deviceButton.textContent =
                            '📍 Refresh My Location';


                    } catch (error) {


                        console.error(
                            'Device weather error:',
                            error
                        );


                        deviceWeather.innerHTML =

                            '<div class="weather-error">' +

                                '<p>' +

                                    escapeHtml(
                                        error.message ||
                                        'Unable to load weather.'
                                    ) +

                                '</p>' +

                            '</div>';


                        deviceButton.textContent =
                            '📍 Try Again';


                    } finally {


                        deviceButton.disabled =
                            false;

                    }

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Farm weather
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                '.farm-weather-toggle'
            )
            .forEach(
                function (button) {


                    button.addEventListener(
                        'click',
                        async function () {


                            const farmId =
                                this.dataset.farmId;


                            const content =
                                document.getElementById(
                                    'farm-weather-' +
                                    farmId
                                );


                            const icon =
                                this.querySelector(
                                    '.expand-icon'
                                );


                            if (!content) {

                                return;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Open / close
                            |--------------------------------------------------------------------------
                            */

                            const opening =
                                content.hidden;


                            content.hidden =
                                !opening;


                            icon.textContent =
                                opening
                                ? '−'
                                : '+';



                            /*
                            |--------------------------------------------------------------------------
                            | Already loaded?
                            |--------------------------------------------------------------------------
                            */

                            if (
                                !opening ||
                                content.dataset.loaded === 'true'
                            ) {

                                return;

                            }



                            const weatherBox =
                                content.querySelector(
                                    '.weather-data'
                                );


                            if (!weatherBox) {

                                return;

                            }


                            const latitude =
                                weatherBox.dataset.latitude;


                            const longitude =
                                weatherBox.dataset.longitude;



                            if (
                                !latitude ||
                                !longitude
                            ) {

                                weatherBox.innerHTML =

                                    '<div class="weather-error">' +

                                        '<p>' +
                                            'Farm coordinates are incomplete.' +
                                        '</p>' +

                                    '</div>';

                                return;

                            }



                            weatherBox.innerHTML =

                                '<div class="weather-message">' +

                                    '<p>' +
                                        'Loading farm weather...' +
                                    '</p>' +

                                '</div>';



                            try {


                                const weather =
                                    await getWeather(
                                        latitude,
                                        longitude
                                    );


                                displayWeather(
                                    weatherBox,
                                    weather
                                );


                                content.dataset.loaded =
                                    'true';


                            } catch (error) {


                                console.error(
                                    'Farm weather error:',
                                    error
                                );


                                weatherBox.innerHTML =

                                    '<div class="weather-error">' +

                                        '<p>' +

                                            escapeHtml(
                                                error.message ||
                                                'Unable to load farm weather.'
                                            ) +

                                        '</p>' +

                                    '</div>';

                            }

                        }
                    );

                }
            );

    }

);

</script>


</body>

</html>