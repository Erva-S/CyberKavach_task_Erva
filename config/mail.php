<?php
use CyberKavach\Core\Environment;

return [
    'host' => Environment::get('MAIL_HOST', 'smtp.example.com'),
    'port' => (int) Environment::get('MAIL_PORT', 587),
    'username' => Environment::get('MAIL_USERNAME', null),
    'password' => Environment::get('MAIL_PASSWORD', null),
    'from_email' => Environment::get('MAIL_FROM', 'no-reply@cyberkavach.local'),
    'from_name' => Environment::get('MAIL_FROM_NAME', 'CyberKavach'),
    'smtp_secure' => Environment::get('MAIL_SECURE', 'tls')
];
