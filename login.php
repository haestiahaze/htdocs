
<?php

require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Redirect Already Logged-In Users
|--------------------------------------------------------------------------
*/

$user = current_user();

if ($user) {
    redirect_dashboard($user['role']);
}

$error = '';

/*
|--------------------------------------------------------------------------
| Handle Login Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Find User Account
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        'SELECT
            id,
            first_name,
            last_name,
            email,
            password_hash,
            role,
            can_record_materials,
            is_active
         FROM users
         WHERE email = ?
         LIMIT 1'
    );

    $stmt->execute([$email]);

    $u = $stmt->fetch();

    /*
    |--------------------------------------------------------------------------
    | Verify Credentials
    |--------------------------------------------------------------------------
    */

    if (
        $u &&
        $u['is_active'] &&
        password_verify($password, $u['password_hash'])
    ) {

        // Generate a new session ID after successful authentication.
        session_regenerate_id(true);

        // Never store the password hash in the session.
        unset($u['password_hash']);

        // Store authenticated user information.
        $_SESSION['user'] = $u;

        // Redirect according to the user's role.
        redirect_dashboard($u['role']);

        exit;

    } else {

        $error = 'Invalid email or password, or your account is disabled.';
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

    <title>Sign In | ForgeFlow</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body class="auth-page">

    <main class="auth-card">

        <!-- Brand Logo -->

        <div class="brand-mark">
            GA
        </div>

        <p class="eyebrow">
           GLASS<span>ALUMINUM</span>
        </p>

        <h1>Welcome back</h1>

        <p class="muted">
            Sign in to manage projects, materials, and updates.
        </p>

        <!-- Error Message -->

        <?php if ($error !== ''): ?>

            <div class="alert error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <!-- Login Form -->

        <form method="post">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <label for="email">
                Email address
            </label>

            <input
                type="email"
                id="email"
                name="email"
                required
                autocomplete="username"
                placeholder="you@company.com"
                value="<?= e($_POST['email'] ?? '') ?>"
            >

            <label for="password">
                Password
            </label>

            <div class="password-row">

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter password"
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

            <button
                class="btn primary full"
                type="submit"
            >
                Sign in
            </button>

        </form>

        <!-- Registration Link -->

        <p class="auth-foot">
            New client?
            <a href="register.php">
                Create an account
            </a>
        </p>

        <!-- Demo Account Information -->

        <div class="demo-box">

            <strong>Demo accounts</strong>

            <br>

            manager@example.com
            · personnel@example.com
            · client@example.com

            <br>

            Password:
            <code>Demo123!</code>

        </div>

    </main>

    <!-- JavaScript -->

    <script src="app.js"></script>

</body>

</html>
