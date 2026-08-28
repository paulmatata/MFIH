<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_farmer();

$farmer_id = authenticated_farmer_id();

if (!$farmer_id) {
    header('Location: ' . FIH_BASE_URL . '/login.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Fetch farmer profile
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        fp.id,
        fp.user_id,
        fp.county_id,
        fp.sub_county_id,
        fp.ward_id,
        fp.farm_size_acres,
        fp.water_source,
        fp.irrigation,

        c.name AS county_name,
        sc.name AS sub_county_name,
        w.name AS ward_name

     FROM farmer_profiles fp

     INNER JOIN counties c
        ON c.id = fp.county_id

     INNER JOIN sub_counties sc
        ON sc.id = fp.sub_county_id

     INNER JOIN wards w
        ON w.id = fp.ward_id

     WHERE fp.user_id = ?

     LIMIT 1'
);

$stmt->execute([
    $farmer_id
]);

$farmer = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$farmer) {

    http_response_code(404);

    exit('Farmer profile not found.');
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

    <title>
        Farmer Dashboard | FIH
    </title>

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

<div class="farmer-dashboard-layout">
<header class="farmer-header">

    <div class="container farmer-header-inner">

        <div>

            <span class="dashboard-label">
                FIH
            </span>

            <h1>
                Farmer Dashboard
            </h1>

        </div>

<form
    method="POST"
    action="<?= e(FIH_BASE_URL) ?>/logout.php"
    class="logout-form"
>

    <?= csrf_field() ?>

    <button
        type="submit"
        class="secondary-button"
    >
        Logout
    </button>

</form>

    </div>

</header>

<main class="container farmer-dashboard">
    <!-- Location -->

    <section class="dashboard-card">

        <div class="dashboard-card-header">

            <div>
                <span class="dashboard-eyebrow">
                    FARM LOCATION
                </span>

                <h2>
                    <?= e($farmer['ward_name']) ?>
                </h2>
            </div>

            <span class="dashboard-icon">
                📍
            </span>

        </div>

        <p class="dashboard-muted">

            <?= e($farmer['county_name']) ?>,
            <?= e($farmer['sub_county_name']) ?>,
            <?= e($farmer['ward_name']) ?>

        </p>

    </section>


    <!-- Farm summary -->

    <section class="dashboard-grid">

        <article class="dashboard-card">

            <span class="dashboard-eyebrow">
                FARM SIZE
            </span>

            <strong class="dashboard-value">

                <?= e(
                    number_format(
                        (float) $farmer['farm_size_acres'],
                        2
                    )
                ) ?>

            </strong>

            <span class="dashboard-muted">
                acres
            </span>

        </article>


        <article class="dashboard-card">

            <span class="dashboard-eyebrow">
                WATER SOURCE
            </span>

            <strong class="dashboard-value-small">

                <?= e(
                    $farmer['water_source'] ?: 'Not provided'
                ) ?>

            </strong>

        </article>


        <article class="dashboard-card">

            <span class="dashboard-eyebrow">
                IRRIGATION
            </span>

            <strong class="dashboard-value-small">

                <?= $farmer['irrigation']
                    ? 'Available'
                    : 'Not indicated'
                ?>

            </strong>

        </article>

    </section>


    <!-- Intelligence -->

    <section class="dashboard-card intelligence-placeholder">

        <span class="dashboard-eyebrow">
            FOOD & WATER INTELLIGENCE
        </span>

        <h2>
            Your farm intelligence will appear here
        </h2>

        <p class="dashboard-muted">

            Weather, soil, water availability,
            crop recommendations, disease warnings
            and market intelligence will be connected
            to your location and farm data.

        </p>

    </section>
</main>
</div>
<script
    src="<?= e(FIH_BASE_URL) ?>/assets/js/global.js"
></script>
</body>

</html>