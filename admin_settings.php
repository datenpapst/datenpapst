<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Zugriff verweigert');
}
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/image_utils.php';
$current = get_setting('primary_color', '#0d6efd');
$cpi_threshold = get_setting('cpi_threshold_default', '5');
$site_title = get_setting('site_title', 'TanMan Plattform');
$contact_email = get_setting('contact_email', '');
$smtp_host = get_setting('smtp_host', '');
$smtp_port = get_setting('smtp_port', '');
$smtp_user = get_setting('smtp_user', '');
$smtp_pass = get_setting('smtp_pass', '');
$smtp_from = get_setting('smtp_from', '');
$pdo = get_db();
$hist = $pdo->query('SELECT index_name, value, recorded_at FROM cpi_history ORDER BY recorded_at DESC LIMIT 10')->fetchAll();
$stmt = $pdo->prepare('SELECT otp_secret FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$has_2fa = !empty($stmt->fetchColumn());
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $color = $_POST['primary_color'] ?? '#0d6efd';
    set_setting('primary_color', $color);
    $current = $color;
    $cpi_threshold = $_POST['cpi_threshold'] ?? $cpi_threshold;
    set_setting('cpi_threshold_default', $cpi_threshold);
    $site_title = trim($_POST['site_title'] ?? $site_title);
    $contact_email = trim($_POST['contact_email'] ?? $contact_email);
    set_setting('site_title', $site_title);
    set_setting('contact_email', $contact_email);
    $smtp_host = trim($_POST['smtp_host'] ?? $smtp_host);
    $smtp_port = trim($_POST['smtp_port'] ?? $smtp_port);
    $smtp_user = trim($_POST['smtp_user'] ?? $smtp_user);
    $smtp_pass = trim($_POST['smtp_pass'] ?? $smtp_pass);
    $smtp_from = trim($_POST['smtp_from'] ?? $smtp_from);
    set_setting('smtp_host', $smtp_host);
    set_setting('smtp_port', $smtp_port);
    set_setting('smtp_user', $smtp_user);
    set_setting('smtp_pass', $smtp_pass);
    set_setting('smtp_from', $smtp_from);
    if (!empty($_FILES['profile_image']['name'])) {
        [$ok, $res] = save_scaled_image($_FILES['profile_image'], __DIR__ . '/uploads/profile', 400, 400, 1048576);
        if ($ok) {
            $pdo->prepare('UPDATE users SET profile_image=? WHERE id=?')->execute([$res, $user['id']]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo t('settings'); ?></title>
<?php $primary = $current; ?>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="script.js" defer></script>
</head>
<body>
<p><?php echo t('logged_in_as'); ?> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="logout.php"><?php echo t('logout'); ?></a></p>
<label class="switch">
    <input type="checkbox" id="lang-toggle" <?php echo $lang === 'en' ? 'checked' : ''; ?>>
    <span class="slider lang-slider"></span>
</label>
<label class="switch">
    <input type="checkbox" id="dark-toggle">
    <span class="slider"></span>
</label>
<h1><?php echo t('settings'); ?></h1>
<?php $img = $pdo->prepare('SELECT profile_image FROM users WHERE id=?'); $img->execute([$user['id']]); $avatar=$img->fetchColumn(); ?>
<?php if ($avatar): ?><p><img src="uploads/profile/<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt=""></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <label><?php echo t('primary_color'); ?>: <input type="color" name="primary_color" value="<?php echo htmlspecialchars($current, ENT_QUOTES, 'UTF-8'); ?>"></label>
    <label>VPI-Schwelle (%): <input type="number" step="0.1" name="cpi_threshold" value="<?php echo htmlspecialchars($cpi_threshold, ENT_QUOTES, 'UTF-8'); ?>"></label>
    <label><?php echo t('site_title'); ?>: <input type="text" name="site_title" value="<?php echo htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8'); ?>"></label>
    <label><?php echo t('contact_email'); ?>: <input type="email" name="contact_email" value="<?php echo htmlspecialchars($contact_email, ENT_QUOTES, 'UTF-8'); ?>"></label>
    <label><?php echo t('profile_image'); ?>: <input type="file" name="profile_image" accept="image/jpeg,image/png"></label>
    <fieldset>
        <legend><?php echo t('mail_settings'); ?></legend>
        <label><?php echo t('smtp_host'); ?>: <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host, ENT_QUOTES, 'UTF-8'); ?>"></label>
        <label><?php echo t('smtp_port'); ?>: <input type="text" name="smtp_port" value="<?php echo htmlspecialchars($smtp_port, ENT_QUOTES, 'UTF-8'); ?>"></label>
        <label><?php echo t('smtp_user'); ?>: <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($smtp_user, ENT_QUOTES, 'UTF-8'); ?>"></label>
        <label><?php echo t('smtp_pass'); ?>: <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($smtp_pass, ENT_QUOTES, 'UTF-8'); ?>"></label>
        <label><?php echo t('smtp_from'); ?>: <input type="email" name="smtp_from" value="<?php echo htmlspecialchars($smtp_from, ENT_QUOTES, 'UTF-8'); ?>"></label>
    </fieldset>
    <button type="submit"><?php echo t('save'); ?></button>
</form>
<?php if ($hist): ?>
<h2><?php echo t('cpi_history'); ?></h2>
<table border="1"><tr><th><?php echo t('recorded_at'); ?></th><th><?php echo t('index'); ?></th><th><?php echo t('value'); ?></th></tr>
<?php foreach ($hist as $h): ?>
<tr><td><?php echo htmlspecialchars($h['recorded_at']); ?></td><td><?php echo htmlspecialchars($h['index_name']); ?></td><td><?php echo htmlspecialchars($h['value']); ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<p>2FA Status: <?php echo $has_2fa ? 'aktiv' : 'inaktiv'; ?> - <a href="enable_2fa.php">bearbeiten</a></p>
</body>
</html>
