<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/totp.php';
if (!isset($_SESSION['pending_user'])) {
    header('Location: index.php');
    exit();
}
$user = $_SESSION['pending_user'];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT otp_secret FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    if ($row && verify_totp($row['otp_secret'], $code)) {
        $remember = !empty($user['remember']);
        login_user($user['id'], $user['email'], $user['role'], $remember);
        unset($_SESSION['pending_user']);
        header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
        exit();
    } else {
        $error = 'Code ungültig';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>2FA Verifizierung</title>
</head>
<body>
<h1>Code eingeben</h1>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form method="post">
    <label>Code: <input type="text" name="code" required></label>
    <button type="submit">Bestätigen</button>
</form>
</body>
</html>
