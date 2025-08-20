<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') { http_response_code(403); exit('Zugriff verweigert'); }
require_once __DIR__ . '/language.php';
$pdo = get_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['subject'] as $key => $langs) {
        foreach ($langs as $lang => $subj) {
            $body = $_POST['body'][$key][$lang] ?? '';
            $stmt = $pdo->prepare('UPDATE mail_templates SET subject=?, body=? WHERE template_key=? AND lang=?');
            $stmt->execute([$subj, $body, $key, $lang]);
        }
    }
}
$rows = $pdo->query('SELECT template_key, lang, subject, body FROM mail_templates')->fetchAll();
$templates = [];
foreach ($rows as $r) { $templates[$r['template_key']][$r['lang']] = $r; }
$primary = get_setting('primary_color', '#0d6efd');
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo t('mail_templates'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="script.js" defer></script>
</head>
<body>
<div id="burger">☰</div>
<p><?php echo t('logged_in_as'); ?> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="logout.php"><?php echo t('logout'); ?></a></p>
<label class="switch">
    <input type="checkbox" id="lang-toggle" <?php echo $lang === 'en' ? 'checked' : ''; ?>>
    <span class="slider lang-slider"></span>
</label>
<label class="switch">
    <input type="checkbox" id="dark-toggle">
    <span class="slider"></span>
</label>
<h1><?php echo t('mail_templates'); ?></h1>
<form method="post">
<?php foreach ($templates as $key => $langs): ?>
    <fieldset>
    <legend><?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?></legend>
    <?php foreach (['de','en'] as $l): $tpl = $langs[$l] ?? ['subject'=>'','body'=>'']; ?>
        <h3><?php echo strtoupper($l); ?></h3>
        <label>Betreff: <input type="text" name="subject[<?php echo $key; ?>][<?php echo $l; ?>]" value="<?php echo htmlspecialchars($tpl['subject'], ENT_QUOTES, 'UTF-8'); ?>"></label><br>
        <label>Text:<br><textarea name="body[<?php echo $key; ?>][<?php echo $l; ?>]" rows="4" cols="60"><?php echo htmlspecialchars($tpl['body'], ENT_QUOTES, 'UTF-8'); ?></textarea></label>
    <?php endforeach; ?>
    </fieldset>
<?php endforeach; ?>
    <button type="submit"><?php echo t('save'); ?></button>
</form>
<nav><ul><li><a href="admin.php">Menü</a></li></ul></nav>
</body>
</html>
