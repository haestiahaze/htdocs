
<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

require_role(['Manager']);

$msg = '';

/*
|--------------------------------------------------------------------------
| Handle Inquiry Updates
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'under_review';
    $response = trim($_POST['response'] ?? '');

    $allowedStatuses = [
        'new',
        'under_review',
        'quoted',
        'accepted',
        'declined',
        'converted_to_project'
    ];

    if ($id > 0 && in_array($status, $allowedStatuses, true)) {

        // Verify that the inquiry exists.
        $check = $pdo->prepare(
            'SELECT client_id
             FROM inquiries
             WHERE id = ?'
        );

        $check->execute([$id]);
        $clientId = $check->fetchColumn();

        if ($clientId !== false) {

            // Update inquiry status and Manager response.
            $stmt = $pdo->prepare(
                'UPDATE inquiries
                 SET status = ?,
                     manager_response = ?
                 WHERE id = ?'
            );

            $stmt->execute([
                $status,
                $response,
                $id
            ]);

            // Notify the client.
            notify_user(
                $pdo,
                (int) $clientId,
                'Inquiry updated',
                'Your inquiry #' . $id .
                ' is now ' . $status . '. ' . $response
            );

            // Record the action in the audit log.
            audit(
                $pdo,
                'Inquiry status updated',
                'inquiry',
                $id,
                $status
            );

            $msg = 'Inquiry updated successfully.';

        } else {

            $msg = 'Inquiry not found.';
        }

    } else {

        $msg = 'Invalid inquiry ID or status.';
    }
}

/*
|--------------------------------------------------------------------------
| Fetch All Inquiries
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    'SELECT
        i.*,
        u.first_name,
        u.last_name,
        u.email
     FROM inquiries i
     JOIN users u ON u.id = i.client_id
     ORDER BY i.created_at DESC'
);

$rows = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Page Header
|--------------------------------------------------------------------------
*/

page_header(
    'Manage inquiries',
    'Review clients, update statuses, and send responses'
);

?>

<div class="content-grid">

    <section class="panel span-12">

        <!-- Feedback Message -->

        <?php if ($msg !== ''): ?>

            <div class="alert success">
                <?= e($msg) ?>
            </div>

        <?php endif; ?>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Client / Inquiry</th>
                        <th>Details</th>
                        <th>Status and Response</th>
                        <th>Attachments</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($rows as $r): ?>

                        <tr>

                            <!-- Client and Inquiry Information -->

                            <td>

                                <strong>
                                    <?= e(
                                        $r['first_name'] . ' ' .
                                        $r['last_name']
                                    ) ?>
                                </strong>

                                <br>

                                <?= e($r['email']) ?>

                                <br>

                                <span class="small">
                                    #<?= (int) $r['id'] ?>
                                    · <?= e($r['project_type']) ?>
                                </span>

                            </td>

                            <!-- Inquiry Details -->

                            <td>

                                <?= e($r['description']) ?>

                                <br>

                                <span class="small">

                                    <?= e($r['location']) ?>

                                    · <?= e(
                                        $r['preferred_timeline'] ?? ''
                                    ) ?>

                                    · <?= e(
                                        $r['budget_range'] ?? ''
                                    ) ?>

                                </span>

                            </td>

                            <!-- Status and Manager Response -->

                            <td>

                                <form method="post">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $r['id'] ?>"
                                    >

                                    <label>
                                        Inquiry status
                                    </label>

                                    <select name="status" required>

                                        <?php
                                        $allowedStatuses = [
                                            'new',
                                            'under_review',
                                            'quoted',
                                            'accepted',
                                            'declined',
                                            'converted_to_project'
                                        ];
                                        ?>

                                        <?php foreach (
                                            $allowedStatuses as $st
                                        ): ?>

                                            <option
                                                value="<?= e($st) ?>"
                                                <?= $r['status'] === $st
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                <?= e(
                                                    ucwords(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $st
                                                        )
                                                    )
                                                ) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                    <label>
                                        Response to client
                                    </label>

                                    <textarea
                                        name="response"
                                        placeholder="Message to client..."
                                        rows="3"
                                    ><?= e(
                                        $r['manager_response'] ?? ''
                                    ) ?></textarea>

                                    <button
                                        type="submit"
                                        class="btn primary"
                                    >
                                        Save update
                                    </button>

                                </form>

                                <!-- Create Quotation -->

                                <a
                                    class="btn secondary"
                                    href="quotations.php?inquiry_id=<?= (int) $r['id'] ?>"
                                >
                                    Create quotation
                                </a>

                            </td>

                            <!-- Attachments -->

                            <td>

                                <?php

                                $attachmentStmt = $pdo->prepare(
                                    'SELECT *
                                     FROM attachments
                                     WHERE inquiry_id = ?'
                                );

                                $attachmentStmt->execute([
                                    $r['id']
                                ]);

                                $attachments = $attachmentStmt->fetchAll();

                                ?>

                                <?php if (empty($attachments)): ?>

                                    <span class="small">
                                        No attachments.
                                    </span>

                                <?php else: ?>

                                    <?php foreach (
                                        $attachments as $file
                                    ): ?>

                                        <a
                                            href="<?= e($file['file_path']) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <?= e(
                                                $file['original_name']
                                            ) ?>
                                        </a>

                                        <br>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

            <?php if (empty($rows)): ?>

                <div class="empty">
                    No inquiries have been submitted yet.
                </div>

            <?php endif; ?>

        </div>

    </section>

</div>

<?php

page_footer();

?>
