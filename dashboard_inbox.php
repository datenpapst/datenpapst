<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/language.php';
$pdo = get_db();
$user = current_user();
$primary = get_setting('primary_color', '#0d6efd');

$stmt = $pdo->prepare('SELECT m.content, m.created_at, u.email, u.profile_image FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.user_id = ? ORDER BY m.created_at DESC');
$stmt->execute([$user['id']]);
$msgs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<title><?php echo t('messages'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="script.js" defer></script>
</head>
<body>
<div id="burger">☰</div>
<p><a href="dashboard.php"><?php echo t('back'); ?></a> | <a href="logout.php"><?php echo t('logout'); ?></a></p>
<h1><?php echo t('messages'); ?></h1>
<ul>
<?php foreach ($msgs as $m): ?>
    <li class="message"><?php if ($m['profile_image']): ?><img src="uploads/profile/<?php echo htmlspecialchars($m['profile_image'], ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt=""> <?php endif; ?><strong><?php echo htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8'); ?></strong> (<?php echo htmlspecialchars($m['created_at'], ENT_QUOTES, 'UTF-8'); ?>)<br><?php echo $m['content']; ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
