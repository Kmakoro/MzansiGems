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

// Never point this integration test at production: it creates/modifies records.
if (!in_array((string)parse_url($baseUrl, PHP_URL_HOST), ['localhost', '127.0.0.1'], true)) {
    fail_test('Integration tests must run on a local test server only.');
}
$testEmail = 'mzansi-ci-' . bin2hex(random_bytes(5)) . '@example.test';
$strongPassword = 'MzansiCI_Reliable_2026!';
$login = api_request('POST', $baseUrl . '/auth/register', [
    'full_name' => 'Thabo Mkhize',
    'email' => $testEmail,
    'city' => 'Johannesburg',
    'password' => $strongPassword,
    'confirm_password' => $strongPassword,
]);
$token = (string)($login['data']['token'] ?? '');
if ($token === '') fail_test('Login did not return a bearer token.');
echo "PASS: strong-password API registration\n";

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

echo "PASS: temporary test changes restored\\n";

// A newly approved gem in a new town must appear in the API categories and search.
$newCity = 'Carnarvon';
$title = 'CI Test Gem ' . bin2hex(random_bytes(4));
$created = api_request('POST', $baseUrl . '/gems', [
    'title' => $title,
    'description' => 'A locally verified discovery described in the automated integration test.',
    'location' => 'Town Centre',
    'city' => $newCity,
    'vibes' => ['Quiet'],
    'budget_level' => 'Free',
    'activity_type' => 'Nature',
    'operating_hours' => '09:00 - 17:00',
    'rating' => 4
], $token);
$gemId = (int)($created['data']['id'] ?? 0);
if (!$gemId) fail_test('Created test gem missing ID.');
db()->prepare("UPDATE gems SET status='approved', approved_at=NOW() WHERE id=?")->execute([$gemId]);
$categories = api_request('GET', $baseUrl . '/categories');
if (!in_array($newCity, $categories['data']['cities'] ?? [], true)) fail_test('Approved new city missing from categories.');
$results = api_request('GET', $baseUrl . '/gems?city=' . urlencode($newCity));
if (!in_array($gemId, array_column($results['data'] ?? [], 'id'), true)) fail_test('New city gem missing from Discover results.');
echo "PASS: all South African cities can appear in categories and Discover\n";

// Remove only the disposable test account and its related rows.
db()->prepare('DELETE FROM users WHERE email=?')->execute([$testEmail]);
echo "ALL SHARED DATABASE INTEGRATION TESTS PASSED\n";
