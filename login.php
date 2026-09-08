<?php
require_once 'config.php';
$page_title = 'Admin Login';
$base = '';

if (is_admin_logged_in()) {
    header('Location: admin/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $username;
        header('Location: admin/dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="login-wrap">
    <div class="login-card">
        <img class="login-logo-img" src="<?= h(get_setting('logo_white_path', 'assets/images/logo-mark-white.png')) ?>" alt="Logo">
        <div class="login-eyebrow">ADMIN ACCESS</div>
        <h2>SIGN IN</h2>

        <?php if ($error): ?>
            <div class="login-error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" style="text-align:left;">
            <div class="form-field">
                <label>Username</label>
                <input type="text" name="username" placeholder="admin" required>
            </div>
            <div class="form-field">
                <label>Password</label>
                <input type="password" name="password" placeholder="********" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">SIGN IN</button>
        </form>
        <p style="color:var(--text-muted);font-size:12px;margin-top:16px;text-transform:none;">
            Default demo login: <b>admin</b> / <b>admin123</b>
        </p>
        <a href="index.php" style="display:inline-block;margin-top:10px;color:var(--text-muted);font-size:12px;">CANCEL</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
