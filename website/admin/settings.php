<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';require_admin();
$keys=['site_name','site_description','allow_registration','require_email_verification','max_posts_per_day','enable_moderation','auto_approve_content'];
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$values=[
 'site_name'=>trim((string)($_POST['site_name']??'Mzansi Gem')),
 'site_description'=>trim((string)($_POST['site_description']??'')),
 'allow_registration'=>isset($_POST['allow_registration'])?'1':'0',
 'require_email_verification'=>isset($_POST['require_email_verification'])?'1':'0',
 'max_posts_per_day'=>(string)max(1,min(100,(int)($_POST['max_posts_per_day']??10))),
 'enable_moderation'=>isset($_POST['enable_moderation'])?'1':'0',
 'auto_approve_content'=>isset($_POST['auto_approve_content'])?'1':'0',
 ];$stmt=db()->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');foreach($values as $key=>$value)$stmt->execute([$key,$value]);flash('success','Platform settings saved.');redirect('admin/settings.php');}
$settings=[];$stmt=db()->query('SELECT setting_key,setting_value FROM settings');foreach($stmt->fetchAll() as $row)$settings[$row['setting_key']]=$row['setting_value'];
$pageTitle='General Settings';$activeAdmin='settings';require dirname(__DIR__).'/includes/admin-header.php';
?>
<div class="admin-page-head"><div><span class="eyebrow">Configuration</span><h1>General Settings</h1><p class="muted">Configure platform behaviour and moderation rules.</p></div></div>
<form method="post"><?=csrf_field()?>
<section class="setting-section"><h2>General Settings</h2><div class="form-group"><label>Site Name</label><input class="form-control" name="site_name" value="<?=e(setting('site_name', APP_NAME))?>" required></div><div class="form-group"><label>Site Description</label><textarea class="form-control" name="site_description" maxlength="300"><?=e($settings['site_description']??'')?></textarea></div></section>
<section class="setting-section"><h2>User Management Settings</h2><div class="toggle-row"><div><strong>Allow User Registration</strong><div class="form-help">Enable new users to sign up.</div></div><label class="switch"><input type="checkbox" name="allow_registration" <?=($settings['allow_registration']??'1')==='1'?'checked':''?>><span></span></label></div><div class="toggle-row"><div><strong>Require Email Verification</strong><div class="form-help">Require manual registrations to verify before sign-in.</div></div><label class="switch"><input type="checkbox" name="require_email_verification" <?=($settings['require_email_verification']??'0')==='1'?'checked':''?>><span></span></label></div><div class="form-group" style="margin-top:14px"><label>Max Posts Per Day Per User</label><input class="form-control" type="number" name="max_posts_per_day" min="1" max="100" value="<?=e($settings['max_posts_per_day']??'10')?>"></div></section>
<section class="setting-section"><h2>Content Moderation Settings</h2><div class="toggle-row"><div><strong>Enable Content Moderation</strong><div class="form-help">Review gems and reviews before publishing.</div></div><label class="switch"><input type="checkbox" name="enable_moderation" <?=($settings['enable_moderation']??'1')==='1'?'checked':''?>><span></span></label></div><div class="toggle-row"><div><strong>Auto-Approve Content</strong><div class="form-help">Automatically approve new gem submissions.</div></div><label class="switch"><input type="checkbox" name="auto_approve_content" <?=($settings['auto_approve_content']??'0')==='1'?'checked':''?>><span></span></label></div></section>
<button class="btn btn-primary">Save Settings</button></form>
<?php require dirname(__DIR__).'/includes/admin-footer.php'; ?>
