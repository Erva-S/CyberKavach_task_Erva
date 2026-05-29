<?php
namespace CyberKavach\Core;

class Auth
{
    public static function check(): bool
    {
        session_start();
        return !empty($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        session_start();
        return $_SESSION['user_id'] ?? null;
    }
}
