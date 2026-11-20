<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

$u = require_role(['Client']);

$msg = '';
$error = '';

/*
|--------------------------------------------------------------------------
| HANDLE INQUIRY SUBMISSION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $type = trim($_POST['project_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $timeline = trim($_POST['timeline'] ?? '');
    $budget = trim($_POST['budget'] ?? '');

    /*
     * Validate required fields.
     */
    if ($type === '' || $description === '' || $location === '') {
        $error = 'Please complete all required fields.';
    } else {
        try {
            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | SAVE PROJECT INQUIRY
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                'INSERT INTO inquiries
                    (
                        client_id,
                        project_type,
                        description,
                        location,
                        preferred_timeline,
                        budget_range
                    )
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $u['id'],
                $type,
                $description,
                $location,
                $timeline !== '' ? $timeline : null,
                $budget !== '' ? $budget : null
            ]);

            $inquiryId = (int) $pdo->lastInsertId();

            /*
            |--------------------------------------------------------------------------
            | UPLOAD ATTACHMENTS
            |--------------------------------------------------------------------------
            */

            $uploadErrors = [];

            if (
                isset($_FILES['attachments']['name']) &&
                is_array($_FILES['attachments']['name'])
            ) {
                $uploadDirectory = __DIR__ . '/uploads';

                if (
                    !is_dir($uploadDirectory) &&
                    !mkdir($uploadDirectory, 0755, true) &&
                    !is_dir($uploadDirectory)
                ) {
                    throw new RuntimeException(
                        'The upload directory could not be created.'
                    );
                }

                $allowedMimeTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'application/pdf' => 'pdf'
                ];

                $fileCount = count($_FILES['attachments']['name']);

                if ($fileCount > 10) {
                    throw new RuntimeException(
                        'You can upload a maximum of 10 attachments per inquiry.'
                    );
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);

                for ($i = 0; $i < $fileCount; $i++) {
                    $originalName = $_FILES['attachments']['name'][$i] ?? '';
                    $tmpName = $_FILES['attachments']['tmp_name'][$i] ?? '';
                    $fileError = $_FILES['attachments']['error'][$i]
                        ?? UPLOAD_ERR_NO_FILE;
                    $fileSize = (int) (
                        $_FILES['attachments']['size'][$i] ?? 0
                    );

                    /*
                     * Ignore empty file inputs.
                     */
                    if (
                        $fileError === UPLOAD_ERR_NO_FILE ||
                        $originalName === ''
                    ) {
                        continue;
                    }

                    if ($fileError !== UPLOAD_ERR_OK) {
                        $uploadErrors[] = basename($originalName) .
                            ': Upload failed.';
                        continue;
                    }

                    /*
                     * Maximum size: 8 MB per file.
                     */
                    if (
                        $fileSize <= 0 ||
                        $fileSize > 8 * 1024 * 1024
                    ) {
                        $uploadErrors[] = basename($originalName) .
                            ': File must be no larger than 8 MB.';
                        continue;
                    }

                    if (
                        $tmpName === '' ||
                        !is_uploaded_file($tmpName)
                    ) {
                        $uploadErrors[] = basename($originalName) .
                            ': Invalid uploaded file.';
                        continue;
                    }

                    /*
                     * Check the actual MIME type instead of trusting
                     * the original filename or browser-provided type.
                     */
                    $mimeType = $finfo->file($tmpName);

                    if (!isset($allowedMimeTypes[$mimeType])) {
                        $uploadErrors[] = basename($originalName) .
                            ': Only PDF, JPG, PNG, and WebP files are allowed.';
                        continue;
                    }

                    /*
                     * Generate a safe, unique stored filename.
                     */
                    $extension = $allowedMimeTypes[$mimeType];
                    $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;

                    $destination = $uploadDirectory . '/' . $newFilename;
                    $relativePath = 'uploads/' . $newFilename;

                    if (!move_uploaded_file($tmpName, $destination)) {
                        $uploadErrors[] = basename($originalName) .
                            ': Unable to save the file.';
                        continue;
                    }

                    /*
                     * Save attachment metadata.
                     */
                    $stmt = $pdo->prepare(
                        'INSERT INTO attachments
                            (
                                inquiry_id,
                                uploaded_by,
                                file_path,
                                original_name,
                                mime_type
                            )
                         VALUES (?, ?, ?, ?, ?)'
                    );

                    $stmt->execute([
                        $inquiryId,
                        $u['id'],
                        $relativePath,
                        basename($originalName),
                        $mimeType
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | RECORD AUDIT LOG
            |--------------------------------------------------------------------------
            */

            audit(
                $pdo,
                'Inquiry submitted',
                'inquiry',
                $inquiryId,
                $type
            );

            /*
             * Commit the inquiry and attachment records before sending
             * notifications, so a notification failure does not undo
             * the saved inquiry.
             */
            $pdo->commit();

            /*
            |--------------------------------------------------------------------------
            | NOTIFY ACTIVE MANAGERS
            |--------------------------------------------------------------------------
            */

            try {
                $managers = $pdo->query(
                    "SELECT id
                     FROM users
                     WHERE role = 'Manager'
                       AND is_active = 1"
                )->fetchAll(PDO::FETCH_ASSOC);

                $clientName = trim(
                    ($u['first_name'] ?? '') . ' ' .
                    ($u['last_name'] ?? '')
                );

                foreach ($managers as $manager) {
                    notify_user(
                        $pdo,
                        (int) $manager['id'],
                        'New project inquiry',
                        $clientName . ' submitted ' . $type . '.'
                    );
                }
            } catch (Throwable $notificationError) {
                error_log(
                    'Inquiry notification error: ' .
                    $notificationError->getMessage()
                );
            }

            $msg = 'Inquiry submitted successfully. The Manager has been notified.';

            if (!empty($uploadErrors)) {
                $msg .= ' Some attachments were skipped: ' .
                    implode(' ', $uploadErrors);
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Inquiry submission error: ' . $e->getMessage());

            $error = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Unable to submit your inquiry. Please try again.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/

page_header(
    'New inquiry',
    'Tell us about your fabrication or installation project'
);

?>

<div class="content-grid">

    <!-- PROJECT INQUIRY FORM -->
    <section class="panel span-8">

        <h2>Project details</h2>

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

        <form method="post" enctype="multipart/form-data">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <div class="form-grid">

                <!-- PROJECT TYPE -->
                <div>
                    <label for="project_type">
                        Project type *
                    </label>

                    <select
                        id="project_type"
                        name="project_type"
                        required
                    >
                        <option value="">Choose a type</option>
                        <option value="Glass and aluminum">
                            Glass and aluminum
                        </option>
                        <option value="Windows and doors">
                            Windows and doors
                        </option>
                        <option value="Partitions">
                            Partitions
                        </option>
                        <option value="Railings">
                            Railings
                        </option>
                        <option value="Custom fabrication">
                            Custom fabrication
                        </option>
                        <option value="Other">
                            Other
                        </option>
                    </select>
                </div>

                <!-- PROJECT LOCATION -->
                <div>
                    <label for="location">
                        Project location *
                    </label>

                    <input
                        type="text"
                        id="location"
                        name="location"
                        required
                        maxlength="255"
                        placeholder="Site address / city"
                    >
                </div>

                <!-- PREFERRED TIMELINE -->
                <div>
                    <label for="timeline">
                        Preferred timeline
                    </label>

                    <input
                        type="text"
                        id="timeline"
                        name="timeline"
                        maxlength="255"
                        placeholder="e.g. Within 4 weeks"
                    >
                </div>

                <!-- BUDGET -->
                <div>
                    <label for="budget">
                        Budget range (optional)
                    </label>

                    <input
                        type="text"
                        id="budget"
                        name="budget"
                        maxlength="255"
                        placeholder="e.g. ₱30,000–₱50,000"
                    >
                </div>

                <!-- DESCRIPTION -->
                <div class="wide">
                    <label for="description">
                        Description and measurements *
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        required
                        placeholder="Describe the work, dimensions, materials, and site requirements..."
                    ></textarea>
                </div>

                <!-- ATTACHMENTS -->
                <div class="wide">
                    <label for="attachments">
                        Photos, plans, or measurements
                    </label>

                    <p class="small">
                        Accepted formats: PDF, JPG, PNG, WebP.
                        Maximum 8 MB per file and 10 files per inquiry.
                    </p>

                    <input
                        type="file"
                        id="attachments"
                        name="attachments[]"
                        multiple
                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                    >
                </div>

            </div>

            <!-- FORM ACTIONS -->
            <div class="form-actions">

                <button type="submit" class="btn primary">
                    Submit inquiry
                </button>

                <a
                    class="btn secondary"
                    href="client_dashboard.php"
                >
                    Cancel
                </a>

            </div>

        </form>

    </section>

    <!-- NEXT STEPS -->
    <section class="panel span-4">

        <h2>What happens next?</h2>

        <p class="muted">
            The Manager reviews your request, may ask for more information,
            and prepares a quotation when the project scope is clear.
        </p>

        <div class="notice">
            <strong>1. Submit details and photos</strong>
            <p class="small">
                Provide the project requirements and measurements.
            </p>
        </div>

        <div class="notice">
            <strong>2. Manager reviews requirements</strong>
            <p class="small">
                The Manager checks the details and determines the next steps.
            </p>
        </div>

        <div class="notice">
            <strong>3. Review your quotation</strong>
            <p class="small">
                Review the proposed scope, costs, and terms.
            </p>
        </div>

        <div class="notice">
            <strong>4. Confirm schedule and scope</strong>
            <p class="small">
                Proceed with the agreed project arrangements.
            </p>
        </div>

    </section>

</div>

<?php page_footer(); ?>
