<?php

/*
|--------------------------------------------------------------------------
| FIH Weather API
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/weather/open_meteo.php';
require_once __DIR__ . '/../includes/weather/weather_codes.php';

/*
|--------------------------------------------------------------------------
| Always return JSON
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=utf-8');


/*
|--------------------------------------------------------------------------
| Prevent PHP warnings/notices from corrupting JSON
|--------------------------------------------------------------------------
*/

ini_set('display_errors', '0');


/*
|--------------------------------------------------------------------------
| Get coordinates
|--------------------------------------------------------------------------
*/

$latitude = $_GET['latitude'] ?? null;
$longitude = $_GET['longitude'] ?? null;


/*
|--------------------------------------------------------------------------
| Validate coordinates
|--------------------------------------------------------------------------
*/

if (
    $latitude === null ||
    $longitude === null ||
    !is_numeric($latitude) ||
    !is_numeric($longitude)
) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Valid latitude and longitude are required.',
        'data' => null
    ]);

    exit;
}


$latitude = (float) $latitude;
$longitude = (float) $longitude;


/*
|--------------------------------------------------------------------------
| Coordinate range validation
|--------------------------------------------------------------------------
*/

if (
    $latitude < -90 ||
    $latitude > 90 ||
    $longitude < -180 ||
    $longitude > 180
) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'The supplied coordinates are outside the valid range.',
        'data' => null
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Request Open-Meteo
|--------------------------------------------------------------------------
*/

try {

    $result = get_open_meteo_weather(
        $latitude,
        $longitude
    );

    if (
    $result['success'] &&
    isset($result['data']['current']['weather_code'])
) {

    $weather_code =
        (int) $result['data']['current']['weather_code'];

    $description =
        get_weather_description(
            $weather_code
        );

    $result['data']['current']['weather_label'] =
        $description['label'];

    $result['data']['current']['weather_icon'] =
        $description['icon'];
}

if (
    $result['success'] &&
    isset($result['data']['daily']['weather_code'])
) {

    $codes =
        $result['data']['daily']['weather_code'];

    $labels = [];
    $icons = [];

    foreach ($codes as $code) {

        $description =
            get_weather_description(
                (int) $code
            );

        $labels[] =
            $description['label'];

        $icons[] =
            $description['icon'];
    }


    $result['data']['daily']['weather_labels'] =
        $labels;

    $result['data']['daily']['weather_icons'] =
        $icons;
}

    if (
        !is_array($result) ||
        !isset($result['success'])
    ) {

        throw new Exception(
            'Invalid response from weather service.'
        );
    }


    if (!$result['success']) {

        http_response_code(502);

    }


    echo json_encode(
        $result,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Weather service error.',
        'data' => null
    ]);

}