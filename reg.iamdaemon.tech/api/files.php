<?php
require_once __DIR__ . '/../config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$username = $_SESSION['username'];
$userDir = "/var/www/users/$username";
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // === ПОЛУЧИТЬ СПИСОК ФАЙЛОВ ===
    if ($action === 'list') {
        $files = [];
        if (is_dir($userDir)) {
            foreach (scandir($userDir) as $file) {
                if ($file !== '.' && $file !== '..' && $file !== '.htaccess') {
                    $path = "$userDir/$file";
                    $files[] = [
                        'name' => $file,
                        'size' => is_file($path) ? round(filesize($path) / 1024, 1) . ' KB' : 'DIR',
                        'is_dir' => is_dir($path),
                        'editable' => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['html', 'htm', 'css', 'js', 'json', 'txt', 'md', 'xml', 'php'])
                    ];
                }
            }
        }
        echo json_encode(['success' => true, 'files' => $files]);
        exit;
    }

    // === ЧТЕНИЕ ФАЙЛА (для редактора) ===
    if ($action === 'edit') {
        $filename = $_GET['file'] ?? '';
        if (!$filename) throw new Exception("Не указан файл");
        
        // Проверка безопасности имени
        if (!preg_match('/^[a-z0-9._-]+$/i', $filename)) {
            throw new Exception("Некорректное имя файла");
        }
        
        $filepath = "$userDir/$filename";
        $realpath = realpath($filepath);
        
        // Проверяем что файл существует и находится в правильной директории
        if ($realpath === false || strpos($realpath, $userDir) !== 0 || !is_file($realpath)) {
            throw new Exception("Файл не найден");
        }
        
        // Проверяем можно ли редактировать
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = ['html', 'htm', 'css', 'js', 'json', 'txt', 'md', 'xml', 'php'];
        if (!in_array($ext, $allowed)) {
            throw new Exception("Этот тип файлов нельзя редактировать");
        }
        
        $content = file_get_contents($realpath);
        echo json_encode(['success' => true, 'content' => $content, 'filename' => $filename]);
        exit;
    }

    // === СОХРАНЕНИЕ ФАЙЛА ===
    if ($action === 'save') {
        $filename = $_POST['file'] ?? '';
        $content = $_POST['content'] ?? '';
        
        if (!$filename) throw new Exception("Не указан файл");
        if (!preg_match('/^[a-z0-9._-]+$/i', $filename)) {
            throw new Exception("Некорректное имя файла");
        }
        
        $filepath = "$userDir/$filename";
        $realpath = realpath($filepath);
        
        if ($realpath === false || strpos($realpath, $userDir) !== 0 || !is_file($realpath)) {
            throw new Exception("Файл не найден");
        }
        
        if (file_put_contents($realpath, $content) === false) {
            throw new Exception("Ошибка записи файла");
        }
        
        echo json_encode(['success' => true, 'message' => 'Файл сохранен']);
        exit;
    }

    // === ПЕРЕИМЕНОВАНИЕ ФАЙЛА ===
    if ($action === 'rename') {
        $oldName = $_POST['old_name'] ?? '';
        $newName = $_POST['new_name'] ?? '';
        
        if (!$oldName || !$newName) throw new Exception("Укажите старое и новое имя");
        if (!preg_match('/^[a-z0-9._-]+$/i', $oldName) || !preg_match('/^[a-z0-9._-]+$/i', $newName)) {
            throw new Exception("Некорректное имя файла");
        }
        
        $oldPath = "$userDir/$oldName";
        $newPath = "$userDir/$newName";
        $realOldPath = realpath($oldPath);
        
        if ($realOldPath === false || strpos($realOldPath, $userDir) !== 0 || !is_file($realOldPath)) {
            throw new Exception("Файл не найден");
        }
        
        if (file_exists($newPath)) {
            throw new Exception("Файл с таким именем уже существует");
        }
        
        if (!rename($realOldPath, $newPath)) {
            throw new Exception("Ошибка переименования");
        }
        
        chmod($newPath, 0644);
        echo json_encode(['success' => true, 'message' => 'Файл переименован']);
        exit;
    }

    // === УДАЛЕНИЕ ФАЙЛА ===
    if ($action === 'delete') {
        $filename = $_POST['file'] ?? '';
        if (!$filename) throw new Exception("Не указан файл");
        if (!preg_match('/^[a-z0-9._-]+$/i', $filename)) {
            throw new Exception("Некорректное имя файла");
        }
        
        $filepath = "$userDir/$filename";
        $realpath = realpath($filepath);
        
        if ($realpath === false || strpos($realpath, $userDir) !== 0) {
            throw new Exception("Файл не найден");
        }
        
        if (is_dir($realpath)) {
            // Удаление директории
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($realpath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) rmdir($file->getPathname());
                else unlink($file->getPathname());
            }
            rmdir($realpath);
        } else {
            unlink($realpath);
        }
        
        echo json_encode(['success' => true, 'message' => 'Файл удален']);
        exit;
    }

    throw new Exception("Неизвестное действие: " . $action);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>