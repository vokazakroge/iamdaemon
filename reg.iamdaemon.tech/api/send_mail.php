<?php
// Подключаем PHPMailer
require __DIR__ . '/../lib/PHPMailer.php';
require __DIR__ . '/../lib/SMTP.php';
require __DIR__ . '/../lib/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Отправка письма через SMTP
 * @return bool|string true при успехе, текст ошибки при неудаче
 */
function sendEmail($to, $subject, $body) {
    // === Читаем .env ВРУЧНУЮ с правильным trim ===
    $envPath = __DIR__ . '/../.env';
    $env = [];
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                // ВАЖНО: trim и ключа, и значения!
                $env[trim($key)] = trim($value);
            }
        }
    }

    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = $env['SMTP_HOST'] ?? 'smtp.mail.ru';
        $mail->SMTPAuth   = true;
        // ВАЖНО: trim убирает скрытые пробелы/символы
        $mail->Username   = trim($env['SMTP_USER'] ?? '');
        $mail->Password   = trim($env['SMTP_PASS'] ?? '');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int)($env['SMTP_PORT'] ?? 465);
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 30;
        
        // Принудительно используем PLAIN (как в curl)
        $mail->SMTPAutoTLS = false;
        $mail->AuthType = 'PLAIN';
        
        // Отладка (поставь 2 для логов, 0 для продакшена)
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = 'error_log';
        
        $mail->setFrom($mail->Username, 'Daemon Service');
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // Отправляем
        if ($mail->send()) {
            return true;
        } else {
            return "SMTP send failed: " . $mail->ErrorInfo;
        }
        
    } catch (Exception $e) {
        // Возвращаем точную ошибку
        return "PHPMailer Exception: " . $e->getMessage();
    }
}
?>