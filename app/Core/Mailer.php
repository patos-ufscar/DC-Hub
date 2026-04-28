<?php
declare(strict_types=1);

namespace App\Core;

final class Mailer
{
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->host      = $_ENV['SMTP_HOST'] ?? 'localhost';
        $this->port      = (int) ($_ENV['SMTP_PORT'] ?? 587);
        $this->user      = $_ENV['SMTP_USER'] ?? '';
        $this->pass      = $_ENV['SMTP_PASS'] ?? '';
        $this->fromEmail = $_ENV['SMTP_FROM'] ?? 'noreply@dchub.local';
        $this->fromName  = $_ENV['SMTP_FROM_NAME'] ?? 'DC Hub';
    }

    public function send(string $to, string $subject, string $htmlBody): bool
    {
        $vendorPath = dirname(__DIR__, 2) . '/vendor/phpmailer';

        if (!is_dir($vendorPath)) {
            error_log('PHPMailer not found at: ' . $vendorPath);
            return false;
        }

        require_once $vendorPath . '/src/Exception.php';
        require_once $vendorPath . '/src/PHPMailer.php';
        require_once $vendorPath . '/src/SMTP.php';

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->user;
            $mail->Password   = $this->pass;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->port;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;

            $mail->send();
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }
}
