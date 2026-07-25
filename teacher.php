<?php
require_once 'config.php';
requireLogin();
if($_SESSION['user_type'] !== 'teacher') { header('Location: home.php'); exit(); }
$teacher_id = $_SESSION['user_id'];
$msg = null;

if($_SERVER['REQUEST_METHOD']=='POST') {
    if(isset($_POST['post_assignment'])) {
        $ok = createAssignment(
            trim($_POST['title']), trim($_POST['description']),
            $_POST['due_date'],
            isset($_FILES['attachment']) ? $_FILES['attachment'] : null
        );
        $msg = $ok
            ? ['type'=>'success','text'=>'Assignment posted successfully!']
            : ['type'=>'error',  'text'=>'Error posting assignment.'];
    }
    elseif(isset($_POST['upload_lesson_media'])) {
        $ok = updateLessonMedia((int)$_POST['lesson_id'], $_FILES['lesson_media']);
        $msg = $ok
            ? ['type'=>'success','text'=>'Lesson media uploaded successfully!']
            : ['type'=>'error',  'text'=>'Upload failed. Check file type (image/video) and size (max 10MB).'];
    }
    elseif(isset($_POST['send_query'])) {
        $ok = sendTeacherQuery($teacher_id, (int)$_POST['student_id'], trim($_POST['message']));
        $msg = $ok
            ? ['type'=>'success','text'=>'Message sent!']
            : ['type'=>'error',  'text'=>'Error sending message.'];
    }
    elseif(isset($_POST['grade_submission'])) {
        $ok = gradeAssignment((int)$_POST['submission_id'], $_POST['grade'], $_POST['feedback']);
        $msg = $ok
            ? ['type'=>'success','text'=>'Submission graded!']
            : ['type'=>'error',  'text'=>'Error grading submission.'];
    }
    elseif(isset($_POST['reset_data'])) {
        resetDemoData();
        $msg = ['type'=>'success','text'=>'All demo data reset.'];
    }
}

$assignments = getAssignments(0);
$submissions = [];
foreach($assignments as $a) $submissions[$a['id']] = getSubmissionsForAssignment($a['id']);
$pdo      = getDBConnection();
$students = $pdo ? $pdo->query("SELECT id, first_name, last_name FROM users WHERE user_type='student' ORDER BY first_name")->fetchAll() : [];
$lessons  = getAllLessons();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Panel - SproutLearn</title>
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
        .particle:nth-child(2){width:16px;height:16px;left:18%;animation-duration:13s;animation-delay:2s;}
        .particle:nth-child(3){width:44px;height:44px;left:34%;animation-duration:11s;animation-delay:1s;}
        .particle:nth-child(4){width:20px;height:20px;left:52%;animation-duration:9s; animation-delay:3s;}
        .particle:nth-child(5){width:36px;height:36px;left:68%;animation-duration:14s;animation-delay:0.5s;}
        .particle:nth-child(6){width:14px;height:14px;left:84%;animation-duration:12s;animation-delay:4s;}
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
        .alert{padding:14px 18px;border-radius:14px;margin-bottom:20px;font-size:14px;display:flex;align-items:center;gap:10px;animation:fadeSlideIn 0.4s ease;}
        .alert-success{background:#dcfce7;color:#16a34a;} .alert-error{background:#fee2e2;color:#dc2626;}
        .card{background:rgba(255,255,255,0.97);backdrop-filter:blur(10px);border-radius:20px;padding:25px;margin-bottom:22px;box-shadow:0 8px 24px rgba(0,0,0,0.1);transition:transform 0.3s;animation:fadeSlideIn 0.5s ease both;}
        .card:nth-child(1){animation-delay:0.05s;} .card:nth-child(2){animation-delay:0.10s;} .card:nth-child(3){animation-delay:0.15s;} .card:nth-child(4){animation-delay:0.20s;} .card:nth-child(5){animation-delay:0.25s;}
        .card:hover{transform:translateY(-3px);}
        .card h3{color:var(--primary);font-size:17px;margin-bottom:16px;display:flex;align-items:center;gap:10px;}
        input,textarea,select{width:100%;padding:12px 16px;margin:8px 0;border:2px solid #e2e8f0;border-radius:50px;font-family:inherit;font-size:14px;outline:none;background:#f8fafc;transition:border-color 0.3s;appearance:none;}
        textarea{border-radius:16px;resize:vertical;}
        input:focus,textarea:focus,select:focus{border-color:var(--primary);background:#fff;}
        input[type=file]{border-radius:14px;}
        input[type=number]{max-width:180px;}
        .btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;padding:11px 24px;border-radius:50px;font-weight:600;font-size:14px;border:none;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;text-decoration:none;}
        .btn:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(102,126,234,0.4);}
        .btn-sm{padding:6px 14px;font-size:12px;}
        .btn-danger{background:linear-gradient(135deg,#ef4444,#dc2626);}
        .btn-danger:hover{box-shadow:0 6px 18px rgba(239,68,68,0.4);}
        .submission-item{border-left:4px solid var(--primary);padding:14px 16px;margin:12px 0;background:#f8fafc;border-radius:12px;}
        .submission-item strong{color:var(--text);font-size:14px;}
        .grade-badge{display:inline-block;padding:3px 10px;border-radius:50px;font-size:12px;font-weight:700;background:#dcfce7;color:#16a34a;}
        .danger-zone{background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.2);border-radius:14px;padding:16px;margin-top:8px;}
        .danger-zone p{font-size:13px;color:#64748b;margin-bottom:12px;}
        @media(max-width:768px){.sidebar{width:70px}.main-content{margin-left:70px}.sidebar .nav-item span,.logo h2 span{display:none}.nav-item{justify-content:center}}
        @media(max-width:480px){.sidebar{width:60px}.main-content{margin-left:60px;padding:15px}}
    </style>
</head>
<body>
<div class="particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<div class="sidebar"><?php include 'sidebar.php'; ?></div>
<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-chalkboard-teacher" style="color:var(--primary)"></i> Teacher Panel</h1>
        <p style="color:#64748b;margin-top:5px;">Manage assignments, lessons, students, and grades</p>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msg['type'] ?>">
        <i class="fas fa-<?= $msg['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg['text']) ?>
    </div>
    <?php endif; ?>

    <!-- Post Assignment -->
    <div class="card">
        <h3><i class="fas fa-plus-circle"></i> Post New Assignment</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Assignment Title" required>
            <textarea name="description" placeholder="Description" rows="3"></textarea>
            <input type="datetime-local" name="due_date" required>
            <input type="file" name="attachment" accept="image/*,video/*">
            <small style="color:#94a3b8;font-size:12px;">Optional: attach image or video (max 10MB)</small><br><br>
            <button type="submit" name="post_assignment" class="btn"><i class="fas fa-upload"></i> Post Assignment</button>
        </form>
    </div>

    <!-- Upload Lesson Media -->
    <div class="card">
        <h3><i class="fas fa-photo-video"></i> Add Media to Lesson</h3>
        <form method="POST" enctype="multipart/form-data">
            <select name="lesson_id" required>
                <option value="">Select lesson...</option>
                <?php foreach($lessons as $l): ?>
                <option value="<?= (int)$l['id'] ?>"><?= htmlspecialchars($l['course_title'].' — '.$l['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="file" name="lesson_media" accept="image/*,video/*" required>
            <small style="color:#94a3b8;font-size:12px;">Image or video, max 10MB</small><br><br>
            <button type="submit" name="upload_lesson_media" class="btn"><i class="fas fa-cloud-upload-alt"></i> Upload Media</button>
        </form>
    </div>

    <!-- Send Message -->
    <div class="card">
        <h3><i class="fas fa-envelope"></i> Send Message to Student</h3>
        <form method="POST">
            <select name="student_id" required>
                <option value="">Select student...</option>
                <?php foreach($students as $s): ?>
                <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <textarea name="message" placeholder="Your message..." rows="3" required></textarea>
            <button type="submit" name="send_query" class="btn"><i class="fas fa-paper-plane"></i> Send Message</button>
        </form>
    </div>

    <!-- Grade Submissions -->
    <div class="card">
        <h3><i class="fas fa-graduation-cap"></i> Grade Submissions</h3>
        <?php $has_subs = false;
        foreach($assignments as $a): $subs = $submissions[$a['id']] ?? [];
        if(empty($subs)) continue; $has_subs = true; ?>
        <h4 style="margin:16px 0 8px;color:var(--text);font-size:15px;"><?= htmlspecialchars($a['title']) ?></h4>
        <?php foreach($subs as $sub): ?>
        <div class="submission-item">
            <strong><i class="fas fa-user"></i> <?= htmlspecialchars($sub['first_name'].' '.$sub['last_name']) ?></strong>
            <?php if(!empty($sub['submission_text'])): ?>
            <p style="font-size:14px;margin-top:6px;color:#475569;"><?= nl2br(htmlspecialchars($sub['submission_text'])) ?></p>
            <?php endif; ?>
            <?php if(!empty($sub['file_path'])): ?>
            <p style="margin-top:6px;"><a href="<?= htmlspecialchars($sub['file_path']) ?>" target="_blank" class="btn btn-sm"><i class="fas fa-download"></i> Download</a></p>
            <?php endif; ?>
            <?php if($sub['grade']===null): ?>
            <form method="POST" style="margin-top:10px;">
                <input type="hidden" name="submission_id" value="<?= (int)$sub['id'] ?>">
                <input type="number" step="0.1" min="0" max="100" name="grade" placeholder="Grade (0–100)" required>
                <textarea name="feedback" placeholder="Feedback" rows="2"></textarea>
                <button type="submit" name="grade_submission" class="btn btn-sm"><i class="fas fa-check"></i> Submit Grade</button>
            </form>
            <?php else: ?>
            <p style="margin-top:8px;font-size:14px;">
                <span class="grade-badge"><?= $sub['grade'] ?>%</span>
                &nbsp;<?= htmlspecialchars($sub['feedback']) ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endforeach; endforeach; ?>
        <?php if(!$has_subs): ?>
        <p style="color:#94a3b8;font-size:14px;text-align:center;padding:16px 0;">No submissions to grade yet.</p>
        <?php endif; ?>
    </div>

    <!-- Reset Demo Data -->
    <div class="card">
        <h3><i class="fas fa-database"></i> Reset Demo Data</h3>
        <div class="danger-zone">
            <p>This will delete all student submissions, quiz attempts, and attendance records. This action cannot be undone.</p>
            <form method="POST">
                <button type="submit" name="reset_data" class="btn btn-danger"
                    onclick="return confirm('Reset all demo data? This cannot be undone.')">
                    <i class="fas fa-trash-alt"></i> Reset All Demo Data
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
