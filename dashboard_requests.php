<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/language.php';
$pdo = get_db();
$user = current_user();
$error = '';
$cstmt = $pdo->prepare('SELECT start_date FROM tenant_apartment WHERE user_id=? AND is_active=1');
$cstmt->execute([$user['id']]);
$contract = $cstmt->fetch();
if ($contract) {
    $start = $contract['start_date'];
    $notice_date = date('Y-m-d', strtotime($start . ' +12 months'));
    $moveout_date = date('Y-m-d', strtotime($start . ' +15 months'));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $details = trim($_POST['details'] ?? '');
    if ($type) {
        if ($type === 'Kündigung' && $contract && date('Y-m-d') < $notice_date) {
            $error = sprintf(t('termination_too_early'), $notice_date);
        } else {
            $stmt = $pdo->prepare('INSERT INTO requests (user_id, type, details) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], $type, $details]);
        }
    } else {
        $error = 'Bitte einen Typ wählen';
    }
}
$stmt = $pdo->prepare('SELECT type, details, created_at FROM requests WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Self-Service Anfragen</title>
</head>
<body>
<p>Eingeloggt als <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="dashboard.php">Menü</a> | <a href="logout.php">Logout</a></p>
<h2>Anfrage stellen</h2>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<?php if ($contract): ?>
<p><?php echo t('contract_start'); ?> <?php echo htmlspecialchars($start); ?> - <?php echo t('earliest_notice'); ?> <?php echo htmlspecialchars($notice_date); ?> (<?php echo t('earliest_moveout'); ?> <?php echo htmlspecialchars($moveout_date); ?>)</p>
<?php endif; ?>
<form method="post">
    <label>Typ:
        <select name="type" required>
            <option value="">--Bitte wählen--</option>
            <option value="Haustier">Haustier</option>
            <option value="Kündigung">Kündigung</option>
        </select>
    </label><br>
    <label>Details:<br><textarea name="details" rows="4" cols="40"></textarea></label><br>
    <button type="submit">Absenden</button>
</form>
<h3>Ihre Anfragen</h3>
<ul>
<?php foreach ($requests as $req): ?>
    <li><?php echo htmlspecialchars($req['created_at'], ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($req['type'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars($req['details'], ENT_QUOTES, 'UTF-8'); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
