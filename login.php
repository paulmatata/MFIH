<?php

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Redirect authenticated users
|--------------------------------------------------------------------------
*/

if (is_authenticated()) {

    $role = authenticated_user_role();

    if ($role === 'farmer') {
        redirect('/farmer/dashboard.php');
    }
}


/*
|--------------------------------------------------------------------------
| Login state
|--------------------------------------------------------------------------
*/

$error = '';

$identifier = '';


/*
|--------------------------------------------------------------------------
| Handle login
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * Verify CSRF token.
     */
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {

        $error = 'Invalid security token. Please refresh the page and try again.';

    } else {

        $identifier = trim(
            $_POST['identifier'] ?? ''
        );

        $password = $_POST['password'] ?? '';


        /*
         * Basic validation.
         */
        if ($identifier === '') {

            $error = 'Please enter your email or phone number.';

        } elseif ($password === '') {

            $error = 'Please enter your password.';

        } else {

            /*
             * IMPORTANT:
             *
             * login_user() in auth.php expects:
             *
             * login_user(PDO $pdo, string $identifier, string $password)
             */
            $authenticated = login_user(
                $pdo,
                $identifier,
                $password
            );


            if ($authenticated) {

                $role = authenticated_user_role();


                /*
                 * Farmer dashboard.
                 */
                if ($role === 'farmer') {

                    redirect('/farmer/dashboard.php');
                }


                /*
                 * These dashboards will be implemented later.
                 */
                if ($role === 'organization') {

                    $error =
                        'Organisation dashboard is not available yet.';

                } elseif ($role === 'government') {

                    $error =
                        'Government dashboard is not available yet.';

                } else {

                    $error =
                        'Your account role could not be recognized.';
                }

            } else {

                $error =
                    'Invalid email/phone or password.';
            }
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login | FIH</title>


    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/auth.css"
    >

</head>


<body>

<main class="auth-page">

    <section class="auth-card">

        <div class="auth-header">

            <h1>
                Welcome Back
            </h1>

            <p>
                Sign in to your FIH account.
            </p>

        </div>


        <?php if ($error !== ''): ?>

            <div
                class="auth-error"
                role="alert"
            >
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="<?= e(FIH_BASE_URL) ?>/login.php"
            class="auth-form"
        >

            <?= csrf_field() ?>


            <div class="form-group">

                <label for="identifier">
                    Email or Phone
                </label>

                <input
                    type="text"
                    id="identifier"
                    name="identifier"
                    value="<?= e($identifier) ?>"
                    placeholder="Enter your email or phone"
                    autocomplete="username"
                    required
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

            </div>


            <button
                type="submit"
                class="auth-submit"
            >
                Sign In
            </button>

        </form>


        <div class="auth-footer">

            <p>

                Don't have an account?

                <a
                    href="<?= e(FIH_BASE_URL) ?>/register.php"
                >
                    Create one
                </a>

            </p>

        </div>

    </section>

</main>

</body>

</html>