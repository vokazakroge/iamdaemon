<?php
require_once __DIR__ . '/config.php';
requireLogin();
checkUserStatus();

$username = $_SESSION['username'];
$userDir = "/var/www/users/$username";
$files = [];

if (is_dir($userDir)) {
    foreach (scandir($userDir) as $file) {
        if ($file !== '.' && $file !== '..' && $file !== '.htaccess') {
            $size = is_file("$userDir/$file") ? round(filesize("$userDir/$file") / 1024, 1) . ' KB' : 'DIR';
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $editable = in_array($ext, ['html', 'htm', 'css', 'js', 'json', 'txt', 'md', 'xml', 'svg']);
            $files[] = [
                'name' => $file,
                'size' => $size,
                'is_dir' => is_dir("$userDir/$file"),
                'editable' => $editable
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DASHBOARD - DAEMON</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <span class="logo">DAEMON / DASHBOARD</span>
            <div style="display: flex; align-items: center; gap: 12px">
                <span class="user-badge">👤 <?php echo htmlspecialchars($username); ?></span>
                <a href="https://reg.iamdaemon.tech/logout.php" class="logout">Logout</a>
            </div>
        </header>

        <!-- КНОПКА ПЕРЕХОДА НА САЙТ -->
        <div style="background: rgba(139,92,246,0.1); border: 1px solid #8b5cf6; border-radius: 10px; padding: 14px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center">
            <div>
                <strong>🌐 Твой сайт</strong><br>
                <small style="color: #94a3b8">https://<?php echo $username; ?>.iamdaemon.tech</small>
            </div>
            <a href="https://<?php echo $username; ?>.iamdaemon.tech" target="_blank" class="upload-btn" style="margin:0; padding: 10px 20px">Открыть сайт ↗</a>
        </div>

        <h2>Files</h2>
        <div class="file-list" id="fileList">
            <?php if (empty($files)): ?>
                <div class="empty-msg">No files yet. Upload below.</div>
            <?php else: foreach ($files as $f): ?>
            <div class="file-item">
                <div class="file-name-wrapper">
                    <span class="file-icon">
                        <?php echo $f['is_dir'] ? '📁' : '📄'; ?>
                    </span>
                    <span class="filename-text" data-name="<?php echo htmlspecialchars($f['name']); ?>">
                        <?php echo htmlspecialchars($f['name']); ?>
                    </span>
                </div>
                <div class="file-actions">
                    <?php if (!$f['is_dir'] && $f['editable']): ?>
                        <button class="btn-icon edit" data-file="<?php echo htmlspecialchars($f['name']); ?>" title="Edit">✏️</button>
                    <?php endif; ?>
                    <?php if (!$f['is_dir']): ?>
                        <button class="btn-icon delete" data-file="<?php echo htmlspecialchars($f['name']); ?>" title="Delete">🗑️</button>
                    <?php endif; ?>
                </div>
                <span class="file-size"><?php echo $f['size']; ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="upload-zone" id="dropZone">
            <p style="font-size: 1.2rem">Drop files here</p>
            <small>Allowed: html, css, js, png, jpg, svg, json, txt, zip (max 20 MB)</small><br>
            <input type="file" id="fileInput" class="hidden" multiple accept=".html,.css,.js,.json,.txt,.xml,.png,.jpg,.jpeg,.gif,.svg,.ico,.pdf,.md,.zip">
            <button class="upload-btn" onclick="document.getElementById('fileInput').click()">Select Files</button>
            <div class="status-msg" id="statusMsg"></div>
        </div>
    </div>

    <div class="modal" id="editorModal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title" id="editorTitle">Editing</span>
                <button class="modal-close" id="closeModal">×</button>
            </div>
            <textarea id="codeEditor"></textarea>
            <div class="modal-footer">
                <button class="modal-btn cancel" id="cancelEdit">Cancel</button>
                <button class="modal-btn save" id="saveEdit">Save</button>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js" defer></script>
</body>
</html>