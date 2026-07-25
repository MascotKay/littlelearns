<?php
require_once 'config.php';
if(isLoggedIn()) {
    // Robust check — handles sessions that predate the is_admin flag
    $goAdmin = isAdmin() || (isset($_SESSION['username']) && $_SESSION['username'] === ADMIN_USERNAME);
    header('Location: ' . ($goAdmin ? 'admin.php' : 'home.php'));
    exit();
}
$error = '';
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $pass     = $_POST['password'];
    $pdo      = getDBConnection();

    if (!$pdo) {
        $error = "Database connection error. Please try again.";
    } else {

        // ── Admin path ──────────────────────────────────────────────────────
        if ($username === ADMIN_USERNAME) {
            provisionAdminUser($pdo); // always upserts with fresh hash

            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([ADMIN_USERNAME]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($pass, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $admin['id'];
                $_SESSION['user_type']  = 'admin';
                $_SESSION['username']   = ADMIN_USERNAME;
                $_SESSION['first_name'] = $admin['first_name'];
                $_SESSION['last_name']  = $admin['last_name'];
                $_SESSION['is_admin']   = true;
                header('Location: admin.php'); exit();
            } else {
                $error = "Invalid admin credentials.";
            }

        // ── Normal user path ────────────────────────────────────────────────
        } else {
            // Do NOT filter by user_type here — avoids ENUM issues
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            // Prevent admin account being used via email
            if ($user && $user['username'] === ADMIN_USERNAME) {
                $error = "Use the username to sign in as admin.";
            } elseif ($user && password_verify($pass, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_type']  = $user['user_type'];
                $_SESSION['username']   = $user['username'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name']  = $user['last_name'];
                $_SESSION['is_admin']   = false;
                header('Location: home.php'); exit();
            } else {
                $error = "Invalid username or password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - SproutLearn</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #f093fb;
            --accent2: #4facfe;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb, #4facfe, #43e97b);
            background-size: 400% 400%;
            animation: gradientBG 14s ease infinite;
            padding: 20px;
        }
        @keyframes gradientBG {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        /* Floating bubbles */
        .bubbles {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }
        .bubble {
            position: absolute;
            bottom: -80px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            animation: floatUp linear infinite;
        }
        .bubble:nth-child(1)  { width:40px; height:40px; left:10%; animation-duration:8s; animation-delay:0s; }
        .bubble:nth-child(2)  { width:20px; height:20px; left:25%; animation-duration:10s; animation-delay:2s; }
        .bubble:nth-child(3)  { width:60px; height:60px; left:40%; animation-duration:12s; animation-delay:1s; }
        .bubble:nth-child(4)  { width:30px; height:30px; left:60%; animation-duration:9s; animation-delay:3s; }
        .bubble:nth-child(5)  { width:50px; height:50px; left:75%; animation-duration:11s; animation-delay:0.5s; }
        .bubble:nth-child(6)  { width:25px; height:25px; left:88%; animation-duration:7s; animation-delay:4s; }
        @keyframes floatUp {
            0%   { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(-110vh) rotate(720deg); opacity: 0; }
        }
        .card {
            position: relative;
            z-index: 1;
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 45px 40px;
            width: 100%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            animation: fadeSlideIn 0.6s ease;
        }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .logo-icon {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px; color: white;
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }
        .card h2 {
            font-size: 26px;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .card p.subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 25px;
        }
        .input-group {
            position: relative;
            margin-bottom: 16px;
        }
        .input-group i {
            position: absolute;
            left: 18px; top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
        }
        .input-group input {
            width: 100%;
            padding: 13px 16px 13px 44px;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s, box-shadow 0.3s;
            outline: none;
            background: #f8fafc;
        }
        .input-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102,126,234,0.12);
            background: #fff;
        }
        .btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 8px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }
        .btn:active { transform: translateY(0); }
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 10px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .demo-box {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 12px;
            margin-top: 20px;
            font-size: 12px;
            color: #64748b;
            line-height: 1.8;
        }
        .demo-box strong { color: #334155; }
        .register-link {
            margin-top: 18px;
            font-size: 14px;
            color: #64748b;
        }
        .register-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="bubbles">
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
    </div>

    <div class="card">
        <div class="logo-icon"><i class="fas fa-seedling"></i></div>
        <h2>SproutLearn</h2>
        <p class="subtitle">Welcome back! Sign in to continue learning.</p>

        <?php if($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="Username or Email" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" name="login" class="btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>


        <p class="register-link">
            Don't have an account? <a href="register.php">Create one</a>
        </p>
    </div>
</body>
</html>
