<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Zugriff verweigert');
}
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
$pdo = get_db();
$primary = get_setting('primary_color', '#0d6efd');
$message = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM announcements WHERE id=?');
    $stmt->execute([$id]);
    $message = t('deleted');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $until = $_POST['visible_until'] ?? null;
    if ($title !== '' && $content !== '') {
        $stmt = $pdo->prepare('INSERT INTO announcements (title, content, visible_until) VALUES (?,?,?)');
        $stmt->execute([$title, $content, $until !== '' ? $until : null]);
        $message = t('saved');
    } else {
        $message = t('fill_all_fields');
    }
}

$ann = $pdo->query('SELECT id, title, visible_until FROM announcements ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<title><?php echo t('announcements_manage'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="script.js" defer></script>
</head>
<body>
<div id="burger">☰</div>
<p><a href="admin.php"><?php echo t('back'); ?></a> | <a href="logout.php"><?php echo t('logout'); ?></a></p>
<h1><?php echo t('announcements_manage'); ?></h1>
<?php if ($message): ?><p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form method="post">
    <label><?php echo t('event_title'); ?>: <input type="text" name="title"></label><br>
    <label><?php echo t('description'); ?>:<br><textarea name="content" rows="4" cols="40"></textarea></label><br>
    <label><?php echo t('visible_until'); ?>: <input type="date" name="visible_until"></label><br>
    <button type="submit"><?php echo t('save'); ?></button>
</form>
<h2><?php echo t('existing_announcements'); ?></h2>
<ul>
<?php foreach ($ann as $a): ?>
    <li><?php echo htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'); ?><?php if ($a['visible_until']): ?> (<?php echo htmlspecialchars($a['visible_until'], ENT_QUOTES, 'UTF-8'); ?>)<?php endif; ?> - <a href="?delete=<?php echo (int)$a['id']; ?>" onclick="return confirm('Sicher?');"><?php echo t('delete'); ?></a></li>
<?php endforeach; ?>
</ul>
</body>
</html>
