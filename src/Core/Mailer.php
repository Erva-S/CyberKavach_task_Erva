<?php
namespace CyberKavach\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Mailer
{
    private PHPMailer $mail;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/mail.php';
        $this->mail = new PHPMailer(true);
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = $config['host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $config['username'];
            $this->mail->Password = $config['password'];
            $this->mail->SMTPSecure = $config['smtp_secure'] ?: PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = (int)$config['port'];

            $this->mail->setFrom($config['from_email'], $config['from_name']);
            $this->mail->isHTML(true);
        } catch (\Throwable $e) {
            // swallow; send will throw if not configured
        }
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody = ''): bool
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($toEmail, $toName ?: '');
            $this->mail->Subject = $subject;
            $this->mail->Body = $htmlBody;
            $this->mail->AltBody = $altBody ?: strip_tags($htmlBody);
            return (bool)$this->mail->send();
        } catch (\Throwable $e) {
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }
}
