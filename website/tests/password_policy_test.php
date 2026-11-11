<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/password_policy.php';

$rejected = [
    '12345678',
    '1122334455667788',
    '123456789012345',
    'Password12345678!',
    'aaaaaaaaaaaaaaaa',
    'passwordpassword',
    'qwertyuiopasdfgh',
    'ALLUPPERCASE123!', // No lowercase
    'alllowercase123!', // No uppercase
    'NoNumbersHere!!', // No number
    'NoSymbolsHere123', // No allowed symbol
    'Aa!1122334455667788', // Predictable digits despite character variety
];
$accepted = [
    'River Moon Lantern 92!',
    'Quiet-coffee-river-moon7!',
    'V7#mQ2!zR9@pL4$s',
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
