<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/password_policy.php';

$rejected = [
    '12345678',
    '123456789012345',
    'Password12345678!',
    'aaaaaaaaaaaaaaaa',
    'passwordpassword',
    'qwertyuiopasdfgh',
];
$accepted = [
    'River Moon Lantern 92!',
    'quiet-coffee-river-moon',
    'Forest meadow after rain',
];

foreach ($rejected as $password) {
    if (password_policy_error($password) === null) {
        fwrite(STDERR, "FAIL: predictable password was accepted\n");
        exit(1);
    }
}
foreach ($accepted as $password) {
    if (password_policy_error($password) !== null) {
        fwrite(STDERR, "FAIL: long passphrase was rejected\n");
        exit(1);
    }
}
echo "PASS: password policy accepts passphrases and rejects predictable passwords\n";
