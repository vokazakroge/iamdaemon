// ================= NAVIGATION (SPA) =================
const navItems = document.querySelectorAll('.nav-item[data-target]');
const sections = document.querySelectorAll('.content-section');

navItems.forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();

        // Убираем активные классы
        navItems.forEach(n => n.classList.remove('active'));
        sections.forEach(s => s.classList.remove('active'));

        // Добавляем активные
        item.classList.add('active');
        const targetId = item.getAttribute('data-target');
        const targetSection = document.getElementById(targetId);

        if (targetSection) {
            targetSection.classList.add('active');

            // Если открыли сокращатель - загрузить ссылки
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

// ================= FILE UPLOAD & LIST =================
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const statusMsg = document.getElementById('statusMsg');

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

    statusMsg.className = 'status-msg';
    statusMsg.textContent = 'Загрузка...';
    statusMsg.style.display = 'block';

    fetch('/api/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                statusMsg.className = 'status-msg error';
                statusMsg.textContent = data.error;
            } else {
                statusMsg.className = 'status-msg success';
                statusMsg.textContent = '✅ Успешно';
                setTimeout(() => location.reload(), 1000);
            }
        })
        .catch(e => {
            statusMsg.className = 'status-msg error';
            statusMsg.textContent = e.message;
        });
}

// DELETE FILE
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
        }).then(r => r.json()).then(d => d.success ? location.reload() : alert(d.error));
    });
});

// EDIT FILE (CodeMirror)
let editor;
const editorModal = document.getElementById('editorModal');
const editorTitle = document.getElementById('editorTitle');
const closeModal = document.getElementById('closeModal');
const cancelEdit = document.getElementById('cancelEdit');
const saveEdit = document.getElementById('saveEdit');

document.querySelectorAll('.btn-icon.edit').forEach(btn => {
    btn.addEventListener('click', function() {
        const filename = this.dataset.file;
        editorTitle.textContent = 'Editing: ' + filename;
        editorModal.classList.add('active');

        fetch(`/api/files.php?action=edit&file=${encodeURIComponent(filename)}`)
            .then(r => r.json())
            .then(data => {
                if (data.error) return alert(data.error);
                const ext = filename.split('.').pop().toLowerCase();
                const modes = {
                    html: 'htmlmixed',
                    htm: 'htmlmixed',
                    css: 'css',
                    js: 'javascript',
                    json: 'javascript',
                    php: 'php'
                };

                if (!editor) {
                    editor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
                        mode: modes[ext] || 'text',
                        theme: 'dracula',
                        lineNumbers: true
                    });
                } else {
                    editor.setOption('mode', modes[ext] || 'text');
                }

                editor.setValue(data.content);
                editor.refresh();
            });
    });
});

closeModal.onclick = () => editorModal.classList.remove('active');
cancelEdit.onclick = () => editorModal.classList.remove('active');
saveEdit.onclick = () => {
    if (!editor) return;
    const filename = editorTitle.textContent.replace('Editing: ', '');
    const fd = new FormData();
    fd.append('action', 'save');
    fd.append('file', filename);
    fd.append('content', editor.getValue());
    saveEdit.disabled = true;
    saveEdit.textContent = 'Saving...';
    fetch('/api/files.php', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(d => {
        if (d.success) {
            editorModal.classList.remove('active');
            setTimeout(() => location.reload(), 500);
        } else alert(d.error);
        saveEdit.disabled = false;
        saveEdit.textContent = 'Save';
    });
};

// ================= SHORTENER LOGIC =================
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
                if (data.error) alert(data.error);
                else {
                    document.getElementById('shortLinkOut').href = data.short_url;
                    document.getElementById('shortLinkOut').textContent = data.short_url;
                    document.getElementById('shortResult').style.display = 'block';
                    loadUrls();
                }
            });
    });
}

function loadUrls() {
    const list = document.getElementById('urlsList');
    if (!list) return;
    fetch('/api/shorten.php?action=list').then(r => r.json()).then(data => {
        if (data.urls.length === 0) list.innerHTML = '<div class="empty-msg">Нет ссылок</div>';
        else {
            let html = '';
            data.urls.forEach(u => {
                html += `<div class="file-item" style="justify-content:space-between;">
                    <div><a href="${u.short_url}" target="_blank" style="color:var(--primary); font-weight:600;">${u.short_url}</a><br><small style="color:var(--muted)">${u.long_url}</small></div>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <span style="color:var(--muted)">${u.clicks} clicks</span>
                        <button class="btn-icon delete" onclick="deleteUrl(${u.id})"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`;
            });
            list.innerHTML = html;
        }
    });
}

// Делаем deleteUrl глобальной
window.deleteUrl = function(id) {
    if (!confirm('Удалить ссылку?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('/api/shorten.php', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(d => d.success ? loadUrls() : alert(d.error));
};

// ================= SETTINGS LOGIC =================
// Avatar
document.getElementById('avatarInput').addEventListener('change', function() {
    const fd = new FormData();
    fd.append('avatar', this.files[0]);
    fd.append('action', 'upload_avatar');
    const msg = document.getElementById('avatarMsg');
    msg.style.display = 'block';
    msg.textContent = 'Loading...';
    fetch('/api/profile.php', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(d => {
        if (d.success) {
            msg.className = 'status-msg success';
            msg.textContent = 'OK';
            document.getElementById('settingsAvatar').src += '?t=' + Date.now();
        } else {
            msg.className = 'status-msg error';
            msg.textContent = d.error;
        }
    });
});

// Password
document.getElementById('btnChangePass').addEventListener('click', () => {
    const fd = new FormData();
    fd.append('action', 'change_password');
    fd.append('current_password', document.getElementById('currPass').value);
    fd.append('new_password', document.getElementById('newPass').value);
    const msg = document.getElementById('passMsg');
    msg.style.display = 'block';
    msg.textContent = 'Saving...';
    fetch('/api/profile.php', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(d => {
        if (d.success) {
            msg.className = 'status-msg success';
            msg.textContent = 'OK';
        } else {
            msg.className = 'status-msg error';
            msg.textContent = d.error;
        }
    });
});

// Delete Account
document.getElementById('btnRequestDelete').addEventListener('click', () => {
    if (!confirm('Отправить код на почту?')) return;
    const fd = new FormData();
    fd.append('action', 'request_delete_code');
    fetch('/api/profile.php', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(d => {
        if (d.success) document.getElementById('deleteStep2').style.display = 'block';
        else alert(d.error);
    });
});
document.getElementById('btnConfirmDelete').addEventListener('click', () => {
    const fd = new FormData();
    fd.append('action', 'confirm_delete');
    fd.append('code', document.getElementById('deleteCode').value);
    fetch('/api/profile.php', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(d => {
        if (d.success) window.location.href = d.redirect;
        else alert(d.error);
    });
});