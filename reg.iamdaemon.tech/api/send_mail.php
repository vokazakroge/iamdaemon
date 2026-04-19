<?php
// Подключаем библиотеку из папки lib
require __DIR__ . '/../lib/PHPMailer.php';
require __DIR__ . '/../lib/SMTP.php';
require __DIR__ . '/../lib/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Отправка письма через SMTP
 * @param string $to      Кому
 * @param string $subject Тема
 * @param string $body    Текст (HTML)
 * @return bool
 */
function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // === НАСТРОЙКИ SMTP (ЗАПОЛНИ СВОИМИ ДАННЫМИ) ===
        $mail->isSMTP();
        $mail->Host       = 'smtp.mail.ru';          // Адрес SMTP сервера
        $mail->SMTPAuth   = true;
        $mail->Username   = 'social@iamdaemon.tech';   // Твоя почта
        $mail->Password   = 'Egor200640012';               // Твой пароль (или App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port       = 465;                       // Порт (465 для SSL, 587 для TLS)
        $mail->CharSet    = 'UTF-8';

        // От кого и кому
        $mail->setFrom('social@iamdaemon.tech', 'Daemon Service');
        $mail->addAddress($to);

        // Письмо
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Если ошибка, пишем её в лог сервера
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>