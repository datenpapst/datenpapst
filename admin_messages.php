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
$message = '';
$primary = get_setting('primary_color', '#0d6efd');
$tenants = $pdo->query("SELECT id, email FROM users WHERE role='tenant'")->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $allowed = '<b><strong><u><i><ul><ol><li><br><p>';
    $content = strip_tags($content, $allowed);
    if ($user_id && $content !== '') {
        $stmt = $pdo->prepare('INSERT INTO messages (user_id, sender_id, content) VALUES (?,?,?)');
        $stmt->execute([$user_id, $user['id'], $content]);
        $message = 'Nachricht gesendet';
    } else {
        $message = 'Alle Felder ausfüllen';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<title><?php echo t('messages_send'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="script.js" defer></script>
<style>
#editor{border:1px solid #ccc;padding:.5rem;min-height:100px;}
.toolbar button{margin-right:.25rem;}
</style>
<script>
function format(cmd){document.execCommand(cmd,false,null);}
function submitForm(){document.getElementById('content').value=document.getElementById('editor').innerHTML;}
</script>
</head>
<body>
<div id="burger">☰</div>
<p><a href="admin.php"><?php echo t('back'); ?></a> | <a href="logout.php"><?php echo t('logout'); ?></a></p>
<h1><?php echo t('messages_send'); ?></h1>
<?php if ($message): ?><p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form method="post" onsubmit="submitForm()">
    <label>Mieter:
        <select name="user_id">
            <?php foreach ($tenants as $t): ?>
                <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['email'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>
    <div class="toolbar">
        <button type="button" onclick="format('bold')"><b>B</b></button>
        <button type="button" onclick="format('underline')"><u>U</u></button>
        <button type="button" onclick="format('insertUnorderedList')">•</button>
    </div>
    <div id="editor" contenteditable="true"></div>
    <input type="hidden" name="content" id="content">
    <button type="submit"><?php echo t('save'); ?></button>
</form>
</body>
</html>
