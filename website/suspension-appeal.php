<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

header('Cache-Control: private, no-store');
$member = suspension_session_user();
if (!$member) {
    flash('warning', 'Please sign in again to review your suspension or appeal.');
    redirect('login.php');
}

// Older suspended accounts did not have an appeal record. Create one safely
// only for the account that has just passed a real authentication check.
$case = suspension_case_for((int)$member['id'], true);
if (!$case) {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT id, status FROM users WHERE id = ? FOR UPDATE');
        $lock->execute([(int)$member['id']]);
        $lockedMember = $lock->fetch();
        if (!$lockedMember || $lockedMember['status'] !== 'suspended') {
            $pdo->rollBack();
            redirect('login.php');
        }
        $case = suspension_case_for((int)$member['id'], true);
        if (!$case) {
            $pdo->prepare('INSERT INTO suspension_cases (user_id, reason) VALUES (?, ?)')
                ->execute([(int)$member['id'], 'Your account was suspended before the appeals system was enabled. Please request the reason from an administrator.']);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
    $case = suspension_case_for((int)$member['id'], true);
}
if (!$case) {
    http_response_code(503);
    exit('The appeal service is temporarily unavailable. Please try again.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        suspension_add_message((int)$case['id'], (int)$member['id'], (string)($_POST['message'] ?? ''), false);
        flash('success', 'Your message has been sent to the moderation team.');
    } catch (DomainException $error) {
        flash('danger', $error->getMessage());
    }
    redirect('suspension-appeal.php');
}

$messages = suspension_messages_for((int)$case['id']);
$pageTitle = 'Account suspension and appeal';
$bodyClass = 'auth-body';
require __DIR__ . '/includes/header.php';
?>
<style>
.appeal-wrap{max-width:820px;margin:48px auto;padding:0 20px 64px}
.appeal-panel{background:#fff;border:1px solid #eadbd2;border-radius:20px;padding:28px;margin-bottom:18px;box-shadow:0 6px 28px #3e2a1a0a}
.appeal-panel h1{font-size:clamp(1.7rem,3vw,2.5rem);margin:8px 0}
.appeal-reason{border-left:4px solid #ed6841;background:#fff3ec;padding:14px 18px;border-radius:8px;white-space:pre-wrap;overflow-wrap:anywhere}
.appeal-msg{margin:14px 0;padding:14px 18px;background:#f6f7f8;border-radius:14px;max-width:90%}
.appeal-msg.admin{background:#fff0e7;margin-left:auto}
.appeal-msg p{white-space:pre-wrap;overflow-wrap:anywhere;margin:9px 0 0}
.appeal-meta{font-size:.8rem;color:#627177}
.appeal-panel textarea{width:100%;min-height:118px;resize:vertical}
.appeal-panel .muted{line-height:1.6}
</style>
<main class="appeal-wrap">
    <section class="appeal-panel">
        <span class="eyebrow">Account access</span>
        <h1>Your account is suspended</h1>
        <p class="muted">Hello <?= e($member['full_name']) ?>. You cannot post, save, react, review or use your normal account while it is suspended. However, you can contact our moderators here.</p>
        <h2 style="margin-top:22px">Reason for suspension</h2>
        <div class="appeal-reason"><?= e($case['reason']) ?></div>
        <p class="muted" style="margin-top:16px">Case #<?= (int)$case['id'] ?> · Status: <strong><?= e(str_replace('_',' ',ucfirst($case['status']))) ?></strong> · Opened <?= e(date('j M Y', strtotime($case['created_at']))) ?></p>
    </section>

    <section class="appeal-panel">
        <h2>Appeal and messages</h2>
        <p class="muted">Explain why you believe this decision should be reviewed. Please be respectful and do not share passwords, private identification documents or payment details. Replies are private between you and the moderation team. Check this page again after signing in.</p>
        <?php if (!$messages): ?><p class="muted">No messages yet. Start the conversation below.</p><?php endif; ?>
        <?php foreach ($messages as $message): ?>
            <article class="appeal-msg <?= ($message['role'] ?? '') === 'admin' ? 'admin' : '' ?>">
                <div class="appeal-meta"><strong><?= e(($message['role'] ?? '') === 'admin' ? 'MzansiGems Moderator' : 'You') ?></strong> · <?= e(date('j M Y H:i', strtotime($message['created_at']))) ?></div>
                <p><?= e($message['message']) ?></p>
            </article>
        <?php endforeach; ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="appeal-message">Your message to the administrators</label>
                <textarea class="form-control" id="appeal-message" name="message" minlength="10" maxlength="2000" required placeholder="I would like to appeal because..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send message / appeal</button>
        </form>
    </section>
    <p style="text-align:center"><a href="<?= url('login.php') ?>">Back to sign in</a></p>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
