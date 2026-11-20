
<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

/*
|--------------------------------------------------------------------------
| PERSONNEL ACCESS ONLY
|--------------------------------------------------------------------------
*/
$u = require_role(['Personnel']);

$msg = '';
$error = '';

/*
|--------------------------------------------------------------------------
| HANDLE ISSUE REPORT SUBMISSION
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $type = $_POST['report_type'] ?? 'other';
    $reason = trim($_POST['reason'] ?? '');

    $allowedTypes = [
        'delay',
        'damage',
        'shortage',
        'other'
    ];

    if (!in_array($type, $allowedTypes, true)) {
        $error = 'Please select a valid report type.';
    } elseif ($reason === '') {
        $error = 'Please provide the reason and details.';
    } else {

        /*
        |--------------------------------------------------------------------------
        | VALIDATE OPTIONAL QUANTITY
        |--------------------------------------------------------------------------
        */
        $quantityInput = trim($_POST['quantity'] ?? '');
        $quantity = null;

        if ($quantityInput !== '') {
            if (!is_numeric($quantityInput) || (float) $quantityInput < 0) {
                $error = 'Quantity must be a valid non-negative number.';
            } else {
                $quantity = (float) $quantityInput;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATE OPTIONAL PROJECT
        |--------------------------------------------------------------------------
        */
        $projectInput = $_POST['project_id'] ?? '';
        $projectId = null;

        if ($projectInput !== '') {
            $projectId = filter_var(
                $projectInput,
                FILTER_VALIDATE_INT
            );

            if (!$projectId || $projectId < 1) {
                $error = 'Please select a valid project.';
            } else {
                $stmt = $pdo->prepare(
                    "SELECT id
                     FROM projects
                     WHERE id = ?
                       AND status NOT IN ('completed', 'cancelled')
                     LIMIT 1"
                );

                $stmt->execute([$projectId]);

                if (!$stmt->fetchColumn()) {
                    $error = 'The selected project is unavailable.';
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATE AND UPLOAD OPTIONAL PHOTO
        |--------------------------------------------------------------------------
        */
        $photo = null;

        if ($error === '' && isset($_FILES['photo'])) {

            $file = $_FILES['photo'];

            if ($file['error'] !== UPLOAD_ERR_NO_FILE) {

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = 'The photo upload failed. Please try again.';
                } elseif ($file['size'] > 8 * 1024 * 1024) {
                    $error = 'The photo must not exceed 8 MB.';
                } elseif (!is_uploaded_file($file['tmp_name'])) {
                    $error = 'Invalid photo upload.';
                } else {

                    $mime = (new finfo(FILEINFO_MIME_TYPE))
                        ->file($file['tmp_name']);

                    $allowedMimes = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp'
                    ];

                    if (!isset($allowedMimes[$mime])) {
                        $error = 'Only JPG, PNG, and WebP images are allowed.';
                    } else {

                        $uploadDir = __DIR__ . '/uploads';

                        if (
                            !is_dir($uploadDir) &&
                            !mkdir($uploadDir, 0755, true) &&
                            !is_dir($uploadDir)
                        ) {
                            $error = 'Unable to create the upload directory.';
                        } else {

                            $fileName = bin2hex(random_bytes(16))
                                . '.'
                                . $allowedMimes[$mime];

                            $destination = $uploadDir . '/' . $fileName;

                            if (
                                move_uploaded_file(
                                    $file['tmp_name'],
                                    $destination
                                )
                            ) {
                                $photo = 'uploads/' . $fileName;
                            } else {
                                $error = 'Unable to save the uploaded photo.';
                            }
                        }
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE ISSUE REPORT
        |--------------------------------------------------------------------------
        */
        if ($error === '') {

            try {

                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'INSERT INTO issue_reports (
                        project_id,
                        reported_by,
                        report_type,
                        reason,
                        quantity,
                        photo_path
                    ) VALUES (?, ?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $projectId,
                    $u['id'],
                    $type,
                    $reason,
                    $quantity,
                    $photo
                ]);

                $reportId = (int) $pdo->lastInsertId();

                /*
                |--------------------------------------------------------------------------
                | AUDIT LOG
                |--------------------------------------------------------------------------
                */
                audit(
                    $pdo,
                    'Issue reported',
                    'issue_report',
                    $reportId,
                    $type
                );

                $pdo->commit();

                /*
                |--------------------------------------------------------------------------
                | NOTIFY ACTIVE MANAGERS
                |--------------------------------------------------------------------------
                */
                $stmt = $pdo->query(
                    "SELECT id
                     FROM users
                     WHERE role = 'Manager'
                       AND is_active = 1"
                );

                $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($managers as $manager) {

                    notify_user(
                        $pdo,
                        (int) $manager['id'],
                        'Personnel issue report',
                        $type . ' reported: '
                            . mb_strimwidth(
                                $reason,
                                0,
                                140,
                                '…'
                            )
                    );
                }

                $msg = 'Report sent to the Manager.';

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                /*
                 * Remove the uploaded file if the report was not saved.
                 */
                if ($photo !== null) {
                    $uploadedPath = __DIR__ . '/' . $photo;

                    if (is_file($uploadedPath)) {
                        unlink($uploadedPath);
                    }
                }

                error_log(
                    'Issue report submission failed: ' . $e->getMessage()
                );

                $error = 'Unable to submit the report. Please try again.';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| LOAD ACTIVE PROJECTS
|--------------------------------------------------------------------------
*/
$projects = $pdo->query(
    "SELECT id, title
     FROM projects
     WHERE status NOT IN ('completed', 'cancelled')
     ORDER BY title"
)->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/
page_header(
    'Report an Issue',
    'Report delays, damaged materials, or inventory shortages.'
);

?>

<div class="content-grid">

    <section class="panel span-8">

        <div class="panel-head">
            <h2>Issue Report Form</h2>
        </div>

        <?php if ($msg !== ''): ?>

            <div class="alert success">
                <?= e($msg) ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="alert error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <form
            method="post"
            enctype="multipart/form-data"
        >

            <!-- CSRF TOKEN -->
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <!-- REPORT TYPE -->
            <label for="report_type">
                Report Type
            </label>

            <select
                name="report_type"
                id="report_type"
                required
            >
                <option value="delay">Delay</option>
                <option value="damage">Damaged Material</option>
                <option value="shortage">Inventory Shortage</option>
                <option value="other">Other</option>
            </select>

            <!-- RELATED PROJECT -->
            <label for="project_id">
                Related Project
            </label>

            <select
                name="project_id"
                id="project_id"
            >
                <option value="">
                    Select Project (Optional)
                </option>

                <?php foreach ($projects as $project): ?>

                    <option value="<?= (int) $project['id'] ?>">
                        <?= e($project['title']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <!-- ISSUE DETAILS -->
            <label for="reason">
                Reason and Details *
            </label>

            <textarea
                name="reason"
                id="reason"
                required
                maxlength="10000"
                placeholder="Describe the issue, its impact, and the next steps..."
            ></textarea>

            <!-- QUANTITY -->
            <label for="quantity">
                Quantity Affected (Optional)
            </label>

            <input
                type="number"
                name="quantity"
                id="quantity"
                min="0"
                step="0.01"
                placeholder="Enter affected quantity"
            >

            <!-- PHOTO UPLOAD -->
            <label for="photo">
                Attach Photo (Optional)
            </label>

            <input
                type="file"
                name="photo"
                id="photo"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            >

            <p class="small">
                Accepted formats: JPG, PNG, or WebP.
                Maximum file size: 8 MB.
            </p>

            <!-- SUBMIT -->
            <div class="form-actions">

                <button
                    type="submit"
                    class="btn primary"
                >
                    Send Report
                </button>

            </div>

        </form>

    </section>

</div>

<?php
page_footer();
?>