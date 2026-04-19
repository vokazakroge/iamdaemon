// Глобальные функции (через window)
window.banUser = function(username, status) {
    console.log('🔒 banUser вызвана:', username, status);
    var newStatus = status === 'active' ? 'banned' : 'active';

    if (!confirm('Заблокировать пользователя ' + username + '?')) {
        console.log('❌ Отменено пользователем');
        return;
    }

    console.log('📤 Отправка POST /api/ban.php');

    fetch('/api/ban.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                username: username,
                status: newStatus
            })
        })
        .then(function(response) {
            console.log('📥 Ответ сервера:', response.status);
            return response.text();
        })
        .then(function(text) {
            console.log('📄 Текст ответа:', text);
            try {
                var data = JSON.parse(text);
                if (data.error) {
                    console.error('❌ Ошибка от API:', data.error);
                    alert('Ошибка: ' + data.error);
                } else {
                    console.log('✅ Успех! Перезагрузка...');
                    location.reload();
                }
            } catch (e) {
                console.error('❌ Не JSON:', text);
                alert('Сервер вернул не JSON: ' + text.substring(0, 100));
            }
        })
        .catch(function(e) {
            console.error('❌ Ошибка fetch:', e);
            alert('Ошибка сети: ' + e.message);
        });
};

window.deleteUser = function(username, id) {
    console.log('🗑️ deleteUser вызвана:', username, id);

    if (!confirm('УДАЛИТЬ пользователя ' + username + '?')) {
        console.log('❌ Отменено пользователем');
        return;
    }

    console.log('📤 Отправка POST /api/delete.php');

    fetch('/api/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                username: username,
                id: parseInt(id)
            })
        })
        .then(function(response) {
            console.log('📥 Ответ сервера:', response.status);
            return response.text();
        })
        .then(function(text) {
            console.log('📄 Текст ответа:', text);
            try {
                var data = JSON.parse(text);
                if (data.error) {
                    console.error('❌ Ошибка от API:', data.error);
                    alert('Ошибка: ' + data.error);
                } else {
                    console.log('✅ Успех! Перезагрузка...');
                    location.reload();
                }
            } catch (e) {
                console.error('❌ Не JSON:', text);
                alert('Сервер вернул не JSON: ' + text.substring(0, 100));
            }
        })
        .catch(function(e) {
            console.error('❌ Ошибка fetch:', e);
            alert('Ошибка сети: ' + e.message);
        });
};

window.viewFiles = function(username) {
    console.log('📁 viewFiles:', username);
    var modal = document.getElementById('filesModal');
    var modalTitle = document.getElementById('modalTitle');
    var filesList = document.getElementById('filesList');

    modalTitle.textContent = '📁 ' + username + '.iamdaemon.tech';
    filesList.innerHTML = '<p>Loading...</p>';
    modal.style.display = 'flex';

    fetch('/api/files.php?username=' + encodeURIComponent(username))
        .then(function(r) {
            return r.json();
        })
        .then(function(data) {
            if (data.error) {
                filesList.innerHTML = '<p style="color:#ef4444;">Error: ' + data.error + '</p>';
                return;
            }

            if (data.files.length === 0) {
                filesList.innerHTML = '<p style="color:#94a3b8;text-align:center;padding:40px;">No files</p>';
            } else {
                var html = '<table style="width:100%;border-collapse:collapse;">';
                html += '<thead><tr style="background:rgba(139,92,246,0.1);"><th style="padding:10px;text-align:left;">File</th><th style="padding:10px;">Size</th><th style="padding:10px;">Actions</th></tr></thead><tbody>';

                data.files.forEach(function(file) {
                    var size = file.is_dir ? 'DIR' : (file.size / 1024).toFixed(2) + ' KB';
                    var icon = file.is_dir ? '📁' : '📄';
                    html += '<tr style="border-bottom:1px solid #2a2a3a;">';
                    html += '<td style="padding:8px;">' + icon + ' ' + file.name + '</td>';
                    html += '<td style="padding:8px;text-align:right;color:#94a3b8;">' + size + '</td>';
                    html += '<td style="padding:8px;text-align:center;">';
                    if (!file.is_dir) {
                        html += '<a href="https://' + username + '.iamdaemon.tech/' + file.name + '" target="_blank" style="color:#8b5cf6;">↗</a>';
                    }
                    html += '</td></tr>';
                });

                html += '</tbody></table>';
                filesList.innerHTML = html;
            }
        })
        .catch(function(e) {
            filesList.innerHTML = '<p style="color:#ef4444;">Error: ' + e.message + '</p>';
        });
};

window.closeFilesModal = function() {
    document.getElementById('filesModal').style.display = 'none';
};

// Закрытие модалки по клику вне её
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filesModal').onclick = function(e) {
        if (e.target === this) {
            window.closeFilesModal();
        }
    };
});

console.log('✅ Admin JS loaded');