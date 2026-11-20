<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

require_role(['Manager']);

$assignment_message = '';
$assignment_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign_task') {
    try {
        verify_csrf();

        $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
        $personnelId = filter_input(INPUT_POST, 'personnel_id', FILTER_VALIDATE_INT);
        $taskTitle = trim((string) ($_POST['task_title'] ?? ''));
        $taskDescription = trim((string) ($_POST['task_description'] ?? ''));
        $dueDate = trim((string) ($_POST['due_date'] ?? ''));

        if (!$projectId || $projectId < 1 || !$personnelId || $personnelId < 1) {
            throw new RuntimeException('Please select a valid project and personnel member.');
        }
        if ($taskTitle === '' || mb_strlen($taskTitle) > 180) {
            throw new RuntimeException('Enter a task title (maximum 180 characters).');
        }
        if (mb_strlen($taskDescription) > 5000) {
            throw new RuntimeException('Task description must be 5,000 characters or fewer.');
        }
        if ($dueDate !== '') {
            $date = DateTime::createFromFormat('!Y-m-d', $dueDate);
            if (!$date || $date->format('Y-m-d') !== $dueDate) {
                throw new RuntimeException('Please enter a valid due date.');
            }
        } else {
            $dueDate = null;
        }

        // Validate that the selected project is active and the account is Personnel.
        $checkProject = $pdo->prepare(
            "SELECT id FROM projects
             WHERE id = ? AND LOWER(status) NOT IN ('completed', 'cancelled')
             LIMIT 1"
        );
        $checkProject->execute([$projectId]);
        if (!$checkProject->fetchColumn()) {
            throw new RuntimeException('The selected project was not found or is no longer active.');
        }

        $checkPersonnel = $pdo->prepare(
            "SELECT id FROM users
             WHERE id = ? AND LOWER(role) = 'personnel' AND is_active = 1
             LIMIT 1"
        );
        $checkPersonnel->execute([$personnelId]);
        if (!$checkPersonnel->fetchColumn()) {
            throw new RuntimeException('The selected active Personnel account was not found.');
        }

        $pdo->beginTransaction();

        // tasks.project_id is required by database.sql, so persist the chosen project here.
        $createTask = $pdo->prepare(
            "INSERT INTO tasks
                (project_id, title, description, due_date, status, notes)
             VALUES
                (?, ?, ?, ?, 'not_started', '')"
        );
        $createTask->execute([$projectId, $taskTitle, $taskDescription, $dueDate]);
        $taskId = (int) $pdo->lastInsertId();

        $createAssignment = $pdo->prepare(
            'INSERT INTO assignments (project_id, task_id, personnel_id)
             VALUES (?, ?, ?)'
        );
        $createAssignment->execute([$projectId, $taskId, $personnelId]);

        audit(
            $pdo,
            'Task assigned to personnel',
            'task',
            $taskId,
            'Project ' . $projectId . ', personnel ' . $personnelId
        );

        $pdo->commit();
        $assignment_message = 'Task assigned successfully. The personnel member can now view it in My Tasks.';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e instanceof PDOException) {
            error_log('Manager task assignment error: ' . $e->getMessage());
            $assignment_error = 'Unable to assign the task. Please check the database and try again.';
        } else {
            $assignment_error = $e->getMessage();
        }
    }
}

$counts = [];
$tables = ['inquiries', 'projects', 'users', 'inventory_items', 'ai_escalations'];
foreach ($tables as $table) {
    $sql = "SELECT COUNT(*) FROM {$table}";
    if ($table === 'ai_escalations') {
        $sql .= " WHERE status = 'open'";
    } elseif ($table === 'projects') {
        $sql .= " WHERE status NOT IN ('completed', 'cancelled')";
    }
    $counts[$table] = (int) $pdo->query($sql)->fetchColumn();
}

$low = (int) $pdo->query(
    'SELECT COUNT(*) FROM inventory_items WHERE quantity <= reorder_point'
)->fetchColumn();

$projectsForAssignment = $pdo->query(
    "SELECT id, title, location FROM projects
     WHERE LOWER(status) NOT IN ('completed', 'cancelled')
     ORDER BY title ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$personnelList = $pdo->query(
    "SELECT id, first_name, last_name FROM users
     WHERE LOWER(role) = 'personnel' AND is_active = 1
     ORDER BY first_name ASC, last_name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

page_header(
    'Manager dashboard',
    'Business overview, delivery performance, stock health, and personnel task assignments'
);
?>

<div class="stat-grid">
    <?php
    stat_card('Inquiries', $counts['inquiries'], 'All incoming requests');
    stat_card('Active projects', $counts['projects'], 'Projects in progress');
    stat_card('Team accounts', $counts['users'], 'Clients and personnel');
    stat_card('Low stock', $low, 'Items at or below reorder point');
    ?>
</div>

<section class="panel" style="margin-bottom: 1.25rem;">
    <div class="panel-head">
        <div>
            <h2>Assign Task to Personnel</h2>
            <p class="small">Choose a project and personnel member, then enter the task details.</p>
        </div>
        <a class="btn" href="manager_tasks.php">View My Tasks page ↗</a>
    </div>

    <?php if ($assignment_message !== ''): ?>
        <div class="alert success"><?= e($assignment_message) ?></div>
    <?php endif; ?>
    <?php if ($assignment_error !== ''): ?>
        <div class="alert error"><?= e($assignment_error) ?></div>
    <?php endif; ?>

    <?php if (empty($projectsForAssignment)): ?>
        <div class="alert error">There are no active projects available for assignment. Add or reopen a project first.</div>
    <?php elseif (empty($personnelList)): ?>
        <div class="alert error">No active Personnel accounts were found. Create or activate a Personnel account first.</div>
    <?php else: ?>
        <form method="post" action="" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="assign_task">

            <div>
                <label for="project_id">Project</label>
                <select id="project_id" name="project_id" required>
                    <option value="">Select project</option>
                    <?php foreach ($projectsForAssignment as $project): ?>
                        <option value="<?= (int) $project['id'] ?>">
                            <?= e($project['title']) ?><?= !empty($project['location']) ? ' — ' . e($project['location']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="personnel_id">Assign to Personnel</label>
                <select id="personnel_id" name="personnel_id" required>
                    <option value="">Select personnel</option>
                    <?php foreach ($personnelList as $personnel): ?>
                        <option value="<?= (int) $personnel['id'] ?>">
                            <?= e(trim($personnel['first_name'] . ' ' . $personnel['last_name'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="task_title">Task Title</label>
                <input type="text" id="task_title" name="task_title" maxlength="180" required placeholder="e.g. Inspect project site">
            </div>

            <div>
                <label for="due_date">Due Date (optional)</label>
                <input type="date" id="due_date" name="due_date">
            </div>

            <div style="grid-column: 1 / -1;">
                <label for="task_description">Task Instructions / Description</label>
                <textarea id="task_description" name="task_description" rows="4" maxlength="5000" placeholder="Describe the work, materials, measurements, or instructions..."></textarea>
            </div>

            <div style="grid-column: 1 / -1;">
                <button type="submit" class="btn primary">Assign Task</button>
            </div>
        </form>
    <?php endif; ?>
</section>

<div class="content-grid">
    <section class="panel span-8">
        <div class="panel-head">
            <div>
                <h2>Latest inquiries</h2>
                <div class="small">Review new opportunities and move them forward.</div>
            </div>
            <a class="btn primary" href="manage_inquiries.php">Manage inquiries</a>
        </div>

        <?php
        $stmt = $pdo->query(
            'SELECT i.*, u.first_name, u.last_name
             FROM inquiries i
             JOIN users u ON u.id = i.client_id
             ORDER BY i.created_at DESC
             LIMIT 7'
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Client / Type</th><th>Location</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><strong><?= e($r['first_name'] . ' ' . $r['last_name']) ?></strong><br><span class="small"><?= e($r['project_type']) ?></span></td>
                            <td><?= e($r['location']) ?></td>
                            <td><?= status_badge($r['status']) ?></td>
                            <td><?= e(date('M j', strtotime($r['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($rows)): ?><div class="empty">No inquiries submitted.</div><?php endif; ?>
        </div>
    </section>

    <section class="panel span-4">
        <h2>Needs attention</h2>
        <a class="quick-link" href="ai_escalations.php">AI escalations <span class="badge orange"><?= $counts['ai_escalations'] ?></span></a>
        <a class="quick-link" href="inventory.php">Low-stock items <span class="badge <?= $low > 0 ? 'red' : '' ?>"><?= $low ?></span></a>
        <a class="quick-link" href="projects.php">Project monitoring <span>↗</span></a>
        <a class="quick-link" href="reports.php">Reports &amp; performance <span>↗</span></a>
        <a class="quick-link" href="manage_users.php">Personnel permissions <span>↗</span></a>
        <a class="quick-link" href="manager_tasks.php">View personnel task page <span>↗</span></a>
    </section>
</div>

<?php page_footer(); ?>