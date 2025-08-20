<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Zugriff verweigert');
}
$pdo = get_db();
$baseDir = __DIR__ . '/uploads/inventory_invoices';
require_once __DIR__ . '/upload_utils.php';
$apartments = $pdo->query('SELECT id,address FROM apartments')->fetchAll();
$apartment_id = (int)($_GET['apartment_id'] ?? ($apartments[0]['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apartment_id = (int)($_POST['apartment_id'] ?? 0);
    if (isset($_POST['add'])) {
        $item = trim($_POST['item'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 1);
        $included = isset($_POST['included']) ? 1 : 0;
        $purchase = $_POST['purchase_date'] ?: null;
        $warranty = (int)($_POST['warranty_months'] ?? 0);
        $invoiceName = null;
        if (!empty($_FILES['invoice']['name'])) {
            $ext = strtolower(pathinfo($_FILES['invoice']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf','jpg','jpeg','png'];
            if (in_array($ext, $allowed, true) && scan_file($_FILES['invoice']['tmp_name'])) {
                $dir = $baseDir . '/' . $apartment_id;
                if (!is_dir($dir)) { mkdir($dir,0770,true); }
                $invoiceName = bin2hex(random_bytes(8)) . '.' . $ext;
                move_uploaded_file($_FILES['invoice']['tmp_name'], $dir . '/' . $invoiceName);
            }
        }
        $stmt = $pdo->prepare('INSERT INTO apartment_inventory (apartment_id,item,quantity,purchase_date,warranty_months,invoice_path,included) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$apartment_id,$item,$quantity,$purchase,$warranty,$invoiceName,$included]);
    } elseif (isset($_POST['delete'])) {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM apartment_inventory WHERE id=? AND apartment_id=?');
        $stmt->execute([$id,$apartment_id]);
    }
}

$items = [];
if ($apartment_id) {
$stmt = $pdo->prepare('SELECT id,item,quantity,purchase_date,warranty_months,invoice_path,included,updated_at FROM apartment_inventory WHERE apartment_id=?');
    $stmt->execute([$apartment_id]);
    $items = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Inventar verwalten</title>
</head>
<body>
<p>Admin <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="admin.php">Menü</a> | <a href="logout.php">Logout</a></p>
<h2>Inventar verwalten</h2>
<form method="get">
    <label>Wohnung:
        <select name="apartment_id">
        <?php foreach ($apartments as $a): ?>
            <option value="<?php echo (int)$a['id']; ?>" <?php if ($a['id']==$apartment_id) echo 'selected'; ?>><?php echo htmlspecialchars($a['address'], ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
        </select>
    </label>
    <button>Auswählen</button>
</form>
<?php if ($apartment_id): ?>
<h3>Einträge</h3>
<table border="1">
<tr><th>Gegenstand</th><th>Anzahl</th><th>Kaufdatum</th><th>Garantie (Monate)</th><th>Rechnung</th><th>Mitvermietet</th><th></th></tr>
<?php foreach ($items as $i): ?>
<tr>
    <td><?php echo htmlspecialchars($i['item'], ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php echo (int)$i['quantity']; ?></td>
    <td><?php echo htmlspecialchars($i['purchase_date']); ?></td>
    <td><?php echo $i['warranty_months']; ?></td>
    <td><?php if ($i['invoice_path']): ?><a href="uploads/inventory_invoices/<?php echo (int)$apartment_id; ?>/<?php echo htmlspecialchars($i['invoice_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">Download</a><?php endif; ?></td>
    <td><?php echo $i['included'] ? 'Ja' : 'Nein'; ?></td>
    <td>
        <form method="post" style="display:inline">
            <input type="hidden" name="apartment_id" value="<?php echo (int)$apartment_id; ?>">
            <input type="hidden" name="id" value="<?php echo (int)$i['id']; ?>">
            <button name="delete" value="1">Löschen</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>
<h3>Neu</h3>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="apartment_id" value="<?php echo (int)$apartment_id; ?>">
    <label>Gegenstand: <input type="text" name="item"></label>
    <label>Anzahl: <input type="number" name="quantity" min="1" value="1"></label>
    <label>Kaufdatum: <input type="date" name="purchase_date"></label>
    <label>Garantie in Monaten: <input type="number" name="warranty_months" min="0"></label>
    <label>Rechnung: <input type="file" name="invoice" accept="application/pdf,image/jpeg,image/png"></label>
    <label><input type="checkbox" name="included" value="1" checked> Mitvermietet</label>
    <button name="add" value="1">Hinzufügen</button>
</form>
<?php endif; ?>
</body>
</html>
