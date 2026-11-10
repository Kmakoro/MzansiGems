<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        suspension_update_user(
            (int)($_POST['user_id'] ?? 0),
            (string)($_POST['role'] ?? 'user'),
            (string)($_POST['status'] ?? 'active'),
            (string)($_POST['suspension_reason'] ?? ''),
            (int)current_user()['id']
        );
        flash('success', 'Account updated. A suspended member may read the reason and contact moderators on their appeal page.');
    } catch (DomainException $error) {
        flash('danger', $error->getMessage());
    }
    redirect('admin/users.php');
}

$q = trim((string)($_GET['q'] ?? ''));
$roleFilter = (string)($_GET['role'] ?? '');
$statusFilter = (string)($_GET['status'] ?? '');
$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(u.full_name LIKE ? OR u.email LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if (in_array($roleFilter, ['user','admin'], true)) {
    $where[] = 'u.role=?'; $params[] = $roleFilter;
}
if (in_array($statusFilter, ['active','suspended'], true)) {
    $where[] = 'u.status=?'; $params[] = $statusFilter;
}
$sql = "SELECT u.*,
    (SELECT COUNT(*) FROM gems g WHERE g.user_id=u.id) AS posts,
    (SELECT COUNT(*) FROM saved_gems s WHERE s.user_id=u.id) AS saved,
    sc.id AS suspension_case_id, sc.reason AS suspension_reason,
    sc.status AS suspension_case_status
    FROM users u
    LEFT JOIN suspension_cases sc ON sc.id = (
        SELECT MAX(sc2.id) FROM suspension_cases sc2
        WHERE sc2.user_id = u.id AND sc2.status <> 'reinstated'
    )
    WHERE " . implode(' AND ', $where) . ' ORDER BY u.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'User Management';
$activeAdmin = 'users';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<style>
.user-access-form{display:grid;grid-template-columns:1fr 1fr;gap:8px;min-width:260px;align-items:start}
.user-access-form .reason-field{grid-column:1/-1}
.user-access-form textarea{font:inherit;width:100%;min-height:72px;resize:vertical}
.user-access-form .btn{grid-column:1/-1}
.user-access-form .reason-field[hidden]{display:none}
.user-access-form label{display:block;font-size:.77rem;margin:0 0 4px;color:#58646b}
.user-appeal-link{display:inline-block;margin-top:8px}
</style>
<div class="admin-page-head">
    <div><span class="eyebrow">Community safety</span><h1>User Management</h1>
    <p class="muted">Give a written reason for each suspension. Suspended members may appeal without accessing normal features.</p></div>
    <a class="btn btn-primary" href="<?= url('admin/appeals.php') ?>">Suspension conversations</a>
</div>
<form class="admin-filter-bar" method="get">
    <input class="form-control search-wide" name="q" value="<?= e($q) ?>" placeholder="Search by name or email…">
    <select class="form-control" name="role">
        <option value="">All Roles</option><option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option><option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>User</option>
    </select>
    <select class="form-control" name="status">
        <option value="">All Statuses</option><option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option><option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
    </select>
    <button class="btn btn-primary">Filter</button>
</form>
<div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Activity</th><th>Joined</th><th>Account action</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><strong><?= e($u['full_name']) ?></strong><br><small><?= e($u['email']) ?></small><br>
                    <small>Level <?= (int)$u['level'] ?> · <?= (int)$u['points'] ?> points</small>
                    <?php if ($u['status'] === 'suspended' && !empty($u['suspension_reason'])): ?>
                        <p style="font-size:.8rem;max-width:280px;overflow-wrap:anywhere"><strong>Reason:</strong> <?= e($u['suspension_reason']) ?></p>
                    <?php endif; ?>
                    <?php if ($u['suspension_case_id']): ?>
                        <a class="user-appeal-link" href="<?= url('admin/appeals.php?case=' . (int)$u['suspension_case_id']) ?>">Open appeal conversation</a>
                    <?php endif; ?>
                </td>
                <td><?= e(ucfirst($u['role'])) ?></td>
                <td><span class="status-badge status-<?= e($u['status']) ?>"><?= e(ucfirst($u['status'])) ?></span></td>
                <td><?= (int)$u['posts'] ?> posts · <?= (int)$u['saved'] ?> saved</td>
                <td><?= e(date('Y-m-d', strtotime($u['created_at']))) ?></td>
                <td>
                    <form method="post" class="user-access-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <div><label for="role-<?= (int)$u['id'] ?>">Role</label>
                            <select id="role-<?= (int)$u['id'] ?>" class="form-control" name="role">
                                <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div><label for="status-<?= (int)$u['id'] ?>">Status</label>
                            <select id="status-<?= (int)$u['id'] ?>" class="form-control" name="status"
                                onchange="this.closest('form').querySelector('.reason-field').hidden = this.value !== 'suspended';">
                                <option value="active" <?= $u['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="suspended" <?= $u['status'] === 'suspended' ? 'selected' : '' ?>>Suspend</option>
                            </select>
                        </div>
                        <div class="reason-field" <?= $u['status'] === 'suspended' ? '' : 'hidden' ?>>
                            <label for="reason-<?= (int)$u['id'] ?>">Reason shown to the user (required for a new suspension)</label>
                            <textarea class="form-control" id="reason-<?= (int)$u['id'] ?>" name="suspension_reason"
                                minlength="10" maxlength="500" placeholder="Describe the policy violation, evidence or safety concern."><?= e($u['suspension_reason'] ?? '') ?></textarea>
                        </div>
                        <button class="btn btn-primary btn-sm" type="submit">Save account changes</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
