<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_farmer();

$farmer_id = authenticated_farmer_id();

if (!$farmer_id) {
    redirect('/login.php');
}


/*
|--------------------------------------------------------------------------
| Load current farmer information
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        full_name,
        email,
        phone
    FROM users
    WHERE id = :user_id
      AND role = 'farmer'
    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'user_id' => $farmer_id
]);

$farmer = $stmt->fetch();


if (!$farmer) {

    http_response_code(404);

    exit('Farmer account could not be found.');
}


/*
|--------------------------------------------------------------------------
| Form values
|--------------------------------------------------------------------------
*/

$full_name = $farmer['full_name'] ?? '';
$email     = $farmer['email'] ?? '';
$phone     = $farmer['phone'] ?? '';

$errors = [];

$success = null;


/*
|--------------------------------------------------------------------------
| Process form
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF protection
    |--------------------------------------------------------------------------
    */

    verify_csrf();


    /*
    |--------------------------------------------------------------------------
    | Collect input
    |--------------------------------------------------------------------------
    */

    $full_name = trim($_POST['full_name'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $phone = trim($_POST['phone'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | Validate full name
    |--------------------------------------------------------------------------
    */

    if ($full_name === '') {

        $errors[] = 'Full name is required.';

    } elseif (mb_strlen($full_name) < 2) {

        $errors[] = 'Full name must contain at least 2 characters.';

    }


    /*
    |--------------------------------------------------------------------------
    | Validate email
    |--------------------------------------------------------------------------
    */

    if ($email === '') {

        $errors[] = 'Email address is required.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = 'Please enter a valid email address.';

    }


    /*
    |--------------------------------------------------------------------------
    | Validate phone
    |--------------------------------------------------------------------------
    */

    if ($phone === '') {

        $errors[] = 'Phone number is required.';

    }


    /*
    |--------------------------------------------------------------------------
    | Check email uniqueness
    |--------------------------------------------------------------------------
    */

if (!$errors) {

    /*
    |--------------------------------------------------------------------------
    | Check email and phone uniqueness
    |--------------------------------------------------------------------------
    */

    $duplicate_sql = "
        SELECT
            email,
            phone
        FROM users
        WHERE id != :user_id
          AND (
                email = :email
                OR phone = :phone
          )
        LIMIT 1
    ";

    $duplicate_stmt = $pdo->prepare($duplicate_sql);

    $duplicate_stmt->execute([
        'user_id' => $farmer_id,
        'email'   => $email,
        'phone'   => $phone
    ]);

    $duplicate = $duplicate_stmt->fetch();


    if ($duplicate) {

        if (
            !empty($duplicate['email']) &&
            $duplicate['email'] === $email
        ) {
            $errors[] = 'That email address is already in use.';
        }


        if (
            !empty($duplicate['phone']) &&
            $duplicate['phone'] === $phone
        ) {
            $errors[] = 'That phone number is already in use.';
        }

    }

}


    /*
    |--------------------------------------------------------------------------
    | Update account
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $update_sql = "
            UPDATE users
            SET
                full_name = :full_name,
                email = :email,
                phone = :phone
            WHERE id = :user_id
              AND role = 'farmer'
            LIMIT 1
        ";

        $update = $pdo->prepare($update_sql);

        $update->execute([
            'full_name' => $full_name,
            'email'     => $email,
            'phone'     => $phone,
            'user_id'   => $farmer_id
        ]);


        $success = 'Your profile has been updated successfully.';

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

    <title>Edit Profile | FIH</title>


    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/farmer.css"
    >
        <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/edit_farmer_profile.css"
    >

</head>


<body>

<?php require_once __DIR__ . '/../includes/farmer_sidebar.php'; ?>


<main class="farmer-dashboard-layout">

    <section class="profile-page">

        <div class="profile-heading">

            <div>

                <p class="profile-eyebrow">
                    Farmer Account
                </p>

                <h1>
                    Edit Profile
                </h1>

                <p>
                    Update your personal account information.
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


                <!-- Full name -->

                <div class="form-group">

                    <label for="full_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?= e($full_name) ?>"
                        autocomplete="name"
                        required
                    >

                </div>


                <!-- Email -->

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e($email) ?>"
                        autocomplete="email"
                        required
                    >

                </div>


                <!-- Phone -->

                <div class="form-group">

                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?= e($phone) ?>"
                        autocomplete="tel"
                        required
                    >

                </div>


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
                        Save Changes
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