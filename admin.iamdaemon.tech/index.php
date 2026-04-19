<?php
require_once '/var/www/reg.iamdaemon.tech/config.php';
requireAdmin();

$db = getDb();

// Получаем всех пользователей
$result = $db->query("SELECT id, username, email, verified, created_at, status FROM users ORDER BY created_at DESC");
$users = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $users[] = $row;
}

// Статистика
$totalUsers = count($users);
$verifiedUsers = 0;
$bannedUsers = 0;
foreach ($users as $u) {
    if ($u['verified']) $verifiedUsers++;
    if ($u['status'] === 'banned') $bannedUsers++;
}

// Получаем список всех поддоменов из папки users
$usersDir = '/var/www/users';
$subdomains = [];
if (is_dir($usersDir)) {
    $scan = scandir($usersDir);
    foreach ($scan as $item) {
        if ($item !== '.' && $item !== '..' && is_dir("$usersDir/$item")) {
            $files = scandir("$usersDir/$item");
            $fileCount = 0;
            $totalSize = 0;
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && is_file("$usersDir/$item/$file")) {
                    $fileCount++;
                    $totalSize += filesize("$usersDir/$item/$file");
                }
            }
            $subdomains[] = [
                'name' => $item,
                'files' => $fileCount,
                'size' => round($totalSize / 1024, 2)
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
    <title>ADMIN - DAEMON</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="container">
        <header>
            <span class="logo">🛡️ ADMIN PANEL</span>
            <div style="display: flex; align-items: center; gap: 12px">
                <span class="user-badge">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="https://reg.iamdaemon.tech/logout.php" class="logout">Logout</a>
            </div>
        </header>

        <h2>📊 Статистика</h2>
        <div class="stats">
            <div class="stat-card">
                <span class="stat-number"><?php echo $totalUsers; ?></span>
                <span class="stat-label">Пользователей</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $verifiedUsers; ?></span>
                <span class="stat-label">Активных</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $bannedUsers; ?></span>
                <span class="stat-label">Заблокировано</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo count($subdomains); ?></span>
                <span class="stat-label">Поддоменов</span>
            </div>
        </div>

        <h2>👥 Пользователи</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Verified</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTable">
                    <?php foreach ($users as $u): ?>
                    <tr data-id="<?php echo $u['id']; ?>">
                        <td><?php echo $u['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $u['verified'] ? 'success' : 'muted'; ?>">
                                <?php echo $u['verified'] ? '✓' : '✗'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($u['created_at'])); ?></td>
                        <td>
                            <span class="badge <?php echo $u['status'] === 'active' ? 'success' : ($u['status'] === 'banned' ? 'error' : 'muted'); ?>">
                                <?php echo htmlspecialchars($u['status']); ?>
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-action ban" 
                                    onclick="banUser('<?php echo htmlspecialchars($u['username']); ?>', '<?php echo $u['status']; ?>')"
                                    title="Ban/Unban">
                                <?php echo $u['status'] === 'active' ? '🔒' : '🔓'; ?>
                            </button>
                            <button class="btn-action delete" 
                                    onclick="deleteUser('<?php echo htmlspecialchars($u['username']); ?>', <?php echo $u['id']; ?>)"
                                    title="Delete user">
                                🗑️
                            </button>
                            <a href="https://<?php echo htmlspecialchars($u['username']); ?>.iamdaemon.tech" target="_blank" class="btn-action" title="Open site">
                                ↗️
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h2>🌐 Поддомены (файлы)</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Subdomain</th>
                        <th>Files</th>
                        <th>Size</th>
                        <th>URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subdomains as $sub): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($sub['name']); ?>.iamdaemon.tech</strong></td>
                        <td><?php echo $sub['files']; ?> files</td>
                        <td><?php echo $sub['size']; ?> KB</td>
                        <td><a href="https://<?php echo htmlspecialchars($sub['name']); ?>.iamdaemon.tech" target="_blank" class="link">Open ↗</a></td>
                        <td>
                            <button class="btn-action view-files" 
                                    onclick="viewFiles('<?php echo htmlspecialchars($sub['name']); ?>')"
                                    title="View files">
                                📁 Files
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal для просмотра файлов -->
    <div id="filesModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:1000;justify-content:center;align-items:center;">
        <div style="background:#12121a;border:1px solid #2a2a3a;border-radius:12px;padding:20px;width:90%;max-width:800px;max-height:80vh;overflow:auto;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 id="modalTitle" style="font-family:'Orbitron',sans-serif;margin:0;color:#fff;">Files</h3>
                <button id="closeModal" onclick="closeFilesModal()" style="background:transparent;border:none;color:#94a3b8;font-size:1.5rem;cursor:pointer;">×</button>
            </div>
            <div id="filesList"></div>
        </div>
    </div>

    <script src="js/admin.js" defer></script>
</body>
</html>