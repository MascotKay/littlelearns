<?php
require_once 'config.php';
requireLogin();
$id = (int)($_GET['id'] ?? 0);
$lesson = getLesson($id);
if(!$lesson) { header('Location: lessons.php'); exit(); }
$user_id = $_SESSION['user_id'];
if(isset($lesson['course_id'])) markAttendance($user_id, $lesson['course_id'], 'present');
$is_complete = isLessonComplete($user_id, $id);
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['complete'])) {
    markLessonComplete($user_id, $id);
    header("Location: lesson-view.php?id=$id"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($lesson['title']) ?> - SproutLearn</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#667eea; --secondary:#764ba2; --white:#fff; --text:#1e293b; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI',sans-serif;
            background:linear-gradient(135deg,#667eea,#764ba2,#f093fb,#4facfe,#43e97b);
            background-size:400% 400%;
            animation:gradientBG 14s ease infinite;
            min-height:100vh;
        }
        @keyframes gradientBG {
            0%   { background-position:0% 50%; }
            50%  { background-position:100% 50%; }
            100% { background-position:0% 50%; }
        }
        .particles { position:fixed; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:0; overflow:hidden; }
        .particle { position:absolute; bottom:-60px; background:rgba(255,255,255,0.12); border-radius:50%; animation:floatUp linear infinite; }
        .particle:nth-child(1) { width:28px;height:28px;left:6%;  animation-duration:10s;animation-delay:0s; }
        .particle:nth-child(2) { width:16px;height:16px;left:20%; animation-duration:13s;animation-delay:2s; }
        .particle:nth-child(3) { width:44px;height:44px;left:36%; animation-duration:11s;animation-delay:1s; }
        .particle:nth-child(4) { width:20px;height:20px;left:54%; animation-duration:9s; animation-delay:3s; }
        .particle:nth-child(5) { width:35px;height:35px;left:70%; animation-duration:14s;animation-delay:0.5s; }
        .particle:nth-child(6) { width:14px;height:14px;left:86%; animation-duration:12s;animation-delay:4s; }
        @keyframes floatUp {
            0%   { transform:translateY(0) rotate(0deg); opacity:.8; }
            100% { transform:translateY(-110vh) rotate(720deg); opacity:0; }
        }
        .sidebar {
            width:250px; background:rgba(255,255,255,0.97);
            backdrop-filter:blur(16px); position:fixed; height:100vh;
            padding:20px 0; box-shadow:2px 0 24px rgba(0,0,0,0.12); z-index:100;
        }
        .main-content { margin-left:250px; padding:25px; position:relative; z-index:1; }
        .logo { padding:0 20px 30px; text-align:center; }
        .logo h2 { color:var(--primary); font-size:20px; }
        .nav-menu { padding:0 15px; }
        .nav-item {
            display:flex; align-items:center; gap:15px; padding:12px 15px;
            border-radius:12px; color:#64748b; text-decoration:none;
            margin-bottom:5px; font-weight:500; transition:all 0.3s;
        }
        .nav-item:hover, .nav-item.active {
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            color:white; transform:translateX(5px);
            box-shadow:0 4px 12px rgba(102,126,234,0.3);
        }
        .nav-item i { width:20px; text-align:center; }
        @keyframes fadeSlideIn {
            from { opacity:0; transform:translateY(20px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .btn-back {
            display:inline-flex; align-items:center; gap:8px;
            color:rgba(255,255,255,0.9); text-decoration:none;
            font-weight:600; margin-bottom:16px; font-size:14px;
            transition:color 0.2s;
        }
        .btn-back:hover { color:white; }
        .lesson-container {
            background:rgba(255,255,255,0.97); backdrop-filter:blur(10px);
            border-radius:28px; padding:40px; max-width:900px; margin:0 auto;
            box-shadow:0 20px 50px rgba(0,0,0,0.15);
            animation:fadeSlideIn 0.6s ease;
        }
        .lesson-title {
            font-size:26px; color:var(--primary); margin-bottom:10px;
            display:flex; align-items:center; gap:12px;
        }
        .lesson-desc { color:#64748b; line-height:1.6; margin-bottom:28px; font-size:15px; }
        .media-container {
            margin:24px 0; text-align:center;
            border-radius:20px; overflow:hidden;
            box-shadow:0 10px 30px rgba(0,0,0,0.12);
        }
        .media-container img,
        .media-container video {
            max-width:100%; display:block; border-radius:20px;
        }
        .lesson-content {
            font-size:16px; line-height:1.8; color:var(--text);
            margin:28px 0; padding:24px;
            background:#f8fafc; border-radius:16px;
            border-left:4px solid var(--primary);
        }
        .complete-section { text-align:center; padding-top:20px; border-top:1px solid #e2e8f0; margin-top:28px; }
        .complete-badge {
            display:inline-flex; align-items:center; gap:10px;
            background:#dcfce7; color:#16a34a;
            padding:14px 28px; border-radius:50px;
            font-weight:700; font-size:16px;
        }
        .btn {
            display:inline-flex; align-items:center; gap:8px;
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            color:white; padding:14px 32px; border-radius:50px;
            font-weight:700; font-size:16px;
            border:none; cursor:pointer;
            transition:transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(102,126,234,0.4); }
        .btn:active { transform:translateY(0); }
        @media (max-width:768px) {
            .sidebar{width:70px} .main-content{margin-left:70px}
            .sidebar .nav-item span,.logo h2 span{display:none}
            .nav-item{justify-content:center}
            .lesson-container{padding:24px}
            .lesson-title{font-size:20px}
        }
        @media (max-width:480px) {
            .sidebar{width:60px} .main-content{margin-left:60px; padding:15px}
        }
    </style>
</head>
<body>
<div class="particles">
    <div class="particle"></div><div class="particle"></div><div class="particle"></div>
    <div class="particle"></div><div class="particle"></div><div class="particle"></div>
</div>

<div class="sidebar"><?php include 'sidebar.php'; ?></div>
<div class="main-content">
    <a href="lessons.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Lessons</a>

    <div class="lesson-container">
        <h1 class="lesson-title">
            <i class="fas fa-book"></i> <?= htmlspecialchars($lesson['title']) ?>
        </h1>
        <p class="lesson-desc"><?= htmlspecialchars($lesson['description']) ?></p>

        <?php if(!empty($lesson['media_path'])): ?>
        <div class="media-container">
            <?php if($lesson['media_type'] === 'image'): ?>
                <img src="<?= htmlspecialchars($lesson['media_path']) ?>" alt="Lesson media">
            <?php else: ?>
                <video controls>
                    <source src="<?= htmlspecialchars($lesson['media_path']) ?>">
                    Your browser does not support the video tag.
                </video>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="lesson-content">
            <?php
            // Render lesson content safely:
            // If it contains HTML tags → strip dangerous ones and render as HTML
            // If it is plain text → escape and convert newlines to <br>
            $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><blockquote><hr><span><a>';
            $plain   = strip_tags($lesson['content'] ?? '');
            if (($lesson['content'] ?? '') !== $plain) {
                // Has HTML — render with safe tag whitelist
                echo strip_tags($lesson['content'], $allowed);
            } else {
                // Plain text — escape and add line breaks
                echo nl2br(htmlspecialchars($lesson['content'] ?? ''));
            }
            ?>
        </div>

        <div class="complete-section">
            <?php if($is_complete): ?>
                <div class="complete-badge">
                    <i class="fas fa-check-circle"></i> Lesson Completed!
                </div>
                <p style="color:#64748b;margin-top:12px;font-size:14px;">
                    Great work! <a href="lessons.php" style="color:var(--primary);font-weight:600;">Browse more lessons</a>
                </p>
            <?php else: ?>
                <form method="POST">
                    <button type="submit" name="complete" class="btn">
                        <i class="fas fa-check"></i> Mark as Complete
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
