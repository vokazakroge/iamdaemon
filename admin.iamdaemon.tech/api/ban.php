<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '/var/www/reg.iamdaemon.tech/config.php';
    
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

    if ($username === getAdminUsername()) {
        http_response_code(403);
        echo json_encode(['error' => 'Cannot ban admin']);
        exit;
    }

    $db = getDb();
    $stmt = $db->prepare('UPDATE users SET status = :s WHERE username = :u');
    $stmt->bindValue(':s', $status, SQLITE3_TEXT);
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
        exit;
    }

    // Работа с папками
    $userDir = "/var/www/users/$username";
    $bannedDir = "/var/www/users_banned/$username";
    
    if ($status === 'banned') {
        // БАН
        if (is_dir($userDir)) {
            if (!is_dir(dirname($bannedDir))) {
                mkdir(dirname($bannedDir), 0755, true);
            }
            // Удаляем старую banned папку если есть
            if (is_dir($bannedDir)) {
                array_map('unlink', glob("$bannedDir/*"));
                rmdir($bannedDir);
            }
            rename($userDir, $bannedDir);
            error_log("BANNED: $username - moved $userDir to $bannedDir");
        }
    } else {
        // РАЗБАН
        if (is_dir($bannedDir)) {
            // Удаляем текущую users папку если есть
            if (is_dir($userDir)) {
                array_map('unlink', glob("$userDir/*"));
                rmdir($userDir);
            }
            rename($bannedDir, $userDir);
            error_log("UNBANNED: $username - moved $bannedDir to $userDir");
        }
        // Создаём папку если её нет
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>