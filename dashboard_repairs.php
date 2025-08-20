<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
$pdo = get_db();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/upload_utils.php';
$primary = get_setting('primary_color', '#0d6efd');

$stmt = $pdo->prepare('SELECT a.id FROM apartments a JOIN tenant_apartment ta ON ta.apartment_id = a.id WHERE ta.user_id = ? AND ta.is_active = 1 LIMIT 1');
$stmt->execute([$user['id']]);
$apartment = $stmt->fetch();
$error = '';
if ($apartment && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $desc = trim($_POST['description'] ?? '');
    $cost = (float)($_POST['cost'] ?? 0);
    $date = $_POST['repair_date'] ?? date('Y-m-d');
    if ($desc && $cost > 0 && $cost <= 150) {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(cost),0) FROM small_repairs WHERE user_id = ? AND YEAR(repair_date) = YEAR(?)');
        $stmt->execute([$user['id'], $date]);
        $sum = (float)$stmt->fetchColumn();
        if ($sum + $cost <= 600) {
            $filename = null;
            if (!empty($_FILES['invoice']['name']) && is_uploaded_file($_FILES['invoice']['tmp_name'])) {
                $ext = strtolower(pathinfo($_FILES['invoice']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf','jpg','jpeg','png'], true) && $_FILES['invoice']['size'] <= 5*1024*1024) {
                    $tmp = $_FILES['invoice']['tmp_name'];
                    if (scan_file($tmp)) {
                        $dir = __DIR__ . '/uploads/repairs/' . $user['id'];
                        if (!is_dir($dir)) { mkdir($dir, 0777, true); }
                        $filename = uniqid() . '.' . $ext;
                        move_uploaded_file($tmp, $dir . '/' . $filename);
                    } else {
                        $error = t('file_rejected');
                    }
                } else {
                    $error = t('invalid_file');
                }
            }
            if (!$error) {
                $stmt = $pdo->prepare('INSERT INTO small_repairs (user_id, apartment_id, description, cost, repair_date, invoice) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$user['id'], $apartment['id'], $desc, $cost, $date, $filename]);
            }
        } else {
            $error = t('year_limit_exceeded');
        }
    } else {
        $error = t('cost_limit_exceeded');
    }
}
$stmt = $pdo->prepare('SELECT repair_date, description, cost, invoice FROM small_repairs WHERE user_id = ? ORDER BY repair_date DESC');
$stmt->execute([$user['id']]);
$repairs = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT COALESCE(SUM(cost),0) FROM small_repairs WHERE user_id = ? AND YEAR(repair_date)=YEAR(CURDATE())');
$stmt->execute([$user['id']]);
$year_sum = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo t('small_repairs'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="script.js" defer></script>
</head>
<body>
<div id="burger">☰</div>
<p><?php echo t('logged_in_as'); ?> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="dashboard.php"><?php echo t('dashboard_menu'); ?></a> | <a href="logout.php"><?php echo t('logout'); ?></a></p>
<label class="switch">
    <input type="checkbox" id="lang-toggle" <?php echo $lang === 'en' ? 'checked' : ''; ?>>
    <span class="slider lang-slider"></span>
</label>
<label class="switch">
    <input type="checkbox" id="dark-toggle">
    <span class="slider"></span>
</label>
<h2><?php echo t('small_repairs'); ?></h2>
<p><?php echo t('max_repair_notice'); ?></p>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<?php if ($apartment): ?>
<form method="post" enctype="multipart/form-data">
    <label><?php echo t('repair_date'); ?>: <input type="date" name="repair_date" value="<?php echo date('Y-m-d'); ?>"></label><br>
    <label><?php echo t('description'); ?>:<br><textarea name="description" rows="4" cols="40" required></textarea></label><br>
    <label><?php echo t('cost'); ?>: <input type="number" name="cost" step="0.01" required></label><br>
    <label><?php echo t('invoice'); ?>: <input type="file" name="invoice" accept="application/pdf,image/jpeg,image/png"></label><br>
    <button type="submit"><?php echo t('add_repair'); ?></button>
</form>
<?php endif; ?>
<h3><?php echo t('year_total'); ?>: <?php echo number_format($year_sum, 2); ?></h3>
<table border="1" cellpadding="5">
<tr><th><?php echo t('repair_date'); ?></th><th><?php echo t('description'); ?></th><th><?php echo t('cost'); ?></th><th><?php echo t('invoice'); ?></th></tr>
<?php foreach ($repairs as $r): ?>
<tr>
<td><?php echo htmlspecialchars($r['repair_date'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['description'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo number_format($r['cost'], 2); ?></td>
<td><?php if ($r['invoice']): ?><a href="<?php echo 'uploads/repairs/' . (int)$user['id'] . '/' . htmlspecialchars($r['invoice'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><?php echo t('invoice'); ?></a><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
