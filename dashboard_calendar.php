<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
$pdo = get_db();
$primary = get_setting('primary_color', '#0d6efd');

$aptStmt = $pdo->prepare('SELECT apartment_id FROM tenant_apartment WHERE user_id=? AND is_active=1');
$aptStmt->execute([$user['id']]);
$apartmentIds = $aptStmt->fetchAll(PDO::FETCH_COLUMN);
$firstApt = $apartmentIds[0] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_request'])) {
        $title = trim($_POST['title'] ?? '');
        $start = $_POST['start'] ?? '';
        $end = $_POST['end'] ?? '';
        $category = (int)($_POST['category'] ?? 0);
        if ($title && $start) {
            $stmt = $pdo->prepare('INSERT INTO calendar_requests (requester_id, apartment_id, category_id, title, start, end) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$user['id'], $firstApt, $category ?: null, $title, $start, $end ?: null]);
        }
    } elseif (isset($_POST['approve_request'])) {
        $id = (int)$_POST['approve_request'];
        $r = $pdo->prepare('SELECT apartment_id, category_id, title, start, end FROM calendar_requests WHERE id=?');
        $r->execute([$id]);
        $req = $r->fetch();
        if ($req) {
            $pdo->prepare('INSERT INTO calendar_events (user_id, apartment_id, category_id, title, start, end, visible_to_tenants) VALUES (NULL,?,?,?,?,?,1)')->execute([$req['apartment_id'], $req['category_id'], $req['title'], $req['start'], $req['end']]);
            $pdo->prepare('UPDATE calendar_requests SET status="approved" WHERE id=?')->execute([$id]);
        }
    } elseif (isset($_POST['decline_request'])) {
        $id = (int)$_POST['decline_request'];
        $pdo->prepare('UPDATE calendar_requests SET status="declined" WHERE id=?')->execute([$id]);
    }
}

$cats = $pdo->query('SELECT id,name FROM event_categories')->fetchAll();
$eventsSql = 'SELECT c.title,c.start,c.end,cat.name AS category FROM calendar_events c LEFT JOIN event_categories cat ON c.category_id=cat.id WHERE (c.user_id = ? OR (c.visible_to_tenants=1 AND (';
$params = [$user['id']];
$parts = [];
if ($apartmentIds) {
    $in = implode(',', array_fill(0, count($apartmentIds), '?'));
    $parts[] = "c.apartment_id IN ($in)";
    $params = array_merge($params, $apartmentIds);
}
$parts[] = '(c.apartment_id IS NULL AND c.user_id IS NULL)';
$eventsSql .= implode(' OR ', $parts) . '))) ORDER BY c.start';
$stmt = $pdo->prepare($eventsSql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$myReq = $pdo->prepare('SELECT id,title,start,end,status FROM calendar_requests WHERE requester_id=? ORDER BY start');
$myReq->execute([$user['id']]);
$myRequests = $myReq->fetchAll();

$incoming = [];
if ($apartmentIds) {
    $in = implode(',', array_fill(0, count($apartmentIds), '?'));
    $st = $pdo->prepare("SELECT r.id,r.title,r.start,r.end FROM calendar_requests r JOIN users u ON r.requester_id=u.id WHERE r.status='pending' AND u.role='admin' AND r.apartment_id IN ($in)");
    $st->execute($apartmentIds);
    $incoming = $st->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<title><?php echo t('calendar'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="script.js" defer></script>
</head>
<body>
<div id="burger">☰</div>
<h1><?php echo t('calendar'); ?></h1>
<p><a href="calendar_export.php"><?php echo t('calendar_export'); ?></a></p>
<?php if ($events): ?>
<table border="1">
<tr><th><?php echo t('event_title'); ?></th><th><?php echo t('start'); ?></th><th><?php echo t('end'); ?></th><th><?php echo t('category'); ?></th></tr>
<?php foreach ($events as $e): ?>
<tr><td><?php echo htmlspecialchars($e['title'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($e['start'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($e['end'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($e['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td></tr>
<?php endforeach; ?>
</table>
<?php else: ?><p><?php echo t('no_events'); ?></p><?php endif; ?>

<?php if ($incoming): ?>
<h3><?php echo t('pending_requests'); ?></h3>
<table border="1">
<tr><th><?php echo t('event_title'); ?></th><th><?php echo t('start'); ?></th><th><?php echo t('end'); ?></th><th></th></tr>
<?php foreach ($incoming as $r): ?>
<tr>
<td><?php echo htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['start'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['end'], ENT_QUOTES, 'UTF-8'); ?></td>
<td>
<form method="post" style="display:inline"><button name="approve_request" value="<?php echo $r['id']; ?>"><?php echo t('approve'); ?></button></form>
<form method="post" style="display:inline"><button name="decline_request" value="<?php echo $r['id']; ?>"><?php echo t('decline'); ?></button></form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<?php if ($myRequests): ?>
<h3><?php echo t('request_event'); ?></h3>
<table border="1">
<tr><th><?php echo t('event_title'); ?></th><th><?php echo t('start'); ?></th><th><?php echo t('end'); ?></th><th>Status</th></tr>
<?php foreach ($myRequests as $r): ?>
<tr><td><?php echo htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($r['start'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($r['end'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo t($r['status']); ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<form method="post">
    <h3><?php echo t('request_event'); ?></h3>
    <input type="text" name="title" placeholder="<?php echo t('event_title'); ?>" required>
    <input type="datetime-local" name="start" required>
    <input type="datetime-local" name="end">
    <select name="category">
        <option value="0">-</option>
        <?php foreach ($cats as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
    </select>
    <button name="add_request"><?php echo t('save'); ?></button>
</form>
<p><a href="dashboard.php"><?php echo t('back'); ?></a></p>
</body>
</html>
