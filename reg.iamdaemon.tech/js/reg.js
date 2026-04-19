const regFormView = document.getElementById('regFormView');
const codeFormView = document.getElementById('codeFormView');
const successView = document.getElementById('successView');

// Элементы первой формы
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

// Элементы второй формы
const codeForm = document.getElementById('codeForm');
const verifyCodeInput = document.getElementById('verifyCode');
const verifyBtn = document.getElementById('verifyBtn');
const codeMsg = document.getElementById('codeMsg');

let currentUser = '';
let isUsernameAvailable = false;

// === ВАЛИДАЦИЯ ===
const validateUsername = v => !v ? { valid: false, msg: '' } : !/^[a-z0-9-]{3,20}$/.test(v) ? { valid: false, msg: 'только a-z, 0-9, -, 3-20 символов' } : { valid: true, msg: '' };
const validateEmail = v => !v ? { valid: false, msg: '' } : !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? { valid: false, msg: 'некорректный email' } : { valid: true, msg: 'OK' };
const validatePassword = v => !v ? { valid: false, msg: '' } : v.length < 8 ? { valid: false, msg: 'минимум 8 символов' } : { valid: true, msg: 'OK' };

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

// === ПРОВЕРКА НИКА ===
usernameInput.addEventListener('input', e => {
    let val = e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
    e.target.value = val;

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

    setTimeout(async() => {
        try {
            const res = await fetch('/api/check_username.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ username: val }) });
            const data = await res.json();
            if (data.available) {
                isUsernameAvailable = true;
                setFieldState(e.target, msgs.username, { valid: true, msg: 'доступно' });
            } else { setFieldState(e.target, msgs.username, { valid: false, msg: 'занято' }); }
        } catch (e) {}
    }, 500);
});

emailInput.addEventListener('input', e => setFieldState(e.target, msgs.email, validateEmail(e.target.value)));
passwordInput.addEventListener('input', e => setFieldState(e.target, msgs.password, validatePassword(e.target.value)));

// === РЕГИСТРАЦИЯ (Шаг 1) ===
regForm.addEventListener('submit', async e => {
    e.preventDefault();
    submitBtn.disabled = true;
    submitBtn.textContent = 'отправляем...';

    const username = usernameInput.value.trim();
    const email = emailInput.value.trim();
    const password = passwordInput.value;

    try {
        const res = await fetch('/api/register.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ username, email, password }) });
        const data = await res.json();

        if (!res.ok || data.error) throw new Error(data.error);

        // Переход к экрану кода
        currentUser = username;
        regFormView.style.display = 'none';
        codeFormView.style.display = 'block';
        document.getElementById('targetEmail').textContent = email;

    } catch (err) {
        msgs.username.textContent = err.message;
        msgs.username.className = 'validation-msg error';
        submitBtn.disabled = false;
        submitBtn.textContent = 'создать аккаунт';
    }
});

// === ПРОВЕРКА КОДА (Шаг 2) ===
codeForm.addEventListener('submit', async e => {
    e.preventDefault();
    const code = verifyCodeInput.value.trim();
    verifyBtn.disabled = true;
    verifyBtn.textContent = 'проверяем...';

    try {
        const res = await fetch('/api/verify.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ username: currentUser, code }) });
        const data = await res.json();

        if (!res.ok || data.error) throw new Error(data.error);

        // Успех
        codeFormView.style.display = 'none';
        successView.style.display = 'block';
        const link = `https://${currentUser}.iamdaemon.tech`;
        document.getElementById('userLink').href = link;
        document.getElementById('userLink').textContent = link;

    } catch (err) {
        codeMsg.textContent = err.message;
        codeMsg.className = 'validation-msg error';
        verifyBtn.disabled = false;
        verifyBtn.textContent = 'подтвердить';
    }
});