<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') { http_response_code(403); exit('forbidden'); }

$base = __DIR__;
$backupDir = $base . '/backups';
if (!is_dir($backupDir)) { mkdir($backupDir, 0770, true); }

if (isset($_POST['create'])) {
    $file = $backupDir.'/backup_'.date('Ymd_His').'.zip';
    $zip = new ZipArchive();
    if ($zip->open($file, ZipArchive::CREATE) === TRUE) {
        $zip->addGlob($base.'/uploads/*/*');
        $zip->close();
    }
}

$files = array_diff(scandir($backupDir), ['.','..']);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Backups</title><link rel="stylesheet" href="style.css"><script src="script.js" defer></script></head>
<body>
<div id="burger">☰</div>
<h1>Backups</h1>
<form method="post"><button name="create">Backup erstellen</button></form>
<ul>
<?php foreach ($files as $f): ?>
    <li><a href="backups/<?php echo urlencode($f); ?>"><?php echo htmlspecialchars($f); ?></a></li>
<?php endforeach; ?>
</ul>
<p><a href="admin.php">Zurück</a></p>
</body></html>
