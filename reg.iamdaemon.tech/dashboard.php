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
            $path = "$userDir/$file";
            $size = is_file($path) ? round(filesize($path) / 1024, 1) . ' KB' : 'DIR';
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $editable = in_array($ext, ['html', 'htm', 'css', 'js', 'json', 'txt', 'md', 'xml', 'svg', 'php']);
            $files[] = ['name' => $file, 'size' => $size, 'is_dir' => is_dir($path), 'editable' => $editable];
        }
    }
}
usort($files, function($a, $b) {
    return ($a['is_dir'] == $b['is_dir']) ? strcasecmp($a['name'], $b['name']) : ($a['is_dir'] ? -1 : 1);
});

// Данные профиля для настроек
$db = getDb();
$stmt = $db->prepare('SELECT username, email, avatar FROM users WHERE username = :u');
$stmt->bindValue(':u', $username, SQLITE3_TEXT);
$profile = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
$avatarUrl = $profile['avatar'] 
    ? "https://reg.iamdaemon.tech/avatars/{$profile['avatar']}" 
    : 'https://ui-avatars.com/api/?name='.urlencode($username).'&background=8b5cf6&color=fff';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DASHBOARD - DAEMON</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="https://iamdaemon.tech" class="logo">DAEMON</a>
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-chevron-left"></i></button>
        </div>
        
        <nav class="sidebar-nav">
            <!-- data-target указывает ID секции -->
            <a href="#" class="nav-item active" data-target="section-files">
                <i class="fas fa-folder-open"></i><span>Файлы</span>
            </a>
            <a href="#" class="nav-item" data-target="section-shortener">
                <i class="fas fa-link"></i><span>Сокращатель</span>
            </a>
            <a href="#" class="nav-item" data-target="section-youtube">
                <i class="fab fa-youtube"></i><span>YouTube</span><span class="badge">NEW</span>
            </a>
            <a href="#" class="nav-item" data-target="section-settings">
                <i class="fas fa-cog"></i><span>Настройки</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><img src="<?= $avatarUrl ?>" alt="Avatar"></div>
                <div class="user-details">
                    <div class="username"><?= htmlspecialchars($username) ?></div>
                    <a href="https://reg.iamdaemon.tech/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content" id="mainContent">
        <header class="top-bar">
            <button class="menu-btn" id="menuBtn"><i class="fas fa-bars"></i></button>
            <div class="site-preview">
                <i class="fas fa-globe"></i>
                <span>https://<?= htmlspecialchars($username) ?>.iamdaemon.tech</span>
                <a href="https://<?= htmlspecialchars($username) ?>.iamdaemon.tech" target="_blank" class="btn-small">Открыть <i class="fas fa-external-link-alt"></i></a>
            </div>
        </header>

        <!-- ================== СЕКЦИЯ: ФАЙЛЫ ================== -->
        <section class="content-section active" id="section-files">
            <div class="section-header"><h1><i class="fas fa-folder-open"></i> Файлы</h1></div>
            <div class="file-list" id="fileList">
                <?php if (empty($files)): ?>
                    <div class="empty-msg"><i class="fas fa-folder-open" style="font-size: 3rem; color: var(--muted); margin-bottom: 15px;"></i><p>Нет файлов. Загрузи что-нибудь!</p></div>
                <?php else: foreach ($files as $f): ?>
                <div class="file-item">
                    <div class="file-name-wrapper">
                        <span class="file-icon"><?php echo $f['is_dir'] ? '<i class="fas fa-folder"></i>' : '<i class="fas fa-file"></i>'; ?></span>
                        <span class="filename-text" data-name="<?php echo htmlspecialchars($f['name']); ?>"><?php echo htmlspecialchars($f['name']); ?></span>
                    </div>
                    <div class="file-actions">
                        <?php if (!$f['is_dir'] && $f['editable']): ?>
                            <button class="btn-icon edit" data-file="<?php echo htmlspecialchars($f['name']); ?>"><i class="fas fa-pen"></i></button>
                        <?php endif; ?>
                        <?php if (!$f['is_dir']): ?>
                            <button class="btn-icon delete" data-file="<?php echo htmlspecialchars($f['name']); ?>"><i class="fas fa-trash"></i></button>
                        <?php endif; ?>
                    </div>
                    <span class="file-size"><?php echo $f['size']; ?></span>
                </div>
                <?php endforeach; endif; ?>
            </div>
            <div class="upload-zone" id="dropZone">
                <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: var(--primary); margin-bottom: 15px;"></i>
                <p style="font-size: 1.2rem; margin-bottom: 10px;">Перетащи файлы сюда</p>
                <input type="file" id="fileInput" class="hidden" multiple>
                <button class="btn-primary" onclick="document.getElementById('fileInput').click()"><i class="fas fa-upload"></i> Выбрать файлы</button>
                <div class="status-msg" id="statusMsg"></div>
            </div>
        </section>

        <!-- ================== СЕКЦИЯ: СОКРАЩАТЕЛЬ ================== -->
        <section class="content-section" id="section-shortener">
            <div class="section-header"><h1><i class="fas fa-link"></i> Сокращатель ссылок</h1></div>
            <div class="card">
                <h2>Создать короткую ссылку</h2>
                <div class="input-group">
                    <input type="text" id="shortLongUrl" placeholder="Вставь длинную ссылку (https://...)">
                    <button class="btn-primary" id="btnCreateLink">Сократить</button>
                </div>
                <div id="shortResult" class="result" style="display:none; margin-top:20px; padding:15px; background:rgba(16,185,129,0.1); border:1px solid #10b981; border-radius:8px;">
                    <strong style="color:#10b981">Готово!</strong> 
                    <a id="shortLinkOut" href="#" target="_blank" style="color:var(--primary); word-break:break-all;"></a>
                </div>
            </div>
            <div class="card">
                <h2>Твои ссылки</h2>
                <div id="urlsList" class="urls-list"><div class="empty-msg">Загрузка...</div></div>
            </div>
        </section>

        <!-- ================== СЕКЦИЯ: YOUTUBE ================== -->
        <section class="content-section" id="section-youtube">
            <div class="section-header"><h1><i class="fab fa-youtube"></i> Скачать с YouTube</h1></div>
            <div class="card" style="max-width: 600px; margin: 0 auto; text-align: center;">
                <i class="fas fa-tools" style="font-size: 4rem; color: var(--primary); margin-bottom: 20px;"></i>
                <h2>Функция в разработке</h2>
                <p style="color: var(--muted);">Скоро здесь появится возможность скачивать видео с YouTube.</p>
            </div>
        </section>

        <!-- ================== СЕКЦИЯ: НАСТРОЙКИ ================== -->
        <section class="content-section" id="section-settings">
            <div class="section-header"><h1><i class="fas fa-cog"></i> Настройки</h1></div>
            
            <div class="card">
                <h2>Аватар</h2>
                <div class="avatar-section" style="display:flex; align-items:center; gap:20px;">
                    <img id="settingsAvatar" src="<?= $avatarUrl ?>" style="width:100px; height:100px; border-radius:50%; border:2px solid var(--primary);">
                    <div>
                        <input type="file" id="avatarInput" accept="image/*" style="display:none">
                        <button class="btn-primary" onclick="document.getElementById('avatarInput').click()">Загрузить</button>
                        <div id="avatarMsg" class="status-msg"></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Сменить пароль</h2>
                <input type="password" id="currPass" placeholder="Текущий пароль" style="width:100%; padding:12px; margin-bottom:10px; background:var(--card); border:1px solid var(--border); color:var(--text); border-radius:8px;">
                <input type="password" id="newPass" placeholder="Новый пароль" style="width:100%; padding:12px; margin-bottom:10px; background:var(--card); border:1px solid var(--border); color:var(--text); border-radius:8px;">
                <button class="btn-primary" id="btnChangePass">Обновить</button>
                <div id="passMsg" class="status-msg"></div>
            </div>

            <div class="card" style="border-color: var(--danger);">
                <h2 style="color: var(--danger);">Удалить аккаунт</h2>
                <p style="margin-bottom:15px; color:var(--muted);">Это действие необратимо.</p>
                <button class="btn-danger" id="btnRequestDelete" style="background:var(--danger); color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer;">Удалить</button>
                <div id="deleteStep2" style="display:none; margin-top:15px;">
                    <input type="text" id="deleteCode" placeholder="Код из письма" style="padding:10px; border-radius:8px; border:1px solid var(--border); background:var(--card); color:var(--text);">
                    <button class="btn-danger" id="btnConfirmDelete">Подтвердить</button>
                </div>
                <div id="deleteMsg" class="status-msg"></div>
            </div>
        </section>
    </main>

    <!-- EDITOR MODAL -->
    <div class="modal" id="editorModal">
        <div class="modal-content">
            <div class="modal-header"><span class="modal-title" id="editorTitle">Editing</span><button class="modal-close" id="closeModal">×</button></div>
            <textarea id="codeEditor"></textarea>
            <div class="modal-footer"><button class="modal-btn cancel" id="cancelEdit">Cancel</button><button class="modal-btn save" id="saveEdit">Save</button></div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="js/dashboard.js" defer></script>
</body>
</html>