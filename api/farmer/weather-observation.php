<?php

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/security.php';


require_farmer();


$user_id = authenticated_farmer_id();


if (!$user_id) {

    http_response_code(401);

    exit('Unauthorized.');

}
if( $_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    exit('Method not allowed.');

}
verify_csrf();

/*
|--------------------------------------------------------------------------
| Input
|--------------------------------------------------------------------------
*/

$farm_id =
    trim(
        $_POST['farm_id'] ?? ''
    );


$observed_condition =
    trim(
        $_POST['observed_condition'] ?? ''
    );
$predicted_condition =
    trim($_POST['predicted_condition'] ?? '');


$farmer_comment =
    trim(
        $_POST['farmer_comment'] ?? ''
    );


if (
    $farm_id === '' ||
    $observed_condition === ''
) {

    exit(
        'Farm and weather condition are required.'
    );

}


/*
|--------------------------------------------------------------------------
| Verify farm belongs to farmer
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
    'user_id' => $user_id
]);


$farm =
    $farm_stmt->fetch(PDO::FETCH_ASSOC);


if (!$farm) {

    http_response_code(404);

    exit('Farm not found.');

}


/*
|--------------------------------------------------------------------------
| Get coordinates
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

    exit(
        'This farm does not have registered coordinates.'
    );

}


/*
|--------------------------------------------------------------------------
| Generate UUID
|--------------------------------------------------------------------------
|
| Use the existing FIH UUID helper.
|
|--------------------------------------------------------------------------
*/

if (
    !function_exists('generate_uuid')
) {

    http_response_code(500);

    exit(
        'UUID generation function is unavailable.'
    );

}


$observation_id =
    generate_uuid();


/*
|--------------------------------------------------------------------------
| Save observation
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
INSERT INTO weather_observations (
    id,
    user_id,
    farm_id,
    latitude,
    longitude,
    observed_at,
    predicted_condition,
    observed_condition,
    farmer_comment
)
VALUES (
    :id,
    :user_id,
    :farm_id,
    :latitude,
    :longitude,
    NOW(),
    :predicted_condition,
    :observed_condition,
    :farmer_comment
)
");


$stmt->execute([

    'id' =>
        $observation_id,

    'user_id' =>
        $user_id,

    'farm_id' =>
        $farm_id,

    'latitude' =>
        $coordinates['latitude'],

    'longitude' =>
        $coordinates['longitude'],

    'predicted_condition' =>
        $predicted_condition,

    'observed_condition' =>
        $observed_condition,

    'farmer_comment' =>
        $farmer_comment !== ''
            ? $farmer_comment
            : null

]);


/*
|--------------------------------------------------------------------------
| Return to farms
|--------------------------------------------------------------------------
*/

redirect(
    '/farmer/farms.php?observation=success'
);

exit;