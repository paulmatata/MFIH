<?php

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');


/*
|--------------------------------------------------------------------------
| Only allow GET requests
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Requested action
|--------------------------------------------------------------------------
*/

$action = $_GET['action'] ?? '';


/*
|--------------------------------------------------------------------------
| Get sub-counties by county
|--------------------------------------------------------------------------
*/

if ($action === 'sub_counties') {

    $county_id = trim($_GET['county_id'] ?? '');


    if ($county_id === '') {

        echo json_encode([
            'success' => false,
            'message' => 'County ID is required.',
            'data' => []
        ]);

        exit;
    }


    $sql = "
        SELECT
            id,
            name
        FROM sub_counties
        WHERE county_id = :county_id
          AND is_active = 1
        ORDER BY name ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'county_id' => $county_id
    ]);


    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll()
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get wards by sub-county
|--------------------------------------------------------------------------
*/

if ($action === 'wards') {

    $sub_county_id =
        trim($_GET['sub_county_id'] ?? '');


    if ($sub_county_id === '') {

        echo json_encode([
            'success' => false,
            'message' => 'Sub-county ID is required.',
            'data' => []
        ]);

        exit;
    }


    $sql = "
        SELECT
            id,
            name
        FROM wards
        WHERE sub_county_id = :sub_county_id
          AND is_active = 1
        ORDER BY name ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'sub_county_id' => $sub_county_id
    ]);


    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll()
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Unknown action
|--------------------------------------------------------------------------
*/

http_response_code(400);

echo json_encode([
    'success' => false,
    'message' => 'Invalid location request.'
]);