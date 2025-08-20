<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/recaptcha.php';
require_once __DIR__ . '/mail_utils.php';
$config = require __DIR__ . '/config.php';
$error = '';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptcha = $_POST['g-recaptcha-response'] ?? '';
    if (!verify_recaptcha($recaptcha)) {
        $error = 'Bitte Captcha bestätigen';
    } else {
        $email = trim($_POST['email'] ?? '');
        if ($email !== '') {
            $pdo = get_db();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            $message = 'Falls die Adresse bekannt ist, wurde ein Link gesendet.';
            if ($user) {
                $token = bin2hex(random_bytes(16));
                $expires = date('Y-m-d H:i:s', time() + 3600);
                $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)')
                    ->execute([$user['id'], $token, $expires]);
                $link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/reset_password.php?token=' . $token;
                queue_mail($email, 'Passwort zurücksetzen', "Zum Zurücksetzen bitte folgenden Link besuchen: $link");
            }
        } else {
            $error = 'Bitte Email eingeben';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Passwort vergessen</title>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
<h1>Passwort vergessen</h1>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<?php if ($message): ?><p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form method="post">
    <label>Email: <input type="email" name="email" required></label>
    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($config['recaptcha_site_key'], ENT_QUOTES, 'UTF-8'); ?>"></div>
    <button type="submit">Link senden</button>
</form>
<p><a href="index.php">Zurück zum Login</a></p>
</body>
</html>
