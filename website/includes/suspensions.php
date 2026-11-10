<?php
declare(strict_types=1);

/**
 * A suspended account never receives the normal login/API session.
 * An independently scoped, short-lived session only unlocks the appeal page.
 */
function suspension_case_for(int $userId, bool $activeOnly = false): ?array
{
    $sql = 'SELECT * FROM suspension_cases WHERE user_id = ?';
    if ($activeOnly) $sql .= " AND status <> 'reinstated'";
    $sql .= ' ORDER BY id DESC LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function suspension_reason_for(int $userId): string
{
    $case = suspension_case_for($userId, true);
    return $case ? (string)$case['reason'] : 'Your account is under administrator review. Please contact the moderation team.';
}

function suspension_session_start(int $userId): void
{
    session_regenerate_id(true);
    unset($_SESSION['user_id'], $_SESSION['intended_url']);
    $_SESSION['suspension_user_id'] = $userId;
    $_SESSION['suspension_authenticated_at'] = time();
}

function suspension_session_user(): ?array
{
    $id = (int)($_SESSION['suspension_user_id'] ?? 0);
    $verifiedAt = (int)($_SESSION['suspension_authenticated_at'] ?? 0);
    if (!$id || !$verifiedAt || time() - $verifiedAt > 86400) {
        unset($_SESSION['suspension_user_id'], $_SESSION['suspension_authenticated_at']);
        return null;
    }
    $stmt = db()->prepare('SELECT id, full_name, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] !== 'suspended') {
        unset($_SESSION['suspension_user_id'], $_SESSION['suspension_authenticated_at']);
        return null;
    }
    return $user;
}

/**
 * Change access atomically. Require a written reason for any new suspension,
 * revoke existing sessions and API tokens, and preserve contributions.
 */
function suspension_update_user(int $userId, string $role, string $status, string $reason, int $adminId): void
{
    if (!in_array($role, ['user', 'admin'], true) || !in_array($status, ['active', 'suspended'], true)) {
        throw new DomainException('Choose a valid role and status.');
    }
    if ($userId === $adminId && ($role !== 'admin' || $status !== 'active')) {
        throw new DomainException('You cannot demote or suspend your own administrator account.');
    }
    $reason = trim($reason);
    if ($status === 'suspended' && $reason !== '' && mb_strlen($reason) < 10) {
        throw new DomainException('Explain the reason for suspension in at least 10 characters.');
    }
    if (mb_strlen($reason) > 500) {
        throw new DomainException('The suspension reason must be 500 characters or fewer.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id, status FROM users WHERE id = ? FOR UPDATE');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) throw new DomainException('User account not found.');

        $stmt = $pdo->prepare("SELECT id, reason FROM suspension_cases WHERE user_id = ? AND status <> 'reinstated' ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $stmt->execute([$userId]);
        $openCase = $stmt->fetch() ?: null;

        if ($status === 'suspended') {
            if ($user['status'] !== 'suspended' || !$openCase) {
                if ($reason === '') throw new DomainException('Provide a reason before suspending this user.');
                $stmt = $pdo->prepare('INSERT INTO suspension_cases (user_id,reason,created_by) VALUES (?,?,?)');
                $stmt->execute([$userId, $reason, $adminId]);
            } elseif ($reason !== '' && $reason !== $openCase['reason']) {
                $pdo->prepare('UPDATE suspension_cases SET reason = ? WHERE id = ?')->execute([$reason, (int)$openCase['id']]);
            }
        } elseif ($user['status'] === 'suspended' && $openCase) {
            $pdo->prepare("UPDATE suspension_cases SET status = 'reinstated', resolved_at = NOW() WHERE id = ?")
                ->execute([(int)$openCase['id']]);
        }

        $pdo->prepare('UPDATE users SET role = ?, status = ? WHERE id = ?')->execute([$role, $status, $userId]);
        if ($status === 'suspended') {
            $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);
            $pdo->prepare('DELETE FROM api_tokens WHERE user_id = ?')->execute([$userId]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

/**
 * Append a private conversation message. A suspended member may post only
 * to their own active case, with a per-hour limit. All message output is
 * HTML-escaped by the pages displaying it.
 */
function suspension_add_message(int $caseId, int $senderId, string $body, bool $isAdmin): void
{
    $body = trim($body);
    if (mb_strlen($body) < 10 || mb_strlen($body) > 2000) {
        throw new DomainException('Write a message between 10 and 2,000 characters.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT user_id, status FROM suspension_cases WHERE id = ? FOR UPDATE');
        $stmt->execute([$caseId]);
        $case = $stmt->fetch();
        if (!$case || $case['status'] === 'reinstated') throw new DomainException('This appeal case is closed.');
        if (!$isAdmin && (int)$case['user_id'] !== $senderId) throw new DomainException('Access denied.');
        if ($isAdmin) {
            $auth = $pdo->prepare('SELECT role,status FROM users WHERE id=?');
            $auth->execute([$senderId]);
            $admin = $auth->fetch();
            if (!$admin || $admin['role'] !== 'admin' || $admin['status'] !== 'active') {
                throw new DomainException('Administrator access is required.');
            }
        }

        if (!$isAdmin) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM suspension_messages WHERE case_id = ? AND sender_user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)');
            $stmt->execute([$caseId, $senderId]);
            if ((int)$stmt->fetchColumn() >= 6) throw new DomainException('You have reached the message limit. Please try again later.');
        }
        $pdo->prepare('INSERT INTO suspension_messages (case_id,sender_user_id,sender_kind,message) VALUES (?,?,?,?)')
            ->execute([$caseId, $senderId, $isAdmin ? 'admin' : 'member', $body]);
        $nextStatus = $isAdmin ? 'under_review' : 'appealed';
        $pdo->prepare('UPDATE suspension_cases SET status = ? WHERE id = ?')->execute([$nextStatus, $caseId]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function suspension_messages_for(int $caseId): array
{
    $stmt = db()->prepare("SELECT m.message,m.created_at,m.sender_kind AS role
        FROM suspension_messages m
        WHERE m.case_id = ? ORDER BY m.id ASC LIMIT 300");
    $stmt->execute([$caseId]);
    return $stmt->fetchAll();
}
