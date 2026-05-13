<?php
// Включаем вывод ошибок для отладки (500 ошибка должна показать текст)
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// Файл для отладки
$log_file = '/tmp/ban_debug.log';
file_put_contents($log_file, "=== BAN REQUEST STARTED ===\n", FILE_APPEND);

try {
    // 1. Загружаем конфиг
    $config_path = '/var/www/reg.iamdaemon.tech/config.php';
    if (!file_exists($config_path)) {
        throw new Exception("Config not found");
    }
    require_once $config_path;
    file_put_contents($log_file, "Config loaded.\n", FILE_APPEND);

    // 2. Проверяем админа
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden (Not Admin)']);
        exit;
    }
    file_put_contents($log_file, "Admin check passed.\n", FILE_APPEND);

    // 3. Читаем данные
    $input = json_decode(file_get_contents('php://input'), true);
    $username = strtolower(trim($input['username'] ?? ''));
    $status = $input['status'] ?? 'active';

    file_put_contents($log_file, "Input: user=$username, status=$status\n", FILE_APPEND);

    if (!$username || !preg_match('/^[a-z0-9-]{3,20}$/', $username)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid username']);
        exit;
    }

    if (!in_array($status, ['active', 'banned'], true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        exit;
    }

    // Защита админа
    $admin_user = function_exists('getAdminUsername') ? getAdminUsername() : 'vokazakroge';
    if ($username === $admin_user) {
        http_response_code(403);
        echo json_encode(['error' => 'Cannot ban admin']);
        exit;
    }

    // 4. Обновляем БД
    $db = getDb();
    $stmt = $db->prepare('UPDATE users SET status = :s WHERE username = :u');
    $stmt->bindValue(':s', $status, SQLITE3_TEXT);
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);

    if (!$stmt->execute()) {
        throw new Exception("DB Update failed");
    }
    file_put_contents($log_file, "DB updated.\n", FILE_APPEND);

    // 5. Файлы
    $userDir = "/var/www/users/$username";
    $bannedDir = "/var/www/users_banned/$username";
    
    if ($status === 'banned') {
        if (is_dir($userDir)) {
            if (!is_dir(dirname($bannedDir))) mkdir(dirname($bannedDir), 0755, true);
            rename($userDir, $bannedDir);
        }
    } else {
        if (is_dir($bannedDir)) {
            if (!is_dir($userDir)) mkdir($userDir, 0755, true);
            rename($bannedDir, $userDir);
        }
    }

    echo json_encode(['success' => true]);
    file_put_contents($log_file, "SUCCESS.\n", FILE_APPEND);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    file_put_contents($log_file, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}
?>
