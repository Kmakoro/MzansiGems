<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$email = (string)($_SESSION['verification_email'] ?? '');
if ($email === '') {
    flash('warning', 'Please sign in first so we can identify the email address.');
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!filter_var(MAIL_FROM, FILTER_VALIDATE_EMAIL)) {
        flash('warning', 'Verification emails are temporarily unavailable. Contact support.');
    } elseif (time() - (int)($_SESSION['last_verification_request'] ?? 0) < 60) {
        flash('warning', 'Please wait a minute before asking for another verification link.');
    } else {
        $stmt = db()->prepare('SELECT id,email_verified_at FROM users WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !empty($user['email_verified_at'])) {
            flash('success', 'If verification is required, check your inbox for a message.');
        } else {
            $token = create_email_verification_token((int)$user['id']);
            $link = url('verify-email.php?token=' . urlencode($token));
            if (APP_ENV === 'development') $_SESSION['dev_verification_link'] = $link;
            $sent = send_app_email(
                $email,
                'Verify your Mzansi Gem email',
                "Verify your Mzansi Gem email address using this link:\n{$link}\n\nThis link expires in 24 hours."
            );
            $_SESSION['last_verification_request'] = time();
            if (!$sent) {
                error_log('MzansiGems verification resend delivery failed.');
                flash('warning', 'We could not dispatch the verification email. Please contact support.');
            } else {
                flash('success', 'Verification email requested. Check your inbox and spam folder.');
            }
        }
    }
    redirect('verification-sent.php');
}

$pageTitle = 'Resend verification';
$bodyClass = 'auth-body';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-shell"><div class="auth-card">
<h1>Resend verification link</h1>
<p class="muted">Request a new verification link for <?=e($email)?>.</p>
<form method="post"><?=csrf_field()?>
<button type="submit" class="btn btn-primary btn-block">Send verification email</button>
</form>
<p class="auth-foot"><a href="<?=url('login.php')?>">Back to sign in</a></p>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
