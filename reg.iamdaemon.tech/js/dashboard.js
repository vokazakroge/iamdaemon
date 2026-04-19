// ================= NAVIGATION (SPA) =================
const navItems = document.querySelectorAll('.nav-item[data-target]');
const sections = document.querySelectorAll('.content-section');

navItems.forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        navItems.forEach(n => n.classList.remove('active'));
        sections.forEach(s => s.classList.remove('active'));

        item.classList.add('active');
        const targetId = item.getAttribute('data-target');
        const targetSection = document.getElementById(targetId);

        if (targetSection) {
            targetSection.classList.add('active');
            if (targetId === 'section-shortener') loadUrls();
        }
    });
});

// ================= SIDEBAR =================
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const menuBtn = document.getElementById('menuBtn');

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        const isCollapsed = sidebar.classList.contains('collapsed');
        sidebarToggle.innerHTML = isCollapsed ? '<i class="fas fa-chevron-right"></i>' : '<i class="fas fa-chevron-left"></i>';
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    });
    if (localStorage.getItem('sidebarCollapsed') === 'true') sidebar.classList.add('collapsed');
}
if (menuBtn) menuBtn.addEventListener('click', () => sidebar.classList.toggle('mobile-open'));

// ================= FILE UPLOAD =================
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const uploadMsg = document.getElementById('uploadMsg');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, preventDefaults, false));

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}
['dragenter', 'dragover'].forEach(e => dropZone.addEventListener(e, () => dropZone.classList.add('dragover'), false));
['dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, () => dropZone.classList.remove('dragover'), false));

dropZone.addEventListener('drop', e => handleFiles(e.dataTransfer.files));
fileInput.addEventListener('change', function() {
    handleFiles(this.files);
});

function handleFiles(files) {
    if (files.length === 0) return;
    const formData = new FormData();
    for (let i = 0; i < files.length; i++) formData.append('files[]', files[i]);

    uploadMsg.className = 'status-msg';
    uploadMsg.textContent = 'Загрузка...';
    uploadMsg.style.display = 'block';

    fetch('/api/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                uploadMsg.className = 'status-msg error';
                uploadMsg.textContent = data.error;
            } else {
                uploadMsg.className = 'status-msg success';
                uploadMsg.textContent = '✅ Успешно загружено';
                setTimeout(() => location.reload(), 1000);
            }
        })
        .catch(e => {
            uploadMsg.className = 'status-msg error';
            uploadMsg.textContent = e.message;
        });
}

// ================= DELETE FILE =================
document.querySelectorAll('.btn-icon.delete').forEach(btn => {
    btn.addEventListener('click', function() {
        const filename = this.dataset.file;
        if (!confirm(`Удалить ${filename}?`)) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('file', filename);
        fetch('/api/files.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(d => d.success ? location.reload() : alert(d.error));
    });
});

// ================= RENAME FILE (DOUBLE CLICK) =================
document.querySelectorAll('.filename-text').forEach(el => {
    el.addEventListener('dblclick', function() {
        const oldName = this.dataset.name;
        const newName = prompt('Новое имя файла:', oldName);

        if (!newName || newName === oldName) return;

        const fd = new FormData();
        fd.append('action', 'rename');
        fd.append('old_name', oldName);
        fd.append('new_name', newName);

        fetch('/api/files.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) location.reload();
                else alert('Ошибка: ' + d.error);
            });
    });
});

// ================= EDITOR (CodeMirror) =================
let editor = null;
const editorModal = document.getElementById('editorModal');
const editorTitle = document.getElementById('editorTitle');
const closeModal = document.getElementById('closeModal');
const cancelEdit = document.getElementById('cancelEdit');
const saveEdit = document.getElementById('saveEdit');
let currentEditFile = null;

document.querySelectorAll('.btn-icon.edit').forEach(btn => {
    btn.addEventListener('click', function() {
        openEditor(this.dataset.file);
    });
});

function openEditor(filename) {
    currentEditFile = filename;
    editorTitle.textContent = 'Редактирование: ' + filename;
    editorModal.classList.add('active');

    fetch(`/api/files.php?action=edit&file=${encodeURIComponent(filename)}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                alert('Ошибка: ' + data.error);
                editorModal.classList.remove('active');
                return;
            }

            const ext = filename.split('.').pop().toLowerCase();
            const modes = {
                'html': 'htmlmixed',
                'htm': 'htmlmixed',
                'css': 'css',
                'js': 'javascript',
                'json': 'javascript',
                'php': 'php',
                'xml': 'xml',
                'svg': 'xml',
                'txt': 'text'
            };
            const mode = modes[ext] || 'text';

            if (!editor) {
                editor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
                    mode: mode,
                    theme: 'dracula',
                    lineNumbers: true,
                    autoCloseTags: true,
                    matchBrackets: true,
                    lineWrapping: true
                });
            } else {
                editor.setOption('mode', mode);
            }

            editor.setValue(data.content);
            setTimeout(() => editor.refresh(), 100);
        })
        .catch(e => {
            alert('Ошибка сети: ' + e.message);
            editorModal.classList.remove('active');
        });
}

closeModal.addEventListener('click', () => editorModal.classList.remove('active'));
cancelEdit.addEventListener('click', () => editorModal.classList.remove('active'));
editorModal.addEventListener('click', (e) => {
    if (e.target === editorModal) editorModal.classList.remove('active');
});

saveEdit.addEventListener('click', () => {
    if (!editor || !currentEditFile) return;

    const content = editor.getValue();
    const formData = new FormData();
    formData.append('action', 'save');
    formData.append('file', currentEditFile);
    formData.append('content', content);

    saveEdit.disabled = true;
    saveEdit.textContent = 'Сохранение...';

    fetch('/api/files.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                editorModal.classList.remove('active');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('Ошибка: ' + data.error);
                saveEdit.disabled = false;
                saveEdit.textContent = 'Save';
            }
        })
        .catch(e => {
            alert('Ошибка сети: ' + e.message);
            saveEdit.disabled = false;
            saveEdit.textContent = 'Save';
        });
});

// ================= SHORTENER =================
const btnCreateLink = document.getElementById('btnCreateLink');
if (btnCreateLink) {
    btnCreateLink.addEventListener('click', () => {
        const longUrl = document.getElementById('shortLongUrl').value;
        if (!longUrl) return alert('Введите URL');

        const fd = new FormData();
        fd.append('action', 'create');
        fd.append('long_url', longUrl);

        fetch('/api/shorten.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    alert('Ошибка: ' + data.error);
                } else {
                    document.getElementById('shortLinkOut').href = data.short_url;
                    document.getElementById('shortLinkOut').textContent = data.short_url;
                    document.getElementById('shortResult').style.display = 'block';
                    loadUrls();
                }
            })
            .catch(e => alert('Ошибка сети: ' + e.message));
    });
}

function loadUrls() {
    const list = document.getElementById('urlsList');
    if (!list) return;

    fetch('/api/shorten.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (data.urls.length === 0) {
                list.innerHTML = '<div class="empty-msg">Нет ссылок</div>';
                return;
            }

            let html = '';
            data.urls.forEach(u => {
                html += `
                <div class="file-item" style="justify-content:space-between;">
                    <div>
                        <a href="${u.short_url}" target="_blank" style="color:var(--primary); font-weight:600; display:block; margin-bottom:5px;">${u.short_url}</a>
                        <small style="color:var(--muted)">${u.long_url}</small>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <span style="color:var(--muted)">${u.clicks} переходов</span>
                        <button class="btn-icon delete" onclick="deleteShortUrl(${u.id})" title="Удалить">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            });
            list.innerHTML = html;
        })
        .catch(e => {
            list.innerHTML = '<div class="empty-msg" style="color:var(--danger)">Ошибка загрузки</div>';
        });
}

window.deleteShortUrl = function(id) {
    if (!confirm('Удалить ссылку?')) return;

    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);

    fetch('/api/shorten.php', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) loadUrls();
            else alert('Ошибка: ' + data.error);
        });
};

// ================= SETTINGS =================
// Avatar
const avatarInput = document.getElementById('avatarInput');
if (avatarInput) {
    avatarInput.addEventListener('change', function() {
        const fd = new FormData();
        fd.append('avatar', this.files[0]);
        fd.append('action', 'upload_avatar');

        const msg = document.getElementById('avatarMsg');
        msg.style.display = 'block';
        msg.textContent = 'Загрузка...';
        msg.className = 'status-msg';

        fetch('/api/profile.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    msg.className = 'status-msg success';
                    msg.textContent = '✅ Аватар загружен';
                    document.getElementById('settingsAvatar').src = 'https://reg.iamdaemon.tech/avatars/' + data.avatar + '?t=' + Date.now();
                } else {
                    msg.className = 'status-msg error';
                    msg.textContent = data.error;
                }
            })
            .catch(e => {
                msg.className = 'status-msg error';
                msg.textContent = 'Ошибка сети';
            });
    });
}

// Password
const btnChangePass = document.getElementById('btnChangePass');
if (btnChangePass) {
    btnChangePass.addEventListener('click', () => {
        const fd = new FormData();
        fd.append('action', 'change_password');
        fd.append('current_password', document.getElementById('currPass').value);
        fd.append('new_password', document.getElementById('newPass').value);

        const msg = document.getElementById('passMsg');
        msg.style.display = 'block';
        msg.textContent = 'Сохранение...';
        msg.className = 'status-msg';

        fetch('/api/profile.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    msg.className = 'status-msg success';
                    msg.textContent = '✅ Пароль изменен';
                    document.getElementById('currPass').value = '';
                    document.getElementById('newPass').value = '';
                } else {
                    msg.className = 'status-msg error';
                    msg.textContent = data.error;
                }
            });
    });
}

// Delete account
const btnRequestDelete = document.getElementById('btnRequestDelete');
if (btnRequestDelete) {
    btnRequestDelete.addEventListener('click', () => {
        if (!confirm('Отправить код подтверждения на почту?')) return;

        const fd = new FormData();
        fd.append('action', 'request_delete_code');

        fetch('/api/profile.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('deleteStep2').style.display = 'block';
                    const msg = document.getElementById('deleteMsg');
                    msg.className = 'status-msg success';
                    msg.textContent = '✅ Код отправлен на почту';
                    msg.style.display = 'block';
                } else {
                    alert('Ошибка: ' + data.error);
                }
            });
    });
}

const btnConfirmDelete = document.getElementById('btnConfirmDelete');
if (btnConfirmDelete) {
    btnConfirmDelete.addEventListener('click', () => {
        const fd = new FormData();
        fd.append('action', 'confirm_delete');
        fd.append('code', document.getElementById('deleteCode').value);

        fetch('/api/profile.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect || 'https://iamdaemon.tech';
                } else {
                    const msg = document.getElementById('deleteMsg');
                    msg.className = 'status-msg error';
                    msg.textContent = 'Ошибка: ' + data.error;
                    msg.style.display = 'block';
                }
            });
    });
}