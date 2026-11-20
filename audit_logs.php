
<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

// Only managers can access the audit log.
$user = require_role(['Manager']);

// Retrieve the 300 most recent audit log entries.
$sql = '
    SELECT
        a.*,
        u.first_name,
        u.last_name
    FROM audit_logs AS a
    LEFT JOIN users AS u
        ON u.id = a.user_id
    ORDER BY a.created_at DESC
    LIMIT 300
';

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display the page header.
page_header(
    'Audit log',
    'Recent tracked changes for accountability'
);

?>

<div class="content-grid">

    <section class="panel span-12">

        <div class="table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Details</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($rows)): ?>

                        <tr>
                            <td colspan="5">
                                No audit log records found.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($rows as $r): ?>

                            <tr>
                                <td>
                                    <?= e($r['created_at'] ?? '') ?>
                                </td>

                                <td>
                                    <?php
                                    $firstName = $r['first_name'] ?? '';
                                    $lastName = $r['last_name'] ?? '';

                                    $displayName = trim(
                                        $firstName . ' ' . $lastName
                                    );

                                    echo e(
                                        $displayName !== ''
                                            ? $displayName
                                            : 'System'
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?= e($r['action'] ?? '') ?>
                                </td>

                                <td>
                                    <?= e(
                                        ($r['entity_type'] ?? 'Unknown')
                                        . ' #'
                                        . ($r['entity_id'] ?? 'N/A')
                                    ) ?>
                                </td>

                                <td>
                                    <?= e($r['details'] ?? '') ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>
            </table>

        </div>

    </section>

</div>

<?php

page_footer();
?>
