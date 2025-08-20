<?php
require_once __DIR__ . '/auth.php';
require_login();
$pdo = get_db();
$user = current_user();
$categories = $pdo->query('SELECT id, name FROM faq_categories ORDER BY name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>FAQ</title>
</head>
<body>
<p>Eingeloggt als <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="dashboard.php">Menü</a> | <a href="logout.php">Logout</a></p>
<h2>FAQ</h2>
<?php foreach ($categories as $c): ?>
    <h3><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
    <ul>
    <?php
        $stmt = $pdo->prepare('SELECT question, answer FROM faqs WHERE category_id = ?');
        $stmt->execute([$c['id']]);
        foreach ($stmt->fetchAll() as $f): ?>
        <li><strong><?php echo htmlspecialchars($f['question'], ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($f['answer'], ENT_QUOTES, 'UTF-8'); ?></li>
    <?php endforeach; ?>
    </ul>
<?php endforeach; ?>
</body>
</html>
