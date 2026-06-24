<?php
use CyberKavach\Core\Environment;

return [
    'env' => Environment::get('APP_ENV', 'production'),
    'debug' => in_array(strtolower((string) Environment::get('APP_DEBUG', 'false')), ['1', 'true', 'yes'], true),
    'url' => Environment::get('APP_URL', 'http://localhost/cyberkavach'),
    'institutional_email_domains' => array_values(array_filter(array_map('trim', explode(',', Environment::get('INSTITUTIONAL_EMAIL_DOMAINS', 'cyberkavach.local'))))),

    'jwt' => [
        'secret' => Environment::get('JWT_SECRET', 'replace_me_with_strong_secret'),
        'algo' => 'HS256',
        'expires_in' => 900 // 15 minutes
    ],

    'otp' => [
        'expires_in_minutes' => (int) Environment::get('OTP_EXPIRES_MINUTES', 10),
        'max_attempts' => (int) Environment::get('OTP_MAX_ATTEMPTS', 3),
    ],

    'cookie' => [
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
        'path' => '/'
    ]
];
