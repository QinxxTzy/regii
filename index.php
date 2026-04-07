<?php
// Database configuration
$host = "localhost";
$dbname = "game_topup";
$username = "root";
$password = "";
 
// Session start
session_start();
 
$error = "";
$success = "";
 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
        $input_username = trim($_POST['username']);
        $input_password = md5(trim($_POST['password'])); // MD5 hash
 
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ? LIMIT 1");
        $stmt->execute([$input_username, $input_password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $success = "Login berhasil! Selamat datang, " . htmlspecialchars($user['username']) . "!";
            header("Location: dashboard.php"); // uncomment untuk redirect
        } else {
            $error = "Username atau password salah!";
        }
    } catch (PDOException $e) {
        $error = "Koneksi database gagal: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameVault — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #00f0ff;
            --secondary: #ff6b00;
            --accent: #7b2fff;
            --dark: #060a14;
            --card-bg: rgba(10, 18, 35, 0.92);
            --border: rgba(0, 240, 255, 0.2);
            --glow: 0 0 20px rgba(0, 240, 255, 0.4);
            --glow-orange: 0 0 20px rgba(255, 107, 0, 0.5);
        }
 
        * { margin: 0; padding: 0; box-sizing: border-box; }
 
        body {
            font-family: 'Rajdhani', sans-serif;
            background-color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
 
        /* Animated Background */
        .bg-scene {
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse 80% 50% at 20% 60%, rgba(123, 47, 255, 0.18) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 80% 20%, rgba(0, 240, 255, 0.12) 0%, transparent 60%),
                radial-gradient(ellipse 50% 60% at 50% 100%, rgba(255, 107, 0, 0.1) 0%, transparent 60%),
                #060a14;
        }
 
        .grid-lines {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image:
                linear-gradient(rgba(0, 240, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 240, 255, 0.04) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 20s linear infinite;
        }
 
        @keyframes gridMove {
            0% { background-position: 0 0; }
            100% { background-position: 50px 50px; }
        }
 
        /* Floating particles */
        .particles {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }
 
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: var(--primary);
            border-radius: 50%;
            animation: floatUp linear infinite;
            opacity: 0;
        }
 
        @keyframes floatUp {
            0% { transform: translateY(100vh) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 0.5; }
            100% { transform: translateY(-20vh) translateX(50px); opacity: 0; }
        }
 
        /* Main Container */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            padding: 20px;
            animation: fadeInUp 0.8s ease forwards;
        }
 
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
 
        /* Logo / Header */
        .brand-header {
            text-align: center;
            margin-bottom: 32px;
        }
 
        .brand-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 16px;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            box-shadow: 0 0 40px rgba(0, 240, 255, 0.5), 0 0 80px rgba(123, 47, 255, 0.3);
            position: relative;
            animation: iconPulse 3s ease-in-out infinite;
        }
 
        @keyframes iconPulse {
            0%, 100% { box-shadow: 0 0 40px rgba(0, 240, 255, 0.5), 0 0 80px rgba(123, 47, 255, 0.3); }
            50% { box-shadow: 0 0 60px rgba(0, 240, 255, 0.8), 0 0 120px rgba(123, 47, 255, 0.5); }
        }
 
        .brand-name {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-transform: uppercase;
        }
 
        .brand-tagline {
            color: rgba(255,255,255,0.4);
            font-size: 12px;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: 4px;
            font-weight: 300;
        }
 
        /* Card */
        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px 36px;
            backdrop-filter: blur(20px);
            box-shadow:
                0 0 0 1px rgba(0, 240, 255, 0.05),
                0 20px 60px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 rgba(255,255,255,0.05);
            position: relative;
            overflow: hidden;
        }
 
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary), var(--accent), transparent);
            animation: scanLine 3s linear infinite;
        }
 
        @keyframes scanLine {
            0% { opacity: 0.4; }
            50% { opacity: 1; }
            100% { opacity: 0.4; }
        }
 
        .card-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
 
        .card-subtitle {
            color: rgba(255,255,255,0.35);
            font-size: 13px;
            margin-bottom: 30px;
            font-weight: 400;
        }
 
        /* Form Controls */
        .form-group { margin-bottom: 20px; }
 
        .form-label {
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--primary);
            margin-bottom: 8px;
            font-weight: 700;
        }
 
        .input-wrap {
            position: relative;
        }
 
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(0, 240, 255, 0.5);
            font-size: 16px;
            z-index: 2;
            transition: color 0.3s;
        }
 
        .form-control {
            background: rgba(0, 240, 255, 0.04);
            border: 1px solid rgba(0, 240, 255, 0.15);
            border-radius: 10px;
            color: #fff;
            font-family: 'Rajdhani', sans-serif;
            font-size: 15px;
            font-weight: 600;
            padding: 14px 16px 14px 46px;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
        }
 
        .form-control::placeholder { color: rgba(255,255,255,0.2); font-weight: 400; }
 
        .form-control:focus {
            background: rgba(0, 240, 255, 0.08);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 240, 255, 0.1), var(--glow);
            color: #fff;
            outline: none;
        }
 
        .form-control:focus + .input-icon,
        .input-wrap:focus-within .input-icon { color: var(--primary); }
 
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            cursor: pointer;
            font-size: 16px;
            z-index: 2;
            transition: color 0.3s;
        }
        .toggle-password:hover { color: var(--primary); }
 
        /* Remember & Forgot */
        .form-check-label {
            color: rgba(255,255,255,0.5);
            font-size: 13px;
            cursor: pointer;
            font-weight: 400;
        }
 
        .form-check-input {
            background-color: transparent;
            border-color: rgba(0, 240, 255, 0.3);
        }
 
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
 
        .forgot-link {
            color: var(--secondary);
            font-size: 13px;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .forgot-link:hover { color: #ffaa44; text-decoration: underline; }
 
        /* Login Button */
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #00c8d4, #0080ff);
            border: none;
            border-radius: 12px;
            font-family: 'Orbitron', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #fff;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 200, 212, 0.4);
            margin-top: 10px;
        }
 
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 200, 212, 0.6);
        }
 
        .btn-login:active { transform: translateY(0); }
 
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
 
        .btn-login:hover::before { left: 100%; }
 
        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: rgba(255,255,255,0.2);
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
 
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.08);
        }
 
        /* Social Login */
        .btn-social {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 13px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            color: rgba(255,255,255,0.6);
            font-family: 'Rajdhani', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
 
        .btn-social:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.2);
            color: #fff;
            transform: translateY(-1px);
        }
 
        /* Register Link */
        .register-text {
            text-align: center;
            color: rgba(255,255,255,0.35);
            font-size: 13px;
            margin-top: 24px;
        }
 
        .register-link {
            color: var(--secondary);
            font-weight: 700;
            text-decoration: none;
            transition: color 0.3s;
        }
 
        .register-link:hover { color: #ffaa44; }
 
        /* Alert */
        .alert-custom {
            border-radius: 10px;
            border: none;
            font-family: 'Rajdhani', sans-serif;
            font-size: 14px;
            font-weight: 600;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
 
        .alert-danger-custom {
            background: rgba(255, 50, 80, 0.12);
            border: 1px solid rgba(255, 50, 80, 0.3);
            color: #ff6680;
        }
 
        .alert-success-custom {
            background: rgba(0, 240, 100, 0.1);
            border: 1px solid rgba(0, 240, 100, 0.3);
            color: #00f064;
        }
 
        /* Features strip */
        .features-strip {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
 
        .feature-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: rgba(255,255,255,0.3);
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
 
        .feature-item i {
            font-size: 18px;
            color: var(--primary);
        }
 
        /* Corner decorations */
        .corner-deco {
            position: absolute;
            width: 20px;
            height: 20px;
        }
 
        .corner-deco.tl { top: 12px; left: 12px; border-top: 2px solid var(--primary); border-left: 2px solid var(--primary); }
        .corner-deco.tr { top: 12px; right: 12px; border-top: 2px solid var(--primary); border-right: 2px solid var(--primary); }
        .corner-deco.bl { bottom: 12px; left: 12px; border-bottom: 2px solid var(--primary); border-left: 2px solid var(--primary); }
        .corner-deco.br { bottom: 12px; right: 12px; border-bottom: 2px solid var(--primary); border-right: 2px solid var(--primary); }
 
        /* Responsive */
        @media (max-width: 480px) {
            .login-card { padding: 28px 20px; }
            .brand-name { font-size: 22px; }
        }
    </style>
</head>
<body>
 
<!-- Background -->
<div class="bg-scene"></div>
<div class="grid-lines"></div>
<div class="particles" id="particles"></div>
 
<!-- Login Wrapper -->
<div class="login-wrapper">
 
    <!-- Brand Header -->
    <div class="brand-header">
        <div class="brand-icon">
            <i class="bi bi-controller text-white"></i>
        </div>
        <div class="brand-name">GameVault</div>
        <div class="brand-tagline">Premium Game Top Up Platform</div>
    </div>
 
    <!-- Login Card -->
    <div class="login-card">
        <div class="corner-deco tl"></div>
        <div class="corner-deco tr"></div>
        <div class="corner-deco bl"></div>
        <div class="corner-deco br"></div>
 
        <div class="card-title">Masuk Akun</div>
        <div class="card-subtitle">Selamat datang kembali, Gamer!</div>
 
        <?php if ($error): ?>
        <div class="alert-custom alert-danger-custom">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
 
        <?php if ($success): ?>
        <div class="alert-custom alert-success-custom">
            <i class="bi bi-check-circle-fill"></i>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>
 
        <form method="POST" action="">
            <!-- Username -->
            <div class="form-group">
                <label class="form-label">Username / Email</label>
                <div class="input-wrap">
                    <i class="bi bi-person-fill input-icon"></i>
                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username..."
                        value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                        required
                        autocomplete="username"
                    >
                </div>
            </div>
 
            <!-- Password -->
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrap">
                    <i class="bi bi-lock-fill input-icon"></i>
                    <input
                        type="password"
                        name="password"
                        id="passwordInput"
                        class="form-control"
                        placeholder="Masukkan password..."
                        required
                        autocomplete="current-password"
                    >
                    <i class="bi bi-eye-fill toggle-password" id="togglePass" onclick="togglePassword()"></i>
                </div>
            </div>
 
            <!-- Remember & Forgot -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                    <label class="form-check-label" for="rememberMe">Ingat saya</label>
                </div>
                <a href="forgot_password.php" class="forgot-link">Lupa password?</a>
            </div>
 
            <!-- Submit -->
            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i> LOGIN SEKARANG
            </button>
        </form>
 
        <div class="divider">atau masuk dengan</div>
 
        <!-- Register -->
        <div class="register-text">
            Belum punya akun? <a href="register.php" class="register-link">Daftar Gratis</a>
        </div>
 
        <!-- Features -->
        <div class="features-strip">
            <div class="feature-item">
                <i class="bi bi-shield-check"></i>
                <span>Aman</span>
            </div>
            <div class="feature-item">
                <i class="bi bi-lightning-charge"></i>
                <span>Instan</span>
            </div>
            <div class="feature-item">
                <i class="bi bi-headset"></i>
                <span>24/7 CS</span>
            </div>
            <div class="feature-item">
                <i class="bi bi-star"></i>
                <span>Terpercaya</span>
            </div>
        </div>
    </div>
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle password visibility
    function togglePassword() {
        const input = document.getElementById('passwordInput');
        const icon = document.getElementById('togglePass');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
        }
    }
 
    // Generate floating particles
    const container = document.getElementById('particles');
    for (let i = 0; i < 30; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.cssText = `
            left: ${Math.random() * 100}%;
            width: ${Math.random() * 3 + 1}px;
            height: ${Math.random() * 3 + 1}px;
            animation-duration: ${Math.random() * 15 + 10}s;
            animation-delay: ${Math.random() * 10}s;
            background: ${Math.random() > 0.5 ? '#00f0ff' : '#7b2fff'};
        `;
        container.appendChild(p);
    }
</script>
 
</body>
</html>