<?php
require_once 'config.php';
requireLogin();
// Redirect admin — robust fallback in case is_admin session flag is absent
if (isAdmin() || (isset($_SESSION['username']) && $_SESSION['username'] === ADMIN_USERNAME)) {
    header('Location: admin.php'); exit();
}
$user_id   = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$first_name = $_SESSION['first_name'];
$courses    = getCourses();
$progress   = getStudentProgress($user_id);
$attendance = getAttendance($user_id);
$assignments = getAssignments($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - SproutLearn</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --bg: #f1f5f9;
            --white: #fff;
            --text: #1e293b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb, #4facfe, #43e97b);
            background-size: 400% 400%;
            animation: gradientBG 14s ease infinite;
            min-height: 100vh;
        }
        @keyframes gradientBG {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating particles */
        .particles { position: fixed; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:0; overflow:hidden; }
        .particle {
            position: absolute; bottom: -60px;
            background: rgba(255,255,255,0.12);
            border-radius: 50%;
            animation: floatUp linear infinite;
        }
        .particle:nth-child(1)  { width:30px; height:30px; left:5%;  animation-duration:10s; animation-delay:0s; }
        .particle:nth-child(2)  { width:18px; height:18px; left:15%; animation-duration:13s; animation-delay:2s; }
        .particle:nth-child(3)  { width:50px; height:50px; left:28%; animation-duration:11s; animation-delay:1s; }
        .particle:nth-child(4)  { width:22px; height:22px; left:42%; animation-duration:9s;  animation-delay:3s; }
        .particle:nth-child(5)  { width:40px; height:40px; left:55%; animation-duration:14s; animation-delay:0.5s; }
        .particle:nth-child(6)  { width:15px; height:15px; left:68%; animation-duration:12s; animation-delay:4s; }
        .particle:nth-child(7)  { width:35px; height:35px; left:80%; animation-duration:10s; animation-delay:1.5s; }
        .particle:nth-child(8)  { width:20px; height:20px; left:92%; animation-duration:8s;  animation-delay:2.5s; }
        @keyframes floatUp {
            0%   { transform: translateY(0) rotate(0deg); opacity: 0.8; }
            100% { transform: translateY(-110vh) rotate(720deg); opacity: 0; }
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(16px);
            position: fixed;
            height: 100vh;
            padding: 20px 0;
            box-shadow: 2px 0 24px rgba(0,0,0,0.12);
            z-index: 100;
            transition: width 0.3s;
        }
        .main-content { margin-left: 250px; padding: 25px; position: relative; z-index: 1; }

        .logo { padding: 0 20px 30px; text-align: center; }
        .logo h2 { color: var(--primary); font-size: 20px; }
        .logo h2 i { margin-right: 8px; }

        .nav-menu { padding: 0 15px; }
        .nav-item {
            display: flex; align-items: center; gap: 15px;
            padding: 12px 15px; border-radius: 12px;
            color: #64748b; text-decoration: none;
            margin-bottom: 5px; font-weight: 500;
            transition: all 0.3s;
        }
        .nav-item:hover, .nav-item.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.3);
        }
        .nav-item i { width: 20px; text-align: center; }

        /* Header */
        .header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 22px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: fadeSlideIn 0.5s ease;
        }
        .header h1 { font-size: 26px; color: var(--text); }
        .header p  { color: #64748b; margin-top: 5px; }

        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 28px 20px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            animation: fadeSlideIn 0.5s ease both;
        }
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
        .stat-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px;
            font-size: 20px; color: white;
        }
        .stat-value { font-size: 38px; font-weight: 700; color: var(--primary); line-height: 1; }
        .stat-label { color: #64748b; font-size: 14px; margin-top: 6px; font-weight: 500; }

        /* Progress bar */
        .progress-bar-wrap {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            animation: fadeSlideIn 0.5s ease 0.5s both;
        }
        .progress-bar-wrap h3 { color: var(--text); margin-bottom: 12px; }
        .progress-track { background: #e2e8f0; border-radius: 50px; height: 14px; overflow: hidden; }
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50px;
            transition: width 1.2s ease;
        }
        .progress-label { color: #64748b; font-size: 14px; margin-top: 8px; }

        /* Quick action buttons */
        .quick-actions {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: fadeSlideIn 0.5s ease 0.6s both;
        }
        .quick-actions h3 { color: var(--text); margin-bottom: 16px; }
        .btn-wrap { display: flex; flex-wrap: wrap; gap: 12px; }
        .btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .main-content { margin-left: 70px; }
            .sidebar .nav-item span, .logo h2 span { display: none; }
            .logo h2 i { font-size: 22px; margin: 0; }
            .nav-item { justify-content: center; }
            .stat-value { font-size: 28px; }
        }
        @media (max-width: 480px) {
            .sidebar { width: 60px; }
            .main-content { margin-left: 60px; padding: 15px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .header h1 { font-size: 20px; }
        }
    </style>
</head>
<body>

<div class="particles">
    <div class="particle"></div><div class="particle"></div>
    <div class="particle"></div><div class="particle"></div>
    <div class="particle"></div><div class="particle"></div>
    <div class="particle"></div><div class="particle"></div>
</div>

<div class="sidebar"><?php include 'sidebar.php'; ?></div>

<div class="main-content">
    <div class="header">
        <h1>Welcome back, <?= htmlspecialchars($first_name) ?>! 👋</h1>
        <p>Your learning journey continues with SproutLearn 🌱</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-book"></i></div>
            <div class="stat-value"><?= count($courses) ?></div>
            <div class="stat-label">Courses</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value"><?= $progress ?>%</div>
            <div class="stat-label">Progress</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-tasks"></i></div>
            <div class="stat-value"><?= count($assignments) ?></div>
            <div class="stat-label">Assignments</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-value"><?= count($attendance) ?></div>
            <div class="stat-label">Attendance</div>
        </div>
    </div>

    <div class="progress-bar-wrap">
        <h3><i class="fas fa-chart-line" style="color:var(--primary)"></i> Overall Learning Progress</h3>
        <div class="progress-track">
            <div class="progress-fill" id="progressFill" style="width:0%"></div>
        </div>
        <p class="progress-label"><?= $progress ?>% of your learning goals complete</p>
    </div>

    <div class="quick-actions">
        <h3><i class="fas fa-bolt" style="color:var(--primary)"></i> Quick Actions</h3>
        <div class="btn-wrap">
            <a href="poems.php"        class="btn"><i class="fas fa-book-open"></i> Read Poems</a>
            <a href="quizzes.php"           class="btn"><i class="fas fa-brain"></i> Take Quiz</a>
            <a href="courses.php"      class="btn"><i class="fas fa-graduation-cap"></i> Browse Courses</a>
            <a href="lessons.php"      class="btn"><i class="fas fa-chalkboard-teacher"></i> My Lessons</a>
            <a href="assignments.php"  class="btn"><i class="fas fa-tasks"></i> Assignments</a>
            <a href="progress.php"     class="btn"><i class="fas fa-chart-bar"></i> My Progress</a>
        </div>
    </div>
</div>

<script>
    // Animate progress bar on load
    window.addEventListener('load', function() {
        setTimeout(function() {
            document.getElementById('progressFill').style.width = '<?= $progress ?>%';
        }, 300);
    });
</script>

</body>
</html>
