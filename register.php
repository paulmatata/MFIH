<?php

require_once __DIR__ . '/includes/bootstrap.php';
include  'includes/navbar.php';

start_secure_session();

$errors = [];

$old = [
    'full_name'       => $_POST['full_name'] ?? '',
    'phone'           => $_POST['phone'] ?? '',
    'email'           => $_POST['email'] ?? '',
    'county_id'       => $_POST['county_id'] ?? '',
    'sub_county_id'   => $_POST['sub_county_id'] ?? '',
    'ward_id'         => $_POST['ward_id'] ?? '',
    'farm_size_acres' => $_POST['farm_size_acres'] ?? '',
    'water_source'    => $_POST['water_source'] ?? '',
    'irrigation'      => $_POST['irrigation'] ?? '',
];


/*
|--------------------------------------------------------------------------
| Handle registration
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    if (!verify_csrf_token(post_value('csrf_token'))) {
        $errors[] = 'Your session has expired. Please refresh and try again.';
    }


    /*
    |--------------------------------------------------------------------------
    | Personal information
    |--------------------------------------------------------------------------
    */

    $full_name = trim($old['full_name']);
    $phone     = trim($old['phone']);
    $email     = trim($old['email']);


    /*
    |--------------------------------------------------------------------------
    | Farm information
    |--------------------------------------------------------------------------
    */

    $county_id     = trim($old['county_id']);
    $sub_county_id = trim($old['sub_county_id']);
    $ward_id       = trim($old['ward_id']);

    $farm_size_acres = trim($old['farm_size_acres']);
    $water_source    = trim($old['water_source']);
    $irrigation      = trim($old['irrigation']);


    /*
    |--------------------------------------------------------------------------
    | Security information
    |--------------------------------------------------------------------------
    */

    $password = post_value('password', false);

    $password_confirmation = post_value(
        'password_confirmation',
        false
    );

    $terms_accepted = isset($_POST['terms_accepted']);


    /*
    |--------------------------------------------------------------------------
    | Validate personal information
    |--------------------------------------------------------------------------
    */

    if ($full_name === '') {
        $errors[] = 'Please enter your full name.';
    }

    if ($phone === '') {
        $errors[] = 'Please enter your phone number.';
    }


    if ($email !== '') {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate farm information
    |--------------------------------------------------------------------------
    */

    if ($county_id === '') {
        $errors[] = 'Please select your county.';
    }

    if ($sub_county_id === '') {
        $errors[] = 'Please select your sub-county.';
    }

    if ($ward_id === '') {
        $errors[] = 'Please select your ward.';
    }

    if (
        $farm_size_acres !== '' &&
        (
            !is_numeric($farm_size_acres) ||
            (float) $farm_size_acres <= 0
        )
    ) {
        $errors[] = 'Please enter a valid farm size.';
    }


    /*
    |--------------------------------------------------------------------------
    | Validate password
    |--------------------------------------------------------------------------
    */

    if (strlen($password) < 8) {

        $errors[] =
            'Password must contain at least 8 characters.';
    }

    if (!preg_match('/[0-9]/', $password)) {

        $errors[] =
            'Password must contain at least one number.';
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {

        $errors[] =
            'Password must contain at least one special character.';
    }

    if ($password !== $password_confirmation) {

        $errors[] =
            'The passwords do not match.';
    }


    /*
    |--------------------------------------------------------------------------
    | Terms
    |--------------------------------------------------------------------------
    */

    if (!$terms_accepted) {

        $errors[] =
            'You must accept the FIH terms and data-use policy.';
    }


    /*
    |--------------------------------------------------------------------------
    | Validate location relationship
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $location_check = $pdo->prepare(
            'SELECT w.id
             FROM wards w
             INNER JOIN sub_counties sc
                 ON sc.id = w.sub_county_id
             INNER JOIN counties c
                 ON c.id = sc.county_id
             WHERE c.id = ?
               AND sc.id = ?
               AND w.id = ?
             LIMIT 1'
        );

        $location_check->execute([
            $county_id,
            $sub_county_id,
            $ward_id
        ]);

        if (!$location_check->fetch()) {

            $errors[] =
                'The selected county, sub-county and ward combination is invalid.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create account
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Create user
            |--------------------------------------------------------------------------
            */

            $user_id = register_user(
                $pdo,
                $full_name,
                $email !== '' ? $email : null,
                $phone,
                $password,
                'farmer'
            );


            /*
            |--------------------------------------------------------------------------
            | Create farmer profile
            |--------------------------------------------------------------------------
            */

            $profile_id = generate_uuid();

            $profile = $pdo->prepare(
                'INSERT INTO farmer_profiles
                (
                    id,
                    user_id,
                    county_id,
                    sub_county_id,
                    ward_id,
                    farm_size_acres,
                    water_source,
                    irrigation
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, ?, ?, ?
                )'
            );

            $profile->execute([
                $profile_id,
                $user_id,
                $county_id,
                $sub_county_id,
                $ward_id,
                $farm_size_acres !== ''
                    ? $farm_size_acres
                    : null,
                $water_source !== ''
                    ? $water_source
                    : null,
                $irrigation !== ''
                    ? $irrigation
                    : null
            ]);


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | Authenticate newly registered farmer
            |--------------------------------------------------------------------------
            */

            start_secure_session();

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_role'] = 'farmer';


            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            redirect('/farmer/');

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] =
                'We could not create your account right now. ' .
                $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Load counties
|--------------------------------------------------------------------------
*/

$counties = $pdo
    ->query(
        'SELECT id, name
         FROM counties
         ORDER BY name ASC'
    )
    ->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Create your FIH farmer account."
    >

    <title>Create Farmer Account | FIH</title>


    <!-- Main FIH styles -->

    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/style.css"
    >


    <!-- Authentication styles -->

    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/auth.css"
    >

</head>


<body>


<main class="auth-page">


    <section class="auth-card">


        <!-- =====================================================
             HEADER
             ===================================================== -->

        <header class="auth-header">

            <h1>
                Create your FIH account
            </h1>

            <p>
                Join FIH and help build better food
                and water intelligence for your community.
            </p>

        </header>



        <!-- =====================================================
             ERROR MESSAGES
             ===================================================== -->

        <?php if (!empty($errors)): ?>

            <div
                class="form-errors"
                role="alert"
            >

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?= e($error) ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>



        <!-- =====================================================
             REGISTRATION FORM
             ===================================================== -->

        <form
            method="POST"
            action=""
            id="farmerRegistrationForm"
            novalidate
        >

            <?= csrf_field() ?>



            <!-- =================================================
                 STEP 1
                 PERSONAL INFORMATION
                 ================================================= -->

            <section
                class="registration-step active"
                data-step="1"
            >

                <div class="step-indicator">

                    <span class="active">
                        1
                    </span>

                    <span>
                        2
                    </span>

                    <span>
                        3
                    </span>

                </div>


                <h2>
                    Personal information
                </h2>

                <p class="step-description">
                    Tell us a little about yourself.
                </p>


                <!-- Full name -->

                <label for="full_name">
                    Full name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    autocomplete="name"
                    maxlength="150"
                    required
                    value="<?= e($old['full_name']) ?>"
                >


                <!-- Phone -->

                <label for="phone">
                    Phone number
                </label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    inputmode="tel"
                    autocomplete="tel"
                    placeholder="e.g. 0712345678"
                    required
                    value="<?= e($old['phone']) ?>"
                >


                <!-- Email -->

                <label for="email">

                    Email

                    <small>
                        (optional)
                    </small>

                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    autocomplete="email"
                    value="<?= e($old['email']) ?>"
                >
<br><br>

                <button
                    type="button"
                    class="primary-button"
                    data-next-step
                >
                    Continue
                </button>

            </section>



            <!-- =================================================
                 STEP 2
                 FARM INFORMATION
                 ================================================= -->

            <section
                class="registration-step"
                data-step="2"
            >

                <div class="step-indicator">

                    <span>
                        1
                    </span>

                    <span class="active">
                        2
                    </span>

                    <span>
                        3
                    </span>

                </div>


                <h2>
                    Farm information
                </h2>

                <p class="step-description">
                    Tell us where you farm and provide
                    a few basic details about your farm.
                </p>


                <!-- County -->

                <label for="county_id">
                    County
                </label>

                <select
                    id="county_id"
                    name="county_id"
                    required
                >

                    <option value="">
                        Select county
                    </option>

                    <?php foreach ($counties as $county): ?>

                        <option
                            value="<?= e($county['id']) ?>"
                            <?= $old['county_id'] === $county['id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($county['name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <!-- Sub-county -->

                <label for="sub_county_id">
                    Sub-county
                </label>

                <select
                    id="sub_county_id"
                    name="sub_county_id"
                    required
                    disabled
                >

                    <option value="">
                        Select sub-county
                    </option>

                </select>


                <!-- Ward -->

                <label for="ward_id">

                    Ward

                    <strong>*</strong>

                </label>

                <select
                    id="ward_id"
                    name="ward_id"
                    required
                    disabled
                >

                    <option value="">
                        Select ward
                    </option>

                </select>


                <!-- Farm size -->

                <label for="farm_size_acres">

                    Farm size in acres

                    <small>
                        (optional)
                    </small>

                </label>

                <input
                    type="number"
                    id="farm_size_acres"
                    name="farm_size_acres"
                    min="0.01"
                    step="0.01"
                    inputmode="decimal"
                    placeholder="e.g. 2.5"
                    value="<?= e(
                        $old['farm_size_acres']
                    ) ?>"
                >


                <!-- Water source -->

                <label for="water_source">

                    Main water source

                    <small>
                        (optional)
                    </small>

                </label>

                <select
                    id="water_source"
                    name="water_source"
                >

                    <option value="">
                        Select water source
                    </option>

                    <option value="rainwater">
                        Rainwater
                    </option>

                    <option value="borehole">
                        Borehole
                    </option>

                    <option value="river">
                        River / Stream
                    </option>

                    <option value="dam">
                        Dam
                    </option>

                    <option value="water_tank">
                        Water tank
                    </option>

                    <option value="well">
                        Well
                    </option>

                    <option value="other">
                        Other
                    </option>

                </select>


                <!-- Irrigation -->

                <label for="irrigation">
                    Irrigation availability
                </label>

                <select
                    id="irrigation"
                    name="irrigation"
                >

                    <option value="">
                        Select option
                    </option>

                    <option value="yes">
                        Yes
                    </option>

                    <option value="no">
                        No
                    </option>

                </select>
<br><br>

                <div class="button-row">

                    <button
                        type="button"
                        class="secondary-button"
                        data-previous-step
                    >
                        Back
                    </button>

                    <button
                        type="button"
                        class="primary-button"
                        data-next-step
                    >
                        Continue
                    </button>

                </div>

            </section>



            <!-- =================================================
                 STEP 3
                 PASSWORD & CONSENT
                 ================================================= -->

            <section
                class="registration-step"
                data-step="3"
            >

                <div class="step-indicator">

                    <span>
                        1
                    </span>

                    <span>
                        2
                    </span>

                    <span class="active">
                        3
                    </span>

                </div>


                <h2>
                    Secure your account
                </h2>

                <p class="step-description">
                    Create a strong password to protect
                    your FIH account.
                </p>


                <!-- Password -->

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    required
                >

                <small>
                    At least 8 characters,
                    one number and one special character.
                </small>


                <!-- Confirm password -->

                <label for="password_confirmation">
                    Confirm password
                </label>

                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    required
                >


                <!-- Consent -->

                <label class="consent-option">

                    <input
                        type="checkbox"
                        name="terms_accepted"
                        value="1"
                        required
                    >

                    <span>
                        I agree to the FIH terms and
                        data-use policy.
                    </span>

                </label>
<br><br>

                <div class="button-row">

                    <button
                        type="button"
                        class="secondary-button"
                        data-previous-step
                    >
                        Back
                    </button>

                    <button
                        type="submit"
                        class="primary-button"
                    >
                        Create account
                    </button>

                </div>

            </section>

        </form>


    </section>

</main>



<!-- =========================================================
     REGISTRATION JAVASCRIPT
     ========================================================= -->
<script>
    const FIH_BASE_URL =
        <?= json_encode(FIH_BASE_URL) ?>;
</script>

<script
    src="<?= e(FIH_BASE_URL) ?>/assets/js/register.js"
    defer
></script>


</body>

</html>