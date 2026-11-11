<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$adminId = (int)current_user()['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $caseId = (int)($_POST['case_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    try {
        $stmt = db()->prepare("SELECT c.id,c.user_id,c.status,u.role,u.status AS user_status
            FROM suspension_cases c JOIN users u ON u.id=c.user_id WHERE c.id=? LIMIT 1");
        $stmt->execute([$caseId]);
        $caseAction = $stmt->fetch();
        if (!$caseAction) throw new DomainException('Appeal case not found.');

        if ($action === 'reply') {
            suspension_add_message($caseId, $adminId, (string)($_POST['message'] ?? ''), true);
            flash('success', 'Your reply is now visible to the suspended member.');
        } elseif ($action === 'reinstate') {
            if ($caseAction['user_status'] !== 'suspended' || $caseAction['status'] === 'reinstated') {
                throw new DomainException('The member is no longer suspended.');
            }
            $note = trim((string)($_POST['resolution_message'] ?? ''));
            if ($note !== '') suspension_add_message($caseId, $adminId, $note, true);
            suspension_update_user((int)$caseAction['user_id'], (string)$caseAction['role'], 'active', '', $adminId);
            flash('success', 'Account reinstated. The member may sign in again and use their existing gems, reviews and saved places.');
        } else {
            throw new DomainException('Unknown moderation action.');
        }
    } catch (DomainException $error) {
        flash('danger', $error->getMessage());
    }
    redirect('admin/appeals.php?case=' . $caseId);
}

$cases = db()->query("SELECT c.*,u.full_name,u.email,u.status AS user_status,
    (SELECT COUNT(*) FROM suspension_messages m WHERE m.case_id=c.id) AS message_count
    FROM suspension_cases c JOIN users u ON u.id=c.user_id
    ORDER BY FIELD(c.status,'appealed','open','under_review','reinstated'), c.updated_at DESC LIMIT 150")->fetchAll();

$selectedId = (int)($_GET['case'] ?? ($cases[0]['id'] ?? 0));
$selected = null;
foreach ($cases as $case) {
    if ((int)$case['id'] === $selectedId) {
        $selected = $case;
        break;
    }
}
if (!$selected && $selectedId > 0) {
    $stmt = db()->prepare("SELECT c.*,u.full_name,u.email,u.status AS user_status,
        (SELECT COUNT(*) FROM suspension_messages m WHERE m.case_id=c.id) AS message_count
        FROM suspension_cases c JOIN users u ON u.id=c.user_id WHERE c.id=?");
    $stmt->execute([$selectedId]);
    $selected = $stmt->fetch() ?: null;
}
$messages = $selected ? suspension_messages_for((int)$selected['id']) : [];
$pending = (int)db()->query("SELECT COUNT(*) FROM suspension_cases WHERE status='appealed'")->fetchColumn();

$pageTitle = 'Suspension Appeals';
$activeAdmin = 'appeals';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<style>
.appeal-admin-grid{display:grid;grid-template-columns:minmax(220px,300px) minmax(0,1fr);gap:20px;align-items:start}
.appeal-admin-box{background:#fff;border:1px solid #e7ddd7;border-radius:15px;padding:22px}
.appeal-admin-item{display:block;text-decoration:none;color:inherit;border-bottom:1px solid #e9e1dc;padding:13px 4px}
.appeal-admin-item:hover,.appeal-admin-item.active{background:#fff2e9}
.appeal-admin-item small{display:block;color:#65767d;margin-top:4px}
.appeal-admin-reason{background:#fff3eb;border-left:4px solid #ed6841;padding:13px;margin:14px 0;white-space:pre-wrap;overflow-wrap:anywhere}
.appeal-admin-msg{border-radius:12px;background:#f3f5f6;padding:12px 16px;margin:12px 0;max-width:93%}
.appeal-admin-msg.admin{margin-left:auto;background:#fff1e7}
.appeal-admin-msg p{white-space:pre-wrap;overflow-wrap:anywhere;margin:8px 0 0}
.appeal-admin-meta{font-size:.8rem;color:#60727b}
.appeal-admin-box textarea{min-height:100px;width:100%;resize:vertical}
.appeal-admin-box form{margin-top:20px}
@media(max-width:850px){.appeal-admin-grid{grid-template-columns:1fr}}
</style>
<div class="admin-page-head">
    <div><span class="eyebrow">Trust and safety</span><h1>Suspension Appeals & Messages</h1>
    <p class="muted">Read explanations, respond privately, and reinstate accounts after review.</p></div>
    <span class="status-badge status-<?= $pending ? 'pending' : 'active' ?>"><?= $pending ?> awaiting a response</span>
</div>
<div class="appeal-admin-grid">
    <section class="appeal-admin-box">
        <h2>Cases</h2>
        <?php if (!$cases): ?><p class="muted">No suspension cases yet.</p><?php endif; ?>
        <?php foreach ($cases as $case): ?>
            <a class="appeal-admin-item <?= (int)$case['id'] === $selectedId ? 'active' : '' ?>" href="<?= url('admin/appeals.php?case=' . (int)$case['id']) ?>">
                <strong><?= e($case['full_name']) ?></strong>
                <small>Case #<?= (int)$case['id'] ?> · <?= e(str_replace('_', ' ', ucfirst($case['status']))) ?></small>
                <small><?= (int)$case['message_count'] ?> messages · Updated <?= e(date('j M H:i', strtotime($case['updated_at']))) ?></small>
            </a>
        <?php endforeach; ?>
    </section>
    <section class="appeal-admin-box">
        <?php if (!$selected): ?>
            <p class="muted">Select a case to view the conversation.</p>
        <?php else: ?>
            <h2><?= e($selected['full_name']) ?> · Case #<?= (int)$selected['id'] ?></h2>
            <p class="muted"><?= e($selected['email']) ?> · Account: <?= e($selected['user_status']) ?> · Case: <?= e(str_replace('_',' ', $selected['status'])) ?></p>
            <h3>Suspension reason</h3>
            <div class="appeal-admin-reason"><?= e($selected['reason']) ?></div>
            <p><a href="<?= url('admin/users.php?q=' . rawurlencode($selected['email'])) ?>">Edit user or suspension reason</a></p>
            <h3>Conversation</h3>
            <?php if (!$messages): ?><p class="muted">No appeal messages yet.</p><?php endif; ?>
            <?php foreach ($messages as $message): ?>
                <div class="appeal-admin-msg <?= ($message['role'] ?? '') === 'admin' ? 'admin' : '' ?>">
                    <div class="appeal-admin-meta"><strong><?= e(($message['role'] ?? '') === 'admin' ? 'Administrator' : $selected['full_name']) ?></strong> · <?= e(date('j M Y H:i',strtotime($message['created_at']))) ?></div>
                    <p><?= e($message['message']) ?></p>
                </div>
            <?php endforeach; ?>

            <?php if ($selected['user_status'] === 'suspended' && $selected['status'] !== 'reinstated'): ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="case_id" value="<?= (int)$selected['id'] ?>">
                    <input type="hidden" name="action" value="reply">
                    <label for="admin-message"><strong>Reply to member</strong></label>
                    <textarea id="admin-message" name="message" class="form-control" minlength="10" maxlength="2000" required placeholder="We have reviewed your appeal..."></textarea>
                    <button class="btn btn-primary" style="margin-top:10px" type="submit">Send private reply</button>
                </form>
                <form method="post" style="border-top:1px solid #e5ddd9;padding-top:20px">
                    <?= csrf_field() ?>
                    <input type="hidden" name="case_id" value="<?= (int)$selected['id'] ?>">
                    <input type="hidden" name="action" value="reinstate">
                    <h3>Restore this account</h3>
                    <p class="muted">This ends the suspension. The member will regain normal access after signing in again. Their existing content remains intact.</p>
                    <label for="resolution-message">Optional final message (shown to the member in case history)</label>
                    <textarea class="form-control" id="resolution-message" name="resolution_message" maxlength="2000" placeholder="Your appeal has been accepted..."></textarea>
                    <button type="submit" class="btn btn-primary" style="margin-top:10px" onclick="return confirm('Reinstate this account and restore access?')">Reinstate account</button>
                </form>
            <?php else: ?>
                <p class="muted">This case is closed. The conversation is retained for moderation records.</p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>