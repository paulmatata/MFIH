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
| Get farmer's farms
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        farm_name
    FROM farmer_profiles
    WHERE user_id = :user_id
    ORDER BY created_at ASC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'user_id' => $farmer_id
]);

$farms = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Existing farm locations
|--------------------------------------------------------------------------
*/

$coordinates_sql = "
    SELECT
        farm_id,
        latitude,
        longitude,
        accuracy_meters
    FROM coordinates
    WHERE user_id = :user_id
      AND source = 'farm_location'
";

$coordinates_stmt = $pdo->prepare(
    $coordinates_sql
);

$coordinates_stmt->execute([
    'user_id' => $farmer_id
]);

$coordinates = [];

foreach (
    $coordinates_stmt->fetchAll()
    as $coordinate
) {

    $coordinates[
        $coordinate['farm_id']
    ] = $coordinate;

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

    <title>Farm Location | FIH</title>

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
        href="<?= e(FIH_BASE_URL) ?>/assets/css/farm-location.css"
        >
</head>

<body>

<?php require_once __DIR__ . '/../includes/farmer_sidebar.php'; ?>


<main class="farmer-dashboard-layout">

    <section class="farm-location-page">

        <div class="profile-heading">
<header class="farm-location-header">

            <h1>
                Farm Location
            </h1>

            <p class="location-card-description">
                Visit your farm and save its current location
                to receive weather information for that farm.
            </p>

        </div>
</header>
<section class="profile-card-location">
        <?php foreach ($farms as $farm): ?>

            <article class="profile-card farm-location-card">

                <h2>
                    <?= e(
                        $farm['farm_name'] ?: 'My Farm'
                    ) ?>
                </h2>


                <p>
                    <?php if (
                        isset($coordinates[$farm['id']])
                    ): ?>

                        Location saved.

                    <?php else: ?>

                        No farm location saved yet.

                    <?php endif; ?>
                </p>


                <div
                    id="location-status-<?= e($farm['id']) ?>"
                    class="location-status"
                >
                </div>


                <div class="location-preview">

                    <div>

                        <span>
                            Latitude
                        </span>

                        <strong
                            id="latitude-<?= e($farm['id']) ?>"
                        >
                            <?=
                                isset($coordinates[$farm['id']])
                                ? e(
                                    $coordinates[$farm['id']]['latitude']
                                )
                                : 'Not set'
                            ?>
                        </strong>

                    </div>


                    <div>

                        <span>
                            Longitude
                        </span>

                        <strong
                            id="longitude-<?= e($farm['id']) ?>"
                        >
                            <?=
                                isset($coordinates[$farm['id']])
                                ? e(
                                    $coordinates[$farm['id']]['longitude']
                                )
                                : 'Not set'
                            ?>
                        </strong>

                    </div>

                </div>


                <button
                    type="button"
                    class="primary-button location-button"
                    data-farm-id="<?= e($farm['id']) ?>"
                >
                    📍 Get My Farm Location
                </button>


                <button
                    type="button"
                    class="secondary-button save-location-button"
                    data-farm-id="<?= e($farm['id']) ?>"
                    disabled
                >
                    Save Farm Location
                </button>

            </article>

        <?php endforeach; ?>

    </section>
    </section>
</main>


<script
    src="<?= e(FIH_BASE_URL) ?>/assets/js/location.js"
></script>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        document
            .querySelectorAll('.location-button')
            .forEach(function (button) {

                button.addEventListener(
                    'click',
                    async function () {

                        const farmId =
                            this.dataset.farmId;

                        const status =
                            document.getElementById(
                                'location-status-' +
                                farmId
                            );

                        const latitude =
                            document.getElementById(
                                'latitude-' +
                                farmId
                            );

                        const longitude =
                            document.getElementById(
                                'longitude-' +
                                farmId
                            );

                        const saveButton =
                            document.querySelector(
                                '.save-location-button[data-farm-id="' +
                                farmId +
                                '"]'
                            );


                        status.textContent =
                            'Getting your location...';


                        try {

                            const position =
                                await getDeviceLocation();


                            latitude.textContent =
                                position.latitude.toFixed(7);


                            longitude.textContent =
                                position.longitude.toFixed(7);


                            status.textContent =
                                'Farm location detected. Review it and save.';


                            saveButton.disabled =
                                false;


                            saveButton.dataset.latitude =
                                position.latitude;


                            saveButton.dataset.longitude =
                                position.longitude;


                            saveButton.dataset.accuracy =
                                position.accuracy;


                        } catch (error) {

                            status.textContent =
                                error.message;

                        }

                    }
                );


            });

    }

);
document
    .querySelectorAll('.save-location-button')
    .forEach(function (button) {

        button.addEventListener(
            'click',
            async function () {

                const farmId =
                    this.dataset.farmId;

                const latitude =
                    this.dataset.latitude;

                const longitude =
                    this.dataset.longitude;

                const accuracy =
                    this.dataset.accuracy;


                if (!latitude || !longitude) {

                    return;

                }


                const formData =
                    new FormData();


                formData.append(
                    'csrf_token',
                    '<?= e(csrf_token()) ?>'
                );

                formData.append(
                    'farm_id',
                    farmId
                );

                formData.append(
                    'latitude',
                    latitude
                );

                formData.append(
                    'longitude',
                    longitude
                );

                formData.append(
                    'accuracy',
                    accuracy
                );


                this.disabled = true;

                this.textContent =
                    'Saving...';


                try {

                    const response =
                        await fetch(
                            '<?= e(FIH_BASE_URL) ?>/api/save-farm-location.php',
                            {
                                method: 'POST',
                                body: formData
                            }
                        );


                    const result =
                        await response.json();


                    if (!result.success) {

                        throw new Error(
                            result.message
                        );

                    }


                    const status =
                        document.getElementById(
                            'location-status-' +
                            farmId
                        );


                    status.textContent =
                        'Farm location saved successfully.';


                    this.textContent =
                        'Location Saved';


                } catch (error) {

                    this.disabled = false;

                    this.textContent =
                        'Save Farm Location';

                    alert(error.message);

                }

            }
        );

    });
</script>

</body>

</html>