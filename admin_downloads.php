<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') { http_response_code(403); exit('Zugriff verweigert'); }
$pdo = get_db();
require_once __DIR__ . '/upload_utils.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['template'])) {
    if ($_FILES['template']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['template']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','xls','xlsx'];
        if (in_array($ext, $allowed, true)) {
            $dir = __DIR__ . '/templates';
            if (!is_dir($dir)) { mkdir($dir, 0700, true); }
            $filename = bin2hex(random_bytes(8)) . '.' . $ext;
            if (!scan_file($_FILES['template']['tmp_name'])) {
                $error = 'Datei blockiert (Virusverdacht)';
            } else {
                move_uploaded_file($_FILES['template']['tmp_name'], $dir . '/' . $filename);
                $title = trim($_POST['title'] ?? '');
                $pdo->prepare('INSERT INTO templates (filename, title) VALUES (?, ?)')->execute([$filename, $title]);
            }
        } else { $error = 'Ungültiges Format'; }
    } else { $error = 'Upload fehlgeschlagen'; }
}
$templates = $pdo->query('SELECT id, title, uploaded_at FROM templates ORDER BY uploaded_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Vorlagen verwalten</title>
</head>
<body>
<p>Admin <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="admin.php">Menü</a> | <a href="logout.php">Logout</a></p>
<h2>Vorlagen</h2>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<ul>
<?php foreach ($templates as $t): ?>
    <li><a href="download.php?template_id=<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8'); ?></a> (<?php echo htmlspecialchars($t['uploaded_at'], ENT_QUOTES, 'UTF-8'); ?>)</li>
<?php endforeach; ?>
</ul>
<h3>Neue Vorlage hochladen</h3>
<form method="post" enctype="multipart/form-data">
    <label>Titel: <input type="text" name="title" required></label><br>
    <input type="file" name="template" required>
    <button type="submit">Hochladen</button>
</form>
</body>
</html>
