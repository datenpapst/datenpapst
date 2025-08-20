<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') { http_response_code(403); exit('forbidden'); }
$pdo = get_db();
require_once __DIR__ . '/upload_utils.php';
require_once __DIR__ . '/mail_utils.php';
require_once __DIR__ . '/language.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int)($_POST['uid'] ?? 0);
    if (isset($_POST['deactivate'])) {
        $pdo->prepare('UPDATE users SET status="disabled" WHERE id=?')->execute([$uid]);
    } elseif (isset($_POST['activate'])) {
        $pdo->prepare('UPDATE users SET status="active" WHERE id=?')->execute([$uid]);
    } elseif (isset($_POST['block'])) {
        $pdo->prepare('UPDATE users SET status="blocked" WHERE id=?')->execute([$uid]);
    } elseif (isset($_POST['delete'])) {
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);
    } elseif (isset($_POST['reset2fa'])) {
        $pdo->prepare('UPDATE users SET otp_secret=NULL WHERE id=?')->execute([$uid]);
    } elseif (isset($_POST['change_role'])) {
        $role = $_POST['role'] === 'admin' ? 'admin' : 'tenant';
        $pdo->prepare('UPDATE users SET role=? WHERE id=?')->execute([$role, $uid]);
    } elseif (!empty($_FILES['doc']['name'])) {
        $dir = __DIR__ . '/uploads/user_docs/' . $uid;
        if (!is_dir($dir)) { mkdir($dir, 0770, true); }
        $name = basename($_FILES['doc']['name']);
        if (scan_file($_FILES['doc']['tmp_name'])) {
            move_uploaded_file($_FILES['doc']['tmp_name'], $dir . '/' . $name);
            $pdo->prepare('INSERT INTO documents (user_id, filename, visibility) VALUES (?,?,"admin")')->execute([$uid, $name]);
        }
    } elseif (isset($_POST['add_user'])) {
        $email = trim($_POST['new_email'] ?? '');
        $pass = $_POST['new_pass'] ?? '';
        $role = $_POST['new_role'] === 'admin' ? 'admin' : 'tenant';
        if ($email && $pass) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO users (email,password_hash,role) VALUES (?,?,?)')->execute([$email,$hash,$role]);
            queue_mail($email, 'Zugangsdaten', "Ihr Zugang:\nEmail: $email\nPasswort: $pass");
        }
    }
}

$users = $pdo->query('SELECT id,email,role,status,otp_secret FROM users')->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Users</title><link rel="stylesheet" href="style.css"><script src="script.js" defer></script></head>
<body>
<div id="burger">☰</div>
<h1><?php echo t('users'); ?></h1>
<form method="post" style="margin-bottom:1em">
    <input type="email" name="new_email" placeholder="<?php echo t('email'); ?>" required>
    <input type="text" name="new_pass" placeholder="<?php echo t('password'); ?>" required>
    <select name="new_role">
        <option value="tenant"><?php echo t('tenant'); ?></option>
        <option value="admin"><?php echo t('admin'); ?></option>
    </select>
    <button name="add_user"><?php echo t('add_user'); ?></button>
</form>
<table border="1"><tr><th>Email</th><th><?php echo t('role'); ?></th><th>Status</th><th>2FA</th><th>Aktionen</th><th>Dokument hochladen</th></tr>
<?php foreach ($users as $u): ?>
<tr><td><?php echo htmlspecialchars($u['email']); ?></td><td>
    <form method="post">
        <input type="hidden" name="uid" value="<?php echo $u['id']; ?>">
        <select name="role">
            <option value="tenant" <?php if ($u['role'] === 'tenant') echo 'selected'; ?>><?php echo t('tenant'); ?></option>
            <option value="admin" <?php if ($u['role'] === 'admin') echo 'selected'; ?>><?php echo t('admin'); ?></option>
        </select>
        <button name="change_role"><?php echo t('save'); ?></button>
    </form>
</td><td><?php echo $u['status']; ?></td><td>
    <?php echo $u['otp_secret'] ? 'aktiv' : 'inaktiv'; ?>
    <?php if ($u['otp_secret']): ?>
    <form method="post" style="display:inline"><input type="hidden" name="uid" value="<?php echo $u['id']; ?>">
        <button name="reset2fa">Zurücksetzen</button>
    </form>
    <?php endif; ?>
</td><td>
    <form method="post" style="display:inline"><input type="hidden" name="uid" value="<?php echo $u['id']; ?>">
    <?php if ($u['status'] === 'active'): ?>
        <button name="deactivate">Deaktivieren</button>
        <button name="block"><?php echo t('block'); ?></button>
    <?php elseif ($u['status'] === 'disabled'): ?>
        <button name="activate">Aktivieren</button>
        <button name="block"><?php echo t('block'); ?></button>
    <?php else: ?>
        <button name="activate">Aktivieren</button>
    <?php endif; ?>
    <button name="delete" onclick="return confirm('Löschen?')">Löschen</button>
    </form>
</td><td>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="uid" value="<?php echo $u['id']; ?>">
      <input type="file" name="doc">
      <button>Upload</button>
    </form>
</td></tr>
<?php endforeach; ?>
</table>
<p><a href="admin.php">Zurück</a></p>
</body>
</html>
