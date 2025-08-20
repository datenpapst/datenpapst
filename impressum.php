<?php
require_once __DIR__ . '/db.php';
$pdo = get_db();
$content = $pdo->query("SELECT content FROM page_contents WHERE page='impressum'")->fetchColumn();
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Impressum</title><link rel="stylesheet" href="style.css"><script src="script.js" defer></script></head>
<body>
<div id="burger">☰</div>
<?php echo nl2br(htmlspecialchars($content)); ?>
</body></html>
