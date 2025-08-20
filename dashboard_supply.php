<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
$pdo = get_db();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/upload_utils.php';
$primary = get_setting('primary_color', '#0d6efd');

$ap_stmt = $pdo->prepare('SELECT a.id FROM apartments a JOIN tenant_apartment ta ON ta.apartment_id=a.id WHERE ta.user_id=? AND ta.is_active=1 LIMIT 1');
$ap_stmt->execute([$user['id']]);
$apartment_id = $ap_stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $apartment_id) {
    $type = $_POST['contract_type'] === 'gas' ? 'gas' : 'electricity';
    $provider = trim($_POST['provider'] ?? '');
    $start = $_POST['contract_start'] ?? null;
    $end = $_POST['contract_end'] ?? null;
    $file = null;
    if (!empty($_FILES['proof']['name']) && is_uploaded_file($_FILES['proof']['tmp_name'])) {
        if (scan_file($_FILES['proof']['tmp_name'])) {
            $dir = __DIR__ . '/uploads/supply/' . $user['id'];
            if (!is_dir($dir)) { mkdir($dir,0770,true); }
            $file = basename($_FILES['proof']['name']);
            move_uploaded_file($_FILES['proof']['tmp_name'], $dir . '/' . $file);
        }
    }
    $stmt = $pdo->prepare('INSERT INTO supply_contracts (user_id, apartment_id, contract_type, provider, contract_start, contract_end, proof) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$user['id'], $apartment_id, $type, $provider, $start, $end, $file]);
}

$contracts = [];
if ($apartment_id) {
    $stmt = $pdo->prepare('SELECT contract_type, provider, contract_start, contract_end, proof, status FROM supply_contracts WHERE user_id=? AND apartment_id=? ORDER BY contract_start DESC');
    $stmt->execute([$user['id'], $apartment_id]);
    $contracts = $stmt->fetchAll();
}
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
<?php if ($apartment_id): ?>
<form method="post" enctype="multipart/form-data">
    <label><?php echo t('contract_type'); ?>:
        <select name="contract_type">
            <option value="electricity">Strom</option>
            <option value="gas">Gas</option>
        </select>
    </label>
    <label><?php echo t('provider'); ?>: <input type="text" name="provider"></label>
    <label><?php echo t('contract_start'); ?>: <input type="date" name="contract_start"></label>
    <label><?php echo t('contract_end'); ?>: <input type="date" name="contract_end"></label>
    <label><?php echo t('upload_proof'); ?>: <input type="file" name="proof"></label>
    <button type="submit"><?php echo t('save'); ?></button>
</form>
<table border="1">
<tr><th><?php echo t('contract_type'); ?></th><th><?php echo t('provider'); ?></th><th><?php echo t('contract_start'); ?></th><th><?php echo t('contract_end'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('documents'); ?></th></tr>
<?php foreach ($contracts as $c): ?>
<tr>
<td><?php echo $c['contract_type']; ?></td>
<td><?php echo htmlspecialchars($c['provider'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo $c['contract_start']; ?></td>
<td><?php echo $c['contract_end']; ?></td>
<td><?php echo $c['status']; ?></td>
<td><?php if ($c['proof']) echo '<a href="uploads/supply/'.$user['id'].'/'.htmlspecialchars($c['proof'], ENT_QUOTES, 'UTF-8').'">Download</a>'; ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<nav><ul><li><a href="dashboard.php"><?php echo t('dashboard_menu'); ?></a></li></ul></nav>
</body>
</html>
