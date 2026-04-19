<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/send_mail.php'; // Подключаем нашу функцию

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$username = strtolower(trim($input['username'] ?? ''));
$email = strtolower(trim($input['email'] ?? ''));
$password = $input['password'] ?? '';

// 1. Валидация
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

// 2. Проверка занятости
$stmt = $db->prepare('SELECT id FROM users WHERE username = :u OR email = :e');
$stmt->bindValue(':u', $username);
$stmt->bindValue(':e', $email);
if ($stmt->execute()->fetchArray()) {
    http_response_code(409);
    echo json_encode(['error' => 'Ник или Email уже заняты']);
    exit;
}

// 3. Генерация кода (6 цифр)
$code = rand(100000, 999999);

// 4. Сохраняем в БД (verified = 0)
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare('INSERT INTO users (username, email, password_hash, code, verified) VALUES (:u, :e, :h, :c, 0)');
$stmt->bindValue(':u', $username);
$stmt->bindValue(':e', $email);
$stmt->bindValue(':h', $hash);
$stmt->bindValue(':c', $code);

if ($stmt->execute()) {
    // 5. Отправляем письмо
    $subject = "Твой код доступа к DAEMON";
    $htmlBody = "
        <h2>Привет, {$username}!</h2>
        <p>Спасибо за регистрацию на DAEMON.</p>
        <p>Твой код подтверждения:</p>
        <h1 style='color:#8b5cf6; font-size:32px;'>{$code}</h1>
        <p>Введи его на странице регистрации.</p>
    ";
    
    if (sendEmail($email, $subject, $htmlBody)) {
        echo json_encode(['success' => true, 'message' => 'Код отправлен на почту']);
    } else {
        // Если письмо не ушло, удаляем юзера из БД (откат)
        $db->exec("DELETE FROM users WHERE username = '$username'");
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка отправки письма. Попробуй позже.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных']);
}
?>