<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
$token=(string)($_GET['token']??'');
if($token===''){flash('danger','The verification link is missing its token.');redirect('login.php');}
$stmt=db()->prepare('SELECT * FROM email_verification_tokens WHERE token_hash=? AND expires_at>NOW() ORDER BY id DESC LIMIT 1');$stmt->execute([hash('sha256',$token)]);$record=$stmt->fetch();
if(!$record){flash('danger','This verification link is invalid or expired.');redirect('login.php');}
db()->beginTransaction();$stmt=db()->prepare('UPDATE users SET email_verified_at=NOW() WHERE id=?');$stmt->execute([(int)$record['user_id']]);$stmt=db()->prepare('DELETE FROM email_verification_tokens WHERE user_id=?');$stmt->execute([(int)$record['user_id']]);db()->commit();unset($_SESSION['verification_email']);flash('success','Email verified successfully. You can now sign in.');redirect('login.php');
