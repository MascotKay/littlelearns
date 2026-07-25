<?php
require_once 'config.php';
requireLogin();
$attempt_id = (int)($_GET['attempt_id'] ?? 0);
$pdo  = getDBConnection();
if (!$pdo) { header('Location: quizzes.php'); exit(); }
$stmt = $pdo->prepare("SELECT qa.*, q.title, q.passing_score FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id=q.id WHERE qa.id=? AND qa.student_id=?");
$stmt->execute([$attempt_id, $_SESSION['user_id']]);
$attempt = $stmt->fetch();
if(!$attempt) { header('Location: quizzes.php'); exit(); }
$passed   = $attempt['score'] >= $attempt['passing_score'];
$score    = round($attempt['score'], 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Results - SproutLearn</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#667eea;--secondary:#764ba2;--white:#fff;--text:#1e293b;}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{
            font-family:'Segoe UI',sans-serif;
            background:linear-gradient(135deg,#667eea,#764ba2,#f093fb,#4facfe,#43e97b);
            background-size:400% 400%;
            animation:gradientBG 14s ease infinite;
            min-height:100vh;
            display:flex;
        }
        @keyframes gradientBG{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
        .particles{position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0;overflow:hidden;}
        .particle{position:absolute;bottom:-60px;background:rgba(255,255,255,0.12);border-radius:50%;animation:floatUp linear infinite;}
        .particle:nth-child(1){width:28px;height:28px;left:5%; animation-duration:10s;animation-delay:0s;}
        .particle:nth-child(2){width:16px;height:16px;left:20%;animation-duration:13s;animation-delay:2s;}
        .particle:nth-child(3){width:44px;height:44px;left:38%;animation-duration:11s;animation-delay:1s;}
        .particle:nth-child(4){width:20px;height:20px;left:56%;animation-duration:9s; animation-delay:3s;}
        .particle:nth-child(5){width:36px;height:36px;left:72%;animation-duration:14s;animation-delay:0.5s;}
        .particle:nth-child(6){width:14px;height:14px;left:87%;animation-duration:12s;animation-delay:4s;}
        @keyframes floatUp{0%{transform:translateY(0) rotate(0deg);opacity:.8;}100%{transform:translateY(-110vh) rotate(720deg);opacity:0;}}
        .sidebar{width:250px;background:rgba(255,255,255,0.97);backdrop-filter:blur(16px);position:fixed;height:100vh;padding:20px 0;box-shadow:2px 0 24px rgba(0,0,0,0.12);z-index:100;}
        .main-content{margin-left:250px;flex:1;display:flex;align-items:center;justify-content:center;padding:25px;position:relative;z-index:1;}
        .logo{padding:0 20px 30px;text-align:center;} .logo h2{color:var(--primary);font-size:20px;}
        .nav-menu{padding:0 15px;}
        .nav-item{display:flex;align-items:center;gap:15px;padding:12px 15px;border-radius:12px;color:#64748b;text-decoration:none;margin-bottom:5px;font-weight:500;transition:all 0.3s;}
        .nav-item:hover,.nav-item.active{background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;transform:translateX(5px);box-shadow:0 4px 12px rgba(102,126,234,0.3);}
        .nav-item i{width:20px;text-align:center;}
        @keyframes popIn{0%{opacity:0;transform:scale(0.7);}70%{transform:scale(1.05);}100%{opacity:1;transform:scale(1);}}
        @keyframes fadeSlideIn{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
        .result-card{background:rgba(255,255,255,0.97);backdrop-filter:blur(14px);border-radius:30px;padding:50px 40px;text-align:center;max-width:480px;width:100%;box-shadow:0 25px 60px rgba(0,0,0,0.2);animation:fadeSlideIn 0.6s ease;}
        .result-emoji{font-size:64px;margin-bottom:12px;animation:popIn 0.7s ease 0.2s both;}
        .result-title{font-size:26px;font-weight:800;color:var(--text);margin-bottom:6px;}
        .result-subtitle{font-size:14px;color:#64748b;margin-bottom:28px;}
        /* Circular score ring */
        .score-ring{position:relative;width:140px;height:140px;margin:0 auto 24px;}
        .score-ring svg{width:140px;height:140px;transform:rotate(-90deg);}
        .score-ring circle{fill:none;stroke-width:12;stroke-linecap:round;}
        .ring-bg{stroke:#e2e8f0;}
        .ring-fg{stroke:url(#scoreGrad);stroke-dasharray:0 376;transition:stroke-dasharray 1.2s ease;}
        .ring-label{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;}
        .ring-label .score-num{font-size:28px;font-weight:800;color:var(--primary);line-height:1;}
        .ring-label .score-unit{font-size:12px;color:#94a3b8;}
        .stats-row{display:flex;justify-content:center;gap:24px;margin-bottom:28px;flex-wrap:wrap;}
        .stat-item{text-align:center;}
        .stat-item .val{font-size:22px;font-weight:700;color:var(--text);}
        .stat-item .lbl{font-size:12px;color:#94a3b8;margin-top:2px;}
        .result-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 20px;border-radius:50px;font-weight:700;font-size:14px;margin-bottom:24px;}
        .badge-pass{background:#dcfce7;color:#16a34a;}
        .badge-fail{background:#fee2e2;color:#dc2626;}
        .passing-note{font-size:12px;color:#94a3b8;margin-bottom:24px;}
        .btn-row{display:flex;flex-wrap:wrap;gap:12px;justify-content:center;}
        .btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;padding:11px 24px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px;border:none;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;}
        .btn:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(102,126,234,0.4);}
        .btn-outline{background:transparent;border:2px solid var(--primary);color:var(--primary);}
        .btn-outline:hover{background:var(--primary);color:white;}
        @media(max-width:768px){.sidebar{width:70px}.main-content{margin-left:70px}.sidebar .nav-item span,.logo h2 span{display:none}.nav-item{justify-content:center}.result-card{padding:36px 24px}}
        @media(max-width:480px){.sidebar{width:60px}.main-content{margin-left:60px;padding:15px}}
    </style>
</head>
<body>
<div class="particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<div class="sidebar"><?php include 'sidebar.php'; ?></div>
<div class="main-content">
    <div class="result-card">
        <div class="result-emoji"><?= $passed ? '🎉' : '📚' ?></div>
        <h1 class="result-title"><?= $passed ? 'Congratulations!' : 'Keep Learning!' ?></h1>
        <p class="result-subtitle"><?= htmlspecialchars($attempt['title']) ?></p>

        <!-- Circular score ring -->
        <div class="score-ring">
            <svg viewBox="0 0 140 140">
                <defs>
                    <linearGradient id="scoreGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#667eea"/>
                        <stop offset="100%" style="stop-color:#764ba2"/>
                    </linearGradient>
                </defs>
                <circle class="ring-bg" cx="70" cy="70" r="60"/>
                <circle class="ring-fg" id="ringFg" cx="70" cy="70" r="60"/>
            </svg>
            <div class="ring-label">
                <div class="score-num"><?= $score ?></div>
                <div class="score-unit">%</div>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-item">
                <div class="val"><?= (int)$attempt['correct_answers'] ?></div>
                <div class="lbl">Correct</div>
            </div>
            <div class="stat-item">
                <div class="val"><?= (int)$attempt['total_questions'] ?></div>
                <div class="lbl">Total</div>
            </div>
            <div class="stat-item">
                <div class="val"><?= (int)$attempt['total_questions'] - (int)$attempt['correct_answers'] ?></div>
                <div class="lbl">Incorrect</div>
            </div>
        </div>

        <div class="result-badge <?= $passed?'badge-pass':'badge-fail' ?>">
            <i class="fas fa-<?= $passed?'check-circle':'times-circle' ?>"></i>
            <?= $passed ? 'Passed' : 'Not passed' ?>
        </div>
        <p class="passing-note">Passing score: <?= (int)$attempt['passing_score'] ?>%</p>

        <div class="btn-row">
            <a href="quizzes.php" class="btn"><i class="fas fa-list"></i> All Quizzes</a>
            <a href="poems.php"   class="btn btn-outline"><i class="fas fa-book-open"></i> Read Poems</a>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function() {
    var score = <?= $score ?>;
    var circumference = 2 * Math.PI * 60; // ~376.99
    var dash = (score / 100) * circumference;
    setTimeout(function() {
        document.getElementById('ringFg').style.strokeDasharray = dash + ' ' + circumference;
    }, 400);
});
</script>
</body>
</html>
