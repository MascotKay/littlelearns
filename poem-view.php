<?php
require_once 'config.php';
requireLogin();
$id   = (int)($_GET['id'] ?? 0);
$poem = getPoem($id);
if(!$poem) { header('Location: poems.php'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($poem['title']) ?> - SproutLearn</title>
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
        .particle:nth-child(1){width:28px;height:28px;left:6%; animation-duration:10s;animation-delay:0s;}
        .particle:nth-child(2){width:16px;height:16px;left:20%;animation-duration:13s;animation-delay:2s;}
        .particle:nth-child(3){width:44px;height:44px;left:37%;animation-duration:11s;animation-delay:1s;}
        .particle:nth-child(4){width:20px;height:20px;left:55%;animation-duration:9s; animation-delay:3s;}
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
        .poem-container{background:rgba(255,255,255,0.97);backdrop-filter:blur(10px);border-radius:28px;padding:40px;max-width:820px;margin:0 auto;box-shadow:0 20px 50px rgba(0,0,0,0.15);animation:fadeSlideIn 0.6s ease;}
        .poem-cover{width:100%;max-height:320px;object-fit:cover;border-radius:18px;margin-bottom:26px;box-shadow:0 10px 30px rgba(0,0,0,0.12);}
        .poem-title{font-size:28px;font-weight:800;color:var(--text);margin-bottom:12px;line-height:1.3;}
        .poem-meta-row{display:flex;flex-wrap:wrap;align-items:center;gap:16px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid #e2e8f0;}
        .poem-meta-item{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#64748b;}
        .diff-badge{display:inline-block;padding:3px 12px;border-radius:50px;font-size:12px;font-weight:700;}
        .diff-easy{background:#dcfce7;color:#16a34a;}
        .diff-medium{background:#fef3c7;color:#92400e;}
        .diff-hard{background:#fee2e2;color:#dc2626;}
        .poem-text{font-size:17px;line-height:2;color:var(--text);white-space:pre-line;font-family:'Georgia',serif;background:#fafafa;border-radius:16px;padding:24px;border-left:4px solid var(--primary);}
        .poem-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px;padding-top:20px;border-top:1px solid #e2e8f0;}
        .btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;padding:10px 22px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px;border:none;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;}
        .btn:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(102,126,234,0.4);}
        .btn-outline{background:transparent;border:2px solid var(--primary);color:var(--primary);}
        .btn-outline:hover{background:var(--primary);color:white;}
        @media(max-width:768px){.sidebar{width:70px}.main-content{margin-left:70px}.sidebar .nav-item span,.logo h2 span{display:none}.nav-item{justify-content:center}.poem-container{padding:24px}.poem-title{font-size:22px}}
        @media(max-width:480px){.sidebar{width:60px}.main-content{margin-left:60px;padding:15px}.poem-text{font-size:15px}}
    </style>
</head>
<body>
<div class="particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<div class="sidebar"><?php include 'sidebar.php'; ?></div>
<div class="main-content">
    <a href="poems.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Poems</a>

    <div class="poem-container">
        <?php if(!empty($poem['image_path'])): ?>
        <img src="<?= htmlspecialchars($poem['image_path']) ?>" alt="<?= htmlspecialchars($poem['title']) ?>" class="poem-cover">
        <?php endif; ?>

        <h1 class="poem-title"><?= htmlspecialchars($poem['title']) ?></h1>

        <div class="poem-meta-row">
            <span class="poem-meta-item"><i class="fas fa-user-pen"></i> <em><?= htmlspecialchars($poem['author']) ?></em></span>
            <span class="poem-meta-item"><i class="fas fa-tag"></i> <?= ucfirst($poem['category']) ?></span>
            <span class="poem-meta-item"><i class="fas fa-clock"></i> <?= (int)$poem['reading_time'] ?> min read</span>
            <?php if(!empty($poem['difficulty_level'])): ?>
            <span class="diff-badge diff-<?= $poem['difficulty_level'] ?>"><?= ucfirst($poem['difficulty_level']) ?></span>
            <?php endif; ?>
        </div>

        <div class="poem-text"><?= htmlspecialchars($poem['content']) ?></div>

        <div class="poem-actions">
            <a href="poems.php" class="btn"><i class="fas fa-book-open"></i> More Poems</a>
            <a href="quizzes.php" class="btn btn-outline"><i class="fas fa-brain"></i> Take Quiz</a>
        </div>
    </div>
</div>
</body>
</html>
