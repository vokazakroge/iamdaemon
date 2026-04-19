<?php
require_once __DIR__ . '/config.php';
requireLogin();
checkUserStatus();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URL Shortener — DAEMON</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0f;
            --card: #12121a;
            --primary: #8b5cf6;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --border: #2a2a3a;
            --success: #10b981;
            --danger: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding-top: 40px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .back-btn {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.95rem;
            transition: 0.2s;
        }

        .back-btn:hover { color: var(--text); }

        h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .input-group {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        input[type="text"] {
            flex: 1;
            padding: 14px 18px;
            background: rgba(139, 92, 246, 0.05);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 1rem;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
        }

        button {
            padding: 14px 28px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        button:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }

        .result {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success);
            padding: 15px 20px;
            border-radius: 10px;
            margin-top: 20px;
            display: none;
        }

        .result.show { display: block; animation: fadeIn 0.3s; }

        .result a {
            color: var(--primary);
            font-weight: 700;
            word-break: break-all;
        }

        .urls-list {
            margin-top: 20px;
        }

        .url-item {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .url-info {
            flex: 1;
            min-width: 250px;
        }

        .url-info a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            display: block;
            margin-bottom: 5px;
        }

        .url-info small {
            color: var(--muted);
            word-break: break-all;
        }

        .url-stats {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .stat {
            text-align: center;
        }

        .stat strong {
            display: block;
            font-size: 1.3rem;
            color: var(--primary);
        }

        .stat span {
            font-size: 0.85rem;
            color: var(--muted);
        }

        .btn-delete {
            background: var(--danger);
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .btn-delete:hover { background: #dc2626; }

        .btn-copy {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--muted);
            padding: 6px 12px;
            font-size: 0.85rem;
            margin-left: 10px;
        }

        .btn-copy:hover {
            border-color: var(--primary);
            color: var(--text);
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: var(--muted);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <a href="https://reg.iamdaemon.tech/dashboard" class="back-btn">← Назад в Dashboard</a>
            <a href="https://iamdaemon.tech" class="logo">DAEMON</a>
        </header>

        <h1>✂️ Сокращатель ссылок</h1>

        <div class="card">
            <h2 style="margin-bottom: 20px; font-family: 'Orbitron';">Создать короткую ссылку</h2>
            <div class="input-group">
                <input type="text" id="longUrl" placeholder="Вставь длинную ссылку (https://...)">
                <button onclick="createShortUrl()">Сократить</button>
            </div>
            <div id="result" class="result">
                <strong>Готово!</strong><br>
                Твоя короткая ссылка: <a id="shortUrl" href="#" target="_blank"></a>
                <button class="btn-copy" onclick="copyUrl()">Копировать</button>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 20px; font-family: 'Orbitron';">Твои ссылки</h2>
            <div id="urlsList" class="urls-list">
                <div class="empty">Загрузка...</div>
            </div>
        </div>
    </div>

    <script>
        const username = '<?= $_SESSION['username'] ?>';

        // Создание короткой ссылки
        function createShortUrl() {
            const longUrl = document.getElementById('longUrl').value;
            if (!longUrl) return alert('Введите URL');

            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('long_url', longUrl);

            fetch('/api/shorten.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                } else {
                    document.getElementById('shortUrl').textContent = data.short_url;
                    document.getElementById('shortUrl').href = data.short_url;
                    document.getElementById('result').classList.add('show');
                    document.getElementById('longUrl').value = '';
                    loadUrls(); // Обновляем список
                }
            });
        }

        // Загрузка списка ссылок
        function loadUrls() {
            fetch('/api/shorten.php?action=list')
            .then(r => r.json())
            .then(data => {
                const container = document.getElementById('urlsList');
                if (data.urls.length === 0) {
                    container.innerHTML = '<div class="empty">У тебя пока нет ссылок</div>';
                    return;
                }

                let html = '';
                data.urls.forEach(url => {
                    html += `
                        <div class="url-item">
                            <div class="url-info">
                                <a href="${url.short_url}" target="_blank">${url.short_url}</a>
                                <small>${url.long_url}</small>
                            </div>
                            <div class="url-stats">
                                <div class="stat">
                                    <strong>${url.clicks}</strong>
                                    <span>переходов</span>
                                </div>
                                <button class="btn-delete" onclick="deleteUrl(${url.id})">Удалить</button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            });
        }

        // Удаление ссылки
        function deleteUrl(id) {
            if (!confirm('Удалить эту ссылку?')) return;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('/api/shorten.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) loadUrls();
                else alert(data.error);
            });
        }

        // Копирование
        function copyUrl() {
            const url = document.getElementById('shortUrl').textContent;
            navigator.clipboard.writeText(url).then(() => {
                alert('Скопировано!');
            });
        }

        // Загружаем список при старте
        loadUrls();
    </script>
</body>
</html>