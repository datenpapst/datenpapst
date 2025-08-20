<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/upload_utils.php';
$pdo = get_db();

$stmt = $pdo->prepare('SELECT * FROM moveouts WHERE user_id=? ORDER BY id DESC LIMIT 1');
$stmt->execute([$user['id']]);
$moveout = $stmt->fetch();
if (!$moveout) { header('Location: dashboard.php'); exit; }
$mid = $moveout['id'];
$error = '';

if (isset($_POST['bank_account'])) {
    $pdo->prepare('UPDATE moveouts SET bank_account=? WHERE id=? AND user_id=?')
        ->execute([trim($_POST['bank_account']), $mid, $user['id']]);
    $moveout['bank_account'] = trim($_POST['bank_account']);
}

if (isset($_POST['task_id'])) {
    $tid = (int)$_POST['task_id'];
    $done = isset($_POST['done']) ? 1 : 0;
    $pdo->prepare('UPDATE moveout_task_status SET tenant_done=? WHERE moveout_id=? AND task_id=?')
        ->execute([$done, $mid, $tid]);
}

if (isset($_FILES['report']) && $_FILES['report']['error'] === UPLOAD_ERR_OK) {
    if (scan_file($_FILES['report']['tmp_name'])) {
        $dir = __DIR__ . '/uploads/handover/' . $user['id'];
        if (!is_dir($dir)) { mkdir($dir, 0775, true); }
        $name = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $_FILES['report']['name']);
        move_uploaded_file($_FILES['report']['tmp_name'], $dir . '/' . $name);
        $pdo->prepare('UPDATE moveouts SET handover_report=? WHERE id=? AND user_id=?')
            ->execute([$name, $mid, $user['id']]);
        $moveout['handover_report'] = $name;
    } else {
        $error = t('file_rejected');
    }
}

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png']) && scan_file($_FILES['photo']['tmp_name'])) {
        $dir = __DIR__ . '/uploads/moveout/' . $mid;
        if (!is_dir($dir)) { mkdir($dir,0775,true); }
        $name = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $dir . '/' . $name);
        $invId = !empty($_POST['inventory_id']) ? (int)$_POST['inventory_id'] : null;
        $pdo->prepare('INSERT INTO moveout_photos (moveout_id, inventory_id, file_path) VALUES (?,?,?)')->execute([$mid, $invId, $name]);
    } else {
        $error = t('invalid_file');
    }
}

if (isset($_POST['request_viewing'])) {
    $time = $_POST['viewing_time'] ?? '';
    $ts = strtotime($time);
    $mo = strtotime($moveout['move_out_date']);
    if ($ts && $ts <= $mo && $ts >= $mo - 90*86400) {
        $pdo->prepare('INSERT INTO viewing_requests (moveout_id, requested_time) VALUES (?,?)')
            ->execute([$mid, $time]);
    } else {
        $error = t('invalid_viewing_time');
    }
}

$tasks = $pdo->prepare('SELECT t.id, t.description, s.tenant_done, s.admin_confirmed FROM moveout_tasks t JOIN moveout_task_status s ON t.id=s.task_id WHERE s.moveout_id=?');
$tasks->execute([$mid]);
$tasks = $tasks->fetchAll();
$days = (strtotime($moveout['move_out_date']) - time())/86400; if ($days < 0) { $days = 0; }
$vre = $pdo->prepare('SELECT id, requested_time, status FROM viewing_requests WHERE moveout_id=? ORDER BY requested_time');
$vre->execute([$mid]);
$requests = $vre->fetchAll();
$inv = $pdo->prepare('SELECT id,name FROM apartment_inventory WHERE apartment_id=?');
$inv->execute([$moveout['apartment_id']]);
$inventory = $inv->fetchAll();
$pstmt = $pdo->prepare('SELECT mp.file_path, mp.inventory_id, ai.name FROM moveout_photos mp LEFT JOIN apartment_inventory ai ON mp.inventory_id=ai.id WHERE mp.moveout_id=?');
$pstmt->execute([$mid]);
$photos = $pstmt->fetchAll();
$counts = [];
foreach ($photos as $p) {
    if (!empty($p['inventory_id'])) { $counts[$p['inventory_id']] = true; }
}
$missing = [];
foreach ($inventory as $i) {
    if (!isset($counts[$i['id']])) { $missing[] = $i['name']; }
}
$cstmt = $pdo->prepare('SELECT description, amount, created_at FROM deposit_claims WHERE moveout_id=? ORDER BY created_at');
$cstmt->execute([$mid]);
$claims = $cstmt->fetchAll();
$cat = $pdo->prepare("SELECT id FROM event_categories WHERE name IN ('Besichtigung','Viewing') LIMIT 1");
$cat->execute();
$viewCat = $cat->fetchColumn();
$viewings = [];
if ($viewCat) {
    $vst = $pdo->prepare('SELECT start FROM calendar_events WHERE apartment_id=? AND category_id=? AND start>=NOW() ORDER BY start');
    $vst->execute([$moveout['apartment_id'], $viewCat]);
    $viewings = $vst->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title><?php echo t('moveout'); ?></title>
</head>
<body>
<p>Eingeloggt als <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="dashboard.php">Menü</a> | <a href="logout.php">Logout</a></p>
<h2><?php echo t('moveout'); ?></h2>
<p><?php echo t('days_until_moveout'); ?> <?php echo (int)$days; ?></p>
<p><?php echo t('moveout_reason'); ?> <?php echo t($moveout['reason']); ?></p>
<?php if ($missing): ?><p style="color:red;"><?php echo t('missing_photos'); ?>: <?php echo htmlspecialchars(implode(', ', $missing), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<h3><?php echo t('moveout_tasks'); ?></h3>
<ul>
<?php foreach ($tasks as $t): ?>
    <li>
        <form method="post">
            <input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">
            <label><input type="checkbox" name="done" value="1" <?php if ($t['tenant_done']) echo 'checked'; ?> onchange="this.form.submit();"> <?php echo htmlspecialchars($t['description']); ?></label>
            <?php if ($t['admin_confirmed']) echo ' ✓'; ?>
        </form>
    </li>
<?php endforeach; ?>
</ul>
<h3><?php echo t('deposit'); ?></h3>
<p><?php echo t('deposit_amount'); ?>: <?php echo htmlspecialchars($moveout['deposit_amount']); ?> €</p>
<p><?php echo t('deposit_deduction'); ?>: <?php echo htmlspecialchars($moveout['deposit_deduction']); ?> €</p>
<p><?php echo t('deposit_return'); ?>: <?php echo htmlspecialchars($moveout['deposit_amount'] - $moveout['deposit_deduction']); ?> €</p>
<form method="post">
<label><?php echo t('bank_account'); ?> <input type="text" name="bank_account" value="<?php echo htmlspecialchars($moveout['bank_account']); ?>"></label>
<button type="submit"><?php echo t('save'); ?></button>
</form>
<?php if ($claims): ?>
<h3><?php echo t('post_deposit_claims'); ?></h3>
<table border="1"><tr><th><?php echo t('date'); ?></th><th><?php echo t('claim_description'); ?></th><th><?php echo t('claim_amount'); ?></th></tr>
<?php foreach ($claims as $c): ?>
<tr><td><?php echo htmlspecialchars($c['created_at']); ?></td><td><?php echo htmlspecialchars($c['description']); ?></td><td><?php echo htmlspecialchars($c['amount']); ?> €</td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<h3><?php echo t('handover_report'); ?></h3>
<?php if ($moveout['handover_report']): ?>
<p><a href="uploads/handover/<?php echo $user['id']; ?>/<?php echo htmlspecialchars($moveout['handover_report']); ?>"><?php echo t('handover_report'); ?></a></p>
<?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="report" accept="image/jpeg,image/png" required>
    <button type="submit"><?php echo t('upload_handover'); ?></button>
</form>

<h3><?php echo t('photo_documentation'); ?></h3>
<table border="1"><tr><th><?php echo t('inventory_item'); ?></th><th><?php echo t('photo_documentation'); ?></th></tr>
<?php foreach ($photos as $p): ?>
<tr><td><?php echo htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td><td><a href="uploads/moveout/<?php echo $mid; ?>/<?php echo htmlspecialchars($p['file_path']); ?>"><?php echo t('photo_documentation'); ?></a></td></tr>
<?php endforeach; ?>
</table>
<form method="post" enctype="multipart/form-data">
    <select name="inventory_id">
        <option value="">-</option>
        <?php foreach ($inventory as $i): ?><option value="<?php echo $i['id']; ?>"><?php echo htmlspecialchars($i['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
    </select>
    <input type="file" name="photo" accept="image/jpeg,image/png" required>
    <button type="submit" name="upload_photo" value="1"><?php echo t('upload_photo'); ?></button>
</form>
<h3><?php echo t('viewing_requests'); ?></h3>
<?php if ($days <= 90): ?>
<form method="post">
    <input type="datetime-local" name="viewing_time" required>
    <button name="request_viewing" value="1"><?php echo t('request_viewing'); ?></button>
    </form>
<?php endif; ?>
<table border="1"><tr><th><?php echo t('viewing_time'); ?></th><th><?php echo t('status'); ?></th></tr>
<?php foreach ($requests as $r): ?>
<tr><td><?php echo htmlspecialchars($r['requested_time']); ?></td><td><?php echo t($r['status']); ?></td></tr>
<?php endforeach; ?>
</table>
<?php if ($viewings): ?><h3><?php echo t('scheduled_viewings'); ?></h3><ul><?php foreach ($viewings as $v): ?><li><?php echo htmlspecialchars($v['start']); ?></li><?php endforeach; ?></ul><?php endif; ?>
</body>
</html>

