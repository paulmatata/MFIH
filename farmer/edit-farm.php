<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_farmer();

$farmer_id = authenticated_farmer_id();

if (!$farmer_id) {
    redirect('/login.php');
}

$errors = [];

$old = [
    'farm_name' => '',
    'county_id' => '',
    'sub_county_id' => '',
    'ward_id' => '',
    'farm_size_acres' => '',
    'water_source' => '',
    'irrigation' => ''
];


/*
|--------------------------------------------------------------------------
| Get current farmer profile
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        farm_name,
        county_id,
        sub_county_id,
        ward_id,
        farm_size_acres,
        water_source,
        irrigation
    FROM farmer_profiles
    WHERE user_id = :user_id
    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'user_id' => $farmer_id
]);

$farm = $stmt->fetch();

if (!$farm) {
    redirect('/farmer/profile.php');
}


/*
|--------------------------------------------------------------------------
| Load existing values
|--------------------------------------------------------------------------
*/

$old = [
    'farm_name'       => $farm['farm_name'] ?? '',
    'county_id'       => $farm['county_id'] ?? '',
    'sub_county_id'   => $farm['sub_county_id'] ?? '',
    'ward_id'         => $farm['ward_id'] ?? '',
    'farm_size_acres' => $farm['farm_size_acres'] ?? '',
    'water_source'    => $farm['water_source'] ?? '',
    'irrigation'      => $farm['irrigation'] ?? ''
];


/*
|--------------------------------------------------------------------------
| Counties
|--------------------------------------------------------------------------
*/

$counties_stmt = $pdo->query("
    SELECT id, name
    FROM counties
    ORDER BY name ASC
");

$counties = $counties_stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Process update
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();


    foreach ($old as $key => $value) {
        $old[$key] = trim($_POST[$key] ?? '');
    }


    if ($old['farm_name'] === '') {
        $errors[] = 'Farm name is required.';
    }


    if ($old['county_id'] === '') {
        $errors[] = 'County is required.';
    }


    if ($old['sub_county_id'] === '') {
        $errors[] = 'Sub-county is required.';
    }


    if ($old['ward_id'] === '') {
        $errors[] = 'Ward is required.';
    }


    if (
        $old['farm_size_acres'] === '' ||
        !is_numeric($old['farm_size_acres']) ||
        $old['farm_size_acres'] <= 0
    ) {
        $errors[] = 'Enter a valid farm size.';
    }


    /*
    |--------------------------------------------------------------------------
    | Verify location hierarchy
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $location_sql = "
            SELECT w.id
            FROM sub_counties sc

            INNER JOIN wards w
                ON w.sub_county_id = sc.id

            WHERE sc.id = :sub_county_id
              AND sc.county_id = :county_id
              AND w.id = :ward_id

            LIMIT 1
        ";

        $location_stmt = $pdo->prepare($location_sql);

        $location_stmt->execute([
            'county_id'     => $old['county_id'],
            'sub_county_id' => $old['sub_county_id'],
            'ward_id'       => $old['ward_id']
        ]);


        if (!$location_stmt->fetch()) {

            $errors[] =
                'The selected location is invalid.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Update profile
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $update_sql = "
            UPDATE farmer_profiles

            SET
                farm_name = :farm_name,
                county_id = :county_id,
                sub_county_id = :sub_county_id,
                ward_id = :ward_id,
                farm_size_acres = :farm_size_acres,
                water_source = :water_source,
                irrigation = :irrigation,
                updated_at = NOW()

            WHERE id = :farm_id
              AND user_id = :user_id

            LIMIT 1
        ";

        $update_stmt = $pdo->prepare($update_sql);

        $update_stmt->execute([
            'farm_name'       => $old['farm_name'],
            'county_id'       => $old['county_id'],
            'sub_county_id'   => $old['sub_county_id'],
            'ward_id'         => $old['ward_id'],
            'farm_size_acres' => $old['farm_size_acres'],
            'water_source'    => $old['water_source'],
            'irrigation'      => $old['irrigation'],
            'farm_id'         => $farm['id'],
            'user_id'         => $farmer_id
        ]);


        redirect('/farmer/profile.php');
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

    <meta
        name="fih-base-url"
        content="<?= e(FIH_BASE_URL) ?>"
    >

    <title>Edit Farm | FIH</title>

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

            <p class="profile-eyebrow">
                Farm Management
            </p>

            <h1>
                Edit Farm
            </h1>

            <p>
                Update your farm information and location.
            </p>

        </div>


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
                class="farmer-form"
            >

                <?= csrf_field() ?>


                <div class="form-group">

                    <label for="farm_name">
                        Farm Name
                    </label>

                    <input
                        type="text"
                        id="farm_name"
                        name="farm_name"
                        value="<?= e($old['farm_name']) ?>"
                        required
                    >

                </div>


                <div class="form-group">

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

                </div>


                <div class="form-group">

                    <label for="sub_county_id">
                        Sub-county
                    </label>

                    <select
                        id="sub_county_id"
                        name="sub_county_id"
                        data-selected="<?= e($old['sub_county_id']) ?>"
                        required
                    >

                        <option value="">
                            Loading sub-counties...
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="ward_id">
                        Ward
                    </label>

                    <select
                        id="ward_id"
                        name="ward_id"
                        data-selected="<?= e($old['ward_id']) ?>"
                        required
                    >

                        <option value="">
                            Loading wards...
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="farm_size_acres">
                        Farm Size (acres)
                    </label>

                    <input
                        type="number"
                        id="farm_size_acres"
                        name="farm_size_acres"
                        value="<?= e($old['farm_size_acres']) ?>"
                        min="0.01"
                        step="0.01"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="water_source">
                        Water Source
                    </label>

                    <input
                        type="text"
                        id="water_source"
                        name="water_source"
                        value="<?= e($old['water_source']) ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="irrigation">
                        Irrigation
                    </label>

                    <select
                        id="irrigation"
                        name="irrigation"
                    >

                        <option value="">
                            Select
                        </option>

                        <option
                            value="Yes"
                            <?= $old['irrigation'] === 'Yes'
                                ? 'selected'
                                : '' ?>
                        >
                            Yes
                        </option>

                        <option
                            value="No"
                            <?= $old['irrigation'] === 'No'
                                ? 'selected'
                                : '' ?>
                        >
                            No
                        </option>

                    </select>

                </div>


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