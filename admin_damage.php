<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Zugriff verweigert');
}
$pdo = get_db();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
$primary = get_setting('primary_color', '#0d6efd');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $claim = trim($_POST['insurance_claim'] ?? '');
    $needs = isset($_POST['needs_pm']) ? 1 : 0;
    if ($id && in_array($status, ['reported','in_progress','needs_info','resolved'], true)) {
        $stmt = $pdo->prepare('UPDATE damage_reports SET status = ?, resolved_at = CASE WHEN ? = "resolved" THEN NOW() ELSE NULL END, insurance_claim=?, needs_pm=? WHERE id = ?');
        $stmt->execute([$status, $status, $claim, $needs, $id]);
        if ($needs) {
            $chk = $pdo->prepare('SELECT pm_notified_at, apartment_id, description FROM damage_reports WHERE id=?');
            $chk->execute([$id]);
            $info = $chk->fetch(PDO::FETCH_ASSOC);
            if (empty($info['pm_notified_at'])) {
                require_once __DIR__ . '/mail_utils.php';
                notify_manager((int)$info['apartment_id'], 'Schadenmeldung', $info['description']);
                $pdo->prepare('UPDATE damage_reports SET pm_notified_at=NOW() WHERE id=?')->execute([$id]);
            }
        }
    }
}

$stmt = $pdo->query('SELECT dr.id, dr.user_id, u.email, a.address, dr.description, dr.image_path, dr.status, dr.created_at, dr.discovered_at, dr.is_wear, dr.late_report, dr.insurance_claim, dr.needs_pm, dr.pm_notified_at, ai.item FROM damage_reports dr JOIN users u ON dr.user_id = u.id JOIN apartments a ON dr.apartment_id = a.id LEFT JOIN apartment_inventory ai ON dr.inventory_id = ai.id ORDER BY dr.created_at DESC');
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
<p><?php echo t('logged_in_as'); ?> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="admin.php"><?php echo t('admin_menu'); ?></a> | <a href="logout.php"><?php echo t('logout'); ?></a></p>
<label class="switch">
    <input type="checkbox" id="lang-toggle" <?php echo $lang === 'en' ? 'checked' : ''; ?>>
    <span class="slider lang-slider"></span>
</label>
<label class="switch">
    <input type="checkbox" id="dark-toggle">
    <span class="slider"></span>
</label>
<h2><?php echo t('damage_reports'); ?></h2>
<table border="1" cellpadding="5">
<tr><th><?php echo t('date'); ?></th><th><?php echo t('discovered_at'); ?></th><th><?php echo t('apartment'); ?></th><th><?php echo t('email'); ?></th><th><?php echo t('item'); ?></th><th><?php echo t('damage_type'); ?></th><th><?php echo t('description'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('insurance_claim'); ?></th><th><?php echo t('notify_manager'); ?></th><th><?php echo t('progress'); ?></th><th><?php echo t('late_report'); ?></th><th><?php echo t('photo'); ?></th><th><?php echo t('actions'); ?></th></tr>
<?php foreach ($reports as $r): ?>
<?php $perc = ['reported'=>25,'in_progress'=>50,'needs_info'=>75,'resolved'=>100]; $p = $perc[$r['status']] ?? 0; ?>
<tr>
<td><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars(substr($r['discovered_at'],0,10), ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['address'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['email'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['item'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo $r['is_wear'] ? t('normal_wear') : t('damage'); ?></td>
<td><?php echo htmlspecialchars($r['description'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo t($r['status']); ?></td>
<td><?php echo htmlspecialchars($r['insurance_claim'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo $r['needs_pm'] ? t('yes') : t('no'); ?><?php if($r['pm_notified_at']) echo ' ('.t('sent_to_manager').')'; ?></td>
<td><div class="progress"><div style="width:<?php echo $p; ?>%"></div></div></td>
<td><?php echo $r['late_report'] ? t('late_report') : ''; ?></td>
<td><?php if ($r['image_path']): ?><a href="<?php echo 'uploads/damages/' . (int)$r['user_id'] . '/' . htmlspecialchars($r['image_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><?php echo t('photo'); ?></a><?php endif; ?></td>
<td><form method="post" style="display:inline"><input type="hidden" name="id" value="<?php echo $r['id']; ?>">
<input type="text" name="insurance_claim" value="<?php echo htmlspecialchars($r['insurance_claim'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" size="8">
<label><input type="checkbox" name="needs_pm" <?php if($r['needs_pm']) echo 'checked'; ?>><?php echo t('notify_manager'); ?></label>
<select name="status">
    <option value="reported" <?php echo $r['status']=='reported'?'selected':''; ?>><?php echo t('reported'); ?></option>
    <option value="in_progress" <?php echo $r['status']=='in_progress'?'selected':''; ?>><?php echo t('in_progress'); ?></option>
    <option value="needs_info" <?php echo $r['status']=='needs_info'?'selected':''; ?>><?php echo t('needs_info'); ?></option>
    <option value="resolved" <?php echo $r['status']=='resolved'?'selected':''; ?>><?php echo t('resolved'); ?></option>
</select><button type="submit"><?php echo t('save'); ?></button></form></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
