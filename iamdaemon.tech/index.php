<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DAEMON — Твоя цифровая империя</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #050508;
            --card: #0a0a0f;
            --primary: #8b5cf6;
            --primary-glow: rgba(139, 92, 246, 0.5);
            --text: #ffffff;
            --muted: #94a3b8;
            --border: #1a1a2e;
            --gradient: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 50%, #c4b5fd 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: 
                radial-gradient(circle at 20% 50%, rgba(139, 92, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(167, 139, 250, 0.1) 0%, transparent 50%);
        }

        /* Navigation */
        nav {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(10px);
            background: rgba(5, 5, 8, 0.8);
            border-bottom: 1px solid var(--border);
        }

        .logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            font-weight: 900;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            color: var(--muted);
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        .nav-links a:hover {
            color: var(--text);
        }

        .btn-nav {
            padding: 10px 24px;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px var(--primary-glow);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 120px 20px 80px;
            position: relative;
        }

        .hero-badge {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid var(--primary);
            border-radius: 20px;
            font-size: 0.9rem;
            color: var(--primary);
            margin-bottom: 30px;
            animation: fadeInDown 0.8s;
        }

        .hero h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(2.5rem, 8vw, 5rem);
            font-weight: 900;
            margin-bottom: 20px;
            line-height: 1.1;
            animation: fadeInUp 0.8s;
        }

        .hero h1 span {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--muted);
            max-width: 600px;
            margin-bottom: 40px;
            animation: fadeInUp 0.8s 0.2s backwards;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
            animation: fadeInUp 0.8s 0.4s backwards;
        }

        .btn-primary {
            padding: 16px 40px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
            box-shadow: 0 10px 40px var(--primary-glow);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 60px var(--primary-glow);
        }

        .btn-secondary {
            padding: 16px 40px;
            background: transparent;
            color: var(--text);
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            background: rgba(139, 92, 246, 0.1);
        }

        /* Features */
        .features {
            padding: 100px 5%;
            background: var(--card);
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .section-title p {
            color: var(--muted);
            font-size: 1.1rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px 30px;
            transition: 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 20px 40px rgba(139, 92, 246, 0.2);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }

        .feature-card p {
            color: var(--muted);
            line-height: 1.6;
        }

        /* URL Shortener Demo */
        .shortener-demo {
            padding: 100px 5%;
            background: var(--bg);
        }

        .demo-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
        }

        .demo-input-group {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .demo-input {
            flex: 1;
            padding: 16px 20px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 1rem;
        }

        .demo-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Stats */
        .stats {
            padding: 80px 5%;
            background: var(--card);
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .stat-item h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 3rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .stat-item p {
            color: var(--muted);
            font-size: 1.1rem;
        }

        /* Footer */
        footer {
            padding: 40px 5%;
            background: var(--bg);
            border-top: 1px solid var(--border);
            text-align: center;
            color: var(--muted);
        }

        footer a {
            color: var(--primary);
            text-decoration: none;
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .demo-input-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    
    <nav>
        <a href="#" class="logo">DAEMON</a>
        <div class="nav-links">
            <a href="#features">Возможности</a>
            <a href="#shortener">Сокращатель</a>
            <a href="https://reg.iamdaemon.tech/login" class="btn-nav">Войти</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-badge">🚀 Бесплатный хостинг + инструменты</div>
        <h1>Создай свою <span>цифровую империю</span></h1>
        <p>Получи бесплатный поддомен, хости файлы, сокращай ссылки и управляй всем через мощный дашборд</p>
        <div class="hero-buttons">
            <a href="https://reg.iamdaemon.tech" class="btn-primary">Начать бесплатно</a>
            <a href="#features" class="btn-secondary">Узнать больше</a>
        </div>
    </section>

    <section id="features" class="features">
        <div class="section-title">
            <h2>Всё в одном месте</h2>
            <p>Мощные инструменты для твоих проектов</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🌐</div>
                <h3>Твой поддомен</h3>
                <p>Получи персональный поддомен вида <strong>твоё-имя.iamdaemon.tech</strong>. Хости сайты, лендинги, проекты — что угодно.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📁</div>
                <h3>Файловый менеджер</h3>
                <p>Загружай, редактируй и управляй файлами прямо из браузера. Поддержка HTML, CSS, JS и других форматов.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">✂️</div>
                <h3>Сокращатель ссылок</h3>
                <p>Создавай короткие ссылки вида <strong>твоё-имя.iamdaemon.tech/go/xyz</strong>. Отслеживай переходы и делись удобно.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <h3>Безопасность</h3>
                <p>SSL-сертификаты, защита паролем, двухфакторная аутентификация. Твои данные под надёжной защитой.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Быстро и просто</h3>
                <p>Интуитивный интерфейс, мгновенный деплой, никаких сложных настроек. Зарегистрировался — и работает.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Статистика</h3>
                <p>Отслеживай посещения, переходы по ссылкам и активность на твоём поддомене в реальном времени.</p>
            </div>
        </div>
    </section>

    <section id="shortener" class="shortener-demo">
        <div class="section-title">
            <h2>Сокращатель ссылок</h2>
            <p>Превращай длинные URL в короткие и красивые</p>
        </div>
        <div class="demo-container">
            <div class="demo-input-group">
                <input type="text" class="demo-input" placeholder="Вставь длинную ссылку сюда..." value="https://example.com/very/long/url/that/needs/to/be/shortened">
                <button class="btn-primary" style="white-space: nowrap;">Сократить</button>
            </div>
            <div style="background: var(--bg); padding: 15px; border-radius: 8px; border: 1px solid var(--border);">
                <span style="color: var(--muted);">Результат: </span>
                <strong style="color: var(--primary);">username.iamdaemon.tech/go/abc123</strong>
            </div>
        </div>
    </section>

    <section class="stats">
        <div class="stats-grid">
            <div class="stat-item">
                <h3>100+</h3>
                <p>Пользователей</p>
            </div>
            <div class="stat-item">
                <h3>500+</h3>
                <p>Поддоменов</p>
            </div>
            <div class="stat-item">
                <h3>1000+</h3>
                <p>Сокращённых ссылок</p>
            </div>
            <div class="stat-item">
                <h3>99.9%</h3>
                <p>Аптайм</p>
            </div>
        </div>
    </section>

    <footer>
        <p>© 2026 DAEMON. Создано с <span style="color: #ef4444;">❤</span> для тебя.</p>
        <p style="margin-top: 10px;"><a href="https://reg.iamdaemon.tech">Регистрация</a> • <a href="https://reg.iamdaemon.tech/login">Вход</a></p>
    </footer>

    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>