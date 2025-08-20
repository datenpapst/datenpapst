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

if ($apartment_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['service_date'] ?? date('Y-m-d');
    $file = null;
    if (!empty($_FILES['invoice']['name']) && is_uploaded_file($_FILES['invoice']['tmp_name'])) {
        if (scan_file($_FILES['invoice']['tmp_name'])) {
            $dir = __DIR__ . '/uploads/therme/' . $user['id'];
            if (!is_dir($dir)) { mkdir($dir,0770,true); }
            $file = basename($_FILES['invoice']['name']);
            move_uploaded_file($_FILES['invoice']['tmp_name'], $dir . '/' . $file);
        }
    }
    $stmt = $pdo->prepare('INSERT INTO therme_services (user_id, apartment_id, service_date, proof) VALUES (?,?,?,?)');
    $stmt->execute([$user['id'], $apartment_id, $date, $file]);
}

$records = [];
$last = null;
if ($apartment_id) {
    $stmt = $pdo->prepare('SELECT id, service_date, proof, status FROM therme_services WHERE user_id=? AND apartment_id=? ORDER BY service_date DESC');
    $stmt->execute([$user['id'], $apartment_id]);
    $records = $stmt->fetchAll();
    if ($records) { $last = $records[0]['service_date']; }
}
$due = !$last || (strtotime($last) < strtotime('-1 year'));
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
<?php if ($due): ?><p style="color:red;"><?php echo t('service_due'); ?></p><?php endif; ?>
<?php if ($apartment_id): ?>
<form method="post" enctype="multipart/form-data">
    <label><?php echo t('date'); ?>: <input type="date" name="service_date" value="<?php echo date('Y-m-d'); ?>"></label>
    <label><?php echo t('upload_proof'); ?>: <input type="file" name="invoice"></label>
    <button type="submit"><?php echo t('save'); ?></button>
</form>
<h2><?php echo t('service_records'); ?></h2>
<table border="1">
<tr><th><?php echo t('date'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('documents'); ?></th></tr>
<?php foreach ($records as $r): ?>
<tr>
    <td><?php echo htmlspecialchars($r['service_date'], ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php echo htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php if ($r['proof']) echo '<a href="uploads/therme/'.$user['id'].'/'.htmlspecialchars($r['proof'], ENT_QUOTES, 'UTF-8').'">Download</a>'; ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<nav><ul><li><a href="dashboard.php"><?php echo t('dashboard_menu'); ?></a></li></ul></nav>
</body>
</html>
