
<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

// Only Managers can access AI escalations.
$user = require_role(['Manager']);

$msg = '';

/**
 * Process the Manager's answer.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $id = (int) ($_POST['id'] ?? 0);
    $answer = trim($_POST['answer'] ?? '');
    $addToKnowledge = isset($_POST['add_to_knowledge']) ? 1 : 0;

    if ($id <= 0) {
        $msg = 'Invalid question ID.';

    } elseif ($answer === '') {
        $msg = 'Please enter an answer before submitting.';

    } else {
        // Confirm that the escalation exists and is still open.
        $stmt = $pdo->prepare(
            "SELECT id, client_id, question
             FROM ai_escalations
             WHERE id = ?
               AND status = 'open'"
        );

        $stmt->execute([$id]);
        $escalation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$escalation) {
            $msg = 'Question not found or it has already been answered.';

        } else {
            try {
                // Use a transaction to keep related database changes together.
                $pdo->beginTransaction();

                // Save the Manager's answer.
                $stmt = $pdo->prepare(
                    "UPDATE ai_escalations
                     SET answer = ?,
                         status = 'answered',
                         add_to_knowledge = ?,
                         answered_at = NOW()
                     WHERE id = ?
                       AND status = 'open'"
                );

                $stmt->execute([
                    $answer,
                    $addToKnowledge,
                    $id
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        'The question could not be updated.'
                    );
                }

                // Optionally add the answer to the AI knowledge base.
                if ($addToKnowledge === 1) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO ai_knowledge
                            (title, category, content, enabled, updated_by)
                         VALUES (?, ?, ?, ?, ?)'
                    );

                    $knowledgeContent =
                        'Question: ' . $escalation['question']
                        . "\n\nAnswer: " . $answer;

                    $stmt->execute([
                        'Manager answer #' . $id,
                        'FAQ',
                        $knowledgeContent,
                        1,
                        $user['id']
                    ]);
                }

                // Record the action in the audit log.
                audit(
                    $pdo,
                    'AI escalation answered',
                    'ai_escalation',
                    $id,
                    'add_to_knowledge=' . $addToKnowledge
                );

                // Commit the database changes.
                $pdo->commit();

                // Notify the client after the database changes succeed.
                if (!empty($escalation['client_id'])) {
                    notify_user(
                        $pdo,
                        (int) $escalation['client_id'],
                        'Manager answered your question',
                        $answer
                    );
                }

                $msg = 'Answer saved and client notified.';

            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log(
                    'AI escalation error: ' . $exception->getMessage()
                );

                $msg = 'Unable to save the answer. Please try again.';
            }
        }
    }
}

/**
 * Retrieve escalations, showing open questions first.
 */
$sql = '
    SELECT
        e.*,
        u.first_name,
        u.last_name,
        u.email
    FROM ai_escalations AS e
    LEFT JOIN users AS u
        ON u.id = e.client_id
    ORDER BY
        (e.status = "open") DESC,
        e.created_at DESC
';

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Display the page.
page_header(
    'AI escalations',
    'Review uncertain questions and send accurate Manager responses'
);

?>

<div class="content-grid">

    <section class="panel span-12">

        <?php if ($msg !== ''): ?>
            <div class="alert success">
                <?= e($msg) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($rows)): ?>

            <div class="empty">
                No escalated questions.
            </div>

        <?php else: ?>

            <?php foreach ($rows as $row): ?>

                <div
                    class="panel"
                    style="box-shadow: none; margin-bottom: 12px;"
                >

                    <div class="panel-head">

                        <div>

                            <h2>
                                Question #<?= (int) $row['id'] ?>
                            </h2>

                            <div class="small">
                                <?= e(
                                    trim(
                                        ($row['first_name'] ?? 'Former client')
                                        . ' '
                                        . ($row['last_name'] ?? '')
                                    )
                                ) ?>

                                ·

                                <?= e($row['created_at'] ?? '') ?>
                            </div>

                        </div>

                        <?= status_badge($row['status'] ?? 'open') ?>

                    </div>

                    <p>
                        <strong>Client question:</strong>

                        <?= e($row['question'] ?? '') ?>
                    </p>

                    <?php if (($row['status'] ?? '') === 'open'): ?>

                        <!-- Answer form for open escalations -->
                        <form method="post">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrf_token()) ?>"
                            >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int) $row['id'] ?>"
                            >

                            <label for="answer-<?= (int) $row['id'] ?>">
                                Reply to client
                            </label>

                            <textarea
                                id="answer-<?= (int) $row['id'] ?>"
                                name="answer"
                                required
                            ></textarea>

                            <label>
                                <input
                                    type="checkbox"
                                    name="add_to_knowledge"
                                    value="1"
                                    style="width: auto;"
                                >

                                Add this answer to AI knowledge base
                            </label>

                            <button
                                type="submit"
                                class="btn primary"
                            >
                                Send answer
                            </button>

                        </form>

                    <?php else: ?>

                        <!-- Display the saved answer -->
                        <p>
                            <strong>Answer:</strong>

                            <?= nl2br(e($row['answer'] ?? '')) ?>
                        </p>

                        <?php if (!empty($row['answered_at'])): ?>

                            <div class="small">
                                Answered:
                                <?= e($row['answered_at']) ?>
                            </div>

                        <?php endif; ?>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</div>

<?php

page_footer();

?>
