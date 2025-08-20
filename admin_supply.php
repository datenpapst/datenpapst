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
    $status = $_POST['status'] ?? 'active';
    $stmt = $pdo->prepare('UPDATE supply_contracts SET status=? WHERE id=?');
    $stmt->execute([$status, $id]);
}

$sql = 'SELECT sc.id, u.email, a.address, sc.contract_type, sc.provider, sc.contract_start, sc.contract_end, sc.status, sc.proof, u.id AS uid FROM supply_contracts sc JOIN users u ON sc.user_id=u.id JOIN apartments a ON sc.apartment_id=a.id ORDER BY sc.contract_start DESC';
$contracts = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo t('supply_contracts'); ?></title>
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
<h1><?php echo t('supply_contracts'); ?></h1>
<table border="1">
<tr><th>ID</th><th><?php echo t('apartment'); ?></th><th><?php echo t('email'); ?></th><th><?php echo t('contract_type'); ?></th><th><?php echo t('provider'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('save'); ?></th><th><?php echo t('documents'); ?></th></tr>
<?php foreach ($contracts as $c): ?>
<tr>
<form method="post">
<td><?php echo (int)$c['id']; ?><input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>"></td>
<td><?php echo htmlspecialchars($c['address'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo $c['contract_type']; ?></td>
<td><?php echo htmlspecialchars($c['provider'], ENT_QUOTES, 'UTF-8'); ?></td>
<td>
    <select name="status">
        <?php foreach (['active','terminated'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php if ($c['status']==$s) echo 'selected'; ?>><?php echo $s; ?></option>
        <?php endforeach; ?>
    </select>
</td>
<td><button><?php echo t('save'); ?></button></td>
<td><?php if ($c['proof']) echo '<a href="uploads/supply/'.$c['uid'].'/'.htmlspecialchars($c['proof'], ENT_QUOTES, 'UTF-8').'">Download</a>'; ?></td>
</form>
</tr>
<?php endforeach; ?>
</table>
<nav><ul><li><a href="admin.php"><?php echo t('admin_menu'); ?></a></li></ul></nav>
</body>
</html>
