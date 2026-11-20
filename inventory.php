<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

$u = require_role(['Manager', 'Personnel']);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (
        $u['role'] !== 'Manager' &&
        empty($u['can_record_materials'])
    ) {
        http_response_code(403);
        exit('Material recording is not enabled for your account.');
    }

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | ADD INVENTORY ITEM (MANAGER ONLY)
    |--------------------------------------------------------------------------
    */

    if ($action === 'add' && $u['role'] === 'Manager') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $unit = trim($_POST['unit'] ?? 'pcs');
        $quantity = max(0, (float) ($_POST['quantity'] ?? 0));
        $reorder_point = max(
            0,
            (float) ($_POST['reorder_point'] ?? 0)
        );
        $unit_cost = max(0, (float) ($_POST['unit_cost'] ?? 0));
        $supplier = trim($_POST['supplier'] ?? '');

        if ($name === '' || $unit === '') {
            $msg = 'Item name and unit are required.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO inventory_items
                    (
                        name,
                        category,
                        unit,
                        quantity,
                        reorder_point,
                        unit_cost,
                        supplier
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $name,
                    $category,
                    $unit,
                    $quantity,
                    $reorder_point,
                    $unit_cost,
                    $supplier
                ]);

                $id = (int) $pdo->lastInsertId();

                audit(
                    $pdo,
                    'Inventory item created',
                    'inventory_item',
                    $id
                );

                $msg = 'Inventory item created successfully.';
            } catch (Throwable $e) {
                error_log('Inventory item creation: ' . $e->getMessage());
                $msg = 'Unable to create inventory item.';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RECORD STOCK MOVEMENT
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'move') {
        $item = (int) ($_POST['item_id'] ?? 0);
        $qty = (float) ($_POST['quantity'] ?? 0);
        $type = $_POST['transaction_type'] ?? '';

        $allowed = [
            'received',
            'issued',
            'consumed',
            'damaged',
            'returned',
            'adjustment'
        ];

        if ($item <= 0 || $qty <= 0 || !in_array($type, $allowed, true)) {
            $msg = 'Please provide valid stock movement details.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'SELECT quantity
                     FROM inventory_items
                     WHERE id = ?
                     FOR UPDATE'
                );

                $stmt->execute([$item]);
                $old = $stmt->fetchColumn();

                if ($old === false) {
                    throw new Exception('Inventory item not found.');
                }

                $old = (float) $old;

                if (in_array($type, ['received', 'returned'], true)) {
                    $delta = $qty;
                } elseif (
                    in_array($type, ['issued', 'consumed', 'damaged'], true)
                ) {
                    $delta = -$qty;
                } else {
                    // Adjustment sets stock to the submitted quantity.
                    $delta = $qty - $old;
                }

                $new = $old + $delta;

                if ($new < 0) {
                    throw new Exception(
                        'Insufficient stock for this movement.'
                    );
                }

                $stmt = $pdo->prepare(
                    'UPDATE inventory_items
                     SET quantity = ?
                     WHERE id = ?'
                );

                $stmt->execute([$new, $item]);

                $project = (int) ($_POST['project_id'] ?? 0);
                $project = $project > 0 ? $project : null;

                $note = trim($_POST['note'] ?? '');

                $stmt = $pdo->prepare(
                    'INSERT INTO stock_transactions
                    (
                        item_id,
                        project_id,
                        user_id,
                        transaction_type,
                        quantity,
                        note
                    )
                    VALUES (?, ?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $item,
                    $project,
                    $u['id'],
                    $type,
                    $qty,
                    $note
                ]);

                audit(
                    $pdo,
                    'Stock transaction',
                    'inventory_item',
                    $item,
                    $type . ' ' . $qty
                );

                $pdo->commit();

                $msg = 'Stock movement recorded successfully.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('Stock movement: ' . $e->getMessage());

                $msg = $e instanceof PDOException
                    ? 'Unable to save stock movement. Please check the database.'
                    : $e->getMessage();
            }
        }
    } else {
        $msg = 'Invalid action or insufficient permission.';
    }
}

/*
|--------------------------------------------------------------------------
| LOAD INVENTORY AND PROJECTS
|--------------------------------------------------------------------------
*/

$items = $pdo->query(
    'SELECT *
     FROM inventory_items
     ORDER BY name'
)->fetchAll(PDO::FETCH_ASSOC);

$projects = $pdo->query(
    "SELECT id, title
     FROM projects
     WHERE status NOT IN ('completed', 'cancelled')
     ORDER BY title"
)->fetchAll(PDO::FETCH_ASSOC);

page_header(
    'Inventory',
    'Stock levels, reorder points, and material movement history'
);
?>

<div class="content-grid">

    <?php if ($u['role'] === 'Manager'): ?>
        <section class="panel span-6">
            <h2>Add Inventory Item</h2>

            <?php if ($msg !== ''): ?>
                <div class="alert success">
                    <?= e($msg) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <input type="hidden" name="action" value="add">

                <label>Item Name *</label>
                <input name="name" required>

                <div class="form-grid">
                    <div>
                        <label>Category</label>
                        <input name="category">
                    </div>

                    <div>
                        <label>Unit</label>
                        <input
                            name="unit"
                            value="pcs"
                            required
                        >
                    </div>

                    <div>
                        <label>Opening Quantity</label>
                        <input
                            type="number"
                            name="quantity"
                            min="0"
                            step="0.01"
                            value="0"
                        >
                    </div>

                    <div>
                        <label>Reorder Point</label>
                        <input
                            type="number"
                            name="reorder_point"
                            min="0"
                            step="0.01"
                            value="0"
                        >
                    </div>

                    <div>
                        <label>Unit Cost (₱)</label>
                        <input
                            type="number"
                            name="unit_cost"
                            min="0"
                            step="0.01"
                            value="0"
                        >
                    </div>

                    <div>
                        <label>Supplier</label>
                        <input name="supplier">
                    </div>
                </div>

                <button class="btn primary" type="submit">
                    Add Item
                </button>
            </form>
        </section>
    <?php endif; ?>

    <section class="panel <?= $u['role'] === 'Manager' ? 'span-6' : 'span-12' ?>">
        <h2>Record Stock Movement</h2>

        <?php if (
            $u['role'] === 'Personnel' &&
            empty($u['can_record_materials'])
        ): ?>
            <div class="alert error">
                Your account does not have permission to record
                material usage. Ask the Manager to enable it.
            </div>
        <?php else: ?>

            <?php if ($msg !== '' && $u['role'] === 'Personnel'): ?>
                <div class="alert success">
                    <?= e($msg) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <input type="hidden" name="action" value="move">

                <label>Inventory Item</label>
                <select name="item_id" required>
                    <option value="">Select an item</option>

                    <?php foreach ($items as $i): ?>
                        <option value="<?= (int) $i['id'] ?>">
                            <?= e($i['name']) ?>
                            (<?= e($i['quantity'] . ' ' . $i['unit']) ?> available)
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="form-grid">
                    <div>
                        <label>Movement Type</label>
                        <select name="transaction_type" required>
                            <?php foreach (
                                [
                                    'received',
                                    'issued',
                                    'consumed',
                                    'damaged',
                                    'returned',
                                    'adjustment'
                                ] as $t
                            ): ?>
                                <option value="<?= e($t) ?>">
                                    <?= e(ucfirst($t)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label>Quantity</label>
                        <input
                            type="number"
                            name="quantity"
                            min="0.01"
                            step="0.01"
                            required
                        >
                    </div>

                    <div>
                        <label>Project (Optional)</label>
                        <select name="project_id">
                            <option value="">Not linked</option>

                            <?php foreach ($projects as $p): ?>
                                <option value="<?= (int) $p['id'] ?>">
                                    <?= e($p['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label>Note / Reason</label>
                        <input
                            name="note"
                            placeholder="Work order or reason"
                        >
                    </div>
                </div>

                <button class="btn primary" type="submit">
                    Record Movement
                </button>
            </form>
        <?php endif; ?>
    </section>

    <section class="panel span-12">
        <div class="panel-head">
            <div>
                <h2>Stock on Hand</h2>
                <div class="small">
                    Items at or below reorder point are flagged.
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>On Hand</th>
                        <th>Reorder Point</th>
                        <th>Unit Cost</th>
                        <th>Supplier</th>
                        <th>State</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($items as $i): ?>
                        <tr>
                            <td><?= e($i['name']) ?></td>
                            <td><?= e($i['category']) ?></td>
                            <td>
                                <?= e($i['quantity'] . ' ' . $i['unit']) ?>
                            </td>
                            <td><?= e($i['reorder_point']) ?></td>
                            <td>
                                ₱<?= number_format((float) $i['unit_cost'], 2) ?>
                            </td>
                            <td><?= e($i['supplier']) ?></td>
                            <td>
                                <?php if (
                                    (float) $i['quantity'] <=
                                    (float) $i['reorder_point']
                                ): ?>
                                    <span class="badge red">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge">In Stock</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!$items): ?>
                <div class="empty">No inventory items have been added.</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel span-12">
        <h2>Recent Stock Transactions</h2>

        <?php
        $tx = $pdo->query(
            'SELECT
                s.*,
                i.name AS item_name,
                u.first_name,
                u.last_name,
                p.title AS project_title
             FROM stock_transactions s
             JOIN inventory_items i ON i.id = s.item_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN projects p ON p.id = s.project_id
             ORDER BY s.created_at DESC
             LIMIT 50'
        )->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Project</th>
                        <th>Recorded By</th>
                        <th>Note</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($tx as $t): ?>
                        <tr>
                            <td><?= e($t['created_at']) ?></td>
                            <td><?= e($t['item_name']) ?></td>
                            <td>
                                <?= status_badge($t['transaction_type']) ?>
                            </td>
                            <td><?= e($t['quantity']) ?></td>
                            <td><?= e($t['project_title'] ?? '—') ?></td>
                            <td>
                                <?= e(
                                    $t['first_name'] . ' ' . $t['last_name']
                                ) ?>
                            </td>
                            <td><?= e($t['note']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!$tx): ?>
                <div class="empty">
                    No stock transactions recorded.
                </div>
            <?php endif; ?>
        </div>
    </section>

</div>

<?php page_footer(); ?>