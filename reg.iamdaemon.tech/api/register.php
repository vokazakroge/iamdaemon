<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/send_mail.php';

    $input = json_decode(file_get_contents('php://input'), true);
    $username = strtolower(trim($input['username'] ?? ''));
    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';

    // Валидация
    if (!$username || !$email || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Заполни все поля']);
        exit;
    }

    if (!preg_match('/^[a-z0-9-]{3,20}$/', $username)) {
        http_response_code(400);
        echo json_encode(['error' => 'Некорректное имя (a-z, 0-9, -, 3-20 символов)']);
        exit;
    }

    $db = getDb();

    // Проверка на существующего (включая забаненных)
    $stmt = $db->prepare('SELECT id, status FROM users WHERE username = :u');
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $existingUser = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($existingUser) {
        if ($existingUser['status'] === 'banned') {
            http_response_code(403);
            echo json_encode(['error' => 'Это имя заблокировано']);
        } else {
            http_response_code(409);
            echo json_encode(['error' => 'Ник уже занят']);
        }
        exit;
    }

    // Проверка email
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :e');
    $stmt->bindValue(':e', $email, SQLITE3_TEXT);
    if ($stmt->execute()->fetchArray()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email уже занят']);
        exit;
    }

    $code = (string)rand(100000, 999999);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, code, verified) VALUES (:u, :e, :h, :c, 0)');
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $stmt->bindValue(':e', $email, SQLITE3_TEXT);
    $stmt->bindValue(':h', $hash, SQLITE3_TEXT);
    $stmt->bindValue(':c', $code, SQLITE3_TEXT);

    if (!$stmt->execute()) {
        throw new Exception('Ошибка записи в БД');
    }

    $subject = "Твой код доступа к DAEMON";
    $htmlBody = "<h2>Привет, {$username}!</h2><p>Код подтверждения: <b style='font-size:28px;letter-spacing:4px;'>{$code}</b></p>";
    
    $mailResult = sendEmail($email, $subject, $htmlBody);
    
    if ($mailResult === true) {
        echo json_encode(['success' => true, 'message' => 'Код отправлен на почту']);
    } else {
        // Не удаляем пользователя, даём шанс resend
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось отправить письмо: ' . $mailResult]);
    }

} catch (Exception $e) {
    error_log("REGISTER ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>