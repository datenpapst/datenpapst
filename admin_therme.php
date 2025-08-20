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
    $status = $_POST['status'] ?? 'submitted';
    if (!in_array($status, ['submitted','approved','rejected'])) { $status = 'submitted'; }
    $stmt = $pdo->prepare('UPDATE therme_services SET status=? WHERE id=?');
    $stmt->execute([$status, $id]);
}

$sql = 'SELECT ts.id, ts.service_date, ts.proof, ts.status, ts.user_id, u.email, a.address FROM therme_services ts JOIN users u ON ts.user_id=u.id JOIN apartments a ON ts.apartment_id=a.id ORDER BY ts.service_date DESC';
$services = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo t('therme_service'); ?></title>
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
<h1><?php echo t('therme_service'); ?></h1>
<table border="1">
<tr><th>ID</th><th><?php echo t('apartment'); ?></th><th><?php echo t('email'); ?></th><th><?php echo t('date'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('documents'); ?></th><th><?php echo t('save'); ?></th></tr>
<?php foreach ($services as $s): ?>
<tr>
<form method="post">
<td><?php echo (int)$s['id']; ?><input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>"></td>
<td><?php echo htmlspecialchars($s['address'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($s['email'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($s['service_date'], ENT_QUOTES, 'UTF-8'); ?></td>
<td>
    <select name="status">
        <?php foreach (['submitted','approved','rejected'] as $st): ?>
            <option value="<?php echo $st; ?>" <?php if ($s['status']==$st) echo 'selected'; ?>><?php echo $st; ?></option>
        <?php endforeach; ?>
    </select>
</td>
<td><?php if ($s['proof']) echo '<a href="uploads/therme/'.(int)$s['user_id'].'/'.htmlspecialchars($s['proof'], ENT_QUOTES, 'UTF-8').'">Download</a>'; ?></td>
<td><button><?php echo t('save'); ?></button></td>
</form>
</tr>
<?php endforeach; ?>
</table>
<nav><ul><li><a href="admin.php"><?php echo t('admin_menu'); ?></a></li></ul></nav>
</body>
</html>
