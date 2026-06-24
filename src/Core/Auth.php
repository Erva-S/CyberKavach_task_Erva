<?php
namespace CyberKavach\Core;

use CyberKavach\Core\Database;
use PDO;

class Auth
{
    private PDO $db;
    private array $config;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->config = require __DIR__ . '/../../config/app.php';
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function attempt(string $email, string $password): bool
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        $this->loginUser($user['id']);
        return true;
    }

    private function loginUser(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['_user_id'] = $userId;
        $_SESSION['_logged_at'] = time();

        // Issue short-lived JWT
        $token = $this->issueJwt(['sub' => $userId, 'iat' => time()]);
        $this->setSessionCookie($token, time() + (int)($this->config['jwt']['expires_in'] ?? 900));

        // record session in DB
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $stmt = $this->db->prepare('INSERT INTO user_sessions (user_id, token_hash, ip_address, user_agent, device_fingerprint, created_at, expires_at, last_used_at, is_revoked) VALUES (:user_id, :token_hash, :ip, :ua, NULL, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), 0)');
        $stmt->execute([':user_id' => $userId, ':token_hash' => hash('sha256', $token), ':ip' => $ip, ':ua' => $ua]);
    }

    public function logout(): void
    {
        $token = $_COOKIE['ck_session'] ?? null;
        if (is_string($token) && $token !== '') {
            $this->revokeSessionToken($token);
        }

        // revoke cookie and session
        $this->setSessionCookie('', time() - 3600);
        if (isset($_SESSION)) {
            $_SESSION = [];
            session_destroy();
        }
    }

    public function check(): bool
    {
        if (!empty($_SESSION['_user_id'])) {
            $token = $_COOKIE['ck_session'] ?? null;
            if (is_string($token) && $token !== '') {
                $session = $this->findActiveSessionByToken($token, (int)$_SESSION['_user_id']);
                if (!$session) {
                    $this->logout();
                    return false;
                }

                $this->touchSessionToken($token);
            }

            return true;
        }

        $token = $_COOKIE['ck_session'] ?? null;
        if (!$token) {
            return false;
        }

        $payload = $this->verifyJwt($token);
        if (!$payload) {
            return false;
        }

        $userId = isset($payload['sub']) ? (int)$payload['sub'] : 0;
        if ($userId <= 0) {
            return false;
        }

        $session = $this->findActiveSessionByToken($token, $userId);
        if (!$session) {
            return false;
        }

        $_SESSION['_user_id'] = $userId;
        $_SESSION['_logged_at'] = time();
        $this->touchSessionToken($token);

        return true;
    }

    public function user(): ?array
    {
        $id = $_SESSION['_user_id'] ?? null;
        if ($id) {
            $stmt = $this->db->prepare('SELECT id, ulid, name, email, avatar_url, department FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        return null;
    }

    public function currentRoleSlug(?int $userId = null): ?string
    {
        $userId = $userId ?? (int)($_SESSION['_user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT r.slug FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = :uid AND ur.is_active = 1 ORDER BY r.level DESC, r.id ASC LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $roleSlug = $stmt->fetchColumn();
        return $roleSlug ? (string)$roleSlug : null;
    }

    public function redirectPathForUser(int $userId): string
    {
        $roleSlug = $this->currentRoleSlug($userId);
        $map = [
            'faculty_coordinator' => '/dashboard/faculty',
            'student_coordinator' => '/dashboard/student',
            'tech_coordinator' => '/dashboard/tech',
            'content_coordinator' => '/dashboard/content',
            'social_media_coordinator' => '/dashboard/social',
            'guest' => '/dashboard/guest',
        ];

        return $map[$roleSlug] ?? '/dashboard';
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function issueJwt(array $claims): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => $this->config['jwt']['algo'], 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode(array_merge($claims, ['exp' => time() + ($this->config['jwt']['expires_in'] ?? 900)])));
        $sig = $this->base64UrlEncode(hash_hmac('sha256', $header . '.' . $payload, $this->config['jwt']['secret'], true));
        return $header . '.' . $payload . '.' . $sig;
    }

    public function verifyJwt(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB, $payloadB, $sigB] = $parts;
        $header = json_decode($this->base64UrlDecode($headerB), true);
        $payload = json_decode($this->base64UrlDecode($payloadB), true);

        $expectedSig = $this->base64UrlEncode(hash_hmac('sha256', $headerB . '.' . $payloadB, $this->config['jwt']['secret'], true));
        if (!hash_equals($expectedSig, $sigB)) {
            return null;
        }

        if (isset($payload['exp']) && time() > (int)$payload['exp']) {
            return null;
        }

        return $payload;
    }

    private function setSessionCookie(string $token, int $expiresAt): void
    {
        setcookie('ck_session', $token, [
            'expires' => $expiresAt,
            'path' => $this->config['cookie']['path'] ?? '/',
            'secure' => $this->config['cookie']['secure'] ?? false,
            'httponly' => $this->config['cookie']['httponly'] ?? true,
            'samesite' => $this->config['cookie']['samesite'] ?? 'Strict',
        ]);
    }

    private function findActiveSessionByToken(string $token, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_sessions WHERE user_id = :user_id AND token_hash = :token_hash AND is_revoked = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => hash('sha256', $token),
        ]);

        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        return $session ?: null;
    }

    private function touchSessionToken(string $token): void
    {
        $stmt = $this->db->prepare('UPDATE user_sessions SET last_used_at = NOW() WHERE token_hash = :token_hash AND is_revoked = 0');
        $stmt->execute([':token_hash' => hash('sha256', $token)]);
    }

    private function revokeSessionToken(string $token): void
    {
        $stmt = $this->db->prepare('UPDATE user_sessions SET is_revoked = 1, last_used_at = NOW() WHERE token_hash = :token_hash');
        $stmt->execute([':token_hash' => hash('sha256', $token)]);
    }
}
