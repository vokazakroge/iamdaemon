<?php
require_once __DIR__ . '/../config.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

$username = $_SESSION['username'];
$userDir = "/var/www/users/$username";
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($action === 'list') {
        $files = [];
        if (is_dir($userDir)) {
            foreach (scandir($userDir) as $f) {
                if ($f === '.' || $f === '..' || $f === '.htaccess') continue;
                $p = "$userDir/$f";
                $files[] = [
                    'name' => $f,
                    'size' => is_file($p) ? round(filesize($p)/1024,1).' KB' : 'DIR',
                    'is_dir' => is_dir($p),
                    'editable' => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['html','htm','css','js','json','txt','md','xml','php'])
                ];
            }
        }
        echo json_encode(['success' => true, 'files' => $files]);
        exit;
    }

    if ($action === 'edit') {
        $f = $_GET['file'] ?? '';
        $path = "$userDir/".basename($f);
        if (!is_file($path)) throw new Exception("File not found");
        echo json_encode(['success' => true, 'content' => file_get_contents($path), 'filename' => basename($f)]);
        exit;
    }

    if ($action === 'save') {
        $f = $_POST['file'] ?? '';
        $path = "$userDir/".basename($f);
        if (!is_file($path)) throw new Exception("File not found");
        if (file_put_contents($path, $_POST['content'] ?? '') === false) throw new Exception("Write error");
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'rename') {
        $old = basename($_POST['old_name'] ?? '');
        $new = basename($_POST['new_name'] ?? '');
        if (!$old || !$new) throw new Exception("Empty name");
        if ($old === $new) throw new Exception("Same name");
        
        $oldPath = "$userDir/$old";
        $newPath = "$userDir/$new";
        
        if (!file_exists($oldPath)) throw new Exception("File not found: $old");
        if (file_exists($newPath)) throw new Exception("File exists: $new");
        
        if (!rename($oldPath, $newPath)) throw new Exception("Rename failed");
        chmod($newPath, 0644);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete') {
        $f = basename($_POST['file'] ?? '');
        $path = "$userDir/$f";
        if (!file_exists($path)) throw new Exception("File not found");
        
        if (is_dir($path)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $file) $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            rmdir($path);
        } else {
            unlink($path);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    throw new Exception("Unknown action");
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>