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
| Get farm
|--------------------------------------------------------------------------
*/

$farm_id = $_GET['farm_id'] ?? '';

if (!$farm_id) {
    redirect('/farmer/farms.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Verify farm belongs to farmer
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        farm_name
    FROM farmer_profiles
    WHERE id = :farm_id
      AND user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    'farm_id' => $farm_id,
    'user_id' => $user_id
]);

$farm = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$farm) {
    http_response_code(404);
    exit('Farm not found.');
}


/*
|--------------------------------------------------------------------------
| Get farm coordinates
|--------------------------------------------------------------------------
*/

$coordinate_stmt = $pdo->prepare("
    SELECT
        latitude,
        longitude
    FROM coordinates
    WHERE farm_id = :farm_id
      AND user_id = :user_id
      AND source = 'farm_location'
    LIMIT 1
");

$coordinate_stmt->execute([
    'farm_id' => $farm_id,
    'user_id' => $user_id
]);

$coordinates =
    $coordinate_stmt->fetch(PDO::FETCH_ASSOC);


if (!$coordinates) {
    exit('This farm does not have registered coordinates.');
}
/*
|--------------------------------------------------------------------------
| Get current weather prediction
|--------------------------------------------------------------------------
*/

$latitude = (float) $coordinates['latitude'];
$longitude = (float) $coordinates['longitude'];

$weather_url =
    'https://api.open-meteo.com/v1/forecast'
    . '?latitude=' . urlencode($latitude)
    . '&longitude=' . urlencode($longitude)
    . '&current=temperature_2m,relative_humidity_2m,weather_code,precipitation,wind_speed_10m'
    . '&timezone=auto';


$weather_context = stream_context_create([
    'http' => [
        'timeout' => 10
    ]
]);


$weather_response = @file_get_contents(
    $weather_url,
    false,
    $weather_context
);


$predicted_condition = null;


if ($weather_response !== false) {

    $weather_data =
        json_decode(
            $weather_response,
            true
        );


    if (
        is_array($weather_data) &&
        isset(
            $weather_data['current']['weather_code']
        )
    ) {

        $weather_code =
            (int)
            $weather_data['current']['weather_code'];


        $predicted_condition =
            match (true) {

                $weather_code === 0
                    => 'Clear sky',

                in_array(
                    $weather_code,
                    [1, 2, 3],
                    true
                )
                    => 'Cloudy',

                in_array(
                    $weather_code,
                    [45, 48],
                    true
                )
                    => 'Fog',

                in_array(
                    $weather_code,
                    [51, 53, 55, 56, 57],
                    true
                )
                    => 'Drizzle',

                in_array(
                    $weather_code,
                    [61, 63, 65, 66, 67],
                    true
                )
                    => 'Rain',

                in_array(
                    $weather_code,
                    [71, 73, 75, 77],
                    true
                )
                    => 'Snow',

                in_array(
                    $weather_code,
                    [80, 81, 82],
                    true
                )
                    => 'Rain showers',

                in_array(
                    $weather_code,
                    [85, 86],
                    true
                )
                    => 'Snow showers',

                in_array(
                    $weather_code,
                    [95, 96, 99],
                    true
                )
                    => 'Thunderstorm',

                default
                    => 'Unknown'
            };

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

    <title>
        Weather Observation | FIH
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

        .observation-page {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            padding: 20px 14px 50px;
        }


        .observation-header {
            margin-bottom: 22px;
        }


        .observation-header h1 {
            margin: 0 0 8px;
        }


        .observation-header p {
            margin: 0;
            line-height: 1.5;
        }


        .observation-card {
            padding: 20px;
            border-radius: 18px;
        }


        .farm-name {
            margin-bottom: 20px;
        }


        .farm-name small {
            display: block;
            margin-bottom: 5px;
            font-size: .75rem;
        }


        .farm-name strong {
            font-size: 1.1rem;
        }


        .observation-question {
            margin-bottom: 18px;
        }


        .observation-question h2 {
            margin: 0 0 8px;
            font-size: 1.1rem;
        }


        .observation-question p {
            margin: 0;
            line-height: 1.5;
        }


        .condition-options {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }


        .condition-option {
            position: relative;
        }


        .condition-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }


        .condition-option label {
            display: flex;
            align-items: center;
            justify-content: center;

            min-height: 55px;

            padding: 10px;

            border: 1px solid rgba(0, 0, 0, .12);

            border-radius: 12px;

            text-align: center;

            cursor: pointer;

            font-size: .85rem;
        }


        .condition-option input:checked + label {
            border: 2px solid currentColor;
            font-weight: 600;
        }


        .observation-comment {
            margin-bottom: 20px;
        }


        .observation-comment label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            font-size: .85rem;
        }


        .observation-comment textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            box-sizing: border-box;
            border-radius: 11px;
            border: 1px solid rgba(0, 0, 0, .15);
            font: inherit;
            resize: vertical;
        }


        .observation-button {
            width: 100%;
            min-height: 50px;
            border: 0;
            border-radius: 11px;
            padding: 13px 18px;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }


        .observation-note {
            margin-top: 15px;
            font-size: .78rem;
            line-height: 1.5;
        }


        @media (min-width: 600px) {

            .observation-page {
                padding: 35px 25px 60px;
            }

            .observation-card {
                padding: 28px;
            }

            .condition-options {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

        }
.prediction-notice {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 20px;
    padding: 14px;
    border-radius: 12px;
    background: rgba(0, 0, 0, 0.05);
}

.prediction-notice strong {
    font-size: .8rem;
}

.prediction-notice span {
    font-size: 1rem;
    font-weight: 600;
}
    </style>

</head>


<body>


<?php

require_once __DIR__ . '/../includes/farmer_sidebar.php';

?>


<main class="farmer-dashboard-layout">


    <section class="observation-page">


        <header class="observation-header">

            <p class="eyebrow">
                WEATHER OBSERVATION
            </p>

            <h1>
                Report What You Are Experiencing
            </h1>

            <p>
                Your observation helps FIH compare predicted
                weather with conditions actually experienced
                at your farm.
            </p>

        </header>


        <section class="profile-card observation-card">


            <div class="farm-name">

                <small>
                    FARM
                </small>

                <strong>
                    🌾 <?= e($farm['farm_name'] ?: 'My Farm') ?>
                </strong>

            </div>
<?php if ($predicted_condition): ?>

    <div class="prediction-notice">

        <strong>
            🌦️ FIH prediction
        </strong>

        <span>
            <?= e($predicted_condition) ?>
        </span>

    </div>

<?php endif; ?>

            <div class="observation-question">

                <h2>
                    What is the weather like right now?
                </h2>

                <p>
                    Select the condition that best describes
                    what you are observing.
                </p>

            </div>


            <form
                method="POST"
                action="<?= e(FIH_BASE_URL) ?>/api/farmer/weather-observation.php"
            >


            <?= csrf_field() ?>


                <input
                    type="hidden"
                    name="farm_id"
                    value="<?= e($farm_id) ?>"
                >
<input
    type="hidden"
    name="predicted_condition"
    value="<?= e($predicted_condition ?? '') ?>"
>

                <div class="condition-options">


                    <div class="condition-option">

                        <input
                            type="radio"
                            name="observed_condition"
                            id="clear"
                            value="Clear sky"
                            required
                        >

                        <label for="clear">
                            ☀️ Clear sky
                        </label>

                    </div>


                    <div class="condition-option">

                        <input
                            type="radio"
                            name="observed_condition"
                            id="cloudy"
                            value="Cloudy"
                        >

                        <label for="cloudy">
                            ☁️ Cloudy
                        </label>

                    </div>


                    <div class="condition-option">

                        <input
                            type="radio"
                            name="observed_condition"
                            id="rain"
                            value="Rain"
                        >

                        <label for="rain">
                            🌧️ Rain
                        </label>

                    </div>


                    <div class="condition-option">

                        <input
                            type="radio"
                            name="observed_condition"
                            id="heavy-rain"
                            value="Heavy rain"
                        >

                        <label for="heavy-rain">
                            🌧️ Heavy rain
                        </label>

                    </div>


                    <div class="condition-option">

                        <input
                            type="radio"
                            name="observed_condition"
                            id="wind"
                            value="Strong wind"
                        >

                        <label for="wind">
                            💨 Strong wind
                        </label>

                    </div>


                    <div class="condition-option">

                        <input
                            type="radio"
                            name="observed_condition"
                            id="other"
                            value="Other"
                        >

                        <label for="other">
                            🌤️ Other
                        </label>

                    </div>


                </div>


                <div class="observation-comment">

                    <label for="comment">
                        Additional observation
                        <span>(optional)</span>
                    </label>


                    <textarea
                        name="farmer_comment"
                        id="comment"
                        maxlength="1000"
                        placeholder="For example: It has been raining heavily for about 20 minutes."
                    ></textarea>

                </div>


                <button
                    type="submit"
                    class="primary-button observation-button"
                >
                    Submit Observation
                </button>


            </form>


            <p class="observation-note">

                FIH uses farmer observations to understand
                how actual conditions compare with weather
                predictions. Your report does not change the
                weather forecast itself.

            </p>


        </section>


    </section>


</main>


</body>

</html>