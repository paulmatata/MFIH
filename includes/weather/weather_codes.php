<?php

/*
|--------------------------------------------------------------------------
| FIH Weather Code Helper
|--------------------------------------------------------------------------
|
| Converts Open-Meteo WMO weather codes into information that is easier
| for farmers to understand.
|
|--------------------------------------------------------------------------
*/

function get_weather_description(int $code): array
{
    switch ($code) {

        case 0:
            return [
                'label' => 'Clear sky',
                'icon' => '☀️'
            ];

        case 1:
            return [
                'label' => 'Mainly clear',
                'icon' => '🌤️'
            ];

        case 2:
            return [
                'label' => 'Partly cloudy',
                'icon' => '⛅'
            ];

        case 3:
            return [
                'label' => 'Overcast',
                'icon' => '☁️'
            ];

        case 45:
        case 48:
            return [
                'label' => 'Foggy',
                'icon' => '🌫️'
            ];

        case 51:
        case 53:
        case 55:
            return [
                'label' => 'Drizzle',
                'icon' => '🌦️'
            ];

        case 56:
        case 57:
            return [
                'label' => 'Freezing drizzle',
                'icon' => '🌧️'
            ];

        case 61:
        case 63:
        case 65:
            return [
                'label' => 'Rain',
                'icon' => '🌧️'
            ];

        case 66:
        case 67:
            return [
                'label' => 'Freezing rain',
                'icon' => '🌧️'
            ];

        case 71:
        case 73:
        case 75:
        case 77:
            return [
                'label' => 'Snow',
                'icon' => '❄️'
            ];

        case 80:
        case 81:
        case 82:
            return [
                'label' => 'Rain showers',
                'icon' => '🌦️'
            ];

        case 85:
        case 86:
            return [
                'label' => 'Snow showers',
                'icon' => '🌨️'
            ];

        case 95:
            return [
                'label' => 'Thunderstorm',
                'icon' => '⛈️'
            ];

        case 96:
        case 99:
            return [
                'label' => 'Thunderstorm with hail',
                'icon' => '⛈️'
            ];

        default:
            return [
                'label' => 'Unknown conditions',
                'icon' => '🌡️'
            ];
    }
}