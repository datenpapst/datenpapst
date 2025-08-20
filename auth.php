<?php
require_once __DIR__ . '/db.php';

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict'
]);

if (empty($_SESSION['user']) && !empty($_COOKIE['remember'])) {
    $data = explode(':', $_COOKIE['remember']);
    if (count($data) === 2) {
        [$id, $token] = $data;
        $stmt = get_db()->prepare('SELECT user_id, token, expires_at FROM auth_tokens WHERE user_id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && hash_equals($row['token'], $token) && strtotime($row['expires_at']) > time()) {
            $u = get_db()->prepare('SELECT id, email, role FROM users WHERE id=? AND status="active"');
            $u->execute([$row['user_id']]);
            if ($user = $u->fetch()) {
                $_SESSION['user'] = $user;
            }
        }
    }
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    $u = current_user();
    if (!$u) {
        header('Location: index.php');
        exit();
    }
    $stmt = get_db()->prepare('SELECT status FROM users WHERE id=?');
    $stmt->execute([$u['id']]);
    if ($stmt->fetchColumn() !== 'active') {
        logout_user();
        header('Location: index.php');
        exit();
    }
}

function login_user($user_id, $email, $role, $remember = false) {
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => $user_id, 'email' => $email, 'role' => $role];
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);
        $stmt = get_db()->prepare('REPLACE INTO auth_tokens (user_id, token, expires_at) VALUES (?,?,?)');
        $stmt->execute([$user_id, $token, $expires]);
        setcookie('remember', $user_id . ':' . $token, time() + 60 * 60 * 24 * 30, '/', '', isset($_SERVER['HTTPS']), true);
    }
}

function logout_user() {
    if (!empty($_COOKIE['remember'])) {
        $parts = explode(':', $_COOKIE['remember']);
        if (count($parts) === 2) {
            $stmt = get_db()->prepare('DELETE FROM auth_tokens WHERE user_id = ?');
            $stmt->execute([$parts[0]]);
        }
        setcookie('remember', '', time() - 3600, '/');
    }
    $_SESSION = [];
    session_destroy();
}
?>
