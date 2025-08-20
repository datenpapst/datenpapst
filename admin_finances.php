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
$error = '';
require_once __DIR__ . '/upload_utils.php';
$primary = get_setting('primary_color', '#0d6efd');

$apartment_id = (int)($_GET['apartment_id'] ?? 0);
$apartments = $pdo->query('SELECT id, address, heating_included FROM apartments')->fetchAll();
if (!$apartment_id && $apartments) { $apartment_id = $apartments[0]['id']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_rent'])) {
        $apartment_id = (int)($_POST['apartment_id']);
        $date = $_POST['payment_date'] ?? date('Y-m-d');
        $rent = (float)($_POST['rent'] ?? 0);
        $op_u = (float)($_POST['op_costs_umlegbar'] ?? 0);
        $op_nu = (float)($_POST['op_costs_nonumlegbar'] ?? 0);
        $stmt = $pdo->prepare('INSERT INTO rent_payments (apartment_id, payment_date, rent, op_costs_umlegbar, op_costs_nonumlegbar) VALUES (?,?,?,?,?)');
        $stmt->execute([$apartment_id, $date, $rent, $op_u, $op_nu]);
    } elseif (isset($_POST['add_expense'])) {
        $apartment_id = (int)($_POST['apartment_id']);
        $date = $_POST['expense_date'] ?? date('Y-m-d');
        $desc = trim($_POST['description'] ?? '');
        $catg = $_POST['category'] ?? 'general';
        $amount = (float)($_POST['amount'] ?? 0);
        $file = null;
        if (!empty($_FILES['invoice']['name'])) {
            $dir = __DIR__ . '/uploads/expenses/' . $apartment_id;
            if (!is_dir($dir)) { mkdir($dir,0770,true); }
            $file = basename($_FILES['invoice']['name']);
            if (!scan_file($_FILES['invoice']['tmp_name'])) {
                $error = 'Datei blockiert (Virusverdacht)';
                $file = null;
            } else {
                move_uploaded_file($_FILES['invoice']['tmp_name'], $dir . '/' . $file);
            }
        }
        $stmt = $pdo->prepare('INSERT INTO apartment_expenses (apartment_id, description, category, amount, expense_date, invoice) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$apartment_id, $desc, $catg, $amount, $date, $file]);
    } elseif (isset($_POST['add_op'])) {
        $apartment_id = (int)$_POST['apartment_id'];
        $date = $_POST['effective_date'] ?? date('Y-m-d');
        $amount = (float)($_POST['amount'] ?? 0);
        $stmt = $pdo->prepare('INSERT INTO operating_costs (apartment_id, amount, effective_date) VALUES (?,?,?)');
        $stmt->execute([$apartment_id, $amount, $date]);
    } elseif (isset($_POST['add_heat'])) {
        $apartment_id = (int)$_POST['apartment_id'];
        $date = $_POST['effective_date'] ?? date('Y-m-d');
        $amount = (float)($_POST['amount'] ?? 0);
        $stmt = $pdo->prepare('INSERT INTO heating_costs (apartment_id, amount, effective_date) VALUES (?,?,?)');
        $stmt->execute([$apartment_id, $amount, $date]);
    }
}

$sql = 'SELECT payment_date, (rent + op_costs_umlegbar + op_costs_nonumlegbar) AS total FROM rent_payments WHERE apartment_id=? ORDER BY payment_date';
$rent_stmt = $pdo->prepare($sql);
$rent_stmt->execute([$apartment_id]);
$rents = $rent_stmt->fetchAll();
$exp_stmt = $pdo->prepare('SELECT expense_date, amount, description, category, invoice FROM apartment_expenses WHERE apartment_id=? ORDER BY expense_date');
$exp_stmt->execute([$apartment_id]);
$expenses = $exp_stmt->fetchAll();
$op_stmt = $pdo->prepare('SELECT effective_date, amount FROM operating_costs WHERE apartment_id=? ORDER BY effective_date DESC');
$op_stmt->execute([$apartment_id]);
$opcosts = $op_stmt->fetchAll();
$heat_stmt = $pdo->prepare('SELECT effective_date, amount FROM heating_costs WHERE apartment_id=? ORDER BY effective_date DESC');
$heat_stmt->execute([$apartment_id]);
$heatcosts = $heat_stmt->fetchAll();
$has_heating = false;
foreach ($apartments as $a) { if ($a['id'] == $apartment_id) { $has_heating = (bool)$a['heating_included']; break; } }
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Finanzen</title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
<h1>Finanzen</h1>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form method="get">
    <label>Wohnung:
        <select name="apartment_id" onchange="this.form.submit()">
        <?php foreach ($apartments as $a): ?>
            <option value="<?php echo (int)$a['id']; ?>" <?php if ($apartment_id==$a['id']) echo 'selected'; ?>><?php echo htmlspecialchars($a['address'], ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
        </select>
    </label>
</form>
<h2>Einnahmen</h2>
<table border="1">
<tr><th>Datum</th><th>Summe</th></tr>
<?php foreach ($rents as $r): ?>
<tr><td><?php echo $r['payment_date']; ?></td><td><?php echo number_format($r['total'],2,',','.'); ?></td></tr>
<?php endforeach; ?>
</table>
<form method="post">
    <input type="hidden" name="add_rent" value="1">
    <input type="hidden" name="apartment_id" value="<?php echo (int)$apartment_id; ?>">
    <label>Datum: <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>"></label>
    <label>Miete: <input type="number" step="0.01" name="rent"></label>
    <label>BK umlegbar: <input type="number" step="0.01" name="op_costs_umlegbar"></label>
    <label>BK nicht umlegbar: <input type="number" step="0.01" name="op_costs_nonumlegbar"></label>
    <button type="submit">Speichern</button>
</form>
<h2><?php echo t('operating_costs'); ?></h2>
<table border="1">
<tr><th><?php echo t('date'); ?></th><th><?php echo t('amount'); ?></th></tr>
<?php foreach ($opcosts as $oc): ?>
<tr><td><?php echo $oc['effective_date']; ?></td><td><?php echo number_format($oc['amount'],2,',','.'); ?></td></tr>
<?php endforeach; ?>
</table>
<form method="post">
    <input type="hidden" name="add_op" value="1">
    <input type="hidden" name="apartment_id" value="<?php echo (int)$apartment_id; ?>">
    <label><?php echo t('date'); ?>: <input type="date" name="effective_date" value="<?php echo date('Y-m-d'); ?>"></label>
    <label><?php echo t('amount'); ?>: <input type="number" step="0.01" name="amount"></label>
    <button type="submit"><?php echo t('save'); ?></button>
</form>
<?php if ($has_heating): ?>
<h2><?php echo t('heating_costs'); ?></h2>
<table border="1">
<tr><th><?php echo t('date'); ?></th><th><?php echo t('amount'); ?></th></tr>
<?php foreach ($heatcosts as $hc): ?>
<tr><td><?php echo $hc['effective_date']; ?></td><td><?php echo number_format($hc['amount'],2,',','.'); ?></td></tr>
<?php endforeach; ?>
</table>
<form method="post">
    <input type="hidden" name="add_heat" value="1">
    <input type="hidden" name="apartment_id" value="<?php echo (int)$apartment_id; ?>">
    <label><?php echo t('date'); ?>: <input type="date" name="effective_date" value="<?php echo date('Y-m-d'); ?>"></label>
    <label><?php echo t('amount'); ?>: <input type="number" step="0.01" name="amount"></label>
    <button type="submit"><?php echo t('save'); ?></button>
</form>
<?php endif; ?>
<h2><?php echo t('expenses'); ?></h2>
<table border="1">
<tr><th><?php echo t('date'); ?></th><th><?php echo t('amount'); ?></th><th><?php echo t('description'); ?></th><th><?php echo t('expense_category'); ?></th><th><?php echo t('invoice'); ?></th></tr>
<?php foreach ($expenses as $e): ?>
<tr>
<td><?php echo $e['expense_date']; ?></td>
<td><?php echo number_format($e['amount'],2,',','.'); ?></td>
<td><?php echo htmlspecialchars($e['description'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo t($e['category']); ?></td>
<td><?php if ($e['invoice']) echo '<a href="uploads/expenses/'.$apartment_id.'/'.htmlspecialchars($e['invoice'], ENT_QUOTES, 'UTF-8').'">Download</a>'; ?></td>
</tr>
<?php endforeach; ?>
</table>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="add_expense" value="1">
    <input type="hidden" name="apartment_id" value="<?php echo (int)$apartment_id; ?>">
    <label><?php echo t('date'); ?>: <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>"></label>
    <label><?php echo t('amount'); ?>: <input type="number" step="0.01" name="amount"></label>
    <label><?php echo t('description'); ?>: <input type="text" name="description"></label>
    <label><?php echo t('expense_category'); ?>: <select name="category">
        <option value="general"><?php echo t('expense_general'); ?></option>
        <option value="advertising"><?php echo t('expense_advertising'); ?></option>
        <option value="renovation"><?php echo t('expense_renovation'); ?></option>
        <option value="handyman"><?php echo t('expense_handyman'); ?></option>
    </select></label>
    <label><?php echo t('invoice'); ?>: <input type="file" name="invoice"></label>
    <button type="submit">Speichern</button>
</form>
<canvas id="financeChart" width="400" height="200"></canvas>
<script>
const rentData = <?php echo json_encode(array_column($rents,'total')); ?>;
const rentLabels = <?php echo json_encode(array_column($rents,'payment_date')); ?>;
const expData = <?php echo json_encode(array_column($expenses,'amount')); ?>;
const expLabels = <?php echo json_encode(array_column($expenses,'expense_date')); ?>;
new Chart(document.getElementById('financeChart'), {
    type: 'line',
    data: {
        labels: rentLabels,
        datasets: [
            {label: 'Einnahmen', data: rentData, borderColor: 'green', fill:false},
            {label: 'Ausgaben', data: expData, borderColor: 'red', fill:false}
        ]
    }
});
</script>
</body>
</html>
