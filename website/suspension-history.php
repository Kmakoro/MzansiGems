<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();
header('Cache-Control: private, no-store');

$memberId = (int)current_user()['id'];
$stmt = db()->prepare('SELECT * FROM suspension_cases WHERE user_id = ? ORDER BY id DESC LIMIT 40');
$stmt->execute([$memberId]);
$cases = $stmt->fetchAll();
$selectedId = (int)($_GET['case'] ?? ($cases[0]['id'] ?? 0));
$selected = null;
foreach ($cases as $case) {
    if ((int)$case['id'] === $selectedId) {
        $selected = $case;
        break;
    }
}
if (!$selected && $selectedId > 0) {
    // Do not reveal whether another user's case exists.
    http_response_code(404);
    exit('Review history not found.');
}
$messages = $selected ? suspension_messages_for((int)$selected['id']) : [];

$pageTitle = 'Account review history';
require __DIR__ . '/includes/header.php';
?>
<section class="section-tight"><div class="container" style="max-width:840px">
    <h1>Account review history</h1>
    <p class="muted">You can read decisions and messages from previous account suspensions. Your account is currently active.</p>
    <p><a class="btn btn-ghost" href="<?= url('profile.php') ?>">Back to profile</a></p>
    <?php if (!$cases): ?>
        <p class="muted">No previous account reviews.</p>
    <?php else: ?>
        <nav class="action-row" style="margin:22px 0">
            <?php foreach ($cases as $case): ?>
                <a class="btn btn-soft btn-sm" href="<?= url('suspension-history.php?case=' . (int)$case['id']) ?>">
                    Case #<?= (int)$case['id'] ?> · <?= e(str_replace('_',' ',$case['status'])) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php if ($selected): ?>
            <div style="border:1px solid #e6d9d1;padding:24px;border-radius:16px;background:#fff">
                <h2>Case #<?= (int)$selected['id'] ?></h2>
                <p class="muted">Opened <?= e(date('j M Y', strtotime($selected['created_at']))) ?> · Status: <?= e(str_replace('_',' ',$selected['status'])) ?></p>
                <h3>Reason</h3>
                <p style="white-space:pre-wrap;overflow-wrap:anywhere;background:#fff1e9;padding:14px"><?= e($selected['reason']) ?></p>
                <h3>Messages</h3>
                <?php if (!$messages): ?><p class="muted">No messages were exchanged for this case.</p><?php endif; ?>
                <?php foreach ($messages as $message): ?>
                    <article style="border-top:1px solid #e6d9d1;padding:16px 0">
                        <small><?= e(($message['role'] ?? '') === 'admin' ? 'MzansiGems Moderator' : 'You') ?> · <?= e(date('j M Y H:i', strtotime($message['created_at']))) ?></small>
                        <p style="white-space:pre-wrap;overflow-wrap:anywhere"><?= e($message['message']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
