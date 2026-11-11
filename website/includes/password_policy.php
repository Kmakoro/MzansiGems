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
