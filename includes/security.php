<?php

/**
 * FIH Security Helpers
 *
 * Central security utilities used throughout the application.
 *
 * This file contains reusable functions for:
 * - Secure sessions
 * - CSRF protection
 * - Input handling
 * - Output escaping
 * - Safe redirects
 */

/*
|--------------------------------------------------------------------------
| Secure Session
|--------------------------------------------------------------------------
*/

/**
 * Start a secure application session.
 *
 * The session is only started once.
 */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $is_https = (
        isset($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    );

    session_set_cookie_params([
        'httponly' => true,
        'secure'   => $is_https,
        'samesite' => 'Lax'
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/

/**
 * Generate or retrieve the current CSRF token.
 */
function csrf_token(): string
{
    start_secure_session();

    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token']) ||
        strlen($_SESSION['csrf_token']) < 64
    ) {
        $_SESSION['csrf_token'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['csrf_token'];
}


/**
 * Return a hidden CSRF input for HTML forms.
 */
function csrf_field(): string
{
    $token = htmlspecialchars(
        csrf_token(),
        ENT_QUOTES,
        'UTF-8'
    );

    return '<input type="hidden" name="csrf_token" value="' .
        $token .
        '">';
}


/**
 * Verify a submitted CSRF token.
 */
function verify_csrf_token(?string $token): bool
{
    start_secure_session();

    if (
        !$token ||
        !isset($_SESSION['csrf_token'])
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['csrf_token'],
        $token
    );
}


/*
|--------------------------------------------------------------------------
| Input Handling
|--------------------------------------------------------------------------
*/

/**
 * Safely retrieve a POST value.
 *
 * Returns null when the field does not exist.
 */
function post_value(
    string $key,
    bool $trim = true
): ?string {
    if (!isset($_POST[$key])) {
        return null;
    }

    $value = $_POST[$key];

    if (!is_string($value)) {
        return null;
    }

    return $trim
        ? trim($value)
        : $value;
}


/**
 * Safely retrieve a GET value.
 */
function get_value(
    string $key,
    bool $trim = true
): ?string {
    if (!isset($_GET[$key])) {
        return null;
    }

    $value = $_GET[$key];

    if (!is_string($value)) {
        return null;
    }

    return $trim
        ? trim($value)
        : $value;
}


/*
|--------------------------------------------------------------------------
| Output Escaping
|--------------------------------------------------------------------------
*/

/**
 * Escape text before displaying it in HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| Redirects
|--------------------------------------------------------------------------
*/

/**
 * Redirect to an internal FIH URL.
 */
function redirect(string $path): never
{
    $path = '/' . ltrim($path, '/');

    header(
        'Location: ' . FIH_BASE_URL . $path
    );

    exit;
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $submitted_token = $_POST['csrf_token'] ?? '';

    $session_token = csrf_token();

    if (
        $submitted_token === '' ||
        !hash_equals($session_token, $submitted_token)
    ) {
        http_response_code(403);

        exit('Invalid security token. Please refresh the page and try again.');
    }
}