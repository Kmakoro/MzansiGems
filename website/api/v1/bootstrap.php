<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

$allowedOrigin = (string)(getenv('API_ALLOWED_ORIGIN') ?: '*');
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

set_exception_handler(static function (Throwable $exception): void {
    $message = APP_ENV === 'development' ? $exception->getMessage() : 'An unexpected server error occurred.';
    api_error($message, 500);
});

function api_response(mixed $data = null, string $message = 'OK', int $status = 200, array $meta = []): never
{
    http_response_code($status);
    $payload = ['success' => $status < 400, 'message' => $message];
    if ($data !== null) {
        $payload['data'] = $data;
    }
    if ($meta !== []) {
        $payload['meta'] = $meta;
    }
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function api_error(string $message, int $status = 400, array $errors = []): never
{
    http_response_code($status);
    $payload = ['success' => false, 'message' => $message];
    if ($errors !== []) {
        $payload['errors'] = $errors;
    }
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function api_input(): array
{
    static $input;
    if (is_array($input)) {
        return $input;
    }
    $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        if ($raw !== '' && !is_array($decoded)) {
            api_error('The JSON request body is invalid.', 400);
        }
        return $input = is_array($decoded) ? $decoded : [];
    }
    if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['PUT', 'PATCH', 'DELETE'], true)) {
        $raw = file_get_contents('php://input') ?: '';
        parse_str($raw, $parsed);
        return $input = is_array($parsed) ? $parsed : [];
    }
    return $input = $_POST;
}

function api_route(): string
{
    $uri = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $marker = '/api/v1';
    $position = strpos($uri, $marker);
    if ($position !== false) {
        $uri = substr($uri, $position + strlen($marker));
    }
    return '/' . trim($uri, '/');
}

function api_method(): string
{
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
    return is_string($override) && $override !== '' ? strtoupper($override) : $method;
}

function api_bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($header === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (preg_match('/^Bearer\s+(.+)$/i', trim((string)$header), $matches)) {
        return trim($matches[1]);
    }
    return null;
}

function api_token_record(): ?array
{
    static $loaded = false;
    static $record = null;
    if ($loaded) {
        return $record;
    }
    $loaded = true;
    $plain = api_bearer_token();
    if (!$plain) {
        return null;
    }
    $hash = hash('sha256', $plain);
    $stmt = db()->prepare("SELECT t.id AS token_id, t.user_id, t.expires_at, u.*
        FROM api_tokens t
        JOIN users u ON u.id = t.user_id
        WHERE t.token_hash = ? AND (t.expires_at IS NULL OR t.expires_at > NOW())
        LIMIT 1");
    $stmt->execute([$hash]);
    $record = $stmt->fetch() ?: null;
    if ($record && $record['status'] === 'active') {
        db()->prepare('UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?')->execute([(int)$record['token_id']]);
        return $record;
    }
    return $record = null;
}

function api_user(): ?array
{
    $record = api_token_record();
    return $record ?: null;
}

function api_require_user(): array
{
    $user = api_user();
    if (!$user) {
        api_error('Authentication is required.', 401);
    }
    return $user;
}

function api_require_admin(): array
{
    $user = api_require_user();
    if (($user['role'] ?? '') !== 'admin') {
        api_error('Administrator access is required.', 403);
    }
    return $user;
}

function api_issue_token(int $userId, string $name = 'Android app', int $days = 90): string
{
    $plain = bin2hex(random_bytes(32));
    $stmt = db()->prepare('INSERT INTO api_tokens (user_id, token_hash, name, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))');
    $stmt->execute([$userId, hash('sha256', $plain), mb_substr($name, 0, 100), $days]);
    return $plain;
}

function api_revoke_current_token(): void
{
    $record = api_token_record();
    if ($record) {
        db()->prepare('DELETE FROM api_tokens WHERE id = ?')->execute([(int)$record['token_id']]);
    }
}

function api_bool(mixed $value): bool
{
    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
}

function api_clean_tags(mixed $value, int $max = 3): array
{
    $items = is_array($value) ? $value : explode(',', (string)$value);
    $items = array_values(array_unique(array_filter(array_map(static fn($item) => trim((string)$item), $items))));
    return array_slice($items, 0, $max);
}

function api_public_user(array $user): array
{
    return [
        'id' => (int)$user['id'],
        'full_name' => (string)$user['full_name'],
        'email' => (string)$user['email'],
        'city' => (string)$user['city'],
        'bio' => (string)($user['bio'] ?? ''),
        'role' => (string)$user['role'],
        'status' => (string)$user['status'],
        'level' => (int)$user['level'],
        'points' => (int)$user['points'],
        'email_verified' => !empty($user['email_verified_at']),
        'preferences' => [
            'notify_new_gems' => (bool)$user['notify_new_gems'],
            'notify_comments' => (bool)$user['notify_comments'],
            'notify_likes_saves' => (bool)$user['notify_likes_saves'],
            'personalized_recommendations' => (bool)$user['personalized_recommendations'],
            'show_saved_gems' => (bool)$user['show_saved_gems'],
            'show_activity_status' => (bool)$user['show_activity_status'],
        ],
        'created_at' => (string)$user['created_at'],
        'updated_at' => (string)$user['updated_at'],
    ];
}

function api_request_origin(): string
{
    $forwardedProto = trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]);
    $scheme = in_array($forwardedProto, ['http', 'https'], true)
        ? $forwardedProto
        : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');

    $forwardedHost = trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''))[0]);
    $host = $forwardedHost !== '' ? $forwardedHost : (string)($_SERVER['HTTP_HOST'] ?? 'localhost');

    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/api/v1/index.php'));
    $marker = '/api/v1';
    $position = strpos($script, $marker);
    $basePath = $position === false ? '' : substr($script, 0, $position);

    return $scheme . '://' . $host . rtrim($basePath, '/');
}

function api_image_url(?string $path): ?string
{
    if (!$path) {
        return null;
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return api_request_origin() . '/' . ltrim($path, '/');
}

function api_gem(array $gem, ?int $viewerId = null, bool $withDetails = false): array
{
    $images = [];
    if ($withDetails || !empty($gem['images_json']) || !empty($gem['images_csv'])) {
        if (!empty($gem['images_json'])) {
            $decoded = json_decode((string)$gem['images_json'], true);
            if (is_array($decoded)) {
                $images = array_values(array_filter(array_map('api_image_url', $decoded)));
            }
        } elseif (!empty($gem['images_csv'])) {
            $images = array_values(array_filter(array_map('api_image_url', explode('||', (string)$gem['images_csv']))));
        } else {
            $stmt = db()->prepare('SELECT image_path FROM gem_images WHERE gem_id = ? ORDER BY sort_order, id');
            $stmt->execute([(int)$gem['id']]);
            $images = array_values(array_filter(array_map(static fn($row) => api_image_url($row['image_path'] ?? null), $stmt->fetchAll())));
        }
    }
    $cover = api_image_url($gem['cover_image'] ?? null) ?: ($images[0] ?? api_image_url('assets/images/gem-placeholder.svg'));
    $viewerId = $viewerId ?: null;
    $saved = isset($gem['viewer_saved']) ? (bool)$gem['viewer_saved'] : false;
    $liked = isset($gem['viewer_liked']) ? (bool)$gem['viewer_liked'] : false;
    if ($viewerId && !isset($gem['viewer_saved'])) {
        $saved = is_gem_saved((int)$gem['id'], $viewerId);
        $liked = is_gem_liked((int)$gem['id'], $viewerId);
    }
    $payload = [
        'id' => (int)$gem['id'],
        'user_id' => (int)$gem['user_id'],
        'title' => (string)$gem['title'],
        'slug' => (string)$gem['slug'],
        'description' => (string)$gem['description'],
        'location' => (string)$gem['location'],
        'city' => (string)$gem['city'],
        'vibes' => tag_list($gem['vibes'] ?? ''),
        'budget_level' => (string)$gem['budget_level'],
        'activity_type' => (string)$gem['activity_type'],
        'operating_hours' => (string)$gem['operating_hours'],
        'cover_image' => $cover,
        'average_rating' => (float)$gem['average_rating'],
        'review_count' => (int)$gem['review_count'],
        'like_count' => (int)($gem['like_count'] ?? 0),
        'save_count' => (int)($gem['save_count'] ?? $gem['saves'] ?? 0),
        'is_liked' => $liked,
        'is_saved' => $saved,
        'status' => (string)$gem['status'],
        'created_at' => (string)$gem['created_at'],
        'updated_at' => (string)$gem['updated_at'],
    ];
    if ($withDetails) {
        $payload['images'] = $images !== [] ? $images : [$cover];
        $payload['submitted_by'] = isset($gem['submitter_name']) ? [
            'id' => (int)$gem['user_id'],
            'full_name' => (string)$gem['submitter_name'],
        ] : null;
    }
    return $payload;
}

function api_review(array $review, ?int $viewerId = null): array
{
    $liked = isset($review['viewer_liked']) ? (bool)$review['viewer_liked'] : false;
    if ($viewerId && !isset($review['viewer_liked'])) {
        $liked = is_review_liked((int)$review['id'], $viewerId);
    }
    return [
        'id' => (int)$review['id'],
        'gem_id' => (int)$review['gem_id'],
        'user_id' => (int)$review['user_id'],
        'reviewer_name' => (string)($review['reviewer_name'] ?? $review['full_name'] ?? ''),
        'gem_title' => (string)($review['gem_title'] ?? ''),
        'rating' => (int)$review['rating'],
        'comment' => (string)($review['comment'] ?? ''),
        'vibes' => tag_list($review['vibes'] ?? ''),
        'status' => (string)$review['status'],
        'is_edited' => (bool)$review['is_edited'],
        'like_count' => (int)($review['like_count'] ?? 0),
        'is_liked' => $liked,
        'created_at' => (string)$review['created_at'],
        'updated_at' => (string)$review['updated_at'],
    ];
}

function api_find_gem(int $id, bool $allowOwnerPending = false): array
{
    $user = api_user();
    $sql = "SELECT g.*, u.full_name AS submitter_name,
        (SELECT COUNT(*) FROM gem_likes gl WHERE gl.gem_id = g.id) AS like_count,
        (SELECT COUNT(*) FROM saved_gems sg WHERE sg.gem_id = g.id) AS save_count,
        (SELECT GROUP_CONCAT(gi.image_path SEPARATOR '||') FROM gem_images gi WHERE gi.gem_id = g.id) AS images_csv
        FROM gems g JOIN users u ON u.id = g.user_id WHERE g.id = ?";
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    $gem = $stmt->fetch();
    if (!$gem) {
        api_error('Mzansi Gem not found.', 404);
    }
    $canSee = $gem['status'] === 'approved' || (($user['role'] ?? '') === 'admin') || ($allowOwnerPending && $user && (int)$gem['user_id'] === (int)$user['id']);
    if (!$canSee) {
        api_error('Mzansi Gem not found.', 404);
    }
    return $gem;
}

function api_validate_gem(array $input, bool $partial = false): array
{
    $errors = [];
    $title = trim((string)($input['title'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    $location = trim((string)($input['location'] ?? ''));
    $city = trim((string)($input['city'] ?? ''));
    $vibes = api_clean_tags($input['vibes'] ?? []);
    $budget = trim((string)($input['budget_level'] ?? ''));
    $activity = trim((string)($input['activity_type'] ?? ''));
    $hours = trim((string)($input['operating_hours'] ?? 'All Day'));

    if (!$partial || array_key_exists('title', $input)) {
        if (mb_strlen($title) < 3 || mb_strlen($title) > 100) $errors['title'] = 'Title must be 3 to 100 characters.';
    }
    if (!$partial || array_key_exists('description', $input)) {
        if (mb_strlen($description) < 20 || mb_strlen($description) > 500) $errors['description'] = 'Description must be 20 to 500 characters.';
    }
    if ((!$partial || array_key_exists('location', $input)) && $location === '') $errors['location'] = 'Location is required.';
    if ((!$partial || array_key_exists('city', $input)) && $city === '') $errors['city'] = 'City is required.';
    if ((!$partial || array_key_exists('vibes', $input)) && $vibes === []) $errors['vibes'] = 'Select at least one vibe.';
    if ((!$partial || array_key_exists('budget_level', $input)) && !in_array($budget, ['Free','Budget','Mid-range','Premium'], true)) $errors['budget_level'] = 'Choose a valid budget level.';
    if ((!$partial || array_key_exists('activity_type', $input)) && !in_array($activity, ['Food','Fitness','Study','Nature','Nightlife','Culture'], true)) $errors['activity_type'] = 'Choose a valid activity type.';
    if ($errors !== []) api_error('Please correct the highlighted fields.', 422, $errors);

    return compact('title', 'description', 'location', 'city', 'vibes', 'budget', 'activity', 'hours');
}

function api_slug(string $title, ?int $ignoreId = null): string
{
    $base = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-')) ?: 'mzansi-gem';
    $slug = $base;
    $counter = 2;
    while (true) {
        $sql = 'SELECT id FROM gems WHERE slug = ?' . ($ignoreId ? ' AND id <> ?' : '') . ' LIMIT 1';
        $stmt = db()->prepare($sql);
        $params = [$slug];
        if ($ignoreId) $params[] = $ignoreId;
        $stmt->execute($params);
        if (!$stmt->fetchColumn()) return $slug;
        $slug = $base . '-' . $counter++;
    }
}

function api_store_uploaded_files(array $files): array
{
    if (!isset($files['name'])) return [];
    $uploads = validate_image_uploads($files, MAX_GEM_IMAGES);
    return store_uploaded_images($uploads);
}
