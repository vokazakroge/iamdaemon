<?php
error_reporting(E_ALL);
ini_set('display_errors', 1); // Показываем ошибки пока
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/send_mail.php';

    $input = json_decode(file_get_contents('php://input'), true);
    $username = strtolower(trim($input['username'] ?? ''));
    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';

    if (!$username || !$email || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Заполни все поля']);
        exit;
    }

    if (!preg_match('/^[a-z0-9-]{3,20}$/', $username)) {
        http_response_code(400);
        echo json_encode(['error' => 'Некорректное имя']);
        exit;
    }

    $db = getDb();

    // Проверка занятости
    $stmt = $db->prepare('SELECT id, verified, status FROM users WHERE username = :u OR email = :e');
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $stmt->bindValue(':e', $email, SQLITE3_TEXT);
    $existing = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($existing) {
        if ($existing['username'] == $username) {
            // Если ник занят
            if ($existing['verified'] == 1) {
                http_response_code(409);
                echo json_encode(['error' => 'Этот ник уже занят кем-то другим']);
            } else {
                // Если ник занят, но не подтвержден — УДАЛЯЕМ и регистрируем заново
                $db->exec("DELETE FROM users WHERE username = '$username'");
            }
        } else {
            // Если занят Email (даже если ник другой)
            if ($existing['verified'] == 1) {
                http_response_code(409);
                echo json_encode(['error' => 'Этот Email уже используется']);
            }
        }
    }

    // Создаем юзера
    $code = (string)rand(100000, 999999);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, code, verified) VALUES (:u, :e, :h, :c, 0)');
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $stmt->bindValue(':e', $email, SQLITE3_TEXT);
    $stmt->bindValue(':h', $hash, SQLITE3_TEXT);
    $stmt->bindValue(':c', $code, SQLITE3_TEXT);

    if (!$stmt->execute()) {
        throw new Exception('DB Error');
    }

    $subject = "Твой код доступа к DAEMON";
    $htmlBody = "<h2>Привет, {$username}!</h2><p>Код: <b style='font-size:24px;'>{$code}</b></p>";
    
    $mailResult = sendEmail($email, $subject, $htmlBody);
    
    if ($mailResult === true) {
        echo json_encode(['success' => true, 'message' => 'Код отправлен']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка отправки письма: ' . $mailResult]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>