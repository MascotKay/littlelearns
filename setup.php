<?php
// setup.php — One-click database installer for SproutLearn
// Delete this file after setup is complete.

$step = $_POST['step'] ?? 'form';
$messages = [];
$errors = [];

if ($step === 'install') {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $user = trim($_POST['db_user'] ?? 'root');
    $pass = $_POST['db_pass'] ?? '';
    $name = trim($_POST['db_name'] ?? 'littlelearners');

    // Connect without selecting a database first
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $messages[] = "✅ Connected to MySQL successfully.";
    } catch (PDOException $e) {
        $errors[] = "❌ Cannot connect to MySQL: " . $e->getMessage();
        $step = 'form';
        goto render;
    }

    // Create database
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$name`");
        $messages[] = "✅ Database '$name' ready.";
    } catch (PDOException $e) {
        $errors[] = "❌ Could not create database: " . $e->getMessage();
        $step = 'form';
        goto render;
    }

    // Run the SQL file
    $sql = file_get_contents(__DIR__ . '/database.sql');
    // Strip CREATE DATABASE / USE statements since we already handled that
    $sql = preg_replace('/^CREATE DATABASE.*?;/im', '', $sql);
    $sql = preg_replace('/^USE\s+\w+\s*;/im', '', $sql);

    // Split on semicolons and run each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $run = 0;
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        try {
            $pdo->exec($stmt);
            $run++;
        } catch (PDOException $e) {
            // Skip duplicate/already-exists errors gracefully
            if (strpos($e->getMessage(), 'already exists') === false &&
                strpos($e->getMessage(), 'Duplicate entry') === false) {
                $errors[] = "⚠️ Statement error: " . $e->getMessage();
            }
        }
    }
    $messages[] = "✅ Ran $run SQL statements (tables + seed data).";

    // Update config.php with the provided credentials
    $config = file_get_contents(__DIR__ . '/config.php');
    $config = preg_replace("/define\('DB_HOST',\s*'[^']*'\)/", "define('DB_HOST', '$host')", $config);
    $config = preg_replace("/define\('DB_USER',\s*'[^']*'\)/", "define('DB_USER', '$user')", $config);
    $config = preg_replace("/define\('DB_PASS',\s*'[^']*'\)/", "define('DB_PASS', '$pass')", $config);
    $config = preg_replace("/define\('DB_NAME',\s*'[^']*'\)/", "define('DB_NAME', '$name')", $config);
    if (file_put_contents(__DIR__ . '/config.php', $config)) {
        $messages[] = "✅ config.php updated with your database credentials.";
    } else {
        $errors[] = "⚠️ Could not write to config.php — update it manually with: host=$host, user=$user, db=$name";
    }

    $step = empty($errors) ? 'done' : 'done_with_warnings';
}

render:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SproutLearn — Database Setup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea, #764ba2); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: white; border-radius: 24px; padding: 40px; max-width: 520px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        h1 { color: #667eea; margin-bottom: 6px; font-size: 1.6rem; }
        .subtitle { color: #64748b; margin-bottom: 28px; font-size: 0.95rem; }
        label { display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 4px; margin-top: 14px; }
        input { width: 100%; padding: 11px 14px; border: 2px solid #e2e8f0; border-radius: 40px; font-size: 0.95rem; transition: border 0.2s; }
        input:focus { outline: none; border-color: #667eea; }
        .row { display: flex; gap: 12px; }
        .row > div { flex: 1; }
        .btn { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 13px 28px; border-radius: 40px; font-size: 1rem; font-weight: 600; cursor: pointer; width: 100%; margin-top: 22px; transition: opacity 0.2s; }
        .btn:hover { opacity: 0.9; }
        .msg { padding: 10px 14px; border-radius: 10px; margin-bottom: 8px; font-size: 0.9rem; }
        .msg.ok { background: #d1fae5; color: #065f46; }
        .msg.err { background: #fee2e2; color: #991b1b; }
        .demo-box { background: #f8fafc; border-radius: 12px; padding: 16px; margin-top: 20px; font-size: 0.88rem; color: #475569; }
        .demo-box code { background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
        .done-icon { font-size: 3rem; text-align: center; margin-bottom: 10px; }
        a.link-btn { display: inline-block; background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 12px 28px; border-radius: 40px; text-decoration: none; font-weight: 600; margin-top: 16px; }
        .warning { background: #fffbeb; border: 1px solid #fbbf24; border-radius: 10px; padding: 12px; margin-top: 14px; font-size: 0.85rem; color: #92400e; }
    </style>
</head>
<body>
<div class="card">
    <?php if ($step === 'form'): ?>
        <h1>🌱 SproutLearn Setup</h1>
        <p class="subtitle">Enter your MySQL credentials to create the database and seed demo data.</p>

        <?php foreach ($errors as $e): ?>
            <div class="msg err"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <input type="hidden" name="step" value="install">
            <div class="row">
                <div>
                    <label>MySQL Host</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div>
                    <label>Database Name</label>
                    <input type="text" name="db_name" value="littlelearners" required>
                </div>
            </div>
            <label>MySQL Username</label>
            <input type="text" name="db_user" value="root" required>
            <label>MySQL Password</label>
            <input type="password" name="db_pass" placeholder="(leave blank if none)">
            <button type="submit" class="btn">⚡ Install Database</button>
        </form>

        <div class="demo-box">
            This will: create the <code>littlelearners</code> database, build all tables, insert demo data, and update <code>config.php</code> automatically.
        </div>

    <?php elseif ($step === 'done' || $step === 'done_with_warnings'): ?>
        <div class="done-icon"><?= empty($errors) ? '🎉' : '⚠️' ?></div>
        <h1 style="text-align:center"><?= empty($errors) ? 'Setup Complete!' : 'Done (with warnings)' ?></h1>
        <br>
        <?php foreach ($messages as $m): ?>
            <div class="msg ok"><?= htmlspecialchars($m) ?></div>
        <?php endforeach; ?>
        <?php foreach ($errors as $e): ?>
            <div class="msg err"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <div class="demo-box" style="margin-top:20px;">
            <strong>Demo accounts</strong> (password: <code>password123</code>)<br><br>
            👩‍🏫 Teacher: <code>teacher</code><br>
            🎒 Student: <code>student</code> or <code>student2</code><br>
            👨‍👩‍👦 Parent: <code>parent</code>
        </div>

        <div class="warning">
            🔒 <strong>Delete <code>setup.php</code></strong> after confirming login works — it should not remain on a production server.
        </div>

        <div style="text-align:center">
            <a href="login.php" class="link-btn">Go to Login →</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
