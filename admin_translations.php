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
$primary = get_setting('primary_color', '#0d6efd');
$site_title = get_setting('site_title', 'TanMan Plattform');
$pdo = get_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $key = trim($_POST['key_name'] ?? '');
    $de = trim($_POST['de'] ?? '');
    $en = trim($_POST['en'] ?? '');
    if ($id) {
        $stmt = $pdo->prepare('UPDATE translations SET key_name=?, de=?, en=? WHERE id=?');
        $stmt->execute([$key,$de,$en,$id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO translations (key_name,de,en) VALUES (?,?,?) ON DUPLICATE KEY UPDATE de=VALUES(de), en=VALUES(en)');
        $stmt->execute([$key,$de,$en]);
    }
    header('Location: admin_translations.php');
    exit();
}
$rows = $pdo->query('SELECT * FROM translations ORDER BY key_name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8'); ?> - <?php echo t('translations_manage'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
</head>
<body>
<h1><?php echo t('translations_manage'); ?></h1>
<?php foreach ($rows as $row): ?>
<form method="post" class="translation-row">
    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
    <input name="key_name" value="<?php echo htmlspecialchars($row['key_name'], ENT_QUOTES, 'UTF-8'); ?>">
    <input name="de" value="<?php echo htmlspecialchars($row['de'], ENT_QUOTES, 'UTF-8'); ?>">
    <input name="en" value="<?php echo htmlspecialchars($row['en'], ENT_QUOTES, 'UTF-8'); ?>">
    <button type="submit"><?php echo t('save'); ?></button>
</form>
<?php endforeach; ?>
<h2>Neu</h2>
<form method="post">
    <input name="key_name" placeholder="Key">
    <input name="de" placeholder="Deutsch">
    <input name="en" placeholder="English">
    <button type="submit"><?php echo t('save'); ?></button>
</form>
</body>
</html>
