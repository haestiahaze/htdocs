
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

$u = require_role(['Personnel']);

$msg = '';
$error = '';

/*
|--------------------------------------------------------------------------
| UPDATE TASK PROGRESS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? 'in_progress';
        $notes = trim($_POST['notes'] ?? '');

        $allowed_statuses = [
            'not_started',
            'in_progress',
            'completed'
        ];

        if (!$task_id || $task_id < 1) {
            throw new Exception('Invalid task selected.');
        }

        if (!in_array($status, $allowed_statuses, true)) {
            throw new Exception('Please select a valid task status.');
        }

        /*
         * Verify that this task is assigned to the logged-in
         * Personnel user before allowing an update.
         */
        $stmt = $pdo->prepare(
            "SELECT t.id
             FROM tasks t
             INNER JOIN assignments a ON a.task_id = t.id
             WHERE t.id = ?
               AND a.personnel_id = ?
             LIMIT 1"
        );

        $stmt->execute([
            $task_id,
            $u['id']
        ]);

        if (!$stmt->fetch()) {
            throw new Exception(
                'You are not authorized to update this task.'
            );
        }

        /*
         * Update task status and work notes.
         */
        $stmt = $pdo->prepare(
            "UPDATE tasks
             SET status = ?, notes = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $status,
            $notes,
            $task_id
        ]);

        audit(
            $pdo,
            'Task progress updated',
            'task',
            $task_id,
            $status
        );

        $msg = 'Task progress saved successfully.';

    } catch (Throwable $e) {
        if ($e instanceof PDOException) {
            error_log('Task update database error: ' . $e->getMessage());
            $error = 'Unable to save task progress. Please try again.';
        } else {
            $error = $e->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| LOAD ASSIGNED PROJECTS AND TASKS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        t.id AS task_id,
        t.title AS task_title,
        t.description AS task_description,
        t.due_date,
        t.status AS task_status,
        t.notes,
        p.id AS project_id,
        p.title AS project_title,
        p.location
     FROM assignments a
     INNER JOIN projects p
        ON p.id = a.project_id
     LEFT JOIN tasks t
        ON t.id = a.task_id
     WHERE a.personnel_id = ?
     ORDER BY
        CASE WHEN t.due_date IS NULL THEN 1 ELSE 0 END,
        t.due_date ASC,
        p.title ASC"
);

$stmt->execute([$u['id']]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/

page_header(
    'Assigned Tasks',
    'Update progress and leave notes from the work site.'
);
?>

<div class="content-grid">

    <section class="panel span-12">

        <div class="panel-head">
            <div>
                <h2>My Assigned Tasks</h2>
                <p class="small">
                    Update the status of your assigned tasks and record
                    work notes or completion details.
                </p>
            </div>
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

        <?php if (empty($rows)): ?>

            <div class="empty">
                <p>You have no assigned tasks yet.</p>
            </div>

        <?php else: ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Project / Task</th>
                            <th>Due Date</th>
                            <th>Status and Work Notes</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= e($r['project_title']) ?>
                                    </strong>

                                    <br>

                                    <?= e(
                                        $r['task_title']
                                        ?? 'Project assignment'
                                    ) ?>

                                    <?php if (!empty($r['location'])): ?>
                                        <br>
                                        <span class="small">
                                            <?= e($r['location']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($r['task_description'])): ?>
                                        <br>
                                        <span class="small">
                                            <?= e($r['task_description']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= e($r['due_date'] ?? '—') ?>
                                </td>

                                <td>
                                    <?php if (!empty($r['task_id'])): ?>

                                        <form method="post">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e(csrf_token()) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="task_id"
                                                value="<?= (int) $r['task_id'] ?>"
                                            >

                                            <label for="status-<?= (int) $r['task_id'] ?>">
                                                Task Status
                                            </label>

                                            <select
                                                id="status-<?= (int) $r['task_id'] ?>"
                                                name="status"
                                                required
                                            >
                                                <?php
                                                $statuses = [
                                                    'not_started' => 'Not Started',
                                                    'in_progress' => 'In Progress',
                                                    'completed' => 'Completed'
                                                ];
                                                ?>

                                                <?php foreach ($statuses as $value => $label): ?>
                                                    <option
                                                        value="<?= e($value) ?>"
                                                        <?= ($r['task_status'] === $value)
                                                            ? 'selected'
                                                            : '' ?>
                                                    >
                                                        <?= e($label) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <label for="notes-<?= (int) $r['task_id'] ?>">
                                                Work Notes
                                            </label>

                                            <textarea
                                                id="notes-<?= (int) $r['task_id'] ?>"
                                                name="notes"
                                                rows="3"
                                                maxlength="5000"
                                                placeholder="Enter work progress, materials used, issues, or completion details..."
                                            ><?= e($r['notes'] ?? '') ?></textarea>

                                            <button
                                                type="submit"
                                                class="btn primary"
                                            >
                                                Save Update
                                            </button>
                                        </form>

                                    <?php else: ?>

                                        <span class="small">
                                            No specific task assigned to this project.
                                        </span>

                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>

</div>

<?php
page_footer();
?>
