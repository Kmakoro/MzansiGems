<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php'; require_login();
if($_SERVER['REQUEST_METHOD']!=='POST') redirect('discover.php'); verify_csrf();
$gemId=(int)($_POST['gem_id']??0);$userId=(int)current_user()['id'];
$stmt=db()->prepare('SELECT id FROM gem_likes WHERE user_id=? AND gem_id=?');$stmt->execute([$userId,$gemId]);$id=$stmt->fetchColumn();
if($id){$stmt=db()->prepare('DELETE FROM gem_likes WHERE id=?');$stmt->execute([$id]);flash('success','Like removed.');}else{$stmt=db()->prepare('INSERT IGNORE INTO gem_likes(user_id,gem_id) VALUES(?,?)');$stmt->execute([$userId,$gemId]);flash('success','You liked this gem.');}
redirect(safe_return_path('gem.php?id='.$gemId));
