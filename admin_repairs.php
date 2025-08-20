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

$stmt = $pdo->query('SELECT sr.id, sr.user_id, sr.repair_date, sr.description, sr.cost, sr.invoice, u.email, a.address,
    (SELECT SUM(cost) FROM small_repairs sr2 WHERE sr2.user_id = sr.user_id AND YEAR(sr2.repair_date) = YEAR(sr.repair_date)) AS year_total
    FROM small_repairs sr JOIN users u ON sr.user_id = u.id JOIN apartments a ON sr.apartment_id = a.id ORDER BY sr.repair_date DESC');
$repairs = $stmt->fetchAll();
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
<p><?php echo t('logged_in_as'); ?> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="admin.php"><?php echo t('admin_menu'); ?></a> | <a href="logout.php"><?php echo t('logout'); ?></a></p>
<label class="switch">
    <input type="checkbox" id="lang-toggle" <?php echo $lang === 'en' ? 'checked' : ''; ?>>
    <span class="slider lang-slider"></span>
</label>
<label class="switch">
    <input type="checkbox" id="dark-toggle">
    <span class="slider"></span>
</label>
<h2><?php echo t('small_repairs'); ?></h2>
<table border="1" cellpadding="5">
<tr><th><?php echo t('repair_date'); ?></th><th><?php echo t('apartment'); ?></th><th><?php echo t('email'); ?></th><th><?php echo t('description'); ?></th><th><?php echo t('cost'); ?></th><th><?php echo t('year_total'); ?></th><th><?php echo t('invoice'); ?></th></tr>
<?php foreach ($repairs as $r): ?>
<tr>
<td><?php echo htmlspecialchars($r['repair_date'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['address'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['email'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['description'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo number_format($r['cost'], 2); ?></td>
<td><?php echo number_format($r['year_total'], 2); ?></td>
<td><?php if ($r['invoice']): ?><a href="<?php echo 'uploads/repairs/' . (int)$r['user_id'] . '/' . htmlspecialchars($r['invoice'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><?php echo t('invoice'); ?></a><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
