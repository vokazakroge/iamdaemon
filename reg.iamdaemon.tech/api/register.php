<?php
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
        echo json_encode(['error' => 'Некорректное имя (a-z, 0-9, -, 3-20 символов)']);
        exit;
    }

    $db = getDb();

    $stmt = $db->prepare('SELECT id FROM users WHERE username = :u OR email = :e');
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $stmt->bindValue(':e', $email, SQLITE3_TEXT);
    if ($stmt->execute()->fetchArray()) {
        http_response_code(409);
        echo json_encode(['error' => 'Ник или Email уже заняты']);
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
        // Возвращаем точную ошибку SMTP на фронтенд
        http_response_code(500);
        echo json_encode(['error' => 'SMTP ошибка: ' . $mailResult]);
    }

} catch (Exception $e) {
    error_log("REGISTER ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>