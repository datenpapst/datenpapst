<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') { http_response_code(403); exit('forbidden'); }
$pdo = get_db();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
$primary = get_setting('primary_color', '#0d6efd');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'open';
    $cost = (float)($_POST['cost'] ?? 0);
    $stmt = $pdo->prepare('UPDATE key_requests SET status=?, cost=? WHERE id=?');
    $stmt->execute([$status, $cost, $id]);
}

$sql = 'SELECT kr.id, u.email, a.address, kr.request_type, kr.description, kr.status, kr.cost FROM key_requests kr JOIN users u ON kr.user_id=u.id JOIN apartments a ON kr.apartment_id=a.id ORDER BY kr.created_at DESC';
$requests = $pdo->query($sql)->fetchAll();
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
<table border="1">
<tr><th>ID</th><th><?php echo t('apartment'); ?></th><th><?php echo t('email'); ?></th><th><?php echo t('type'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('cost'); ?></th><th><?php echo t('save'); ?></th></tr>
<?php foreach ($requests as $r): ?>
<tr>
<form method="post">
<td><?php echo (int)$r['id']; ?><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"></td>
<td><?php echo htmlspecialchars($r['address'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['email'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo $r['request_type']; ?></td>
<td>
    <select name="status">
        <?php foreach (['open','approved','rejected','replaced'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php if ($r['status']==$s) echo 'selected'; ?>><?php echo $s; ?></option>
        <?php endforeach; ?>
    </select>
</td>
<td><input type="number" step="0.01" name="cost" value="<?php echo number_format($r['cost'],2,'.',''); ?>"></td>
<td><button><?php echo t('save'); ?></button></td>
</form>
</tr>
<?php endforeach; ?>
</table>
<nav><ul><li><a href="admin.php"><?php echo t('admin_menu'); ?></a></li></ul></nav>
</body>
</html>
