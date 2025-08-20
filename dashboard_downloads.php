<?php
require_once __DIR__ . '/auth.php';
require_login();
$pdo = get_db();
$user = current_user();
$templates = $pdo->query('SELECT id, title, uploaded_at FROM templates ORDER BY uploaded_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Downloads</title>
</head>
<body>
<p>Eingeloggt als <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="dashboard.php">Menü</a> | <a href="logout.php">Logout</a></p>
<h2>Vorlagen & Dokumente</h2>
<ul>
<?php foreach ($templates as $t): ?>
    <li><a href="download.php?template_id=<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8'); ?></a> (<?php echo htmlspecialchars($t['uploaded_at'], ENT_QUOTES, 'UTF-8'); ?>)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
