function checkUserStatus() {
    if (!isLoggedIn()) return;
    
    $username = $_SESSION['username'];
    $db = getDb();
    $stmt = $db->prepare('SELECT status FROM users WHERE username = :u');
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    if ($user && $user['status'] === 'banned') {
        // Удаляем сессию
        session_destroy();
        // Редирект на страницу с сообщением
        header('Location: https://reg.iamdaemon.tech/banned.php');
        exit;
    }
}