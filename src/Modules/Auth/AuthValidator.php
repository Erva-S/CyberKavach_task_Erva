<?php
namespace CyberKavach\Modules\Auth;

class AuthValidator
{
    public static function validateRegister(array $data, array $options = []): array
    {
        $errors = [];

        $name = trim((string)($data['name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $confirm = $data['password_confirmation'] ?? '';

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        } else {
            // optional institutional domain whitelist
            if (!empty($options['domains']) && is_array($options['domains'])) {
                $ok = false;
                foreach ($options['domains'] as $d) {
                    if (str_ends_with(strtolower($email), '@' . strtolower(ltrim($d, '@')))) {
                        $ok = true;
                        break;
                    }
                }
                if (!$ok) {
                    $errors['email'] = 'Email must be an institutional email.';
                }
            }
        }

        if (!is_string($password) || strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } else {
            if (!preg_match('/[A-Z]/', $password)) {
                $errors['password'] = 'Password must include at least one uppercase letter.';
            }
            if (!preg_match('/[a-z]/', $password)) {
                $errors['password'] = 'Password must include at least one lowercase letter.';
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors['password'] = 'Password must include at least one digit.';
            }
        }

        if ($confirm !== '' && $password !== $confirm) {
            $errors['password_confirmation'] = 'Password confirmation does not match.';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    public static function validateLogin(array $data): array
    {
        $errors = [];
        $email = trim((string)($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        }
        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    public static function sanitizeRegisterInput(array $data): array
    {
        return [
            'name' => htmlspecialchars(trim((string)($data['name'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'email' => strtolower(trim((string)($data['email'] ?? ''))),
        ];
    }
}
