<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/controllers.php';

$route = api_route();
$method = api_method();

if ($route === '/' && $method === 'GET') {
    api_response([
        'name' => 'Mzansi Gem API',
        'version' => '1.0.0',
        'documentation' => api_request_origin() . '/api/v1/openapi.yaml',
    ], 'Mzansi Gem API is online.');
}
if ($route === '/health' && $method === 'GET') {
    db()->query('SELECT 1');
    api_response(['status' => 'healthy', 'time' => date(DATE_ATOM)]);
}

$routes = [
    ['POST', '#^/auth/register$#', 'api_auth_register'],
    ['POST', '#^/auth/login$#', 'api_auth_login'],
    ['POST', '#^/auth/firebase$#', 'api_auth_firebase'],
    ['POST', '#^/auth/forgot-password$#', 'api_auth_forgot_password'],
    ['POST', '#^/auth/logout$#', 'api_auth_logout'],
    ['GET', '#^/auth/me$#', 'api_auth_me'],
    ['POST', '#^/auth/verify-email$#', 'api_auth_verify_email'],
    ['POST', '#^/auth/reset-password$#', 'api_auth_reset_password'],
    ['GET', '#^/categories$#', 'api_categories'],
    ['GET', '#^/gems$#', 'api_gems_index'],
    ['POST', '#^/gems$#', 'api_gems_create'],
    ['GET', '#^/gems/random$#', 'api_gems_random'],
    ['GET', '#^/gems/(\d+)$#', 'api_gems_show'],
    ['PUT|PATCH|POST', '#^/gems/(\d+)$#', 'api_gems_update'],
    ['DELETE', '#^/gems/(\d+)$#', 'api_gems_delete'],
    ['POST', '#^/gems/(\d+)/like$#', 'api_gems_toggle_like'],
    ['POST', '#^/gems/(\d+)/save$#', 'api_gems_toggle_save'],
    ['PUT|PATCH|POST', '#^/gems/(\d+)/note$#', 'api_gems_save_note'],
    ['POST', '#^/gems/(\d+)/reviews$#', 'api_reviews_create'],
    ['PUT|PATCH', '#^/reviews/(\d+)$#', 'api_reviews_update'],
    ['DELETE', '#^/reviews/(\d+)$#', 'api_reviews_delete'],
    ['POST', '#^/reviews/(\d+)/like$#', 'api_reviews_toggle_like'],
    ['GET', '#^/saved$#', 'api_saved_index'],
    ['GET', '#^/profile$#', 'api_profile_show'],
    ['PUT|PATCH', '#^/profile$#', 'api_profile_update'],
    ['PUT|PATCH', '#^/profile/settings$#', 'api_profile_settings'],
    ['PUT|PATCH', '#^/profile/password$#', 'api_profile_password'],
    ['DELETE', '#^/profile$#', 'api_profile_delete'],
    ['GET', '#^/profile/gems$#', 'api_profile_gems'],
    ['GET', '#^/profile/reviews$#', 'api_profile_reviews'],
    ['POST', '#^/reports$#', 'api_reports_create'],
    ['GET', '#^/admin/dashboard$#', 'api_admin_dashboard'],
    ['GET', '#^/admin/users$#', 'api_admin_users'],
    ['PUT|PATCH', '#^/admin/users/(\d+)$#', 'api_admin_user_update'],
    ['GET', '#^/admin/moderation$#', 'api_admin_moderation'],
    ['PUT|PATCH', '#^/admin/gems/(\d+)$#', 'api_admin_gem_moderate'],
    ['PUT|PATCH', '#^/admin/reviews/(\d+)$#', 'api_admin_review_moderate'],
    ['PUT|PATCH', '#^/admin/reports/(\d+)$#', 'api_admin_report_update'],
    ['GET', '#^/admin/analytics$#', 'api_admin_analytics'],
    ['GET', '#^/admin/settings$#', 'api_admin_settings_get'],
    ['PUT|PATCH', '#^/admin/settings$#', 'api_admin_settings_update'],
];

$foundPath = false;
foreach ($routes as [$allowedMethods, $pattern, $handler]) {
    if (!preg_match($pattern, $route, $matches)) {
        continue;
    }
    $foundPath = true;
    if (!in_array($method, explode('|', $allowedMethods), true)) {
        continue;
    }
    array_shift($matches);
    $arguments = array_map(static fn($value) => ctype_digit((string)$value) ? (int)$value : $value, $matches);
    $handler(...$arguments);
}

if ($foundPath) {
    api_error('Method not allowed.', 405);
}

api_error('API route not found.', 404);
