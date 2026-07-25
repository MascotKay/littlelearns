<?php
require_once 'config.php';
requireLogin();
$user_type = $_SESSION['user_type'];
$msg = null;
if($user_type=='teacher' && $_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['upload_q_image']) && isset($_FILES['q_image'])) {
    $msg = updateQuizQuestionImage((int)$_POST['question_id'], $_FILES['q_image'])
        ? ['type'=>'success','text'=>'Question image uploaded!']
        : ['type'=>'error',  'text'=>'Upload failed. Image files only (max 10MB).'];
}
$quizzes     = getAllQuizzes();
$attempts    = getQuizAttempts($_SESSION['user_id']);
$attempt_map = [];
foreach($attempts as $a) $attempt_map[$a['quiz_id']] = $a;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quizzes - SproutLearn</title>
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
        .particle:nth-child(1){width:30px;height:30px;left:5%; animation-duration:10s;animation-delay:0s;}
        .particle:nth-child(2){width:18px;height:18px;left:20%;animation-duration:13s;animation-delay:2s;}
        .particle:nth-child(3){width:46px;height:46px;left:36%;animation-duration:11s;animation-delay:1s;}
        .particle:nth-child(4){width:22px;height:22px;left:52%;animation-duration:9s; animation-delay:3s;}
        .particle:nth-child(5){width:38px;height:38px;left:68%;animation-duration:14s;animation-delay:0.5s;}
        .particle:nth-child(6){width:15px;height:15px;left:84%;animation-duration:12s;animation-delay:4s;}
        @keyframes floatUp{0%{transform:translateY(0) rotate(0deg);opacity:.8;}100%{transform:translateY(-110vh) rotate(720deg);opacity:0;}}
        .sidebar{width:250px;background:rgba(255,255,255,0.97);backdrop-filter:blur(16px);position:fixed;height:100vh;padding:20px 0;box-shadow:2px 0 24px rgba(0,0,0,0.12);z-index:100;}
        .main-content{margin-left:250px;padding:25px;position:relative;z-index:1;}
        .logo{padding:0 20px 30px;text-align:center;} .logo h2{color:var(--primary);font-size:20px;}
        .nav-menu{padding:0 15px;}
        .nav-item{display:flex;align-items:center;gap:15px;padding:12px 15px;border-radius:12px;color:#64748b;text-decoration:none;margin-bottom:5px;font-weight:500;transition:all 0.3s;}
        .nav-item:hover,.nav-item.active{background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;transform:translateX(5px);box-shadow:0 4px 12px rgba(102,126,234,0.3);}
        .nav-item i{width:20px;text-align:center;}
        @keyframes fadeSlideIn{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
        .page-header{background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);padding:28px 30px;border-radius:22px;margin-bottom:25px;box-shadow:0 10px 30px rgba(0,0,0,0.1);animation:fadeSlideIn 0.5s ease;}
        .page-header h1{font-size:24px;color:var(--text);}
        .page-header p{color:#64748b;margin-top:5px;}
        .alert{padding:14px 18px;border-radius:14px;margin-bottom:20px;font-size:14px;display:flex;align-items:center;gap:10px;animation:fadeSlideIn 0.4s ease;}
        .alert-success{background:#dcfce7;color:#16a34a;} .alert-error{background:#fee2e2;color:#dc2626;}
        .upload-card{background:rgba(255,255,255,0.97);backdrop-filter:blur(10px);border-radius:20px;padding:25px;margin-bottom:22px;box-shadow:0 8px 24px rgba(0,0,0,0.1);animation:fadeSlideIn 0.5s ease 0.1s both;}
        .upload-card h3{color:var(--text);margin-bottom:6px;}
        .upload-card p{color:#64748b;font-size:14px;margin-bottom:14px;}
        select,input[type=file]{width:100%;padding:12px 16px;margin:8px 0;border:2px solid #e2e8f0;border-radius:50px;font-family:inherit;font-size:14px;outline:none;background:#f8fafc;transition:border-color 0.3s;appearance:none;}
        select:focus,input[type=file]:focus{border-color:var(--primary);background:#fff;}
        input[type=file]{border-radius:14px;}
        .quiz-card{background:rgba(255,255,255,0.97);backdrop-filter:blur(10px);border-radius:20px;overflow:hidden;margin-bottom:20px;box-shadow:0 8px 24px rgba(0,0,0,0.1);transition:transform 0.3s,box-shadow 0.3s;animation:fadeSlideIn 0.5s ease both;}
        .quiz-card:nth-child(1){animation-delay:0.05s;} .quiz-card:nth-child(2){animation-delay:0.10s;} .quiz-card:nth-child(3){animation-delay:0.15s;}
        .quiz-card:hover{transform:translateY(-5px);box-shadow:0 18px 36px rgba(0,0,0,0.15);}
        .quiz-card-accent{height:5px;background:linear-gradient(135deg,var(--primary),var(--secondary));}
        .quiz-card-body{padding:22px;}
        .quiz-card-body h3{color:var(--text);font-size:17px;margin-bottom:8px;}
        .quiz-card-body p{color:#64748b;font-size:14px;line-height:1.6;margin-bottom:12px;}
        .quiz-meta{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:16px;}
        .meta-tag{display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#94a3b8;font-weight:500;}
        .score-display{display:flex;align-items:center;gap:10px;margin-bottom:14px;}
        .score-badge{padding:6px 16px;border-radius:50px;font-weight:700;font-size:15px;}
        .score-pass{background:#dcfce7;color:#16a34a;} .score-fail{background:#fee2e2;color:#dc2626;}
        .btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;padding:10px 22px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px;border:none;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;}
        .btn:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(102,126,234,0.4);}
        .btn-outline{background:transparent;border:2px solid var(--primary);color:var(--primary);}
        .btn-outline:hover{background:var(--primary);color:white;}
        @media(max-width:768px){.sidebar{width:70px}.main-content{margin-left:70px}.sidebar .nav-item span,.logo h2 span{display:none}.nav-item{justify-content:center}}
        @media(max-width:480px){.sidebar{width:60px}.main-content{margin-left:60px;padding:15px}}
    </style>
</head>
<body>
<div class="particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<div class="sidebar"><?php include 'sidebar.php'; ?></div>
<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-brain" style="color:var(--primary)"></i> Quizzes</h1>
        <p>Test your knowledge and track your scores</p>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msg['type'] ?>">
        <i class="fas fa-<?= $msg['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg['text']) ?>
    </div>
    <?php endif; ?>

    <?php if($user_type=='teacher'): ?>
    <div class="upload-card">
        <h3><i class="fas fa-image" style="color:var(--primary)"></i> Upload Question Image</h3>
        <p>Attach an image to a quiz question</p>
        <form method="POST" enctype="multipart/form-data">
            <select name="question_id" required>
                <option value="">Select question...</option>
                <?php foreach($quizzes as $qz): foreach(getQuizQuestions($qz['id']) as $qq): ?>
                <option value="<?= (int)$qq['id'] ?>"><?= htmlspecialchars($qz['title'].' — '.substr($qq['question_text'],0,50)) ?></option>
                <?php endforeach; endforeach; ?>
            </select>
            <input type="file" name="q_image" accept="image/*" required>
            <small style="color:#94a3b8;font-size:12px;">Image files only (max 10MB)</small><br><br>
            <button type="submit" name="upload_q_image" class="btn"><i class="fas fa-upload"></i> Upload Image</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if(empty($quizzes)): ?>
    <div style="background:rgba(255,255,255,0.95);border-radius:20px;padding:40px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);">
        <i class="fas fa-question-circle" style="font-size:48px;color:#cbd5e1;"></i>
        <p style="color:#64748b;margin-top:12px;">No quizzes available yet.</p>
    </div>
    <?php else: ?>
    <?php foreach($quizzes as $q):
        $has   = isset($attempt_map[$q['id']]);
        $score = $has ? round($attempt_map[$q['id']]['score'],1) : null;
        $passed = $has && $score >= $q['passing_score'];
    ?>
    <div class="quiz-card">
        <div class="quiz-card-accent"></div>
        <div class="quiz-card-body">
            <h3><?= htmlspecialchars($q['title']) ?></h3>
            <p><?= htmlspecialchars($q['description']) ?></p>
            <div class="quiz-meta">
                <span class="meta-tag"><i class="fas fa-list-ol"></i> <?= (int)$q['question_count'] ?> questions</span>
                <span class="meta-tag"><i class="fas fa-clock"></i> <?= (int)$q['time_limit'] ?> min</span>
                <span class="meta-tag"><i class="fas fa-trophy"></i> Pass: <?= (int)$q['passing_score'] ?>%</span>
            </div>
            <?php if($has): ?>
            <div class="score-display">
                <span class="score-badge <?= $passed?'score-pass':'score-fail' ?>"><?= $score ?>%</span>
                <span style="color:#64748b;font-size:14px;"><?= $passed?'✅ Passed':'❌ Not passed yet' ?></span>
            </div>
            <a href="quiz-start.php?id=<?= (int)$q['id'] ?>" class="btn btn-outline">
                <i class="fas fa-redo"></i> Retake Quiz
            </a>
            <?php else: ?>
            <a href="quiz-start.php?id=<?= (int)$q['id'] ?>" class="btn">
                <i class="fas fa-play"></i> Start Quiz
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
