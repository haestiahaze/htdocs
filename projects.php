
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
    $id = (int) ($_POST['id'] ?? 0);

    try {
        /*
        |--------------------------------------------------------------------------
        | UPDATE PROJECT SETTINGS
        |--------------------------------------------------------------------------
        */

        if ($action === 'update') {
            $allowedStatuses = [
                'planning',
                'scheduled',
                'fabrication',
                'installation',
                'on_hold',
                'completed',
                'cancelled'
            ];

            $status = $_POST['status'] ?? 'planning';

            if (!in_array($status, $allowedStatuses, true)) {
                throw new RuntimeException('Please select a valid project status.');
            }

            $clientVisible = isset($_POST['client_visible']) ? 1 : 0;

            $startDate = trim($_POST['start_date'] ?? '');
            $dueDate = trim($_POST['due_date'] ?? '');

            $startDate = $startDate !== '' ? $startDate : null;
            $dueDate = $dueDate !== '' ? $dueDate : null;

            /*
             * Validate supplied dates.
             */
            foreach (['Start date' => $startDate, 'Due date' => $dueDate] as $label => $dateValue) {
                if ($dateValue !== null) {
                    $date = DateTime::createFromFormat('!Y-m-d', $dateValue);

                    if (!$date || $date->format('Y-m-d') !== $dateValue) {
                        throw new RuntimeException($label . ' is invalid.');
                    }
                }
            }

            if (
                $startDate !== null &&
                $dueDate !== null &&
                $dueDate < $startDate
            ) {
                throw new RuntimeException(
                    'The due date cannot be earlier than the start date.'
                );
            }

            $stmt = $pdo->prepare(
                'UPDATE projects
                 SET status = ?,
                     client_visible = ?,
                     start_date = ?,
                     due_date = ?
                 WHERE id = ?'
            );

            $stmt->execute([
                $status,
                $clientVisible,
                $startDate,
                $dueDate,
                $id
            ]);

            audit(
                $pdo,
                'Project settings updated',
                'project',
                $id,
                'status=' . $status . '; client_visible=' . $clientVisible
            );

            $msg = 'Project updated successfully.';
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE PROJECT TASK
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'task') {
            $title = trim($_POST['task_title'] ?? '');
            $description = trim($_POST['task_description'] ?? '');
            $taskDue = trim($_POST['task_due'] ?? '');

            if ($title === '') {
                throw new RuntimeException('Please enter a task title.');
            }

            if ($taskDue !== '') {
                $date = DateTime::createFromFormat('!Y-m-d', $taskDue);

                if (!$date || $date->format('Y-m-d') !== $taskDue) {
                    throw new RuntimeException('Please enter a valid task due date.');
                }
            }

            /*
             * Confirm that the project exists.
             */
            $stmt = $pdo->prepare(
                'SELECT id FROM projects WHERE id = ?'
            );
            $stmt->execute([$id]);

            if (!$stmt->fetch()) {
                throw new RuntimeException('Project not found.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO tasks
                    (project_id, title, description, due_date)
                 VALUES (?, ?, ?, ?)'
            );

            $stmt->execute([
                $id,
                $title,
                $description,
                $taskDue !== '' ? $taskDue : null
            ]);

            audit(
                $pdo,
                'Task created',
                'project',
                $id,
                $title
            );

            $msg = 'Task created successfully.';
        }

        /*
        |--------------------------------------------------------------------------
        | ASSIGN PERSONNEL TO A PROJECT OR TASK
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'assign') {
            $personnelId = (int) ($_POST['personnel_id'] ?? 0);
            $taskId = (int) ($_POST['task_id'] ?? 0);

            $taskId = $taskId > 0 ? $taskId : null;

            /*
             * Confirm that the project exists.
             */
            $stmt = $pdo->prepare(
                'SELECT id FROM projects WHERE id = ?'
            );
            $stmt->execute([$id]);

            if (!$stmt->fetch()) {
                throw new RuntimeException('Project not found.');
            }

            /*
             * Confirm that the selected person is active personnel.
             */
            $stmt = $pdo->prepare(
                "SELECT id
                 FROM users
                 WHERE id = ?
                   AND role = 'Personnel'
                   AND is_active = 1"
            );
            $stmt->execute([$personnelId]);

            if (!$stmt->fetch()) {
                throw new RuntimeException(
                    'Please select an active personnel account.'
                );
            }

            /*
             * If a task is selected, verify that it belongs to this project.
             */
            if ($taskId !== null) {
                $stmt = $pdo->prepare(
                    'SELECT id
                     FROM tasks
                     WHERE id = ?
                       AND project_id = ?'
                );
                $stmt->execute([$taskId, $id]);

                if (!$stmt->fetch()) {
                    throw new RuntimeException(
                        'The selected task does not belong to this project.'
                    );
                }
            }

            /*
             * Create the assignment.
             */
            $stmt = $pdo->prepare(
                'INSERT INTO assignments
                    (project_id, task_id, personnel_id)
                 VALUES (?, ?, ?)'
            );

            $stmt->execute([
                $id,
                $taskId,
                $personnelId
            ]);

            notify_user(
                $pdo,
                $personnelId,
                'New project assignment',
                'You have been assigned to project #' . $id . '.'
            );

            audit(
                $pdo,
                'Personnel assigned',
                'project',
                $id,
                'personnel=' . $personnelId
            );

            $msg = 'Personnel assigned successfully.';
        }

        else {
            throw new RuntimeException('Invalid action.');
        }
    } catch (Throwable $e) {
        if ($e instanceof RuntimeException) {
            $error = $e->getMessage();
        } else {
            error_log('Project management error: ' . $e->getMessage());
            $error = 'Unable to complete the requested action. Please try again.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| LOAD PROJECTS
|--------------------------------------------------------------------------
*/

$projects = $pdo->query(
    'SELECT
        p.*,
        u.first_name,
        u.last_name
     FROM projects p
     JOIN users u ON u.id = p.client_id
     ORDER BY p.created_at DESC'
)->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| LOAD ACTIVE PERSONNEL
|--------------------------------------------------------------------------
*/

$people = $pdo->query(
    "SELECT id, first_name, last_name
     FROM users
     WHERE role = 'Personnel'
       AND is_active = 1
     ORDER BY first_name, last_name"
)->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/

page_header(
    'Projects',
    'Schedule, assign, and control client visibility'
);
?>

<div class="content-grid">

    <section class="panel span-12">

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

        <?php if (empty($projects)): ?>

            <div class="empty">
                No projects yet. Convert an accepted quotation to create one.
            </div>

        <?php else: ?>

            <?php foreach ($projects as $project): ?>

                <div
                    class="panel"
                    style="box-shadow:none; margin-bottom:20px;"
                >

                    <!-- PROJECT INFORMATION -->
                    <div class="panel-head">

                        <div>
                            <h2>
                                #<?= (int) $project['id'] ?>
                                ·
                                <?= e($project['title']) ?>
                            </h2>

                            <div class="small">
                                <?= e(
                                    $project['first_name'] . ' ' .
                                    $project['last_name']
                                ) ?>

                                ·

                                <?= e($project['location'] ?? '') ?>
                            </div>
                        </div>

                        <?= status_badge($project['status']) ?>

                    </div>

                    <!-- UPDATE PROJECT -->
                    <form method="post" class="form-grid">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrf_token()) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="update"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $project['id'] ?>"
                        >

                        <div>
                            <label>Status</label>

                            <select name="status" required>
                                <?php
                                $statuses = [
                                    'planning',
                                    'scheduled',
                                    'fabrication',
                                    'installation',
                                    'on_hold',
                                    'completed',
                                    'cancelled'
                                ];
                                ?>

                                <?php foreach ($statuses as $status): ?>
                                    <option
                                        value="<?= e($status) ?>"
                                        <?= $project['status'] === $status
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= e(ucwords(str_replace('_', ' ', $status))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label>Client visibility</label>

                            <label>
                                <input
                                    type="checkbox"
                                    name="client_visible"
                                    value="1"
                                    style="width:auto;"
                                    <?= !empty($project['client_visible'])
                                        ? 'checked'
                                        : '' ?>
                                >

                                Show updates to client
                            </label>
                        </div>

                        <div>
                            <label>Start date</label>

                            <input
                                type="date"
                                name="start_date"
                                value="<?= e($project['start_date'] ?? '') ?>"
                            >
                        </div>

                        <div>
                            <label>Due date</label>

                            <input
                                type="date"
                                name="due_date"
                                value="<?= e($project['due_date'] ?? '') ?>"
                            >
                        </div>

                        <div class="wide">
                            <button type="submit" class="btn primary">
                                Save project
                            </button>
                        </div>

                    </form>

                   

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</div>

<?php page_footer(); ?>