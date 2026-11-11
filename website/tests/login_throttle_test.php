<?php
declare(strict_types=1);

$directory = sys_get_temp_dir() . '/mzansigem-throttle-test-' . bin2hex(random_bytes(8));
putenv('MZANSI_LOGIN_THROTTLE_DIR=' . $directory);
$_SERVER['REMOTE_ADDR'] = '192.0.2.8';

require dirname(__DIR__) . '/includes/login_throttle.php';

function check_throttle(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        exit(1);
    }
}

try {
    $email = 'test-' . bin2hex(random_bytes(5)) . '@example.test';
    check_throttle(!login_throttle_blocked($email), 'Fresh email should not be blocked');

    for ($i = 0; $i < LOGIN_THROTTLE_EMAIL_IP_LIMIT; $i++) {
        login_throttle_record_failure($email);
    }
    check_throttle(login_throttle_blocked($email), 'Five failed attempts should block email/IP');

    login_throttle_clear($email);
    check_throttle(!login_throttle_blocked($email), 'Successful login should clear email/IP failures');

    // Different addresses share a per-IP budget, even when their email differs.
    for ($i = LOGIN_THROTTLE_EMAIL_IP_LIMIT; $i < LOGIN_THROTTLE_IP_LIMIT; $i++) {
        login_throttle_record_failure('different-' . $i . '@example.test');
    }
    check_throttle(login_throttle_blocked('another@example.test'), 'IP-wide failure budget should block spraying');

    $_SERVER['REMOTE_ADDR'] = '198.51.100.9';
    check_throttle(!login_throttle_blocked($email), 'Different IP should not inherit previous IP budget');

    echo "PASS: login throttling, reset and per-IP protection" . PHP_EOL;
} finally {
    foreach (glob($directory . '/*.json') ?: [] as $file) {
        unlink($file);
    }
    if (is_dir($directory)) {
        rmdir($directory);
    }
    putenv('MZANSI_LOGIN_THROTTLE_DIR');
}
