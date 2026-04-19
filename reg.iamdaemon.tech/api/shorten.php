<?php
require_once __DIR__ . '/../config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$db = getDb();
$username = $_SESSION['username'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Создаем таблицу если нет
$db->exec("CREATE TABLE IF NOT EXISTS short_urls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    short_code TEXT UNIQUE NOT NULL,
    long_url TEXT NOT NULL,
    clicks INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

try {
    // === СОЗДАТЬ КОРОТКУЮ ССЫЛКУ ===
    if ($action === 'create') {
        $longUrl = trim($_POST['long_url'] ?? '');
        
        if (!$longUrl) throw new Exception("Введите URL");
        
        // Добавляем https:// если нет
        if (!preg_match('~^https?://~i', $longUrl)) {
            $longUrl = 'https://' . $longUrl;
        }
        
        if (!filter_var($longUrl, FILTER_VALIDATE_URL)) {
            throw new Exception("Некорректный URL");
        }

        // Получаем ID пользователя
        $stmt = $db->prepare('SELECT id FROM users WHERE username = :u');
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        if (!$user) throw new Exception("Пользователь не найден");

        // Генерируем уникальный код (6 символов)
        $shortCode = substr(bin2hex(random_bytes(3)), 0, 6);
        
        // Проверяем уникальность
        $attempts = 0;
        while ($attempts < 10) {
            $stmt = $db->prepare('SELECT id FROM short_urls WHERE short_code = :code');
            $stmt->bindValue(':code', $shortCode, SQLITE3_TEXT);
            if (!$stmt->execute()->fetchArray()) break;
            $shortCode = substr(bin2hex(random_bytes(3)), 0, 6);
            $attempts++;
        }
        
        if ($attempts >= 10) throw new Exception("Не удалось сгенерировать код");

        // Сохраняем в БД
        $stmt = $db->prepare('INSERT INTO short_urls (user_id, short_code, long_url) VALUES (:uid, :code, :url)');
        $stmt->bindValue(':uid', $user['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':code', $shortCode, SQLITE3_TEXT);
        $stmt->bindValue(':url', $longUrl, SQLITE3_TEXT);
        
        if (!$stmt->execute()) throw new Exception("Ошибка сохранения");

        $shortUrl = "https://{$username}.iamdaemon.tech/go/{$shortCode}";
        
        echo json_encode([
            'success' => true,
            'short_url' => $shortUrl,
            'short_code' => $shortCode,
            'long_url' => $longUrl
        ]);
        exit;
    }

    // === ПОЛУЧИТЬ СПИСОК ССЫЛОК ===
    if ($action === 'list') {
        $stmt = $db->prepare('SELECT id FROM users WHERE username = :u');
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        if (!$user) throw new Exception("Пользователь не найден");

        $stmt = $db->prepare('SELECT * FROM short_urls WHERE user_id = :uid ORDER BY created_at DESC');
        $stmt->bindValue(':uid', $user['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $urls = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $urls[] = [
                'id' => $row['id'],
                'short_code' => $row['short_code'],
                'short_url' => "https://{$username}.iamdaemon.tech/go/{$row['short_code']}",
                'long_url' => $row['long_url'],
                'clicks' => (int)$row['clicks'],
                'created_at' => $row['created_at']
            ];
        }

        echo json_encode(['success' => true, 'urls' => $urls]);
        exit;
    }

    // === УДАЛИТЬ ССЫЛКУ ===
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) throw new Exception("Неверный ID");
        
        $stmt = $db->prepare('SELECT id FROM users WHERE username = :u');
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        if (!$user) throw new Exception("Пользователь не найден");

        $stmt = $db->prepare('DELETE FROM short_urls WHERE id = :id AND user_id = :uid');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':uid', $user['id'], SQLITE3_INTEGER);
        $stmt->execute();

        echo json_encode(['success' => true]);
        exit;
    }

    throw new Exception("Неизвестное действие");

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>