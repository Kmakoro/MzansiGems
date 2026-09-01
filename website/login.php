<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
if (is_logged_in()) redirect('profile.php');
$errors = [];
$needsVerification = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    remember_old_input($_POST);
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($password === '') $errors[] = 'Enter your password.';
    if (!$errors) {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1'); $stmt->execute([$email]); $user = $stmt->fetch();
        $legacyDemoEmail = legacy_demo_email_for($email);
        if (!$user && $legacyDemoEmail !== null) {
            $stmt->execute([$legacyDemoEmail]);
            $user = $stmt->fetch();
        }
        if (!$user || !password_verify($password, $user['password_hash'])) $errors[] = 'The email address or password is incorrect.';
        elseif ($user['status'] !== 'active') $errors[] = 'This account is suspended. Contact an administrator.';
        elseif (setting('require_email_verification', '0') === '1' && empty($user['email_verified_at'])) {
            $errors[] = 'Verify your email address before signing in.';
            $needsVerification = true;
            $_SESSION['verification_email'] = $email;
        }
        else {
            if ($legacyDemoEmail !== null && $user['email'] === $legacyDemoEmail) {
                $upgrade = db()->prepare('UPDATE IGNORE users SET email = ? WHERE id = ?');
                $upgrade->execute([$email, (int)$user['id']]);
                if ($upgrade->rowCount() === 1) $user['email'] = $email;
            }
            login_user($user, isset($_POST['remember'])); clear_old_input();
            $target = $_SESSION['intended_url'] ?? url('profile.php'); unset($_SESSION['intended_url']);
            header('Location: ' . $target); exit;
        }
    }
}
$pageTitle = 'Welcome back'; $bodyClass = 'auth-body'; require __DIR__ . '/includes/header.php';
?>
<div class="auth-shell"><div class="auth-card">
    <div class="auth-icon">♙</div><h1>Welcome Back!</h1><p class="muted">Sign in to discover places with Mzansi Gem</p>
    <?php if ($errors): ?><div class="toast toast-danger" style="position:static;margin-bottom:14px"><span><?= e(implode(' ', $errors)) ?></span></div><?php endif; ?>
    <?php if ($needsVerification): ?><a class="btn btn-ghost btn-block" style="margin-bottom:14px" href="<?= url('resend-verification.php') ?>">Send a new verification link</a><?php endif; ?>
    <form method="post"><?= csrf_field() ?>
        <div class="form-group"><label for="email">Email Address</label><input class="form-control" id="email" name="email" type="email" value="<?= old('email') ?>" placeholder="you@example.com" required></div>
        <div class="form-group"><label for="password">Password</label><input class="form-control" id="password" name="password" type="password" placeholder="Enter your password" required></div>
        <div class="checkbox-row"><label><input type="checkbox" name="remember" value="1"> Remember me</label><a href="<?= url('forgot-password.php') ?>" style="color:var(--orange-dark);font-weight:700">Forgot password?</a></div>
        <button class="btn btn-primary btn-block" style="margin-top:16px" type="submit">Sign In</button>
    </form>
    <div class="auth-separator">or</div><a class="google-btn" href="<?= url('google-auth.php') ?>">◉ Continue with Google</a>
    <p class="auth-foot">Don't have an account? <a href="<?= url('register.php') ?>">Sign up</a></p>
    <p class="form-help" style="text-align:center">Demo: user@mzansigem.local / User@123</p>
</div></div>
<?php clear_old_input(); require __DIR__ . '/includes/footer.php'; ?>
