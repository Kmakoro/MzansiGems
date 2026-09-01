<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
$email=(string)($_SESSION['verification_email']??'');
if($email===''){flash('warning','Enter your email and password first so the account can be identified.');redirect('login.php');}
$stmt=db()->prepare('SELECT id,email_verified_at FROM users WHERE email=? LIMIT 1');$stmt->execute([$email]);$user=$stmt->fetch();
if(!$user){flash('success','If the account exists, a verification link has been generated.');redirect('login.php');}
if(!empty($user['email_verified_at'])){flash('success','This email address is already verified.');redirect('login.php');}
$token=create_email_verification_token((int)$user['id']);$link=url('verify-email.php?token='.urlencode($token));
if(APP_ENV==='development')$_SESSION['dev_verification_link']=$link;
send_app_email($email,'Verify your Mzansi Gem email',"Verify your email address using this link:\n{$link}\n\nThis link expires in 24 hours.");
flash('success','A new verification link has been generated.');redirect('verification-sent.php');
