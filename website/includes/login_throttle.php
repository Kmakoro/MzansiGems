<?php
declare(strict_types=1);

/**
 * Low-overhead login rate limiting for the website.
 *
 * Uses locked files in a private temporary directory, not files under httpdocs.
 * Counts failed attempts per IP and per (IP, email) within a rolling 15-minute window.
 * Never stores plaintext email addresses or passwords in the throttle files.
 */
const LOGIN_THROTTLE_WINDOW = 900;
const LOGIN_THROTTLE_EMAIL_IP_LIMIT = 5;
const LOGIN_THROTTLE_IP_LIMIT = 30;

function login_throttle_remote_ip(): string
{
    // Only trust the server-provided address, not caller-controlled forwarded headers.
    return (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function login_throttle_bucket_key(string $kind, string $email = ''): string
{
    $address = login_throttle_remote_ip();
    if ($kind === 'ip') {
        return 'ip:' . $address;
    }
    return 'ip-email:' . $address . ':' . strtolower(trim($email));
}

/**
 * Returns true if a bucket has reached its limit. The "failure" operation
 * records another failed login; "clear" removes failures from that bucket.
 */
function login_throttle_bucket(string $key, int $limit, string $operation = 'check'): bool
{
    $configured = getenv('MZANSI_LOGIN_THROTTLE_DIR');
    $directory = ($configured !== false && $configured !== '')
        ? $configured
        : sys_get_temp_dir() . '/mzansigem-login-throttle-' . substr(hash('sha256', __DIR__), 0, 16);

    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
        // Continue login rather than crashing the site if the host temp directory is unavailable.
        error_log('MzansiGems login throttle: storage directory is not writable.');
        return false;
    }

    $filename = rtrim($directory, '/\\') . '/' . hash('sha256', $key) . '.json';
    $handle = @fopen($filename, 'c+');
    if ($handle === false) {
        error_log('MzansiGems login throttle: cannot open rate-limit state.');
        return false;
    }
    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return false;
    }

    try {
        rewind($handle);
        $previous = json_decode(stream_get_contents($handle) ?: '[]', true);
        $now = time();
        $recent = [];
        if (is_array($previous)) {
            foreach ($previous as $timestamp) {
                if (is_int($timestamp) && $timestamp > $now - LOGIN_THROTTLE_WINDOW && $timestamp <= $now) {
                    $recent[] = $timestamp;
                }
            }
        }

        $blocked = count($recent) >= $limit;
        if ($operation === 'failure') {
            $recent[] = $now;
        } elseif ($operation === 'clear') {
            $recent = [];
        }

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($recent, JSON_THROW_ON_ERROR));
        fflush($handle);
        return $blocked;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function login_throttle_blocked(string $email): bool
{
    return login_throttle_bucket(login_throttle_bucket_key('ip-email', $email), LOGIN_THROTTLE_EMAIL_IP_LIMIT)
        || login_throttle_bucket(login_throttle_bucket_key('ip'), LOGIN_THROTTLE_IP_LIMIT);
}

function login_throttle_record_failure(string $email): void
{
    login_throttle_bucket(login_throttle_bucket_key('ip-email', $email), LOGIN_THROTTLE_EMAIL_IP_LIMIT, 'failure');
    login_throttle_bucket(login_throttle_bucket_key('ip'), LOGIN_THROTTLE_IP_LIMIT, 'failure');
}

function login_throttle_clear(string $email): void
{
    // Clear only this account/IP pair. Keep the IP-wide budget to slow password spraying.
    login_throttle_bucket(login_throttle_bucket_key('ip-email', $email), LOGIN_THROTTLE_EMAIL_IP_LIMIT, 'clear');
}
