<?php
declare(strict_types=1);

function url(string $path = ''): string
{
    return APP_URL . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page, and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($flashes) ? $flashes : [];
}

function remember_old_input(array $input): void
{
    unset($input['password'], $input['confirm_password'], $input['csrf_token']);
    $_SESSION['old'] = $input;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

function clear_old_input(): void
{
    unset($_SESSION['old']);
}

function current_user(): ?array
{
    static $cachedId = null;
    static $cachedUser = null;

    $id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    if (!$id) {
        return null;
    }
    if ($cachedId === $id && is_array($cachedUser)) {
        return $cachedUser;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] !== 'active') {
        unset($_SESSION['user_id']);
        return null;
    }

    $cachedId = $id;
    $cachedUser = $user;
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Please sign in to continue.');
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? url();
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Administrator access is required.');
    }
}

function login_user(array $user, bool $remember = false): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];

    if ($remember) {
        $selector = bin2hex(random_bytes(8));
        $validator = bin2hex(random_bytes(32));
        $hash = hash('sha256', $validator);
        $expires = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');
        $stmt = db()->prepare('INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([(int)$user['id'], $selector, $hash, $expires]);
        setcookie('remember_gems', $selector . ':' . $validator, [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function attempt_remember_login(): void
{
    if (!empty($_SESSION['user_id']) || empty($_COOKIE['remember_gems'])) {
        return;
    }
    [$selector, $validator] = array_pad(explode(':', (string)$_COOKIE['remember_gems'], 2), 2, null);
    if (!$selector || !$validator) {
        return;
    }
    $stmt = db()->prepare('SELECT rt.*, u.* FROM remember_tokens rt JOIN users u ON u.id = rt.user_id WHERE rt.selector = ? AND rt.expires_at > NOW() LIMIT 1');
    $stmt->execute([$selector]);
    $row = $stmt->fetch();
    if ($row && hash_equals((string)$row['token_hash'], hash('sha256', $validator)) && $row['status'] === 'active') {
        $_SESSION['user_id'] = (int)$row['user_id'];
        session_regenerate_id(true);
    }
}

function logout_user(): void
{
    if (!empty($_COOKIE['remember_gems'])) {
        [$selector] = explode(':', (string)$_COOKIE['remember_gems'], 2);
        if ($selector) {
            $stmt = db()->prepare('DELETE FROM remember_tokens WHERE selector = ?');
            $stmt->execute([$selector]);
        }
        setcookie('remember_gems', '', time() - 3600, '/');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function send_app_email(string $to, string $subject, string $body): bool
{
    if (MAIL_FROM === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $headers = [
        'From: ' . MAIL_FROM,
        'Reply-To: ' . MAIL_FROM,
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    return @mail($to, $subject, $body, implode("
", $headers));
}

function legacy_demo_email_for(string $email): ?string
{
    return match ($email) {
        'admin@mzansigem.local' => 'admin@hiddengems.local',
        'user@mzansigem.local' => 'user@hiddengems.local',
        default => null,
    };
}

function create_email_verification_token(int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $stmt = db()->prepare('DELETE FROM email_verification_tokens WHERE user_id = ?');
    $stmt->execute([$userId]);
    $stmt = db()->prepare('INSERT INTO email_verification_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))');
    $stmt->execute([$userId, hash('sha256', $token)]);
    return $token;
}

function setting(string $key, string $default = ''): string
{
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    $resolved = $value === false ? $default : (string)$value;
    if ($key === 'site_name' && strtolower((string)preg_replace('/[^a-z]+/i', '', $resolved)) === 'hiddengems') {
        $resolved = APP_NAME;
    }
    return $cache[$key] = $resolved;
}

function gem_images(int $gemId): array
{
    $stmt = db()->prepare('SELECT * FROM gem_images WHERE gem_id = ? ORDER BY sort_order, id');
    $stmt->execute([$gemId]);
    return $stmt->fetchAll();
}

function gem_image_url(array $gem, ?array $images = null): string
{
    if (!empty($gem['cover_image'])) {
        return str_starts_with((string)$gem['cover_image'], 'http') ? (string)$gem['cover_image'] : url((string)$gem['cover_image']);
    }
    $images ??= gem_images((int)$gem['id']);
    if (!empty($images[0]['image_path'])) {
        $path = (string)$images[0]['image_path'];
        return str_starts_with($path, 'http') ? $path : url($path);
    }
    return asset('images/gem-placeholder.svg');
}

function tag_list(?string $csv): array
{
    if (!$csv) {
        return [];
    }
    return array_values(array_filter(array_map('trim', explode(',', $csv))));
}

function is_gem_saved(int $gemId, ?int $userId = null): bool
{
    $userId ??= (int)(current_user()['id'] ?? 0);
    if (!$userId) {
        return false;
    }
    $stmt = db()->prepare('SELECT 1 FROM saved_gems WHERE user_id = ? AND gem_id = ?');
    $stmt->execute([$userId, $gemId]);
    return (bool)$stmt->fetchColumn();
}

function is_gem_liked(int $gemId, ?int $userId = null): bool
{
    $userId ??= (int)(current_user()['id'] ?? 0);
    if (!$userId) {
        return false;
    }
    $stmt = db()->prepare('SELECT 1 FROM gem_likes WHERE user_id = ? AND gem_id = ?');
    $stmt->execute([$userId, $gemId]);
    return (bool)$stmt->fetchColumn();
}

function is_review_liked(int $reviewId, ?int $userId = null): bool
{
    $userId ??= (int)(current_user()['id'] ?? 0);
    if (!$userId) {
        return false;
    }
    $stmt = db()->prepare('SELECT 1 FROM review_likes WHERE user_id = ? AND review_id = ?');
    $stmt->execute([$userId, $reviewId]);
    return (bool)$stmt->fetchColumn();
}

function award_points(int $userId, int $points): void
{
    $stmt = db()->prepare('UPDATE users SET points = GREATEST(0, points + ?), level = GREATEST(1, FLOOR((points + ?) / 250) + 1) WHERE id = ?');
    $stmt->execute([$points, $points, $userId]);
}

function recalculate_gem_rating(int $gemId): void
{
    $stmt = db()->prepare("SELECT COALESCE(AVG(rating), 0), COUNT(*) FROM reviews WHERE gem_id = ? AND status = 'approved'");
    $stmt->execute([$gemId]);
    [$average, $count] = $stmt->fetch(PDO::FETCH_NUM);
    $update = db()->prepare('UPDATE gems SET average_rating = ?, review_count = ? WHERE id = ?');
    $update->execute([round((float)$average, 2), (int)$count, $gemId]);
}

function record_activity(?int $userId, string $type, string $description): void
{
    $stmt = db()->prepare('INSERT INTO activity_log (user_id, activity_type, description) VALUES (?, ?, ?)');
    $stmt->execute([$userId ?: null, $type, mb_substr($description, 0, 255)]);
}

function validate_image_uploads(array $files, int $max = MAX_GEM_IMAGES): array
{
    $normalized = [];
    if (!isset($files['name']) || !is_array($files['name'])) {
        return $normalized;
    }
    $count = count($files['name']);
    if ($count > $max) {
        throw new RuntimeException("You can upload a maximum of {$max} images.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    for ($i = 0; $i < $count; $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if (($files['error'][$i] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('One of the images could not be uploaded.');
        }
        if ((int)$files['size'][$i] > MAX_UPLOAD_BYTES) {
            throw new RuntimeException('Each image must be 5 MB or smaller.');
        }
        $mime = $finfo->file($files['tmp_name'][$i]);
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Images must be JPEG, PNG, or WEBP files.');
        }
        $normalized[] = [
            'tmp_name' => $files['tmp_name'][$i],
            'extension' => $allowed[$mime],
        ];
    }
    return $normalized;
}

function store_uploaded_images(array $uploads): array
{
    $directory = dirname(__DIR__) . '/uploads/gems';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('The upload directory could not be created.');
    }
    $paths = [];
    foreach ($uploads as $upload) {
        $name = bin2hex(random_bytes(16)) . '.' . $upload['extension'];
        $destination = $directory . '/' . $name;
        if (!move_uploaded_file($upload['tmp_name'], $destination)) {
            throw new RuntimeException('An uploaded image could not be saved.');
        }
        $paths[] = 'uploads/gems/' . $name;
    }
    return $paths;
}

function render_stars(float $rating): string
{
    $rounded = (int)round($rating);
    return str_repeat('★', max(0, min(5, $rounded))) . str_repeat('☆', max(0, 5 - $rounded));
}

function query_gems(string $where = "g.status = 'approved'", array $params = [], string $order = 'g.created_at DESC', int $limit = 12): array
{
    $sql = "SELECT g.*, u.full_name AS contributor_name,
            (SELECT COUNT(*) FROM saved_gems sg WHERE sg.gem_id = g.id) AS saves,
            (SELECT COUNT(*) FROM gem_likes gl WHERE gl.gem_id = g.id) AS likes
            FROM gems g JOIN users u ON u.id = g.user_id
            WHERE {$where} ORDER BY {$order} LIMIT " . max(1, $limit);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function safe_return_path(string $fallback = 'index.php'): string
{
    $return = (string)($_POST['return_to'] ?? $_GET['return_to'] ?? $fallback);
    if (str_starts_with($return, 'http') || str_contains($return, "\n") || str_contains($return, "\r")) {
        return $fallback;
    }
    return ltrim($return, '/');
}
