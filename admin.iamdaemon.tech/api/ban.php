<?php
// Отключаем вывод ошибок в HTML
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Гарантируем JSON-ответ
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '/var/www/reg.iamdaemon.tech/config.php';
    
    // Проверяем авторизацию
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $username = strtolower(trim($input['username'] ?? ''));
    $status = $input['status'] ?? 'active';

    if (!$username || !in_array($status, ['active', 'banned'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    // Нельзя забанить админа
    if ($username === getAdminUsername()) {
        http_response_code(403);
        echo json_encode(['error' => 'Cannot ban admin']);
        exit;
    }

    $db = getDb();
    $stmt = $db->prepare('UPDATE users SET status = :s WHERE username = :u');
    $stmt->bindValue(':s', $status, SQLITE3_TEXT);
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);

    if ($stmt->execute()) {
        // Если бан — перемещаем папку
        if ($status === 'banned') {
            $userDir = "/var/www/users/$username";
            $bannedDir = "/var/www/users_banned/$username";
            
            if (is_dir($userDir)) {
                if (!is_dir(dirname($bannedDir))) {
                    mkdir(dirname($bannedDir), 0755, true);
                }
                rename($userDir, $bannedDir);
            }
        } else {
            // Если разбан — возвращаем папку
            $userDir = "/var/www/users/$username";
            $bannedDir = "/var/www/users_banned/$username";
            
            if (is_dir($bannedDir) && !is_dir($userDir)) {
                rename($bannedDir, $userDir);
            }
        }
        
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>