<?php
require_once 'config.php';
requireLogin();
$user_type = $_SESSION['user_type'];
$msg = null;
if($user_type=='teacher' && $_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['upload_media']) && isset($_FILES['media'])) {
    $lid = (int)$_POST['lesson_id'];
    if(updateLessonMedia($lid, $_FILES['media'])) $msg = ['type'=>'success','text'=>'Media uploaded successfully!'];
    else $msg = ['type'=>'error','text'=>'Upload failed. Allowed: jpg, png, gif, mp4, webm, ogg (max 10MB).'];
}
$courses = getCourses();
$all_lessons = [];
foreach($courses as $c) {
    $mods = getCourseModules($c['id']);
    foreach($mods as $m) {
        $less = getModuleLessons($m['id']);
        foreach($less as $l) {
            $l['course_title'] = $c['title'];
            $all_lessons[] = $l;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lessons - SproutLearn</title>
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
        .particle:nth-child(1) { width:30px;height:30px;left:5%;  animation-duration:10s;animation-delay:0s; }
        .particle:nth-child(2) { width:18px;height:18px;left:20%; animation-duration:13s;animation-delay:2s; }
        .particle:nth-child(3) { width:46px;height:46px;left:35%; animation-duration:11s;animation-delay:1s; }
        .particle:nth-child(4) { width:22px;height:22px;left:52%; animation-duration:9s; animation-delay:3s; }
        .particle:nth-child(5) { width:36px;height:36px;left:68%; animation-duration:14s;animation-delay:0.5s; }
        .particle:nth-child(6) { width:14px;height:14px;left:82%; animation-duration:12s;animation-delay:4s; }
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
        .page-header {
            background:rgba(255,255,255,0.95); backdrop-filter:blur(10px);
            padding:28px 30px; border-radius:22px; margin-bottom:25px;
            box-shadow:0 10px 30px rgba(0,0,0,0.1); animation:fadeSlideIn 0.5s ease;
        }
        .page-header h1 { font-size:24px; color:var(--text); }
        .page-header p  { color:#64748b; margin-top:5px; }
        .alert {
            padding:14px 18px; border-radius:14px; margin-bottom:20px;
            font-size:14px; display:flex; align-items:center; gap:10px;
            animation:fadeSlideIn 0.4s ease;
        }
        .alert-success { background:#dcfce7; color:#16a34a; }
        .alert-error   { background:#fee2e2; color:#dc2626; }
        .upload-card {
            background:rgba(255,255,255,0.97); backdrop-filter:blur(10px);
            border-radius:20px; padding:25px; margin-bottom:25px;
            box-shadow:0 8px 24px rgba(0,0,0,0.1); animation:fadeSlideIn 0.5s ease 0.1s both;
        }
        .upload-card h3 { color:var(--text); margin-bottom:6px; }
        .upload-card p  { color:#64748b; font-size:14px; margin-bottom:14px; }
        .form-group { margin-bottom:14px; }
        select, input[type=file] {
            width:100%; padding:12px 16px; border:2px solid #e2e8f0;
            border-radius:50px; font-family:inherit; font-size:14px;
            background:#f8fafc; outline:none; transition:border-color 0.3s;
            appearance:none;
        }
        select:focus, input[type=file]:focus { border-color:var(--primary); background:#fff; }
        input[type=file] { border-radius:14px; }
        .lessons-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(300px,1fr));
            gap:20px;
        }
        .lesson-card {
            background:rgba(255,255,255,0.97); backdrop-filter:blur(10px);
            border-radius:20px; overflow:hidden;
            box-shadow:0 8px 24px rgba(0,0,0,0.1);
            transition:transform 0.3s, box-shadow 0.3s;
            animation:fadeSlideIn 0.5s ease both;
        }
        .lesson-card:nth-child(1) { animation-delay:0.05s; }
        .lesson-card:nth-child(2) { animation-delay:0.10s; }
        .lesson-card:nth-child(3) { animation-delay:0.15s; }
        .lesson-card:nth-child(4) { animation-delay:0.20s; }
        .lesson-card:nth-child(5) { animation-delay:0.25s; }
        .lesson-card:hover { transform:translateY(-7px); box-shadow:0 18px 36px rgba(0,0,0,0.15); }
        .lesson-card-header {
            padding:4px 0 0;
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            height:5px;
        }
        .lesson-card-body { padding:22px; }
        .lesson-card-body h3 { font-size:15px; color:var(--text); margin-bottom:8px; }
        .lesson-card-body p  { font-size:13px; color:#64748b; line-height:1.5; margin-bottom:12px; }
        .lesson-meta { display:flex; align-items:center; gap:16px; margin-bottom:16px; }
        .meta-tag {
            display:inline-flex; align-items:center; gap:5px;
            font-size:12px; color:#94a3b8; font-weight:500;
        }
        .btn {
            display:inline-flex; align-items:center; gap:8px;
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            color:white; padding:9px 20px; border-radius:50px;
            text-decoration:none; font-weight:600; font-size:13px;
            transition:transform 0.2s, box-shadow 0.2s; border:none; cursor:pointer;
        }
        .btn:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(102,126,234,0.4); }
        @media (max-width:768px) {
            .sidebar{width:70px} .main-content{margin-left:70px}
            .sidebar .nav-item span,.logo h2 span{display:none}
            .nav-item{justify-content:center}
            .lessons-grid{grid-template-columns:repeat(auto-fill,minmax(240px,1fr))}
        }
        @media (max-width:480px) {
            .sidebar{width:60px} .main-content{margin-left:60px; padding:15px}
            .lessons-grid{grid-template-columns:1fr}
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

    <div class="page-header">
        <h1><i class="fas fa-chalkboard-teacher" style="color:var(--primary)"></i> All Lessons</h1>
        <p>Browse and start any available lesson</p>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msg['type'] ?>">
        <i class="fas fa-<?= $msg['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg['text']) ?>
    </div>
    <?php endif; ?>

    <?php if($user_type=='teacher'): ?>
    <div class="upload-card">
        <h3><i class="fas fa-cloud-upload-alt" style="color:var(--primary)"></i> Upload Lesson Media</h3>
        <p>Attach an image or video to any lesson</p>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <select name="lesson_id" required>
                    <option value="">Select lesson...</option>
                    <?php foreach($all_lessons as $l): ?>
                    <option value="<?= (int)$l['id'] ?>"><?= htmlspecialchars($l['course_title'].' — '.$l['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <input type="file" name="media" accept="image/*,video/*" required>
                <small style="color:#94a3b8;font-size:12px;margin-top:4px;display:block;">Max 10MB &mdash; jpg, png, gif, mp4, webm, ogg</small>
            </div>
            <button type="submit" name="upload_media" class="btn">
                <i class="fas fa-upload"></i> Upload Media
            </button>
        </form>
    </div>
    <?php endif; ?>

    <?php if(empty($all_lessons)): ?>
        <div style="background:rgba(255,255,255,0.95);border-radius:20px;padding:40px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);">
            <i class="fas fa-book-reader" style="font-size:48px;color:#cbd5e1;"></i>
            <p style="color:#64748b;margin-top:12px;">No lessons available yet.</p>
        </div>
    <?php else: ?>
    <div class="lessons-grid">
        <?php foreach($all_lessons as $l): ?>
        <div class="lesson-card">
            <div class="lesson-card-header"></div>
            <div class="lesson-card-body">
                <h3><?= htmlspecialchars($l['title']) ?></h3>
                <p><?= htmlspecialchars($l['description']) ?></p>
                <div class="lesson-meta">
                    <span class="meta-tag"><i class="fas fa-book"></i> <?= htmlspecialchars($l['course_title']) ?></span>
                    <span class="meta-tag"><i class="fas fa-clock"></i> <?= (int)$l['duration'] ?> min</span>
                </div>
                <a href="lesson-view.php?id=<?= (int)$l['id'] ?>" class="btn">
                    <i class="fas fa-play"></i> Start Lesson
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
