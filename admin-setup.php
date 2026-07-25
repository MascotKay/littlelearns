<?php
/**
 * Admin Setup Verification Page
 * Visit: http://localhost/littlelearners/admin-setup.php
 *
 * This page verifies and (re)provisions the admin account.
 * The admin is also auto-provisioned on the first login attempt,
 * so running this page manually is optional — it's mainly for
 * troubleshooting or resetting the admin password.
 */
require_once 'config.php';

$pdo = getDBConnection();
$steps  = [];
$errors = [];

if (!$pdo) {
    $errors[] = 'Cannot connect to database. Check config.php.';
} else {

    // Step 1 — Alter ENUM
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN user_type
                    ENUM('student','teacher','parent','admin') NOT NULL DEFAULT 'student'");
        $steps[] = ['ok', 'user_type ENUM extended — "admin" value is now available.'];
    } catch (PDOException $e) {
        $steps[] = ['info', 'ENUM already includes "admin" (or could not alter): ' . $e->getMessage()];
    }

    // Step 2 — Check if admin already exists
    $stmt = $pdo->prepare("SELECT id, user_type FROM users WHERE username = ?");
    $stmt->execute([ADMIN_USERNAME]);
    $existing = $stmt->fetch();

    // Step 3 — Hash the password on THIS server
    $hash   = password_hash(ADMIN_PASSWORD, PASSWORD_BCRYPT, ['cost' => 12]);
    $verify = password_verify(ADMIN_PASSWORD, $hash);
    $steps[] = $verify
        ? ['ok',  'Password hash generated and verified successfully.']
        : ['fail','Hash verification FAILED — PHP bcrypt issue.'];

    // Step 4 — Upsert admin user
    if ($existing) {
        // Update password + force user_type to admin
        $stmt = $pdo->prepare(
            "UPDATE users SET password=?, user_type='admin', first_name='Mascot', last_name='Nuel'
             WHERE username=?"
        );
        $stmt->execute([$hash, ADMIN_USERNAME]);
        $steps[] = ['ok', 'Existing admin account updated — password reset to <strong>' . htmlspecialchars(ADMIN_PASSWORD) . '</strong>.'];
    } else {
        // Fresh insert
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, password, user_type, first_name, last_name)
                 VALUES (?, 'mascot@sproutlearn.admin', ?, 'admin', 'Mascot', 'Nuel')"
            );
            $stmt->execute([ADMIN_USERNAME, $hash]);
            $steps[] = ['ok', 'Admin account <strong>' . htmlspecialchars(ADMIN_USERNAME) . '</strong> created successfully.'];
        } catch (PDOException $e) {
            $errors[] = 'Failed to insert admin user: ' . $e->getMessage();
        }
    }

    // Step 5 — Final verification: try fetching and verifying
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([ADMIN_USERNAME]);
    $admin = $stmt->fetch();
    if ($admin && password_verify(ADMIN_PASSWORD, $admin['password'])) {
        $steps[] = ['ok', 'Login test passed — credentials verified against database.'];
    } else {
        $steps[] = ['fail', 'Login test FAILED — could not verify password against stored hash.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Setup - SproutLearn</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',sans-serif;background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:30px 20px;}
        .box{background:white;border-radius:20px;padding:36px 32px;max-width:620px;width:100%;box-shadow:0 8px 30px rgba(0,0,0,0.1);}
        .logo{display:flex;align-items:center;gap:12px;margin-bottom:24px;}
        .logo-icon{width:48px;height:48px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:20px;}
        h1{font-size:20px;color:#1e293b;}
        h1 span{font-size:13px;font-weight:400;color:#64748b;margin-left:8px;}
        .steps{list-style:none;margin:20px 0;display:flex;flex-direction:column;gap:10px;}
        .step{display:flex;align-items:flex-start;gap:12px;padding:12px 16px;border-radius:12px;font-size:14px;}
        .step-ok  {background:#dcfce7;color:#15803d;}
        .step-info{background:#f0f9ff;color:#0369a1;}
        .step-fail{background:#fee2e2;color:#dc2626;}
        .step-icon{font-size:16px;flex-shrink:0;margin-top:1px;}
        .error-box{background:#fee2e2;color:#dc2626;padding:14px 16px;border-radius:12px;font-size:14px;margin-bottom:16px;}
        .creds{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin:20px 0;font-size:14px;}
        .creds strong{color:#1e293b;}
        .creds code{background:#e2e8f0;padding:2px 8px;border-radius:6px;font-family:monospace;}
        .warn{background:#fef3c7;border-left:4px solid #f59e0b;padding:14px 16px;border-radius:10px;font-size:13px;color:#92400e;margin-top:16px;}
        .btn{display:inline-flex;align-items:center;gap:8px;margin-top:20px;padding:12px 28px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;border-radius:50px;text-decoration:none;font-weight:700;font-size:14px;transition:transform .2s;}
        .btn:hover{transform:translateY(-2px);}
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="box">
    <div class="logo">
        <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
        <h1>Admin Setup <span>SproutLearn</span></h1>
    </div>

    <?php foreach($errors as $e): ?>
        <div class="error-box"><i class="fas fa-times-circle"></i> <?= $e ?></div>
    <?php endforeach; ?>

    <?php if(empty($errors)): ?>
    <ul class="steps">
        <?php foreach($steps as [$type, $text]): ?>
        <li class="step step-<?= $type ?>">
            <span class="step-icon">
                <i class="fas fa-<?= $type==='ok'?'check-circle':($type==='fail'?'times-circle':'info-circle') ?>"></i>
            </span>
            <span><?= $text ?></span>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="creds">
        <strong>Admin Login Credentials:</strong><br><br>
        Username: <code><?= htmlspecialchars(ADMIN_USERNAME) ?></code><br>
        Password: <code><?= htmlspecialchars(ADMIN_PASSWORD) ?></code>
    </div>

    <div class="warn">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Security note:</strong> You can delete <code>admin-setup.php</code> after confirming login works.
        The admin account auto-provisions on first login, so this file is only needed for password resets.
    </div>

    <a href="login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Go to Login</a>
    <?php endif; ?>
</div>
</body>
</html>
