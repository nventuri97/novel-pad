<?php
return [
    'smtp_host' => getenv('SMTP_HOST'),
    'smtp_user' => getenv('SMTP_USER'),
    'smtp_password' => getenv('SMTP_PASSWORD'),
    'smtp_port' => getenv('SMTP_PORT') ?: 587,
    'smtp_encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
    'novelpad_url' => getenv('NOVELPAD_URL'),
    'db_host' => getenv('DB_HOST'),
    'db_user' => getenv('DB_USER'),
    'db_password' => getenv('DB_PASSWORD')
];
?>