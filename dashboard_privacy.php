<?php
require_once __DIR__ . '/auth.php';
require_login();
$u = current_user();
$pdo = get_db();
$stmt = $pdo->prepare('SELECT requested_at, confirmed_at FROM deletion_requests WHERE user_id=?');
$stmt->execute([$u['id']]);
$req = $stmt->fetch();
if ($_SERVER['REQUEST_METHOD']==='POST' && !$req) {
    $pdo->prepare('INSERT INTO deletion_requests (user_id) VALUES (?)')->execute([$u['id']]);
    $req = ['requested_at'=>date('Y-m-d H:i:s'), 'confirmed_at'=>null];
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Datenschutz</title><link rel="stylesheet" href="style.css"><script src="script.js" defer></script></head>
<body>
<div id="burger">☰</div>
<h1>Datenschutz</h1>
<?php if ($req): ?>
<p>Löschanfrage gestellt am <?php echo $req['requested_at']; ?><?php if ($req['confirmed_at']): ?>, bestätigt am <?php echo $req['confirmed_at']; ?><?php endif; ?></p>
<?php else: ?>
<form method="post"><button>Datenlöschung beantragen</button></form>
<?php endif; ?>
<p><a href="dashboard.php">Zurück</a></p>
</body></html>
