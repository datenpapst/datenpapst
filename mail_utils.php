<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';

function queue_mail(string $to, string $subject, string $body): void {
    $pdo = get_db();
    $stmt = $pdo->prepare('INSERT INTO email_queue (recipient, subject, body, created_at) VALUES (?,?,?,NOW())');
    $stmt->execute([$to, $subject, $body]);
}

function notify_manager(int $apartment_id, string $subject, string $body): void {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT manager_contact FROM apartments WHERE id=?');
    $stmt->execute([$apartment_id]);
    if ($email = $stmt->fetchColumn()) {
        queue_mail($email, $subject, $body);
    }
}

function queue_template_mail(string $to, string $template, array $vars = [], string $lang = 'de'): void {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT subject, body FROM mail_templates WHERE template_key=? AND lang=?');
    $stmt->execute([$template, $lang]);
    if ($tpl = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $subject = $tpl['subject'];
        $body = $tpl['body'];
        foreach ($vars as $k => $v) {
            $subject = str_replace('{{'.$k.'}}', $v, $subject);
            $body = str_replace('{{'.$k.'}}', $v, $body);
        }
        queue_mail($to, $subject, $body);
    }
}

function send_queued_emails(): void {
    $pdo = get_db();
    $host = get_setting('smtp_host', '');
    $port = get_setting('smtp_port', '');
    $user = get_setting('smtp_user', '');
    $pass = get_setting('smtp_pass', '');
    $from = get_setting('smtp_from', 'no-reply@example.com');
    if ($host) { ini_set('SMTP', $host); }
    if ($port) { ini_set('smtp_port', $port); }
    if ($from) { ini_set('sendmail_from', $from); }
    $stmt = $pdo->query('SELECT id, recipient, subject, body FROM email_queue WHERE sent_at IS NULL ORDER BY created_at');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $headers = "From: $from\r\nX-Mailer: PHP";
        @mail($row['recipient'], $row['subject'], $row['body'], $headers);
        $pdo->prepare('UPDATE email_queue SET sent_at = NOW() WHERE id=?')->execute([$row['id']]);
        usleep(100000); // 0.1s pause to avoid bursts
    }
}
?>
