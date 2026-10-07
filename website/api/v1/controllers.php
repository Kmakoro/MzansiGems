<?php
declare(strict_types=1);

function api_auth_register(): never
{
    if (setting('allow_registration', '1') !== '1') {
        api_error('New registrations are currently disabled.', 403);
    }
    $input = api_input();
    $name = trim((string)($input['full_name'] ?? ''));
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $city = trim((string)($input['city'] ?? ''));
    $cities = ['Cape Town','Johannesburg','Durban','Pretoria','Gqeberha','Bloemfontein','Polokwane','Mbombela','East London','Kimberley'];
    $password = (string)($input['password'] ?? '');
    $confirmation = (string)($input['confirm_password'] ?? $password);
    $errors = [];
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors['full_name'] = 'Full name must be 2 to 100 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if ($city === '' || !in_array($city, $cities, true)) $errors['city'] = 'Select a valid South African city.';
    if (mb_strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    if ($password !== $confirmation) $errors['confirm_password'] = 'Passwords do not match.';
    $stmt = db()->prepare('SELECT 1 FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetchColumn()) $errors['email'] = 'An account with this email already exists.';
    if ($errors !== []) api_error('Please correct the highlighted fields.', 422, $errors);

    $requiresVerification = setting('require_email_verification', '0') === '1';
    $stmt = db()->prepare('INSERT INTO users (full_name,email,city,password_hash,email_verified_at) VALUES (?,?,?,?,?)');
    $stmt->execute([$name, $email, $city, password_hash($password, PASSWORD_DEFAULT), $requiresVerification ? null : date('Y-m-d H:i:s')]);
    $userId = (int)db()->lastInsertId();
    record_activity($userId, 'user_joined', $name . ' joined the community.');
    $stmt = db()->prepare('SELECT id,full_name,email,city,bio,role,status,level,points,password_hash,email_verified_at,notify_new_gems,notify_comments,notify_likes_saves,personalized_recommendations,show_saved_gems,show_activity_status,created_at,updated_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($requiresVerification) {
        $verificationToken = create_email_verification_token($userId);
        $webVerifyLink = api_request_origin() . '/verify-email.php?token=' . urlencode($verificationToken);
        $appVerifyLink = 'mzansigem://auth/verify-email?token=' . urlencode($verificationToken);
        send_app_email(
            $email,
            'Verify your Mzansi Gem email',
            "Welcome to Mzansi Gem!\n\nWebsite:\n{$webVerifyLink}\n\nAndroid app:\n{$appVerifyLink}\n\nThese links expire in 24 hours."
        );
    }

    if ($requiresVerification) {
        api_response(
            ['token' => '', 'token_type' => 'Bearer', 'user' => api_public_user($user)],
            'Account created. Please verify your email address before signing in.',
            201
        );
    }

    $token = api_issue_token($userId, (string)($input['device_name'] ?? 'Android app'));
    api_response(['token' => $token, 'token_type' => 'Bearer', 'user' => api_public_user($user)], 'Account created successfully.', 201);
}

function api_auth_login(): never
{
    $input = api_input();
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $password = (string)($input['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        api_error('Email and password are required.', 422);
    }
    $stmt = db()->prepare('SELECT id,full_name,email,city,bio,role,status,level,points,password_hash,email_verified_at,notify_new_gems,notify_comments,notify_likes_saves,personalized_recommendations,show_saved_gems,show_activity_status,created_at,updated_at FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        api_error('The email address or password is incorrect.', 401);
    }
    if ($user['status'] !== 'active') {
        api_error('This account is suspended.', 403);
    }
    if (setting('require_email_verification', '0') === '1' && empty($user['email_verified_at'])) {
        api_error('Please verify your email address before signing in.', 403);
    }
    $token = api_issue_token((int)$user['id'], (string)($input['device_name'] ?? 'Android app'));
    api_response(['token' => $token, 'token_type' => 'Bearer', 'user' => api_public_user($user)], 'Welcome back!');
}

function api_auth_firebase(): never
{
    if (FIREBASE_WEB_API_KEY === '') {
        api_error('Firebase authentication is not configured on the server.', 503);
    }

    $input = api_input();
    $idToken = trim((string)($input['id_token'] ?? ''));
    if ($idToken === '') {
        api_error('Firebase ID token is required.', 422);
    }

    $firebaseUser = api_verify_firebase_id_token($idToken);
    $firebaseUid = trim((string)($firebaseUser['localId'] ?? ''));
    $email = strtolower(trim((string)($firebaseUser['email'] ?? '')));
    $name = trim((string)($firebaseUser['displayName'] ?? 'Google User'));

    if ($firebaseUid === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        api_error('Google sign-in did not return a usable account.', 401);
    }
    if (($firebaseUser['emailVerified'] ?? false) !== true) {
        api_error('Your Google email address is not verified.', 403);
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE firebase_uid = ? LIMIT 1');
    $stmt->execute([$firebaseUid]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && !empty($user['firebase_uid']) && !hash_equals((string)$user['firebase_uid'], $firebaseUid)) {
            api_error('This email address is already linked to another Google account.', 409);
        }

        if ($user) {
            db()->prepare('UPDATE users SET firebase_uid = ?, email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?')
                ->execute([$firebaseUid, (int)$user['id']]);
        } else {
            if (setting('allow_registration', '1') !== '1') {
                api_error('New registrations are currently disabled.', 403);
            }
            $safeName = mb_substr($name !== '' ? $name : 'Google User', 0, 100);
            $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            $stmt = db()->prepare(
                'INSERT INTO users (full_name,email,firebase_uid,city,password_hash,email_verified_at) VALUES (?,?,?,?,?,NOW())'
            );
            $stmt->execute([$safeName, $email, $firebaseUid, 'Not specified', $randomPassword]);
            $userId = (int)db()->lastInsertId();
            record_activity($userId, 'user_joined', $safeName . ' joined with Google on Android.');
        }

        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    }

    if (!$user || $user['status'] !== 'active') {
        api_error('This account is suspended or unavailable.', 403);
    }

    $token = api_issue_token((int)$user['id'], (string)($input['device_name'] ?? 'Mzansi Gem Android - Google'));
    record_activity((int)$user['id'], 'mobile_login', $user['full_name'] . ' signed in with Google on Android.');

    api_response(
        ['token' => $token, 'token_type' => 'Bearer', 'user' => api_public_user($user)],
        'Signed in with Google successfully.'
    );
}

function api_verify_firebase_id_token(string $idToken): array
{
    if (!function_exists('curl_init')) {
        api_error('The PHP cURL extension is required for Firebase authentication.', 500);
    }

    $url = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . rawurlencode(FIREBASE_WEB_API_KEY);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['idToken' => $idToken], JSON_THROW_ON_ERROR),
    ]);

    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $status !== 200) {
        api_error('Google sign-in could not be verified.', 401);
    }

    $decoded = json_decode((string)$response, true);
    $firebaseUser = $decoded['users'][0] ?? null;
    if (!is_array($firebaseUser)) {
        api_error('Google sign-in could not be verified.', 401);
    }
    return $firebaseUser;
}

function api_auth_forgot_password(): never
{
    $email = strtolower(trim((string)(api_input()['email'] ?? '')));
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
        if ($userId) {
            $token = bin2hex(random_bytes(32));
            db()->prepare('DELETE FROM password_resets WHERE user_id = ? AND used_at IS NULL')->execute([(int)$userId]);
            db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
                ->execute([(int)$userId, hash('sha256', $token)]);
            $resetLink = api_request_origin() . '/reset-password.php?token=' . urlencode($token);
            $appResetLink = 'mzansigem://auth/reset-password?token=' . urlencode($token);
            send_app_email($email, 'Reset your Mzansi Gem password', "A password reset was requested for your Mzansi Gem account.

Website:
{$resetLink}

Android app:
{$appResetLink}

These links expire in one hour.");
        }
    }
    api_response(null, 'If the account exists, a password reset link has been sent.');
}

function api_auth_logout(): never
{
    api_require_user();
    api_revoke_current_token();
    api_response(null, 'Signed out successfully.');
}

function api_auth_me(): never
{
    api_response(api_public_user(api_require_user()));
}

function api_auth_verify_email(): never
{
    $token = trim((string)(api_input()['token'] ?? ''));
    if ($token === '') api_error('Verification token is required.', 422);
    $stmt = db()->prepare('SELECT user_id, id FROM email_verification_tokens WHERE token_hash = ? AND expires_at > NOW() LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    $record = $stmt->fetch();
    if (!$record) api_error('Invalid or expired verification token.', 400);
    $userId = (int)$record['user_id'];
    db()->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = ?')->execute([$userId]);
    db()->prepare('DELETE FROM email_verification_tokens WHERE id = ?')->execute([(int)$record['id']]);
    api_response(null, 'Email verified successfully. You can now sign in.');
}

function api_auth_reset_password(): never
{
    $input = api_input();
    $token = trim((string)($input['token'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $confirm = (string)($input['confirm_password'] ?? '');
    if ($token === '') api_error('Reset token is required.', 422);
    if (mb_strlen($password) < 8) api_error('Password must be at least 8 characters.', 422);
    if ($password !== $confirm) api_error('Passwords do not match.', 422);
    $stmt = db()->prepare('SELECT user_id, id FROM password_resets WHERE token_hash = ? AND expires_at > NOW() AND used_at IS NULL ORDER BY id DESC LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    $record = $stmt->fetch();
    if (!$record) api_error('This reset link is invalid or expired.', 400);
    $userId = (int)$record['user_id'];
    db()->beginTransaction();
    try {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
        db()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([(int)$record['id']]);
        db()->prepare('DELETE FROM api_tokens WHERE user_id = ?')->execute([$userId]);
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        throw $e;
    }
    api_response(null, 'Password updated successfully.');
}

function api_categories(): never
{
    api_response([
        'cities' => ['Cape Town','Johannesburg','Durban','Pretoria','Gqeberha','Bloemfontein','Polokwane','Mbombela','East London','Kimberley'],
        'vibes' => ['Chill', 'Romantic', 'Adventurous', 'Social', 'Quiet'],
        'budgets' => ['Free', 'Budget', 'Mid-range', 'Premium'],
        'activities' => ['Food', 'Fitness', 'Study', 'Nature', 'Nightlife', 'Culture'],
    ]);
}

function api_gems_index(): never
{
    $viewer = api_user();
    $viewerId = (int)($viewer['id'] ?? 0);
    $q = trim((string)($_GET['q'] ?? ''));
    $city = trim((string)($_GET['city'] ?? ''));
    $vibe = trim((string)($_GET['vibe'] ?? ''));
    $budget = trim((string)($_GET['budget'] ?? ''));
    $activity = trim((string)($_GET['activity'] ?? ''));
    $sort = trim((string)($_GET['sort'] ?? 'recommended'));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(30, max(1, (int)($_GET['per_page'] ?? 12)));
    $offset = ($page - 1) * $perPage;

    $where = ["g.status = 'approved'"];
    $params = [];
    if ($q !== '') {
        $where[] = '(g.title LIKE ? OR g.description LIKE ? OR g.location LIKE ? OR g.city LIKE ?)';
        $needle = '%' . $q . '%';
        array_push($params, $needle, $needle, $needle, $needle);
    }
    if ($city !== '' && $city !== 'All Cities') { $where[] = 'g.city = ?'; $params[] = $city; }
    if ($vibe !== '') { $where[] = 'FIND_IN_SET(?, REPLACE(g.vibes, ", ", ","))'; $params[] = $vibe; }
    if ($budget !== '') { $where[] = 'g.budget_level = ?'; $params[] = $budget; }
    if ($activity !== '') { $where[] = 'g.activity_type = ?'; $params[] = $activity; }

    $order = match ($sort) {
        'rating' => 'g.average_rating DESC, g.review_count DESC',
        'newest' => 'g.created_at DESC',
        'popular' => 'save_count DESC, like_count DESC, g.average_rating DESC',
        default => 'g.average_rating DESC, save_count DESC, g.created_at DESC',
    };
    $whereSql = implode(' AND ', $where);
    $count = db()->prepare("SELECT COUNT(*) FROM gems g WHERE {$whereSql}");
    $count->execute($params);
    $total = (int)$count->fetchColumn();

    $sql = "SELECT g.*,
        (SELECT COUNT(*) FROM gem_likes gl WHERE gl.gem_id = g.id) AS like_count,
        (SELECT COUNT(*) FROM saved_gems sg WHERE sg.gem_id = g.id) AS save_count";
    if ($viewerId) {
        $sql .= ", EXISTS(SELECT 1 FROM gem_likes vgl WHERE vgl.gem_id = g.id AND vgl.user_id = {$viewerId}) AS viewer_liked,
                  EXISTS(SELECT 1 FROM saved_gems vsg WHERE vsg.gem_id = g.id AND vsg.user_id = {$viewerId}) AS viewer_saved";
    }
    $sql .= " FROM gems g WHERE {$whereSql} ORDER BY {$order} LIMIT {$perPage} OFFSET {$offset}";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $gems = array_map(static fn($gem) => api_gem($gem, $viewerId), $stmt->fetchAll());
    api_response($gems, 'Gems loaded.', 200, [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'last_page' => max(1, (int)ceil($total / $perPage)),
    ]);
}

function api_gems_random(): never
{
    $viewer = api_user();
    $countStmt = db()->query("SELECT COUNT(*) FROM gems WHERE status = 'approved'");
    $total = (int)$countStmt->fetchColumn();
    if ($total === 0) api_error('No approved gems are available yet.', 404);
    $offset = random_int(0, max(0, $total - 1));
    $stmt = db()->prepare("SELECT g.*,
        (SELECT COUNT(*) FROM gem_likes gl WHERE gl.gem_id = g.id) AS like_count,
        (SELECT COUNT(*) FROM saved_gems sg WHERE sg.gem_id = g.id) AS save_count
        FROM gems g WHERE g.status = 'approved' LIMIT 1 OFFSET ?");
    $stmt->execute([$offset]);
    $gem = $stmt->fetch();
    if (!$gem) api_error('No approved gems are available yet.', 404);
    api_response(api_gem($gem, (int)($viewer['id'] ?? 0)));
}

function api_gems_show(int $id): never
{
    $viewer = api_user();
    $viewerId = (int)($viewer['id'] ?? 0);
    $gem = api_find_gem($id, true);
    $reviewVisibility = "r.status = 'approved'";
    if ($viewerId) {
        $reviewVisibility .= " OR r.user_id = {$viewerId}";
    }
    if (($viewer['role'] ?? '') === 'admin') {
        $reviewVisibility .= " OR r.status IN ('pending','rejected')";
    }
        $stmt = db()->prepare("SELECT r.id,r.gem_id,r.user_id,r.rating,r.comment,r.vibes,r.status,r.is_edited,r.created_at,r.updated_at, u.full_name AS reviewer_name,
                (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id = r.id) AS like_count" .
                ($viewerId ? ", EXISTS(SELECT 1 FROM review_likes vrl WHERE vrl.review_id = r.id AND vrl.user_id = {$viewerId}) AS viewer_liked" : '') .
                " FROM reviews r JOIN users u ON u.id = r.user_id
                    WHERE r.gem_id = ? AND ({$reviewVisibility})
                    ORDER BY r.created_at DESC");
    $stmt->execute([$id]);
    $reviews = array_map(static fn($review) => api_review($review, $viewerId), $stmt->fetchAll());
    $payload = api_gem($gem, $viewerId, true);
    $payload['reviews'] = $reviews;
    api_response($payload);
}

function api_gems_create(): never
{
    $user = api_require_user();
    $input = array_merge(api_input(), $_POST);
    $valid = api_validate_gem($input);
    $rating = (int)($input['rating'] ?? 0);
    if ($rating < 1 || $rating > 5) api_error('Choose a rating from 1 to 5.', 422, ['rating' => 'Rating is required.']);
    $maxPosts = max(1, (int)setting('max_posts_per_day', '10'));
    $stmt = db()->prepare('SELECT COUNT(*) FROM gems WHERE user_id = ? AND created_at >= CURDATE()');
    $stmt->execute([(int)$user['id']]);
    if ((int)$stmt->fetchColumn() >= $maxPosts) api_error('You have reached the daily submission limit.', 429);
    $paths = api_store_uploaded_files($_FILES['images'] ?? []);
    $status = ($user['role'] === 'admin' || setting('enable_moderation', '1') !== '1' || setting('auto_approve_content', '0') === '1') ? 'approved' : 'pending';
    $slug = api_slug($valid['title']);
    $cover = $paths[0] ?? null;
    db()->beginTransaction();
    try {
        $stmt = db()->prepare('INSERT INTO gems (user_id,title,slug,description,location,city,vibes,budget_level,activity_type,operating_hours,cover_image,average_rating,review_count,status,approved_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([(int)$user['id'], $valid['title'], $slug, $valid['description'], $valid['location'], $valid['city'], implode(',', $valid['vibes']), $valid['budget'], $valid['activity'], $valid['hours'] ?: 'All Day', $cover, $status === 'approved' ? $rating : 0, $status === 'approved' ? 1 : 0, $status, $status === 'approved' ? date('Y-m-d H:i:s') : null]);
        $gemId = (int)db()->lastInsertId();
        if ($paths) {
            $placeholders = implode(',', array_fill(0, count($paths), '(?,?,?,?)'));
            $values = [];
            foreach ($paths as $index => $path) {
                $values[] = $gemId;
                $values[] = $path;
                $values[] = $valid['title'];
                $values[] = $index;
            }
            db()->prepare('INSERT INTO gem_images (gem_id,image_path,alt_text,sort_order) VALUES ' . $placeholders)->execute($values);
        }
        db()->prepare("INSERT INTO reviews (gem_id,user_id,rating,comment,status) VALUES (?,?,?,'Initial contributor rating',?)")
            ->execute([$gemId, (int)$user['id'], $rating, $status]);
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        throw $e;
    }
    award_points((int)$user['id'], 100);
    record_activity((int)$user['id'], 'gem_submitted', $user['full_name'] . ' submitted ' . $valid['title'] . '.');
    api_response(api_gem(api_find_gem($gemId, true), (int)$user['id'], true), $status === 'pending' ? 'Your hidden gem is awaiting administrator approval.' : 'Your hidden gem is now live.', 201);
}

function api_gems_update(int $id): never
{
    $user = api_require_user();
    $gem = api_find_gem($id, true);
    if ((int)$gem['user_id'] !== (int)$user['id'] && $user['role'] !== 'admin') api_error('You cannot edit this gem.', 403);
    $input = array_merge(api_input(), $_POST);
    $merged = [
        'title' => $input['title'] ?? $gem['title'],
        'description' => $input['description'] ?? $gem['description'],
        'location' => $input['location'] ?? $gem['location'],
        'city' => $input['city'] ?? $gem['city'],
        'vibes' => $input['vibes'] ?? $gem['vibes'],
        'budget_level' => $input['budget_level'] ?? $gem['budget_level'],
        'activity_type' => $input['activity_type'] ?? $gem['activity_type'],
        'operating_hours' => $input['operating_hours'] ?? $gem['operating_hours'],
    ];
    $valid = api_validate_gem($merged);
    $paths = api_store_uploaded_files($_FILES['images'] ?? []);
    $cover = $paths[0] ?? $gem['cover_image'];
    $status = $user['role'] === 'admin' ? 'approved' : ((setting('enable_moderation', '1') === '1' && setting('auto_approve_content', '0') !== '1') ? 'pending' : 'approved');
    db()->beginTransaction();
    try {
        $stmt = db()->prepare('UPDATE gems SET title=?,slug=?,description=?,location=?,city=?,vibes=?,budget_level=?,activity_type=?,operating_hours=?,cover_image=?,status=?,rejection_reason=?,approved_at=? WHERE id=?');
        $rejection = $status === 'rejected' ? ($input['rejection_reason'] ?? 'Rejected by administrator') : null;
        $stmt->execute([$valid['title'], api_slug($valid['title'], $id), $valid['description'], $valid['location'], $valid['city'], implode(',', $valid['vibes']), $valid['budget'], $valid['activity'], $valid['hours'] ?: 'All Day', $cover, $status, $rejection, $status === 'approved' ? date('Y-m-d H:i:s') : null, $id]);
        if ($paths) {
            $placeholders = implode(',', array_fill(0, count($paths), '(?,?,?,?)'));
            $values = [];
            foreach ($paths as $index => $path) {
                $values[] = $id;
                $values[] = $path;
                $values[] = $valid['title'];
                $values[] = $index + 10;
            }
            db()->prepare('INSERT INTO gem_images (gem_id,image_path,alt_text,sort_order) VALUES ' . $placeholders)->execute($values);
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        throw $e;
    }
    award_points((int)$user['id'], 20);
    record_activity((int)$user['id'], 'gem_updated', $user['full_name'] . ' updated ' . $valid['title'] . '.');
    api_response(api_gem(api_find_gem($id, true), (int)$user['id'], true), $status === 'pending' ? 'Changes saved and submitted for approval.' : 'Gem updated.');
}

function api_gems_delete(int $id): never
{
    $user = api_require_user();
    $gem = api_find_gem($id, true);
    if ((int)$gem['user_id'] !== (int)$user['id'] && $user['role'] !== 'admin') api_error('You cannot delete this gem.', 403);
    db()->prepare('DELETE FROM gems WHERE id = ?')->execute([$id]);
    record_activity((int)$user['id'], 'gem_deleted', $user['full_name'] . ' deleted ' . $gem['title'] . '.');
    api_response(null, 'Gem deleted.');
}

function api_gems_toggle_like(int $id): never
{
    $user = api_require_user();
    api_find_gem($id);
    $stmt = db()->prepare('SELECT id FROM gem_likes WHERE user_id = ? AND gem_id = ?');
    $stmt->execute([(int)$user['id'], $id]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        db()->prepare('DELETE FROM gem_likes WHERE id = ?')->execute([(int)$existing]);
        $liked = false;
    } else {
        db()->prepare('INSERT INTO gem_likes (user_id,gem_id) VALUES (?,?)')->execute([(int)$user['id'], $id]);
        $liked = true;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM gem_likes WHERE gem_id = ?');
    $stmt->execute([$id]);
    api_response(['is_liked' => $liked, 'like_count' => (int)$stmt->fetchColumn()], $liked ? 'Gem liked.' : 'Like removed.');
}

function api_gems_toggle_save(int $id): never
{
    $user = api_require_user();
    api_find_gem($id);
    $stmt = db()->prepare('SELECT id FROM saved_gems WHERE user_id = ? AND gem_id = ?');
    $stmt->execute([(int)$user['id'], $id]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        db()->prepare('DELETE FROM saved_gems WHERE id = ?')->execute([(int)$existing]);
        $saved = false;
    } else {
        db()->prepare('INSERT INTO saved_gems (user_id,gem_id) VALUES (?,?)')->execute([(int)$user['id'], $id]);
        $saved = true;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM saved_gems WHERE gem_id = ?');
    $stmt->execute([$id]);
    api_response(['is_saved' => $saved, 'save_count' => (int)$stmt->fetchColumn()], $saved ? 'Gem saved.' : 'Gem removed from saved list.');
}

function api_gems_save_note(int $id): never
{
    $user = api_require_user();
    $note = trim((string)(api_input()['personal_note'] ?? ''));
    if (mb_strlen($note) > 150) api_error('Personal note must be 150 characters or fewer.', 422);
    $stmt = db()->prepare('INSERT INTO saved_gems (user_id,gem_id,personal_note) VALUES (?,?,?) ON DUPLICATE KEY UPDATE personal_note=VALUES(personal_note), updated_at=CURRENT_TIMESTAMP');
    $stmt->execute([(int)$user['id'], $id, $note !== '' ? $note : null]);
    api_response(['personal_note' => $note], 'Note saved successfully.');
}

function api_saved_index(): never
{
    $user = api_require_user();
    $sql = "SELECT g.*, sg.personal_note,
        (SELECT COUNT(*) FROM gem_likes gl WHERE gl.gem_id = g.id) AS like_count,
        (SELECT COUNT(*) FROM saved_gems s2 WHERE s2.gem_id = g.id) AS save_count,
        1 AS viewer_saved,
        EXISTS(SELECT 1 FROM gem_likes vgl WHERE vgl.gem_id = g.id AND vgl.user_id = ?) AS viewer_liked
        FROM saved_gems sg JOIN gems g ON g.id = sg.gem_id
        WHERE sg.user_id = ? ORDER BY sg.updated_at DESC";
    $stmt = db()->prepare($sql);
    $stmt->execute([(int)$user['id'], (int)$user['id']]);
    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $gem = api_gem($row, (int)$user['id']);
        $gem['personal_note'] = (string)($row['personal_note'] ?? '');
        $items[] = $gem;
    }
    api_response($items, 'Saved gems loaded.');
}

function api_reviews_create(int $gemId): never
{
    $user = api_require_user();
    $gem = api_find_gem($gemId);
    $input = api_input();
    $rating = (int)($input['rating'] ?? 0);
    $comment = trim((string)($input['comment'] ?? ''));
    $vibes = api_clean_tags($input['vibes'] ?? [], 4);
    $errors = [];
    if ($rating < 1 || $rating > 5) $errors['rating'] = 'Select a rating between 1 and 5.';
    if (mb_strlen($comment) > 300) $errors['comment'] = 'Review must be 300 characters or fewer.';
    $stmt = db()->prepare('SELECT id FROM reviews WHERE user_id = ? AND gem_id = ?');
    $stmt->execute([(int)$user['id'], $gemId]);
    if ($stmt->fetchColumn()) $errors['review'] = 'You have already reviewed this gem.';
    if ($errors !== []) api_error('Review could not be submitted.', 422, $errors);
    $status = ($user['role'] === 'admin' || setting('enable_moderation', '1') !== '1' || setting('auto_approve_content', '0') === '1') ? 'approved' : 'pending';
    $stmt = db()->prepare('INSERT INTO reviews (gem_id,user_id,rating,comment,vibes,status) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$gemId, (int)$user['id'], $rating, $comment ?: null, implode(',', $vibes), $status]);
    $id = (int)db()->lastInsertId();
    award_points((int)$user['id'], 25);
    record_activity((int)$user['id'], 'review_posted', $user['full_name'] . ' reviewed ' . $gem['title'] . '.');
    if ($status === 'approved') recalculate_gem_rating($gemId);
    $stmt = db()->prepare('SELECT r.*, u.full_name AS reviewer_name, 0 AS like_count FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.id=?');
    $stmt->execute([$id]);
    api_response(api_review($stmt->fetch(), (int)$user['id']), $status === 'pending' ? 'Thank you. Your review is awaiting approval.' : 'Your review is now live.', 201);
}

function api_reviews_update(int $id): never
{
    $user = api_require_user();
    $stmt = db()->prepare('SELECT id,gem_id,user_id,rating,comment,vibes,status,is_edited,created_at,updated_at FROM reviews WHERE id = ?');
    $stmt->execute([$id]);
    $review = $stmt->fetch();
    if (!$review) api_error('Review not found.', 404);
    if ((int)$review['user_id'] !== (int)$user['id'] && $user['role'] !== 'admin') api_error('You cannot edit this review.', 403);
    $input = api_input();
    $rating = (int)($input['rating'] ?? $review['rating']);
    $comment = trim((string)($input['comment'] ?? $review['comment'] ?? ''));
    $vibes = api_clean_tags($input['vibes'] ?? $review['vibes'] ?? [], 4);
    if ($rating < 1 || $rating > 5) api_error('Select a rating between 1 and 5.', 422);
    if (mb_strlen($comment) > 300) api_error('Review must be 300 characters or fewer.', 422);
    $status = $user['role'] === 'admin' ? 'approved' : ((setting('enable_moderation', '1') === '1' && setting('auto_approve_content', '0') !== '1') ? 'pending' : 'approved');
    db()->prepare('UPDATE reviews SET rating=?,comment=?,vibes=?,status=?,is_edited=1 WHERE id=?')->execute([$rating, $comment ?: null, implode(',', $vibes), $status, $id]);
    recalculate_gem_rating((int)$review['gem_id']);
    $stmt = db()->prepare('SELECT r.*, u.full_name AS reviewer_name, (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id=r.id) AS like_count FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.id=?');
    $stmt->execute([$id]);
    api_response(api_review($stmt->fetch(), (int)$user['id']), $status === 'pending' ? 'Review updated and returned to moderation.' : 'Review updated.');
}

function api_reviews_delete(int $id): never
{
    $user = api_require_user();
    $stmt = db()->prepare('SELECT id,gem_id,user_id,rating,comment,vibes,status,is_edited,created_at,updated_at FROM reviews WHERE id = ?');
    $stmt->execute([$id]);
    $review = $stmt->fetch();
    if (!$review) api_error('Review not found.', 404);
    if ((int)$review['user_id'] !== (int)$user['id'] && $user['role'] !== 'admin') api_error('You cannot delete this review.', 403);
    db()->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
    recalculate_gem_rating((int)$review['gem_id']);
    api_response(null, 'Review deleted.');
}

function api_reviews_toggle_like(int $id): never
{
    $user = api_require_user();
    $stmt = db()->prepare('SELECT id FROM reviews WHERE id = ? AND status = "approved"');
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) api_error('Review not found.', 404);
    $stmt = db()->prepare('SELECT id FROM review_likes WHERE user_id = ? AND review_id = ?');
    $stmt->execute([(int)$user['id'], $id]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        db()->prepare('DELETE FROM review_likes WHERE id = ?')->execute([(int)$existing]);
        $liked = false;
    } else {
        db()->prepare('INSERT INTO review_likes (user_id,review_id) VALUES (?,?)')->execute([(int)$user['id'], $id]);
        $liked = true;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM review_likes WHERE review_id = ?');
    $stmt->execute([$id]);
    api_response(['is_liked' => $liked, 'like_count' => (int)$stmt->fetchColumn()], $liked ? 'Review liked.' : 'Like removed.');
}

function api_profile_show(): never
{
    $user = api_require_user();
    $stmt = db()->prepare('SELECT COUNT(*) FROM gems WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    $posted = (int)$stmt->fetchColumn();
    $stmt = db()->prepare('SELECT COUNT(*) FROM saved_gems WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    $saved = (int)$stmt->fetchColumn();
    $stmt = db()->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    $reviews = (int)$stmt->fetchColumn();
    $payload = api_public_user($user);
    $payload['stats'] = compact('posted', 'saved', 'reviews');
    api_response($payload);
}

function api_profile_update(): never
{
    $user = api_require_user();
    $input = api_input();
    $name = trim((string)($input['full_name'] ?? $user['full_name']));
    $email = strtolower(trim((string)($input['email'] ?? $user['email'])));
    $city = trim((string)($input['city'] ?? $user['city']));
    $bio = trim((string)($input['bio'] ?? $user['bio'] ?? ''));
    $errors = [];
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors['full_name'] = 'Full name must be 2 to 100 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if ($city === '' || mb_strlen($city) > 100) $errors['city'] = 'City is required.';
    if (mb_strlen($bio) > 300) $errors['bio'] = 'Bio must be 300 characters or fewer.';
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
    $stmt->execute([$email, (int)$user['id']]);
    if ($stmt->fetchColumn()) $errors['email'] = 'That email address is already in use.';
    if ($errors !== []) api_error('Please correct the highlighted fields.', 422, $errors);
    db()->prepare('UPDATE users SET full_name=?,email=?,city=?,bio=? WHERE id=?')->execute([$name, $email, $city, $bio ?: null, (int)$user['id']]);
    $stmt = db()->prepare('SELECT id,full_name,email,city,bio,role,status,level,points,password_hash,email_verified_at,notify_new_gems,notify_comments,notify_likes_saves,personalized_recommendations,show_saved_gems,show_activity_status,created_at,updated_at FROM users WHERE id = ?');
    $stmt->execute([(int)$user['id']]);
    api_response(api_public_user($stmt->fetch()), 'Profile updated.');
}

function api_profile_settings(): never
{
    $user = api_require_user();
    $input = api_input();
    $values = [
        (int)api_bool($input['notify_new_gems'] ?? $user['notify_new_gems']),
        (int)api_bool($input['notify_comments'] ?? $user['notify_comments']),
        (int)api_bool($input['notify_likes_saves'] ?? $user['notify_likes_saves']),
        (int)api_bool($input['personalized_recommendations'] ?? $user['personalized_recommendations']),
        (int)api_bool($input['show_saved_gems'] ?? $user['show_saved_gems']),
        (int)api_bool($input['show_activity_status'] ?? $user['show_activity_status']),
        (int)$user['id'],
    ];
    db()->prepare('UPDATE users SET notify_new_gems=?,notify_comments=?,notify_likes_saves=?,personalized_recommendations=?,show_saved_gems=?,show_activity_status=? WHERE id=?')->execute($values);
    $stmt = db()->prepare('SELECT id,full_name,email,city,bio,role,status,level,points,password_hash,email_verified_at,notify_new_gems,notify_comments,notify_likes_saves,personalized_recommendations,show_saved_gems,show_activity_status,created_at,updated_at FROM users WHERE id = ?');
    $stmt->execute([(int)$user['id']]);
    api_response(api_public_user($stmt->fetch()), 'Preferences saved.');
}

function api_profile_password(): never
{
    $user = api_require_user();
    $input = api_input();
    $current = (string)($input['current_password'] ?? '');
    $password = (string)($input['password'] ?? '');
    $confirm = (string)($input['confirm_password'] ?? '');
    if (!password_verify($current, (string)$user['password_hash'])) api_error('Current password is incorrect.', 422);
    if (mb_strlen($password) < 8) api_error('New password must be at least 8 characters.', 422);
    if ($password !== $confirm) api_error('New passwords do not match.', 422);
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), (int)$user['id']]);
    db()->prepare('DELETE FROM api_tokens WHERE user_id = ? AND id <> ?')->execute([(int)$user['id'], (int)(api_token_record()['token_id'] ?? 0)]);
    api_response(null, 'Password changed successfully.');
}

function api_profile_delete(): never
{
    $user = api_require_user();
    $password = (string)(api_input()['password'] ?? '');
    if (!password_verify($password, (string)$user['password_hash'])) api_error('Password confirmation is incorrect.', 422);
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([(int)$user['id']]);
    api_response(null, 'Account deleted.');
}

function api_profile_gems(): never
{
    $user = api_require_user();
    $stmt = db()->prepare("SELECT g.*,
        (SELECT COUNT(*) FROM gem_likes gl WHERE gl.gem_id=g.id) AS like_count,
        (SELECT COUNT(*) FROM saved_gems sg WHERE sg.gem_id=g.id) AS save_count
        FROM gems g WHERE g.user_id=? ORDER BY g.created_at DESC");
    $stmt->execute([(int)$user['id']]);
    api_response(array_map(static fn($gem) => api_gem($gem, (int)$user['id']), $stmt->fetchAll()));
}

function api_profile_reviews(): never
{
    $user = api_require_user();
    $stmt = db()->prepare("SELECT r.*, u.full_name AS reviewer_name, g.title AS gem_title,
        (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id=r.id) AS like_count
        FROM reviews r JOIN users u ON u.id=r.user_id JOIN gems g ON g.id=r.gem_id
        WHERE r.user_id=? ORDER BY r.created_at DESC");
    $stmt->execute([(int)$user['id']]);
    api_response(array_map(static fn($review) => api_review($review, (int)$user['id']), $stmt->fetchAll()));
}

function api_reports_create(): never
{
    $user = api_require_user();
    $input = api_input();
    $gemId = !empty($input['gem_id']) ? (int)$input['gem_id'] : null;
    $reviewId = !empty($input['review_id']) ? (int)$input['review_id'] : null;
    $type = trim((string)($input['violation_type'] ?? ''));
    $details = trim((string)($input['details'] ?? ''));
    if (!$gemId && !$reviewId) api_error('Choose content to report.', 422);
    if ($type === '' || mb_strlen($type) > 100) api_error('Violation type is required.', 422);
    if (mb_strlen($details) > 400) api_error('Report details must be 400 characters or fewer.', 422);
    db()->prepare('INSERT INTO reports (reporter_id,gem_id,review_id,violation_type,details) VALUES (?,?,?,?,?)')->execute([(int)$user['id'], $gemId, $reviewId, $type, $details ?: null]);
    api_response(null, 'Report submitted. Thank you for helping keep the community safe.', 201);
}

function api_admin_dashboard(): never
{
    api_require_admin();
    $metrics = [];
    foreach ([
        'total_users' => 'SELECT COUNT(*) FROM users',
        'total_posts' => 'SELECT COUNT(*) FROM gems',
        'active_reports' => "SELECT COUNT(*) FROM reports WHERE status='pending'",
        'pending_gems' => "SELECT COUNT(*) FROM gems WHERE status='pending'",
        'pending_reviews' => "SELECT COUNT(*) FROM reviews WHERE status='pending'",
    ] as $key => $sql) {
        $metrics[$key] = (int)db()->query($sql)->fetchColumn();
    }
    $activity = db()->query('SELECT a.*,u.full_name FROM activity_log a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT 10')->fetchAll();
    api_response(['metrics' => $metrics, 'recent_activity' => $activity]);
}

function api_admin_users(): never
{
    api_require_admin();
    $q = trim((string)($_GET['q'] ?? ''));
    $role = trim((string)($_GET['role'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));
    $where = ['1=1']; $params = [];
    if ($q !== '') { $where[] = '(full_name LIKE ? OR email LIKE ?)'; $params[] = "%{$q}%"; $params[] = "%{$q}%"; }
    if (in_array($role, ['user','admin'], true)) { $where[] = 'role=?'; $params[] = $role; }
    if (in_array($status, ['active','suspended'], true)) { $where[] = 'status=?'; $params[] = $status; }
    $stmt = db()->prepare('SELECT id,full_name,email,city,bio,role,status,level,points,email_verified_at,notify_new_gems,notify_comments,notify_likes_saves,personalized_recommendations,show_saved_gems,show_activity_status,created_at,updated_at FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC');
    $stmt->execute($params);
    api_response(array_map('api_public_user', $stmt->fetchAll()));
}

function api_admin_user_update(int $id): never
{
    $admin = api_require_admin();
    $input = api_input();
    $role = (string)($input['role'] ?? '');
    $status = (string)($input['status'] ?? '');
    if (!in_array($role, ['user','admin'], true) || !in_array($status, ['active','suspended'], true)) api_error('Choose a valid role and status.', 422);
    if ($id === (int)$admin['id'] && $status === 'suspended') api_error('You cannot suspend your own account.', 422);
    db()->prepare('UPDATE users SET role=?,status=? WHERE id=?')->execute([$role, $status, $id]);
    $stmt = db()->prepare('SELECT id,full_name,email,city,bio,role,status,level,points,email_verified_at,notify_new_gems,notify_comments,notify_likes_saves,personalized_recommendations,show_saved_gems,show_activity_status,created_at,updated_at FROM users WHERE id=?'); $stmt->execute([$id]);
    $user = $stmt->fetch(); if (!$user) api_error('User not found.', 404);
    api_response(api_public_user($user), 'User updated.');
}

function api_admin_moderation(): never
{
    api_require_admin();
    $pendingGems = db()->query("SELECT g.*,u.full_name AS submitter_name FROM gems g JOIN users u ON u.id=g.user_id WHERE g.status='pending' ORDER BY g.created_at")->fetchAll();
    $pendingReviews = db()->query("SELECT r.*,u.full_name AS reviewer_name,g.title AS gem_title FROM reviews r JOIN users u ON u.id=r.user_id JOIN gems g ON g.id=r.gem_id WHERE r.status='pending' ORDER BY r.created_at")->fetchAll();
    $reports = db()->query("SELECT rp.*,u.full_name AS reporter_name,g.title AS gem_title,r.comment AS review_comment FROM reports rp JOIN users u ON u.id=rp.reporter_id LEFT JOIN gems g ON g.id=rp.gem_id LEFT JOIN reviews r ON r.id=rp.review_id WHERE rp.status='pending' ORDER BY rp.created_at")->fetchAll();
    api_response([
        'pending_gems' => array_map(static fn($gem) => api_gem($gem, null, true), $pendingGems),
        'pending_reviews' => array_map(static fn($review) => api_review($review), $pendingReviews),
        'reports' => $reports,
    ]);
}

function api_admin_gem_moderate(int $id): never
{
    $admin = api_require_admin();
    $input = api_input();
    $status = (string)($input['status'] ?? '');
    $reason = trim((string)($input['rejection_reason'] ?? ''));
    if (!in_array($status, ['approved','rejected','pending'], true)) api_error('Choose a valid moderation status.', 422);
    db()->prepare('UPDATE gems SET status=?,rejection_reason=?,approved_at=? WHERE id=?')->execute([$status, $status === 'rejected' ? ($reason ?: 'Rejected by administrator') : null, $status === 'approved' ? date('Y-m-d H:i:s') : null, $id]);
    record_activity((int)$admin['id'], 'content_' . $status, $admin['full_name'] . ' marked gem #' . $id . ' as ' . $status . '.');
    api_response(api_gem(api_find_gem($id, true), (int)$admin['id'], true), 'Gem moderation status updated.');
}

function api_admin_review_moderate(int $id): never
{
    $admin = api_require_admin();
    $status = (string)(api_input()['status'] ?? '');
    if (!in_array($status, ['approved','rejected','pending'], true)) api_error('Choose a valid moderation status.', 422);
    $stmt = db()->prepare('SELECT gem_id FROM reviews WHERE id=?'); $stmt->execute([$id]);
    $gemId = $stmt->fetchColumn(); if (!$gemId) api_error('Review not found.', 404);
    db()->prepare('UPDATE reviews SET status=? WHERE id=?')->execute([$status, $id]);
    recalculate_gem_rating((int)$gemId);
    record_activity((int)$admin['id'], 'review_' . $status, $admin['full_name'] . ' marked review #' . $id . ' as ' . $status . '.');
    api_response(null, 'Review moderation status updated.');
}

function api_admin_report_update(int $id): never
{
    api_require_admin();
    $input = api_input();
    $status = (string)($input['status'] ?? '');
    $note = trim((string)($input['resolution_note'] ?? ''));
    if (!in_array($status, ['resolved','dismissed','pending'], true)) api_error('Choose a valid report status.', 422);
    db()->prepare('UPDATE reports SET status=?,resolution_note=?,resolved_at=? WHERE id=?')->execute([$status, $note ?: null, $status === 'pending' ? null : date('Y-m-d H:i:s'), $id]);
    api_response(null, 'Report updated.');
}

function api_admin_analytics(): never
{
    api_require_admin();
    $growth = db()->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,COUNT(*) AS total FROM users GROUP BY month ORDER BY month")->fetchAll();
    $categories = db()->query("SELECT activity_type AS category,COUNT(*) AS posts FROM gems GROUP BY activity_type ORDER BY posts DESC")->fetchAll();
    $engagement = [
        'posts' => (int)db()->query('SELECT COUNT(*) FROM gems')->fetchColumn(),
        'reviews' => (int)db()->query('SELECT COUNT(*) FROM reviews')->fetchColumn(),
        'likes' => (int)db()->query('SELECT COUNT(*) FROM gem_likes')->fetchColumn(),
        'saves' => (int)db()->query('SELECT COUNT(*) FROM saved_gems')->fetchColumn(),
    ];
    api_response(compact('growth', 'categories', 'engagement'));
}

function api_admin_settings_get(): never
{
    api_require_admin();
    $rows = db()->query('SELECT setting_key,setting_value FROM settings ORDER BY setting_key')->fetchAll();
    $settings = [];
    foreach ($rows as $row) $settings[$row['setting_key']] = $row['setting_value'];
    api_response($settings);
}

function api_admin_settings_update(): never
{
    api_require_admin();
    $allowed = ['site_name','site_description','allow_registration','require_email_verification','max_posts_per_day','enable_moderation','auto_approve_content'];
    $input = api_input();
    $stmt = db()->prepare('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    foreach ($allowed as $key) {
        if (array_key_exists($key, $input)) $stmt->execute([$key, (string)$input[$key]]);
    }
    api_admin_settings_get();
}
