<?php
declare(strict_types=1);

/**
 * Shared server-side password policy for website forms and the JSON API.
 * The Android UI mirrors this policy for feedback, but the server is authoritative.
 */
function password_policy_error(string $password): ?string
{
    $length = mb_strlen($password, 'UTF-8');
    if ($length < 12 || strlen($password) > 72) {
        return 'Use a password of at least 12 characters (maximum 72 bytes).';
    }
    if (preg_match('/^[0-9]+$/D', $password)
        || preg_match('/^(.)\\1{11,}$/usD', $password)
        || preg_match('/^(?:password|admin|qwerty|welcome|letmein|mzansigem|mzansigems|user)[0-9!@#$%^&*._-]*$/iD', $password)) {
        return 'Choose a less predictable password; avoid common passwords and repeated characters.';
    }
    return null;
}

/** Keep rate limits per email and remote address, never globally lock out a shared IP. */
function auth_login_bucket(string $email): string
{
    return hash('sha256', strtolower(trim($email)) . "\0" . (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

function auth_login_rate_limited(string $email): bool
{
    $stmt = db()->prepare('SELECT attempts FROM login_throttles WHERE bucket_key = ? AND window_started > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $stmt->execute([auth_login_bucket($email)]);
    return (int)$stmt->fetchColumn() >= 6;
}

function auth_record_login_failure(string $email): void
{
    db()->prepare("INSERT INTO login_throttles (bucket_key, attempts, window_started)
                  VALUES (?, 1, NOW())
                  ON DUPLICATE KEY UPDATE
                    attempts = IF(window_started <= DATE_SUB(NOW(), INTERVAL 15 MINUTE), 1, attempts + 1),
                    window_started = IF(window_started <= DATE_SUB(NOW(), INTERVAL 15 MINUTE), NOW(), window_started)")
        ->execute([auth_login_bucket($email)]);
}

function auth_clear_login_failures(string $email): void
{
    db()->prepare('DELETE FROM login_throttles WHERE bucket_key = ?')
        ->execute([auth_login_bucket($email)]);
}

/**
 * A matching legacy hash does NOT mean that a weak password is acceptable.
 * Invalidate existing sessions/tokens as soon as we identify such an account.
 */
function auth_force_password_reset(int $userId): void
{
    db()->beginTransaction();
    try {
        db()->prepare('UPDATE users SET must_reset_password = 1, auth_version = auth_version + 1 WHERE id = ? AND must_reset_password = 0')
            ->execute([$userId]);
        db()->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);
        db()->prepare('DELETE FROM api_tokens WHERE user_id = ?')->execute([$userId]);
        db()->commit();
    } catch (Throwable $exception) {
        if (db()->inTransaction()) db()->rollBack();
        throw $exception;
    }
}

function auth_reset_required_message(): string
{
    return 'For your security, this account needs a password reset. Use Forgot password to choose a stronger password.';
}
