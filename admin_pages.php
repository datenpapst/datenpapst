<?php
require_once __DIR__ . '/auth.php';
require_login();
$u = current_user();
if ($u['role'] !== 'admin') { http_response_code(403); exit('forbidden'); }
$pdo = get_db();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    foreach (['impressum','privacy'] as $p) {
        $content = $_POST[$p] ?? '';
        $stmt = $pdo->prepare('REPLACE INTO page_contents (page,content) VALUES (?,?)');
        $stmt->execute([$p,$content]);
    }
}
$data=[];
foreach (['impressum','privacy'] as $p) {
    $stmt=$pdo->prepare('SELECT content FROM page_contents WHERE page=?');
    $stmt->execute([$p]);
    $data[$p]=$stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Seiten</title><link rel="stylesheet" href="style.css"><script src="script.js" defer></script></head>
<body>
<div id="burger">☰</div>
<h1>Impressum & Datenschutz</h1>
<form method="post">
<h3>Impressum</h3>
<textarea name="impressum" rows="10" cols="80"><?php echo htmlspecialchars($data['impressum'] ?? ''); ?></textarea>
<h3>Datenschutz</h3>
<textarea name="privacy" rows="10" cols="80"><?php echo htmlspecialchars($data['privacy'] ?? ''); ?></textarea><br>
<button>Speichern</button>
</form>
<p><a href="admin.php">Zurück</a></p>
</body></html>
