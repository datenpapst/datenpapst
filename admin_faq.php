<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') { http_response_code(403); exit('Zugriff verweigert'); }
$pdo = get_db();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_category'])) {
        $name = trim($_POST['new_category']);
        if ($name !== '') {
            $pdo->prepare('INSERT INTO faq_categories (name) VALUES (?)')->execute([$name]);
        }
    } elseif (isset($_POST['question'], $_POST['answer'], $_POST['category_id'])) {
        $question = trim($_POST['question']);
        $answer = trim($_POST['answer']);
        $cat = (int)$_POST['category_id'];
        if ($question && $answer) {
            $pdo->prepare('INSERT INTO faqs (category_id, question, answer) VALUES (?, ?, ?)')
                ->execute([$cat, $question, $answer]);
        } else {
            $error = 'Frage und Antwort angeben';
        }
    }
}
$categories = $pdo->query('SELECT id, name FROM faq_categories ORDER BY name')->fetchAll();
$faqs = $pdo->query('SELECT f.question, f.answer, c.name FROM faqs f JOIN faq_categories c ON f.category_id=c.id ORDER BY c.name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>FAQ verwalten</title>
</head>
<body>
<p>Admin <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="admin.php">Menü</a> | <a href="logout.php">Logout</a></p>
<h2>Neue Kategorie</h2>
<form method="post">
    <input type="text" name="new_category" required>
    <button type="submit">Speichern</button>
</form>
<h2>Neue FAQ</h2>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form method="post">
    <label>Kategorie:
        <select name="category_id">
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>
    <label>Frage:<br><textarea name="question" rows="2" cols="40" required></textarea></label><br>
    <label>Antwort:<br><textarea name="answer" rows="4" cols="40" required></textarea></label><br>
    <button type="submit">Hinzufügen</button>
</form>
<h2>Vorhandene FAQs</h2>
<ul>
<?php foreach ($faqs as $f): ?>
    <li><strong><?php echo htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($f['question'], ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($f['answer'], ENT_QUOTES, 'UTF-8'); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
