
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

$u = require_role(['Client']);
$id = (int) $u['id'];

/*
|--------------------------------------------------------------------------
| Count Client Inquiries
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM inquiries
     WHERE client_id = ?'
);

$stmt->execute([$id]);
$inquiries = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Count Client Projects
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM projects
     WHERE client_id = ?'
);

$stmt->execute([$id]);
$projects = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Count Quotations Awaiting Client Response
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM quotations
     WHERE client_id = ?
       AND status = 'sent'"
);

$stmt->execute([$id]);
$quotations = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Count Unread Notifications
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM notifications
     WHERE user_id = ?
       AND is_read = 0'
);

$stmt->execute([$id]);
$notifications = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Fetch Recent Client Inquiries
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT *
     FROM inquiries
     WHERE client_id = ?
     ORDER BY created_at DESC
     LIMIT 5'
);

$stmt->execute([$id]);
$rows = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Page Header
|--------------------------------------------------------------------------
*/

page_header(
    'Client overview',
    'Your inquiries, quotations, and project updates'
);

?>

<!-- Client Dashboard Statistics -->

<div class="stat-grid">

    <?php
    stat_card(
        'My inquiries',
        $inquiries,
        'Requests submitted'
    );

    stat_card(
        'Projects',
        $projects,
        'Projects on file'
    );

    stat_card(
        'Quotations to review',
        $quotations,
        'Awaiting your response'
    );

    stat_card(
        'Unread notifications',
        $notifications,
        'Updates for you'
    );
    ?>

</div>

<!-- Dashboard Content -->

<div class="content-grid">

    <!-- Recent Inquiries -->

    <section class="panel span-8">

        <div class="panel-head">

            <div>
                <h2>Recent inquiries</h2>

                <div class="small">
                    Track each request from review to quotation.
                </div>
            </div>

            <a
                class="btn primary"
                href="new_inquiry.php"
            >
                + New inquiry
            </a>

        </div>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Project Type</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($rows as $r): ?>

                        <tr>

                            <!-- Project Type -->

                            <td>
                                <?= e($r['project_type']) ?>
                            </td>

                            <!-- Project Location -->

                            <td>
                                <?= e($r['location']) ?>
                            </td>

                            <!-- Inquiry Status -->

                            <td>
                                <?= status_badge($r['status']) ?>
                            </td>

                            <!-- Submission Date -->

                            <td>
                                <?= e(
                                    date(
                                        'M j, Y',
                                        strtotime($r['created_at'])
                                    )
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

            <?php if (empty($rows)): ?>

                <div class="empty">
                    No inquiries yet. Start with a new inquiry.
                </div>

            <?php endif; ?>

        </div>

    </section>

    <!-- Quick Actions -->

    <section class="panel span-4">

        <h2>Quick actions</h2>

        <a
            class="quick-link"
            href="new_inquiry.php"
        >
            Submit project inquiry
            <span>↗</span>
        </a>

        <a
            class="quick-link"
            href="my_inquiries.php"
        >
            View quotations
            <span>↗</span>
        </a>

        <a
            class="quick-link"
            href="my_projects.php"
        >
            Project progress
            <span>↗</span>
        </a>

        <a
            class="quick-link"
            href="ai_assistant.php"
        >
            Ask AI assistant
            <span>↗</span>
        </a>

    </section>

</div>

<?php

page_footer();

?>
