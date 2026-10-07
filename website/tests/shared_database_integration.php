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

$health = api_request('GET', $baseUrl . '/health');
if (($health['data']['status'] ?? '') !== 'healthy') fail_test('Health endpoint is not healthy.');
echo "PASS: API health\n";

$login = api_request('POST', $baseUrl . '/auth/login', [
    'email' => 'user@mzansigem.local',
    'password' => 'User@123',
]);
$token = (string)($login['data']['token'] ?? '');
if ($token === '') fail_test('Login did not return a bearer token.');
echo "PASS: email/password login\n";

$me = api_request('GET', $baseUrl . '/auth/me', null, $token);
if (($me['data']['email'] ?? '') !== 'user@mzansigem.local') fail_test('Authenticated user mismatch.');
echo "PASS: authenticated user\n";

$syncName = 'CI Mobile Website Sync';
api_request('PATCH', $baseUrl . '/profile', [
    'full_name' => $syncName,
    'email' => 'user@mzansigem.local',
    'city' => 'Johannesburg',
    'bio' => 'Verified by shared database integration test.',
], $token);

$stmt = db()->prepare('SELECT full_name FROM users WHERE email = ? LIMIT 1');
$stmt->execute(['user@mzansigem.local']);
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
$stmt->execute(['user@mzansigem.local']);
$settings = array_map('intval', $stmt->fetch(PDO::FETCH_NUM) ?: []);
if ($settings !== [0,1,0,1,1,0]) fail_test('Settings did not persist in shared MySQL.');
echo "PASS: settings synchronize through shared MySQL\n";

$like = api_request('POST', $baseUrl . '/gems/1/like', null, $token);
$stmt = db()->prepare('SELECT COUNT(*) FROM gem_likes gl JOIN users u ON u.id=gl.user_id WHERE u.email=? AND gl.gem_id=1');
$stmt->execute(['user@mzansigem.local']);
if ((int)$stmt->fetchColumn() !== (!empty($like['data']['is_liked']) ? 1 : 0)) fail_test('Like state differs between API and MySQL.');
api_request('POST', $baseUrl . '/gems/1/like', null, $token);
echo "PASS: gem like synchronizes through shared MySQL\n";

$save = api_request('POST', $baseUrl . '/gems/1/save', null, $token);
$stmt = db()->prepare('SELECT COUNT(*) FROM saved_gems sg JOIN users u ON u.id=sg.user_id WHERE u.email=? AND sg.gem_id=1');
$stmt->execute(['user@mzansigem.local']);
if ((int)$stmt->fetchColumn() !== (!empty($save['data']['is_saved']) ? 1 : 0)) fail_test('Saved state differs between API and MySQL.');
api_request('POST', $baseUrl . '/gems/1/save', null, $token);
echo "PASS: saved gem synchronizes through shared MySQL\n";

api_request('PATCH', $baseUrl . '/profile', [
    'full_name' => 'Thabo Mkhize',
    'email' => 'user@mzansigem.local',
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

echo "PASS: temporary test changes restored\n";
echo "ALL SHARED DATABASE INTEGRATION TESTS PASSED\n";
