
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

require_role(['Manager']);

$msg = '';
$error = '';

/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | CREATE QUOTATION
    |--------------------------------------------------------------------------
    */

    if ($action === 'create') {
        $inquiryId = (int) ($_POST['inquiry_id'] ?? 0);

        if ($inquiryId <= 0) {
            $error = 'Please select a valid inquiry.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'SELECT client_id, project_type, description, location
                     FROM inquiries
                     WHERE id = ?'
                );
                $stmt->execute([$inquiryId]);

                $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$inquiry) {
                    throw new RuntimeException('The selected inquiry was not found.');
                }

                $validUntil = trim($_POST['valid_until'] ?? '');
                $terms = trim($_POST['terms'] ?? '');

                if ($validUntil !== '') {
                    $date = DateTime::createFromFormat('!Y-m-d', $validUntil);

                    if (!$date || $date->format('Y-m-d') !== $validUntil) {
                        throw new RuntimeException('Please enter a valid expiry date.');
                    }
                }

                /*
                 * Create the quotation as a draft.
                 */
                $stmt = $pdo->prepare(
                    'INSERT INTO quotations
                        (inquiry_id, client_id, status, terms, valid_until, created_by)
                     VALUES (?, ?, "draft", ?, ?, ?)'
                );

                $stmt->execute([
                    $inquiryId,
                    $inquiry['client_id'],
                    $terms,
                    $validUntil !== '' ? $validUntil : null,
                    current_user()['id']
                ]);

                $quotationId = (int) $pdo->lastInsertId();

                /*
                 * Process quotation line items.
                 */
                $descriptions = $_POST['item_description'] ?? [];
                $types = $_POST['item_type'] ?? [];
                $quantities = $_POST['quantity'] ?? [];
                $prices = $_POST['unit_price'] ?? [];

                $allowedTypes = [
                    'material',
                    'labor',
                    'installation',
                    'other'
                ];

                $total = 0;

                foreach ($descriptions as $key => $description) {
                    $description = trim((string) $description);
                    $quantity = (float) ($quantities[$key] ?? 0);
                    $unitPrice = (float) ($prices[$key] ?? 0);
                    $itemType = $types[$key] ?? 'other';

                    if ($description === '') {
                        continue;
                    }

                    if (
                        !is_numeric($quantities[$key] ?? null) ||
                        !is_numeric($prices[$key] ?? null) ||
                        $quantity <= 0 ||
                        $unitPrice < 0
                    ) {
                        throw new RuntimeException(
                            'Each line item must have a valid quantity and non-negative unit price.'
                        );
                    }

                    if (!in_array($itemType, $allowedTypes, true)) {
                        $itemType = 'other';
                    }

                    $lineTotal = round($quantity * $unitPrice, 2);
                    $total += $lineTotal;

                    $stmt = $pdo->prepare(
                        'INSERT INTO quotation_items
                            (
                                quotation_id,
                                item_type,
                                description,
                                quantity,
                                unit_price,
                                line_total
                            )
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );

                    $stmt->execute([
                        $quotationId,
                        $itemType,
                        $description,
                        $quantity,
                        $unitPrice,
                        $lineTotal
                    ]);
                }

                /*
                 * Save the quotation total.
                 */
                $stmt = $pdo->prepare(
                    'UPDATE quotations
                     SET total = ?
                     WHERE id = ?'
                );

                $stmt->execute([
                    round($total, 2),
                    $quotationId
                ]);

                audit(
                    $pdo,
                    'Quotation created',
                    'quotation',
                    $quotationId
                );

                $pdo->commit();

                $msg = 'Draft quotation created successfully.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if ($e instanceof RuntimeException) {
                    $error = $e->getMessage();
                } else {
                    error_log('Quotation creation error: ' . $e->getMessage());
                    $error = 'Unable to create the quotation. Please try again.';
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SEND QUOTATION TO CLIENT
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'send') {
        $quotationId = (int) ($_POST['quotation_id'] ?? 0);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT *
                 FROM quotations
                 WHERE id = ?
                 FOR UPDATE'
            );
            $stmt->execute([$quotationId]);

            $quotation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$quotation) {
                throw new RuntimeException('Quotation not found.');
            }

            if ($quotation['status'] !== 'draft') {
                throw new RuntimeException(
                    'Only draft quotations can be sent to the client.'
                );
            }

            $stmt = $pdo->prepare(
                "UPDATE quotations
                 SET status = 'sent'
                 WHERE id = ?"
            );
            $stmt->execute([$quotationId]);

            $stmt = $pdo->prepare(
                "UPDATE inquiries
                 SET status = 'quoted'
                 WHERE id = ?"
            );
            $stmt->execute([$quotation['inquiry_id']]);

            audit(
                $pdo,
                'Quotation sent',
                'quotation',
                $quotationId
            );

            $pdo->commit();

            /*
             * Notify the client after the transaction commits.
             */
            try {
                notify_user(
                    $pdo,
                    (int) $quotation['client_id'],
                    'Quotation ready',
                    'Quotation #' . $quotationId . ' is ready for your review.'
                );
            } catch (Throwable $notificationError) {
                error_log(
                    'Quotation notification error: ' .
                    $notificationError->getMessage()
                );
            }

            $msg = 'Quotation sent to the client successfully.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                error_log('Send quotation error: ' . $e->getMessage());
                $error = 'Unable to send the quotation. Please try again.';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CONVERT ACCEPTED QUOTATION INTO A PROJECT
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'convert') {
        $quotationId = (int) ($_POST['quotation_id'] ?? 0);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT *
                 FROM quotations
                 WHERE id = ?
                 FOR UPDATE'
            );
            $stmt->execute([$quotationId]);

            $quotation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$quotation) {
                throw new RuntimeException('Quotation not found.');
            }

            if ($quotation['status'] !== 'accepted') {
                throw new RuntimeException(
                    'Only accepted quotations can be converted into projects.'
                );
            }

            /*
             * Prevent creating a duplicate project for the same quotation.
             */
            $stmt = $pdo->prepare(
                'SELECT id
                 FROM projects
                 WHERE quotation_id = ?
                 LIMIT 1'
            );
            $stmt->execute([$quotationId]);

            if ($stmt->fetch()) {
                throw new RuntimeException(
                    'A project has already been created from this quotation.'
                );
            }

            $stmt = $pdo->prepare(
                'SELECT project_type, description, location
                 FROM inquiries
                 WHERE id = ?'
            );
            $stmt->execute([$quotation['inquiry_id']]);

            $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$inquiry) {
                throw new RuntimeException(
                    'The inquiry linked to this quotation was not found.'
                );
            }

            $stmt = $pdo->prepare(
                'INSERT INTO projects
                    (
                        inquiry_id,
                        quotation_id,
                        client_id,
                        title,
                        description,
                        location
                    )
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $quotation['inquiry_id'],
                $quotationId,
                $quotation['client_id'],
                $inquiry['project_type'],
                $inquiry['description'],
                $inquiry['location']
            ]);

            $projectId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                "UPDATE inquiries
                 SET status = 'converted_to_project'
                 WHERE id = ?"
            );
            $stmt->execute([$quotation['inquiry_id']]);

            $stmt = $pdo->prepare(
                "UPDATE quotations
                 SET status = 'approved'
                 WHERE id = ?"
            );
            $stmt->execute([$quotationId]);

            audit(
                $pdo,
                'Accepted quotation converted to project',
                'project',
                $projectId
            );

            $pdo->commit();

            $msg = 'Project created successfully from the accepted quotation.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                error_log('Convert quotation error: ' . $e->getMessage());
                $error = 'Unable to convert the quotation. Please try again.';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| LOAD INQUIRIES AND QUOTATIONS
|--------------------------------------------------------------------------
*/

$inquiries = $pdo->query(
    'SELECT
        i.id,
        i.project_type,
        i.client_id,
        u.first_name,
        u.last_name
     FROM inquiries i
     JOIN users u ON u.id = i.client_id
     ORDER BY i.id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

$quotes = $pdo->query(
    'SELECT
        q.*,
        i.project_type,
        u.first_name,
        u.last_name
     FROM quotations q
     JOIN inquiries i ON i.id = q.inquiry_id
     JOIN users u ON u.id = q.client_id
     ORDER BY q.id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

page_header(
    'Quotations',
    'Prepare itemized quotations, send them, and convert accepted work'
);
?>

<div class="content-grid">

    <!-- CREATE QUOTATION -->
    <section class="panel span-12">
        <h2>Create a quotation</h2>

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

        <?php if (empty($inquiries)): ?>
            <div class="alert">
                No inquiries are available for quotation.
            </div>
        <?php else: ?>

            <form method="post">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <input type="hidden" name="action" value="create">

                <div class="form-grid">

                    <div>
                        <label for="inquiry_id">Inquiry *</label>

                        <select
                            id="inquiry_id"
                            name="inquiry_id"
                            required
                        >
                            <option value="">Select an inquiry</option>

                            <?php foreach ($inquiries as $inquiry): ?>
                                <option
                                    value="<?= (int) $inquiry['id'] ?>"
                                    <?= (
                                        (int) ($_GET['inquiry_id'] ?? 0)
                                        === (int) $inquiry['id']
                                    ) ? 'selected' : '' ?>
                                >
                                    #<?= (int) $inquiry['id'] ?>
                                    ·
                                    <?= e(
                                        $inquiry['project_type'] .
                                        ' — ' .
                                        $inquiry['first_name'] . ' ' .
                                        $inquiry['last_name']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="valid_until">Valid until</label>

                        <input
                            type="date"
                            id="valid_until"
                            name="valid_until"
                        >
                    </div>

                    <div class="wide">
                        <label for="terms">Terms and notes</label>

                        <textarea
                            id="terms"
                            name="terms"
                            rows="3"
                            placeholder="Scope, exclusions, payment terms..."
                        ></textarea>
                    </div>

                </div>

                <h3>Line items</h3>

                <?php for ($x = 0; $x < 4; $x++): ?>
                    <div class="form-grid">

                        <div>
                            <label>Item description</label>

                            <input
                                type="text"
                                name="item_description[]"
                                placeholder="Materials, labor, installation..."
                            >
                        </div>

                        <div class="two-col">

                            <div>
                                <label>Type</label>

                                <select name="item_type[]">
                                    <option value="material">Material</option>
                                    <option value="labor">Labor</option>
                                    <option value="installation">Installation</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label>Quantity</label>

                                <input
                                    type="number"
                                    name="quantity[]"
                                    min="0.01"
                                    step="0.01"
                                    value="1"
                                >
                            </div>

                        </div>

                        <div>
                            <label>Unit price (₱)</label>

                            <input
                                type="number"
                                name="unit_price[]"
                                min="0"
                                step="0.01"
                                value="0"
                            >
                        </div>

                    </div>
                <?php endfor; ?>

                <button type="submit" class="btn primary">
                    Save draft quotation
                </button>
            </form>

        <?php endif; ?>
    </section>

    <!-- QUOTATION REGISTER -->
    <section class="panel span-12">
        <h2>Quotation register</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Quotation</th>
                        <th>Client</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($quotes)): ?>
                        <tr>
                            <td colspan="5">No quotations found.</td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($quotes as $quote): ?>
                            <tr>
                                <td>
                                    #<?= (int) $quote['id'] ?>
                                    ·
                                    <?= e($quote['project_type']) ?>

                                    <br>

                                    <span class="small">
                                        Inquiry #<?= (int) $quote['inquiry_id'] ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e(
                                        $quote['first_name'] . ' ' .
                                        $quote['last_name']
                                    ) ?>
                                </td>

                                <td>
                                    ₱<?= number_format(
                                        (float) $quote['total'],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    <?= status_badge($quote['status']) ?>
                                </td>

                                <td>
                                    <div class="action-buttons">

                                        <?php if ($quote['status'] === 'draft'): ?>
                                            <form method="post">
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e(csrf_token()) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="send"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="quotation_id"
                                                    value="<?= (int) $quote['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn primary"
                                                    onclick="return confirm('Send this quotation to the client?');"
                                                >
                                                    Send to client
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($quote['status'] === 'accepted'): ?>
                                            <form method="post">
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e(csrf_token()) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="convert"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="quotation_id"
                                                    value="<?= (int) $quote['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn secondary"
                                                    onclick="return confirm('Convert this accepted quotation into a project?');"
                                                >
                                                    Convert to project
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (
                                            !in_array(
                                                $quote['status'],
                                                ['draft', 'accepted'],
                                                true
                                            )
                                        ): ?>
                                            <span class="small">No actions</span>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<?php page_footer(); ?>