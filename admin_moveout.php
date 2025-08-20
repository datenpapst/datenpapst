<?php
require_once __DIR__ . '/auth.php';
require_login();
$u = current_user();
if ($u['role'] !== 'admin') { http_response_code(403); exit('forbidden'); }
require_once __DIR__ . '/language.php';
$pdo = get_db();

if (isset($_POST['start'])) {
    $ta = (int)$_POST['tenant_apartment'];
    $date = $_POST['move_out_date'];
    $deposit = (float)$_POST['deposit_amount'];
    $reason = $_POST['reason'] ?? 'tenant_notice';
    $stmt = $pdo->prepare('SELECT user_id, apartment_id FROM tenant_apartment WHERE id=? AND is_active=1');
    $stmt->execute([$ta]);
    $row = $stmt->fetch();
    if ($row) {
        $pdo->prepare('INSERT INTO moveouts (user_id, apartment_id, move_out_date, deposit_amount, reason) VALUES (?,?,?,?,?)')
            ->execute([$row['user_id'], $row['apartment_id'], $date, $deposit, $reason]);
        $mid = $pdo->lastInsertId();
        $tasks = $pdo->prepare('SELECT id FROM moveout_tasks WHERE apartment_id=?');
        $tasks->execute([$row['apartment_id']]);
        foreach ($tasks as $t) {
            $pdo->prepare('INSERT INTO moveout_task_status (moveout_id, task_id) VALUES (?,?)')->execute([$mid,$t['id']]);
        }
        if (!empty($_POST['block_tenant'])) {
            $pdo->prepare('UPDATE users SET status="blocked" WHERE id=?')->execute([$row['user_id']]);
        }
    }
}

if (isset($_POST['add_task'])) {
    $mid = (int)$_POST['mid'];
    $desc = trim($_POST['description'] ?? '');
    if ($desc !== '') {
        $ap = $pdo->prepare('SELECT apartment_id FROM moveouts WHERE id=?');
        $ap->execute([$mid]);
        $apartment_id = $ap->fetchColumn();
        if ($apartment_id) {
            $pdo->prepare('INSERT INTO moveout_tasks (apartment_id, description) VALUES (?,?)')->execute([$apartment_id,$desc]);
            $tid = $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO moveout_task_status (moveout_id, task_id) VALUES (?,?)')->execute([$mid,$tid]);
        }
    }
}

if (isset($_POST['delete_task'])) {
    $tid = (int)$_POST['task_id'];
    $pdo->prepare('DELETE FROM moveout_tasks WHERE id=?')->execute([$tid]);
    $pdo->prepare('DELETE FROM moveout_task_status WHERE task_id=?')->execute([$tid]);
}

if (isset($_POST['confirm_task'])) {
    $pdo->prepare('UPDATE moveout_task_status SET admin_confirmed=1 WHERE moveout_id=? AND task_id=?')
        ->execute([(int)$_POST['mid'], (int)$_POST['task_id']]);
}

if (isset($_POST['update_deposit'])) {
    $pdo->prepare('UPDATE moveouts SET deposit_amount=?, deposit_deduction=?, bank_account=? WHERE id=?')
        ->execute([(float)$_POST['deposit_amount'], (float)$_POST['deposit_deduction'], $_POST['bank_account'], (int)$_POST['mid']]);
}

if (isset($_POST['add_claim'])) {
    $mid = (int)$_POST['mid'];
    $desc = trim($_POST['description'] ?? '');
    $amount = (float)$_POST['amount'];
    if ($desc !== '' && $amount > 0) {
        $pdo->prepare('INSERT INTO deposit_claims (moveout_id, description, amount) VALUES (?,?,?)')
            ->execute([$mid, $desc, $amount]);
    }
}

if (isset($_POST['approve_viewing'])) {
    $id = (int)$_POST['id'];
    $vr = $pdo->prepare('SELECT vr.requested_time, m.apartment_id FROM viewing_requests vr JOIN moveouts m ON vr.moveout_id=m.id WHERE vr.id=?');
    $vr->execute([$id]);
    if ($row = $vr->fetch()) {
        $cat = $pdo->prepare("SELECT id FROM event_categories WHERE name IN ('Besichtigung','Viewing') LIMIT 1");
        $cat->execute();
        $cid = $cat->fetchColumn();
        $pdo->prepare('INSERT INTO calendar_events (apartment_id, category_id, title, start, visible_to_tenants) VALUES (?,?,?,?,1)')
            ->execute([$row['apartment_id'], $cid, 'Besichtigung', $row['requested_time']]);
        $pdo->prepare('UPDATE viewing_requests SET status="approved" WHERE id=?')->execute([$id]);
    }
}

if (isset($_POST['decline_viewing'])) {
    $id = (int)$_POST['id'];
    $pdo->prepare('UPDATE viewing_requests SET status="declined" WHERE id=?')->execute([$id]);
}

if (isset($_GET['mid'])) {
    $mid = (int)$_GET['mid'];
    $stmt = $pdo->prepare('SELECT m.*, u.email, a.address FROM moveouts m JOIN users u ON m.user_id=u.id JOIN apartments a ON m.apartment_id=a.id WHERE m.id=?');
    $stmt->execute([$mid]);
    $m = $stmt->fetch();
    if (!$m) { exit('not found'); }
    $tasks = $pdo->prepare('SELECT t.id, t.description, s.tenant_done, s.admin_confirmed FROM moveout_tasks t JOIN moveout_task_status s ON t.id=s.task_id WHERE s.moveout_id=?');
    $tasks->execute([$mid]);
    $tasks = $tasks->fetchAll();
    $vstmt = $pdo->prepare('SELECT id, requested_time, status FROM viewing_requests WHERE moveout_id=? ORDER BY requested_time');
    $vstmt->execute([$mid]);
    $vre = $vstmt->fetchAll();
    $pstmt = $pdo->prepare('SELECT mp.file_path, ai.name FROM moveout_photos mp LEFT JOIN apartment_inventory ai ON mp.inventory_id=ai.id WHERE mp.moveout_id=?');
    $pstmt->execute([$mid]);
    $photos = $pstmt->fetchAll();
    $cstmt = $pdo->prepare('SELECT description, amount, created_at FROM deposit_claims WHERE moveout_id=? ORDER BY created_at');
    $cstmt->execute([$mid]);
    $claims = $cstmt->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="de"><head><meta charset="UTF-8"><title><?php echo t('moveout_manage'); ?></title><link rel="stylesheet" href="style.css"></head>
    <body>
    <p><a href="admin_moveout.php"><?php echo t('back'); ?></a></p>
    <h1><?php echo t('moveout_manage'); ?> - <?php echo htmlspecialchars($m['email']); ?></h1>
    <p><?php echo t('days_until_moveout'); ?> <?php echo max(0,(int)((strtotime($m['move_out_date'])-time())/86400)); ?></p>
    <p><?php echo t('moveout_reason'); ?> <?php echo t($m['reason']); ?></p>
    <h2><?php echo t('moveout_tasks'); ?></h2>
    <table border="1"><tr><th><?php echo t('moveout_tasks'); ?></th><th><?php echo t('mark_done'); ?></th><th><?php echo t('confirm'); ?></th><th>Del</th></tr>
    <?php foreach ($tasks as $t): ?>
    <tr>
      <td><?php echo htmlspecialchars($t['description']); ?></td>
      <td><?php echo $t['tenant_done'] ? '✓' : ''; ?></td>
      <td><?php if (!$t['admin_confirmed']): ?><form method="post" style="display:inline;"><input type="hidden" name="mid" value="<?php echo $mid; ?>"><input type="hidden" name="task_id" value="<?php echo $t['id']; ?>"><button name="confirm_task"><?php echo t('confirm'); ?></button></form><?php else: ?>✓<?php endif; ?></td>
      <td><form method="post" style="display:inline;" onsubmit="return confirm('delete?');"><input type="hidden" name="task_id" value="<?php echo $t['id']; ?>"><button name="delete_task">X</button></form></td>
    </tr>
    <?php endforeach; ?>
    </table>
    <form method="post">
        <input type="hidden" name="mid" value="<?php echo $mid; ?>">
        <input type="text" name="description">
        <button name="add_task">+</button>
    </form>
    <h2><?php echo t('deposit'); ?></h2>
    <form method="post">
        <input type="hidden" name="mid" value="<?php echo $mid; ?>">
        <label><?php echo t('deposit_amount'); ?> <input type="number" step="0.01" name="deposit_amount" value="<?php echo htmlspecialchars($m['deposit_amount']); ?>"></label><br>
        <label><?php echo t('deposit_deduction'); ?> <input type="number" step="0.01" name="deposit_deduction" value="<?php echo htmlspecialchars($m['deposit_deduction']); ?>"></label><br>
        <label><?php echo t('bank_account'); ?> <input type="text" name="bank_account" value="<?php echo htmlspecialchars($m['bank_account']); ?>"></label><br>
        <button name="update_deposit"><?php echo t('save'); ?></button>
    </form>
    <?php if ($claims): ?>
    <h3><?php echo t('post_deposit_claims'); ?></h3>
    <table border="1"><tr><th><?php echo t('date'); ?></th><th><?php echo t('claim_description'); ?></th><th><?php echo t('claim_amount'); ?></th></tr>
    <?php foreach ($claims as $c): ?>
    <tr><td><?php echo htmlspecialchars($c['created_at']); ?></td><td><?php echo htmlspecialchars($c['description']); ?></td><td><?php echo htmlspecialchars($c['amount']); ?> €</td></tr>
    <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="mid" value="<?php echo $mid; ?>">
        <input type="text" name="description" placeholder="<?php echo t('claim_description'); ?>">
        <input type="number" step="0.01" name="amount" placeholder="<?php echo t('claim_amount'); ?>">
        <button name="add_claim"><?php echo t('add_claim'); ?></button>
    </form>
    <h2><?php echo t('viewing_requests'); ?></h2>
    <table border="1"><tr><th><?php echo t('viewing_time'); ?></th><th><?php echo t('status'); ?></th><th></th></tr>
    <?php foreach ($vre as $v): ?>
    <tr>
        <td><?php echo htmlspecialchars($v['requested_time']); ?></td>
        <td><?php echo t($v['status']); ?></td>
        <td><?php if ($v['status']==='pending'): ?><form method="post" style="display:inline"><input type="hidden" name="id" value="<?php echo $v['id']; ?>"><button name="approve_viewing">OK</button><button name="decline_viewing">X</button></form><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
    </table>
    <?php if (!empty($m['handover_report'])): ?>
    <p><a href="uploads/handover/<?php echo (int)$m['user_id']; ?>/<?php echo htmlspecialchars($m['handover_report']); ?>"><?php echo t('handover_report'); ?></a></p>
    <?php endif; ?>
    <?php if ($photos): ?>
    <h2><?php echo t('photo_documentation'); ?></h2>
    <ul>
    <?php foreach ($photos as $p): ?>
        <li><a href="uploads/moveout/<?php echo $mid; ?>/<?php echo htmlspecialchars($p['file_path']); ?>"><?php echo htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    </body></html>
    <?php
    exit;
}

$tenants = $pdo->query('SELECT ta.id, u.email, a.address FROM tenant_apartment ta JOIN users u ON ta.user_id=u.id JOIN apartments a ON ta.apartment_id=a.id WHERE ta.is_active=1')->fetchAll();
$moveouts = $pdo->query('SELECT m.id,u.email,a.address,m.move_out_date,m.reason FROM moveouts m JOIN users u ON m.user_id=u.id JOIN apartments a ON m.apartment_id=a.id ORDER BY m.move_out_date DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title><?php echo t('moveout_manage'); ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<h1><?php echo t('moveout_manage'); ?></h1>
<h2>Neuen Prozess starten</h2>
<form method="post">
<select name="tenant_apartment">
<?php foreach ($tenants as $t): ?>
<option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['email'] . ' - ' . $t['address']); ?></option>
<?php endforeach; ?>
</select>
<input type="date" name="move_out_date" required>
<input type="number" step="0.01" name="deposit_amount" placeholder="<?php echo t('deposit_amount'); ?>">
<select name="reason">
    <option value="tenant_notice"><?php echo t('tenant_notice'); ?></option>
    <option value="landlord_termination"><?php echo t('landlord_termination'); ?></option>
    <option value="contract_breach"><?php echo t('contract_breach'); ?></option>
</select>
<label><input type="checkbox" name="block_tenant" value="1"> <?php echo t('block_tenant'); ?></label>
<button name="start">Start</button>
</form>
<h2>Prozesse</h2>
<table border="1"><tr><th>Mieter</th><th>Wohnung</th><th>Datum</th><th><?php echo t('moveout_reason'); ?></th><th></th></tr>
<?php foreach ($moveouts as $m): ?>
<tr>
<td><?php echo htmlspecialchars($m['email']); ?></td>
<td><?php echo htmlspecialchars($m['address']); ?></td>
<td><?php echo htmlspecialchars($m['move_out_date']); ?></td>
<td><?php echo t($m['reason']); ?></td>
<td><a href="admin_moveout.php?mid=<?php echo $m['id']; ?>"><?php echo t('details'); ?></a></td>
</tr>
<?php endforeach; ?>
</table>
<p><a href="admin.php"><?php echo t('back'); ?></a></p>
</body>
</html>

