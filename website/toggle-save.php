<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php'; require_login();
if($_SERVER['REQUEST_METHOD']!=='POST') redirect('discover.php'); verify_csrf();
$gemId=(int)($_POST['gem_id']??0); $userId=(int)current_user()['id'];
$stmt=db()->prepare("SELECT id FROM gems WHERE id=? AND status='approved'");$stmt->execute([$gemId]);if(!$stmt->fetch()) {flash('danger','Gem not found.');redirect(safe_return_path());}
$stmt=db()->prepare('SELECT id FROM saved_gems WHERE user_id=? AND gem_id=?');$stmt->execute([$userId,$gemId]);$saved=$stmt->fetchColumn();
if($saved){$stmt=db()->prepare('DELETE FROM saved_gems WHERE id=?');$stmt->execute([$saved]);flash('success','Removed from favourites.');}
else{$stmt=db()->prepare('INSERT INTO saved_gems (user_id,gem_id) VALUES (?,?)');$stmt->execute([$userId,$gemId]);award_points($userId,5);record_activity($userId,'gem_saved',current_user()['full_name'].' saved a gem.');flash('success','Saved to your favourites.');}
redirect(safe_return_path());
