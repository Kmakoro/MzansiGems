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

function revoke_weak_password_sessions(int $userId): void
{
    db()->prepare('DELETE FROM api_tokens WHERE user_id = ?')->execute([$userId]);
    db()->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);
}
