<?php
require_once __DIR__ . '/config.php';
requireLogin();
checkUserStatus(); // Проверка бана

$db = getDb();
$stmt = $db->prepare('SELECT username, email, avatar FROM users WHERE username = :u');
$stmt->bindValue(':u', $_SESSION['username'], SQLITE3_TEXT);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

// Формируем ссылку на аватар (с временем, чтобы кэш не мешал)
$avatarUrl = $user['avatar'] 
    ? "https://{$_SESSION['username']}.iamdaemon.tech/{$user['avatar']}?t=" . time() 
    : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']) . '&background=8b5cf6&color=fff';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — DAEMON</title>
    <!-- Подключаем шрифты -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --bg: #0a0a0f; 
            --card: #12121a; 
            --primary: #8b5cf6; 
            --primary-hover: #7c3aed;
            --text: #e2e8f0; 
            --muted: #94a3b8; 
            --border: #2a2a3a; 
            --danger: #ef4444; 
            --danger-bg: #451a1a;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--bg); 
            color: var(--text); 
            min-height: 100vh; 
            padding: 20px; 
            display: flex; 
            justify-content: center; 
        }

        .container { max-width: 700px; width: 100%; padding-top: 20px; }

        /* Header */
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .logo { font-family: 'Orbitron', sans-serif; font-size: 1.5rem; font-weight: 700; color: var(--primary); text-decoration: none; }
        .back-btn { color: var(--muted); text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 5px; transition: 0.2s; }
        .back-btn:hover { color: var(--text); }
        
        h1 { font-family: 'Orbitron', sans-serif; font-size: 1.8rem; margin-bottom: 30px; }
        h2 { font-family: 'Orbitron', sans-serif; font-size: 1.1rem; margin-bottom: 20px; color: var(--text); border-bottom: 1px solid var(--border); padding-bottom: 10px; }
        
        /* Cards */
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin-bottom: 24px; }
        
        /* Avatar Section */
        .avatar-section { display: flex; align-items: center; gap: 24px; }
        .avatar-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary); background: #1a1a24; }
        
        /* Inputs */
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: var(--muted); font-weight: 500; }
        
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 12px 16px; background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border); border-radius: 10px; color: var(--text); 
            font-family: 'Inter', sans-serif; font-size: 1rem; transition: 0.2s;
        }
        input:focus { outline: none; border-color: var(--primary); background: rgba(139, 92, 246, 0.05); }
        
        /* Fix для автозаполнения (убирает желтый фон) */
        input:-webkit-autofill, input:-webkit-autofill:hover, input:-webkit-autofill:focus, input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px var(--card) inset !important;
            -webkit-text-fill-color: var(--text) !important;
        }

        /* Buttons */
        button {
            padding: 12px 24px; border-radius: 10px; border: none; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; transition: 0.2s; font-size: 0.95rem;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
        
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; }

        /* Messages */
        .msg { margin-top: 12px; padding: 12px; border-radius: 8px; font-size: 0.9rem; display: none; animation: fadeIn 0.3s; }
        .msg.success { background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }
        .msg.error { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        /* Danger Zone */
        .danger-card { border-color: var(--danger); background: rgba(239, 68, 68, 0.02); }
        .danger-card h2 { color: var(--danger); border-bottom-color: var(--danger); }
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
            <div class="form-group">
                <input type="password" id="currPass" placeholder="Текущий пароль">
            </div>
            <div class="form-group">
                <input type="password" id="newPass" placeholder="Новый пароль">
            </div>
            <button class="btn-primary" onclick="changePassword()">Обновить пароль</button>
            <div id="passMsg" class="msg"></div>
        </div>

        <!-- ПОЧТА -->
        <div class="card">
            <h2>Сменить Email</h2>
            <p style="color:var(--muted); font-size:0.9rem; margin-bottom: 15px;">Текущий: <strong><?= htmlspecialchars($user['email']) ?></strong></p>
            <div class="form-group">
                <input type="email" id="newEmail" placeholder="Новый Email">
            </div>
            <div class="form-group">
                <input type="password" id="emailPass" placeholder="Пароль для подтверждения">
            </div>
            <button class="btn-primary" onclick="changeEmail()">Обновить Email</button>
            <div id="emailMsg" class="msg"></div>
        </div>

        <!-- УДАЛЕНИЕ -->
        <div class="card danger-card">
            <h2>Опасная зона</h2>
            <p style="margin-bottom: 15px; color: var(--muted);">Удаление аккаунта необратимо. Все файлы будут уничтожены.</p>
            
            <div id="deleteStep1">
                <button class="btn-danger" onclick="requestDelete()">Удалить аккаунт</button>
            </div>
            
            <div id="deleteStep2" style="display:none; margin-top:15px; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 10px;">
                <p style="margin-bottom: 10px;">Мы отправили код на вашу почту. Введите его:</p>
                <input type="text" id="deleteCode" placeholder="000000" maxlength="6" style="width:120px; text-align:center; letter-spacing:5px; font-size:1.2rem; margin-bottom: 10px;">
                <button class="btn-danger" onclick="confirmDelete()">Подтвердить удаление</button>
            </div>
            <div id="deleteMsg" class="msg"></div>
        </div>
    </div>

    <script>
        // --- Avatar ---
        document.getElementById('avatarInput').addEventListener('change', function() {
            if(this.files.length === 0) return;
            const formData = new FormData();
            formData.append('avatar', this.files[0]);
            formData.append('action', 'upload_avatar');
            
            const msg = document.getElementById('avatarMsg');
            msg.style.display = 'block';
            msg.textContent = 'Загрузка...';
            msg.className = 'msg'; // reset

            fetch('/api/profile.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    msg.className = 'msg success';
                    msg.textContent = d.message;
                    // Обновляем картинку (добавляем timestamp чтобы не кэшировалось)
                    document.getElementById('avatarPreview').src = `https://${'<?= $_SESSION['username'] ?>'}.iamdaemon.tech/${d.avatar}?t=${Date.now()}`;
                } else {
                    msg.className = 'msg error';
                    msg.textContent = d.error;
                }
            })
            .catch(e => {
                msg.className = 'msg error';
                msg.textContent = 'Ошибка сети';
            });
        });

        // --- Password ---
        function changePassword() {
            const curr = document.getElementById('currPass').value;
            const newP = document.getElementById('newPass').value;
            if(!curr || !newP) return alert("Заполни все поля");

            const fd = new FormData();
            fd.append('action', 'change_password');
            fd.append('current_password', curr);
            fd.append('new_password', newP);

            const msg = document.getElementById('passMsg');
            msg.style.display = 'block';
            msg.textContent = 'Сохранение...';
            msg.className = 'msg';

            fetch('/api/profile.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    msg.className = 'msg success';
                    msg.textContent = d.message;
                    document.getElementById('currPass').value = ''; 
                    document.getElementById('newPass').value = '';
                } else {
                    msg.className = 'msg error';
                    msg.textContent = d.error;
                }
            });
        }

        // --- Email ---
        function changeEmail() {
            const newE = document.getElementById('newEmail').value;
            const pass = document.getElementById('emailPass').value;
            if(!newE || !pass) return alert("Заполни все поля");

            const fd = new FormData();
            fd.append('action', 'change_email');
            fd.append('new_email', newE);
            fd.append('password', pass);

            const msg = document.getElementById('emailMsg');
            msg.style.display = 'block';
            msg.textContent = 'Сохранение...';
            msg.className = 'msg';

            fetch('/api/profile.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    msg.className = 'msg success';
                    msg.textContent = d.message;
                    setTimeout(() => location.reload(), 1000);
                } else {
                    msg.className = 'msg error';
                    msg.textContent = d.error;
                }
            });
        }

        // --- Delete ---
        function requestDelete() {
            if(!confirm("Вы уверены? Это действие нельзя отменить.")) return;
            
            const fd = new FormData();
            fd.append('action', 'request_delete_code');
            
            const msg = document.getElementById('deleteMsg');
            msg.style.display = 'block';
            msg.textContent = 'Отправка кода...';
            msg.className = 'msg';

            fetch('/api/profile.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    msg.className = 'msg success';
                    msg.textContent = 'Код отправлен на почту!';
                    document.getElementById('deleteStep1').style.display = 'none';
                    document.getElementById('deleteStep2').style.display = 'block';
                } else {
                    msg.className = 'msg error';
                    msg.textContent = d.error;
                }
            });
        }

        function confirmDelete() {
            const code = document.getElementById('deleteCode').value;
            const fd = new FormData();
            fd.append('action', 'confirm_delete');
            fd.append('code', code);

            const msg = document.getElementById('deleteMsg');
            msg.style.display = 'block';
            msg.textContent = 'Проверка...';
            msg.className = 'msg';

            fetch('/api/profile.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    alert("Аккаунт удален. Перенаправление...");
                    window.location.href = d.redirect;
                } else {
                    msg.className = 'msg error';
                    msg.textContent = d.error;
                }
            });
        }
    </script>
</body>
</html>