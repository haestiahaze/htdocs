
<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

$u = require_role(['Personnel']);

/*
|--------------------------------------------------------------------------
| COUNT ASSIGNED PROJECTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT COUNT(DISTINCT project_id)
     FROM assignments
     WHERE personnel_id = ?'
);

$stmt->execute([$u['id']]);

$projectCount = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| COUNT OPEN ASSIGNED TASKS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM assignments a
     JOIN tasks t ON t.id = a.task_id
     WHERE a.personnel_id = ?
       AND t.status != 'completed'"
);

$stmt->execute([$u['id']]);

$taskCount = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| COUNT LOW-STOCK INVENTORY ITEMS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    'SELECT COUNT(*)
     FROM inventory_items
     WHERE quantity <= reorder_point'
);

$lowStockCount = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/

page_header(
    'Personnel overview',
    'Your assigned work and on-site updates'
);

?>

<!-- DASHBOARD STATISTICS -->
<div class="stat-grid">

    <?php
    stat_card(
        'Assigned projects',
        $projectCount,
        'Projects assigned to you'
    );

    stat_card(
        'Open tasks',
        $taskCount,
        'Not yet completed'
    );

    stat_card(
        'Low-stock items',
        $lowStockCount,
        'Inventory watch'
    );

    stat_card(
        'Material recording',
        !empty($u['can_record_materials'])
            ? 'Enabled'
            : 'Disabled',
        'Permission set by Manager'
    );
    ?>

</div>

<div class="content-grid">

    <!-- MY ACTIVE ASSIGNMENTS -->
    <section class="panel span-8">

        <div class="panel-head">

            <div>
                <h2>My active assignments</h2>

                <div class="small">
                    Tasks and projects assigned by the Manager.
                </div>
            </div>

            <a href="my_tasks.php" class="btn primary">
                View tasks
            </a>

        </div>

        <?php

        /*
         * Load the personnel member's assignments.
         */
        $stmt = $pdo->prepare(
            'SELECT
                p.title,
                p.location,
                p.status AS project_status,
                t.title AS task_title,
                t.status AS task_status,
                t.due_date
             FROM assignments a
             JOIN projects p
                ON p.id = a.project_id
             LEFT JOIN tasks t
                ON t.id = a.task_id
             WHERE a.personnel_id = ?
             ORDER BY t.due_date ASC
             LIMIT 8'
        );

        $stmt->execute([$u['id']]);

        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        ?>

        <div class="table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>Project / Task</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Due Date</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($assignments)): ?>

                        <tr>
                            <td colspan="4">
                                No assignments yet.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($assignments as $assignment): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= e($assignment['title']) ?>
                                    </strong>

                                    <br>

                                    <span class="small">
                                        <?= e(
                                            $assignment['task_title']
                                                ?? 'Project assignment'
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e($assignment['location'] ?? '') ?>
                                </td>

                                <td>
                                    <?php
                                    $status = $assignment['task_status']
                                        ?? $assignment['project_status'];

                                    echo status_badge($status);
                                    ?>
                                </td>

                                <td>
                                    <?= e(
                                        $assignment['due_date'] ?: '—'
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>
            </table>

        </div>

    </section>

    <!-- ON-SITE ACTIONS -->
    <section class="panel span-4">

        <h2>On-site actions</h2>

        <p class="small">
            Quick access to your daily work tools.
        </p>

        <a class="quick-link" href="my_tasks.php">
            Update task progress
            <span>↗</span>
        </a>

        <a class="quick-link" href="inventory.php">
            Check material availability
            <span>↗</span>
        </a>

        <a class="quick-link" href="report_issue.php">
            Report delay / damage / shortage
            <span>↗</span>
        </a>

        <?php if (!empty($u['can_record_materials'])): ?>

            <a class="quick-link" href="record_materials.php">
                Record material usage
                <span>↗</span>
            </a>

        <?php endif; ?>

    </section>

</div>

<?php page_footer(); ?>
