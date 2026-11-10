<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';require_admin();$tab=(string)($_GET['tab']??$_POST['tab']??'gems');if(!in_array($tab,['gems','reviews','reports'],true))$tab='gems';
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$action=(string)($_POST['action']??'');$id=(int)($_POST['id']??0);
 if(in_array($action,['approve_gem','reject_gem'],true)){$status=$action==='approve_gem'?'approved':'rejected';$reason=trim((string)($_POST['reason']??''));$stmt=db()->prepare('UPDATE gems SET status=?,rejection_reason=?,approved_at=? WHERE id=?');$stmt->execute([$status,$status==='rejected'?($reason?:'Submission did not meet platform guidelines.'):null,$status==='approved'?date('Y-m-d H:i:s'):null,$id]);record_activity((int)current_user()['id'],'content_'.$status,current_user()['full_name'].' '.$status.' gem #'.$id.'.');flash('success','Gem '.$status.'.');}
 elseif(in_array($action,['approve_review','reject_review'],true)){$status=$action==='approve_review'?'approved':'rejected';$stmt=db()->prepare('SELECT gem_id FROM reviews WHERE id=?');$stmt->execute([$id]);$gemId=(int)$stmt->fetchColumn();$stmt=db()->prepare('UPDATE reviews SET status=? WHERE id=?');$stmt->execute([$status,$id]);if($gemId)recalculate_gem_rating($gemId);flash('success','Review '.$status.'.');}
 elseif(in_array($action,['resolve_report','dismiss_report'],true)){$status=$action==='resolve_report'?'resolved':'dismissed';$note=trim((string)($_POST['resolution_note']??''));$stmt=db()->prepare('UPDATE reports SET status=?,resolution_note=?,resolved_at=NOW() WHERE id=?');$stmt->execute([$status,$note?:null,$id]);flash('success','Report '.$status.'.');}
 redirect('admin/moderation.php?tab='.$tab);
}
$pendingGems=(int)db()->query("SELECT COUNT(*) FROM gems WHERE status='pending'")->fetchColumn();$pendingReviews=(int)db()->query("SELECT COUNT(*) FROM reviews WHERE status='pending'")->fetchColumn();$pendingReports=(int)db()->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();$avgApproval=(float)(db()->query("SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE,created_at,approved_at))/60,0) FROM gems WHERE approved_at IS NOT NULL")->fetchColumn());
if($tab==='gems'){$items=db()->query("SELECT g.*,u.full_name FROM gems g JOIN users u ON u.id=g.user_id WHERE g.status='pending' ORDER BY g.created_at")->fetchAll();}
elseif($tab==='reviews'){$items=db()->query("SELECT r.*,u.full_name,g.title FROM reviews r JOIN users u ON u.id=r.user_id JOIN gems g ON g.id=r.gem_id WHERE r.status='pending' ORDER BY r.created_at")->fetchAll();}
else{$items=db()->query("SELECT r.*,u.full_name AS reporter,g.title,g.id AS content_gem_id,rv.comment AS review_comment FROM reports r JOIN users u ON u.id=r.reporter_id LEFT JOIN reviews rv ON rv.id=r.review_id LEFT JOIN gems g ON g.id=COALESCE(r.gem_id,rv.gem_id) WHERE r.status='pending' ORDER BY r.created_at")->fetchAll();}
$pageTitle='Content Moderation';$activeAdmin='moderation';require dirname(__DIR__).'/includes/admin-header.php';
?>
<div class="admin-page-head"><div><span class="eyebrow">Quality control</span><h1>Content Moderation</h1><p class="muted">Review pending submissions and community reports.</p></div></div>
<div class="metric-grid"><div class="metric-card"><span>Pending Gems</span><strong><?=$pendingGems?></strong><small>Awaiting review</small></div><div class="metric-card"><span>Pending Reviews</span><strong><?=$pendingReviews?></strong><small>Awaiting review</small></div><div class="metric-card"><span>Active Reports</span><strong><?=$pendingReports?></strong><small>Require attention</small></div><div class="metric-card"><span>Avg. Approval Time</span><strong><?=number_format($avgApproval,1)?>h</strong><small>Historical average</small></div></div>
<nav class="moderation-tabs"><a class="<?=$tab==='gems'?'active':''?>" href="<?=url('admin/moderation.php?tab=gems')?>">Pending Gems</a><a class="<?=$tab==='reviews'?'active':''?>" href="<?=url('admin/moderation.php?tab=reviews')?>">Pending Reviews</a><a class="<?=$tab==='reports'?'active':''?>" href="<?=url('admin/moderation.php?tab=reports')?>">Reported Content</a></nav>
<div class="table-wrap"><table class="data-table"><thead><?php if($tab==='gems'):?><tr><th>Gem ID</th><th>Title</th><th>Submitted By</th><th>Date Submitted</th><th>Actions</th></tr><?php elseif($tab==='reviews'):?><tr><th>Review ID</th><th>Comment Preview</th><th>Submitted By</th><th>Associated Gem</th><th>Actions</th></tr><?php else:?><tr><th>Content</th><th>Violation</th><th>Reported By</th><th>Date</th><th>Actions</th></tr><?php endif;?></thead><tbody>
<?php if(!$items):?><tr><td colspan="5"><div class="empty-state"><p>No pending items in this section.</p></div></td></tr><?php endif;?>
<?php foreach($items as $item):?><?php if($tab==='gems'):?><tr><td>#<?=(int)$item['id']?></td><td><strong><?=e($item['title'])?></strong><br><small><?=e($item['location'])?></small></td><td><?=e($item['full_name'])?></td><td><?=e(date('Y-m-d',strtotime($item['created_at'])))?></td><td><div class="action-row"><form method="post"><?=csrf_field()?><input type="hidden" name="tab" value="gems"><input type="hidden" name="id" value="<?=(int)$item['id']?>"><input type="hidden" name="action" value="approve_gem"><button class="btn btn-success btn-sm">Approve</button></form><form method="post"><?=csrf_field()?><input type="hidden" name="tab" value="gems"><input type="hidden" name="id" value="<?=(int)$item['id']?>"><input type="hidden" name="action" value="reject_gem"><input class="form-control" name="reason" placeholder="Rejection reason" style="width:180px"><button class="btn btn-danger btn-sm">Reject</button></form><a class="btn btn-ghost btn-sm" href="<?=url('edit-gem.php?id='.(int)$item['id'])?>">Edit Before Approval</a></div></td></tr>
<?php elseif($tab==='reviews'):?><tr><td>#<?=(int)$item['id']?></td><td><?=e(mb_strimwidth($item['comment']?:'No written comment.',0,100,'…'))?><br><span class="stars"><?=e(render_stars((float)$item['rating']))?></span></td><td><?=e($item['full_name'])?></td><td><?=e($item['title'])?></td><td><div class="action-row"><form method="post"><?=csrf_field()?><input type="hidden" name="tab" value="reviews"><input type="hidden" name="id" value="<?=(int)$item['id']?>"><button class="btn btn-success btn-sm" name="action" value="approve_review">Approve</button><button class="btn btn-danger btn-sm" name="action" value="reject_review">Reject</button></form><a class="btn btn-ghost btn-sm" href="<?=url('edit-review.php?id='.(int)$item['id'])?>">Edit</a></div></td></tr>
<?php else:?><tr><td><strong><?php if($item['content_gem_id']):?><a href="<?=url('gem.php?id='.(int)$item['content_gem_id'])?>"><?=e($item['title']?:'Review report')?></a><?php else:?><?=e($item['title']?:'Reported content')?><?php endif;?></strong><br><small><?=e($item['review_comment']?:$item['details'])?></small></td><td><?=e($item['violation_type'])?></td><td><?=e($item['reporter'])?></td><td><?=e(date('Y-m-d',strtotime($item['created_at'])))?></td><td><form method="post" class="action-row"><?=csrf_field()?><input type="hidden" name="tab" value="reports"><input type="hidden" name="id" value="<?=(int)$item['id']?>"><input class="form-control" name="resolution_note" placeholder="Resolution note" style="width:180px"><button class="btn btn-success btn-sm" name="action" value="resolve_report">Resolve</button><button class="btn btn-ghost btn-sm" name="action" value="dismiss_report">Dismiss</button></form><?php if($item['review_id']||$item['gem_id']):?><form method="post" action="<?=url('delete-item.php')?>" style="margin-top:7px"><?=csrf_field()?><input type="hidden" name="type" value="<?=$item['review_id']?'review':'gem'?>"><input type="hidden" name="id" value="<?=(int)($item['review_id']?:$item['gem_id'])?>"><input type="hidden" name="admin_return" value="1"><button class="btn btn-danger btn-sm" data-confirm="Delete the reported content permanently?">Delete Content</button></form><?php endif;?></td></tr><?php endif;?><?php endforeach;?></tbody></table></div>
<?php if ($tab === 'gems'): ?>
<section class="section-tight" style="margin-top:28px">
  <div class="section-head"><div><span class="eyebrow">Manage live content</span><h2>Approved Gems — Edit Anytime</h2><p class="muted">Administrators can correct titles, city, location, budget, activity, photos and descriptions even after approval. Changes stay approved.</p></div></div>
  <form method="get" class="filter-actions" style="margin:12px 0 16px">
    <input type="hidden" name="tab" value="gems">
    <label for="approved-search">Find an approved gem by name, city or location</label>
    <input id="approved-search" class="form-control" name="approved_q" value="<?=e((string)($_GET['approved_q']??''))?>" placeholder="Search approved gems" maxlength="100">
    <button class="btn btn-primary btn-sm" type="submit">Search</button>
  </form>
  <?php
  $approvedSearch=trim((string)($_GET['approved_q']??''));
  $approvedPage=max(1,(int)($_GET['approved_page']??1));
  $approvedWhere="WHERE g.status='approved'";
  $approvedParams=[];
  if ($approvedSearch!=='') {
      $approvedWhere.=" AND (g.title LIKE ? OR g.city LIKE ? OR g.location LIKE ?)";
      $approvedParams=array_fill(0,3,'%'.$approvedSearch.'%');
  }
  $approvedCount=db()->prepare("SELECT COUNT(*) FROM gems g ".$approvedWhere);
  $approvedCount->execute($approvedParams);
  $approvedTotal=(int)$approvedCount->fetchColumn();
  $approvedOffset=($approvedPage-1)*30;
  $approvedQuery=db()->prepare("SELECT g.id,g.title,g.city,g.location,g.updated_at,u.full_name FROM gems g JOIN users u ON u.id=g.user_id ".$approvedWhere." ORDER BY g.updated_at DESC LIMIT 30 OFFSET ".$approvedOffset);
  $approvedQuery->execute($approvedParams);
  $approvedItems=$approvedQuery->fetchAll();
  ?>
  <div class="table-wrap"><table class="data-table"><thead><tr><th>Gem</th><th>City</th><th>Contributor</th><th>Last Updated</th><th>Action</th></tr></thead><tbody>
    <?php if (!$approvedItems): ?><tr><td colspan="5">No approved gems yet.</td></tr><?php endif; ?>
    <?php foreach ($approvedItems as $approved): ?>
      <tr><td><strong><?=e($approved['title'])?></strong><br><small><?=e($approved['location'])?></small></td><td><?=e($approved['city'])?></td><td><?=e($approved['full_name'])?></td><td><?=e(date('Y-m-d',strtotime($approved['updated_at'])))?></td><td><a class="btn btn-primary btn-sm" href="<?=url('edit-gem.php?id='.(int)$approved['id'])?>">Edit Approved Gem</a></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <div class="filter-actions"><span class="result-count">Showing <?=count($approvedItems)?> of <?=$approvedTotal?> approved gems</span><div class="action-row"><?php if($approvedPage>1):?><a class="btn btn-ghost btn-sm" href="<?=url('admin/moderation.php?tab=gems&approved_page='.($approvedPage-1).'&approved_q='.urlencode($approvedSearch))?>">Previous page</a><?php endif;?><?php if($approvedOffset+30<$approvedTotal):?><a class="btn btn-ghost btn-sm" href="<?=url('admin/moderation.php?tab=gems&approved_page='.($approvedPage+1).'&approved_q='.urlencode($approvedSearch))?>">Next page</a><?php endif;?></div></div>
</section>
<?php endif; ?>
<?php require dirname(__DIR__).'/includes/admin-footer.php'; ?>
