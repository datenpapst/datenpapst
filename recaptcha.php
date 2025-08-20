<?php
function verify_recaptcha(string $response): bool {
    $config = require __DIR__ . '/config.php';
    $secret = $config['recaptcha_secret_key'] ?? '';
    if (!$secret || !$response) {
        return false;
    }
    $data = http_build_query([
        'secret' => $secret,
        'response' => $response,
    ]);
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => $data,
            'timeout' => 5,
        ],
    ];
    $context = stream_context_create($opts);
    $result = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    if ($result === false) {
        return false;
    }
    $json = json_decode($result, true);
    return isset($json['success']) && $json['success'] === true;
}
?>
