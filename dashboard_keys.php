<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
$pdo = get_db();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
$primary = get_setting('primary_color', '#0d6efd');

$ap_stmt = $pdo->prepare('SELECT a.id FROM apartments a JOIN tenant_apartment ta ON ta.apartment_id=a.id WHERE ta.user_id=? AND ta.is_active=1 LIMIT 1');
$ap_stmt->execute([$user['id']]);
$apartment_id = $ap_stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $apartment_id) {
    $type = $_POST['request_type'] === 'loss' ? 'loss' : 'additional';
    $desc = trim($_POST['description'] ?? '');
    $stmt = $pdo->prepare('INSERT INTO key_requests (user_id, apartment_id, request_type, description) VALUES (?,?,?,?)');
    $stmt->execute([$user['id'], $apartment_id, $type, $desc]);
}

$requests = [];
if ($apartment_id) {
    $stmt = $pdo->prepare('SELECT id, request_type, description, status, cost, created_at FROM key_requests WHERE user_id=? AND apartment_id=? ORDER BY created_at DESC');
    $stmt->execute([$user['id'], $apartment_id]);
    $requests = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo t('keys'); ?></title>
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
<h1><?php echo t('keys'); ?></h1>
<p><?php echo t('key_policy'); ?></p>
<?php if ($apartment_id): ?>
<form method="post">
    <label><?php echo t('request_new_key'); ?> <input type="radio" name="request_type" value="additional" checked></label>
    <label><?php echo t('report_key_loss'); ?> <input type="radio" name="request_type" value="loss"></label>
    <label><?php echo t('description'); ?>: <input type="text" name="description"></label>
    <button type="submit"><?php echo t('save'); ?></button>
</form>
<table border="1">
<tr><th><?php echo t('date'); ?></th><th><?php echo t('type'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('cost'); ?></th></tr>
<?php foreach ($requests as $r): ?>
<tr>
<td><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo $r['request_type']; ?></td>
<td><?php echo $r['status']; ?></td>
<td><?php echo number_format($r['cost'],2,',','.'); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<nav><ul><li><a href="dashboard.php"><?php echo t('dashboard_menu'); ?></a></li></ul></nav>
</body>
</html>
