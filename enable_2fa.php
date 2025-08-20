<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/totp.php';
require_once __DIR__ . '/settings.php';
$pdo = get_db();
$user = current_user();
$site_title = get_setting('site_title', 'TanMan Plattform');
$stmt = $pdo->prepare('SELECT otp_secret FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$row = $stmt->fetch();
$has = !empty($row['otp_secret']);
$message = '';

if (isset($_POST['disable']) && $has) {
    $pdo->prepare('UPDATE users SET otp_secret = NULL WHERE id = ?')->execute([$user['id']]);
    $has = false;
    $message = 'Zwei-Faktor-Authentifizierung deaktiviert.';
} elseif (!$has && isset($_POST['code']) && isset($_SESSION['setup_secret'])) {
    $code = trim($_POST['code']);
    if (verify_totp($_SESSION['setup_secret'], $code)) {
        $pdo->prepare('UPDATE users SET otp_secret = ? WHERE id = ?')->execute([$_SESSION['setup_secret'], $user['id']]);
        unset($_SESSION['setup_secret']);
        $has = true;
        $message = 'Zwei-Faktor-Authentifizierung aktiviert.';
    } else {
        $message = 'Code ungültig, bitte erneut versuchen.';
    }
}

if (!$has && !isset($_SESSION['setup_secret'])) {
    $_SESSION['setup_secret'] = generate_secret();
}
$secret = $_SESSION['setup_secret'] ?? '';
$otpauth = 'otpauth://totp/' . urlencode($site_title . ':' . $user['email']) . '?secret=' . $secret . '&issuer=' . urlencode($site_title);
$qr = 'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=' . urlencode($otpauth);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>2FA Einstellungen</title>
</head>
<body>
<p>Eingeloggt als <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="dashboard.php">Menü</a> | <a href="logout.php">Logout</a></p>
<h2>Zwei-Faktor-Authentifizierung</h2>
<?php if ($message): ?><p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<?php if ($has): ?>
    <p>2FA ist aktiviert.</p>
    <form method="post">
        <button type="submit" name="disable" value="1">Deaktivieren</button>
    </form>
<?php else: ?>
    <p>Scannen Sie den QR-Code mit Ihrer Authenticator-App und geben Sie anschließend den Code ein.</p>
    <img src="<?php echo $qr; ?>" alt="QR Code"><br>
    <p>Secret: <?php echo htmlspecialchars($secret, ENT_QUOTES, 'UTF-8'); ?></p>
    <form method="post">
        <label>Code: <input type="text" name="code" required></label>
        <button type="submit">Aktivieren</button>
    </form>
<?php endif; ?>
</body>
</html>
