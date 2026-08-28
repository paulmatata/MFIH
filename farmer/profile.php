<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';


/*
|--------------------------------------------------------------------------
| Farmer protection
|--------------------------------------------------------------------------
*/

require_farmer();


/*
|--------------------------------------------------------------------------
| Get authenticated farmer
|--------------------------------------------------------------------------
*/

$farmer_id = authenticated_farmer_id();

if (!$farmer_id) {
    redirect('/login.php');
}


/*
|--------------------------------------------------------------------------
| Fetch farmer profile
|--------------------------------------------------------------------------
|
| We retrieve the user's personal information together with
| the farmer's current location and farm information.
|
*/

$sql = "
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.phone,
        u.status,
        u.created_at,

        fp.id AS farmer_profile_id,
        fp.farm_size_acres,
        fp.water_source,
        fp.irrigation,

        c.name AS county_name,
        sc.name AS sub_county_name,
        w.name AS ward_name

    FROM users u

    LEFT JOIN farmer_profiles fp
        ON fp.user_id = u.id

    LEFT JOIN counties c
        ON c.id = fp.county_id

    LEFT JOIN sub_counties sc
        ON sc.id = fp.sub_county_id

    LEFT JOIN wards w
        ON w.id = fp.ward_id

    WHERE u.id = :user_id
      AND u.role = 'farmer'

    LIMIT 1
";


$stmt = $pdo->prepare($sql);

$stmt->execute([
    'user_id' => $farmer_id
]);

$farmer = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Profile not found
|--------------------------------------------------------------------------
*/

if (!$farmer) {

    http_response_code(404);

    exit('Farmer profile could not be found.');
}


/*
|--------------------------------------------------------------------------
| Display helpers
|--------------------------------------------------------------------------
*/

$full_name = $farmer['full_name'] ?? 'Not provided';

$email = $farmer['email'] ?? null;

$phone = $farmer['phone'] ?? null;

$farm_size = $farmer['farm_size_acres'];

$water_source = $farmer['water_source'];

$irrigation = (int) $farmer['irrigation'] === 1;

$county = $farmer['county_name'];

$sub_county = $farmer['sub_county_name'];

$ward = $farmer['ward_name'];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Profile | FIH</title>


    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/farmer_profile.css"
    >
        <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/farmer.css"
    >

</head>


<body>

    <?php require_once __DIR__ . '/../includes/farmer_sidebar.php'; ?>

<div class="farmer-dashboard-layout">
<main class="farmer-page">

    <section class="profile-page">

        <div class="profile-heading">

            <div>

                <p class="profile-eyebrow">
                    Farmer Account
                </p>

                <h1>
                    My Profile
                </h1>

                <p>
                    Manage your personal information and farm details.
                </p>

            </div>


            <div class="profile-actions">

                <a
                    href="<?= e(FIH_BASE_URL) ?>/farmer/edit-profile.php"
                    class="primary-button"
                >
                    Edit Profile
                </a>

            </div>

        </div>


        <!-- =====================================================
             PERSONAL INFORMATION
             ===================================================== -->

        <section class="profile-card">

            <div class="profile-card-header">

                <div>

                    <h2>
                        Personal Information
                    </h2>

                    <p>
                        Your basic FIH account information.
                    </p>

                </div>

            </div>


            <div class="profile-grid">

                <div class="profile-field">

                    <span class="profile-label">
                        Full Name
                    </span>

                    <strong>
                        <?= e($full_name) ?>
                    </strong>

                </div>


                <div class="profile-field">

                    <span class="profile-label">
                        Phone
                    </span>

                    <strong>
                        <?= e($phone ?: 'Not provided') ?>
                    </strong>

                </div>


                <div class="profile-field">

                    <span class="profile-label">
                        Email
                    </span>

                    <strong>
                        <?= e($email ?: 'Not provided') ?>
                    </strong>

                </div>


                <div class="profile-field">

                    <span class="profile-label">
                        Account Status
                    </span>

                    <strong>
                        <?= e(ucfirst($farmer['status'])) ?>
                    </strong>

                </div>

            </div>

        </section>


        <!-- =====================================================
             FARM INFORMATION
             ===================================================== -->

        <section class="profile-card">

            <div class="profile-card-header">

                <div>

                    <h2>
                        Farm Information
                    </h2>

                    <p>
                        Information currently associated with your farm.
                    </p>

                </div>

            </div>


            <div class="profile-grid">

                <div class="profile-field">

                    <span class="profile-label">
                        Farm Size
                    </span>

                    <strong>

                        <?php if ($farm_size !== null): ?>

                            <?= e(number_format((float) $farm_size, 2)) ?>
                            acres

                        <?php else: ?>

                            Not provided

                        <?php endif; ?>

                    </strong>

                </div>


                <div class="profile-field">

                    <span class="profile-label">
                        Water Source
                    </span>

                    <strong>
                        <?= e($water_source ?: 'Not provided') ?>
                    </strong>

                </div>


                <div class="profile-field">

                    <span class="profile-label">
                        Irrigation
                    </span>

                    <strong>

                        <?= $irrigation ? 'Yes' : 'No' ?>

                    </strong>

                </div>

            </div>

        </section>


        <!-- =====================================================
             FARM LOCATION
             ===================================================== -->

        <section class="profile-card">

            <div class="profile-card-header">

                <div>

                    <h2>
                        Farm Location
                    </h2>

                    <p>
                        The location currently registered with FIH.
                    </p>

                </div>

            </div>


            <div class="profile-grid">

                <div class="profile-field">

                    <span class="profile-label">
                        County
                    </span>

                    <strong>
                        <?= e($county ?: 'Not provided') ?>
                    </strong>

                </div>


                <div class="profile-field">

                    <span class="profile-label">
                        Sub-county
                    </span>

                    <strong>
                        <?= e($sub_county ?: 'Not provided') ?>
                    </strong>

                </div>


                <div class="profile-field">

                    <span class="profile-label">
                        Ward
                    </span>

                    <strong>
                        <?= e($ward ?: 'Not provided') ?>
                    </strong>

                </div>

            </div>

        </section>


        <!-- =====================================================
             ACCOUNT ACTIONS
             ===================================================== -->

        <section class="profile-card profile-security-card">

            <div class="profile-card-header">

                <div>

                    <h2>
                        Account Security
                    </h2>

                    <p>
                        Keep your account information secure.
                    </p>

                </div>

            </div>


            <div class="profile-actions-list">

                <a
                    href="<?= e(FIH_BASE_URL) ?>/farmer/change-password.php"
                    class="secondary-button"
                >
                    Change Password
                </a>

            </div>

        </section>

    </section>

</main>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>

</div>
</body>

</html>