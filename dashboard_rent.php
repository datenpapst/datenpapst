<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
$pdo = get_db();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
$primary = get_setting('primary_color', '#0d6efd');

$stmt = $pdo->prepare('SELECT a.id, a.address, a.bank_name, a.bank_iban, a.bank_bic, a.rent_base, a.cpi_base, a.cpi_threshold, a.cpi_index, a.heating_included FROM apartments a JOIN tenant_apartment ta ON ta.apartment_id = a.id WHERE ta.user_id = ? AND ta.is_active = 1 LIMIT 1');
$stmt->execute([$user['id']]);
$apartment = $stmt->fetch();
$notice = null;
$op_notice = null;
$new_rent = $apartment['rent_base'];
$opcosts = [];
$last_inc = null;
if ($apartment) {
    $threshold = (float)$apartment['cpi_threshold'];
    $current_cpi = (float)get_setting('cpi_current_' . $apartment['cpi_index'], '100');
    $increase = ($current_cpi - $apartment['cpi_base']) / $apartment['cpi_base'] * 100;
    if ($increase < 0) { $increase = 0; }
    if ($increase > $threshold) {
        $new_rent = $apartment['rent_base'] * (1 + $increase/100);
        $notice = t('rent_increase');
    }
    $inc = $pdo->prepare('SELECT old_rent,new_rent,increase_amount,applied_at FROM rent_increases WHERE apartment_id=? ORDER BY applied_at DESC LIMIT 1');
    $inc->execute([$apartment['id']]);
    $last_inc = $inc->fetch();
    $stmt = $pdo->prepare('SELECT amount, effective_date FROM operating_costs WHERE apartment_id = ? ORDER BY effective_date DESC LIMIT 2');
    $stmt->execute([$apartment['id']]);
    $opcosts = $stmt->fetchAll();
    if ($apartment['heating_included']) {
        $hstmt = $pdo->prepare('SELECT amount, effective_date FROM heating_costs WHERE apartment_id = ? ORDER BY effective_date DESC');
        $hstmt->execute([$apartment['id']]);
        $heatcosts = $hstmt->fetchAll();
    } else {
        $heatcosts = [];
    }
    if (count($opcosts) >= 2 && $opcosts[0]['amount'] > $opcosts[1]['amount']) {
        $op_notice = t('op_cost_increase');
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo t('rent_costs'); ?></title>
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
<h1><?php echo t('rent_costs'); ?></h1>
<?php if ($apartment): ?>
<p><?php echo t('rent_due_first'); ?></p>
<p><?php echo t('no_offset'); ?></p>
<?php if ($notice): ?><p style="color:red;"><?php echo $notice; ?></p><?php endif; ?>
<table border="1">
<tr><th><?php echo t('rent_base'); ?></th><th><?php echo t('rent_new'); ?></th><th><?php echo t('rent_diff'); ?></th></tr>
<tr>
<td><?php echo number_format($apartment['rent_base'],2,',','.'); ?></td>
<td><?php echo number_format($new_rent,2,',','.'); ?></td>
<td><?php echo number_format($new_rent - $apartment['rent_base'],2,',','.'); ?></td>
</tr>
</table>
<?php if ($last_inc): ?><p><?php echo t('rent_increase_applied'); ?>: <?php echo $last_inc['applied_at']; ?> (+<?php echo number_format($last_inc['increase_amount'],2,',','.'); ?>)</p><?php endif; ?>
<h2><?php echo t('bank_details'); ?></h2>
<p><?php echo htmlspecialchars($apartment['bank_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?><br>
<?php echo t('iban'); ?>: <?php echo htmlspecialchars($apartment['bank_iban'] ?? '', ENT_QUOTES, 'UTF-8'); ?><br>
<?php echo t('bic'); ?>: <?php echo htmlspecialchars($apartment['bank_bic'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
<h2><?php echo t('operating_costs'); ?></h2>
<?php if ($op_notice): ?><p style="color:red;"><?php echo $op_notice; ?></p><?php endif; ?>
<table border="1">
<tr><th><?php echo t('date'); ?></th><th><?php echo t('amount'); ?></th></tr>
<?php foreach ($opcosts as $oc): ?>
<tr><td><?php echo $oc['effective_date']; ?></td><td><?php echo number_format($oc['amount'],2,',','.'); ?></td></tr>
<?php endforeach; ?>
</table>
<?php if ($apartment['heating_included']): ?>
<h2><?php echo t('heating_costs'); ?></h2>
<p><?php echo t('heating_notice'); ?></p>
<table border="1">
<tr><th><?php echo t('date'); ?></th><th><?php echo t('amount'); ?></th></tr>
<?php foreach ($heatcosts as $hc): ?>
<tr><td><?php echo $hc['effective_date']; ?></td><td><?php echo number_format($hc['amount'],2,',','.'); ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php endif; ?>
<nav>
<ul>
    <li><a href="dashboard.php"><?php echo t('dashboard_menu'); ?></a></li>
</ul>
</nav>
</body>
</html>
