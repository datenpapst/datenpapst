<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/language.php';
$pdo = get_db();
$user = current_user();
$error='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cat = $_POST['category'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $details = trim($_POST['details'] ?? '');
    if ($cat) {
        $stmt = $pdo->prepare('INSERT INTO usage_requests (user_id, category, person_name, details) VALUES (?,?,?,?)');
        $stmt->execute([$user['id'], $cat, $name, $details]);
    } else {
        $error = t('fill_all_fields');
    }
}
$stmt = $pdo->prepare('SELECT category, person_name, status, created_at FROM usage_requests WHERE user_id=? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$reqs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><title><?php echo t('usage_change'); ?></title></head>
<body>
<p>Eingeloggt als <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="dashboard.php">Menü</a> | <a href="logout.php">Logout</a></p>
<h2><?php echo t('usage_change'); ?></h2>
<p><?php echo t('usage_notice'); ?></p>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<form method="post">
<label><?php echo t('category'); ?>:
<select name="category" required>
<option value="">--Bitte wählen--</option>
<option value="partner"><?php echo t('partner'); ?></option>
<option value="roommate"><?php echo t('roommate'); ?></option>
<option value="airbnb"><?php echo t('airbnb'); ?></option>
<option value="sublet"><?php echo t('sublet'); ?></option>
<option value="structural_change"><?php echo t('structural_change'); ?></option>
</select></label><br>
<label><?php echo t('person_name'); ?>:<br><input type="text" name="name"></label><br>
<label><?php echo t('details'); ?><br><textarea name="details" rows="4" cols="40"></textarea></label><br>
<button type="submit"><?php echo t('save'); ?></button>
</form>
<h3><?php echo t('requests'); ?></h3>
<ul>
<?php foreach ($reqs as $r): ?>
<li><?php echo htmlspecialchars($r['created_at']); ?> - <?php echo t($r['category']); ?> <?php echo htmlspecialchars($r['person_name']); ?> (<?php echo t($r['status']); ?>)</li>
<?php endforeach; ?>
</ul>
</body></html>
