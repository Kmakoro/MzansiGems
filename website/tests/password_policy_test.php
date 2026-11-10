<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/password_policy.php';
$cases = [
    ['1234', true],
    ['password1234', true],
    ['123456789012', true],
    ['A much stronger passphrase 2026!', false],
];
foreach ($cases as [$password, $reject]) {
    $actual = password_policy_error($password) !== null;
    if ($actual !== $reject) {
        fwrite(STDERR, "Password policy regression failed.\n");
        exit(1);
    }
}
echo "Password policy tests passed.\n";
