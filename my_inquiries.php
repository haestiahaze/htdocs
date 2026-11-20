
<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

$u = require_role(['Client']);

/*
|--------------------------------------------------------------------------
| Handle Client Quotation Response
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $id = (int) ($_POST['quotation_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $map = [
        'accept'   => 'accepted',
        'decline'  => 'declined',
        'revision' => 'revision_requested'
    ];

    if ($id > 0 && isset($map[$action])) {

        $newStatus = $map[$action];

        $stmt = $pdo->prepare(
            "UPDATE quotations
             SET status = ?
             WHERE id = ?
               AND client_id = ?
               AND status = 'sent'"
        );

        $stmt->execute([
            $newStatus,
            $id,
            $u['id']
        ]);

        if ($stmt->rowCount() > 0) {

            audit(
                $pdo,
                'Client quotation response',
                'quotation',
                $id,
                $newStatus
            );

            $managerStmt = $pdo->prepare(
                'SELECT created_by
                 FROM quotations
                 WHERE id = ?'
            );

            $managerStmt->execute([$id]);

            $managerId = $managerStmt->fetchColumn();

            if ($managerId) {

                notify_user(
                    $pdo,
                    (int) $managerId,
                    'Quotation response received',
                    'A client responded to quotation #' . $id . '.'
                );
            }
        }
    }

    // Redirect to prevent duplicate form submissions.
    header('Location: my_inquiries.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Fetch Client Inquiries
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT *
     FROM inquiries
     WHERE client_id = ?
     ORDER BY created_at DESC'
);

$stmt->execute([$u['id']]);

$rows = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Page Header
|--------------------------------------------------------------------------
*/

page_header(
    'My inquiries',
    'Review request status, Manager responses, and quotations'
);

?>

<div class="content-grid">

    <section class="panel span-12">

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Inquiry</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Manager Response</th>
                        <th>Quotation</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($rows as $r): ?>

                        <tr>

                            <!-- Inquiry Information -->
                            <td>
                                <strong>
                                    #<?= (int) $r['id'] ?>
                                    · <?= e($r['project_type']) ?>
                                </strong>

                                <br>

                                <span class="small">
                                    <?= e($r['location']) ?>
                                </span>
                            </td>

                            <!-- Inquiry Description -->
                            <td>
                                <?= e(
                                    mb_strimwidth(
                                        (string) $r['description'],
                                        0,
                                        110,
                                        '…'
                                    )
                                ) ?>
                            </td>

                            <!-- Inquiry Status -->
                            <td>
                                <?= status_badge($r['status']) ?>
                            </td>

                            <!-- Manager Response -->
                            <td>
                                <?= e(
                                    $r['manager_response']
                                    ?: 'No response yet'
                                ) ?>
                            </td>

                            <!-- Quotations -->
                            <td>

                                <?php

                                $quoteStmt = $pdo->prepare(
                                    'SELECT *
                                     FROM quotations
                                     WHERE inquiry_id = ?
                                     ORDER BY id DESC'
                                );

                                $quoteStmt->execute([$r['id']]);

                                $quotes = $quoteStmt->fetchAll();

                                ?>

                                <?php if (empty($quotes)): ?>

                                    <span class="small">
                                        No quotation yet.
                                    </span>

                                <?php else: ?>

                                    <?php foreach ($quotes as $quote): ?>

                                        <strong>
                                            #<?= (int) $quote['id'] ?>
                                            · ₱<?= number_format(
                                                (float) $quote['total'],
                                                2
                                            ) ?>
                                        </strong>

                                        <br>

                                        <?= status_badge($quote['status']) ?>

                                        <!-- Client Response Buttons -->
                                        <?php if ($quote['status'] === 'sent'): ?>

                                            <form
                                                method="post"
                                                class="inline-form"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e(csrf_token()) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="quotation_id"
                                                    value="<?= (int) $quote['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="action"
                                                    value="accept"
                                                    class="btn primary"
                                                    onclick="return confirm('Are you sure you want to accept this quotation?');"
                                                >
                                                    Accept
                                                </button>

                                                <button
                                                    type="submit"
                                                    name="action"
                                                    value="revision"
                                                    class="btn secondary"
                                                    onclick="return confirm('Request a revision for this quotation?');"
                                                >
                                                    Revise
                                                </button>

                                                <button
                                                    type="submit"
                                                    name="action"
                                                    value="decline"
                                                    class="btn danger"
                                                    onclick="return confirm('Are you sure you want to decline this quotation?');"
                                                >
                                                    Decline
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                        <!-- Quotation Terms -->
                                        <br>

                                        <span class="small">
                                            <?= e($quote['terms'] ?? '') ?>
                                        </span>

                                        <hr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

            <!-- Empty State -->
            <?php if (empty($rows)): ?>

                <div class="empty">
                    No inquiries yet.
                </div>

            <?php endif; ?>

        </div>

    </section>

</div>

<?php

page_footer();

?>
