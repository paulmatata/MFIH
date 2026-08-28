<?php

/**
 * FIH Authentication Service
 *
 * Handles:
 * - User registration
 * - Login
 * - Logout
 * - Current authenticated user
 * - Authentication checks
 * - Role checks
 *
 * Authentication is intentionally kept separate from
 * general security helpers in security.php.
 */


/*
|--------------------------------------------------------------------------
| UUID Generation
|--------------------------------------------------------------------------
*/

/**
 * Generate a UUID v4.
 *
 * FIH uses CHAR(36) UUID identifiers throughout
 * the application database.
 */
function generate_uuid(): string
{
    $data = random_bytes(16);

    // Set UUID version to 4.
    $data[6] = chr(
        ord($data[6]) & 0x0f | 0x40
    );

    // Set UUID variant.
    $data[8] = chr(
        ord($data[8]) & 0x3f | 0x80
    );

    return sprintf(
        '%s-%s-%s-%s-%s',
        bin2hex(substr($data, 0, 4)),
        bin2hex(substr($data, 4, 2)),
        bin2hex(substr($data, 6, 2)),
        bin2hex(substr($data, 8, 2)),
        bin2hex(substr($data, 10, 6))
    );
}


/*
|--------------------------------------------------------------------------
| Authentication State
|--------------------------------------------------------------------------
*/

/**
 * Determine whether a user is currently authenticated.
 */
function is_authenticated(): bool
{
    start_secure_session();

    return isset($_SESSION['user_id'])
        && is_string($_SESSION['user_id'])
        && $_SESSION['user_id'] !== '';
}


/**
 * Return the authenticated user's ID.
 */
function authenticated_user_id(): ?string
{
    start_secure_session();

    if (!is_authenticated()) {
        return null;
    }

    return $_SESSION['user_id'];
}


/**
 * Return the authenticated user's role.
 */
function authenticated_user_role(): ?string
{
    start_secure_session();

    if (!is_authenticated()) {
        return null;
    }

    return $_SESSION['user_role'] ?? null;
}


/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

/**
 * Create a new user account.
 *
 * Returns the newly-created user UUID.
 */
function register_user(
    PDO $pdo,
    string $full_name,
    ?string $email,
    ?string $phone,
    string $password,
    string $role
): string {

    $full_name = trim($full_name);
    $email = $email !== null
        ? trim($email)
        : null;
    $phone = $phone !== null
        ? trim($phone)
        : null;

    /*
    |--------------------------------------------------------------------------
    | Validate role
    |--------------------------------------------------------------------------
    */

    $allowed_roles = [
        'farmer',
        'organization',
        'government'
    ];

    if (!in_array($role, $allowed_roles, true)) {
        throw new InvalidArgumentException(
            'Invalid user role.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validate name
    |--------------------------------------------------------------------------
    */

    if ($full_name === '') {
        throw new InvalidArgumentException(
            'Full name is required.'
        );
    }

    if (mb_strlen($full_name) > 150) {
        throw new InvalidArgumentException(
            'Full name is too long.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validate contact information
    |--------------------------------------------------------------------------
    */

    if ($email === null && $phone === null) {
        throw new InvalidArgumentException(
            'Email or phone number is required.'
        );
    }

    if ($email !== null) {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'Invalid email address.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Password validation
    |--------------------------------------------------------------------------
    */

    if (strlen($password) < 8) {
        throw new InvalidArgumentException(
            'Password must contain at least 8 characters.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Check duplicate email
    |--------------------------------------------------------------------------
    */

    if ($email !== null) {

        $stmt = $pdo->prepare(
            'SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            throw new RuntimeException(
                'An account with this email already exists.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Check duplicate phone
    |--------------------------------------------------------------------------
    */

    if ($phone !== null) {

        $stmt = $pdo->prepare(
            'SELECT id
             FROM users
             WHERE phone = ?
             LIMIT 1'
        );

        $stmt->execute([$phone]);

        if ($stmt->fetch()) {
            throw new RuntimeException(
                'An account with this phone number already exists.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create user
    |--------------------------------------------------------------------------
    */

    $password_hash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    $stmt = $pdo->prepare(
        'INSERT INTO users
        (
            id,
            full_name,
            email,
            phone,
            password_hash,
            role
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?
        )'
    );

    $stmt->execute([
        $user_id,
        $full_name,
        $email,
        $phone,
        $password_hash,
        $role
    ]);

    return $user_id;
}


/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

/**
 * Authenticate a user using email or phone.
 */
function login_user(
    PDO $pdo,
    string $identifier,
    string $password
): bool {

    $identifier = trim($identifier);

    if ($identifier === '') {
        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Find user
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        'SELECT
            id,
            full_name,
            password_hash,
            role,
            status
         FROM users
         WHERE email = ?
            OR phone = ?
         LIMIT 1'
    );

    $stmt->execute([
        $identifier,
        $identifier
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$user) {
        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Account status
    |--------------------------------------------------------------------------
    */

    if ($user['status'] !== 'active') {
        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Verify password
    |--------------------------------------------------------------------------
    */

    if (!password_verify(
        $password,
        $user['password_hash']
    )) {
        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Regenerate session ID
    |--------------------------------------------------------------------------
    */

    start_secure_session();

    session_regenerate_id(true);


    /*
    |--------------------------------------------------------------------------
    | Store authentication state
    |--------------------------------------------------------------------------
    */

    $_SESSION['user_id'] = $user['id'];

    $_SESSION['user_role'] = $user['role'];


    /*
    |--------------------------------------------------------------------------
    | Update last login
    |--------------------------------------------------------------------------
    */

    $update = $pdo->prepare(
        'UPDATE users
         SET last_login_at = CURRENT_TIMESTAMP
         WHERE id = ?'
    );

    $update->execute([
        $user['id']
    ]);

    return true;
}


/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

/**
 * Completely destroy the authenticated session.
 */
function logout_user(): void
{
    start_secure_session();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}


/*
|--------------------------------------------------------------------------
| Authentication Guards
|--------------------------------------------------------------------------
*/

/**
 * Require an authenticated user.
 *
 * Redirects unauthenticated users to login.
 */
function require_authentication(): void
{
    if (!is_authenticated()) {
        redirect('/login.php');
    }
}


/**
 * Require a specific role.
 */
function require_role(string $role): void
{
    require_authentication();

    if (authenticated_user_role() !== $role) {
        http_response_code(403);

        exit('Access denied.');
    }
}