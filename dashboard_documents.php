<?php
require_once __DIR__ . '/auth.php';
require_login();
$pdo = get_db();
$user = current_user();
require_once __DIR__ . '/upload_utils.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    if ($_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','jpg','jpeg','png'];
        if (in_array($ext, $allowed, true)) {
            $dir = __DIR__ . '/uploads/' . $user['id'];
            if (!is_dir($dir)) {
                mkdir($dir, 0700, true);
            }
            $filename = bin2hex(random_bytes(8)) . '.' . $ext;
            if (!scan_file($_FILES['document']['tmp_name'])) {
                $error = 'Datei blockiert (Virusverdacht)';
            } else {
                move_uploaded_file($_FILES['document']['tmp_name'], $dir . '/' . $filename);
                $stmt = $pdo->prepare('INSERT INTO documents (user_id, filename) VALUES (?, ?)');
                $stmt->execute([$user['id'], $filename]);
            }
        } else {
            $error = 'Ungültiges Dateiformat';
        }
    } else {
        $error = 'Upload fehlgeschlagen';
    }
}

$stmt = $pdo->prepare('SELECT id, filename, uploaded_at FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC');
$stmt->execute([$user['id']]);
$docs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Dokumente</title>
</head>
<body>
<p>Eingeloggt als <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="dashboard.php">Menü</a> | <a href="logout.php">Logout</a></p>
<h2>Dokumente</h2>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<ul>
<?php foreach ($docs as $doc): ?>
    <li><a href="download.php?id=<?php echo (int)$doc['id']; ?>">Dokument vom <?php echo htmlspecialchars($doc['uploaded_at'], ENT_QUOTES, 'UTF-8'); ?></a></li>
<?php endforeach; ?>
</ul>
<h3>Neues Dokument hochladen</h3>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="document" required>
    <button type="submit">Hochladen</button>
</form>
</body>
</html>
