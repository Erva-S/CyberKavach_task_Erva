<?php
use CyberKavach\Core\Environment;

return [
    'host' => Environment::get('DB_HOST', 'localhost'),
    'port' => (string) Environment::get('DB_PORT', '3306'),
    'database' => Environment::get('DB_DATABASE', 'cyberkavach'),
    'username' => Environment::get('DB_USERNAME', 'root'),
    'password' => Environment::get('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
];
