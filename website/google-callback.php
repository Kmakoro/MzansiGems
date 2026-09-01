<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$state = (string)($_GET['state'] ?? '');
$code = (string)($_GET['code'] ?? '');
if ($state === '' || $code === '' || !hash_equals($_SESSION['google_oauth_state'] ?? '', $state)) {
    flash('danger', 'Google sign-in could not be verified. Please try again.');
    redirect('login.php');
}
unset($_SESSION['google_oauth_state']);

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_POSTFIELDS => http_build_query([
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => url('google-callback.php'),
        'grant_type' => 'authorization_code',
    ]),
]);
$tokenResponse = curl_exec($ch);
$tokenStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$tokenData = json_decode((string)$tokenResponse, true);
if ($tokenStatus !== 200 || empty($tokenData['access_token'])) {
    flash('danger', 'Google sign-in failed while exchanging the authorization code.');
    redirect('login.php');
}

$ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tokenData['access_token']],
]);
$userResponse = curl_exec($ch);
$userStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$googleUser = json_decode((string)$userResponse, true);
if ($userStatus !== 200 || empty($googleUser['email'])) {
    flash('danger', 'Google did not return a usable email address.');
    redirect('login.php');
}

$email = strtolower(trim((string)$googleUser['email']));
$stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user) {
    if (setting('allow_registration', '1') !== '1') {
        flash('danger', 'New registrations are currently disabled.');
        redirect('login.php');
    }
    $name = trim((string)($googleUser['name'] ?? 'Google User')) ?: 'Google User';
    $stmt = db()->prepare('INSERT INTO users (full_name,email,city,password_hash,email_verified_at) VALUES (?,?,?,?,NOW())');
    $stmt->execute([$name, $email, 'Not specified', password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT)]);
    $id = (int)db()->lastInsertId();
    record_activity($id, 'user_joined', $name . ' joined with Google.');
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
}
if ($user['status'] !== 'active') {
    flash('danger', 'This account is suspended.');
    redirect('login.php');
}
login_user($user, true);
flash('success', 'Signed in with Google.');
redirect('profile.php');
