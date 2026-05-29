<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CyberKavach\Core\Router;

// Basic front controller (scaffold)
http_response_code(200);
header('Content-Type: text/html; charset=utf-8');

echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CyberKavach</title><link rel="stylesheet" href="/cyberkavach/public/assets/css/design-system.css"></head><body><main style="padding:32px;color:var(--text-primary);background:var(--bg-base);font-family:var(--font-body);min-height:100vh;"><h1>CyberKavach — scaffold</h1><p>Front controller is working. Implement routing in <code>src/Core/Router.php</code>.</p></main></body></html>';
