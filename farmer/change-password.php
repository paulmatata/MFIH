<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_farmer();

$farmer_id = authenticated_farmer_id();

if (!$farmer_id) {
    redirect('/login.php');
}


$errors = [];

$success = null;


/*
|--------------------------------------------------------------------------
| Process password change
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();


    /*
    |--------------------------------------------------------------------------
    | Get submitted passwords
    |--------------------------------------------------------------------------
    */

    $current_password =
        $_POST['current_password'] ?? '';

    $new_password =
        $_POST['new_password'] ?? '';

    $confirm_password =
        $_POST['confirm_password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Basic validation
    |--------------------------------------------------------------------------
    */

    if ($current_password === '') {

        $errors[] =
            'Please enter your current password.';

    }


    if ($new_password === '') {

        $errors[] =
            'Please enter a new password.';

    }


    if ($confirm_password === '') {

        $errors[] =
            'Please confirm your new password.';

    }


    /*
    |--------------------------------------------------------------------------
    | New password confirmation
    |--------------------------------------------------------------------------
    */

    if (
        $new_password !== '' &&
        $confirm_password !== '' &&
        $new_password !== $confirm_password
    ) {

        $errors[] =
            'The new passwords do not match.';

    }


    /*
    |--------------------------------------------------------------------------
    | Password strength
    |--------------------------------------------------------------------------
    |
    | FIH password policy:
    |
    | Minimum 8 characters
    | At least one number
    | At least one special character
    |
    */

    if ($new_password !== '') {

        if (strlen($new_password) < 8) {

            $errors[] =
                'New password must contain at least 8 characters.';

        }


        if (!preg_match('/[0-9]/', $new_password)) {

            $errors[] =
                'New password must contain at least one number.';

        }


        if (!preg_match('/[^A-Za-z0-9]/', $new_password)) {

            $errors[] =
                'New password must contain at least one special character.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Retrieve current password hash
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $sql = "
            SELECT password_hash
            FROM users
            WHERE id = :user_id
              AND role = 'farmer'
              AND status = 'active'
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'user_id' => $farmer_id
        ]);

        $user = $stmt->fetch();


        if (!$user) {

            $errors[] =
                'Your account could not be found.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Verify current password
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        if (
            !password_verify(
                $current_password,
                $user['password_hash']
            )
        ) {

            $errors[] =
                'Your current password is incorrect.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Prevent reusing the current password
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        if (
            password_verify(
                $new_password,
                $user['password_hash']
            )
        ) {

            $errors[] =
                'Your new password must be different from your current password.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Update password
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $new_hash = password_hash(
            $new_password,
            PASSWORD_DEFAULT
        );


        $update_sql = "
            UPDATE users
            SET password_hash = :password_hash
            WHERE id = :user_id
              AND role = 'farmer'
              AND status = 'active'
            LIMIT 1
        ";

        $update_stmt = $pdo->prepare($update_sql);

        $update_stmt->execute([
            'password_hash' => $new_hash,
            'user_id'       => $farmer_id
        ]);


        $success =
            'Your password has been changed successfully.';


        /*
        |--------------------------------------------------------------------------
        | Clear password fields
        |--------------------------------------------------------------------------
        */

        $current_password = '';
        $new_password = '';
        $confirm_password = '';

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Change Password | FIH</title>


    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/farmer.css"
    >

</head>


<body>

<?php require_once __DIR__ . '/../includes/farmer_sidebar.php'; ?>


<main class="farmer-dashboard-layout">

    <section class="profile-page">

        <div class="profile-heading">

            <div>

                <p class="profile-eyebrow">
                    Account Security
                </p>

                <h1>
                    Change Password
                </h1>

                <p>
                    Update your password to keep your FIH account secure.
                </p>

            </div>

        </div>


        <?php if ($success): ?>

            <div class="form-message success">
                <?= e($success) ?>
            </div>

        <?php endif; ?>


        <?php if ($errors): ?>

            <div class="form-message error">

                <ul>

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= e($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <section class="profile-card">

            <form
                method="POST"
                action=""
                class="farmer-form"
            >

                <?= csrf_field() ?>


                <!-- Current password -->

                <div class="form-group">

                    <label for="current_password">
                        Current Password
                    </label>

                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                        required
                    >

                </div>


                <!-- New password -->

                <div class="form-group">

                    <label for="new_password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >

                    <small>
                        Minimum 8 characters, including a number
                        and a special character.
                    </small>

                </div>


                <!-- Confirm -->

                <div class="form-group">

                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >

                </div>

<br><br>
                <!-- Actions -->

                <div class="form-actions">

                    <a
                        href="<?= e(FIH_BASE_URL) ?>/farmer/profile.php"
                        class="secondary-button"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="primary-button"
                    >
                        Change Password
                    </button>

                </div>

            </form>

        </section>

    </section>

</main>


<script
    src="<?= e(FIH_BASE_URL) ?>/assets/js/global.js"
></script>

</body>

</html>