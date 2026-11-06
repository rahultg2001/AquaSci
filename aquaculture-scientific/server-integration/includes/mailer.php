<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

function send_portal_email(string $to, string $toName, string $subject, string $body, array $attachments = []): void
{
    $src = dirname(__DIR__) . '/PHPMailer/src/';
    foreach (['Exception.php', 'PHPMailer.php', 'SMTP.php'] as $required) {
        if (!is_file($src . $required)) {
            throw new RuntimeException('PHPMailer is missing. Copy Exception.php, PHPMailer.php and SMTP.php into PHPMailer/src/.');
        }
        require_once $src . $required;
    }
    $cfg = app_config()['mail'] ?? [];
    foreach (['smtp_host', 'smtp_username', 'smtp_password', 'from_email', 'to_email'] as $required) {
        if (empty($cfg[$required])) {
            throw new RuntimeException('Email settings are incomplete in private/app-config.php.');
        }
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = (string)$cfg['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = (string)$cfg['smtp_username'];
    $mail->Password = (string)$cfg['smtp_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = (int)($cfg['smtp_port'] ?? 465);
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 20;
    $mail->setFrom((string)$cfg['from_email'], (string)($cfg['from_name'] ?? 'Aquaculture Scientific'));
    $mail->addAddress($to, $toName);
    $mail->Subject = preg_replace('/[\r\n]+/', ' ', $subject) ?? 'Aquaculture Scientific notification';
    $mail->Body = $body;
    foreach ($attachments as $attachment) {
        if (!empty($attachment['path']) && is_file($attachment['path'])) {
            $mail->addAttachment($attachment['path'], (string)($attachment['name'] ?? basename($attachment['path'])));
        }
    }
    $mail->send();
}
