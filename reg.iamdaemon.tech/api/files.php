<?php
require_once __DIR__ . '/../config.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

$username = $_SESSION['username'];
$userDir = getUserDir($username);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function resolveUserFile($userDir, $filename, $mustExist = true) {
    if (!isSafeFilename($filename)) {
        throw new Exception("Invalid filename");
    }

    $base = realpath($userDir);
    if (!$base) {
        throw new Exception("User directory not found");
    }

    $path = $userDir . '/' . $filename;
    if ($mustExist) {
        $real = realpath($path);
        if (!$real || strpos($real, $base) !== 0) {
            throw new Exception("File not found");
        }
        return $real;
    }

    $parent = realpath(dirname($path));
    if (!$parent || strpos($parent, $base) !== 0) {
        throw new Exception("Invalid path");
    }
    return $path;
}

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
                    'editable' => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), getEditableExtensions(), true)
                ];
            }
        }
        echo json_encode(['success' => true, 'files' => $files]);
        exit;
    }

    if ($action === 'edit') {
        $f = $_GET['file'] ?? '';
        $path = resolveUserFile($userDir, $f);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!is_file($path) || !in_array($ext, getEditableExtensions(), true)) throw new Exception("Cannot edit this file type");
        echo json_encode(['success' => true, 'content' => file_get_contents($path), 'filename' => basename($f)]);
        exit;
    }

    if ($action === 'save') {
        $f = $_POST['file'] ?? '';
        $path = resolveUserFile($userDir, $f);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!is_file($path) || !in_array($ext, getEditableExtensions(), true)) throw new Exception("Cannot save this file type");
        if (file_put_contents($path, $_POST['content'] ?? '') === false) throw new Exception("Write error");
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'rename') {
        $old = $_POST['old_name'] ?? '';
        $new = $_POST['new_name'] ?? '';
        if (!$old || !$new) throw new Exception("Empty name");
        if ($old === $new) throw new Exception("Same name");
        
        $oldPath = resolveUserFile($userDir, $old);
        $newPath = resolveUserFile($userDir, $new, false);
        
        if (file_exists($newPath)) throw new Exception("File exists: $new");
        
        if (!rename($oldPath, $newPath)) throw new Exception("Rename failed");
        chmod($newPath, 0644);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete') {
        $f = $_POST['file'] ?? '';
        $path = resolveUserFile($userDir, $f);
        
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
