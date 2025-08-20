<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') { http_response_code(403); exit('forbidden'); }
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
$pdo = get_db();
$primary = get_setting('primary_color', '#0d6efd');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_request'])) {
        $id = (int)$_POST['approve_request'];
        $r = $pdo->prepare('SELECT requester_id, apartment_id, category_id, title, start, end FROM calendar_requests WHERE id=?');
        $r->execute([$id]);
        $req = $r->fetch();
        if ($req) {
            $pdo->prepare('INSERT INTO calendar_events (user_id, apartment_id, category_id, title, start, end, visible_to_tenants) VALUES (NULL,?,?,?,?,?,1)')->execute([$req['apartment_id'], $req['category_id'], $req['title'], $req['start'], $req['end']]);
            $pdo->prepare('UPDATE calendar_requests SET status="approved" WHERE id=?')->execute([$id]);
        }
    } elseif (isset($_POST['decline_request'])) {
        $id = (int)$_POST['decline_request'];
        $pdo->prepare('UPDATE calendar_requests SET status="declined" WHERE id=?')->execute([$id]);
    } elseif (isset($_POST['add_category'])) {
        $name = trim($_POST['cat_name'] ?? '');
        if ($name !== '') {
            $pdo->prepare('INSERT INTO event_categories (name) VALUES (?)')->execute([$name]);
        }
    } elseif (isset($_POST['add_event'])) {
        $title = trim($_POST['title'] ?? '');
        $start = $_POST['start'] ?? '';
        $end = $_POST['end'] ?? '';
        $category = (int)($_POST['category'] ?? 0);
        $apt = (int)($_POST['apartment'] ?? 0);
        $internal = isset($_POST['internal']) ? 1 : 0;
        $confirm = isset($_POST['confirm']) ? 1 : 0;
        if ($title && $start) {
            $catname = '';
            if ($category) {
                $s = $pdo->prepare('SELECT name FROM event_categories WHERE id=?');
                $s->execute([$category]);
                $catname = $s->fetchColumn() ?: '';
            }
            if ($catname === 'Kontrolle' || $catname === 'Inspection') {
                $chk = $pdo->prepare('SELECT COUNT(*) FROM calendar_events WHERE apartment_id=? AND category_id=? AND YEAR(start)=YEAR(?)');
                $chk->execute([$apt, $category, $start]);
                if ($chk->fetchColumn() >= 2) {
                    $error = t('inspection_limit');
                    goto skip_insert;
                }
            }
            if ($confirm) {
                $pdo->prepare('INSERT INTO calendar_requests (requester_id, apartment_id, category_id, title, start, end) VALUES (?,?,?,?,?,?)')->execute([$user['id'], $apt ?: null, $category ?: null, $title, $start, $end ?: null]);
            } else {
                $visible = $internal ? 0 : 1;
                $creator = $internal ? $user['id'] : null;
                $stmt = $pdo->prepare('INSERT INTO calendar_events (user_id, apartment_id, category_id, title, start, end, visible_to_tenants) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$creator, $apt ?: null, $category ?: null, $title, $start, $end ?: null, $visible]);
            }
            skip_insert:;
        }
    }
}

$cats = $pdo->query('SELECT id,name FROM event_categories')->fetchAll();
$apts = $pdo->query('SELECT id,address FROM apartments')->fetchAll();
$events = $pdo->query('SELECT c.title,c.start,c.end,cat.name AS category,a.address,c.visible_to_tenants FROM calendar_events c LEFT JOIN event_categories cat ON c.category_id=cat.id LEFT JOIN apartments a ON c.apartment_id=a.id ORDER BY c.start')->fetchAll();
$requests = $pdo->query("SELECT r.id,r.title,r.start,r.end,a.address,u.email FROM calendar_requests r JOIN users u ON r.requester_id=u.id LEFT JOIN apartments a ON r.apartment_id=a.id WHERE r.status='pending' AND u.role='tenant' ORDER BY r.start")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<title><?php echo t('calendar_manage'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="script.js" defer></script>
</head>
<body>
<div id="burger">☰</div>
<h1><?php echo t('calendar_manage'); ?></h1>
<h2><?php echo t('events'); ?></h2>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<table border="1">
<tr><th><?php echo t('event_title'); ?></th><th><?php echo t('start'); ?></th><th><?php echo t('end'); ?></th><th><?php echo t('category'); ?></th><th><?php echo t('apartment'); ?></th><th><?php echo t('visibility'); ?></th></tr>
<?php foreach ($events as $e): ?>
<tr><td><?php echo htmlspecialchars($e['title'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($e['start'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($e['end'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($e['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($e['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo $e['visible_to_tenants'] ? t('tenant_visible') : t('internal'); ?></td></tr>
<?php endforeach; ?>
</table>

<?php if ($requests): ?>
<h2><?php echo t('pending_requests'); ?></h2>
<table border="1">
<tr><th><?php echo t('event_title'); ?></th><th><?php echo t('start'); ?></th><th><?php echo t('end'); ?></th><th><?php echo t('apartment'); ?></th><th><?php echo t('request_event'); ?></th></tr>
<?php foreach ($requests as $r): ?>
<tr>
<td><?php echo htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['start'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['end'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($r['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td>
<form method="post" style="display:inline"><button name="approve_request" value="<?php echo $r['id']; ?>"><?php echo t('approve'); ?></button></form>
<form method="post" style="display:inline"><button name="decline_request" value="<?php echo $r['id']; ?>"><?php echo t('decline'); ?></button></form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<form method="post">
    <h3><?php echo t('add_event'); ?></h3>
    <input type="text" name="title" placeholder="<?php echo t('event_title'); ?>" required>
    <input type="datetime-local" name="start" required>
    <input type="datetime-local" name="end">
    <select name="category">
        <option value="0">-</option>
        <?php foreach ($cats as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
    </select>
    <select name="apartment">
        <option value="0">-</option>
        <?php foreach ($apts as $a): ?><option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['address'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
    </select>
    <label><input type="checkbox" name="internal" value="1"> <?php echo t('internal'); ?></label>
    <label><input type="checkbox" name="confirm" value="1"> <?php echo t('request_event'); ?></label>
    <button name="add_event"><?php echo t('save'); ?></button>
</form>
<h2><?php echo t('categories'); ?></h2>
<ul><?php foreach ($cats as $c): ?><li><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ul>
<form method="post">
    <input type="text" name="cat_name" placeholder="<?php echo t('category'); ?>">
    <button name="add_category"><?php echo t('add_category'); ?></button>
</form>
<p><a href="admin.php"><?php echo t('back'); ?></a></p>
</body>
</html>
