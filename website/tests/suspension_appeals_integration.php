<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

function check_suspension(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException('FAILED: ' . $message);
    echo 'PASS: ' . $message . PHP_EOL;
}

$adminId = (int)db()->query("SELECT id FROM users WHERE role='admin' AND status='active' LIMIT 1")->fetchColumn();
$otherUserId = (int)db()->query("SELECT id FROM users WHERE role='user' AND status='active' LIMIT 1")->fetchColumn();
check_suspension($adminId > 0 && $otherUserId > 0, 'Test accounts available');

$testEmail = 'suspension-ci-' . bin2hex(random_bytes(6)) . '@example.test';
$pdo = db();
$stmt = $pdo->prepare('INSERT INTO users (full_name,email,city,password_hash) VALUES (?,?,?,?)');
$stmt->execute(['CI Suspension Test', $testEmail, 'Polokwane', password_hash('A-strong-test-password-2!', PASSWORD_DEFAULT)]);
$userId = (int)$pdo->lastInsertId();

try {
    try {
        suspension_update_user($userId, 'user', 'suspended', '', $adminId);
        throw new RuntimeException('Blank suspension reason was accepted.');
    } catch (DomainException $expected) {
        check_suspension(str_contains($expected->getMessage(), 'reason'), 'A suspension reason is required');
    }

    $pdo->prepare("INSERT INTO api_tokens (user_id,token_hash,name) VALUES (?,?,?)")
        ->execute([$userId, hash('sha256', 'ci-appeal-api'), 'CI token']);
    $pdo->prepare("INSERT INTO remember_tokens (user_id,selector,token_hash,expires_at) VALUES (?,?,?,DATE_ADD(NOW(), INTERVAL 1 DAY))")
        ->execute([$userId, bin2hex(random_bytes(8)), hash('sha256', 'ci-appeal-remember')]);

    suspension_update_user($userId, 'user', 'suspended', 'Repeated harmful messages.', $adminId);
    $stmt = $pdo->prepare('SELECT status FROM users WHERE id=?');
    $stmt->execute([$userId]);
    check_suspension($stmt->fetchColumn() === 'suspended', 'User is suspended');

    $case = suspension_case_for($userId, true);
    check_suspension($case !== null && $case['reason'] === 'Repeated harmful messages.', 'Case stores the exact visible reason');

    foreach (['api_tokens','remember_tokens'] as $table) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE user_id=?');
        $stmt->execute([$userId]);
        check_suspension((int)$stmt->fetchColumn() === 0, $table . ' access revoked');
    }

    try {
        suspension_add_message((int)$case['id'], $otherUserId, 'I should not access another person case.', false);
        throw new RuntimeException('Unauthorized appeal posting was accepted.');
    } catch (DomainException $expected) {
        check_suspension($expected->getMessage() === 'Access denied.', 'Other members cannot post to someone else case');
    }

    suspension_add_message((int)$case['id'], $userId, 'Please reconsider my account suspension.', false);
    check_suspension(suspension_case_for($userId, true)['status'] === 'appealed', 'Member appeal is queued');

    suspension_add_message((int)$case['id'], $adminId, 'We received your appeal and are reviewing it.', true);
    check_suspension(suspension_case_for($userId, true)['status'] === 'under_review', 'Admin reply updates case status');

    $messages = suspension_messages_for((int)$case['id']);
    check_suspension(count($messages) === 2 && $messages[0]['role'] === 'member' && $messages[1]['role'] === 'admin', 'Private message history records author roles');

    suspension_update_user($userId, 'user', 'active', '', $adminId);
    $stmt = $pdo->prepare('SELECT status FROM users WHERE id=?');
    $stmt->execute([$userId]);
    check_suspension($stmt->fetchColumn() === 'active', 'Reinstatement restores normal account status');
    check_suspension(suspension_case_for($userId, false)['status'] === 'reinstated', 'Case is closed and retained after reinstatement');

    echo 'Suspension appeals integration test passed.' . PHP_EOL;
} finally {
    $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$userId]);
}
