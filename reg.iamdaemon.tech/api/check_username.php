<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$username = strtolower(trim($input['username'] ?? ''));

if (!$username || !preg_match('/^[a-z0-9-]{3,20}$/', $username)) {
    echo json_encode(['available' => false, 'error' => 'Invalid username']);
    exit;
}

$db = getDb();

// Ищем пользователя
$stmt = $db->prepare('SELECT id, verified FROM users WHERE username = :u');
$stmt->bindValue(':u', $username, SQLITE3_TEXT);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if ($user) {
    // Если пользователь ЕСТЬ, проверяем статус
    if ($user['verified'] == 1) {
        // Аккаунт активен — ник точно занят
        echo json_encode(['available' => false, 'reason' => 'taken']);
    } else {
        // Аккаунт НЕ подтвержден (висит без дела)
        // АВТОМАТИЧЕСКИ УДАЛЯЕМ ЕГО, чтобы освободить ник
        $deleteStmt = $db->prepare('DELETE FROM users WHERE id = :id');
        $deleteStmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
        $deleteStmt->execute();
        
        // И говорим, что ник свободен
        echo json_encode(['available' => true, 'message' => 'Cleaned up unfinished registration']);
    }
} else {
    // Пользователя нет вообще
    echo json_encode(['available' => true]);
}
?>