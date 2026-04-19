<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/send_mail.php';

    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Не авторизован']);
        exit;
    }

    $db = getDb();
    $currentUser = $_SESSION['username'];
    $action = $_POST['action'] ?? '';

    // === 1. СМЕНА ПАРОЛЯ ===
    if ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';

        if (!$currentPass || !$newPass) throw new Exception("Заполни все поля");
        if (strlen($newPass) < 6) throw new Exception("Пароль минимум 6 символов");

        $stmt = $db->prepare('SELECT password_hash FROM users WHERE username = :u');
        $stmt->bindValue(':u', $currentUser, SQLITE3_TEXT);
        $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!password_verify($currentPass, $user['password_hash'])) {
            throw new Exception("Неверный текущий пароль");
        }

        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE users SET password_hash = :h WHERE username = :u');
        $stmt->bindValue(':h', $hash, SQLITE3_TEXT);
        $stmt->bindValue(':u', $currentUser, SQLITE3_TEXT);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Пароль изменен']);
    }

    // === 2. СМЕНА ПОЧТЫ ===
    elseif ($action === 'change_email') {
        $newEmail = strtolower(trim($_POST['new_email'] ?? ''));
        $currentPass = $_POST['password'] ?? '';

        if (!$newEmail || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) throw new Exception("Некорректный Email");
        if (!$currentPass) throw new Exception("Введи пароль для подтверждения");

        $stmt = $db->prepare('SELECT password_hash FROM users WHERE username = :u');
        $stmt->bindValue(':u', $currentUser, SQLITE3_TEXT);
        $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!password_verify($currentPass, $user['password_hash'])) {
            throw new Exception("Неверный пароль");
        }

        $stmt = $db->prepare('SELECT id FROM users WHERE email = :e AND username != :u');
        $stmt->bindValue(':e', $newEmail, SQLITE3_TEXT);
        $stmt->bindValue(':u', $currentUser, SQLITE3_TEXT);
        if ($stmt->execute()->fetchArray()) throw new Exception("Этот Email уже занят");

        $stmt = $db->prepare('UPDATE users SET email = :e WHERE username = :u');
        $stmt->bindValue(':e', $newEmail, SQLITE3_TEXT);
        $stmt->bindValue(':u', $currentUser, SQLITE3_TEXT);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Email изменен']);
    }

    // === 3. ЗАГРУЗКА АВАТАРКИ (ИСПРАВЛЕНО) ===
    elseif ($action === 'upload_avatar') {
        if (!isset($_FILES['avatar'])) throw new Exception("Нет файла");

        $file = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowedTypes)) throw new Exception("Только картинки (JPG, PNG, WEBP)");
        if ($file['size'] > $maxSize) throw new Exception("Файл слишком большой (макс 2MB)");

        // Генерируем имя файла: username.ext
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $currentUser . '.' . $ext;
        
        // ПУТЬ ИЗМЕНИЛИ: теперь в папке avatars, а не в users
        $uploadDir = __DIR__ . '/../avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        
        $destination = "$uploadDir/$filename";

        // Удаляем старые аватарки этого юзера (на случай смены формата)
        foreach (glob("$uploadDir/{$currentUser}.*") as $oldFile) {
            if (is_file($oldFile)) unlink($oldFile);
        }

        // Перемещаем файл
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Ошибка загрузки на сервер");
        }
        chmod($destination, 0644);

        // Обновляем БД (сохраняем просто имя файла)
        $stmt = $db->prepare('UPDATE users SET avatar = :a WHERE username = :u');
        $stmt->bindValue(':a', $filename, SQLITE3_TEXT);
        $stmt->bindValue(':u', $currentUser, SQLITE3_TEXT);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Аватарка обновлена', 'avatar' => $filename]);
    }

    // === 4. ЗАПРОС КОДА ДЛЯ УДАЛЕНИЯ ===
    elseif ($action === 'request_delete_code') {
        $code = (string)rand(100000, 999999);
        $_SESSION['delete_code'] = $code;

        $stmt = $db->prepare('SELECT email FROM users WHERE username = :u');
        $stmt->bindValue(':u', $currentUser, SQLITE3_TEXT);
        $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        $subject = "Код для удаления аккаунта DAEMON";
        $body = "<h2>Вы запросили удаление аккаунта.</h2><p>Ваш код: <b style='font-size:24px;'>$code</b></p>";
        sendEmail($user['email'], $subject, $body);

        echo json_encode(['success' => true, 'message' => 'Код отправлен на почту']);
    }

    // === 5. ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ ===
    elseif ($action === 'confirm_delete') {
        $inputCode = $_POST['code'] ?? '';
        
        if ($inputCode != ($_SESSION['delete_code'] ?? '')) {
            throw new Exception("Неверный код");
        }

        // 1. Удаляем файлы сайта
        $userDir = "/var/www/users/$currentUser";
        if (is_dir($userDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($userDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                if ($file->isDir()) rmdir($file->getPathname());
                else unlink($file->getPathname());
            }
            rmdir($userDir);
        }

        // 2. Удаляем аватарку
        $avatarDir = __DIR__ . '/../avatars';
        foreach (glob("$avatarDir/{$currentUser}.*") as $avatarFile) {
            if (is_file($avatarFile)) unlink($avatarFile);
        }

        // 3. Удаляем из БД
        $stmt = $db->prepare('DELETE FROM users WHERE username = :u');
        $stmt->bindValue(':u', $currentUser, SQLITE3_TEXT);
        $stmt->execute();

        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Аккаунт удален', 'redirect' => 'https://reg.iamdaemon.tech']);
    }

    else {
        throw new Exception("Неизвестное действие");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>