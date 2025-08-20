<?php
return [
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_pass' => getenv('DB_PASS') ?: '',
    'db_name' => getenv('DB_NAME') ?: 'mietverwaltung',
    'recaptcha_site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '',
    'recaptcha_secret_key' => getenv('RECAPTCHA_SECRET_KEY') ?: '',
];
?>
