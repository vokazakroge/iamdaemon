<?php
require_once __DIR__ . '/config.php';
requireLogin();

$db = getDb();
$stmt = $db->prepare('SELECT username, email, avatar FROM users WHERE username = :u');
$stmt->bindValue(':u', $_SESSION['username'], SQLITE3_TEXT);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

$avatarUrl = $user['avatar'] ? "https://{$_SESSION['username']}.iamdaemon.tech/{$user['avatar']}" : 'https://via.placeholder.com/150/8b5cf6/ffffff?text=User';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — DAEMON</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #0a0a0f; --card: #12121a; --primary: #8b5cf6; --text: #e2e8f0; --muted: #94a3b8; --border: #2a2a3a; --danger: #ef4444; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; padding: 20px; display: flex; justify-content: center; }
        .container { max-width: 800px; width: 100%; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .logo { font-family: 'Orbitron'; font-size: 1.5rem; font-weight: 700; color: var(--primary); text-decoration: none; }
        .back-btn { color: var(--muted); text-decoration: none; font-size: 0.9rem; }
        .back-btn:hover { color: var(--text); }
        
        h2 { font-family: 'Orbitron'; font-size: 1.3rem; margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 10px; }
        
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        
        .avatar-section { display: flex; align-items: center; gap: 20px; }
        .avatar-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
        
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 12px; margin: 8px 0 16px; background: rgba(139, 92, 246, 0.05);
            border: 1px solid var(--border); border-radius: 8px; color: var(--text); box-sizing: border-box;
        }
        input:focus { outline: none; border-color: var(--primary); }
        
        button {
            padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; transition: 0.2s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #7c3aed; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; }
        
        .msg { margin-top: 10px; padding: 10px; border-radius: 8px; font-size: 0.9rem; display: none; }
        .msg.success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981; }
        .msg.error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <a href="https://reg.iamdaemon.tech/dashboard" class="back-btn">← Назад в Dashboard</a>
            <a href="https://reg.iamdaemon.tech" class="logo">DAEMON</a>
        </header>

        <h1>Настройки профиля</h1>

        <!-- АВАТАР -->
        <div class="card">
            <h2>Аватар</h2>
            <div class="avatar-section">
                <img id="avatarPreview" src="<?= $avatarUrl ?>" class="avatar-img">
                <div>
                    <input type="file" id="avatarInput" accept="image/*" style="display:none">
                    <button class="btn-primary" onclick="document.getElementById('avatarInput').click()">Загрузить картинку</button>
                    <div id="avatarMsg" class="msg"></div>
                </div>
            </div>
        </div>

        <!-- ПАРОЛЬ -->
        <div class="card">
            <h2>Сменить пароль</h2>
            <input type="password" id="currPass" placeholder="Текущий пароль">
            <input type="password" id="newPass" placeholder="Новый пароль">
            <button class="btn-primary" onclick="changePassword()">Обновить пароль</button>
            <div id="passMsg" class="msg"></div>
        </div>

        <!-- ПОЧТА -->
        <div class="card">
            <h2>Сменить Email</h2>
            <p style="color:var(--muted); font-size:0.9rem;">Текущий: <?= htmlspecialchars($user['email']) ?></p>
            <input type="email" id="newEmail" placeholder="Новый Email">
            <input type="password" id="emailPass" placeholder="Пароль для подтверждения">
            <button class="btn-primary" onclick="changeEmail()">Обновить Email</button>
            <div id="emailMsg" class="msg"></div>
        </div>

        <!-- УДАЛЕНИЕ -->
        <div class="card" style="border-color: var(--danger);">
            <h2 style="color: var(--danger); border-bottom-color: var(--danger);">Опасная зона</h2>
            <p>Удаление аккаунта необратимо. Все файлы будут уничтожены.</p>
            <div id="deleteStep1">
                <button class="btn-danger" onclick="requestDelete()">Удалить аккаунт</button>
            </div>
            <div id="deleteStep2" style="display:none; margin-top:15px;">
                <p>Мы отправили код на вашу почту. Введите его для подтверждения:</p>
                <input type="text" id="deleteCode" placeholder="Код из письма" maxlength="6" style="width:150px; text-align:center; letter-spacing:5px; font-size:1.2rem;">
                <button class="btn-danger" onclick="confirmDelete()">Подтвердить удаление</button>
            </div>
            <div id="deleteMsg" class="msg"></div>
        </div>
    </div>

    <script>
        // --- Avatar ---
        document.getElementById('avatarInput').addEventListener('change', function() {
            const formData = new FormData();
            formData.append('avatar', this.files[0]);
            formData.append('action', 'upload_avatar');
            
            fetch('/api/profile.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                const msg = document.getElementById('avatarMsg');
                msg.style.display = 'block';
                if(d.success) {
                    msg.className = 'msg success';
                    msg.textContent = d.message;
                    document.getElementById('avatarPreview').src = `https://${'<?= $_SESSION['username'] ?>'}.iamdaemon.tech/${d.avatar}?t=${Date.now()}`;
                } else {
                    msg.className = 'msg error';
                    msg.textContent = d.error;
                }
            });
        });

        // --- Password ---
        function changePassword() {
            const curr = document.getElementById('currPass').value;
            const newP = document.getElementById('newPass').value;
            if(!curr || !newP) return alert("Заполни поля");

            const fd = new FormData();
            fd.append('action', 'change_password');
            fd.append('current_password', curr);
            fd.append('new_password', newP);

            fetch('/api/profile.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                const msg = document.getElementById('passMsg');
                msg.style.display = 'block';
                msg.className = d.success ? 'msg success' : 'msg error';
                msg.textContent = d.success ? d.message : d.error;
                if(d.success) { document.getElementById('currPass').value = ''; document.getElementById('newPass').value = ''; }
            });
        }

        // --- Email ---
        function changeEmail() {
            const newE = document.getElementById('newEmail').value;
            const pass = document.getElementById('emailPass').value;
            if(!newE || !pass) return alert("Заполни поля");

            const fd = new FormData();
            fd.append('action', 'change_email');
            fd.append('new_email', newE);
            fd.append('password', pass);

            fetch('/api/profile.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                const msg = document.getElementById('emailMsg');
                msg.style.display = 'block';
                msg.className = d.success ? 'msg success' : 'msg error';
                msg.textContent = d.success ? d.message : d.error;
                if(d.success) location.reload();
            });
        }

        // --- Delete ---
        function requestDelete() {
            if(!confirm("Точно удалить?")) return;
            const fd = new FormData();
            fd.append('action', 'request_delete_code');
            
            fetch('/api/profile.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    document.getElementById('deleteStep1').style.display = 'none';
                    document.getElementById('deleteStep2').style.display = 'block';
                } else {
                    alert(d.error);
                }
            });
        }

        function confirmDelete() {
            const code = document.getElementById('deleteCode').value;
            const fd = new FormData();
            fd.append('action', 'confirm_delete');
            fd.append('code', code);

            fetch('/api/profile.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    alert("Аккаунт удален. Перенаправление...");
                    window.location.href = d.redirect;
                } else {
                    const msg = document.getElementById('deleteMsg');
                    msg.style.display = 'block';
                    msg.className = 'msg error';
                    msg.textContent = d.error;
                }
            });
        }
    </script>
</body>
</html>