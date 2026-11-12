<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/password_policy.php';

function require_condition(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

$strong = 'River Moon Lantern 92!';
require_condition(!password_reset_needed(['password_reset_required' => 0], $strong),
    'Strong existing credential should log in normally.');
require_condition(password_reset_needed(['password_reset_required' => 0], '1234'),
    'Verified weak legacy password must trigger mandatory reset.');
require_condition(password_reset_needed(['password_reset_required' => 1], $strong),
    'Flagged account cannot bypass mandatory reset with a strong credential.');
require_condition(password_reset_needed(['password_reset_required' => 1], null),
    'Previously flagged bearer/session account must stay blocked.');
require_condition(!password_reset_needed(['password_reset_required' => 0], null),
    'An unflagged Google-only user should not be blocked.');

echo "PASS: mandatory legacy password reset decision rules\n";
