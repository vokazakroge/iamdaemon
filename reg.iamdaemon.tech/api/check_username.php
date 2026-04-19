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
$stmt = $db->prepare('SELECT id, status FROM users WHERE username = :u');
$stmt->bindValue(':u', $username, SQLITE3_TEXT);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if ($user) {
    // Если пользователь забанен — тоже считаем что имя занято
    echo json_encode(['available' => false, 'reason' => $user['status'] === 'banned' ? 'banned' : 'taken']);
} else {
    echo json_encode(['available' => true]);
}
?>