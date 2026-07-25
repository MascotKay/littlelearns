<?php
require_once 'config.php';
requireLogin();
if($_SESSION['user_type'] != 'parent') { header('Location: home.php'); exit(); }
$parent_id       = $_SESSION['user_id'];
$children        = getChildren($parent_id);
$selected_child  = $_GET['child_id'] ?? ($children[0]['id'] ?? 0);
$child_data = null;
if($selected_child && isParentOf($parent_id, $selected_child)) {
    $child_data    = getUser($selected_child);
    $quiz_attempts = getQuizAttempts($selected_child);
    $assignments   = getAssignments($selected_child);
    $attendance    = getAttendance($selected_child);
    $queries       = getQueriesForStudent($selected_child);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Family Dashboard - SproutLearn</title>
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
        .card{background:rgba(255,255,255,0.97);backdrop-filter:blur(10px);border-radius:20px;padding:25px;margin-bottom:22px;box-shadow:0 8px 24px rgba(0,0,0,0.1);animation:fadeSlideIn 0.5s ease both;}
        .card:nth-child(1){animation-delay:0.05s;} .card:nth-child(2){animation-delay:0.10s;} .card:nth-child(3){animation-delay:0.15s;}
        .card h3{font-size:17px;color:var(--text);margin-bottom:16px;display:flex;align-items:center;gap:10px;}
        select{width:100%;max-width:340px;padding:12px 16px;border:2px solid #e2e8f0;border-radius:50px;font-family:inherit;font-size:14px;outline:none;background:#f8fafc;transition:border-color 0.3s;appearance:none;}
        select:focus{border-color:var(--primary);background:#fff;}
        /* Stats mini grid */
        .mini-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:14px;margin-bottom:18px;}
        .mini-stat{background:#f8fafc;border-radius:14px;padding:16px;text-align:center;}
        .mini-stat .val{font-size:26px;font-weight:800;color:var(--primary);}
        .mini-stat .lbl{font-size:12px;color:#94a3b8;margin-top:4px;}
        /* Messages list */
        .msg-list{list-style:none;padding:0;}
        .msg-item{padding:14px 16px;border-left:4px solid var(--primary);background:#f8fafc;border-radius:10px;margin-bottom:10px;}
        .msg-item strong{color:var(--text);font-size:14px;}
        .msg-item p{color:#475569;font-size:14px;margin-top:4px;}
        .msg-item time{color:#94a3b8;font-size:12px;display:block;margin-top:4px;}
        .empty-state{color:#94a3b8;font-size:14px;text-align:center;padding:16px 0;}
        @media(max-width:768px){.sidebar{width:70px}.main-content{margin-left:70px}.sidebar .nav-item span,.logo h2 span{display:none}.nav-item{justify-content:center}}
        @media(max-width:480px){.sidebar{width:60px}.main-content{margin-left:60px;padding:15px}.mini-stats{grid-template-columns:1fr 1fr}}
    </style>
</head>
<body>
<div class="particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<div class="sidebar"><?php include 'sidebar.php'; ?></div>
<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-users" style="color:var(--primary)"></i> Family Dashboard</h1>
        <p style="color:#64748b;margin-top:5px;">Monitor your child's learning progress</p>
    </div>

    <!-- Child selector -->
    <div class="card">
        <h3><i class="fas fa-child" style="color:var(--primary)"></i> Select Child</h3>
        <?php if(empty($children)): ?>
            <p class="empty-state">No children linked to your account yet.</p>
        <?php else: ?>
        <form method="GET">
            <select name="child_id" onchange="this.form.submit()">
                <?php foreach($children as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $selected_child==$c['id']?'selected':'' ?>>
                    <?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>

    <?php if($child_data): ?>
    <?php
        $poem_quiz_attempts = array_filter($quiz_attempts, fn($a)=>$a['quiz_id']==1);
        $last_quiz = !empty($poem_quiz_attempts) ? reset($poem_quiz_attempts) : null;
        $submitted_count = count(array_filter($assignments, fn($a)=>$a['submitted']));
    ?>

    <!-- Child stats -->
    <div class="card">
        <h3>
            <i class="fas fa-chart-bar" style="color:var(--primary)"></i>
            <?= htmlspecialchars($child_data['first_name']) ?>'s Overview
        </h3>
        <div class="mini-stats">
            <div class="mini-stat">
                <div class="val"><?= $last_quiz ? round($last_quiz['score'],1).'%' : '—' ?></div>
                <div class="lbl">Poem Quiz</div>
            </div>
            <div class="mini-stat">
                <div class="val"><?= count($attendance) ?></div>
                <div class="lbl">Attendance Days</div>
            </div>
            <div class="mini-stat">
                <div class="val"><?= $submitted_count ?></div>
                <div class="lbl">Submissions</div>
            </div>
            <div class="mini-stat">
                <div class="val"><?= count($assignments) ?></div>
                <div class="lbl">Total Tasks</div>
            </div>
        </div>
    </div>

    <!-- Teacher messages -->
    <div class="card">
        <h3><i class="fas fa-envelope" style="color:var(--primary)"></i> Teacher Messages</h3>
        <?php if(empty($queries)): ?>
            <p class="empty-state">No messages from teachers yet.</p>
        <?php else: ?>
        <ul class="msg-list">
            <?php foreach($queries as $q): ?>
            <li class="msg-item">
                <strong><?= htmlspecialchars($q['first_name'].' '.$q['last_name']) ?></strong>
                <p><?= htmlspecialchars($q['message']) ?></p>
                <time><?= htmlspecialchars($q['created_at']) ?></time>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
