<?php
require_once 'config.php';
requireLogin();
$user_type  = $_SESSION['user_type'];
$msg        = null;
if($user_type=='teacher' && $_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['add_poem'])) {
    $file = isset($_FILES['image']) ? $_FILES['image'] : null;
    $ok   = createPoem(
        trim($_POST['title']), trim($_POST['author']),
        $_POST['category'], $_POST['difficulty_level'],
        (int)$_POST['reading_time'], trim($_POST['content']), $file
    );
    $msg = $ok
        ? ['type'=>'success','text'=>'Poem added successfully!']
        : ['type'=>'error',  'text'=>'Error adding poem. Please try again.'];
}
$category   = $_GET['category']   ?? null;
$difficulty = $_GET['difficulty'] ?? null;
$poems      = getPoems($category, $difficulty);
$categories  = ['nursery','educational','fun','classic'];
$difficulties = ['easy','medium','hard'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Poems - SproutLearn</title>
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
        .particle:nth-child(2){width:18px;height:18px;left:18%;animation-duration:13s;animation-delay:2s;}
        .particle:nth-child(3){width:46px;height:46px;left:34%;animation-duration:11s;animation-delay:1s;}
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
        .page-header{background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);padding:22px 28px;border-radius:22px;margin-bottom:20px;box-shadow:0 10px 30px rgba(0,0,0,0.1);animation:fadeSlideIn 0.5s ease;}
        .page-header h1{font-size:24px;color:var(--text);margin-bottom:14px;}
        .filter-bar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;}
        .filter-btn{padding:6px 16px;border-radius:50px;text-decoration:none;font-size:13px;font-weight:600;transition:all 0.2s;display:inline-block;}
        .filter-btn-cat{background:#e2e8f0;color:#475569;}
        .filter-btn-cat:hover,.filter-btn-cat.active{background:var(--primary);color:white;}
        .filter-btn-diff{background:#fce7f3;color:#9d174d;}
        .filter-btn-diff:hover,.filter-btn-diff.active{background:#ec4899;color:white;}
        .filter-btn-all{background:#f1f5f9;color:#475569;}
        .filter-btn-all:hover{background:#667eea;color:white;}
        .filter-sep{width:1px;height:20px;background:#e2e8f0;display:inline-block;margin:0 4px;}
        .alert{padding:14px 18px;border-radius:14px;margin-bottom:20px;font-size:14px;display:flex;align-items:center;gap:10px;animation:fadeSlideIn 0.4s ease;}
        .alert-success{background:#dcfce7;color:#16a34a;} .alert-error{background:#fee2e2;color:#dc2626;}
        .add-card{background:rgba(255,255,255,0.97);backdrop-filter:blur(10px);border-radius:20px;padding:25px;margin-bottom:22px;box-shadow:0 8px 24px rgba(0,0,0,0.1);animation:fadeSlideIn 0.5s ease 0.1s both;}
        .add-card h3{color:var(--text);margin-bottom:14px;}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
        input,select,textarea{width:100%;padding:11px 16px;margin:6px 0;border:2px solid #e2e8f0;border-radius:50px;font-family:inherit;font-size:14px;outline:none;background:#f8fafc;transition:border-color 0.3s;appearance:none;}
        textarea{border-radius:16px;resize:vertical;}
        input:focus,select:focus,textarea:focus{border-color:var(--primary);background:#fff;}
        input[type=file]{border-radius:14px;}
        .poems-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;}
        .poem-card{background:rgba(255,255,255,0.97);backdrop-filter:blur(10px);border-radius:20px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,0.1);transition:transform 0.3s,box-shadow 0.3s;animation:fadeSlideIn 0.5s ease both;}
        .poem-card:nth-child(1){animation-delay:0.05s;} .poem-card:nth-child(2){animation-delay:0.10s;} .poem-card:nth-child(3){animation-delay:0.15s;} .poem-card:nth-child(4){animation-delay:0.20s;} .poem-card:nth-child(5){animation-delay:0.25s;} .poem-card:nth-child(6){animation-delay:0.30s;}
        .poem-card:hover{transform:translateY(-7px);box-shadow:0 18px 36px rgba(0,0,0,0.15);}
        .poem-img{width:100%;height:160px;object-fit:cover;}
        .poem-img-placeholder{width:100%;height:80px;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;font-size:32px;}
        .poem-body{padding:18px;}
        .poem-body h3{font-size:15px;color:var(--text);margin-bottom:6px;}
        .poem-meta{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:10px;}
        .poem-tag{font-size:11px;color:#94a3b8;font-weight:500;}
        .poem-excerpt{font-size:13px;color:#64748b;line-height:1.5;margin-bottom:14px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;}
        .btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;padding:9px 20px;border-radius:50px;text-decoration:none;font-weight:600;font-size:13px;border:none;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;}
        .btn:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(102,126,234,0.4);}
        @media(max-width:768px){
            .sidebar{width:70px}.main-content{margin-left:70px}
            .sidebar .nav-item span,.logo h2 span{display:none}.nav-item{justify-content:center}
            .form-row{grid-template-columns:1fr}
        }
        @media(max-width:480px){.sidebar{width:60px}.main-content{margin-left:60px;padding:15px}.poems-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<div class="sidebar"><?php include 'sidebar.php'; ?></div>
<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-book-open" style="color:var(--primary)"></i> Poems</h1>
        <div class="filter-bar">
            <?php foreach($categories as $c): ?>
            <a href="poems.php?category=<?= $c ?>" class="filter-btn filter-btn-cat <?= $category==$c?'active':'' ?>"><?= ucfirst($c) ?></a>
            <?php endforeach; ?>
            <span class="filter-sep"></span>
            <?php foreach($difficulties as $d): ?>
            <a href="poems.php?difficulty=<?= $d ?>" class="filter-btn filter-btn-diff <?= $difficulty==$d?'active':'' ?>"><?= ucfirst($d) ?></a>
            <?php endforeach; ?>
            <a href="poems.php" class="filter-btn filter-btn-all">All</a>
        </div>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msg['type'] ?>">
        <i class="fas fa-<?= $msg['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg['text']) ?>
    </div>
    <?php endif; ?>

    <?php if($user_type=='teacher'): ?>
    <div class="add-card">
        <h3><i class="fas fa-plus-circle" style="color:var(--primary)"></i> Add New Poem</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <input type="text" name="title"  placeholder="Poem Title"  required>
                <input type="text" name="author" placeholder="Author Name" required>
            </div>
            <div class="form-row">
                <select name="category" required>
                    <?php foreach($categories as $c): ?><option value="<?= $c ?>"><?= ucfirst($c) ?></option><?php endforeach; ?>
                </select>
                <select name="difficulty_level" required>
                    <?php foreach($difficulties as $d): ?><option value="<?= $d ?>"><?= ucfirst($d) ?></option><?php endforeach; ?>
                </select>
            </div>
            <input type="number" name="reading_time" placeholder="Reading time (minutes)" min="1" required>
            <textarea name="content" placeholder="Poem text..." rows="5" required></textarea>
            <input type="file" name="image" accept="image/*">
            <small style="color:#94a3b8;font-size:12px;">Optional: cover image (max 10MB)</small><br><br>
            <button type="submit" name="add_poem" class="btn"><i class="fas fa-plus"></i> Add Poem</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if(empty($poems)): ?>
    <div style="background:rgba(255,255,255,0.95);border-radius:20px;padding:40px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);">
        <i class="fas fa-book" style="font-size:48px;color:#cbd5e1;"></i>
        <p style="color:#64748b;margin-top:12px;">No poems found. Try a different filter.</p>
    </div>
    <?php else: ?>
    <div class="poems-grid">
        <?php foreach($poems as $p): ?>
        <div class="poem-card">
            <?php if(!empty($p['image_path'])): ?>
                <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="poem-img">
            <?php else: ?>
                <div class="poem-img-placeholder">📖</div>
            <?php endif; ?>
            <div class="poem-body">
                <h3><?= htmlspecialchars($p['title']) ?></h3>
                <div class="poem-meta">
                    <span class="poem-tag"><i class="fas fa-user-pen"></i> <?= htmlspecialchars($p['author']) ?></span>
                    <span class="poem-tag"><i class="fas fa-tag"></i> <?= ucfirst($p['category']) ?></span>
                    <span class="poem-tag"><i class="fas fa-clock"></i> <?= (int)$p['reading_time'] ?> min</span>
                </div>
                <p class="poem-excerpt"><?= htmlspecialchars(substr($p['content'],0,120)) ?><?= strlen($p['content'])>120?'…':'' ?></p>
                <a href="poem-view.php?id=<?= (int)$p['id'] ?>" class="btn">
                    <i class="fas fa-book-reader"></i> Read Poem
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
