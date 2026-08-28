<?php

/**
 * FIH Application Configuration
 *
 * Central configuration for the Food & Water Intelligence Hub.
 *
 * Keeping application-wide settings here prevents configuration
 * values from being scattered throughout the project.
 */

// Application environment.
// Change to "production" when deploying the live system.
define(
    'FIH_ENV',
    getenv('FIH_ENV') ?: 'development'
);

// Application name.
define(
    'FIH_NAME',
    'Farmers Intelligence Hub'
);

// Application short name.
define(
    'FIH_SHORT_NAME',
    'FIH'
);

// Application timezone.
date_default_timezone_set(
    getenv('FIH_TIMEZONE') ?: 'Africa/Nairobi'
);

// Base URL.
// Set FIH_BASE_URL as an environment variable when deploying.
define(
    'FIH_BASE_URL',
    rtrim(
        getenv('FIH_BASE_URL') ?: 'http://localhost/FIH',
        '/'
    )
);

// Application version.
define(
    'FIH_VERSION',
    '2.0.0'
);