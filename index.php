<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/recaptcha.php';
require_once __DIR__ . '/auth.php';
$config = require __DIR__ . '/config.php';

require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
$primary = get_setting('primary_color', '#0d6efd');
$site_title = get_setting('site_title', 'TanMan Plattform');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptcha = $_POST['g-recaptcha-response'] ?? '';
    if (!verify_recaptcha($recaptcha)) {
        $error = t('captcha_error');
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = get_db()->prepare('SELECT id, password_hash, role, otp_secret, status FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
            $remember = !empty($_POST['remember']);
            if (!empty($user['otp_secret'])) {
                $_SESSION['pending_user'] = ['id' => $user['id'], 'email' => $email, 'role' => $user['role'], 'remember' => $remember];
                header('Location: verify_2fa.php');
                exit();
            } else {
                login_user($user['id'], $email, $user['role'], $remember);
                header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
                exit();
            }
        } else {
            $error = t('login_failed');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8'); ?> - <?php echo t('login_title'); ?></title>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="script.js" defer></script>
</head>
<body>
<?php if (!empty($_SESSION['lang_prompt'])): ?>
<div class="lang-banner">
    <?php echo t('suggest_en'); ?>
    <button onclick="location.href='language.php?lang=en'">EN</button>
    <button onclick="location.href='language.php?dismiss_lang=1'">✖</button>
</div>
<?php endif; ?>
<label class="switch">
    <input type="checkbox" id="lang-toggle" <?php echo $lang === 'en' ? 'checked' : ''; ?>>
    <span class="slider lang-slider"></span>
</label>
<label class="switch">
    <input type="checkbox" id="dark-toggle">
    <span class="slider"></span>
</label>
<h1><?php echo htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8'); ?></h1>
<h2><?php echo t('login_title'); ?></h2>
<?php if ($error): ?>
<p style="color:red;">
    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
</p>
<?php endif; ?>
<form method="post">
    <label><?php echo t('email'); ?>: <input type="email" name="email" required></label><br>
    <label><?php echo t('password'); ?>: <input type="password" name="password" required></label><br>
    <label><input type="checkbox" name="remember"> <?php echo t('stay_logged_in'); ?></label><br>
    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($config['recaptcha_site_key'], ENT_QUOTES, 'UTF-8'); ?>"></div>
    <button type="submit"><?php echo t('login'); ?></button>
</form>
<p><a href="forgot_password.php"><?php echo t('forgot_password'); ?></a></p>

<?php if (empty($_COOKIE['cookie_consent'])): ?>
<div id="cookie-banner">Diese Seite nutzt Cookies. <button id="cookie-accept">OK</button></div>
<?php endif; ?>
</body>
</html>
