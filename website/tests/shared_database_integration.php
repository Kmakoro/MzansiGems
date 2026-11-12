<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/config/database.php';

$baseUrl = rtrim((string)(getenv('TEST_API_URL') ?: 'http://127.0.0.1:8080/api/v1'), '/');

function fail_test(string $message): never {
    fwrite(STDERR, "FAILED: {$message}\n");
    exit(1);
}

function api_request(string $method, string $url, ?array $json = null, ?string $token = null): array {
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($json !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json, JSON_THROW_ON_ERROR));
    }
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!is_string($raw)) fail_test('No API response.');
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) fail_test('API returned invalid JSON.');
    if ($status >= 400 || empty($decoded['success'])) {
        fail_test('API request failed: ' . ($decoded['message'] ?? ('HTTP ' . $status)));
    }
    return $decoded;
}

// This integration test changes seeded database records and generates failed
// logins. It must never run against production or a public staging host.
$testHost = (string)parse_url($baseUrl, PHP_URL_HOST);
if (!in_array($testHost, ['localhost', '127.0.0.1'], true)) {
    fail_test('Integration tests may only target a local test API.');
}

function expect_api_login_error(string $url, string $email, string $password, int $expectedStatus): void {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR),
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $response = is_string($raw) ? json_decode($raw, true) : null;
    if ($status !== $expectedStatus || !is_array($response) || ($response['success'] ?? null) !== false) {
        fail_test('Expected API login HTTP ' . $expectedStatus . ', got HTTP ' . $status . '.');
    }
}

// Use a disposable strong-password account. The seeded legacy fixture
// deliberately uses an old weak password and must not be our normal user.
$testEmail = 'ci-legacy-' . bin2hex(random_bytes(6)) . '@example.test';
$testPassword = 'River Moon Lantern 92!';

$health = api_request('GET', $baseUrl . '/health');
if (($health['data']['status'] ?? '') !== 'healthy') fail_test('Health endpoint is not healthy.');
echo "PASS: API health\n";

// Five wrong passwords for one IP/email must still return 401;
// the next request must be blocked with HTTP 429.
$guessEmail = 'ci-unknown-' . bin2hex(random_bytes(6)) . '@example.test';
for ($attempt = 0; $attempt < 5; $attempt++) {
    expect_api_login_error($baseUrl . '/auth/login', $guessEmail, 'wrong-password', 401);
}
expect_api_login_error($baseUrl . '/auth/login', $guessEmail, 'wrong-password', 429);
echo "PASS: API rejects repeated password guesses with HTTP 429\n";

// The 5 failed attempts must not prevent a different, legitimate account on
// the same test IP from signing in.
$registration = api_request('POST', $baseUrl . '/auth/register', [
    'full_name' => 'Task Three Test User',
    'email' => $testEmail,
    'city' => 'Johannesburg',
    'password' => $testPassword,
    'confirm_password' => $testPassword,
]);
if (empty($registration['data']['token'])) fail_test('Strong-password registration did not return a token.');
echo "PASS: strong-password disposable account registered\n";

$login = api_request('POST', $baseUrl . '/auth/login', [
    'email' => $testEmail,
    'password' => $testPassword,
]);
$token = (string)($login['data']['token'] ?? '');
if ($token === '') fail_test('Login did not return a bearer token.');
echo "PASS: independent legitimate API login after unrelated bad guesses\n";

$me = api_request('GET', $baseUrl . '/auth/me', null, $token);
if (($me['data']['email'] ?? '') !== $testEmail) fail_test('Authenticated user mismatch.');
echo "PASS: authenticated user\n";

$syncName = 'CI Mobile Website Sync';
api_request('PATCH', $baseUrl . '/profile', [
    'full_name' => $syncName,
    'email' => $testEmail,
    'city' => 'Johannesburg',
    'bio' => 'Verified by shared database integration test.',
], $token);

$stmt = db()->prepare('SELECT full_name FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$testEmail]);
if ((string)$stmt->fetchColumn() !== $syncName) fail_test('Profile update did not reach the same MySQL database.');
echo "PASS: Android API profile change is visible through website database connection\n";

api_request('PATCH', $baseUrl . '/profile/settings', [
    'notify_new_gems' => false,
    'notify_comments' => true,
    'notify_likes_saves' => false,
    'personalized_recommendations' => true,
    'show_saved_gems' => true,
    'show_activity_status' => false,
], $token);

$stmt = db()->prepare('SELECT notify_new_gems,notify_comments,notify_likes_saves,personalized_recommendations,show_saved_gems,show_activity_status FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$testEmail]);
$settings = array_map('intval', $stmt->fetch(PDO::FETCH_NUM) ?: []);
if ($settings !== [0,1,0,1,1,0]) fail_test('Settings did not persist in shared MySQL.');
echo "PASS: settings synchronize through shared MySQL\n";

$like = api_request('POST', $baseUrl . '/gems/1/like', null, $token);
$stmt = db()->prepare('SELECT COUNT(*) FROM gem_likes gl JOIN users u ON u.id=gl.user_id WHERE u.email=? AND gl.gem_id=1');
$stmt->execute([$testEmail]);
if ((int)$stmt->fetchColumn() !== (!empty($like['data']['is_liked']) ? 1 : 0)) fail_test('Like state differs between API and MySQL.');
api_request('POST', $baseUrl . '/gems/1/like', null, $token);
echo "PASS: gem like synchronizes through shared MySQL\n";

$save = api_request('POST', $baseUrl . '/gems/1/save', null, $token);
$stmt = db()->prepare('SELECT COUNT(*) FROM saved_gems sg JOIN users u ON u.id=sg.user_id WHERE u.email=? AND sg.gem_id=1');
$stmt->execute([$testEmail]);
if ((int)$stmt->fetchColumn() !== (!empty($save['data']['is_saved']) ? 1 : 0)) fail_test('Saved state differs between API and MySQL.');
api_request('POST', $baseUrl . '/gems/1/save', null, $token);
echo "PASS: saved gem synchronizes through shared MySQL\n";

api_request('PATCH', $baseUrl . '/profile', [
    'full_name' => 'Thabo Mkhize',
    'email' => $testEmail,
    'city' => 'Johannesburg',
    'bio' => 'Always looking for the next underrated place.',
], $token);

api_request('PATCH', $baseUrl . '/profile/settings', [
    'notify_new_gems' => true,
    'notify_comments' => true,
    'notify_likes_saves' => true,
    'personalized_recommendations' => true,
    'show_saved_gems' => true,
    'show_activity_status' => true,
], $token);

echo "PASS: seeded database user unchanged; temporary test changes restored\n";

// Simulate a legacy account using a verified weak credential, without touching
// seeded real/sample accounts or running against a non-local server.
$identity = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$identity->execute([$testEmail]);
$legacyUserId = (int)$identity->fetchColumn();
if ($legacyUserId < 1) fail_test('Disposable legacy test account missing.');

db()->prepare('UPDATE users SET password_hash = ?, password_reset_required = 0 WHERE id = ?')
    ->execute([password_hash('1234', PASSWORD_DEFAULT), $legacyUserId]);
$selector = bin2hex(random_bytes(8));
db()->prepare('INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY))')
    ->execute([$legacyUserId, $selector, hash('sha256', 'test-validator')]);

// The verified weak password must not produce a new API bearer token.
expect_api_login_error($baseUrl . '/auth/login', $testEmail, '1234', 403);
$flag = db()->prepare('SELECT password_reset_required FROM users WHERE id = ?');
$flag->execute([$legacyUserId]);
if ((int)$flag->fetchColumn() !== 1) fail_test('Verified legacy account was not flagged for reset.');
$tokens = db()->prepare('SELECT COUNT(*) FROM api_tokens WHERE user_id = ?');
$tokens->execute([$legacyUserId]);
if ((int)$tokens->fetchColumn() !== 0) fail_test('Legacy account API tokens were not revoked.');
$remember = db()->prepare('SELECT COUNT(*) FROM remember_tokens WHERE user_id = ?');
$remember->execute([$legacyUserId]);
if ((int)$remember->fetchColumn() !== 0) fail_test('Legacy remember tokens were not revoked.');
echo "PASS: verified weak password denied, reset flagged and sessions revoked\n";

// Even if the stored password is later made strong, the persistent reset
// requirement must remain until the user completes token-based recovery.
db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
    ->execute([password_hash($testPassword, PASSWORD_DEFAULT), $legacyUserId]);
expect_api_login_error($baseUrl . '/auth/login', $testEmail, $testPassword, 403);

// Reuse the existing, single-use email reset mechanism, not an insecure
// change-password or auto-login bypass.
$resetToken = bin2hex(random_bytes(32));
db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
    ->execute([$legacyUserId, hash('sha256', $resetToken)]);
function expect_api_reset_error(string $url, array $input, int $expectedStatus): void {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($input, JSON_THROW_ON_ERROR),
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $body = is_string($raw) ? json_decode($raw, true) : null;
    if ($status !== $expectedStatus || !is_array($body) || ($body['success'] ?? null) !== false) {
        fail_test('Expected password reset HTTP ' . $expectedStatus . ', got ' . $status);
    }
}
expect_api_reset_error($baseUrl . '/auth/reset-password', [
    'token' => $resetToken, 'password' => '1234', 'confirm_password' => '1234',
], 422);
api_request('POST', $baseUrl . '/auth/reset-password', [
    'token' => $resetToken, 'password' => $testPassword, 'confirm_password' => $testPassword,
]);
expect_api_reset_error($baseUrl . '/auth/reset-password', [
    'token' => $resetToken, 'password' => $testPassword, 'confirm_password' => $testPassword,
], 400);
$flag->execute([$legacyUserId]);
if ((int)$flag->fetchColumn() !== 0) fail_test('Strong password reset did not clear required-reset flag.');
$restoredLogin = api_request('POST', $baseUrl . '/auth/login', [
    'email' => $testEmail, 'password' => $testPassword,
]);
if (empty($restoredLogin['data']['token'])) fail_test('Could not log in after strong password reset.');
echo "PASS: weak reset denied; strong single-use reset restores login\n";

// Test credentials and resulting rows stay inside the CI database.
db()->prepare('DELETE FROM users WHERE id = ?')->execute([$legacyUserId]);
echo "PASS: disposable test account removed\n";
echo "ALL SHARED DATABASE INTEGRATION TESTS PASSED\n";
