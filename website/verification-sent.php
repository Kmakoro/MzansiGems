<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
$email=(string)($_SESSION['verification_email']??'your email address');
$devLink=$_SESSION['dev_verification_link']??null;
unset($_SESSION['dev_verification_link']);
$pageTitle='Verify your email';$bodyClass='auth-body';require __DIR__.'/includes/header.php';
?>
<div class="auth-shell"><div class="auth-card"><div class="auth-icon">✉</div><h1>Check your inbox</h1><p class="muted">We sent a verification link to <?=e($email)?>. The link expires in 24 hours.</p>
<?php if($devLink):?><div class="content-card"><strong>Development verification link</strong><p class="form-help">This is displayed because APP_ENV is development. In production, the link is sent through the configured mail service.</p><a style="word-break:break-all;color:var(--orange-dark)" href="<?=e($devLink)?>"><?=e($devLink)?></a></div><?php endif;?>
<div class="action-row" style="justify-content:center"><a class="btn btn-primary" href="<?=url('login.php')?>">Back to Sign In</a><a class="btn btn-ghost" href="<?=url('resend-verification.php')?>">Resend Link</a></div></div></div>
<?php require __DIR__.'/includes/footer.php'; ?>
