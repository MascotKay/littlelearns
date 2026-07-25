<?php
require_once 'config.php';
requireLogin();
$quiz_id   = (int)($_GET['id'] ?? 1);
$quiz      = getQuizById($quiz_id);
if(!$quiz) { header('Location: quizzes.php'); exit(); }
$questions = getQuizQuestions($quiz_id);

if($_SERVER['REQUEST_METHOD']=='POST') {
    $score = 0; $correct = 0;
    foreach($questions as $q) {
        $ans = $_POST['q'.$q['id']] ?? '';
        foreach($q['options'] as $opt) {
            if($opt['is_correct'] && $opt['id']==$ans) { $score += $q['points']; $correct++; break; }
        }
    }
    $total_points = array_sum(array_column($questions,'points'));
    $percentage   = $total_points > 0 ? ($score / $total_points) * 100 : 0;
    $attempt_id   = saveQuizAttempt($quiz_id, $_SESSION['user_id'], $percentage, count($questions), $correct);
    header("Location: quiz-results.php?attempt_id=$attempt_id"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($quiz['title']) ?> - SproutLearn</title>
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
        .main-content{margin-left:250px;padding:25px;position:relative;z-index:1;}
        .logo{padding:0 20px 30px;text-align:center;} .logo h2{color:var(--primary);font-size:20px;}
        .nav-menu{padding:0 15px;}
        .nav-item{display:flex;align-items:center;gap:15px;padding:12px 15px;border-radius:12px;color:#64748b;text-decoration:none;margin-bottom:5px;font-weight:500;transition:all 0.3s;}
        .nav-item:hover,.nav-item.active{background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;transform:translateX(5px);box-shadow:0 4px 12px rgba(102,126,234,0.3);}
        .nav-item i{width:20px;text-align:center;}
        @keyframes fadeSlideIn{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
        .btn-back{display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,0.9);text-decoration:none;font-weight:600;margin-bottom:16px;font-size:14px;transition:color 0.2s;}
        .btn-back:hover{color:white;}
        .quiz-container{background:rgba(255,255,255,0.97);backdrop-filter:blur(10px);border-radius:26px;padding:36px;max-width:820px;margin:0 auto;box-shadow:0 20px 50px rgba(0,0,0,0.15);animation:fadeSlideIn 0.6s ease;}
        .quiz-title{font-size:24px;color:var(--primary);margin-bottom:8px;display:flex;align-items:center;gap:12px;}
        .quiz-meta{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:28px;padding-bottom:20px;border-bottom:1px solid #e2e8f0;}
        .meta-tag{display:inline-flex;align-items:center;gap:5px;font-size:13px;color:#94a3b8;font-weight:500;}
        .question-card{background:#f8fafc;border-radius:18px;padding:22px;margin-bottom:20px;border:2px solid transparent;transition:border-color 0.2s;}
        .question-card:has(input:checked){border-color:rgba(102,126,234,0.25);}
        .question-num{font-size:12px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;}
        .question-text{font-size:16px;font-weight:600;color:var(--text);margin-bottom:14px;line-height:1.5;}
        .question-points{font-size:12px;color:#94a3b8;}
        .question-img{max-width:100%;max-height:240px;border-radius:12px;margin-bottom:14px;display:block;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
        .option-label{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:12px;cursor:pointer;margin-bottom:8px;border:2px solid #e2e8f0;transition:all 0.2s;font-size:14px;color:var(--text);}
        .option-label:hover{border-color:var(--primary);background:rgba(102,126,234,0.05);}
        .option-label input[type=radio]{accent-color:var(--primary);width:16px;height:16px;flex-shrink:0;}
        .submit-area{text-align:center;padding-top:20px;border-top:1px solid #e2e8f0;margin-top:10px;}
        .btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;padding:14px 36px;border-radius:50px;font-weight:700;font-size:16px;border:none;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;}
        .btn:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(102,126,234,0.4);}
        .btn:active{transform:translateY(0);}
        @media(max-width:768px){.sidebar{width:70px}.main-content{margin-left:70px}.sidebar .nav-item span,.logo h2 span{display:none}.nav-item{justify-content:center}.quiz-container{padding:22px}}
        @media(max-width:480px){.sidebar{width:60px}.main-content{margin-left:60px;padding:15px}.quiz-title{font-size:18px}}
    </style>
</head>
<body>
<div class="particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<div class="sidebar"><?php include 'sidebar.php'; ?></div>
<div class="main-content">
    <a href="quizzes.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Quizzes</a>

    <div class="quiz-container">
        <h1 class="quiz-title"><i class="fas fa-brain"></i> <?= htmlspecialchars($quiz['title']) ?></h1>
        <?php if(!empty($quiz['description'])): ?>
        <p style="color:#64748b;font-size:14px;margin-bottom:14px;"><?= htmlspecialchars($quiz['description']) ?></p>
        <?php endif; ?>
        <div class="quiz-meta">
            <span class="meta-tag"><i class="fas fa-list-ol"></i> <?= count($questions) ?> questions</span>
            <span class="meta-tag"><i class="fas fa-clock"></i> <?= (int)$quiz['time_limit'] ?> min</span>
            <span class="meta-tag"><i class="fas fa-trophy"></i> Passing score: <?= (int)$quiz['passing_score'] ?>%</span>
        </div>

        <?php if(empty($questions)): ?>
        <p style="color:#94a3b8;text-align:center;padding:20px;">No questions found for this quiz.</p>
        <?php else: ?>
        <form method="POST">
            <?php foreach($questions as $idx=>$q): ?>
            <div class="question-card">
                <p class="question-num">Question <?= ($idx+1) ?> <span class="question-points">&bull; <?= (int)$q['points'] ?> pt<?= $q['points']!=1?'s':'' ?></span></p>
                <p class="question-text"><?= htmlspecialchars($q['question_text']) ?></p>
                <?php if(!empty($q['image_path'])): ?>
                    <img src="<?= htmlspecialchars($q['image_path']) ?>" alt="Question image" class="question-img">
                <?php endif; ?>
                <?php foreach($q['options'] as $oidx=>$opt): ?>
                <label class="option-label">
                    <input type="radio" name="q<?= (int)$q['id'] ?>" value="<?= (int)$opt['id'] ?>" <?= $oidx===0?'required':''?> onchange="this.form.querySelectorAll('[name=q<?= (int)$q['id'] ?>]').forEach(r=>r.removeAttribute('required'))">
                    <?= htmlspecialchars($opt['option_text']) ?>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
            <div class="submit-area">
                <button type="submit" class="btn"><i class="fas fa-check"></i> Submit Quiz</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
