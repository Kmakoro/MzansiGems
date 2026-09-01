<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
    flash('warning', 'Google OAuth is not configured yet. Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET to your environment.');
    redirect('login.php');
}

$state = bin2hex(random_bytes(24));
$_SESSION['google_oauth_state'] = $state;
$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => url('google-callback.php'),
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'select_account',
];
header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;
