<?php

/**
 * FIH Crop Health API
 *
 * Handles communication between FIH and the external
 * Kindwise Crop.health API.
 *
 * The farmer-facing pages should NOT call Kindwise directly.
 */


/*
|--------------------------------------------------------------------------
| Main diagnosis function
|--------------------------------------------------------------------------
*/

function diagnose_crop_image(
    string $image_path,
    ?string $crop_type = null,
    ?string $description = null
): array {

    $config =
        require __DIR__ . '/../config/crop_health.php';


    /*
    |--------------------------------------------------------------------------
    | Provider
    |--------------------------------------------------------------------------
    */

    $provider =
        $config['provider'] ?? null;


    if ($provider === 'kindwise') {

        return diagnose_with_kindwise(
            $image_path,
            $crop_type,
            $description,
            $config
        );
    }


    return [

        'success' => false,

        'status' => 'provider_error',

        'error' =>
            'Crop health diagnosis provider is not configured.'

    ];
}


/*
|--------------------------------------------------------------------------
| Kindwise Crop.health
|--------------------------------------------------------------------------
*/

function diagnose_with_kindwise(
    string $image_path,
    ?string $crop_type,
    ?string $description,
    array $config
): array {


    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    */

    $api_url =
        trim(
            $config['api_url'] ?? ''
        );


    $api_key =
        trim(
            $config['api_key'] ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | Check API configuration
    |--------------------------------------------------------------------------
    */

    if (
        $api_url === ''
        ||
        $api_key === ''
        ||
        $api_key ===
            'PASTE_YOUR_KINDWISE_API_KEY_HERE'
    ) {

        return [

            'success' => false,

            'status' =>
                'provider_not_configured',

            'error' =>
                'Kindwise Crop.health API key is not configured.'

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Check image
    |--------------------------------------------------------------------------
    */

    if (!is_file($image_path)) {

        return [

            'success' => false,

            'status' => 'invalid_image',

            'error' =>
                'The uploaded crop image could not be found.'

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Detect MIME type
    |--------------------------------------------------------------------------
    */

    $mime_type =
        mime_content_type($image_path);


    $allowed_types = [

        'image/jpeg',
        'image/png',
        'image/webp'

    ];


    if (
        !in_array(
            $mime_type,
            $allowed_types,
            true
        )
    ) {

        return [

            'success' => false,

            'status' => 'invalid_image_type',

            'error' =>
                'Unsupported crop image format.'

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Read image
    |--------------------------------------------------------------------------
    */

    $image_contents =
        file_get_contents(
            $image_path
        );


    if ($image_contents === false) {

        return [

            'success' => false,

            'status' => 'image_read_error',

            'error' =>
                'Unable to read the uploaded crop image.'

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Convert image to Base64
    |--------------------------------------------------------------------------
    */

    $image_base64 =
        base64_encode(
            $image_contents
        );


    /*
    |--------------------------------------------------------------------------
    | Kindwise details
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | Kindwise expects these as URL query parameters.
    | They must NOT be placed inside the JSON body.
    |
    */

    $details = [

        'common_names',

        'description',

        'treatment',

        'symptoms',

        'severity',

        'spreading',

        'taxonomy',

        'url',

        'eppo_code',

        'eppo_regulation_status',

        'gbif_id'

    ];


    /*
    |--------------------------------------------------------------------------
    | Build query string
    |--------------------------------------------------------------------------
    */

    $query = http_build_query(

        [

            'details' =>
                implode(
                    ',',
                    $details
                ),

            'language' =>
                $config['language'] ?? 'en',

            'similar_images' =>
                'true'

        ],

        '',

        '&',

        PHP_QUERY_RFC3986

    );


    /*
    |--------------------------------------------------------------------------
    | Final API URL
    |--------------------------------------------------------------------------
    */

    $request_url =
        $api_url
        . '?'
        . $query;


    /*
    |--------------------------------------------------------------------------
    | Request body
    |--------------------------------------------------------------------------
    |
    | Only accepted request-body data goes here.
    |
    */

    $request = [

        'images' => [

            $image_base64

        ]

    ];


    /*
    |--------------------------------------------------------------------------
    | Optional crop hint
    |--------------------------------------------------------------------------
    |
    | Only send this if the current Kindwise endpoint accepts
    | the crop field. For now we deliberately don't send it.
    |
    | The farmer's selected crop is still available to FIH
    | and can be stored alongside the diagnosis.
    |
    */


    /*
    |--------------------------------------------------------------------------
    | Encode JSON
    |--------------------------------------------------------------------------
    */

    $json =
        json_encode(
            $request,
            JSON_UNESCAPED_UNICODE
            |
            JSON_UNESCAPED_SLASHES
        );


    if ($json === false) {

        return [

            'success' => false,

            'status' => 'request_error',

            'error' =>
                'Could not prepare the Kindwise request.'

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Initialise cURL
    |--------------------------------------------------------------------------
    */

    $curl =
        curl_init(
            $request_url
        );


    if ($curl === false) {

        return [

            'success' => false,

            'status' => 'curl_error',

            'error' =>
                'Could not initialize cURL.'

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | cURL options
    |--------------------------------------------------------------------------
    */

    curl_setopt_array(

        $curl,

        [

            CURLOPT_POST =>
                true,

            CURLOPT_POSTFIELDS =>
                $json,

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_TIMEOUT =>
                $config['timeout'] ?? 60,

            CURLOPT_CONNECTTIMEOUT =>
                20,

            CURLOPT_HTTPHEADER => [

                'Content-Type: application/json',

                'Accept: application/json',

                'Api-Key: ' . $api_key

            ]

        ]

    );


    /*
    |--------------------------------------------------------------------------
    | Send request
    |--------------------------------------------------------------------------
    */

    $response =
        curl_exec(
            $curl
        );


    /*
    |--------------------------------------------------------------------------
    | cURL error
    |--------------------------------------------------------------------------
    */

    if ($response === false) {

        $curl_error =
            curl_error(
                $curl
            );


        $http_code =
            curl_getinfo(
                $curl,
                CURLINFO_HTTP_CODE
            );


        curl_close($curl);


        return [

            'success' => false,

            'status' =>
                'connection_error',

            'error' =>
                'Unable to contact Kindwise: '
                . $curl_error,

            'http_code' =>
                $http_code

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | HTTP information
    |--------------------------------------------------------------------------
    */

    $http_code =
        curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );


    $content_type =
        curl_getinfo(
            $curl,
            CURLINFO_CONTENT_TYPE
        );


    curl_close($curl);


    /*
    |--------------------------------------------------------------------------
    | Decode JSON
    |--------------------------------------------------------------------------
    */

    $data =
        json_decode(
            $response,
            true
        );


    /*
    |--------------------------------------------------------------------------
    | Invalid JSON
    |--------------------------------------------------------------------------
    */

    if (
        !is_array($data)
    ) {

        return [

            'success' => false,

            'status' =>
                'invalid_response',

            'error' =>
                'Kindwise returned invalid JSON.',

            'http_code' =>
                $http_code,

            'content_type' =>
                $content_type,

            'raw_response' =>
                $response

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | API error
    |--------------------------------------------------------------------------
    */

    if (
        $http_code < 200
        ||
        $http_code >= 300
    ) {

        return [

            'success' => false,

            'status' =>
                'api_error',

            'error' =>
                'Kindwise returned HTTP '
                . $http_code,

            'http_code' =>
                $http_code,

            'response' =>
                $data

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Successful diagnosis
    |--------------------------------------------------------------------------
    */

    return [

        'success' => true,

        'status' =>
            'completed',

        'http_code' =>
            $http_code,

        'data' =>
            $data

    ];
}