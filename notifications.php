<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

$u = require_login();

$msg = '';
$error = '';

/*
|--------------------------------------------------------------------------
| MARK ALL NOTIFICATIONS AS READ
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $stmt = $pdo->prepare(
            'UPDATE notifications
             SET is_read = 1
             WHERE user_id = ?'
        );

        $stmt->execute([$u['id']]);

        $msg = 'All notifications have been marked as read.';
    } catch (Throwable $e) {
        error_log('Mark notifications as read error: ' . $e->getMessage());

        $error = 'Unable to update notifications. Please try again.';
    }
}

/*
|--------------------------------------------------------------------------
| LOAD USER NOTIFICATIONS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT *
     FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 100'
);

$stmt->execute([$u['id']]);

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/

page_header(
    'Notifications',
    'Messages about inquiries, assignments, and inventory'
);

?>

<div class="content-grid">

    <section class="panel span-12">

        <div class="panel-head">
            <div>
                <h2>Your notifications</h2>

                <p class="small">
                    View important updates related to your account and work.
                </p>
            </div>

            <form method="post">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <button type="submit" class="btn secondary">
                    Mark all as read
                </button>
            </form>
        </div>

        <!-- SUCCESS MESSAGE -->
        <?php if ($msg !== ''): ?>
            <div class="alert success">
                <?= e($msg) ?>
            </div>
        <?php endif; ?>

        <!-- ERROR MESSAGE -->
        <?php if ($error !== ''): ?>
            <div class="alert error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <!-- NOTIFICATION LIST -->
        <?php if (empty($notifications)): ?>

            <div class="empty">
                You have no notifications.
            </div>

        <?php else: ?>

            <?php foreach ($notifications as $notification): ?>

                <div class="notice">

                    <div class="panel-head">
                        <strong>
                            <?= e($notification['title']) ?>
                        </strong>

                        <?php if (empty($notification['is_read'])): ?>
                            <span class="badge orange">
                                New
                            </span>
                        <?php endif; ?>
                    </div>

                    <p>
                        <?= nl2br(e($notification['message'])) ?>
                    </p>

                    <div class="small">
                        <?= e($notification['created_at']) ?>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</div>

<?php page_footer(); ?>
