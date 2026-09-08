<?php
require_once 'config.php';
$page_title = 'Sign In';
$base = '';

if (is_customer_logged_in()) {
    header('Location: account.php');
    exit;
}

$redirectParam = $_GET['redirect'] ?? 'account.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, password_hash FROM customers WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();

    if ($customer && !empty($customer['password_hash']) && password_verify($password, $customer['password_hash'])) {
        $_SESSION['customer_id'] = $customer['id'];
        header('Location: ' . ($_POST['redirect'] ?? 'account.php'));
        exit;
    } elseif ($customer && empty($customer['password_hash'])) {
        $error = 'This account was created with Google or Facebook. Use that button below to sign in.';
    } else {
        $error = 'Incorrect email or password.';
    }
}

$googleConfigured = get_setting('google_client_id', '') !== '';
$facebookConfigured = get_setting('facebook_app_id', '') !== '';
?>
<?php include 'includes/header.php'; ?>

<div class="login-wrap">
    <div class="login-card">
        <img class="login-logo-img" src="<?= h(get_setting('logo_white_path', 'assets/images/logo-mark-white.png')) ?>" alt="Logo">
        <div class="login-eyebrow">WELCOME BACK</div>
        <h2>SIGN IN</h2>

        <?php if ($error): ?>
            <div class="login-error"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($googleConfigured || $facebookConfigured): ?>
        <div class="social-login-row">
            <?php if ($googleConfigured): ?>
            <a href="oauth-google.php?redirect=<?= urlencode($redirectParam) ?>" class="btn-social btn-google">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.66-.22-2.45H12v4.64h6.47a5.54 5.54 0 0 1-2.4 3.63v3h3.87c2.27-2.09 3.58-5.17 3.58-8.82z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.94-2.91l-3.87-3c-1.08.72-2.45 1.15-4.07 1.15-3.13 0-5.78-2.11-6.73-4.96H1.28v3.11A12 12 0 0 0 12 24z"/><path fill="#FBBC05" d="M5.27 14.28A7.2 7.2 0 0 1 4.89 12c0-.79.14-1.56.38-2.28V6.61H1.28A12 12 0 0 0 0 12c0 1.94.46 3.77 1.28 5.39l3.99-3.11z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.69 1.28 6.61l3.99 3.11C6.22 6.86 8.87 4.75 12 4.75z"/></svg>
                Continue with Google
            </a>
            <?php endif; ?>
            <?php if ($facebookConfigured): ?>
            <a href="oauth-facebook.php?redirect=<?= urlencode($redirectParam) ?>" class="btn-social btn-facebook">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="#fff"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.5 1.49-3.89 3.78-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.44 2.91h-2.34V22c4.78-.79 8.44-4.94 8.44-9.94z"/></svg>
                Continue with Facebook
            </a>
            <?php endif; ?>
        </div>
        <div class="social-divider"><span>or sign in with email</span></div>
        <?php endif; ?>

        <form method="POST" style="text-align:left;">
            <input type="hidden" name="redirect" value="<?= h($redirectParam) ?>">
            <div class="form-field">
                <label>Email</label>
                <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">SIGN IN</button>
        </form>
        <p style="color:var(--text-muted);font-size:12px;margin-top:16px;text-transform:none;">
            New here? <a href="account-register.php" style="color:var(--blue);">Create an account</a>
        </p>
        <p style="color:var(--text-muted);font-size:12px;margin-top:8px;text-transform:none;">
            Booked as a guest? <a href="my-bookings.php" style="color:var(--blue);">Look up your booking by email</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
