<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$username = strtolower(trim($input['username'] ?? ''));
$code = trim($input['code'] ?? '');

$db = getDb();

// Ищем юзера
$stmt = $db->prepare('SELECT id, code, verified FROM users WHERE username = :u');
$stmt->bindValue(':u', $username);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'Пользователь не найден']);
    exit;
}

if ($user['verified']) {
    echo json_encode(['error' => 'Аккаунт уже активирован']);
    exit;
}

if ($user['code'] == $code) {
    // Код верный! Активируем
    $stmt = $db->prepare('UPDATE users SET verified = 1 WHERE id = :id');
    $stmt->bindValue(':id', $user['id']);
    $stmt->execute();

    // Создаем папку пользователя
    $userDir = "/var/www/users/$username";
    if (!is_dir($userDir)) mkdir($userDir, 0755, true);

    // Логиним пользователя
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $username;

    echo json_encode(['success' => true, 'redirect' => "https://$username.iamdaemon.tech"]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Неверный код']);
}
?>