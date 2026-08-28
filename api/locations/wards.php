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
| Get sub-county ID
|--------------------------------------------------------------------------
*/

$sub_county_id = $_GET['sub_county_id'] ?? '';

$sub_county_id = trim($sub_county_id);


if ($sub_county_id === '') {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Sub-county ID is required.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Fetch wards
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT id, name
     FROM wards
     WHERE sub_county_id = ?
     ORDER BY name ASC'
);

$stmt->execute([
    $sub_county_id
]);


$wards = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


echo json_encode([
    'success' => true,
    'data' => $wards
]);