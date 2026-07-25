<?php
require_once 'config.php';
requireLogin();
$user_id   = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$course_id = $_GET['course_id'] ?? null;
$msg = null;

/* ── Teacher: post / grade ── */
if($user_type === 'teacher') {
    if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['post_assignment'])) {
        $title = trim($_POST['title']);
        $desc  = trim($_POST['description']);
        $due   = $_POST['due_date'];
        $file  = isset($_FILES['attachment']) ? $_FILES['attachment'] : null;
        $msg   = createAssignment($title,$desc,$due,$file)
            ? ['type'=>'success','text'=>'Assignment posted!']
            : ['type'=>'error',  'text'=>'Error posting assignment.'];
    }
    if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['grade_submission'])) {
        $msg = gradeAssignment($_POST['submission_id'],$_POST['grade'],$_POST['feedback'])
            ? ['type'=>'success','text'=>'Submission graded!']
            : ['type'=>'error',  'text'=>'Error grading submission.'];
    }
    $assignments = getAssignments(0);
    $submissions = [];
    foreach($assignments as $a) $submissions[$a['id']] = getSubmissionsForAssignment($a['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Assignments - SproutLearn</title>
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
        .card:hover{transform:translateY(-4px);}
        .card h3{color:var(--text);margin-bottom:16px;font-size:17px;}
        input,textarea,select{width:100%;padding:12px 16px;margin:8px 0;border:2px solid #e2e8f0;border-radius:50px;font-family:inherit;font-size:14px;outline:none;background:#f8fafc;transition:border-color 0.3s;}
        textarea{border-radius:16px;resize:vertical;}
        input:focus,textarea:focus,select:focus{border-color:var(--primary);background:#fff;}
        input[type=file]{border-radius:14px;}
        .btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;padding:10px 22px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px;border:none;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;}
        .btn:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(102,126,234,0.4);}
        .btn-sm{padding:6px 14px;font-size:12px;}
        .submission-item{border-left:4px solid var(--primary);padding:14px 16px;margin:12px 0;background:#f8fafc;border-radius:12px;}
        .submission-item strong{color:var(--text);}
        .grade-badge{display:inline-block;padding:3px 10px;border-radius:50px;font-size:12px;font-weight:700;}
        .grade-pass{background:#dcfce7;color:#16a34a;} .grade-pending{background:#fef3c7;color:#92400e;}
        .media-preview img{max-width:200px;border-radius:10px;margin-top:8px;}
        .media-preview video{max-width:300px;border-radius:10px;margin-top:8px;}
        @media(max-width:768px){.sidebar{width:70px}.main-content{margin-left:70px}.sidebar .nav-item span,.logo h2 span{display:none}.nav-item{justify-content:center}}
        @media(max-width:480px){.sidebar{width:60px}.main-content{margin-left:60px;padding:15px}}
    </style>
</head>
<body>
<div class="particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<div class="sidebar"><?php include 'sidebar.php'; ?></div>
<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-tasks" style="color:var(--primary)"></i> Teacher — Assignments</h1>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msg['type'] ?>">
        <i class="fas fa-<?= $msg['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg['text']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <h3><i class="fas fa-plus-circle" style="color:var(--primary)"></i> Post New Assignment</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Assignment Title" required>
            <textarea name="description" placeholder="Description" rows="3"></textarea>
            <input type="datetime-local" name="due_date" required>
            <input type="file" name="attachment" accept="image/*,video/*">
            <small style="color:#94a3b8;font-size:12px;">Optional: attach image or video (max 10MB)</small><br><br>
            <button type="submit" name="post_assignment" class="btn"><i class="fas fa-paper-plane"></i> Post Assignment</button>
        </form>
    </div>

    <?php foreach($assignments as $a): ?>
    <div class="card">
        <h3><?= htmlspecialchars($a['title']) ?></h3>
        <p style="color:#64748b;font-size:14px;margin-bottom:10px;">Due: <?= htmlspecialchars($a['due_date']) ?></p>
        <?php if($a['attachment_path']): ?>
        <div class="media-preview">
            <?php if(strpos($a['attachment_type'],'image')!==false): ?>
                <img src="<?= htmlspecialchars($a['attachment_path']) ?>" alt="Attachment">
            <?php else: ?>
                <video controls><source src="<?= htmlspecialchars($a['attachment_path']) ?>"></video>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <h4 style="margin:16px 0 8px;color:var(--text);">Submissions</h4>
        <?php if(empty($submissions[$a['id']])): ?>
            <p style="color:#94a3b8;font-size:14px;">No submissions yet.</p>
        <?php else: foreach($submissions[$a['id']] as $sub): ?>
        <div class="submission-item">
            <strong><?= htmlspecialchars($sub['first_name'].' '.$sub['last_name']) ?></strong>
            <?php if($sub['submission_text']): ?>
            <p style="font-size:14px;margin-top:6px;color:#475569;"><?= nl2br(htmlspecialchars($sub['submission_text'])) ?></p>
            <?php endif; ?>
            <?php if($sub['file_path']): ?>
            <p style="margin-top:6px;"><a href="<?= htmlspecialchars($sub['file_path']) ?>" target="_blank" class="btn btn-sm"><i class="fas fa-download"></i> View File</a></p>
            <?php endif; ?>
            <?php if($sub['grade']===null): ?>
            <form method="POST" style="margin-top:10px;">
                <input type="hidden" name="submission_id" value="<?= (int)$sub['id'] ?>">
                <input type="number" step="0.1" min="0" max="100" name="grade" placeholder="Grade %" required style="max-width:200px;">
                <textarea name="feedback" placeholder="Feedback" rows="2"></textarea>
                <button type="submit" name="grade_submission" class="btn"><i class="fas fa-check"></i> Submit Grade</button>
            </form>
            <?php else: ?>
            <p style="margin-top:8px;font-size:14px;">
                <span class="grade-badge grade-pass"><?= $sub['grade'] ?>%</span>
                &nbsp;<?= htmlspecialchars($sub['feedback']) ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
    </div>
    <?php endforeach; ?>
</div>
</body>
</html>
<?php exit(); }

/* ── Student: submit ── */
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['submit_assignment'])) {
    $assign_id = (int)$_POST['assignment_id'];
    $text = trim($_POST['submission_text']);
    $file = isset($_FILES['submission_file']) ? $_FILES['submission_file'] : null;
    if(submitAssignment($assign_id,$user_id,$text,$file)) {
        header("Location: assignments.php"); exit();
    }
    $msg = ['type'=>'error','text'=>'Error submitting assignment.'];
}
$assignments = getAssignments($user_id, $course_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignments - SproutLearn</title>
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
        .alert{padding:14px 18px;border-radius:14px;margin-bottom:20px;font-size:14px;display:flex;align-items:center;gap:10px;}
        .alert-error{background:#fee2e2;color:#dc2626;}
        .card{background:rgba(255,255,255,0.97);backdrop-filter:blur(10px);border-radius:20px;padding:25px;margin-bottom:22px;box-shadow:0 8px 24px rgba(0,0,0,0.1);transition:transform 0.3s;animation:fadeSlideIn 0.5s ease both;}
        .card:nth-child(1){animation-delay:0.05s;} .card:nth-child(2){animation-delay:0.10s;} .card:nth-child(3){animation-delay:0.15s;}
        .card:hover{transform:translateY(-4px);}
        .card h3{color:var(--text);margin-bottom:8px;font-size:17px;}
        .card-meta{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:14px;}
        .meta-tag{display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#94a3b8;font-weight:500;}
        textarea,input[type=file]{width:100%;padding:12px 16px;margin:8px 0;border:2px solid #e2e8f0;font-family:inherit;font-size:14px;outline:none;background:#f8fafc;transition:border-color 0.3s;}
        textarea{border-radius:16px;resize:vertical;}
        textarea:focus,input[type=file]:focus{border-color:var(--primary);background:#fff;}
        input[type=file]{border-radius:14px;}
        .submitted-badge{display:inline-flex;align-items:center;gap:8px;background:#dcfce7;color:#16a34a;padding:8px 16px;border-radius:50px;font-weight:600;font-size:14px;margin-bottom:10px;}
        .grade-display{background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:12px 16px;margin-top:10px;font-size:14px;color:#0369a1;}
        .btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;padding:11px 24px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px;border:none;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;}
        .btn:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(102,126,234,0.4);}
        .btn-sm{padding:6px 14px;font-size:12px;}
        .media-preview{margin:12px 0;}
        .media-preview img{max-width:100%;max-height:300px;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,0.1);}
        .media-preview video{max-width:100%;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,0.1);}
        @media(max-width:768px){.sidebar{width:70px}.main-content{margin-left:70px}.sidebar .nav-item span,.logo h2 span{display:none}.nav-item{justify-content:center}}
        @media(max-width:480px){.sidebar{width:60px}.main-content{margin-left:60px;padding:15px}}
    </style>
</head>
<body>
<div class="particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<div class="sidebar"><?php include 'sidebar.php'; ?></div>
<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-tasks" style="color:var(--primary)"></i> My Assignments</h1>
        <p style="color:#64748b;margin-top:5px;">Submit and track your assignments</p>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msg['type'] ?>"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($msg['text']) ?></div>
    <?php endif; ?>

    <?php if(empty($assignments)): ?>
    <div style="background:rgba(255,255,255,0.95);border-radius:20px;padding:40px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);">
        <i class="fas fa-clipboard-list" style="font-size:48px;color:#cbd5e1;"></i>
        <p style="color:#64748b;margin-top:12px;">No assignments available yet.</p>
    </div>
    <?php else: ?>
    <?php foreach($assignments as $a): ?>
    <div class="card">
        <h3><?= htmlspecialchars($a['title']) ?></h3>
        <p style="color:#475569;font-size:14px;line-height:1.6;margin-bottom:12px;"><?= htmlspecialchars($a['description']) ?></p>

        <?php if(!empty($a['attachment_path'])): ?>
        <div class="media-preview">
            <?php if($a['attachment_type']==='image'): ?>
                <img src="<?= htmlspecialchars($a['attachment_path']) ?>" alt="Assignment media">
            <?php else: ?>
                <video controls><source src="<?= htmlspecialchars($a['attachment_path']) ?>"></video>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card-meta">
            <span class="meta-tag"><i class="fas fa-calendar-alt"></i> Due: <?= htmlspecialchars($a['due_date']) ?></span>
            <span class="meta-tag"><i class="fas fa-star"></i> <?= (int)$a['points'] ?> pts</span>
        </div>

        <?php if($a['submitted']): ?>
            <div class="submitted-badge"><i class="fas fa-check-circle"></i> Submitted</div>
            <div class="grade-display">
                <strong>Grade:</strong> <?= $a['grade'] !== null ? htmlspecialchars($a['grade']).'%' : '<em>Pending review</em>' ?>
            </div>
            <?php if(!empty($a['submission_text'])): ?>
            <details style="margin-top:10px;">
                <summary style="cursor:pointer;color:var(--primary);font-size:14px;font-weight:600;">Your submission</summary>
                <p style="padding:10px;font-size:14px;color:#475569;"><?= nl2br(htmlspecialchars($a['submission_text'])) ?></p>
            </details>
            <?php endif; ?>
            <?php if(!empty($a['submission_file'])): ?>
            <p style="margin-top:8px;"><a href="<?= htmlspecialchars($a['submission_file']) ?>" target="_blank" class="btn btn-sm"><i class="fas fa-download"></i> View Uploaded File</a></p>
            <?php endif; ?>
        <?php else: ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="assignment_id" value="<?= (int)$a['id'] ?>">
                <textarea name="submission_text" placeholder="Write your answer here..." required rows="4"></textarea>
                <input type="file" name="submission_file" accept="image/*,video/*">
                <small style="color:#94a3b8;font-size:12px;">Optional: attach image or video (max 10MB)</small><br><br>
                <button type="submit" name="submit_assignment" class="btn">
                    <i class="fas fa-paper-plane"></i> Submit Assignment
                </button>
            </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
