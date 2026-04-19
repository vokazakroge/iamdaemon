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
    $userId = (int)($input['id'] ?? 0);

    if (!$username || !$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    if ($username === getAdminUsername()) {
        http_response_code(403);
        echo json_encode(['error' => 'Cannot delete admin']);
        exit;
    }

    $db = getDb();
    $stmt = $db->prepare('DELETE FROM users WHERE id = :id AND username = :u');
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
        exit;
    }

    // Удаляем папки (и из users, и из users_banned)
    $dirsToDelete = [
        "/var/www/users/$username",
        "/var/www/users_banned/$username"
    ];
    
    foreach ($dirsToDelete as $dir) {
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
            rmdir($dir);
            error_log("DELETED: $dir");
        }
    }

    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>