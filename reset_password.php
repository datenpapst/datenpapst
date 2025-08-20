<?php
require_once __DIR__ . '/db.php';
$token = $_GET['token'] ?? '';
$error = '';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($token && $password) {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        if ($row) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([$hash, $row['user_id']]);
            $pdo->prepare('DELETE FROM password_resets WHERE token = ?')->execute([$token]);
            $message = 'Passwort aktualisiert. <a href="index.php">Zum Login</a>';
        } else {
            $error = 'Ungültiger oder abgelaufener Token';
        }
    } else {
        $error = 'Alle Felder ausfüllen';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Passwort zurücksetzen</title>
</head>
<body>
<h1>Passwort zurücksetzen</h1>
<?php if ($error): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>
<?php if ($message): ?>
<p><?php echo $message; ?></p>
<?php else: ?>
<form method="post">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
    <label>Neues Passwort: <input type="password" name="password" required></label>
    <button type="submit">Speichern</button>
</form>
<?php endif; ?>
</body>
</html>
