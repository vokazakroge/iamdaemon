<?php
require __DIR__ . '/../lib/PHPMailer.php';
require __DIR__ . '/../lib/SMTP.php';
require __DIR__ . '/../lib/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function loadEnv($path) {
    if (!file_exists($path)) return false;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
    return true;
}
loadEnv(__DIR__ . '/../.env');

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.mail.ru';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $_ENV['SMTP_PORT'] ?? 465;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 30;
        
        // Отладочная информация
        $mail->SMTPDebug = 0; // 2 для включения отладки
        $mail->Debugoutput = 'error_log';
        
        $mail->setFrom($_ENV['SMTP_USER'], 'Daemon Service');
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // Пробуем отправить
        if (!$mail->send()) {
            return "Ошибка отправки: " . $mail->ErrorInfo;
        }
        
        return true;
    } catch (Exception $e) {
        return "PHPMailer Exception: " . $e->getMessage();
    }
}
?>