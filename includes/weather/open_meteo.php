<?php

function get_open_meteo_weather(
    float $latitude,
    float $longitude
): array {

    if (
        $latitude < -90 ||
        $latitude > 90 ||
        $longitude < -180 ||
        $longitude > 180
    ) {

        return [
            'success' => false,
            'message' => 'Invalid coordinates.',
            'data' => null
        ];
    }


    $params = [

        'latitude' => $latitude,

        'longitude' => $longitude,

        'current' => implode(',', [

            'temperature_2m',

            'relative_humidity_2m',

            'apparent_temperature',

            'precipitation',

            'rain',

            'weather_code',

            'wind_speed_10m'

        ]),

        'daily' => implode(',', [

            'weather_code',

            'temperature_2m_max',

            'temperature_2m_min',

            'precipitation_sum',

            'rain_sum'

        ]),

        'forecast_days' => 7,

        'timezone' => 'auto'

    ];


    $url =
        'https://api.open-meteo.com/v1/forecast?' .
        http_build_query($params);


    $context = stream_context_create([

        'http' => [

            'method' => 'GET',

            'timeout' => 15,

            'ignore_errors' => true,

            'header' =>
                "Accept: application/json\r\n" .
                "User-Agent: FIH-Weather/1.0\r\n"

        ]

    ]);


    $response = @file_get_contents(
        $url,
        false,
        $context
    );


    if ($response === false) {

        return [
            'success' => false,
            'message' =>
                'Could not connect to Open-Meteo.',
            'data' => null
        ];
    }


    $data = json_decode(
        $response,
        true
    );


    if (
        !is_array($data)
    ) {

        return [
            'success' => false,
            'message' =>
                'Open-Meteo returned invalid data.',
            'data' => null
        ];
    }


    if (
        isset($data['error']) &&
        $data['error'] === true
    ) {

        return [
            'success' => false,
            'message' =>
                $data['reason']
                ?? 'Open-Meteo returned an error.',
            'data' => null
        ];
    }


    return [
        'success' => true,
        'message' => null,
        'data' => $data
    ];
}