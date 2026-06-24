<?php
namespace CyberKavach\Modules\Auth;

use CyberKavach\Core\Request;
use CyberKavach\Core\Response;
use CyberKavach\Core\Auth as CoreAuth;
use CyberKavach\Core\Database;
use CyberKavach\Core\Audit;
use CyberKavach\Core\Mailer;
use CyberKavach\Core\View;
use PDO;

class AuthController
{
    private CoreAuth $auth;
    private PDO $db;
    private array $config;
    private Mailer $mailer;

    public function __construct()
    {
        $this->auth = new CoreAuth();
        $this->db = Database::getConnection();
        $this->config = require __DIR__ . '/../../../config/app.php';
        $this->mailer = new Mailer();
    }

    public function login(Request $request): void
    {
        $data = $request->json() ?? $request->all();
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            Response::json(['error' => 'Missing credentials'], 422);
        }

        $ok = $this->auth->attempt($email, $password);
        if ($ok) {
            $user = $this->auth->user();
            Audit::log('login', 'auth', 'user', (string)$user['id'] ?? null, null, ['email' => $email], (int)$user['id']);
            Response::json(['status' => 'ok', 'user' => $user]);
        }

        Response::json(['error' => 'Invalid credentials'], 401);
    }

    public function loginForm(Request $request): void
    {
        $data = $request->all();
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            Response::redirect('/login?error=missing');
        }

        if ($this->auth->attempt((string)$email, (string)$password)) {
            $user = $this->auth->user();
            if ($user) {
                Audit::log('login', 'auth', 'user', (string)$user['id'], null, ['email' => $email], (int)$user['id']);
                Response::redirect($this->auth->redirectPathForUser((int)$user['id']));
            }
        }

        Response::redirect('/login?error=invalid');
    }

    public function registerPage(Request $request): void
    {
        View::render('auth/register', [
            'title' => 'Create account — CyberKavach',
        ], 'auth');
    }

    public function loginPage(Request $request): void
    {
        View::render('auth/login', [
            'title' => 'Sign in — CyberKavach',
        ], 'auth');
    }

    public function register(Request $request): void
    {
        $data = $request->json() ?? $request->all();
        $name = trim((string)($data['name'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $password = $data['password'] ?? null;
        $otpCode = trim((string)($data['otp_code'] ?? ''));

        if (!$name || !$email || !$password || $otpCode === '') {
            Response::json(['error' => 'Missing fields'], 422);
        }

        if (!$this->isInstitutionalEmail($email)) {
            Response::json(['error' => 'Use your institutional email address'], 422);
        }

        // check exists
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            Response::json(['error' => 'Email already registered'], 409);
        }

        $stmt = $this->db->prepare('SELECT * FROM auth_otps WHERE email = :email AND purpose = "registration" AND consumed_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
        $stmt->execute([':email' => $email]);
        $otp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$otp) {
            Response::json(['error' => 'Verification code not found or expired'], 404);
        }

        if ((int)$otp['attempts'] >= (int)($this->config['otp']['max_attempts'] ?? 3)) {
            Response::json(['error' => 'Too many attempts'], 429);
        }

        if (!hash_equals((string)$otp['code_hash'], hash('sha256', $otpCode))) {
            $upd = $this->db->prepare('UPDATE auth_otps SET attempts = attempts + 1 WHERE id = :id');
            $upd->execute([':id' => $otp['id']]);
            Response::json(['error' => 'Invalid verification code'], 401);
        }

        $ulid = $this->generateUlid();
        $passHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $ins = $this->db->prepare('INSERT INTO users (ulid, name, email, password_hash, created_at, updated_at) VALUES (:ulid, :name, :email, :ph, NOW(), NOW())');
        $ins->execute([':ulid' => $ulid, ':name' => $name, ':email' => $email, ':ph' => $passHash]);
        $userId = (int)$this->db->lastInsertId();

        $upd = $this->db->prepare('UPDATE auth_otps SET attempts = attempts + 1, consumed_at = NOW() WHERE id = :id');
        $upd->execute([':id' => $otp['id']]);

        Audit::log('register', 'auth', 'user', (string)$userId, null, ['email' => $email], $userId);

        // auto-login
        $this->auth->attempt($email, $password);
        $user = $this->auth->user();

        Response::json(['status' => 'created', 'user' => $user], 201);
    }

    public function logout(Request $request): void
    {
        $user = $this->auth->user();
        $userId = $user['id'] ?? null;
        $this->auth->logout();
        Audit::log('logout', 'auth', 'user', (string)$userId, null, null, $userId ? (int)$userId : null);
        Response::json(['status' => 'ok']);
    }

    public function requestOtp(Request $request): void
    {
        $data = $request->json() ?? $request->all();
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $purpose = (string)($data['purpose'] ?? 'registration');

        if (!$email || !in_array($purpose, ['registration', 'password_reset'], true)) {
            Response::json(['error' => 'Invalid request'], 422);
        }

        if ($purpose === 'registration' && !$this->isInstitutionalEmail($email)) {
            Response::json(['error' => 'Use your institutional email address'], 422);
        }

        $userId = null;
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $existingUser = $stmt->fetchColumn();

        if ($purpose === 'registration' && $existingUser) {
            Response::json(['error' => 'Email already registered'], 409);
        }

        if ($purpose === 'password_reset' && !$existingUser) {
            Response::json(['error' => 'Account not found'], 404);
        }

        if ($existingUser) {
            $userId = (int)$existingUser;
        }

        $code = (string)random_int(100000, 999999);
        $codeHash = hash('sha256', $code);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int)($this->config['otp']['expires_in_minutes'] ?? 10) . ' minutes'));

        $ins = $this->db->prepare('INSERT INTO auth_otps (user_id, email, purpose, code_hash, attempts, expires_at, created_at, ip_address) VALUES (:user_id, :email, :purpose, :code_hash, 0, :expires_at, NOW(), :ip)');
        $ins->execute([
            ':user_id' => $userId,
            ':email' => $email,
            ':purpose' => $purpose,
            ':code_hash' => $codeHash,
            ':expires_at' => $expiresAt,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $subject = $purpose === 'password_reset' ? 'Your CyberKavach password reset code' : 'Your CyberKavach verification code';
        $body = '<p>Your one-time code is <strong>' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong>.</p><p>This code expires in ' . (int)($this->config['otp']['expires_in_minutes'] ?? 10) . ' minutes.</p>';
        $this->mailer->send($email, '', $subject, $body);

        Audit::log('otp.request', 'auth', 'auth_otps', null, null, ['email' => $email, 'purpose' => $purpose], $userId);

        Response::json(['status' => 'otp_sent']);
    }

    public function verifyOtp(Request $request): void
    {
        $data = $request->json() ?? $request->all();
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $purpose = (string)($data['purpose'] ?? 'registration');
        $code = trim((string)($data['code'] ?? ''));

        if (!$email || !$code || !in_array($purpose, ['registration', 'password_reset'], true)) {
            Response::json(['error' => 'Invalid request'], 422);
        }

        $stmt = $this->db->prepare('SELECT * FROM auth_otps WHERE email = :email AND purpose = :purpose AND consumed_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
        $stmt->execute([':email' => $email, ':purpose' => $purpose]);
        $otp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp) {
            Response::json(['error' => 'Code not found or expired'], 404);
        }

        if ((int)$otp['attempts'] >= (int)($this->config['otp']['max_attempts'] ?? 3)) {
            Response::json(['error' => 'Too many attempts'], 429);
        }

        $nextAttempts = (int)$otp['attempts'] + 1;
        if (!hash_equals((string)$otp['code_hash'], hash('sha256', $code))) {
            $upd = $this->db->prepare('UPDATE auth_otps SET attempts = :attempts WHERE id = :id');
            $upd->execute([':attempts' => $nextAttempts, ':id' => $otp['id']]);
            Response::json(['error' => 'Invalid code'], 401);
        }

        $consumedAt = date('Y-m-d H:i:s');
        $upd = $this->db->prepare('UPDATE auth_otps SET attempts = :attempts, consumed_at = :consumed_at WHERE id = :id');
        $upd->execute([':attempts' => $nextAttempts, ':consumed_at' => $consumedAt, ':id' => $otp['id']]);

        Audit::log('otp.verify', 'auth', 'auth_otps', (string)$otp['id'], null, ['email' => $email, 'purpose' => $purpose], $otp['user_id'] ? (int)$otp['user_id'] : null);

        Response::json(['status' => 'verified']);
    }

    public function resetPassword(Request $request): void
    {
        $data = $request->json() ?? $request->all();
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $code = trim((string)($data['code'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if (!$email || !$code || $password === '') {
            Response::json(['error' => 'Invalid request'], 422);
        }

        $stmt = $this->db->prepare('SELECT * FROM auth_otps WHERE email = :email AND purpose = "password_reset" AND consumed_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
        $stmt->execute([':email' => $email]);
        $otp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp) {
            Response::json(['error' => 'Code not found or expired'], 404);
        }

        if ((int)$otp['attempts'] >= (int)($this->config['otp']['max_attempts'] ?? 3)) {
            Response::json(['error' => 'Too many attempts'], 429);
        }

        if (!hash_equals((string)$otp['code_hash'], hash('sha256', $code))) {
            $upd = $this->db->prepare('UPDATE auth_otps SET attempts = attempts + 1 WHERE id = :id');
            $upd->execute([':id' => $otp['id']]);
            Response::json(['error' => 'Invalid code'], 401);
        }

        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $userId = (int)$stmt->fetchColumn();
        if ($userId <= 0) {
            Response::json(['error' => 'Account not found'], 404);
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $upd = $this->db->prepare('UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id');
        $upd->execute([':password_hash' => $passwordHash, ':id' => $userId]);

        $upd = $this->db->prepare('UPDATE auth_otps SET attempts = attempts + 1, consumed_at = NOW() WHERE id = :id');
        $upd->execute([':id' => $otp['id']]);

        Audit::log('password.reset', 'auth', 'user', (string)$userId, null, ['email' => $email], $userId);

        Response::json(['status' => 'password_updated']);
    }

    public function me(Request $request): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }
        $user = $this->auth->user();
        Response::json(['user' => $user]);
    }

    public function dashboard(Request $request, string $role = 'dashboard'): void
    {
        if (!$this->auth->check()) {
            Response::redirect('/login');
        }

        $user = $this->auth->user();
        $currentRole = $this->auth->currentRoleSlug($user ? (int)$user['id'] : null) ?? $role;

        View::render('dashboard/home', [
            'title' => 'Dashboard — CyberKavach',
            'user' => $user,
            'role' => $currentRole,
        ], 'dashboard');
    }

    private function generateUlid(): string
    {
        // simple ULID-like generator (time + random)
        $time = microtime(true);
        $rand = bin2hex(random_bytes(10));
        $base = sprintf('%0.0f', $time * 1000) . $rand;
        return substr($base, 0, 26);
    }

    private function isInstitutionalEmail(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }

        $domain = strtolower($parts[1]);
        $allowedDomains = $this->config['institutional_email_domains'] ?? [];
        if (empty($allowedDomains)) {
            return false;
        }

        foreach ($allowedDomains as $allowedDomain) {
            $allowedDomain = strtolower(trim((string)$allowedDomain));
            if ($allowedDomain === '') {
                continue;
            }

            if ($domain === $allowedDomain || str_ends_with($domain, '.' . $allowedDomain)) {
                return true;
            }
        }

        return false;
    }
}
