<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
$pdo = get_db();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/image_utils.php';
require_once __DIR__ . '/settings.php';
$primary = get_setting('primary_color', '#0d6efd');

$stmt = $pdo->prepare('SELECT a.id FROM apartments a JOIN tenant_apartment ta ON ta.apartment_id = a.id WHERE ta.user_id = ? AND ta.is_active = 1 LIMIT 1');
$stmt->execute([$user['id']]);
$apartment = $stmt->fetch();
$items = [];
if ($apartment) {
    $stmt = $pdo->prepare('SELECT id, item FROM apartment_inventory WHERE apartment_id = ?');
    $stmt->execute([$apartment['id']]);
    $items = $stmt->fetchAll();
}

$error = '';
$pre = isset($_GET['inv']) ? (int)$_GET['inv'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $apartment) {
$desc = trim($_POST['description'] ?? '');
$inv = (int)($_POST['inventory_id'] ?? 0);
$disc = $_POST['discovered_at'] ?? date('Y-m-d');
$type = $_POST['type'] ?? 'damage';
if ($desc) {
    $filename = null;
    if (!empty($_FILES['photo']['name']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
        $dir = __DIR__ . '/uploads/damages/' . $user['id'];
        list($ok,$name) = save_scaled_image($_FILES['photo'], $dir);
        if ($ok) {
            $filename = $name;
        } else {
            $error = $name;
        }
    }
    $late = (time() - strtotime($disc)) > 86400 ? 1 : 0;
    $is_wear = ($type === 'wear') ? 1 : 0;
    $stmt = $pdo->prepare('INSERT INTO damage_reports (user_id, apartment_id, inventory_id, description, image_path, discovered_at, is_wear, late_report) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$user['id'], $apartment['id'], $inv ?: null, $desc, $filename, $disc, $is_wear, $late]);
} else {
    $error = 'Bitte Beschreibung angeben';
}
}

$stmt = $pdo->prepare('SELECT dr.inventory_id, ai.item, dr.description, dr.image_path, dr.status, dr.created_at, dr.discovered_at, dr.is_wear, dr.late_report, dr.insurance_claim, dr.needs_pm FROM damage_reports dr LEFT JOIN apartment_inventory ai ON dr.inventory_id = ai.id WHERE dr.user_id = ? ORDER BY dr.created_at DESC');
$stmt->execute([$user['id']]);
$reports = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo t('damage_reports'); ?></title>
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
<h2><?php echo t('report_damage'); ?></h2>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <label><?php echo t('item'); ?>:
        <select name="inventory_id">
            <option value="">-</option>
            <?php foreach ($items as $it): ?>
                <option value="<?php echo (int)$it['id']; ?>" <?php echo $pre == $it['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($it['item'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>
    <label><?php echo t('damage_type'); ?>:
        <select name="type">
            <option value="damage"><?php echo t('damage'); ?></option>
            <option value="wear"><?php echo t('normal_wear'); ?></option>
        </select>
    </label><br>
    <label><?php echo t('discovered_at'); ?>: <input type="date" name="discovered_at" value="<?php echo date('Y-m-d'); ?>"></label><br>
    <label><?php echo t('description'); ?>:<br><textarea name="description" rows="4" cols="40" required></textarea></label><br>
    <label><?php echo t('photo'); ?>: <input type="file" name="photo" accept="image/jpeg,image/png"></label><br>
    <button type="submit"><?php echo t('save'); ?></button>
</form>
<h3><?php echo t('damage_reports'); ?></h3>
<table border="1" cellpadding="5">
<tr><th><?php echo t('date'); ?></th><th><?php echo t('discovered_at'); ?></th><th><?php echo t('item'); ?></th><th><?php echo t('damage_type'); ?></th><th><?php echo t('description'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('insurance_claim'); ?></th><th><?php echo t('notify_manager'); ?></th><th><?php echo t('progress'); ?></th><th><?php echo t('late_report'); ?></th><th><?php echo t('photo'); ?></th></tr>
<?php foreach ($reports as $r): ?>
<?php $perc = ['reported'=>25,'in_progress'=>50,'needs_info'=>75,'resolved'=>100]; $p = $perc[$r['status']] ?? 0; ?>
<tr>
<td><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars(substr($r['discovered_at'],0,10), ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['item'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo $r['is_wear'] ? t('normal_wear') : t('damage'); ?></td>
<td><?php echo htmlspecialchars($r['description'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo t($r['status']); ?></td>
<td><?php echo htmlspecialchars($r['insurance_claim'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo $r['needs_pm'] ? t('yes') : t('no'); ?></td>
<td><div class="progress"><div style="width:<?php echo $p; ?>%"></div></div></td>
<td><?php echo $r['late_report'] ? t('late_report') : ''; ?></td>
<td><?php if ($r['image_path']): ?><a href="<?php echo 'uploads/damages/' . (int)$user['id'] . '/' . htmlspecialchars($r['image_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><?php echo t('photo'); ?></a><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>