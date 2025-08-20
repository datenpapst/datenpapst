<?php
if (file_exists(__DIR__.'/install.lock')) { exit('Install gesperrt'); }
$config = require __DIR__.'/config.php';
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',$config['db_host'],$config['db_name']);
try {
    $pdo = new PDO($dsn,$config['db_user'],$config['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $sql = file_get_contents(__DIR__.'/init.sql');
    $pdo->exec($sql);
    file_put_contents(__DIR__.'/install.lock','installed');
    echo '<h1>Installation abgeschlossen</h1>';
    if (empty($config['recaptcha_site_key']) || empty($config['recaptcha_secret_key'])) {
        echo '<p><strong>reCAPTCHA konfigurieren:</strong> Besuchen Sie <a href="https://www.google.com/recaptcha/admin/'
            . 'create" target="_blank">google.com/recaptcha</a>, '
            . 'legen Sie ein neues Projekt vom Typ "reCAPTCHA v2" an und tragen Sie Site-Key und Secret als Umgebungsvariablen <code>RECAPTCHA_SITE_KEY</code> '
            . 'und <code>RECAPTCHA_SECRET_KEY</code> ein.</p>';
    }
    echo '<p>Nach dem ersten Login als Admin können Sie unter <code>enable_2fa.php</code> die Zwei-Faktor-Authentifizierung aktivieren. Scannen Sie den angezeigten QR-Code mit einer Authenticator-App (z.B. Google oder Microsoft).</p>';
} catch (Exception $e) {
    echo 'Fehler: '.$e->getMessage();
}
?>
