<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

$log_file = '/tmp/delete_debug.log';
file_put_contents($log_file, "=== DELETE REQUEST STARTED ===\n", FILE_APPEND);

try {
    require_once '/var/www/reg.iamdaemon.tech/config.php';
    file_put_contents($log_file, "Config loaded.\n", FILE_APPEND);

    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $username = strtolower(trim($input['username'] ?? ''));
    $userId = (int)($input['id'] ?? 0);

    file_put_contents($log_file, "Input: user=$username, id=$userId\n", FILE_APPEND);

    if (!$username || !$userId || !preg_match('/^[a-z0-9-]{3,20}$/', $username)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    $admin_user = function_exists('getAdminUsername') ? getAdminUsername() : 'vokazakroge';
    if ($username === $admin_user) {
        http_response_code(403);
        echo json_encode(['error' => 'Cannot delete admin']);
        exit;
    }

    $db = getDb();
    $stmt = $db->prepare('DELETE FROM users WHERE id = :id AND username = :u');
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $stmt->execute();
    file_put_contents($log_file, "DB deleted.\n", FILE_APPEND);

    // Удаление файлов
    $dirsToDelete = ["/var/www/users/$username", "/var/www/users_banned/$username"];
    foreach ($dirsToDelete as $dir) {
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                if ($file->isDir()) rmdir($file->getPathname());
                else unlink($file->getPathname());
            }
            rmdir($dir);
        }
    }
    file_put_contents($log_file, "Files deleted.\n", FILE_APPEND);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    file_put_contents($log_file, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}
?>
