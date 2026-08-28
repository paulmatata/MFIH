<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');


/*
|--------------------------------------------------------------------------
| Only allow GET
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
| Get county ID
|--------------------------------------------------------------------------
*/

$county_id = $_GET['county_id'] ?? '';

$county_id = trim($county_id);


if ($county_id === '') {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'County ID is required.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Fetch sub-counties
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT id, name
     FROM sub_counties
     WHERE county_id = ?
     ORDER BY name ASC'
);

$stmt->execute([
    $county_id
]);


$sub_counties = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


echo json_encode([
    'success' => true,
    'data' => $sub_counties
]);