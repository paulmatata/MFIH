<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/crop_health_api.php';

require_farmer();

$user_id = authenticated_farmer_id();

if (!$user_id) {
    redirect('/login.php');
}

$errors = [];

$crop_records = [];

$stmt = $pdo->prepare("
    SELECT
        cr.id,
        cr.farm_id,
        c.name AS crop_name,
        fp.farm_name

    FROM crop_records cr

    INNER JOIN crops c
        ON c.id = cr.crop_id

    INNER JOIN farmer_profiles fp
        ON fp.id = cr.farm_id

    WHERE fp.user_id = ?

    ORDER BY fp.farm_name, c.name
");

$stmt->execute([
    $user_id
]);

$crop_records = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Submit observation
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $crop_record_id =
        trim($_POST['crop_record_id'] ?? '');

    $problem_type =
        trim($_POST['problem_type'] ?? '');

    $description =
        trim($_POST['description'] ?? '');

    $impact_percentage =
        trim($_POST['impact_percentage'] ?? '');

    $errors = [];


    if ($crop_record_id === '') {
        $errors[] = 'Please select the affected crop.';
    }


    if (
        $impact_percentage !== ''
        &&
        (
            !is_numeric($impact_percentage)
            ||
            (float)$impact_percentage < 0
            ||
            (float)$impact_percentage > 100
        )
    ) {
        $errors[] =
            'Impact must be between 0 and 100 percent.';
    }


    /*
    |--------------------------------------------------------------------------
    | Verify crop belongs to farmer
    |--------------------------------------------------------------------------
    */

    $crop_stmt = $pdo->prepare("
        SELECT
            cr.id,
            cr.farm_id

        FROM crop_records cr

        INNER JOIN farmer_profiles fp
            ON fp.id = cr.farm_id

        WHERE cr.id = ?
          AND fp.user_id = ?

        LIMIT 1
    ");

    $crop_stmt->execute([
        $crop_record_id,
        $user_id
    ]);

    $crop = $crop_stmt->fetch(PDO::FETCH_ASSOC);


    if (!$crop) {
        $errors[] =
            'Invalid crop selected.';
    }


    /*
    |--------------------------------------------------------------------------
    | Image validation
    |--------------------------------------------------------------------------
    */

    $image_path = null;

    if (
        isset($_FILES['image'])
        &&
        $_FILES['image']['error']
        !== UPLOAD_ERR_NO_FILE
    ) {

        if (
            $_FILES['image']['error']
            !== UPLOAD_ERR_OK
        ) {

            $errors[] =
                'There was a problem uploading the image.';

        } else {

            $allowed_types = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            $file_type =
                mime_content_type(
                    $_FILES['image']['tmp_name']
                );

            if (
                !in_array(
                    $file_type,
                    $allowed_types,
                    true
                )
            ) {

                $errors[] =
                    'Please upload a JPG, PNG or WebP image.';

            }


            if (
                $_FILES['image']['size']
                > 5 * 1024 * 1024
            ) {

                $errors[] =
                    'Image must not exceed 5 MB.';
            }
        }

    } else {

        $errors[] =
            'Please take or upload a photograph.';
    }


    /*
    |--------------------------------------------------------------------------
    | Save observation
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $upload_directory =
            __DIR__
            . '/../uploads/crop-health/';


        if (
            !is_dir($upload_directory)
        ) {

            mkdir(
                $upload_directory,
                0755,
                true
            );
        }


        $extension =
            strtolower(
                pathinfo(
                    $_FILES['image']['name'],
                    PATHINFO_EXTENSION
                )
            );


        $observation_id =
            generate_uuid();


        $filename =
            $observation_id
            . '.'
            . $extension;


        $destination =
            $upload_directory
            . $filename;


        if (
            !move_uploaded_file(
                $_FILES['image']['tmp_name'],
                $destination
            )
        ) {

            $errors[] =
                'The image could not be saved.';

        } else {

            $image_path =
                'uploads/crop-health/'
                . $filename;


            $insert = $pdo->prepare("
                INSERT INTO crop_health_observations (
                    id,
                    crop_record_id,
                    farm_id,
                    observed_at,
                    problem_type,
                    description,
                    impact_percentage,
                    image_path,
                    farmer_confirmed,
                    status,
                    created_at,
                    updated_at
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    NOW(),
                    ?,
                    ?,
                    ?,
                    ?,
                    1,
                    'pending',
                    NOW(),
                    NOW()
                )
            ");

$insert->execute([
    $observation_id,
    $crop_record_id,
    $crop['farm_id'],
    $problem_type !== ''
        ? $problem_type
        : null,
    $description !== ''
        ? $description
        : null,
    $impact_percentage !== ''
        ? $impact_percentage
        : null,
    $image_path
]);


/*
|--------------------------------------------------------------------------
| Run external crop-health diagnosis
|--------------------------------------------------------------------------
*/

$diagnosis_result =
    diagnose_crop_image(
        $destination,
        null,
        $description !== ''
            ? $description
            : null
    );
/*
|--------------------------------------------------------------------------
| Save diagnosis response
|--------------------------------------------------------------------------
*/

if ($diagnosis_result['success']) {

    $api_data =
        $diagnosis_result['data'];


    /*
    |--------------------------------------------------------------------------
    | Request ID
    |--------------------------------------------------------------------------
    */

    $external_reference =
        $api_data['request_id']
        ?? null;


    /*
    |--------------------------------------------------------------------------
    | Get primary diagnosis
    |--------------------------------------------------------------------------
    */

    $diagnosis_name = null;

    $confidence = null;


    if (
        isset($api_data['diagnoses'])
        &&
        is_array($api_data['diagnoses'])
        &&
        isset($api_data['diagnoses'][0])
    ) {

        $primary =
            $api_data['diagnoses'][0];


        $diagnosis_name =
            $primary['name']
            ?? null;


        $confidence =
            isset(
                $primary['confidence']
            )
            ? (
                (float)
                $primary['confidence']
                * 100
            )
            : null;
    }


    /*
    |--------------------------------------------------------------------------
    | Store complete API response
    |--------------------------------------------------------------------------
    */

    $diagnosis_data =
        json_encode(
            $api_data,
            JSON_UNESCAPED_UNICODE
            |
            JSON_UNESCAPED_SLASHES
        );


    /*
    |--------------------------------------------------------------------------
    | Create diagnosis ID
    |--------------------------------------------------------------------------
    */

    $diagnosis_id =
        generate_uuid();


    /*
    |--------------------------------------------------------------------------
    | Insert
    |--------------------------------------------------------------------------
    */

    $diagnosis_stmt =
        $pdo->prepare("
            INSERT INTO crop_health_diagnoses (

                id,
                observation_id,
                provider,
                external_reference,
                diagnosis_name,
                confidence,
                diagnosis_status,
                diagnosis_data,
                diagnosed_at,
                created_at,
                updated_at

            )
            VALUES (

                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NOW(),
                NOW(),
                NOW()

            )
        ");


    $diagnosis_stmt->execute([

        $diagnosis_id,

        $observation_id,

        'kindwise',

        $external_reference,

        $diagnosis_name,

        $confidence,

        'completed',

        $diagnosis_data

    ]);

}

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

redirect(
    '/farmer/crop-health.php?success=1'
);
        }
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

    <title>
        Crop Health | FIH
    </title>

    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(FIH_BASE_URL) ?>/assets/css/farmer.css"
    >

    <style>

        .health-page {
            max-width: 800px;
            margin: auto;
            padding: 20px 14px 70px;
        }

        .health-intro {
            margin-bottom: 22px;
        }

        .health-intro p {
            line-height: 1.6;
            opacity: .7;
        }

        .health-form {
            padding: 20px;
            border-radius: 18px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            font-size: .85rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,.15);
            font: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .camera-box {
            border: 2px dashed rgba(0,0,0,.2);
            border-radius: 16px;
            padding: 25px 15px;
            text-align: center;
            margin-bottom: 20px;
        }

        .camera-box strong {
            display: block;
            margin-bottom: 5px;
        }

        .camera-box small {
            opacity: .6;
        }

        .camera-input {
            margin-top: 15px;
            width: 100%;
        }

        .error-box {
            padding: 13px;
            border-radius: 12px;
            margin-bottom: 18px;
            background: rgba(180,0,0,.08);
        }

        .error-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .success-box {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 18px;
            background: rgba(0,130,70,.1);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            border: 0;
            border-radius: 11px;
            font-weight: 700;
            cursor: pointer;
        }

        @media (min-width: 700px) {

            .health-page {
                padding: 35px 25px 70px;
            }

        }

    </style>

</head>

<body>

<?php
require_once __DIR__ . '/../includes/farmer_sidebar.php';
?>

<div class="farmer-dashboard-layout">

    <main class="health-page">

        <section class="health-intro">

            <h1>
                🌱 Crop Health
            </h1>

            <p>
                Something unusual on your crop?
                Take a photograph and report what
                you are seeing. FIH will use the
                observation for crop-health analysis
                and future regional warnings.
            </p>

        </section>


        <?php if (
            isset($_GET['success'])
        ): ?>

            <div class="success-box">

                ✓ Your observation has been
                successfully recorded.

            </div>

        <?php endif; ?>


        <?php if ($errors): ?>

            <div class="error-box">

                <ul>

                    <?php foreach (
                        $errors
                        as $error
                    ): ?>

                        <li>
                            <?= e($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <section class="profile-card health-form">

            <form
                method="POST"
                enctype="multipart/form-data"
            >

                <?= csrf_field() ?>


                <!-- CROP -->

                <div class="form-group">

                    <label for="crop_record_id">
                        Affected crop
                    </label>

                    <select
                        id="crop_record_id"
                        name="crop_record_id"
                        required
                    >

                        <option value="">
                            Select crop
                        </option>

                        <?php foreach (
                            $crop_records
                            as $crop
                        ): ?>

                            <option
                                value="<?= e(
                                    $crop['id']
                                ) ?>"
                            >

                                <?= e(
                                    $crop['crop_name']
                                ) ?>

                                -

                                <?= e(
                                    $crop['farm_name']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- IMAGE -->

                <div class="camera-box">

                    <strong>
                        📷 Photograph the problem
                    </strong>

                    <small>
                        Take a clear picture of the
                        affected part of the crop.
                    </small>

                    <input
                        class="camera-input"
                        type="file"
                        name="image"
                        accept="image/jpeg,image/png,image/webp"
                        capture="environment"
                        required
                    >

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group">

                    <label for="description">
                        What did you observe?
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="For example: leaves have holes, leaves are turning yellow, insects are present..."
                    ></textarea>

                </div>


                <!-- PROBLEM TYPE -->

                <div class="form-group">

                    <label for="problem_type">
                        What do you think it might be?
                    </label>

                    <select
                        id="problem_type"
                        name="problem_type"
                    >

                        <option value="">
                            I don't know
                        </option>

                        <option value="pest">
                            Pest
                        </option>

                        <option value="disease">
                            Disease
                        </option>

                        <option value="nutrient_deficiency">
                            Nutrient deficiency
                        </option>

                        <option value="water_stress">
                            Water stress
                        </option>

                        <option value="other">
                            Other
                        </option>

                    </select>

                </div>


                <!-- IMPACT -->

                <div class="form-group">

                    <label for="impact_percentage">
                        Estimated affected area (%)
                    </label>

                    <input
                        type="number"
                        id="impact_percentage"
                        name="impact_percentage"
                        min="0"
                        max="100"
                        step="0.1"
                        placeholder="Example: 10"
                    >

                </div>


                <button
                    type="submit"
                    class="submit-btn"
                >
                    Submit Crop Observation
                </button>

            </form>

        </section>

    </main>

</div>

</body>

</html>