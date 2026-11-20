
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

$u = require_role(['Client']);

$stmt = $pdo->prepare(
    'SELECT *
     FROM projects
     WHERE client_id = ?
       AND client_visible = 1
     ORDER BY created_at DESC'
);

$stmt->execute([$u['id']]);
$rows = $stmt->fetchAll();

page_header(
    'My projects',
    'Only updates enabled for client visibility are shown'
);
?>

<div class="content-grid">
    <section class="panel span-12">

        <?php foreach ($rows as $p): ?>

            <div class="panel"
                 style="box-shadow: none; margin-bottom: 14px;">

                <div class="panel-head">
                    <div>
                        <h2><?= e($p['title']) ?></h2>

                        <div class="small">
                            <?= e($p['location']) ?>
                            · Due
                            <?= e($p['due_date'] ?: 'Not scheduled') ?>
                        </div>
                    </div>

                    <?= status_badge($p['status']) ?>
                </div>

                <p><?= e($p['description']) ?></p>

                <?php
                $taskStmt = $pdo->prepare(
                    'SELECT *
                     FROM tasks
                     WHERE project_id = ?
                     ORDER BY due_date'
                );

                $taskStmt->execute([$p['id']]);
                $tasks = $taskStmt->fetchAll();
                ?>

                <?php foreach ($tasks as $t): ?>

                    <div class="notice">
                        <strong><?= e($t['title']) ?></strong>
                        · <?= status_badge($t['status']) ?>

                        <br>

                        <?= e($t['notes'] ?? '') ?>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endforeach; ?>

        <?php if (empty($rows)): ?>

            <div class="empty">
                There are no client-visible projects yet.
                The Manager enables visibility when updates
                are ready to share.
            </div>

        <?php endif; ?>

    </section>
</div>

<?php
page_footer();
?>
