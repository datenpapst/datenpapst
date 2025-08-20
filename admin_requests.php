<?php
require_once __DIR__ . '/auth.php';
require_login();
$u = current_user();
if ($u['role'] !== 'admin') { http_response_code(403); exit('forbidden'); }
$pdo = get_db();
if (isset($_POST['confirm'])) {
    $pdo->prepare('UPDATE deletion_requests SET confirmed_at=NOW() WHERE id=?')->execute([ (int)$_POST['id'] ]);
}
if (isset($_POST['delete_user'])) {
    $id=(int)$_POST['uid'];
    $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM deletion_requests WHERE user_id=?')->execute([$id]);
}
$reqs = $pdo->query('SELECT dr.id, dr.user_id, u.email, dr.requested_at, dr.confirmed_at FROM deletion_requests dr JOIN users u ON dr.user_id=u.id')->fetchAll();
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Anfragen</title><link rel="stylesheet" href="style.css"><script src="script.js" defer></script></head>
<body>
<div id="burger">☰</div>
<h1>Löschanfragen</h1>
<table border="1"><tr><th>Email</th><th>angefragt</th><th>bestätigt</th><th>Aktion</th></tr>
<?php foreach ($reqs as $r): ?>
<tr><td><?php echo htmlspecialchars($r['email']); ?></td><td><?php echo $r['requested_at']; ?></td><td><?php echo $r['confirmed_at']; ?></td><td>
<?php if (!$r['confirmed_at']): ?>
<form method="post"><input type="hidden" name="id" value="<?php echo $r['id']; ?>"><button name="confirm">Bestätigen</button></form>
<?php elseif (strtotime($r['confirmed_at']) < time()-14*24*3600): ?>
<form method="post"><input type="hidden" name="uid" value="<?php echo $r['user_id']; ?>"><button name="delete_user">Endgültig löschen</button></form>
<?php endif; ?>
</td></tr>
<?php endforeach; ?>
</table>
<p><a href="admin.php">Zurück</a></p>
</body></html>
