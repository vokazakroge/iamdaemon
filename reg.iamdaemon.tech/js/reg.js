const regFormView = document.getElementById('regFormView');
const codeFormView = document.getElementById('codeFormView');
const successView = document.getElementById('successView');

const regForm = document.getElementById('regForm');
const submitBtn = document.getElementById('submitBtn');
const usernameInput = document.getElementById('username');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const msgs = {
    username: document.getElementById('usernameMsg'),
    email: document.getElementById('emailMsg'),
    password: document.getElementById('passwordMsg')
};

const codeForm = document.getElementById('codeForm');
const verifyCodeInput = document.getElementById('verifyCode');
const verifyBtn = document.getElementById('verifyBtn');
const codeMsg = document.getElementById('codeMsg');

let currentUser = '';
let isUsernameAvailable = false;

// === ДИНАМИЧЕСКИЙ PREVIEW ===
const subdomainPreview = document.getElementById('subdomainPreview');
usernameInput.addEventListener('input', function() {
    const val = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
    if (val.length > 0) {
        subdomainPreview.textContent = val + '.iamdaemon.tech';
    } else {
        subdomainPreview.textContent = 'имя.iamdaemon.tech';
    }
});

const validateUsername = v => !v ? {
    valid: false,
    msg: ''
} : !/^[a-z0-9-]{3,20}$/.test(v) ? {
    valid: false,
    msg: 'только a-z, 0-9, -, 3-20 символов'
} : {
    valid: true,
    msg: ''
};
const validateEmail = v => !v ? {
    valid: false,
    msg: ''
} : !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? {
    valid: false,
    msg: 'некорректный email'
} : {
    valid: true,
    msg: 'OK'
};
const validatePassword = v => !v ? {
    valid: false,
    msg: ''
} : v.length < 8 ? {
    valid: false,
    msg: 'минимум 8 символов'
} : {
    valid: true,
    msg: 'OK'
};

function setFieldState(input, msgEl, state) {
    input.classList.remove('valid', 'invalid');
    msgEl.textContent = state.msg;
    if (state.msg) {
        msgEl.className = state.valid ? 'validation-msg success' : 'validation-msg error';
    }
    checkFormReady();
}

function checkFormReady() {
    const u = validateUsername(usernameInput.value);
    const e = validateEmail(emailInput.value);
    const p = validatePassword(passwordInput.value);
    submitBtn.disabled = !(u.valid && isUsernameAvailable && e.valid && p.valid);
}

usernameInput.addEventListener('input', e => {
    let val = e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
    e.target.value = val;

    // Обновляем preview
    if (val.length > 0) {
        subdomainPreview.textContent = val + '.iamdaemon.tech';
    } else {
        subdomainPreview.textContent = 'имя.iamdaemon.tech';
    }

    const fmt = validateUsername(val);
    if (!fmt.valid) {
        isUsernameAvailable = false;
        setFieldState(e.target, msgs.username, fmt);
        return;
    }

    msgs.username.textContent = '⏳ проверяем...';
    msgs.username.className = 'validation-msg';
    isUsernameAvailable = false;
    checkFormReady();

    setTimeout(async () => {
        try {
            const res = await fetch('/api/check_username.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: val
                })
            });
            const text = await res.text();
            console.log('Check username response:', text);

            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Не JSON:', text);
                msgs.username.textContent = 'Ошибка сервера';
                msgs.username.className = 'validation-msg error';
                return;
            }

            if (data.available) {
                isUsernameAvailable = true;
                setFieldState(e.target, msgs.username, {
                    valid: true,
                    msg: 'доступно'
                });
            } else {
                setFieldState(e.target, msgs.username, {
                    valid: false,
                    msg: 'занято'
                });
            }
        } catch (e) {
            console.error(e);
            msgs.username.textContent = 'Ошибка сети';
            msgs.username.className = 'validation-msg error';
        }
    }, 500);
});

emailInput.addEventListener('input', e => setFieldState(e.target, msgs.email, validateEmail(e.target.value)));
passwordInput.addEventListener('input', e => setFieldState(e.target, msgs.password, validatePassword(e.target.value)));

// === РЕГИСТРАЦИЯ ===
regForm.addEventListener('submit', async e => {
    e.preventDefault();
    submitBtn.disabled = true;
    submitBtn.textContent = 'отправляем...';
    msgs.username.textContent = '';

    const username = usernameInput.value.trim();
    const email = emailInput.value.trim();
    const password = passwordInput.value;

    try {
        console.log('Отправка регистрации...', {
            username,
            email
        });

        const res = await fetch('/api/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                username,
                email,
                password
            })
        });

        const text = await res.text();
        console.log('Ответ сервера:', res.status, text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (parseErr) {
            console.error('Не JSON:', text);
            throw new Error('Сервер вернул не JSON. Проверьте логи сервера.');
        }

        if (!res.ok || data.error) {
            throw new Error(data.error || 'Ошибка регистрации');
        }

        currentUser = username;
        regFormView.style.display = 'none';
        codeFormView.style.display = 'block';
        document.getElementById('targetEmail').textContent = email;

    } catch (err) {
        console.error(err);
        msgs.username.textContent = err.message;
        msgs.username.className = 'validation-msg error';
        submitBtn.disabled = false;
        submitBtn.textContent = 'создать аккаунт';
    }
});

// === ВВОД КОДА ===
codeForm.addEventListener('submit', async e => {
    e.preventDefault();
    const code = verifyCodeInput.value.trim();
    verifyBtn.disabled = true;
    verifyBtn.textContent = 'проверяем...';
    codeMsg.textContent = '';

    try {
        const res = await fetch('/api/verify.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                username: currentUser,
                code
            })
        });

        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch {
            throw new Error('Ошибка ответа сервера');
        }

        if (!res.ok || data.error) throw new Error(data.error);

        codeFormView.style.display = 'none';
        successView.style.display = 'block';

        const userSite = `https://${currentUser}.iamdaemon.tech`;
        const dashboardUrl = 'https://reg.iamdaemon.tech/dashboard';

        const linkEl = document.getElementById('userLink');
        linkEl.href = userSite;
        linkEl.textContent = userSite;

        const dashBtn = document.getElementById('dashboardBtn');
        if (dashBtn) {
            dashBtn.onclick = () => {
                window.location.href = dashboardUrl;
            };
        }

    } catch (err) {
        console.error(err);
        codeMsg.textContent = err.message;
        codeMsg.className = 'validation-msg error';
        verifyBtn.disabled = false;
        verifyBtn.textContent = 'подтвердить';
    }
});