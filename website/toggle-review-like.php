<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require_login();
if($_SERVER['REQUEST_METHOD']!=='POST')redirect('discover.php');verify_csrf();
$reviewId=(int)($_POST['review_id']??0);$gemId=(int)($_POST['gem_id']??0);$userId=(int)current_user()['id'];
$stmt=db()->prepare("SELECT id FROM reviews WHERE id=? AND status='approved'");$stmt->execute([$reviewId]);if(!$stmt->fetch()){flash('danger','Review not found.');redirect('gem.php?id='.$gemId);}
$stmt=db()->prepare('SELECT id FROM review_likes WHERE user_id=? AND review_id=?');$stmt->execute([$userId,$reviewId]);$id=$stmt->fetchColumn();
if($id){$stmt=db()->prepare('DELETE FROM review_likes WHERE id=?');$stmt->execute([$id]);}else{$stmt=db()->prepare('INSERT INTO review_likes(user_id,review_id) VALUES(?,?)');$stmt->execute([$userId,$reviewId]);}
redirect('gem.php?id='.$gemId.'#reviews');
