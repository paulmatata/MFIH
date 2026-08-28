<?php
include 'includes/header.php';
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
include 'includes/navbar.php';
echo generate_uuid();
?>
<!-- ===========================
     HERO SECTION
=========================== -->

<section class="hero">
    <div class="hero-left">

        <span class="hero-badge">
            <i class="bi bi-globe2"></i>
            Climate Smart Agriculture Platform
        </span>

        <h1 class="hero-title">
            <span>Predict.</span><br>
            <span>Protect.</span><br>
            <span>Prosper.</span>
        </h1>

        <p class="hero-description">
            Empowering farmers with AI-driven insights, weather intelligence,
            innovation and predictive food security to build resilient farming
            communities across Africa.
        </p>

        <div class="hero-buttons">

            <a href="register.php" class="btn-start">
                Get Started
            </a>

            <a href="#" class="btn-demo">

                <i class="bi bi-play-circle-fill"></i>

                Watch Demo

            </a>

        </div>

    </div>

<div class="hero-right">

    <div class="hero-scene">

        <div class="hero-background">

            <div class="sun"></div>

            <div class="cloud cloud1"></div>

            <div class="cloud cloud2"></div>

            <div class="cloud cloud3"></div>

            <div class="particles"></div>

        </div>

        <div class="hero-globe"></div>
        <!-- Farmer Character -->
    <div class="farmer-character">
        <img
            src="assets/images/hero/farmer-hero-removebg-preview.png"
            alt="Farmer"
            class="hero-farmer">

        <!-- Interactive Crop -->
<div class="crop-growth" id="cropGrowth" aria-hidden="true">

    <div class="crop-seed"></div>

    <div class="crop-stem"></div>

    <div class="crop-leaf crop-leaf-left"></div>

    <div class="crop-leaf crop-leaf-right"></div>

</div>
    </div>

    </div>

</div>


<div class="hero-wave">

<svg
xmlns="http://www.w3.org/2000/svg"
viewBox="0 0 1440 320">

<path
fill="#ffffff"
fill-opacity="1"
d="M0,128L60,138.7C120,149,240,171,360,165.3C480,160,600,128,720,133.3C840,139,960,181,1080,192C1200,203,1320,181,1380,170.7L1440,160L1440,320L0,320Z">
</path>

</svg>

</div>

</section>

<!-- =========================================================
     DEVICE WEATHER
========================================================= -->

<section class="home-weather-section">

    <div class="home-weather-container">

        <div class="home-weather-heading">

            <span class="weather-eyebrow">
                LOCAL WEATHER
            </span>

            <h2>
                Weather where you are
            </h2>

            <p>
                Allow location access to see current weather
                conditions and the forecast for your location.
            </p>

        </div>


        <div
            id="home-weather-card"
            class="home-weather-card"
        >

            <div
                id="home-weather-loading"
                class="home-weather-state"
            >

                <div class="weather-loader">
                    🌍
                </div>

                <p>
                    Getting your location...
                </p>

            </div>


            <div
                id="home-weather-permission"
                class="home-weather-state"
                hidden
            >

                <div class="weather-state-icon">
                    📍
                </div>

                <h3>
                    See your local weather
                </h3>

                <p>
                    FIH uses your device location to provide
                    weather information for where you are.
                </p>

                <button
                    type="button"
                    id="home-weather-location-btn"
                    class="weather-location-button"
                >
                    📍 Use my location
                </button>

            </div>


            <div
                id="home-weather-error"
                class="home-weather-state"
                hidden
            >

                <div class="weather-state-icon">
                    ⚠️
                </div>

                <h3>
                    Weather unavailable
                </h3>

                <p id="home-weather-error-message">
                    We could not retrieve weather information
                    for your location.
                </p>

                <button
                    type="button"
                    id="home-weather-retry"
                    class="weather-location-button"
                >
                    🔄 Try again
                </button>

            </div>


            <div
                id="home-weather-content"
                hidden
            >

                <div class="home-weather-location">

                    <span>
                        📍 Your current location
                    </span>

                    <small id="home-weather-coordinates">
                    </small>

                </div>


                <div class="home-weather-current">

                    <div class="weather-current-main">

                        <div
                            id="home-weather-icon"
                            class="weather-main-icon"
                        >
                            🌤️
                        </div>

                        <div>

                            <div
                                id="home-weather-temperature"
                                class="weather-temperature"
                            >
                                --
                            </div>

                            <div
                                id="home-weather-condition"
                                class="weather-condition"
                            >
                                Loading...
                            </div>

                        </div>

                    </div>


                    <div class="weather-current-details">

                        <div class="weather-detail">

                            <span>
                                💧
                            </span>

                            <div>
                                <small>
                                    Humidity
                                </small>

                                <strong id="home-weather-humidity">
                                    --
                                </strong>
                            </div>

                        </div>


                        <div class="weather-detail">

                            <span>
                                💨
                            </span>

                            <div>
                                <small>
                                    Wind
                                </small>

                                <strong id="home-weather-wind">
                                    --
                                </strong>
                            </div>

                        </div>


                        <div class="weather-detail">

                            <span>
                                🌧️
                            </span>

                            <div>
                                <small>
                                    Rain
                                </small>

                                <strong id="home-weather-rain">
                                    --
                                </strong>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="weather-forecast-heading">

                    <h3>
                        Forecast
                    </h3>

                </div>


                <div
                    id="home-weather-forecast"
                    class="home-weather-forecast"
                >
                </div>


                <p class="weather-source">
                    Weather data provided by Open-Meteo.
                </p>

            </div>

        </div>

    </div>

</section>
<script>

(function () {

    "use strict";


    const loading =
        document.getElementById(
            "home-weather-loading"
        );

    const permission =
        document.getElementById(
            "home-weather-permission"
        );

    const error =
        document.getElementById(
            "home-weather-error"
        );

    const content =
        document.getElementById(
            "home-weather-content"
        );

    const errorMessage =
        document.getElementById(
            "home-weather-error-message"
        );

    const locationButton =
        document.getElementById(
            "home-weather-location-btn"
        );

    const retryButton =
        document.getElementById(
            "home-weather-retry"
        );


    function showOnly(element) {

        loading.hidden = true;
        permission.hidden = true;
        error.hidden = true;
        content.hidden = true;

        element.hidden = false;

    }


    function weatherDescription(code) {

        const descriptions = {

            0: {
                text: "Clear sky",
                icon: "☀️"
            },

            1: {
                text: "Mainly clear",
                icon: "🌤️"
            },

            2: {
                text: "Partly cloudy",
                icon: "⛅"
            },

            3: {
                text: "Overcast",
                icon: "☁️"
            },

            45: {
                text: "Fog",
                icon: "🌫️"
            },

            48: {
                text: "Depositing rime fog",
                icon: "🌫️"
            },

            51: {
                text: "Light drizzle",
                icon: "🌦️"
            },

            53: {
                text: "Drizzle",
                icon: "🌦️"
            },

            55: {
                text: "Heavy drizzle",
                icon: "🌧️"
            },

            61: {
                text: "Light rain",
                icon: "🌦️"
            },

            63: {
                text: "Rain",
                icon: "🌧️"
            },

            65: {
                text: "Heavy rain",
                icon: "🌧️"
            },

            80: {
                text: "Rain showers",
                icon: "🌦️"
            },

            81: {
                text: "Rain showers",
                icon: "🌧️"
            },

            82: {
                text: "Heavy rain showers",
                icon: "🌧️"
            },

            95: {
                text: "Thunderstorm",
                icon: "⛈️"
            },

            96: {
                text: "Thunderstorm with hail",
                icon: "⛈️"
            },

            99: {
                text: "Thunderstorm with hail",
                icon: "⛈️"
            }

        };


        return descriptions[code] || {
            text: "Unknown conditions",
            icon: "🌤️"
        };

    }


    function getDayName(dateString) {

        const date =
            new Date(
                dateString + "T12:00:00"
            );

        return date.toLocaleDateString(
            undefined,
            {
                weekday: "short"
            }
        );

    }


    function displayWeather(data) {

        const current =
            data.current;

        const daily =
            data.daily;


        const currentCondition =
            weatherDescription(
                current.weather_code
            );


        document.getElementById(
            "home-weather-icon"
        ).textContent =
            currentCondition.icon;


        document.getElementById(
            "home-weather-temperature"
        ).textContent =
            Math.round(
                current.temperature_2m
            ) + "°C";


        document.getElementById(
            "home-weather-condition"
        ).textContent =
            currentCondition.text;


        document.getElementById(
            "home-weather-humidity"
        ).textContent =
            Math.round(
                current.relative_humidity_2m
            ) + "%";


        document.getElementById(
            "home-weather-wind"
        ).textContent =
            Math.round(
                current.wind_speed_10m
            ) + " km/h";


        document.getElementById(
            "home-weather-rain"
        ).textContent =
            current.precipitation + " mm";


        const forecast =
            document.getElementById(
                "home-weather-forecast"
            );


        forecast.innerHTML = "";


        for (
            let i = 0;
            i < Math.min(
                3,
                daily.time.length
            );
            i++
        ) {

            const condition =
                weatherDescription(
                    daily.weather_code[i]
                );


            const card =
                document.createElement(
                    "div"
                );

            card.className =
                "weather-day";


            card.innerHTML = `

                <div class="weather-day-name">
                    ${getDayName(
                        daily.time[i]
                    )}
                </div>

                <div class="weather-day-icon">
                    ${condition.icon}
                </div>

                <div class="weather-day-temperature">
                    ${Math.round(
                        daily.temperature_2m_max[i]
                    )}° /
                    ${Math.round(
                        daily.temperature_2m_min[i]
                    )}°
                </div>

                <div class="weather-day-rain">
                    🌧️
                    ${Math.round(
                        daily.precipitation_probability_max[i]
                    )}% rain probability
                </div>

            `;


            forecast.appendChild(card);

        }


        showOnly(content);

    }


    async function loadWeather(
        latitude,
        longitude
    ) {

        showOnly(loading);


        const url =
            "https://api.open-meteo.com/v1/forecast"
            + "?latitude="
            + encodeURIComponent(latitude)
            + "&longitude="
            + encodeURIComponent(longitude)
            + "&current="
            + "temperature_2m,"
            + "relative_humidity_2m,"
            + "precipitation,"
            + "weather_code,"
            + "wind_speed_10m"
            + "&daily="
            + "weather_code,"
            + "temperature_2m_max,"
            + "temperature_2m_min,"
            + "precipitation_probability_max"
            + "&forecast_days=3"
            + "&timezone=auto";


        try {

            const response =
                await fetch(url);


            if (!response.ok) {

                throw new Error(
                    "Weather request failed."
                );

            }


            const data =
                await response.json();


            if (
                !data.current ||
                !data.daily
            ) {

                throw new Error(
                    "Incomplete weather data."
                );

            }


            document.getElementById(
                "home-weather-coordinates"
            ).textContent =
                Number(latitude).toFixed(4)
                + ", "
                + Number(longitude).toFixed(4);


            displayWeather(data);

        } catch (err) {

            errorMessage.textContent =
                "We could not retrieve weather "
                + "information right now. "
                + "Please try again.";

            showOnly(error);

        }

    }


    function requestLocation() {

        if (
            !navigator.geolocation
        ) {

            errorMessage.textContent =
                "Your browser does not support "
                + "device location.";

            showOnly(error);

            return;

        }


        showOnly(loading);


        navigator.geolocation.getCurrentPosition(

            function (position) {

                loadWeather(
                    position.coords.latitude,
                    position.coords.longitude
                );

            },

            function (errorObject) {

                if (
                    errorObject.code ===
                    errorObject.PERMISSION_DENIED
                ) {

                    showOnly(permission);

                } else {

                    errorMessage.textContent =
                        "We could not determine "
                        + "your current location.";

                    showOnly(error);

                }

            },

            {
                enableHighAccuracy: false,
                timeout: 10000,
                maximumAge: 300000
            }

        );

    }


    locationButton.addEventListener(
        "click",
        requestLocation
    );


    retryButton.addEventListener(
        "click",
        requestLocation
    );


    /*
    |--------------------------------------------------------------------------
    | First page load
    |--------------------------------------------------------------------------
    */

    requestLocation();


})();

</script>
<?php
include 'includes/footer.php';
?>