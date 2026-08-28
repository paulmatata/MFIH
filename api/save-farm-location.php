<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_farmer();

$farmer_id = authenticated_farmer_id();

header(
    'Content-Type: application/json; charset=utf-8'
);


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
}


verify_csrf();


$farm_id =
    trim($_POST['farm_id'] ?? '');

$latitude =
    $_POST['latitude'] ?? null;

$longitude =
    $_POST['longitude'] ?? null;

$accuracy =
    $_POST['accuracy'] ?? null;


if (
    $farm_id === '' ||
    !is_numeric($latitude) ||
    !is_numeric($longitude)
) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid farm location.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate coordinate ranges
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
        'message' => 'Invalid coordinates.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Confirm farm belongs to farmer
|--------------------------------------------------------------------------
*/

$farm_stmt = $pdo->prepare("
    SELECT id
    FROM farmer_profiles
    WHERE id = :farm_id
      AND user_id = :user_id
    LIMIT 1
");

$farm_stmt->execute([
    'farm_id' => $farm_id,
    'user_id' => $farmer_id
]);


if (!$farm_stmt->fetch()) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'You do not have access to this farm.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Save or update farm coordinates
|--------------------------------------------------------------------------
*/

$existing_stmt = $pdo->prepare("
    SELECT id
    FROM coordinates
    WHERE farm_id = :farm_id
      AND source = 'farm_location'
    LIMIT 1
");

$existing_stmt->execute([
    'farm_id' => $farm_id
]);

$existing = $existing_stmt->fetch();


if ($existing) {

    $update_stmt = $pdo->prepare("
        UPDATE coordinates

        SET
            latitude = :latitude,
            longitude = :longitude,
            accuracy_meters = :accuracy,
            updated_at = NOW()

        WHERE id = :id
    ");

    $update_stmt->execute([
        'latitude' => $latitude,
        'longitude' => $longitude,
        'accuracy' => $accuracy,
        'id' => $existing['id']
    ]);

} else {

    $coordinate_id = generate_uuid();


    $insert_stmt = $pdo->prepare("
        INSERT INTO coordinates (
            id,
            user_id,
            farm_id,
            latitude,
            longitude,
            accuracy_meters,
            source,
            created_at,
            updated_at
        )

        VALUES (
            :id,
            :user_id,
            :farm_id,
            :latitude,
            :longitude,
            :accuracy,
            'farm_location',
            NOW(),
            NOW()
        )
    ");

    $insert_stmt->execute([
        'id' => $coordinate_id,
        'user_id' => $farmer_id,
        'farm_id' => $farm_id,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'accuracy' => $accuracy
    ]);
}


echo json_encode([
    'success' => true,
    'message' => 'Farm location saved successfully.'
]);