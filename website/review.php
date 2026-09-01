<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require_login();
$gemId=(int)($_GET['gem_id']??$_POST['gem_id']??0);$stmt=db()->prepare("SELECT * FROM gems WHERE id=? AND status='approved'");$stmt->execute([$gemId]);$gem=$stmt->fetch();if(!$gem){http_response_code(404);exit('Gem not found.');}
$existingStmt=db()->prepare('SELECT id FROM reviews WHERE user_id=? AND gem_id=?');$existingStmt->execute([(int)current_user()['id'],$gemId]);$existingId=$existingStmt->fetchColumn();$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$rating=(int)($_POST['rating']??0);$comment=trim((string)($_POST['comment']??''));$vibes=$_POST['vibes']??[];
 if($existingId)$errors[]='You have already reviewed this gem. You can edit your existing review instead.';
 if($rating<1||$rating>5)$errors[]='Please select a rating between 1 and 5 stars.';
 if(mb_strlen($comment)>300)$errors[]='Your review must be 300 characters or fewer.';
 if(!is_array($vibes))$vibes=[];
 if(!$errors){$status=(setting('enable_moderation','1')==='1'&&setting('auto_approve_content','0')!=='1')?'pending':'approved';$stmt=db()->prepare('INSERT INTO reviews(gem_id,user_id,rating,comment,vibes,status) VALUES(?,?,?,?,?,?)');$stmt->execute([$gemId,(int)current_user()['id'],$rating,$comment?:null,implode(',',array_slice(array_map('trim',$vibes),0,4)),$status]);award_points((int)current_user()['id'],25);record_activity((int)current_user()['id'],'review_posted',current_user()['full_name'].' reviewed '.$gem['title'].'.');if($status==='approved')recalculate_gem_rating($gemId);flash('success',$status==='pending'?'Thank you for your review. It will appear once approved by an administrator.':'Your review is now live.');redirect('gem.php?id='.$gemId.'#reviews');}
}
$pageTitle='Review '.$gem['title'];require __DIR__.'/includes/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Share your experience</span><h1>Review for <?=e($gem['title'])?></h1><p class="muted">Help the community know what makes this place special.</p></div></section>
<section class="section-tight"><div class="container" style="max-width:760px">
<?php if($errors):?><div class="toast toast-danger" style="position:static;margin-bottom:16px"><span><?=e(implode(' ',$errors))?></span></div><?php endif;?>
<div class="content-card"><form method="post"><?=csrf_field()?><input type="hidden" name="gem_id" value="<?=$gemId?>">
<div class="form-group"><label>Your Name</label><input class="form-control" value="<?=e(current_user()['full_name'])?>" disabled></div>
<div class="form-group"><label>Rating *</label><div class="star-input"><?php for($i=5;$i>=1;$i--):?><input id="star<?=$i?>" name="rating" type="radio" value="<?=$i?>" <?=((int)($_POST['rating']??0)===$i)?'checked':''?>><label for="star<?=$i?>">★</label><?php endfor;?></div></div>
<div class="form-group"><label for="comment">Write your review</label><textarea class="form-control" id="comment" name="comment" maxlength="300" data-char-counter="#review-count" placeholder="What did you love? Any tips for others?"><?=e($_POST['comment']??'')?></textarea><div class="char-counter" id="review-count">0/300</div></div>
<div class="form-group"><label>Vibe & Activity (optional)</label><div class="choice-row"><?php foreach(['Romantic','Chill','Food','Date Night'] as $v):?><label class="choice-pill"><input type="checkbox" name="vibes[]" value="<?=e($v)?>" <?=in_array($v,$_POST['vibes']??[],true)?'checked':''?>><span><?=e($v)?></span></label><?php endforeach;?></div></div>
<div class="action-row"><a class="btn btn-ghost" href="<?=url('gem.php?id='.$gemId)?>">Cancel</a><?php if($existingId):?><a class="btn btn-primary" href="<?=url('edit-review.php?id='.(int)$existingId)?>">Edit Existing Review</a><?php else:?><button class="btn btn-primary" type="submit">Post Review</button><?php endif;?></div>
</form></div></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
