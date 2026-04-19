// Ban/Unban
document.querySelectorAll('.btn-action.ban').forEach(btn => {
    btn.addEventListener('click', async () => {
        const username = btn.dataset.username;
        const currentStatus = btn.dataset.status;
        const newStatus = currentStatus === 'active' ? 'banned' : 'active';
        const action = currentStatus === 'active' ? 'заблокировать' : 'разблокировать';

        console.log('🔍 Ban click:', {
            username,
            currentStatus,
            newStatus
        });

        if (!confirm(`${action} пользователя ${username}?`)) return;

        try {
            console.log('📤 Отправка запроса...');
            const res = await fetch('/api/ban.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: username,
                    status: newStatus
                })
            });

            console.log('📥 Ответ сервера:', res.status);
            const text = await res.text();
            console.log('📄 Тело ответа:', text);

            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('Сервер вернул не JSON: ' + text.substring(0, 200));
            }

            if (!res.ok || data.error) {
                throw new Error(data.error || 'Ошибка сервера');
            }

            // Обновляем UI
            const row = btn.closest('tr');
            const statusCell = row.querySelector('td:nth-child(6) .badge');
            statusCell.textContent = newStatus;
            statusCell.className = `badge ${newStatus === 'active' ? 'success' : 'error'}`;

            btn.textContent = newStatus === 'active' ? '🔓' : '🔒';
            btn.dataset.status = newStatus;

            alert(`✅ Пользователь ${username} ${action === 'заблокировать' ? 'заблокирован' : 'разблокирован'}`);

        } catch (err) {
            console.error('❌ Ошибка:', err);
            alert('Ошибка: ' + err.message);
        }
    });
});

// Delete user
document.querySelectorAll('.btn-action.delete').forEach(btn => {
    btn.addEventListener('click', async () => {
        const username = btn.dataset.username;
        const userId = btn.dataset.id;

        console.log(' Delete click:', {
            username,
            userId
        });

        if (!confirm(`УДАЛИТЬ пользователя ${username}?\n\nЭто удалит:\n- Аккаунт из базы\n- Все файлы с поддомена`)) return;

        try {
            console.log('📤 Отправка запроса...');
            const res = await fetch('/api/delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: username,
                    id: parseInt(userId)
                })
            });

            console.log('📥 Ответ сервера:', res.status);
            const text = await res.text();
            console.log('📄 Тело ответа:', text);

            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('Сервер вернул не JSON: ' + text.substring(0, 200));
            }

            if (!res.ok || data.error) {
                throw new Error(data.error || 'Ошибка сервера');
            }

            // Удаляем строку из таблицы
            btn.closest('tr').remove();
            alert(`✅ Пользователь ${username} удалён`);

        } catch (err) {
            console.error('❌ Ошибка:', err);
            alert('Ошибка: ' + err.message);
        }
    });
});

// View files modal
document.querySelectorAll('.btn-action.view-files').forEach(btn => {
    btn.addEventListener('click', async () => {
        const username = btn.dataset.username;
        const modal = document.getElementById('filesModal');
        const modalTitle = document.getElementById('modalTitle');
        const filesList = document.getElementById('filesList');
        const closeModal = document.getElementById('closeModal');

        console.log('🔍 View files:', username);

        modalTitle.textContent = `📁 ${username}.iamdaemon.tech`;
        filesList.innerHTML = '<p>Loading...</p>';
        modal.style.display = 'flex';

        try {
            const res = await fetch(`/api/files.php?username=${encodeURIComponent(username)}`);
            const data = await res.json();

            if (!res.ok || data.error) {
                throw new Error(data.error);
            }

            if (data.files.length === 0) {
                filesList.innerHTML = '<p style="color:#94a3b8;text-align:center;padding:40px;">No files</p>';
            } else {
                let html = '<table style="width:100%;border-collapse:collapse;">';
                html += '<thead><tr style="background:rgba(139,92,246,0.1);"><th style="padding:10px;text-align:left;">File</th><th style="padding:10px;">Size</th><th style="padding:10px;">Actions</th></tr></thead><tbody>';

                data.files.forEach(file => {
                    const size = file.is_dir ? 'DIR' : (file.size / 1024).toFixed(2) + ' KB';
                    const icon = file.is_dir ? '📁' : '📄';
                    html += `<tr style="border-bottom:1px solid #2a2a3a;">
                        <td style="padding:8px;">${icon} ${file.name}</td>
                        <td style="padding:8px;text-align:right;color:#94a3b8;">${size}</td>
                        <td style="padding:8px;text-align:center;">
                            ${!file.is_dir ? `<a href="https://${username}.iamdaemon.tech/${file.name}" target="_blank" style="color:#8b5cf6;">↗</a>` : ''}
                        </td>
                    </tr>`;
                });

                html += '</tbody></table>';
                filesList.innerHTML = html;
            }

        } catch (err) {
            console.error('❌ Ошибка:', err);
            filesList.innerHTML = `<p style="color:#ef4444;">Error: ${err.message}</p>`;
        }

        closeModal.onclick = () => {
            modal.style.display = 'none';
        };
        modal.onclick = (e) => {
            if (e.target === modal) modal.style.display = 'none';
        };
    });
});