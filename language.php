<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'] === 'en' ? 'en' : 'de';
    $_SESSION['lang_prompt'] = false;
    $ref = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header('Location: ' . $ref);
    exit();
}
if (isset($_GET['dismiss_lang'])) {
    $_SESSION['lang_prompt'] = false;
    $ref = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header('Location: ' . $ref);
    exit();
}
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'de';
    $browser = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2);
    if ($browser !== 'de') {
        $_SESSION['lang_prompt'] = true;
    }
}
$lang = $_SESSION['lang'];
$translations = require __DIR__ . '/lang/' . $lang . '.php';
try {
    $pdo = get_db();
    $stmt = $pdo->query("SELECT key_name, {$lang} AS txt FROM translations");
    foreach ($stmt as $row) {
        $translations[$row['key_name']] = $row['txt'];
    }
} catch (Exception $e) {
    // table may not exist yet; ignore
}
function t(string $key): string {
    global $translations;
    return $translations[$key] ?? $key;
}
