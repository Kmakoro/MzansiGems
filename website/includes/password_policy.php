<?php
declare(strict_types=1);

/**
 * Shared server-side password policy. This is authoritative for the web UI
 * and every REST client, including Android.
 */
function password_policy_error(string $password): ?string
{
    if (strlen($password) < 12) {
        return 'Password must contain at least 12 characters.';
    }
    if (strlen($password) > 1024) {
        return 'Password must contain no more than 1024 bytes.';
    }
    $common = ['password1234', 'password12345', '123456789012', 'qwerty123456', 'admin12345678'];
    if (in_array(strtolower($password), $common, true)) {
        return 'Choose a less common password.';
    }
    return null;
}

function password_upgrade_required(string $password): bool
{
    return password_policy_error($password) !== null;
}

function revoke_password_sessions(int $userId): void
{
    db()->prepare('DELETE FROM api_tokens WHERE user_id = ?')->execute([$userId]);
    db()->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);
}

function revoke_weak_password_sessions(int $userId): void
{
    db()->prepare('UPDATE users SET password_reset_required = 1 WHERE id = ?')->execute([$userId]);
    revoke_password_sessions($userId);
}

/**
 * Persistent, per-email-and-IP attempt counter across PHP workers.
 * Apply the companion additive SQL migration before deploying this code.
 */
function login_throttle_key(string $email): string
{
    $address = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash('sha256', strtolower(trim($email)) . "\0" . $address);
}

function login_throttle_blocked(string $email): bool
{
    $stmt = db()->prepare('SELECT failures FROM auth_login_attempts WHERE attempt_key = ? AND window_start > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $stmt->execute([login_throttle_key($email)]);
    return (int)($stmt->fetchColumn() ?: 0) >= 8;
}

function login_throttle_fail(string $email): void
{
    $stmt = db()->prepare(
        'INSERT INTO auth_login_attempts (attempt_key, failures, window_start) VALUES (?, 1, NOW())
         ON DUPLICATE KEY UPDATE failures = IF(window_start <= DATE_SUB(NOW(), INTERVAL 15 MINUTE), 1, failures + 1),
                                 window_start = IF(window_start <= DATE_SUB(NOW(), INTERVAL 15 MINUTE), NOW(), window_start)'
    );
    $stmt->execute([login_throttle_key($email)]);
}

function login_throttle_success(string $email): void
{
    db()->prepare('DELETE FROM auth_login_attempts WHERE attempt_key = ?')->execute([login_throttle_key($email)]);
}
