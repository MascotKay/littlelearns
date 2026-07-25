<?php
require_once 'config.php';
requireLogin();
$user_id    = $_SESSION['user_id'];
$progress   = getStudentProgress($user_id);
$scores     = getStudentScores($user_id);
$attendance = getAttendance($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Progress - SproutLearn</title>
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
        .card{background:rgba(255,255,255,0.97);backdrop-filter:blur(10px);border-radius:20px;padding:25px;margin-bottom:22px;box-shadow:0 8px 24px rgba(0,0,0,0.1);animation:fadeSlideIn 0.5s ease both;overflow:hidden;}
        .card:nth-child(1){animation-delay:0.05s;} .card:nth-child(2){animation-delay:0.10s;} .card:nth-child(3){animation-delay:0.15s;} .card:nth-child(4){animation-delay:0.20s;}
        .card h3{font-size:17px;color:var(--text);margin-bottom:16px;display:flex;align-items:center;gap:10px;}
        .progress-track{background:#e2e8f0;border-radius:50px;height:16px;overflow:hidden;margin-bottom:8px;}
        .progress-fill{height:100%;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:50px;transition:width 1.2s ease;width:0%;}
        .progress-label{color:#64748b;font-size:14px;}
        /* Tables */
        .table-wrap{overflow-x:auto;}
        table{width:100%;border-collapse:collapse;}
        thead th{padding:10px 14px;text-align:left;background:linear-gradient(135deg,rgba(102,126,234,0.08),rgba(118,75,162,0.08));color:var(--text);font-size:13px;font-weight:700;border-bottom:2px solid #e2e8f0;}
        tbody td{padding:12px 14px;border-bottom:1px solid #f1f5f9;font-size:14px;color:#475569;}
        tbody tr:last-child td{border-bottom:none;}
        tbody tr:hover td{background:#f8fafc;}
        .grade-pill{display:inline-block;padding:3px 10px;border-radius:50px;font-size:12px;font-weight:700;}
        .grade-high{background:#dcfce7;color:#16a34a;}
        .grade-mid{background:#fef3c7;color:#92400e;}
        .grade-low{background:#fee2e2;color:#dc2626;}
        .attend-pill{display:inline-block;padding:3px 10px;border-radius:50px;font-size:12px;font-weight:600;}
        .attend-present{background:#dcfce7;color:#16a34a;}
        .attend-absent{background:#fee2e2;color:#dc2626;}
        .attend-late{background:#fef3c7;color:#92400e;}
        .empty-state{color:#94a3b8;font-size:14px;text-align:center;padding:20px 0;}
        @media(max-width:768px){.sidebar{width:70px}.main-content{margin-left:70px}.sidebar .nav-item span,.logo h2 span{display:none}.nav-item{justify-content:center}thead th,tbody td{font-size:12px;padding:8px 10px;}}
        @media(max-width:480px){.sidebar{width:60px}.main-content{margin-left:60px;padding:15px}}
    </style>
</head>
<body>
<div class="particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<div class="sidebar"><?php include 'sidebar.php'; ?></div>
<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-chart-line" style="color:var(--primary)"></i> My Progress</h1>
        <p style="color:#64748b;margin-top:5px;">Track your learning journey</p>
    </div>

    <!-- Overall progress -->
    <div class="card">
        <h3><i class="fas fa-tachometer-alt" style="color:var(--primary)"></i> Overall Progress</h3>
        <div class="progress-track">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        <p class="progress-label"><strong><?= $progress ?>%</strong> of learning goals complete</p>
    </div>

    <!-- Assignment Scores -->
    <div class="card">
        <h3><i class="fas fa-tasks" style="color:var(--primary)"></i> Assignment Scores</h3>
        <?php if(empty($scores['assignments'])): ?>
            <p class="empty-state">No graded assignments yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Assignment</th><th>Grade</th><th>Course</th></tr></thead>
                <tbody>
                <?php foreach($scores['assignments'] as $a):
                    $g = (float)$a['grade'];
                    $cls = $g>=80?'grade-high':($g>=50?'grade-mid':'grade-low');
                ?>
                <tr>
                    <td><?= htmlspecialchars($a['title']) ?></td>
                    <td><span class="grade-pill <?= $cls ?>"><?= $g ?>%</span></td>
                    <td><?= htmlspecialchars($a['course_title']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quiz Scores -->
    <div class="card">
        <h3><i class="fas fa-brain" style="color:var(--primary)"></i> Quiz Scores</h3>
        <?php if(empty($scores['quizzes'])): ?>
            <p class="empty-state">No quiz attempts yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Quiz</th><th>Your Score</th><th>Pass Mark</th><th>Course</th><th>Result</th></tr></thead>
                <tbody>
                <?php foreach($scores['quizzes'] as $q):
                    $s = (float)$q['score'];
                    $p = (float)$q['passing_score'];
                    $passed = $s >= $p;
                ?>
                <tr>
                    <td><?= htmlspecialchars($q['title']) ?></td>
                    <td><span class="grade-pill <?= $s>=80?'grade-high':($s>=$p?'grade-mid':'grade-low') ?>"><?= $s ?>%</span></td>
                    <td><?= $p ?>%</td>
                    <td><?= htmlspecialchars($q['course_title']) ?></td>
                    <td><?= $passed ? '✅ Passed' : '❌ Failed' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Attendance -->
    <div class="card">
        <h3><i class="fas fa-calendar-check" style="color:var(--primary)"></i> Attendance</h3>
        <?php if(empty($attendance)): ?>
            <p class="empty-state">No attendance records found.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Course</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach($attendance as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['date']) ?></td>
                    <td><?= htmlspecialchars($a['course_title']) ?></td>
                    <td>
                        <span class="attend-pill attend-<?= strtolower($a['status']) ?>">
                            <?= ucfirst($a['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
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
