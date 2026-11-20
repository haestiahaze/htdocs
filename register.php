
<?php

require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| REDIRECT ALREADY LOGGED-IN USERS
|--------------------------------------------------------------------------
*/
if (current_user()) {
    redirect_dashboard(current_user()['role']);
}

/*
|--------------------------------------------------------------------------
| INITIALIZE VARIABLES
|--------------------------------------------------------------------------
*/
$error = '';

$first = '';
$last  = '';
$email = '';

/*
|--------------------------------------------------------------------------
| HANDLE REGISTRATION
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    /*
    |--------------------------------------------------------------------------
    | GET FORM INPUTS
    |--------------------------------------------------------------------------
    */
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | VALIDATE INPUTS
    |--------------------------------------------------------------------------
    */
    if (
        $first === '' ||
        $last === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $error = 'Enter your first name, last name, and a valid email.';

    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';

    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | CREATE CLIENT ACCOUNT
        |--------------------------------------------------------------------------
        */
        try {

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | INSERT USER
            |--------------------------------------------------------------------------
            */
            $stmt = $pdo->prepare(
                "INSERT INTO users (
                    first_name,
                    last_name,
                    email,
                    password_hash,
                    role
                ) VALUES (?, ?, ?, ?, 'Client')"
            );

            $stmt->execute([
                $first,
                $last,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $userId = (int) $pdo->lastInsertId();

            /*
            |--------------------------------------------------------------------------
            | CREATE CUSTOMER PROFILE
            |--------------------------------------------------------------------------
            */
            $stmt = $pdo->prepare(
                'INSERT INTO customers (user_id)
                 VALUES (?)'
            );

            $stmt->execute([$userId]);

            /*
            |--------------------------------------------------------------------------
            | COMMIT DATABASE TRANSACTION
            |--------------------------------------------------------------------------
            */
            $pdo->commit();

            /*
            |--------------------------------------------------------------------------
            | CREATE USER SESSION
            |--------------------------------------------------------------------------
            */
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'                  => $userId,
                'first_name'          => $first,
                'last_name'           => $last,
                'email'               => $email,
                'role'                => 'Client',
                'can_record_materials' => 0,
                'is_active'           => 1
            ];

            /*
            |--------------------------------------------------------------------------
            | REDIRECT TO CLIENT DASHBOARD
            |--------------------------------------------------------------------------
            */
            redirect_dashboard('Client');

        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | ROLLBACK IF REGISTRATION FAILS
            |--------------------------------------------------------------------------
            */
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            /*
            |--------------------------------------------------------------------------
            | DISPLAY APPROPRIATE ERROR
            |--------------------------------------------------------------------------
            */
            if ($e->getCode() === '23000') {
                $error = 'That email is already registered. Please sign in instead.';
            } else {
                error_log(
                    'Client registration failed: ' . $e->getMessage()
                );

                $error = 'Registration failed. Please check your database setup.';
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Account | GlassAluminum</title>

    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-page">

    <main class="auth-card">

        <!-- BRAND -->
        <div class="brand-mark">
            GA
        </div>

        <p class="eyebrow">
            CLIENT PORTAL
        </p>

        <h1>Create your account</h1>

        <p class="muted">
            Submit inquiries and follow your project in one place.
        </p>

        <!-- ERROR MESSAGE -->
        <?php if ($error !== ''): ?>

            <div class="alert error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <!-- REGISTRATION FORM -->
        <form method="post" action="">

            <!-- CSRF TOKEN -->
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <!-- FIRST AND LAST NAME -->
            <div class="two-col">

                <div>
                    <label for="first_name">
                        First Name
                    </label>

                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="<?= e($first) ?>"
                        autocomplete="given-name"
                        maxlength="100"
                        placeholder="Emily"
                        required
                    >
                </div>

                <div>
                    <label for="last_name">
                        Last Name
                    </label>

                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="<?= e($last) ?>"
                        autocomplete="family-name"
                        maxlength="100"
                        placeholder="Reyes"
                        required
                    >
                </div>

            </div>

            <!-- EMAIL -->
            <label for="email">
                Email Address
            </label>

            <input
                type="email"
                id="email"
                name="email"
                value="<?= e($email) ?>"
                autocomplete="email"
                maxlength="254"
                placeholder="email@gmail.com"
                required
            >

            <!-- PASSWORD -->
            <label for="password">
                Password (8+ characters)
            </label>
            <div class="password-row">

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                     minlength="8"
                    autocomplete="New-password"
                    placeholder="New password"
                >

                <button
                    class="eye"
                    type="button"
                    onclick="togglePassword('password', this)"
                    aria-label="Show password"
                >
                    Show
                </button>

            </div>
            <!-- CONFIRM PASSWORD -->
            <label for="confirm_password">Confirm Password</label>
<div class="password-row">
    <input
        type="password"
        id="confirm_password"
        name="confirm_password"
        required
        minlength="8"
        autocomplete="new-password"
        placeholder="Confirm password"
    >

    <button
        class="eye"
        type="button"
        onclick="togglePassword('confirm_password', this)"
        aria-label="Show password"
    >
        Show
    </button>
</div>
            <!-- SUBMIT -->
            <button
                class="btn primary full"
                type="submit"
            >
                Create Client Account
            </button>

        </form>

        <!-- LOGIN LINK -->
        <p class="auth-foot">
            Already registered?
            <a href="login.php">Sign in</a>
        </p>

    </main>
<script src="app.js"></script>
</body>

</html>