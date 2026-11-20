
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

/*
|--------------------------------------------------------------------------
| MANAGER ACCESS ONLY
|--------------------------------------------------------------------------
*/
require_role(['Manager']);

/*
|--------------------------------------------------------------------------
| DATE FILTERS
|--------------------------------------------------------------------------
*/
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

/*
|--------------------------------------------------------------------------
| VALIDATE DATE INPUTS
|--------------------------------------------------------------------------
*/
$fromDate = DateTime::createFromFormat('!Y-m-d', $from);
$toDate   = DateTime::createFromFormat('!Y-m-d', $to);

if (
    !$fromDate ||
    $fromDate->format('Y-m-d') !== $from ||
    !$toDate ||
    $toDate->format('Y-m-d') !== $to ||
    $from > $to
) {
    $from = date('Y-m-01');
    $to   = date('Y-m-d');
}

/*
|--------------------------------------------------------------------------
| INQUIRY STATUS SUMMARY
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare(
    'SELECT status, COUNT(*) AS total
     FROM inquiries
     WHERE DATE(created_at) BETWEEN ? AND ?
     GROUP BY status
     ORDER BY status'
);

$stmt->execute([$from, $to]);
$inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PROJECT STATUS SUMMARY
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare(
    'SELECT status, COUNT(*) AS total
     FROM projects
     WHERE DATE(created_at) BETWEEN ? AND ?
     GROUP BY status
     ORDER BY status'
);

$stmt->execute([$from, $to]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| ACCEPTED / APPROVED QUOTATION VALUE
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare(
    'SELECT COALESCE(SUM(total), 0)
     FROM quotations
     WHERE status IN ("accepted", "approved")
       AND DATE(created_at) BETWEEN ? AND ?'
);

$stmt->execute([$from, $to]);
$revenue = (float) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| INVENTORY SNAPSHOT
|--------------------------------------------------------------------------
*/
$stmt = $pdo->query(
    'SELECT
        name,
        quantity,
        unit,
        reorder_point,
        unit_cost,
        quantity * unit_cost AS stock_value
     FROM inventory_items
     ORDER BY name'
);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/
page_header(
    'Reports',
    'Filter business activity by date and export the current summary to CSV.'
);
?>

<div class="content-grid">

    <!-- DATE FILTERS AND CSV EXPORT -->
    <section class="panel span-12">

        <div class="panel-head">
            <h2>Report Filters</h2>
        </div>

        <form method="get" class="inline-form">

            <div>
                <label for="from">From</label>
                <input
                    type="date"
                    id="from"
                    name="from"
                    value="<?= e($from) ?>"
                    required
                >
            </div>

            <div>
                <label for="to">To</label>
                <input
                    type="date"
                    id="to"
                    name="to"
                    value="<?= e($to) ?>"
                    min="<?= e($from) ?>"
                    required
                >
            </div>

            <button type="submit" class="btn primary">
                Apply Filters
            </button>

            <a
                class="btn secondary"
                href="reports_export.php?from=<?= urlencode($from) ?>&amp;to=<?= urlencode($to) ?>"
            >
                Export CSV
            </a>

        </form>

    </section>


    <!-- ACCEPTED QUOTATION VALUE -->
    <section class="panel span-4">

        <div class="stat-label">
            Accepted Quotation Value
        </div>

        <div class="stat-number">
            ₱<?= number_format($revenue, 2) ?>
        </div>

        <div class="stat-note">
            Accepted or approved quotations created during the selected dates.
        </div>

    </section>


    <!-- INQUIRY STATUS -->
    <section class="panel span-4">

        <div class="panel-head">
            <h2>Inquiry Status</h2>
        </div>

        <?php if (!empty($inquiries)): ?>

            <?php foreach ($inquiries as $row): ?>

                <div class="quick-link">
                    <span>
                        <?= e($row['status'] ?? 'Unknown') ?>
                    </span>

                    <strong>
                        <?= (int) $row['total'] ?>
                    </strong>
                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">
                No inquiries found for the selected dates.
            </div>

        <?php endif; ?>

    </section>


    <!-- PROJECT STATUS -->
    <section class="panel span-4">

        <div class="panel-head">
            <h2>Project Status</h2>
        </div>

        <?php if (!empty($projects)): ?>

            <?php foreach ($projects as $row): ?>

                <div class="quick-link">
                    <span>
                        <?= e($row['status'] ?? 'Unknown') ?>
                    </span>

                    <strong>
                        <?= (int) $row['total'] ?>
                    </strong>
                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">
                No projects found for the selected dates.
            </div>

        <?php endif; ?>

    </section>


    <!-- INVENTORY SNAPSHOT -->
    <section class="panel span-12">

        <div class="panel-head">
            <h2>Inventory Snapshot</h2>

            <span class="small">
                Current inventory quantities and estimated stock values
            </span>
        </div>

        <div class="table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Reorder Point</th>
                        <th>Unit Cost</th>
                        <th>Stock Value</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($items)): ?>

                        <?php foreach ($items as $item): ?>

                            <tr>
                                <td>
                                    <?= e($item['name']) ?>
                                </td>

                                <td>
                                    <?= e(
                                        $item['quantity'] . ' ' . $item['unit']
                                    ) ?>
                                </td>

                                <td>
                                    <?= e($item['reorder_point']) ?>
                                </td>

                                <td>
                                    ₱<?= number_format(
                                        (float) $item['unit_cost'],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    ₱<?= number_format(
                                        (float) $item['stock_value'],
                                        2
                                    ) ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="empty">
                                No inventory items found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>
            </table>

        </div>

    </section>

</div>

<?php
page_footer();
?>