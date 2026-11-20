
<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

require_role(['Manager']);

$msg = '';

/*
|--------------------------------------------------------------------------
| Handle Form Submissions
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Update Personnel Permissions
    |--------------------------------------------------------------------------
    */

    if ($action === 'permission' && $id > 0) {

        $canRecordMaterials = isset($_POST['can_record_materials']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $pdo->prepare(
            "UPDATE users
             SET can_record_materials = ?,
                 is_active = ?
             WHERE id = ?
               AND role = 'Personnel'"
        );

        $stmt->execute([
            $canRecordMaterials,
            $isActive,
            $id
        ]);

        audit(
            $pdo,
            'Personnel permissions updated',
            'user',
            $id,
            'can_record_materials=' . $canRecordMaterials .
            '; active=' . $isActive
        );

        $msg = 'Personnel permissions updated.';

    /*
    |--------------------------------------------------------------------------
    | Create Team Account
    |--------------------------------------------------------------------------
    */

    } elseif ($action === 'create') {

        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role = $_POST['role'] ?? '';
        $pass = $_POST['password'] ?? '';

        $canRecordMaterials = (
            $role === 'Personnel' &&
            isset($_POST['can_record_materials'])
        ) ? 1 : 0;

        if (
            $first !== '' &&
            $last !== '' &&
            in_array($role, ['Personnel', 'Manager'], true) &&
            filter_var($email, FILTER_VALIDATE_EMAIL) &&
            strlen($pass) >= 8
        ) {

            try {

                $stmt = $pdo->prepare(
                    'INSERT INTO users (
                        first_name,
                        last_name,
                        email,
                        password_hash,
                        role,
                        can_record_materials
                    ) VALUES (?, ?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $first,
                    $last,
                    $email,
                    password_hash($pass, PASSWORD_DEFAULT),
                    $role,
                    $canRecordMaterials
                ]);

                $newUserId = (int) $pdo->lastInsertId();

                audit(
                    $pdo,
                    'User created',
                    'user',
                    $newUserId,
                    $role
                );

                $msg = 'Account created successfully.';

            } catch (PDOException $e) {

                // Avoid exposing database errors to users.
                if ($e->getCode() === '23000') {
                    $msg = 'Could not create account. The email may already exist.';
                } else {
                    error_log('Account creation failed: ' . $e->getMessage());
                    $msg = 'Could not create account. Please check your database settings.';
                }
            }

        } else {

            $msg = 'Check all fields, select a valid role, and use a password with at least 8 characters.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Fetch User Accounts
|--------------------------------------------------------------------------
*/

$users = $pdo->query(
    'SELECT
        id,
        first_name,
        last_name,
        email,
        role,
        is_active,
        can_record_materials,
        created_at
     FROM users
     ORDER BY role, last_name'
)->fetchAll();

/*
|--------------------------------------------------------------------------
| Page Header
|--------------------------------------------------------------------------
*/

page_header(
    'People & access',
    'Create accounts and configure individual personnel permissions'
);

?>

<div class="content-grid">

    <!-- Create Team Account -->

    <section class="panel span-4">

        <h2>Create team account</h2>

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
                value="create"
            >

            <input
                type="hidden"
                name="id"
                value="0"
            >

            <label for="first_name">
                First name
            </label>

            <input
                type="text"
                id="first_name"
                name="first_name"
                required
            >

            <label for="last_name">
                Last name
            </label>

            <input
                type="text"
                id="last_name"
                name="last_name"
                required
            >

            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                required
            >

            <label for="role">
                Role
            </label>

            <select id="role" name="role" required>

                <option value="Personnel">
                    Personnel
                </option>

                <option value="Manager">
                    Manager
                </option>

            </select>

            <label for="password">
                Temporary password (8+ characters)
            </label>

            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
            >

            <label>
                <input
                    type="checkbox"
                    name="can_record_materials"
                    value="1"
                    style="width: auto;"
                >

                Allow material recording (Personnel only)
            </label>

            <button
                type="submit"
                class="btn primary"
            >
                Create account
            </button>

        </form>

    </section>

    <!-- Accounts and Permissions -->

    <section class="panel span-8">

        <h2>Accounts and permissions</h2>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Access settings</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($users as $person): ?>

                        <tr>

                            <!-- User Information -->

                            <td>

                                <strong>
                                    <?= e(
                                        $person['first_name'] . ' ' .
                                        $person['last_name']
                                    ) ?>
                                </strong>

                                <br>

                                <?= e($person['email']) ?>

                                <br>

                                <?= status_badge(
                                    $person['is_active']
                                        ? 'active'
                                        : 'disabled'
                                ) ?>

                            </td>

                            <!-- User Role -->

                            <td>
                                <?= e($person['role']) ?>
                            </td>

                            <!-- Access Settings -->

                            <td>

                                <?php if ($person['role'] === 'Personnel'): ?>

                                    <form method="post">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrf_token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="permission"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $person['id'] ?>"
                                        >

                                        <label>

                                            <input
                                                type="checkbox"
                                                name="can_record_materials"
                                                value="1"
                                                style="width: auto;"
                                                <?= $person['can_record_materials']
                                                    ? 'checked'
                                                    : '' ?>
                                            >

                                            Can record material usage

                                        </label>

                                        <label>

                                            <input
                                                type="checkbox"
                                                name="is_active"
                                                value="1"
                                                style="width: auto;"
                                                <?= $person['is_active']
                                                    ? 'checked'
                                                    : '' ?>
                                            >

                                            Account active

                                        </label>

                                        <button
                                            type="submit"
                                            class="btn secondary"
                                        >
                                            Save permissions
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span class="small">
                                        Manager-controlled role
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

</div>

<?php

page_footer();

?>
