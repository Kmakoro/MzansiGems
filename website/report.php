<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require_login();if($_SERVER['REQUEST_METHOD']!=='POST')redirect('index.php');verify_csrf();
$gemId=(int)($_POST['gem_id']??0);$reviewId=(int)($_POST['review_id']??0);$type=trim((string)($_POST['violation_type']??''));$details=trim((string)($_POST['details']??''));
if($type===''){flash('danger','Select a reason for the report.');redirect($gemId?'gem.php?id='.$gemId:'profile.php');}
$stmt=db()->prepare('INSERT INTO reports(reporter_id,gem_id,review_id,violation_type,details) VALUES(?,?,?,?,?)');$stmt->execute([(int)current_user()['id'],$gemId?:null,$reviewId?:null,mb_substr($type,0,100),mb_substr($details,0,400)]);record_activity((int)current_user()['id'],'content_reported',current_user()['full_name'].' reported content.');flash('success','Thank you. An administrator will review your report.');redirect($gemId?'gem.php?id='.$gemId:'profile.php');
