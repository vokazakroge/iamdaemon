// ================= NAVIGATION =================
document.querySelectorAll('.nav-item[data-target]').forEach(item => {
    item.addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        item.classList.add('active');
        const t = document.getElementById(item.dataset.target);
        if (t) {
            t.classList.add('active');
            if (item.dataset.target === 'section-shortener') loadUrls();
        }
    });
});

// ================= SIDEBAR =================
const sb = document.getElementById('sidebar');
const sbToggle = document.getElementById('sidebarToggle');
const menuBtn = document.getElementById('menuBtn');

if (sbToggle) {
    sbToggle.addEventListener('click', () => {
        sb.classList.toggle('collapsed');
        const c = sb.classList.contains('collapsed');
        sbToggle.innerHTML = c ? '<i class="fas fa-chevron-right"></i>' : '<i class="fas fa-chevron-left"></i>';
        localStorage.setItem('sidebarCollapsed', c);
    });
    if (localStorage.getItem('sidebarCollapsed') === 'true') sb.classList.add('collapsed');
}
if (menuBtn) menuBtn.addEventListener('click', () => sb.classList.toggle('mobile-open'));

// ================= UPLOAD =================
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const uploadMsg = document.getElementById('uploadMsg');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, ev => {
    ev.preventDefault();
    ev.stopPropagation();
}, false));
['dragenter', 'dragover'].forEach(e => dropZone.addEventListener(e, () => dropZone.classList.add('dragover'), false));
['dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, () => dropZone.classList.remove('dragover'), false));

dropZone.addEventListener('drop', e => handleFiles(e.dataTransfer.files));
fileInput.addEventListener('change', function() {
    handleFiles(this.files);
});

function handleFiles(files) {
    if (!files.length) return;
    const fd = new FormData();
    for (let i = 0; i < files.length; i++) fd.append('files[]', files[i]);
    uploadMsg.className = 'status-msg';
    uploadMsg.textContent = 'Загрузка...';
    uploadMsg.style.display = 'block';
    fetch('/api/upload.php', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json()).then(d => {
            if (d.error) {
                uploadMsg.className = 'status-msg error';
                uploadMsg.textContent = d.error;
            } else {
                uploadMsg.className = 'status-msg success';
                uploadMsg.textContent = '✅ Успешно';
                setTimeout(() => location.reload(), 800);
            }
        }).catch(e => {
            uploadMsg.className = 'status-msg error';
            uploadMsg.textContent = e.message;
        });
}

// ================= DELETE =================
document.querySelectorAll('.btn-icon.delete').forEach(btn => {
    btn.addEventListener('click', function() {
        const f = this.dataset.file;
        if (!confirm(`Удалить ${f}?`)) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('file', f);
        fetch('/api/files.php', {
            method: 'POST',
            body: fd
        }).then(r => r.json()).then(d => d.success ? location.reload() : alert(d.error));
    });
});

// ================= INLINE RENAME (DOUBLE CLICK) =================
document.querySelectorAll('.filename-text').forEach(el => {
    el.addEventListener('dblclick', function() {
        const span = this;
        const oldName = span.dataset.name;
        const input = document.createElement('input');
        input.type = 'text';
        input.value = oldName;
        input.style.cssText = "background:rgba(139,92,246,0.1); border:1px solid var(--primary); color:var(--text); font-size:1rem; padding:2px 6px; border-radius:4px; width:80%; font-family:inherit; outline:none;";

        span.replaceWith(input);
        input.focus();
        input.select();

        const finishRename = () => {
            const newName = input.value.trim();
            if (newName && newName !== oldName) {
                const fd = new FormData();
                fd.append('action', 'rename');
                fd.append('old_name', oldName);
                fd.append('new_name', newName);
                fetch('/api/files.php', {
                    method: 'POST',
                    body: fd
                }).then(r => r.json()).then(d => {
                    if (d.success) location.reload();
                    else {
                        alert(d.error);
                        location.reload();
                    }
                });
            } else {
                input.replaceWith(span);
            }
        };

        input.addEventListener('blur', finishRename);
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') input.blur();
            if (e.key === 'Escape') {
                input.value = oldName;
                input.blur();
            }
        });
    });
});

// ================= EDITOR =================
let editor = null;
const edModal = document.getElementById('editorModal');
const edTitle = document.getElementById('editorTitle');
const closeEd = document.getElementById('closeModal');
const cancelEd = document.getElementById('cancelEdit');
const saveEd = document.getElementById('saveEdit');
let curFile = null;

document.querySelectorAll('.btn-icon.edit').forEach(btn => btn.addEventListener('click', function() {
    openEditor(this.dataset.file);
}));

function openEditor(fn) {
    curFile = fn;
    edTitle.textContent = 'Редактирование: ' + fn;
    edModal.classList.add('active');
    fetch(`/api/files.php?action=edit&file=${encodeURIComponent(fn)}`)
        .then(r => r.json()).then(d => {
            if (d.error) {
                alert(d.error);
                edModal.classList.remove('active');
                return;
            }
            const ext = fn.split('.').pop().toLowerCase();
            const modes = {
                html: 'htmlmixed',
                htm: 'htmlmixed',
                css: 'css',
                js: 'javascript',
                json: 'javascript',
                php: 'php',
                xml: 'xml',
                svg: 'xml',
                txt: 'text'
            };
            if (!editor) editor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
                mode: modes[ext] || 'text',
                theme: 'dracula',
                lineNumbers: true,
                autoCloseTags: true,
                matchBrackets: true,
                lineWrapping: true
            });
            else editor.setOption('mode', modes[ext] || 'text');
            editor.setValue(d.content);
            setTimeout(() => editor.refresh(), 50);
        }).catch(e => {
            alert(e.message);
            edModal.classList.remove('active');
        });
}

closeEd.onclick = cancelEd.onclick = () => edModal.classList.remove('active');
edModal.onclick = e => {
    if (e.target === edModal) edModal.classList.remove('active');
};

saveEd.onclick = () => {
    if (!editor || !curFile) return;
    const fd = new FormData();
    fd.append('action', 'save');
    fd.append('file', curFile);
    fd.append('content', editor.getValue());
    saveEd.disabled = true;
    saveEd.textContent = 'Сохранение...';
    fetch('/api/files.php', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(d => {
        if (d.success) {
            edModal.classList.remove('active');
            setTimeout(() => location.reload(), 300);
        } else {
            alert(d.error);
            saveEd.disabled = false;
            saveEd.textContent = 'Save';
        }
    });
};

// ================= SHORTENER =================
const btnLink = document.getElementById('btnCreateLink');
if (btnLink) btnLink.addEventListener('click', () => {
    const url = document.getElementById('shortLongUrl').value;
    if (!url) return alert('Введите URL');
    const fd = new FormData();
    fd.append('action', 'create');
    fd.append('long_url', url);
    fetch('/api/shorten.php', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(d => {
        if (d.error) alert(d.error);
        else {
            document.getElementById('shortLinkOut').href = d.short_url;
            document.getElementById('shortLinkOut').textContent = d.short_url;
            document.getElementById('shortResult').style.display = 'block';
            loadUrls();
        }
    });
});

function loadUrls() {
    const list = document.getElementById('urlsList');
    if (!list) return;
    fetch('/api/shorten.php?action=list').then(r => r.json()).then(d => {
        if (!d.urls.length) {
            list.innerHTML = '<div class="empty-msg">Нет ссылок</div>';
            return;
        }
        list.innerHTML = d.urls.map(u => `
            <div class="file-item" style="justify-content:space-between;">
                <div><a href="${u.short_url}" target="_blank" style="color:var(--primary);font-weight:600;display:block;margin-bottom:4px;">${u.short_url}</a><small style="color:var(--muted)">${u.long_url}</small></div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <span style="color:var(--muted)">${u.clicks} переходов</span>
                    <button class="btn-icon delete" onclick="delUrl(${u.id})"><i class="fas fa-trash"></i></button>
                </div>
            </div>`).join('');
    });
}
window.delUrl = id => {
    if (!confirm('Удалить?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('/api/shorten.php', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(d => d.success ? loadUrls() : alert(d.error));
};

// ================= SETTINGS =================
const avIn = document.getElementById('avatarInput');
if (avIn) avIn.addEventListener('change', function() {
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
    }).then(r => r.json()).then(d => {
        if (d.success) {
            msg.className = 'status-msg success';
            msg.textContent = '✅ Готово';
            document.getElementById('settingsAvatar').src = 'https://reg.iamdaemon.tech/avatars/' + d.avatar + '?t=' + Date.now();
        } else {
            msg.className = 'status-msg error';
            msg.textContent = d.error;
        }
    });
});

const btnPass = document.getElementById('btnChangePass');
if (btnPass) btnPass.addEventListener('click', () => {
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
    }).then(r => r.json()).then(d => {
        if (d.success) {
            msg.className = 'status-msg success';
            msg.textContent = '✅ Пароль изменен';
            document.getElementById('currPass').value = '';
            document.getElementById('newPass').value = '';
        } else {
            msg.className = 'status-msg error';
            msg.textContent = d.error;
        }
    });
});

const btnDel = document.getElementById('btnRequestDelete');
if (btnDel) btnDel.addEventListener('click', () => {
    if (!confirm('Отправить код на почту?')) return;
    fetch('/api/profile.php', {
        method: 'POST',
        body: new FormData().append('action', 'request_delete_code')
    }).then(r => r.json()).then(d => {
        if (d.success) {
            document.getElementById('deleteStep2').style.display = 'block';
            const m = document.getElementById('deleteMsg');
            m.className = 'status-msg success';
            m.textContent = '✅ Код отправлен';
            m.style.display = 'block';
        } else alert(d.error);
    });
});

const btnConfDel = document.getElementById('btnConfirmDelete');
if (btnConfDel) btnConfDel.addEventListener('click', () => {
    const fd = new FormData();
    fd.append('action', 'confirm_delete');
    fd.append('code', document.getElementById('deleteCode').value);
    fetch('/api/profile.php', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(d => {
        if (d.success) window.location.href = d.redirect || 'https://iamdaemon.tech';
        else {
            const m = document.getElementById('deleteMsg');
            m.className = 'status-msg error';
            m.textContent = 'Ошибка: ' + d.error;
            m.style.display = 'block';
        }
    });
});