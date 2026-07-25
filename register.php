<?php
require_once 'config.php';
if(isLoggedIn()) { header('Location: home.php'); exit(); }
$error = $success = '';
if($_SERVER['REQUEST_METHOD']=='POST') {
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];
    $first     = trim($_POST['first_name']);
    $last      = trim($_POST['last_name']);
    // Block the reserved admin username from being registered
    if (strtolower($username) === strtolower(ADMIN_USERNAME)) {
        $error = "That username is not available. Please choose another.";
    } elseif(empty($username) || empty($email) || empty($password) || empty($first) || empty($last)) {
        $error = "All fields are required.";
    } elseif($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif(strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $pdo = getDBConnection();
        if($pdo) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=?");
            $stmt->execute([$username, $email]);
            if($stmt->fetch()) {
                $error = "Username or email already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type, first_name, last_name) VALUES (?,?,?,?,?,?)");
                if($stmt->execute([$username, $email, $hash, $user_type, $first, $last])) {
                    $success = "Registration successful! You can now log in.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        } else {
            $error = "Database connection error.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - SproutLearn</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
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
            padding: 30px 20px;
        }
        @keyframes gradientBG {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
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
        .bubble:nth-child(1) { width:45px; height:45px; left:8%;  animation-duration:9s;  animation-delay:0s; }
        .bubble:nth-child(2) { width:20px; height:20px; left:22%; animation-duration:11s; animation-delay:2s; }
        .bubble:nth-child(3) { width:55px; height:55px; left:38%; animation-duration:13s; animation-delay:1s; }
        .bubble:nth-child(4) { width:30px; height:30px; left:58%; animation-duration:8s;  animation-delay:3s; }
        .bubble:nth-child(5) { width:48px; height:48px; left:72%; animation-duration:10s; animation-delay:0.5s; }
        .bubble:nth-child(6) { width:22px; height:22px; left:90%; animation-duration:12s; animation-delay:4s; }
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
            padding: 40px 35px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            animation: fadeSlideIn 0.6s ease;
        }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .logo-icon {
            width: 65px; height: 65px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            font-size: 26px; color: white;
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }
        .card h2 {
            text-align: center;
            font-size: 24px;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .card p.subtitle {
            text-align: center;
            color: #64748b;
            font-size: 14px;
            margin-bottom: 25px;
        }
        .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .input-group {
            position: relative;
            margin-bottom: 14px;
        }
        .input-group i {
            position: absolute;
            left: 16px; top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
        }
        .input-group input, .input-group select {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s, box-shadow 0.3s;
            outline: none;
            background: #f8fafc;
            appearance: none;
        }
        .input-group input:focus, .input-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102,126,234,0.12);
            background: #fff;
        }
        .user-type-label {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 6px;
            font-weight: 600;
        }
        .user-type { display: flex; gap: 10px; margin-bottom: 16px; }
        .type-opt {
            flex: 1;
            text-align: center;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            transition: all 0.2s;
            user-select: none;
        }
        .type-opt:hover { border-color: var(--primary); color: var(--primary); }
        .type-opt.selected {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-color: transparent;
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
            margin-top: 4px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 10px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .success {
            background: #dcfce7;
            color: #16a34a;
            padding: 10px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .login-link {
            text-align: center;
            margin-top: 18px;
            font-size: 14px;
            color: #64748b;
        }
        .login-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
        .login-link a:hover { text-decoration: underline; }
        @media (max-width: 480px) { .row-2 { grid-template-columns: 1fr; gap: 0; } }
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
        <h2>Create Account</h2>
        <p class="subtitle">Join SproutLearn and start your journey!</p>

        <?php if($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="row-2">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="first_name" placeholder="First Name" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="last_name" placeholder="Last Name" required>
                </div>
            </div>
            <div class="input-group">
                <i class="fas fa-at"></i>
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password (min 6 characters)" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>

            <p class="user-type-label">I am a:</p>
            <div class="user-type">
                <div class="type-opt selected" onclick="selectType(event, 'student')">
                    <i class="fas fa-graduation-cap"></i> Student
                </div>
                <div class="type-opt" onclick="selectType(event, 'teacher')">
                    <i class="fas fa-chalkboard-teacher"></i> Teacher
                </div>
                <div class="type-opt" onclick="selectType(event, 'parent')">
                    <i class="fas fa-users"></i> Parent
                </div>
            </div>
            <input type="hidden" name="user_type" id="user_type" value="student">

            <button type="submit" class="btn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <p class="login-link">
            Already have an account? <a href="login.php">Sign in</a>
        </p>
    </div>

    <script>
    function selectType(event, type) {
        document.querySelectorAll('.type-opt').forEach(o => o.classList.remove('selected'));
        event.currentTarget.classList.add('selected');
        document.getElementById('user_type').value = type;
    }
    </script>
</body>
</html>
