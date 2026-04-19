// Ban/Unban
window.banUser = function(username, status) {
    var newStatus = status === 'active' ? 'banned' : 'active';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/ban.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.error) {
                    alert('Ошибка: ' + data.error);
                } else {
                    location.reload();
                }
            } catch (e) {
                alert('Ошибка ответа: ' + xhr.responseText);
            }
        } else {
            alert('HTTP ошибка: ' + xhr.status);
        }
    };

    xhr.onerror = function() {
        alert('Сетевая ошибка');
    };

    xhr.send(JSON.stringify({
        username: username,
        status: newStatus
    }));
};

// Delete user
window.deleteUser = function(username, id) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/delete.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.error) {
                    alert('Ошибка: ' + data.error);
                } else {
                    location.reload();
                }
            } catch (e) {
                alert('Ошибка ответа: ' + xhr.responseText);
            }
        } else {
            alert('HTTP ошибка: ' + xhr.status);
        }
    };

    xhr.onerror = function() {
        alert('Сетевая ошибка');
    };

    xhr.send(JSON.stringify({
        username: username,
        id: parseInt(id)
    }));
};

// View files
window.viewFiles = function(username) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/api/files.php?username=' + encodeURIComponent(username), true);

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                var modal = document.getElementById('filesModal');
                var modalTitle = document.getElementById('modalTitle');
                var filesList = document.getElementById('filesList');

                modalTitle.textContent = '📁 ' + username + '.iamdaemon.tech';

                if (data.error) {
                    filesList.innerHTML = '<p style="color:#ef4444;">Error: ' + data.error + '</p>';
                } else if (data.files.length === 0) {
                    filesList.innerHTML = '<p style="color:#94a3b8;text-align:center;padding:40px;">No files</p>';
                } else {
                    var html = '<table style="width:100%;border-collapse:collapse;">';
                    html += '<thead><tr style="background:rgba(139,92,246,0.1);"><th style="padding:10px;text-align:left;">File</th><th style="padding:10px;">Size</th></tr></thead><tbody>';

                    data.files.forEach(function(file) {
                        var size = file.is_dir ? 'DIR' : (file.size / 1024).toFixed(2) + ' KB';
                        var icon = file.is_dir ? '📁' : '📄';
                        html += '<tr style="border-bottom:1px solid #2a2a3a;">';
                        html += '<td style="padding:8px;">' + icon + ' ' + file.name + '</td>';
                        html += '<td style="padding:8px;text-align:right;color:#94a3b8;">' + size + '</td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table>';
                    filesList.innerHTML = html;
                }

                modal.style.display = 'flex';
            } catch (e) {
                alert('Ошибка парсинга: ' + e.message);
            }
        }
    };

    xhr.send();
};

window.closeFilesModal = function() {
    document.getElementById('filesModal').style.display = 'none';
};

document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('filesModal');
    if (modal) {
        modal.onclick = function(e) {
            if (e.target === this) {
                window.closeFilesModal();
            }
        };
    }
});