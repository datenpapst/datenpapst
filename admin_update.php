<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') { http_response_code(403); exit('forbidden'); }
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/upload_utils.php';
$primary = get_setting('primary_color', '#0d6efd');
$version = get_setting('version', '0.1');
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['package']['name'])) {
    if (substr($_FILES['package']['name'], -4) !== '.zip') {
        $message = t('invalid_file');
    } elseif (!scan_file($_FILES['package']['tmp_name'])) {
        $message = t('update_failed');
    } else {
        $zip = new ZipArchive();
        if ($zip->open($_FILES['package']['tmp_name']) === true) {
            $zip->extractTo(__DIR__);
            $zip->close();
            $message = t('update_applied');
        } else {
            $message = t('update_failed');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo t('updates'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="script.js" defer></script>
</head>
<body>
<div id="burger">☰</div>
<p><?php echo t('logged_in_as'); ?> <?php echo htmlspecialchars($user['email']); ?> | <a href="admin.php"><?php echo t('admin_menu'); ?></a> | <a href="logout.php"><?php echo t('logout'); ?></a></p>
<label class="switch">
    <input type="checkbox" id="lang-toggle" <?php echo $lang === 'en' ? 'checked' : ''; ?>>
    <span class="slider lang-slider"></span>
</label>
<label class="switch">
    <input type="checkbox" id="dark-toggle">
    <span class="slider"></span>
</label>
<h1><?php echo t('updates'); ?></h1>
<p><?php echo t('current_version'); ?>: <?php echo htmlspecialchars($version); ?></p>
<?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <label><?php echo t('upload_update'); ?>: <input type="file" name="package" accept="application/zip"></label>
    <button type="submit"><?php echo t('apply_update'); ?></button>
</form>
</body>
</html>
