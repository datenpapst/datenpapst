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

$year = (int)($_GET['year'] ?? date('Y'));
$apartments = $pdo->query('SELECT id, address FROM apartments')->fetchAll();
$apartment_id = (int)($_GET['apartment_id'] ?? ($apartments[0]['id'] ?? 0));

$start = sprintf('%d-01-01', $year);
$end   = sprintf('%d-12-31', $year);

$stmt = $pdo->prepare('SELECT SUM(rent) rent, SUM(op_costs_umlegbar) op_u, SUM(op_costs_nonumlegbar) op_nu FROM rent_payments WHERE apartment_id=? AND payment_date BETWEEN ? AND ?');
$stmt->execute([$apartment_id, $start, $end]);
$rent = $stmt->fetch();

$exp_stmt = $pdo->prepare('SELECT category, SUM(amount) total FROM apartment_expenses WHERE apartment_id=? AND expense_date BETWEEN ? AND ? GROUP BY category');
$exp_stmt->execute([$apartment_id, $start, $end]);
$expenses = ['advertising'=>0,'renovation'=>0,'handyman'=>0,'general'=>0];
foreach ($exp_stmt as $row) { $expenses[$row['category']] = (float)$row['total']; }

$op_stmt = $pdo->prepare('SELECT SUM(amount) FROM operating_costs WHERE apartment_id=? AND effective_date BETWEEN ? AND ?');
$op_stmt->execute([$apartment_id, $start, $end]);
$op_total = (float)$op_stmt->fetchColumn();

$heat_stmt = $pdo->prepare('SELECT SUM(amount) FROM heating_costs WHERE apartment_id=? AND effective_date BETWEEN ? AND ?');
$heat_stmt->execute([$apartment_id, $start, $end]);
$heat_total = (float)$heat_stmt->fetchColumn();

$income_total = (float)$rent['rent'] + (float)$rent['op_u'] + (float)$rent['op_nu'];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo t('tax_summary'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
</head>
<body>
<div id="burger">☰</div>
<p><?php echo t('logged_in_as'); ?> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="logout.php"><?php echo t('logout'); ?></a></p>
<h1><?php echo t('tax_summary'); ?></h1>
<form method="get">
    <label><?php echo t('apartment'); ?>:
        <select name="apartment_id" onchange="this.form.submit()">
        <?php foreach ($apartments as $a): ?>
            <option value="<?php echo (int)$a['id']; ?>" <?php if($apartment_id==$a['id']) echo 'selected'; ?>><?php echo htmlspecialchars($a['address'], ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
        </select>
    </label>
    <label><?php echo t('year'); ?>:
        <input type="number" name="year" value="<?php echo $year; ?>" onchange="this.form.submit()">
    </label>
</form>

<table border="1">
<tr><th><?php echo t('category'); ?></th><th><?php echo t('amount'); ?></th></tr>
<tr><td><?php echo t('rent_costs'); ?></td><td><?php echo number_format($income_total,2,',','.'); ?></td></tr>
<tr><td><?php echo t('operating_costs'); ?></td><td><?php echo number_format($op_total,2,',','.'); ?></td></tr>
<tr><td><?php echo t('heating_costs'); ?></td><td><?php echo number_format($heat_total,2,',','.'); ?></td></tr>
<tr><td><?php echo t('expense_advertising'); ?></td><td><?php echo number_format($expenses['advertising'],2,',','.'); ?></td></tr>
<tr><td><?php echo t('expense_renovation'); ?></td><td><?php echo number_format($expenses['renovation'],2,',','.'); ?></td></tr>
<tr><td><?php echo t('expense_handyman'); ?></td><td><?php echo number_format($expenses['handyman'],2,',','.'); ?></td></tr>
<tr><td><?php echo t('expense_general'); ?></td><td><?php echo number_format($expenses['general'],2,',','.'); ?></td></tr>
</table>
</body>
</html>
