<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
$pdo = get_db();
require_once __DIR__ . '/language.php';

$stmt = $pdo->prepare('SELECT a.id FROM apartments a JOIN tenant_apartment ta ON ta.apartment_id=a.id WHERE ta.user_id=? AND ta.is_active=1 LIMIT 1');
$stmt->execute([$user['id']]);
$apartment = $stmt->fetch();
if (!$apartment) { exit('Keine Wohnung'); }

$stmt = $pdo->prepare('SELECT id, item, quantity, purchase_date, warranty_months, included, updated_at FROM apartment_inventory WHERE apartment_id=?');
$stmt->execute([$apartment['id']]);
$items = $stmt->fetchAll();
$last = null;
foreach ($items as $i) {
    if ($last === null || $i['updated_at'] > $last) { $last = $i['updated_at']; }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo t('inventory'); ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<p><a href="dashboard.php">&larr; <?php echo t('dashboard_menu'); ?></a></p>
<h2><?php echo t('inventory'); ?></h2>
<?php if ($last): ?><p><?php echo t('inventory_last_update'); ?> <?php echo $last; ?></p><?php endif; ?>
<table border="1">
<tr><th><?php echo t('item'); ?></th><th><?php echo t('quantity'); ?></th><th><?php echo t('purchase_date'); ?></th><th><?php echo t('warranty_until'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('actions'); ?></th></tr>
<?php foreach ($items as $i): ?>
<tr>
    <td><?php echo htmlspecialchars($i['item'], ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php echo (int)$i['quantity']; ?></td>
    <td><?php echo htmlspecialchars($i['purchase_date']); ?></td>
    <td><?php echo ($i['purchase_date'] && $i['warranty_months']) ? date('Y-m-d', strtotime($i['purchase_date'] . ' +' . (int)$i['warranty_months'] . ' months')) : ''; ?></td>
    <td><?php echo $i['included'] ? '' : t('not_included'); ?></td>
    <td><a href="dashboard_damage.php?inv=<?php echo (int)$i['id']; ?>"><?php echo t('report_damage'); ?></a></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
