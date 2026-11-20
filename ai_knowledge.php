

<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';


// Only Managers can manage the AI knowledge base.
$user = require_role(['Manager']);

$msg = '';

/**
 * Process submitted forms.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';

    // Add a new entry or update an existing entry.
    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($title !== '' && $content !== '') {
            $enabled = isset($_POST['enabled']) ? 1 : 0;

            if ($id > 0) {
                // Update an existing knowledge entry.
                $stmt = $pdo->prepare(
                    'UPDATE ai_knowledge
                     SET title = ?,
                         category = ?,
                         content = ?,
                         enabled = ?,
                         updated_by = ?
                     WHERE id = ?'
                );

                $stmt->execute([
                    $title,
                    $category,
                    $content,
                    $enabled,
                    $user['id'],
                    $id
                ]);

                audit(
                    $pdo,
                    'AI knowledge updated',
                    'ai_knowledge',
                    $id
                );

            } else {
                // Create a new knowledge entry.
                $stmt = $pdo->prepare(
                    'INSERT INTO ai_knowledge
                        (title, category, content, enabled, updated_by)
                     VALUES (?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $title,
                    $category,
                    $content,
                    $enabled,
                    $user['id']
                ]);

                $newId = (int) $pdo->lastInsertId();

                audit(
                    $pdo,
                    'AI knowledge created',
                    'ai_knowledge',
                    $newId
                );
            }

            $msg = 'Knowledge entry saved.';
        } else {
            $msg = 'Please enter both a title and approved information.';
        }

    } elseif ($action === 'toggle') {
        // Enable or disable an existing entry.
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE ai_knowledge
                 SET enabled = 1 - enabled,
                     updated_by = ?
                 WHERE id = ?'
            );

            $stmt->execute([
                $user['id'],
                $id
            ]);

            if ($stmt->rowCount() > 0) {
                audit(
                    $pdo,
                    'AI knowledge enabled state toggled',
                    'ai_knowledge',
                    $id
                );

                $msg = 'Entry state updated.';
            } else {
                $msg = 'Entry not found or its state was unchanged.';
            }
        } else {
            $msg = 'Invalid knowledge entry ID.';
        }
    }
}

// Retrieve knowledge entries, newest updates first.
$stmt = $pdo->query(
    'SELECT *
     FROM ai_knowledge
     ORDER BY updated_at DESC'
);

$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display the page.
page_header(
    'AI knowledge',
    'Maintain the approved information available to the client assistant'
);

?>

<div class="content-grid">

    <!-- Add a knowledge entry -->
    <section class="panel span-5">

        <h2>Add knowledge entry</h2>

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

            <input
                type="hidden"
                name="action"
                value="save"
            >

            <label for="title">Title</label>

            <input
                type="text"
                id="title"
                name="title"
                required
            >

            <label for="category">Category</label>

            <input
                type="text"
                id="category"
                name="category"
                placeholder="Services, Materials, Process, FAQ"
            >

            <label for="content">
                Approved answer / information
            </label>

            <textarea
                id="content"
                name="content"
                required
            ></textarea>

            <label>
                <input
                    type="checkbox"
                    name="enabled"
                    value="1"
                    checked
                    style="width: auto;"
                >
                Enabled for AI assistant
            </label>

            <button
                type="submit"
                class="btn primary"
            >
                Save entry
            </button>

        </form>

    </section>

    <!-- Display existing knowledge entries -->
    <section class="panel span-7">

        <h2>Knowledge base entries</h2>

        <?php if (empty($entries)): ?>

            <div class="notice">
                No knowledge entries have been added yet.
            </div>

        <?php else: ?>

            <?php foreach ($entries as $entry): ?>

                <div class="notice">

                    <div class="panel-head">

                        <strong>
                            <?= e($entry['title'] ?? '') ?>
                        </strong>

                        <?php if ((int) ($entry['enabled'] ?? 0) === 1): ?>

                            <span class="badge">
                                Enabled
                            </span>

                        <?php else: ?>

                            <span class="badge gray">
                                Disabled
                            </span>

                        <?php endif; ?>

                    </div>

                    <div class="small">
                        <?= e($entry['category'] ?? '') ?>
                    </div>

                    <p>
                        <?= nl2br(e($entry['content'] ?? '')) ?>
                    </p>

                    <form method="post">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrf_token()) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="toggle"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $entry['id'] ?>"
                        >

                        <button
                            type="submit"
                            class="btn secondary"
                        >
                            <?= (int) $entry['enabled'] === 1
                                ? 'Disable'
                                : 'Enable' ?>
                        </button>

                    </form>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</div>

<?php

page_footer();

?>
