<?php

/**
 * FIH Application Bootstrap
 *
 * Loads the core configuration required by the application.
 *
 * This file should be included before using application-wide
 * configuration values or shared services.
 */

// Prevent direct execution.
if (!defined('FIH_BOOTSTRAPPED')) {
    define('FIH_BOOTSTRAPPED', true);
}

// Load application configuration.
require_once __DIR__ . '/../config/app.php';

// Load database configuration.
require_once __DIR__ . '/../config/database.php';

//Load security helpers.
require_once __DIR__ . '/../includes/security.php';

//Load user auth
require_once __DIR__ . '/../includes/auth.php';