<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$devLink = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1'); $stmt->execute([$email]); $id = $stmt->fetchColumn();
        if ($id) {
            $token = bin2hex(random_bytes(32));
            $stmt = db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
            $stmt->execute([(int)$id, hash('sha256', $token)]);
            $resetLink = url('reset-password.php?token=' . urlencode($token));
            send_app_email($email, 'Reset your Mzansi Gem password', "A password reset was requested for your Mzansi Gem account.

Reset your password using this link:
{$resetLink}

This link expires in one hour.");
            if (APP_ENV === 'development') {
                $devLink = $resetLink;
            }
        }
    }
    flash('success', 'If the account exists, a password reset link has been generated.');
}
$pageTitle='Forgot password'; $bodyClass='auth-body'; require __DIR__.'/includes/header.php';
?>
<div class="auth-shell"><div class="auth-card"><div class="auth-icon">?</div><h1>Reset your password</h1><p class="muted">Enter the email linked to your account.</p>
<form method="post"><?= csrf_field() ?><div class="form-group"><label>Email Address</label><input class="form-control" name="email" type="email" required></div><button class="btn btn-primary btn-block">Generate Reset Link</button></form>
<?php if ($devLink): ?><div class="content-card" style="margin-top:18px"><strong>Development reset link</strong><p class="form-help">In production, email this link through your mail provider.</p><a style="word-break:break-all;color:var(--orange-dark)" href="<?= e($devLink) ?>"><?= e($devLink) ?></a></div><?php endif; ?>
<p class="auth-foot"><a href="<?= url('login.php') ?>">Back to sign in</a></p></div></div>
<?php require __DIR__.'/includes/footer.php'; ?>
