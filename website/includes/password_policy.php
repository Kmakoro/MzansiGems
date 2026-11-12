<?php
declare(strict_types=1);

/** Validate new passwords only; existing accounts can still sign in. */
function password_policy_error(string $password): ?string
{
    if (!preg_match('//u', $password)) {
        return 'Password contains invalid characters.';
    }
    if (preg_match_all('/./us', $password) < 15) {
        return 'Password must contain at least 15 characters.';
    }
    if (strlen($password) > 4096) {
        return 'Password is too long.';
    }

    // Require all four character types in newly created passwords.
    if (!preg_match('/[A-Z]/', $password)
        || !preg_match('/[a-z]/', $password)
        || !preg_match('/[0-9]/', $password)
        || !preg_match('/[!@#$%^&*]/', $password)) {
        return 'Password must include uppercase (A-Z), lowercase (a-z), a number (0-9), and a symbol (!@#$%^&*).';
    }

    // Reject obvious paired-digit runs even when surrounded by other characters.
    if (preg_match('/(?:00|11|22|33|44|55|66|77|88|99){4,}/', $password)) {
        return 'Choose a less predictable password; avoid common passwords, sequences, and repeated patterns.';
    }

    // Normalize only for comparison. Never change the actual password before hashing.
    $compact = preg_replace('/[^\p{L}\p{N}]/u', '', $password);
    $normalized = strtolower($compact ?? $password);
    $common = [
        'password', 'password123', 'password12345678', 'passwordpassword',
        '12345678', '123456789', '1234567890', 'qwerty', 'qwerty123',
        'qwertyuiop', 'letmein', 'welcome', 'admin', 'administrator',
        'iloveyou', 'mzansigems', 'hiddengems', 'correcthorsebatterystaple',
    ];
    if (in_array($normalized, $common, true)
        || preg_match('/^(?:password|welcome|admin|mzansigems|hiddengems)[0-9]*$/u', $normalized)
        || preg_match('/^(.{1,8})\1+$/us', $normalized)
        || str_contains($normalized, '12345678')
        || str_contains($normalized, '87654321')
        || str_contains($normalized, 'abcdefgh')
        || str_contains($normalized, 'qwertyui')) {
        return 'Choose a less predictable password; avoid common passwords, sequences, and repeated patterns.';
    }

    return null;
}

/**
 * Apply only after the account password has been verified. Password hashes
 * cannot be inspected to infer whether the original password was weak.
 */
function password_reset_needed(array $user, ?string $verifiedPassword = null): bool
{
    return !empty($user['password_reset_required'])
        || ($verifiedPassword !== null && password_policy_error($verifiedPassword) !== null);
}

/**
 * Lock the verified legacy account until an email-based password reset.
 * Revoke long-lived login credentials immediately, without deleting users,
 * content, reviews or password-reset links.
 */
function mark_password_reset_required(int $userId): void
{
    $connection = db();
    $connection->beginTransaction();
    try {
        $connection->prepare('UPDATE users SET password_reset_required = 1 WHERE id = ?')
            ->execute([$userId]);
        $connection->prepare('DELETE FROM api_tokens WHERE user_id = ?')
            ->execute([$userId]);
        $connection->prepare('DELETE FROM remember_tokens WHERE user_id = ?')
            ->execute([$userId]);
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}
