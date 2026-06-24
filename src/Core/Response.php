<?php
namespace CyberKavach\Core;

class Response
{
    public static function json($data, int $status = 200): void
    {
        if (PHP_SAPI !== 'cli') {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // CLI: just print JSON without sending HTTP headers
        echo json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        exit;
    }

    public static function view(string $html): void
    {
        if (PHP_SAPI !== 'cli') {
            header('Content-Type: text/html; charset=utf-8');
            echo $html;
            exit;
        }

        // CLI: output raw
        echo $html . PHP_EOL;
        exit;
    }

    public static function redirect(string $url): void
    {
        if (PHP_SAPI !== 'cli') {
            header('Location: ' . $url);
            exit;
        }

        // CLI: print the redirect target
        echo 'Redirect: ' . $url . PHP_EOL;
        exit;
    }
}
