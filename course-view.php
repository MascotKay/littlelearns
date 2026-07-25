<?php
require_once 'config.php';
requireLogin();
$id = (int)($_GET['id'] ?? 0);
$course = getCourse($id);
if(!$course) { header('Location: courses.php'); exit(); }
$modules  = getCourseModules($id);
$progress = getStudentProgress($_SESSION['user_id'], $id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($course['title']) ?> - SproutLearn</title>
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
        .particle:nth-child(1) { width:28px;height:28px;left:8%;  animation-duration:10s;animation-delay:0s; }
        .particle:nth-child(2) { width:16px;height:16px;left:22%; animation-duration:13s;animation-delay:2s; }
        .particle:nth-child(3) { width:44px;height:44px;left:38%; animation-duration:11s;animation-delay:1s; }
        .particle:nth-child(4) { width:20px;height:20px;left:55%; animation-duration:9s; animation-delay:3s; }
        .particle:nth-child(5) { width:36px;height:36px;left:70%; animation-duration:14s;animation-delay:0.5s; }
        .particle:nth-child(6) { width:14px;height:14px;left:85%; animation-duration:12s;animation-delay:4s; }
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
            display:flex; align-items:center; gap:15px;
            padding:12px 15px; border-radius:12px;
            color:#64748b; text-decoration:none;
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
        .course-hero {
            background:rgba(255,255,255,0.95); backdrop-filter:blur(10px);
            border-radius:22px; padding:30px;
            margin-bottom:25px; box-shadow:0 10px 30px rgba(0,0,0,0.1);
            animation:fadeSlideIn 0.5s ease;
        }
        .course-hero h1 { font-size:26px; color:var(--text); margin-bottom:8px; }
        .course-hero p  { color:#64748b; line-height:1.6; margin-bottom:18px; }
        .progress-track { background:#e2e8f0; border-radius:50px; height:12px; overflow:hidden; margin-bottom:8px; }
        .progress-fill {
            height:100%;
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            border-radius:50px; transition:width 1.2s ease;
            width: 0%;
        }
        .progress-label { color:#64748b; font-size:13px; }
        .btn {
            display:inline-flex; align-items:center; gap:8px;
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            color:white; padding:10px 22px; border-radius:50px;
            text-decoration:none; font-weight:600; font-size:14px;
            transition:transform 0.2s, box-shadow 0.2s; border:none; cursor:pointer;
        }
        .btn:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(102,126,234,0.4); }
        .btn-back {
            display:inline-flex; align-items:center; gap:8px;
            color:rgba(255,255,255,0.9); text-decoration:none;
            font-weight:600; margin-bottom:16px; font-size:14px;
            transition:color 0.2s;
        }
        .btn-back:hover { color:white; }
        .module-card {
            background:rgba(255,255,255,0.97); backdrop-filter:blur(10px);
            border-radius:20px; margin-bottom:20px;
            box-shadow:0 8px 24px rgba(0,0,0,0.1);
            overflow:hidden; animation:fadeSlideIn 0.5s ease both;
            transition:transform 0.3s;
        }
        .module-card:hover { transform:translateY(-4px); }
        .module-card:nth-child(1) { animation-delay:0.1s; }
        .module-card:nth-child(2) { animation-delay:0.2s; }
        .module-card:nth-child(3) { animation-delay:0.3s; }
        .module-header {
            padding:18px 22px;
            background:linear-gradient(135deg,rgba(102,126,234,0.08),rgba(118,75,162,0.08));
            border-bottom:1px solid #e2e8f0;
            display:flex; align-items:center; gap:12px;
        }
        .module-header h3 { color:var(--text); font-size:16px; }
        .module-header p  { color:#64748b; font-size:13px; margin-top:2px; }
        .module-icon {
            width:40px; height:40px; flex-shrink:0;
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            border-radius:10px; display:flex; align-items:center; justify-content:center;
            color:white; font-size:16px;
        }
        .lessons-list { padding:8px 0; }
        .lesson-item {
            display:flex; align-items:center; justify-content:space-between;
            padding:14px 22px; border-bottom:1px solid #f1f5f9;
            gap:12px; flex-wrap:wrap; transition:background 0.2s;
        }
        .lesson-item:last-child { border-bottom:none; }
        .lesson-item:hover { background:#f8fafc; }
        .lesson-info { display:flex; align-items:center; gap:10px; flex:1; min-width:0; }
        .lesson-info .check { color:#10b981; font-size:14px; }
        .lesson-info .dot  { color:#cbd5e1; font-size:10px; }
        .lesson-title { font-size:14px; color:var(--text); font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .lesson-meta  { font-size:12px; color:#94a3b8; margin-top:2px; }
        .empty-state {
            background:rgba(255,255,255,0.95); border-radius:20px;
            padding:40px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.1);
        }
        @media (max-width:768px) {
            .sidebar{width:70px} .main-content{margin-left:70px}
            .sidebar .nav-item span,.logo h2 span{display:none}
            .nav-item{justify-content:center}
        }
        @media (max-width:480px) {
            .sidebar{width:60px} .main-content{margin-left:60px; padding:15px}
            .course-hero h1{font-size:20px}
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
    <a href="courses.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Courses</a>

    <div class="course-hero">
        <h1><?= htmlspecialchars($course['title']) ?></h1>
        <p><?= htmlspecialchars($course['description']) ?></p>
        <div class="progress-track">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        <p class="progress-label">Your progress: <strong><?= $progress ?>%</strong> complete</p>
    </div>

    <?php if(empty($modules)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open" style="font-size:48px;color:#cbd5e1;"></i>
            <p style="color:#64748b;margin-top:12px;">No modules found for this course.</p>
        </div>
    <?php else: ?>
        <?php foreach($modules as $m):
            $lessons = getModuleLessons($m['id']);
        ?>
        <div class="module-card">
            <div class="module-header">
                <div class="module-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <h3><?= htmlspecialchars($m['title']) ?></h3>
                    <?php if(!empty($m['description'])): ?>
                    <p><?= htmlspecialchars($m['description']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="lessons-list">
                <?php if(empty($lessons)): ?>
                    <p style="padding:16px 22px;color:#94a3b8;font-size:14px;">No lessons in this module yet.</p>
                <?php else: ?>
                    <?php foreach($lessons as $l):
                        $complete = isLessonComplete($_SESSION['user_id'], $l['id']);
                    ?>
                    <div class="lesson-item">
                        <div class="lesson-info">
                            <?php if($complete): ?>
                                <i class="fas fa-check-circle check"></i>
                            <?php else: ?>
                                <i class="fas fa-circle dot"></i>
                            <?php endif; ?>
                            <div>
                                <div class="lesson-title"><?= htmlspecialchars($l['title']) ?></div>
                                <div class="lesson-meta"><i class="fas fa-clock"></i> <?= (int)$l['duration'] ?> min</div>
                            </div>
                        </div>
                        <a href="lesson-view.php?id=<?= (int)$l['id'] ?>" class="btn" style="padding:8px 18px;font-size:13px;">
                            <?= $complete ? '<i class="fas fa-redo"></i> Review' : '<i class="fas fa-play"></i> Start' ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
window.addEventListener('load', function() {
    setTimeout(function() {
        document.getElementById('progressFill').style.width = '<?= (int)$progress ?>%';
    }, 300);
});
</script>
</body>
</html>
