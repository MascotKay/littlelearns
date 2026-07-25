<?php
require_once 'config.php';
requireLogin();
$courses = getCourses();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Courses - SproutLearn</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#667eea; --secondary:#764ba2; --white:#fff; --text:#1e293b; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI',sans-serif;
            background: linear-gradient(135deg,#667eea,#764ba2,#f093fb,#4facfe,#43e97b);
            background-size: 400% 400%;
            animation: gradientBG 14s ease infinite;
            min-height: 100vh;
        }
        @keyframes gradientBG {
            0%   { background-position:0% 50%; }
            50%  { background-position:100% 50%; }
            100% { background-position:0% 50%; }
        }
        .particles { position:fixed; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:0; overflow:hidden; }
        .particle {
            position:absolute; bottom:-60px;
            background:rgba(255,255,255,0.12);
            border-radius:50%;
            animation:floatUp linear infinite;
        }
        .particle:nth-child(1) { width:30px;height:30px;left:5%;  animation-duration:10s;animation-delay:0s; }
        .particle:nth-child(2) { width:18px;height:18px;left:18%; animation-duration:13s;animation-delay:2s; }
        .particle:nth-child(3) { width:48px;height:48px;left:32%; animation-duration:11s;animation-delay:1s; }
        .particle:nth-child(4) { width:22px;height:22px;left:50%; animation-duration:9s; animation-delay:3s; }
        .particle:nth-child(5) { width:38px;height:38px;left:65%; animation-duration:14s;animation-delay:0.5s; }
        .particle:nth-child(6) { width:15px;height:15px;left:80%; animation-duration:12s;animation-delay:4s; }
        .particle:nth-child(7) { width:28px;height:28px;left:90%; animation-duration:10s;animation-delay:1.5s; }
        @keyframes floatUp {
            0%   { transform:translateY(0) rotate(0deg); opacity:.8; }
            100% { transform:translateY(-110vh) rotate(720deg); opacity:0; }
        }
        .sidebar {
            width:250px; background:rgba(255,255,255,0.97);
            backdrop-filter:blur(16px); position:fixed; height:100vh;
            padding:20px 0; box-shadow:2px 0 24px rgba(0,0,0,0.12);
            z-index:100; transition:width 0.3s;
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
        .page-header {
            background:rgba(255,255,255,0.95); backdrop-filter:blur(10px);
            padding:28px 30px; border-radius:22px; margin-bottom:25px;
            box-shadow:0 10px 30px rgba(0,0,0,0.1);
            animation:fadeSlideIn 0.5s ease;
        }
        .page-header h1 { font-size:24px; color:var(--text); }
        .page-header p  { color:#64748b; margin-top:5px; }
        @keyframes fadeSlideIn {
            from { opacity:0; transform:translateY(20px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .courses-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(300px,1fr));
            gap:22px;
        }
        .course-card {
            background:rgba(255,255,255,0.97); backdrop-filter:blur(10px);
            border-radius:20px; overflow:hidden;
            box-shadow:0 8px 24px rgba(0,0,0,0.1);
            transition:transform 0.3s, box-shadow 0.3s;
            animation:fadeSlideIn 0.5s ease both;
        }
        .course-card:nth-child(1) { animation-delay:0.05s; }
        .course-card:nth-child(2) { animation-delay:0.10s; }
        .course-card:nth-child(3) { animation-delay:0.15s; }
        .course-card:nth-child(4) { animation-delay:0.20s; }
        .course-card:nth-child(5) { animation-delay:0.25s; }
        .course-card:nth-child(6) { animation-delay:0.30s; }
        .course-card:hover { transform:translateY(-8px); box-shadow:0 20px 40px rgba(0,0,0,0.15); }
        .course-card-header {
            padding:20px;
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            position:relative; overflow:hidden;
        }
        .course-card-header::after {
            content:''; position:absolute;
            top:-30px; right:-30px;
            width:100px; height:100px;
            background:rgba(255,255,255,0.1);
            border-radius:50%;
        }
        .course-card-header h3 { color:white; font-size:16px; line-height:1.4; }
        .course-badge {
            display:inline-block; margin-top:8px;
            padding:3px 12px; background:rgba(255,255,255,0.25);
            border-radius:50px; font-size:12px; color:white; font-weight:600;
        }
        .course-body { padding:20px; }
        .course-body p { color:#64748b; font-size:14px; line-height:1.6; margin-bottom:16px; }
        .btn {
            display:inline-flex; align-items:center; gap:8px;
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            color:white; padding:10px 22px; border-radius:50px;
            text-decoration:none; font-weight:600; font-size:14px;
            transition:transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(102,126,234,0.4); }
        @media (max-width:768px) {
            .sidebar{width:70px} .main-content{margin-left:70px}
            .sidebar .nav-item span,.logo h2 span{display:none}
            .nav-item{justify-content:center}
            .courses-grid{grid-template-columns:repeat(auto-fill,minmax(240px,1fr))}
        }
        @media (max-width:480px) {
            .sidebar{width:60px} .main-content{margin-left:60px; padding:15px}
            .courses-grid{grid-template-columns:1fr}
        }
    </style>
</head>
<body>
<div class="particles">
    <div class="particle"></div><div class="particle"></div><div class="particle"></div>
    <div class="particle"></div><div class="particle"></div><div class="particle"></div>
    <div class="particle"></div>
</div>

<div class="sidebar"><?php include 'sidebar.php'; ?></div>
<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-graduation-cap" style="color:var(--primary)"></i> Our Courses</h1>
        <p>Choose a course to begin your learning adventure</p>
    </div>

    <?php if(empty($courses)): ?>
        <div style="background:rgba(255,255,255,0.95);border-radius:20px;padding:40px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);">
            <i class="fas fa-book-open" style="font-size:48px;color:#cbd5e1;"></i>
            <p style="color:#64748b;margin-top:12px;">No courses available yet.</p>
        </div>
    <?php else: ?>
    <div class="courses-grid">
        <?php foreach($courses as $c): ?>
        <div class="course-card">
            <div class="course-card-header">
                <h3><?= htmlspecialchars($c['title']) ?></h3>
                <span class="course-badge"><?= htmlspecialchars($c['category'] ?? 'General') ?></span>
            </div>
            <div class="course-body">
                <p><?= htmlspecialchars($c['description']) ?></p>
                <a href="course-view.php?id=<?= (int)$c['id'] ?>" class="btn">
                    <i class="fas fa-arrow-right"></i> View Course
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
