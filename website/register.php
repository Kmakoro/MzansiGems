<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
if (is_logged_in()) redirect('profile.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    remember_old_input($_POST);
    if (setting('allow_registration', '1') !== '1') $errors[] = 'New registrations are currently disabled.';

    $name = trim((string)($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $city = trim((string)($_POST['city'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $cities = ['Cape Town','Johannesburg','Durban','Pretoria','Gqeberha','Bloemfontein','Polokwane','Mbombela','East London','Kimberley'];

    if (mb_strlen($name) < 2) $errors[] = 'Full name must contain at least 2 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!in_array($city, $cities, true)) $errors[] = 'Select a valid South African city.';
    if (strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $check = db()->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'An account already exists with that email address.';
        } else {
            $requiresVerification = setting('require_email_verification', '0') === '1';
            $stmt = db()->prepare('INSERT INTO users (full_name,email,city,password_hash,email_verified_at) VALUES (?,?,?,?,?)');
            $stmt->execute([$name,$email,$city,password_hash($password, PASSWORD_DEFAULT),$requiresVerification ? null : date('Y-m-d H:i:s')]);
            $userId = (int)db()->lastInsertId();
            record_activity($userId, 'user_joined', $name . ' joined the community.');
            clear_old_input();
            if ($requiresVerification) {
                $token = create_email_verification_token($userId);
                $verificationLink = url('verify-email.php?token=' . urlencode($token));
                $appVerificationLink = 'mzansigem://auth/verify-email?token=' . urlencode($token);
                $_SESSION['verification_email'] = $email;
                if (APP_ENV === 'development') {
                    $_SESSION['dev_verification_link'] = $verificationLink;
                }
                send_app_email($email, 'Verify your Mzansi Gem email', "Welcome to Mzansi Gem!

Verify your email address using either link:

Website:
{$verificationLink}

Android app:
{$appVerificationLink}

These links expire in 24 hours.");
                flash('success', 'Your account was created. Verify your email address before signing in.');
                redirect('verification-sent.php');
            }
            $stmt = db()->prepare('SELECT id,full_name,email,city,bio,role,status,level,points,password_hash,email_verified_at,notify_new_gems,notify_comments,notify_likes_saves,personalized_recommendations,show_saved_gems,show_activity_status,created_at,updated_at FROM users WHERE id = ?'); $stmt->execute([$userId]);
            login_user($stmt->fetch());
            flash('success', 'Welcome to Mzansi Gem! Your account is ready.');
            redirect('profile.php');
        }
    }
}
$pageTitle = 'Create your account'; $bodyClass = 'auth-body';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-shell"><div class="auth-card">
    <div class="auth-icon">♙</div><h1>Join the Community</h1><p class="muted">Start discovering with Mzansi Gem today</p>
    <?php if ($errors): ?><div class="toast toast-danger" style="position:static;margin-bottom:14px"><span><?= e(implode(' ', $errors)) ?></span></div><?php endif; ?>
    <form method="post" novalidate><?= csrf_field() ?>
        <div class="form-group"><label for="full_name">Full Name</label><input class="form-control" id="full_name" name="full_name" value="<?= old('full_name') ?>" placeholder="Your name" required minlength="2"></div>
        <div class="form-group"><label for="email">Email Address</label><input class="form-control" id="email" name="email" type="email" value="<?= old('email') ?>" placeholder="you@example.com" required></div>
        <div class="form-group"><label for="city">Your City</label><select class="form-control" id="city" name="city" required><option value="">Select a city</option><?php foreach (['Cape Town','Johannesburg','Durban','Pretoria','Gqeberha','Bloemfontein','Polokwane','Mbombela','East London','Kimberley'] as $city): ?><option <?= old('city') === e($city) ? 'selected' : '' ?>><?= e($city) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label for="password">Password</label><input class="form-control" id="password" name="password" type="password" placeholder="Create a password" required minlength="8"></div>
        <div class="form-group"><label for="confirm_password">Confirm Password</label><input class="form-control" id="confirm_password" name="confirm_password" type="password" placeholder="Confirm your password" required></div>
        <button class="btn btn-primary btn-block" type="submit">Create Account</button>
    </form>
    <div class="auth-separator">or</div><a class="google-btn" href="<?= url('google-auth.php') ?>">◉ Continue with Google</a>
    <p class="auth-foot">Already have an account? <a href="<?= url('login.php') ?>">Sign in</a></p>
</div></div>
<?php clear_old_input(); require __DIR__ . '/includes/footer.php'; ?>
