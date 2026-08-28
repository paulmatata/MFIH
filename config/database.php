<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Farmers Innovation Hub
| Database Connection
|--------------------------------------------------------------------------*/


$dbHost = getenv('FIH_DB_HOST') ?: '127.0.0.1';
$dbName = getenv('FIH_DB_NAME') ?: 'farmers_innovation_hub';
$dbUser = getenv('FIH_DB_USER') ?: 'root';
$dbPass = getenv('FIH_DB_PASS') ?: '';


$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";


$options = [

    PDO::ATTR_ERRMODE =>
        PDO::ERRMODE_EXCEPTION,

    PDO::ATTR_DEFAULT_FETCH_MODE =>
        PDO::FETCH_ASSOC,

    PDO::ATTR_EMULATE_PREPARES =>
        false,

    PDO::ATTR_STRINGIFY_FETCHES =>
        false

];


try {

    $pdo = new PDO(
        $dsn,
        $dbUser,
        $dbPass,
        $options
    );

} catch (PDOException $exception) {

    /*
     * Never expose the actual database error
     * to visitors.
     *
     * Detailed errors belong in server logs.
     */

    error_log(
        'FIH Database connection failed: '
        . $exception->getMessage()
    );

    http_response_code(500);

    exit(
        'The system is temporarily unavailable. '
        . 'Please try again later.'
    );
}