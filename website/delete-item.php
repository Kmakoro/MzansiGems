<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require_login();if($_SERVER['REQUEST_METHOD']!=='POST')redirect('profile.php');verify_csrf();
$type=(string)($_POST['type']??'');$id=(int)($_POST['id']??0);$userId=(int)current_user()['id'];
if($type==='gem'){$stmt=db()->prepare('SELECT user_id,title FROM gems WHERE id=?');$stmt->execute([$id]);$row=$stmt->fetch();if($row&&((int)$row['user_id']===$userId||is_admin())){$stmt=db()->prepare('DELETE FROM gems WHERE id=?');$stmt->execute([$id]);record_activity($userId,'gem_deleted',current_user()['full_name'].' deleted '.$row['title'].'.');flash('success','Gem deleted.');}else flash('danger','You cannot delete that gem.');}
elseif($type==='review'){$stmt=db()->prepare('SELECT user_id,gem_id FROM reviews WHERE id=?');$stmt->execute([$id]);$row=$stmt->fetch();if($row&&((int)$row['user_id']===$userId||is_admin())){$stmt=db()->prepare('DELETE FROM reviews WHERE id=?');$stmt->execute([$id]);recalculate_gem_rating((int)$row['gem_id']);flash('success','Review deleted.');}else flash('danger','You cannot delete that review.');}
redirect(is_admin()&&($_POST['admin_return']??'')==='1'?'admin/moderation.php':'profile.php?tab='.($type==='review'?'reviews':'posted'));
