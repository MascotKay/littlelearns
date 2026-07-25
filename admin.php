<?php
require_once 'config.php';
requireLogin();
requireAdmin();

$pdo = getDBConnection();
$msg = '';
$msg_type = '';
$active = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

// ── Stats helper ──────────────────────────────────────────────────────────────
function qs($pdo,$sql,$p=[]){ $st=$pdo->prepare($sql); $st->execute($p); return $st->fetchColumn(); }

// ── POST Actions ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act'])) {
    $act = $_POST['act'];
    try {
        // ── Users
        if ($act === 'create_user') {
            $active = 'users';
            $hash = password_hash($_POST['pw'], PASSWORD_BCRYPT);
            $st = $pdo->prepare("INSERT INTO users (first_name,last_name,username,email,password,user_type) VALUES (?,?,?,?,?,?)");
            $st->execute([trim($_POST['fn']),trim($_POST['ln']),trim($_POST['un']),trim($_POST['em']),$hash,$_POST['ro']]);
            $msg='User created successfully.'; $msg_type='success';
        } elseif ($act === 'delete_user') {
            $active = 'users';
            $pdo->prepare("DELETE FROM users WHERE id=? AND username!=?")->execute([$_POST['uid'], ADMIN_USERNAME]);
            $msg='User deleted.'; $msg_type='success';
        } elseif ($act === 'change_role') {
            $active = 'users';
            $pdo->prepare("UPDATE users SET user_type=? WHERE id=? AND username!=?")->execute([$_POST['ro'],$_POST['uid'],ADMIN_USERNAME]);
            $msg='Role updated.'; $msg_type='success';
        } elseif ($act === 'reset_pw') {
            $active = 'users';
            $hash = password_hash($_POST['pw'], PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash,$_POST['uid']]);
            $msg='Password reset.'; $msg_type='success';
        } elseif ($act === 'link_parent') {
            $active = 'users';
            $pdo->prepare("INSERT IGNORE INTO parent_child (parent_id,student_id) VALUES (?,?)")->execute([$_POST['pid'],$_POST['sid']]);
            $msg='Parent linked to student.'; $msg_type='success';
        } elseif ($act === 'unlink_parent') {
            $active = 'users';
            $pdo->prepare("DELETE FROM parent_child WHERE parent_id=? AND student_id=?")->execute([$_POST['pid'],$_POST['sid']]);
            $msg='Parent unlinked.'; $msg_type='success';

        // ── Courses & Modules & Lessons
        } elseif ($act === 'create_course') {
            $active = 'courses';
            $pdo->prepare("INSERT INTO courses (title,description,category) VALUES (?,?,?)")->execute([trim($_POST['ct']),trim($_POST['cd']),trim($_POST['cc'])]);
            $msg='Course created.'; $msg_type='success';
        } elseif ($act === 'toggle_course') {
            $active = 'courses';
            $pdo->prepare("UPDATE courses SET is_active=1-is_active WHERE id=?")->execute([$_POST['cid']]);
            $msg='Course status toggled.'; $msg_type='success';
        } elseif ($act === 'delete_course') {
            $active = 'courses';
            $pdo->prepare("DELETE FROM courses WHERE id=?")->execute([$_POST['cid']]);
            $msg='Course deleted.'; $msg_type='success';
        } elseif ($act === 'create_module') {
            $active = 'courses';
            $mo = !empty($_POST['mo']) ? (int)$_POST['mo'] : 0;
            $pdo->prepare("INSERT INTO modules (course_id,title,description,module_order) VALUES (?,?,?,?)")->execute([(int)$_POST['mcourse_id'], trim($_POST['mt']), trim($_POST['md']), $mo]);
            $msg='Module created.'; $msg_type='success';
        } elseif ($act === 'delete_module') {
            $active = 'courses';
            $pdo->prepare("DELETE FROM modules WHERE id=?")->execute([(int)$_POST['modid']]);
            $msg='Module deleted (and all its lessons).'; $msg_type='success';
        } elseif ($act === 'create_lesson') {
            $active = 'courses';
            $pdo->prepare("INSERT INTO lessons (module_id,title,description,content,duration) VALUES (?,?,?,?,?)")->execute([$_POST['mid'],trim($_POST['lt']),trim($_POST['ld']),trim($_POST['lc']),(int)$_POST['ldur']]);
            $msg='Lesson created.'; $msg_type='success';
        } elseif ($act === 'delete_lesson') {
            $active = 'courses';
            $pdo->prepare("DELETE FROM lessons WHERE id=?")->execute([$_POST['lid']]);
            $msg='Lesson deleted.'; $msg_type='success';

        // ── Assignments
        } elseif ($act === 'create_assignment_admin') {
            $active = 'assignments';
            $cid = !empty($_POST['acid']) ? (int)$_POST['acid'] : null;
            $pdo->prepare("INSERT INTO assignments (course_id,title,description,due_date,points,is_active) VALUES (?,?,?,?,?,1)")
                ->execute([$cid, trim($_POST['at']), trim($_POST['ad']), $_POST['adue'], (int)$_POST['apt']]);
            $msg='Assignment created.'; $msg_type='success';
        } elseif ($act === 'toggle_assign') {
            $active = 'assignments';
            $pdo->prepare("UPDATE assignments SET is_active=1-is_active WHERE id=?")->execute([$_POST['aid']]);
            $msg='Assignment status toggled.'; $msg_type='success';
        } elseif ($act === 'delete_assign') {
            $active = 'assignments';
            $pdo->prepare("DELETE FROM assignments WHERE id=?")->execute([$_POST['aid']]);
            $msg='Assignment deleted.'; $msg_type='success';
        } elseif ($act === 'grade_sub') {
            $active = 'assignments';
            $pdo->prepare("UPDATE assignment_submissions SET grade=?,feedback=?,graded_at=NOW() WHERE id=?")->execute([$_POST['grade'],trim($_POST['fb']),$_POST['subid']]);
            $msg='Submission graded.'; $msg_type='success';

        // ── Quizzes
        } elseif ($act === 'create_quiz') {
            $active = 'quizzes';
            $lid = !empty($_POST['qlid']) && (int)$_POST['qlid'] > 0 ? (int)$_POST['qlid'] : null;
            $pdo->prepare("INSERT INTO quizzes (lesson_id,title,description,time_limit,passing_score,is_active) VALUES (?,?,?,?,?,1)")
                ->execute([$lid, trim($_POST['qt']), trim($_POST['qd']), (int)$_POST['qtl'], (int)$_POST['qps']]);
            $msg='Quiz created.'; $msg_type='success';
        } elseif ($act === 'add_question') {
            $active = 'quizzes';
            $qid = (int)$_POST['qid'];
            $st = $pdo->prepare("SELECT COALESCE(MAX(question_order),0)+1 FROM quiz_questions WHERE quiz_id=?");
            $st->execute([$qid]);
            $order = (int)$st->fetchColumn();
            $pdo->prepare("INSERT INTO quiz_questions (quiz_id,question_text,points,question_order) VALUES (?,?,?,?)")->execute([$qid, trim($_POST['qtext']), (int)$_POST['qpts'], $order]);
            $msg='Question added.'; $msg_type='success';
        } elseif ($act === 'add_option') {
            $active = 'quizzes';
            $qnid = (int)$_POST['question_id'];
            $st = $pdo->prepare("SELECT COALESCE(MAX(option_order),0)+1 FROM quiz_options WHERE question_id=?");
            $st->execute([$qnid]);
            $order = (int)$st->fetchColumn();
            $correct = isset($_POST['is_correct']) ? 1 : 0;
            if ($correct) { $pdo->prepare("UPDATE quiz_options SET is_correct=0 WHERE question_id=?")->execute([$qnid]); }
            $pdo->prepare("INSERT INTO quiz_options (question_id,option_text,is_correct,option_order) VALUES (?,?,?,?)")->execute([$qnid, trim($_POST['otext']), $correct, $order]);
            $msg='Option added.'; $msg_type='success';
        } elseif ($act === 'set_correct') {
            $active = 'quizzes';
            $oid = (int)$_POST['option_id'];
            $st = $pdo->prepare("SELECT question_id FROM quiz_options WHERE id=?"); $st->execute([$oid]);
            $qnid = $st->fetchColumn();
            $pdo->prepare("UPDATE quiz_options SET is_correct=0 WHERE question_id=?")->execute([$qnid]);
            $pdo->prepare("UPDATE quiz_options SET is_correct=1 WHERE id=?")->execute([$oid]);
            $msg='Correct answer updated.'; $msg_type='success';
        } elseif ($act === 'delete_question') {
            $active = 'quizzes';
            $pdo->prepare("DELETE FROM quiz_questions WHERE id=?")->execute([(int)$_POST['question_id']]);
            $msg='Question deleted.'; $msg_type='success';
        } elseif ($act === 'delete_option') {
            $active = 'quizzes';
            $pdo->prepare("DELETE FROM quiz_options WHERE id=?")->execute([(int)$_POST['option_id']]);
            $msg='Option deleted.'; $msg_type='success';
        } elseif ($act === 'toggle_quiz') {
            $active = 'quizzes';
            $pdo->prepare("UPDATE quizzes SET is_active=1-is_active WHERE id=?")->execute([$_POST['qid']]);
            $msg='Quiz status toggled.'; $msg_type='success';
        } elseif ($act === 'delete_quiz') {
            $active = 'quizzes';
            $pdo->prepare("DELETE FROM quizzes WHERE id=?")->execute([$_POST['qid']]);
            $msg='Quiz deleted.'; $msg_type='success';

        // ── Poems
        } elseif ($act === 'create_poem_admin') {
            $active = 'content';
            $imagePath = null;
            if (!empty($_FILES['poem_img']) && $_FILES['poem_img']['error'] === UPLOAD_ERR_OK) {
                $imagePath = uploadFile($_FILES['poem_img'], 'poems');
            }
            try { $pdo->exec("ALTER TABLE poems ADD COLUMN image_path VARCHAR(255) NULL"); } catch(PDOException $e2) {}
            $pdo->prepare("INSERT INTO poems (title,author,category,difficulty_level,reading_time,content,image_path) VALUES (?,?,?,?,?,?,?)")
                ->execute([trim($_POST['pt']),trim($_POST['pa']),$_POST['pcat'],$_POST['pdiff'],(int)$_POST['prt'],trim($_POST['pc']),$imagePath]);
            $msg='Poem added to library.'; $msg_type='success';
        } elseif ($act === 'delete_poem') {
            $active = 'content';
            $pdo->prepare("DELETE FROM poems WHERE id=?")->execute([$_POST['pid']]);
            $msg='Poem deleted.'; $msg_type='success';

        // ── System
        } elseif ($act === 'full_reset') {
            $active = 'system'; resetDemoData();
            $msg='All demo data has been reset.'; $msg_type='success';
        } elseif ($act === 'clear_attendance') {
            $active = 'system'; $pdo->exec("DELETE FROM attendance");
            $msg='Attendance records cleared.'; $msg_type='success';
        } elseif ($act === 'clear_messages') {
            $active = 'system'; $pdo->exec("DELETE FROM teacher_queries");
            $msg='Messages cleared.'; $msg_type='success';
        }
    } catch (PDOException $e) {
        $msg = 'Error: ' . $e->getMessage(); $msg_type = 'error';
    }
}

// ── Stats ─────────────────────────────────────────────────────────────────────
$stats = [
    'users'          => qs($pdo,"SELECT COUNT(*) FROM users WHERE username!=?",[ADMIN_USERNAME]),
    'students'       => qs($pdo,"SELECT COUNT(*) FROM users WHERE user_type='student' AND username!=?",[ADMIN_USERNAME]),
    'teachers'       => qs($pdo,"SELECT COUNT(*) FROM users WHERE user_type='teacher'"),
    'parents'        => qs($pdo,"SELECT COUNT(*) FROM users WHERE user_type='parent'"),
    'active_courses' => qs($pdo,"SELECT COUNT(*) FROM courses WHERE is_active=1"),
    'all_courses'    => qs($pdo,"SELECT COUNT(*) FROM courses"),
    'lessons'        => qs($pdo,"SELECT COUNT(*) FROM lessons"),
    'assignments'    => qs($pdo,"SELECT COUNT(*) FROM assignments"),
    'poems'          => qs($pdo,"SELECT COUNT(*) FROM poems"),
    'quizzes'        => qs($pdo,"SELECT COUNT(*) FROM quizzes"),
    'attempts'       => qs($pdo,"SELECT COUNT(*) FROM quiz_attempts"),
    'submissions'    => qs($pdo,"SELECT COUNT(*) FROM assignment_submissions"),
    'pending_grades' => qs($pdo,"SELECT COUNT(*) FROM assignment_submissions WHERE grade IS NULL"),
    'attendance'     => qs($pdo,"SELECT COUNT(*) FROM attendance"),
    'progress_done'  => qs($pdo,"SELECT COUNT(*) FROM student_progress WHERE completed=1"),
    'messages'       => qs($pdo,"SELECT COUNT(*) FROM teacher_queries"),
];

// ── Data Queries ──────────────────────────────────────────────────────────────
$users_stmt = $pdo->prepare("SELECT * FROM users WHERE username!=? ORDER BY user_type,first_name");
$users_stmt->execute([ADMIN_USERNAME]);
$users = $users_stmt->fetchAll();

$parents  = array_filter($users, fn($u) => $u['user_type']==='parent');
$students = array_filter($users, fn($u) => $u['user_type']==='student');

$pc_links = $pdo->query("SELECT pc.*,p.first_name AS pf,p.last_name AS pl,s.first_name AS sf,s.last_name AS sl FROM parent_child pc JOIN users p ON pc.parent_id=p.id JOIN users s ON pc.student_id=s.id ORDER BY p.first_name")->fetchAll();

$courses = $pdo->query("SELECT c.*,(SELECT COUNT(*) FROM modules m WHERE m.course_id=c.id) AS mc,(SELECT COUNT(*) FROM lessons l JOIN modules m ON l.module_id=m.id WHERE m.course_id=c.id) AS lc FROM courses c ORDER BY c.order_index,c.title")->fetchAll();

$modules = $pdo->query("SELECT m.*,c.title AS course_title FROM modules m JOIN courses c ON m.course_id=c.id ORDER BY c.title,m.module_order")->fetchAll();

$lessons_all = $pdo->query("SELECT l.id,l.title,l.duration,l.media_path,l.media_type,m.title AS module_title,c.title AS course_title FROM lessons l JOIN modules m ON l.module_id=m.id JOIN courses c ON m.course_id=c.id ORDER BY c.title,m.module_order,l.lesson_order")->fetchAll();

$assignments_all = $pdo->query("SELECT a.*,c.title AS course_title,(SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id=a.id) AS sub_count,(SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id=a.id AND s.grade IS NULL) AS ungraded FROM assignments a LEFT JOIN courses c ON a.course_id=c.id ORDER BY a.due_date")->fetchAll();

$all_subs = $pdo->query("SELECT s.*,a.title AS asgn_title,u.first_name,u.last_name,u.username FROM assignment_submissions s JOIN assignments a ON s.assignment_id=a.id JOIN users u ON s.student_id=u.id ORDER BY s.submitted_at DESC")->fetchAll();

$quizzes_all = $pdo->query("SELECT q.*,l.title AS lesson_title,(SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id=q.id) AS q_count,(SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id=q.id) AS a_count FROM quizzes q LEFT JOIN lessons l ON q.lesson_id=l.id ORDER BY q.created_at DESC")->fetchAll();

// Quiz builder: questions + options per quiz
$quiz_builder_data = [];
foreach($quizzes_all as $qz) {
    $st = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id=? ORDER BY question_order");
    $st->execute([$qz['id']]);
    $questions = $st->fetchAll();
    foreach($questions as &$q) {
        $st2 = $pdo->prepare("SELECT * FROM quiz_options WHERE question_id=? ORDER BY option_order");
        $st2->execute([$q['id']]);
        $q['options'] = $st2->fetchAll();
    }
    unset($q);
    $quiz_builder_data[$qz['id']] = $questions;
}

$poems_all = $pdo->query("SELECT * FROM poems ORDER BY title")->fetchAll();

$activity = $pdo->query("
    (SELECT 'quiz' AS type, u.first_name, u.last_name, q.title AS label, qa.score AS extra, qa.attempted_at AS ts
     FROM quiz_attempts qa JOIN users u ON qa.student_id=u.id JOIN quizzes q ON qa.quiz_id=q.id)
    UNION ALL
    (SELECT 'sub' AS type, u.first_name, u.last_name, a.title AS label, s.grade AS extra, s.submitted_at AS ts
     FROM assignment_submissions s JOIN users u ON s.student_id=u.id JOIN assignments a ON s.assignment_id=a.id)
    UNION ALL
    (SELECT 'lesson' AS type, u.first_name, u.last_name, l.title AS label, NULL AS extra, sp.completed_at AS ts
     FROM student_progress sp JOIN users u ON sp.student_id=u.id JOIN lessons l ON sp.lesson_id=l.id WHERE sp.completed=1)
    ORDER BY ts DESC LIMIT 20")->fetchAll();

$top_scorers = $pdo->query("SELECT u.first_name,u.last_name,AVG(qa.score) AS avg_score,COUNT(*) AS attempts FROM quiz_attempts qa JOIN users u ON qa.student_id=u.id GROUP BY qa.student_id ORDER BY avg_score DESC LIMIT 8")->fetchAll();

$student_monitor = $pdo->query("SELECT u.id,u.first_name,u.last_name,u.username,
    (SELECT COUNT(*) FROM student_progress sp WHERE sp.student_id=u.id AND sp.completed=1) AS lessons_done,
    (SELECT COUNT(*) FROM assignment_submissions s WHERE s.student_id=u.id) AS submissions,
    (SELECT COUNT(DISTINCT date) FROM attendance a WHERE a.student_id=u.id) AS attendance_days,
    (SELECT ROUND(AVG(qa.score),1) FROM quiz_attempts qa WHERE qa.student_id=u.id) AS avg_quiz
    FROM users u WHERE u.user_type='student' ORDER BY u.first_name")->fetchAll();

$db_tables = ['users','courses','modules','lessons','assignments','assignment_submissions','attendance','student_progress','poems','quizzes','quiz_attempts','teacher_queries'];
$table_counts = [];
foreach ($db_tables as $tbl) {
    try { $table_counts[$tbl] = $pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn(); }
    catch(Exception $e) { $table_counts[$tbl] = '—'; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Panel — SproutLearn</title>
<meta name="description" content="SproutLearn admin panel — manage users, courses, quizzes, and content.">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:'Segoe UI',system-ui,sans-serif;font-size:14px;color:#e2e8f0}
body{background:linear-gradient(135deg,#0f0c29,#302b63,#24243e,#302b63);background-size:400% 400%;animation:bgAnim 18s ease infinite;min-height:100vh}
@keyframes bgAnim{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}

/* Particles */
.particles{position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0;overflow:hidden}
.particle{position:absolute;bottom:-20px;border-radius:50%;background:rgba(255,255,255,0.12);animation:rise linear infinite}
.particle:nth-child(1){left:8%;width:8px;height:8px;animation-duration:12s;animation-delay:0s}
.particle:nth-child(2){left:20%;width:5px;height:5px;animation-duration:15s;animation-delay:3s}
.particle:nth-child(3){left:35%;width:10px;height:10px;animation-duration:10s;animation-delay:1s}
.particle:nth-child(4){left:52%;width:6px;height:6px;animation-duration:13s;animation-delay:4s}
.particle:nth-child(5){left:65%;width:9px;height:9px;animation-duration:11s;animation-delay:2s}
.particle:nth-child(6){left:78%;width:5px;height:5px;animation-duration:14s;animation-delay:5s}
.particle:nth-child(7){left:90%;width:7px;height:7px;animation-duration:9s;animation-delay:1.5s}
@keyframes rise{0%{transform:translateY(0) scale(1);opacity:.8}100%{transform:translateY(-110vh) scale(0.3);opacity:0}}

/* Hamburger */
.hamburger{display:none;position:fixed;top:14px;left:14px;z-index:300;background:rgba(229,62,62,0.9);border:none;border-radius:8px;padding:9px 12px;cursor:pointer;color:#fff;font-size:16px;align-items:center;justify-content:center;backdrop-filter:blur(8px);transition:background .2s}
.hamburger:hover{background:rgba(229,62,62,1)}
.overlay{display:none;position:fixed;inset:0;z-index:150;background:rgba(0,0,0,0.5)}
.overlay.on{display:block}

/* Sidebar */
#sidebar{position:fixed;top:0;left:0;width:252px;height:100vh;background:rgba(10,8,30,0.97);border-right:1px solid rgba(255,255,255,0.08);display:flex;flex-direction:column;z-index:200;overflow-y:auto;transition:transform .3s ease}
.sb-header{padding:20px 16px 16px;border-bottom:1px solid rgba(255,255,255,0.08)}
.sb-avatar{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#e53e3e,#9b2335);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;margin:0 auto 10px;letter-spacing:1px}
.sb-name{text-align:center;font-size:13px;font-weight:600;color:#f0f0f0}
.sb-badge{display:inline-block;background:#e53e3e;color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-top:4px;text-transform:uppercase;letter-spacing:.5px}
.sb-section{padding:12px 10px 4px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:rgba(255,255,255,0.35)}
.lk{display:flex;align-items:center;gap:9px;padding:9px 14px;border-radius:8px;cursor:pointer;border:none;background:transparent;color:rgba(255,255,255,0.65);font-size:13px;width:100%;text-align:left;transition:all .2s;position:relative;text-decoration:none}
button.lk{cursor:pointer}
.lk:hover{background:rgba(255,255,255,0.07);color:#fff}
.lk.on{background:linear-gradient(90deg,rgba(229,62,62,0.25),rgba(229,62,62,0.05));color:#fc8181;border-left:3px solid #e53e3e}
.lk i{width:18px;text-align:center;font-size:13px;flex-shrink:0}
.badge-pill{background:#e53e3e;color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;margin-left:auto}
.sb-footer{margin-top:auto;padding:12px 10px;border-top:1px solid rgba(255,255,255,0.08)}
.lk-logout{color:rgba(255,100,100,0.75)!important}
.lk-logout:hover{background:rgba(229,62,62,0.15)!important;color:#fc8181!important}

/* Main */
#main{margin-left:252px;padding:24px;position:relative;z-index:1;min-height:100vh;transition:margin-left .3s ease}

/* Alert */
.alert{padding:12px 18px;border-radius:10px;margin-bottom:18px;font-size:13px;display:flex;align-items:center;gap:10px;animation:fadeSlideIn .4s ease}
.alert.success{background:rgba(72,187,120,0.15);border:1px solid rgba(72,187,120,0.4);color:#9ae6b4}
.alert.error{background:rgba(245,101,101,0.15);border:1px solid rgba(245,101,101,0.4);color:#feb2b2}

/* Panes */
.pane{display:none;animation:fadeUp .35s ease}
.pane.on{display:block}
@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeSlideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}

/* Section header */
.sec-head{margin-bottom:20px}
.sec-head h2{font-size:22px;font-weight:700;color:#e2e8f0}
.sec-head p{color:rgba(255,255,255,0.45);font-size:13px;margin-top:2px}

/* Cards */
.cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:14px;padding:18px 16px;text-align:center;transition:transform .2s,box-shadow .2s;animation:fadeUp .4s ease both}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,0.3)}
.stat-card .icon{font-size:22px;margin-bottom:8px}
.stat-card .num{font-size:28px;font-weight:800;line-height:1}
.stat-card .lbl{font-size:11px;color:rgba(255,255,255,0.5);margin-top:4px;text-transform:uppercase;letter-spacing:.6px}

/* Color accents */
.c-blue .icon,.c-blue .num{color:#63b3ed}
.c-green .icon,.c-green .num{color:#68d391}
.c-orange .icon,.c-orange .num{color:#f6ad55}
.c-purple .icon,.c-purple .num{color:#b794f4}
.c-pink .icon,.c-pink .num{color:#fc8181}
.c-teal .icon,.c-teal .num{color:#4fd1c5}
.c-yellow .icon,.c-yellow .num{color:#faf089}
.c-red .icon,.c-red .num{color:#e53e3e}

/* Panel boxes */
.panel{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.09);border-radius:14px;padding:20px;margin-bottom:20px}
.panel h3{font-size:15px;font-weight:600;margin-bottom:14px;color:#e2e8f0;display:flex;align-items:center;gap:8px}

/* Tables */
.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px}
th{text-align:left;padding:9px 12px;border-bottom:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.5);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap}
td{padding:9px 12px;border-bottom:1px solid rgba(255,255,255,0.05);color:#e2e8f0;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(255,255,255,0.03)}

/* Search bar */
.search-bar{display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:9px;padding:8px 14px;margin-bottom:14px}
.search-bar i{color:rgba(255,255,255,0.35);font-size:13px}
.search-bar input{background:transparent;border:none;outline:none;color:#e2e8f0;font-size:13px;width:100%;font-family:inherit}
.search-bar input::placeholder{color:rgba(255,255,255,0.3)}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:7px;border:none;cursor:pointer;font-size:12px;font-weight:600;transition:all .2s;text-decoration:none}
.btn-sm{padding:4px 9px;font-size:11px}
.btn-primary{background:linear-gradient(135deg,#4299e1,#3182ce);color:#fff}
.btn-primary:hover{filter:brightness(1.15)}
.btn-success{background:linear-gradient(135deg,#48bb78,#38a169);color:#fff}
.btn-success:hover{filter:brightness(1.15)}
.btn-danger{background:linear-gradient(135deg,#e53e3e,#c53030);color:#fff}
.btn-danger:hover{filter:brightness(1.15)}
.btn-warning{background:linear-gradient(135deg,#ed8936,#dd6b20);color:#fff}
.btn-warning:hover{filter:brightness(1.15)}
.btn-secondary{background:rgba(255,255,255,0.1);color:#e2e8f0}
.btn-secondary:hover{background:rgba(255,255,255,0.15)}

/* Forms */
.form-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:14px}
.form-group{display:flex;flex-direction:column;gap:5px}
.form-group label{font-size:11px;font-weight:600;color:rgba(255,255,255,0.55);text-transform:uppercase;letter-spacing:.5px}
input[type=text],input[type=email],input[type=password],input[type=number],input[type=datetime-local],select,textarea{background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.15);border-radius:7px;color:#e2e8f0;padding:8px 11px;font-size:13px;font-family:inherit;width:100%;outline:none;transition:border-color .2s}
input[type=text]:focus,input[type=email]:focus,input[type=password]:focus,input[type=number]:focus,input[type=datetime-local]:focus,select:focus,textarea:focus{border-color:rgba(99,179,237,0.7);background:rgba(255,255,255,0.1)}
textarea{resize:vertical;min-height:80px}
select option{background:#1a1a2e;color:#e2e8f0}

/* Tags */
.tag{display:inline-block;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase}
.tag-active{background:rgba(72,187,120,0.2);color:#68d391;border:1px solid rgba(72,187,120,0.3)}
.tag-inactive{background:rgba(245,101,101,0.2);color:#fc8181;border:1px solid rgba(245,101,101,0.3)}
.tag-student{background:rgba(99,179,237,0.2);color:#63b3ed;border:1px solid rgba(99,179,237,0.3)}
.tag-teacher{background:rgba(183,148,244,0.2);color:#b794f4;border:1px solid rgba(183,148,244,0.3)}
.tag-parent{background:rgba(246,173,85,0.2);color:#f6ad55;border:1px solid rgba(246,173,85,0.3)}
.tag-admin{background:rgba(229,62,62,0.2);color:#fc8181;border:1px solid rgba(229,62,62,0.3)}
.tag-correct{background:rgba(72,187,120,0.2);color:#68d391;border:1px solid rgba(72,187,120,0.3);font-size:9px}

/* Progress bar */
.bar-wrap{background:rgba(255,255,255,0.1);border-radius:4px;height:6px;overflow:hidden}
.bar-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,#63b3ed,#b794f4)}

/* Activity dot */
.dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.dot-quiz{background:#b794f4}
.dot-sub{background:#68d391}
.dot-lesson{background:#f6ad55}

/* Score colors */
.score-good{color:#68d391}
.score-bad{color:#fc8181}

/* Grade card */
.grade-card{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.09);border-radius:10px;padding:14px;margin-bottom:12px}

/* System health */
.health-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}
.health-item{background:rgba(255,255,255,0.04);border-radius:8px;padding:12px;font-size:12px}
.health-item .hk{color:rgba(255,255,255,0.45);margin-bottom:3px;font-size:11px}
.health-item .hv{color:#e2e8f0;font-weight:600}

/* Quiz builder */
.quiz-builder-box{border:1px solid rgba(255,255,255,0.09);border-radius:10px;margin-bottom:14px;overflow:hidden}
.quiz-builder-summary{padding:12px 16px;background:rgba(255,255,255,0.04);cursor:pointer;display:flex;align-items:center;justify-content:space-between;font-size:13px;font-weight:600;list-style:none;user-select:none}
.quiz-builder-summary:hover{background:rgba(255,255,255,0.07)}
.quiz-builder-summary::marker,.quiz-builder-summary::-webkit-details-marker{display:none}
.quiz-builder-body{padding:16px;background:rgba(0,0,0,0.15)}
.question-card{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:8px;padding:12px;margin-bottom:10px}
.question-card .q-head{display:flex;align-items:flex-start;gap:10px;justify-content:space-between;margin-bottom:8px}
.question-card .q-text{font-size:13px;font-weight:600;flex:1}
.option-row{display:flex;align-items:center;gap:8px;padding:5px 8px;border-radius:6px;background:rgba(255,255,255,0.03);margin-bottom:4px;font-size:12px}
.option-row.is-correct{background:rgba(72,187,120,0.08);border:1px solid rgba(72,187,120,0.2)}

details summary{cursor:pointer;padding:8px 0;color:rgba(255,255,255,0.6);font-size:12px;list-style:none}
details summary::before{content:'▶ ';font-size:10px}
details[open] summary::before{content:'▼ '}

/* Responsive */
@media(max-width:768px){
  .hamburger{display:flex}
  #sidebar{transform:translateX(-260px)}
  #sidebar.open{transform:translateX(0)}
  #main{margin-left:0;padding:16px;padding-top:56px}
  .cards-grid{grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px}
  .form-row{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- Hamburger -->
<button class="hamburger" id="hamburger" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></button>
<div class="overlay" id="overlay"></div>

<div class="particles">
<?php for($i=1;$i<=7;$i++): ?><div class="particle"></div><?php endfor; ?>
</div>

<!-- ── SIDEBAR ── -->
<nav id="sidebar">
  <div class="sb-header">
    <?php $adm_fn = $_SESSION['first_name'] ?? 'Admin'; $adm_ln = $_SESSION['last_name'] ?? ''; $adm_init = strtoupper(substr($adm_fn,0,1).substr($adm_ln,0,1)) ?: strtoupper(substr($adm_fn,0,2)); ?>
    <div class="sb-avatar"><?= htmlspecialchars($adm_init) ?></div>
    <div class="sb-name"><?= htmlspecialchars(trim($adm_fn.' '.$adm_ln)) ?></div>
    <div style="text-align:center"><span class="sb-badge"><i class="fas fa-shield-alt"></i> Admin</span></div>
  </div>

  <div class="sb-section">Monitor</div>
  <button class="lk <?= $active==='overview' ? 'on' : '' ?>" onclick="sw('overview',this)"><i class="fas fa-tachometer-alt"></i> Dashboard</button>
  <button class="lk <?= $active==='activity' ? 'on' : '' ?>" onclick="sw('activity',this)"><i class="fas fa-bolt"></i> Live Activity</button>
  <button class="lk <?= $active==='students' ? 'on' : '' ?>" onclick="sw('students',this)"><i class="fas fa-user-graduate"></i> Student Monitor</button>

  <div class="sb-section">Control</div>
  <button class="lk <?= $active==='users' ? 'on' : '' ?>" onclick="sw('users',this)">
    <i class="fas fa-users"></i> Users
    <?php if($stats['users']>0): ?><span class="badge-pill" style="background:rgba(99,179,237,0.7)"><?= $stats['users'] ?></span><?php endif; ?>
  </button>
  <button class="lk <?= $active==='courses' ? 'on' : '' ?>" onclick="sw('courses',this)"><i class="fas fa-book-open"></i> Courses &amp; Lessons</button>
  <button class="lk <?= $active==='assignments' ? 'on' : '' ?>" onclick="sw('assignments',this)">
    <i class="fas fa-tasks"></i> Assignments
    <?php if($stats['pending_grades']>0): ?><span class="badge-pill"><?= $stats['pending_grades'] ?></span><?php endif; ?>
  </button>
  <button class="lk <?= $active==='quizzes' ? 'on' : '' ?>" onclick="sw('quizzes',this)"><i class="fas fa-question-circle"></i> Quizzes</button>
  <button class="lk <?= $active==='content' ? 'on' : '' ?>" onclick="sw('content',this)"><i class="fas fa-feather-alt"></i> Poems</button>

  <div class="sb-section">System</div>
  <button class="lk <?= $active==='system' ? 'on' : '' ?>" onclick="sw('system',this)"><i class="fas fa-cogs"></i> System &amp; Tools</button>
  <a class="lk" href="home.php"><i class="fas fa-arrow-left"></i> Back to Site</a>

  <div class="sb-footer">
    <a class="lk lk-logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</nav>

<!-- ── MAIN ── -->
<div id="main">

<?php if($msg): ?>
<div class="alert <?= $msg_type ?>" id="main-alert">
  <i class="fas <?= $msg_type==='success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
  <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- ════════════════ PANE: OVERVIEW ════════════════ -->
<div class="pane <?= $active==='overview'?'on':'' ?>" id="pane-overview">
  <div class="sec-head"><h2><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h2><p>Welcome back, <?= htmlspecialchars($_SESSION['first_name'] ?? 'Admin') ?>. Here's what's happening on SproutLearn.</p></div>

  <div class="cards-grid">
    <?php
    $card_data = [
      ['icon'=>'fa-users','num'=>$stats['users'],'lbl'=>'Total Users','cls'=>'c-blue'],
      ['icon'=>'fa-user-graduate','num'=>$stats['students'],'lbl'=>'Students','cls'=>'c-green'],
      ['icon'=>'fa-chalkboard-teacher','num'=>$stats['teachers'],'lbl'=>'Teachers','cls'=>'c-purple'],
      ['icon'=>'fa-user-friends','num'=>$stats['parents'],'lbl'=>'Parents','cls'=>'c-orange'],
      ['icon'=>'fa-book-open','num'=>$stats['active_courses'],'lbl'=>'Active Courses','cls'=>'c-teal'],
      ['icon'=>'fa-play-circle','num'=>$stats['lessons'],'lbl'=>'Lessons','cls'=>'c-blue'],
      ['icon'=>'fa-tasks','num'=>$stats['assignments'],'lbl'=>'Assignments','cls'=>'c-yellow'],
      ['icon'=>'fa-feather-alt','num'=>$stats['poems'],'lbl'=>'Poems','cls'=>'c-pink'],
      ['icon'=>'fa-question-circle','num'=>$stats['quizzes'],'lbl'=>'Quizzes','cls'=>'c-purple'],
      ['icon'=>'fa-bolt','num'=>$stats['attempts'],'lbl'=>'Quiz Attempts','cls'=>'c-orange'],
      ['icon'=>'fa-paper-plane','num'=>$stats['submissions'],'lbl'=>'Submissions','cls'=>'c-teal'],
      ['icon'=>'fa-clock','num'=>$stats['pending_grades'],'lbl'=>'Pending Grades','cls'=>'c-red'],
    ];
    foreach($card_data as $i=>$c): ?>
    <div class="stat-card <?= $c['cls'] ?>" style="animation-delay:<?= $i*0.04 ?>s">
      <div class="icon"><i class="fas <?= $c['icon'] ?>"></i></div>
      <div class="num"><?= $c['num'] ?></div>
      <div class="lbl"><?= $c['lbl'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:20px">
    <div class="panel">
      <h3><i class="fas fa-trophy" style="color:#f6ad55"></i> Top Quiz Scorers</h3>
      <?php if(empty($top_scorers)): ?>
        <p style="color:rgba(255,255,255,0.4);font-size:13px">No quiz attempts yet.</p>
      <?php else: ?>
      <table><thead><tr><th>#</th><th>Student</th><th>Avg Score</th><th>Attempts</th></tr></thead><tbody>
      <?php foreach($top_scorers as $i=>$ts): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($ts['first_name'].' '.$ts['last_name']) ?></td>
          <td class="<?= $ts['avg_score']>=70?'score-good':'score-bad' ?>"><?= round($ts['avg_score'],1) ?>%</td>
          <td><?= $ts['attempts'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
      <?php endif; ?>
    </div>
    <div class="panel">
      <h3><i class="fas fa-chart-bar" style="color:#63b3ed"></i> User Breakdown</h3>
      <?php
      $total_u = max(1, $stats['users']);
      $breakdown = [
        ['Students', $stats['students'], '#63b3ed'],
        ['Teachers', $stats['teachers'], '#b794f4'],
        ['Parents',  $stats['parents'],  '#f6ad55'],
      ];
      foreach($breakdown as $b): $pct = round($b[1]/$total_u*100); ?>
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:12px">
          <span><?= $b[0] ?></span><span><?= $b[1] ?> (<?= $pct ?>%)</span>
        </div>
        <div class="bar-wrap"><div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $b[2] ?>"></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="panel">
    <h3><i class="fas fa-heartbeat" style="color:#68d391"></i> System Health</h3>
    <div class="health-grid">
      <div class="health-item"><div class="hk">PHP Version</div><div class="hv"><?= PHP_VERSION ?></div></div>
      <div class="health-item"><div class="hk">DB Connection</div><div class="hv" style="color:#68d391">Connected ✓</div></div>
      <div class="health-item"><div class="hk">Attendance Records</div><div class="hv"><?= $stats['attendance'] ?></div></div>
      <div class="health-item"><div class="hk">Lessons Completed</div><div class="hv"><?= $stats['progress_done'] ?></div></div>
      <div class="health-item"><div class="hk">Teacher Messages</div><div class="hv"><?= $stats['messages'] ?></div></div>
      <div class="health-item"><div class="hk">All Courses</div><div class="hv"><?= $stats['all_courses'] ?></div></div>
    </div>
  </div>
</div>

<!-- ════════════════ PANE: ACTIVITY ════════════════ -->
<div class="pane <?= $active==='activity'?'on':'' ?>" id="pane-activity">
  <div class="sec-head"><h2><i class="fas fa-bolt"></i> Live Activity Feed</h2><p>Most recent 20 events across the platform. Auto-refreshes every 60 seconds.</p></div>
  <div class="panel" id="activity-panel">
    <h3><i class="fas fa-stream"></i> Recent Events</h3>
    <?php if(empty($activity)): ?>
      <p style="color:rgba(255,255,255,0.4);font-size:13px">No activity recorded yet.</p>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:10px">
    <?php foreach($activity as $ev):
      $dot_cls = $ev['type']==='quiz' ? 'dot-quiz' : ($ev['type']==='sub' ? 'dot-sub' : 'dot-lesson');
      $type_lbl = $ev['type']==='quiz' ? 'Quiz' : ($ev['type']==='sub' ? 'Submission' : 'Lesson');
      $icon = $ev['type']==='quiz' ? 'fa-question-circle' : ($ev['type']==='sub' ? 'fa-paper-plane' : 'fa-check-circle');
      $extra = '';
      if($ev['type']==='quiz' && $ev['extra']!==null) $extra = ' — Score: '.$ev['extra'].'%';
      if($ev['type']==='sub' && $ev['extra']!==null) $extra = ' — Grade: '.$ev['extra'];
    ?>
    <div style="display:flex;align-items:center;gap:12px;padding:9px 12px;background:rgba(255,255,255,0.04);border-radius:8px">
      <div class="dot <?= $dot_cls ?>"></div>
      <div style="flex:1">
        <div style="font-size:13px;font-weight:600"><?= htmlspecialchars($ev['first_name'].' '.$ev['last_name']) ?> — <?= htmlspecialchars($ev['label']) ?></div>
        <div style="font-size:11px;color:rgba(255,255,255,0.45)"><?= $type_lbl ?><?= htmlspecialchars($extra) ?> &nbsp;·&nbsp; <?= $ev['ts'] ? date('M j, g:i a', strtotime($ev['ts'])) : 'N/A' ?></div>
      </div>
      <i class="fas <?= $icon ?>" style="color:rgba(255,255,255,0.25)"></i>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ════════════════ PANE: STUDENT MONITOR ════════════════ -->
<div class="pane <?= $active==='students'?'on':'' ?>" id="pane-students">
  <div class="sec-head"><h2><i class="fas fa-user-graduate"></i> Student Monitor</h2><p>Per-student performance overview across all subjects.</p></div>
  <div class="panel">
    <div class="tbl-wrap">
    <table>
      <thead><tr><th>Student</th><th>Username</th><th>Lessons Done</th><th>Submissions</th><th>Attendance Days</th><th>Avg Quiz</th><th>Progress</th></tr></thead>
      <tbody>
      <?php
      $total_lessons = max(1,(int)$stats['lessons']);
      foreach($student_monitor as $sm):
        $pct = $total_lessons > 0 ? min(100, round(($sm['lessons_done']/$total_lessons)*100)) : 0;
        $avg = $sm['avg_quiz']!==null ? (float)$sm['avg_quiz'] : null;
      ?>
      <tr>
        <td><?= htmlspecialchars($sm['first_name'].' '.$sm['last_name']) ?></td>
        <td style="color:rgba(255,255,255,0.5)"><?= htmlspecialchars($sm['username']) ?></td>
        <td><?= $sm['lessons_done'] ?> / <?= $total_lessons ?></td>
        <td><?= $sm['submissions'] ?></td>
        <td><?= $sm['attendance_days'] ?></td>
        <td class="<?= $avg===null?'':($avg>=70?'score-good':'score-bad') ?>">
          <?= $avg===null ? '<span style="color:rgba(255,255,255,0.3)">N/A</span>' : round($avg,1).'%' ?>
        </td>
        <td style="min-width:100px">
          <div class="bar-wrap"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
          <div style="font-size:10px;color:rgba(255,255,255,0.4);margin-top:3px"><?= $pct ?>%</div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($student_monitor)): ?>
        <tr><td colspan="7" style="text-align:center;color:rgba(255,255,255,0.35);padding:20px">No students found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- ════════════════ PANE: USERS ════════════════ -->
<div class="pane <?= $active==='users'?'on':'' ?>" id="pane-users">
  <div class="sec-head"><h2><i class="fas fa-users"></i> User Management</h2><p>Create, edit roles, reset passwords, and manage parent-child links.</p></div>

  <!-- Create User -->
  <div class="panel">
    <h3><i class="fas fa-user-plus" style="color:#68d391"></i> Create New User</h3>
    <form method="POST" id="create-user-form">
      <input type="hidden" name="act" value="create_user">
      <div class="form-row">
        <div class="form-group"><label>First Name</label><input type="text" name="fn" required placeholder="First name"></div>
        <div class="form-group"><label>Last Name</label><input type="text" name="ln" required placeholder="Last name"></div>
        <div class="form-group"><label>Username</label><input type="text" name="un" required placeholder="username"></div>
        <div class="form-group"><label>Email</label><input type="email" name="em" required placeholder="email@example.com"></div>
        <div class="form-group"><label>Password</label><input type="password" name="pw" id="new-pw" required placeholder="Password"></div>
        <div class="form-group"><label>Confirm Password</label><input type="password" id="confirm-pw" required placeholder="Confirm password"></div>
        <div class="form-group"><label>Role</label>
          <select name="ro">
            <option value="student">Student</option>
            <option value="teacher">Teacher</option>
            <option value="parent">Parent</option>
          </select>
        </div>
      </div>
      <div id="pw-mismatch" style="display:none;color:#fc8181;font-size:12px;margin-bottom:10px"><i class="fas fa-exclamation-circle"></i> Passwords do not match.</div>
      <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Create User</button>
    </form>
  </div>

  <!-- All Users Table -->
  <div class="panel">
    <h3><i class="fas fa-list" style="color:#63b3ed"></i> All Users <span style="font-size:12px;font-weight:400;color:rgba(255,255,255,0.4)">(<?= count($users) ?>)</span></h3>
    <div class="search-bar"><i class="fas fa-search"></i><input type="text" id="user-search" placeholder="Search by name, username or email…"></div>
    <div class="tbl-wrap">
    <table id="users-table">
      <thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
      <tr>
        <td style="color:rgba(255,255,255,0.35)"><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></td>
        <td style="color:rgba(255,255,255,0.5)"><?= htmlspecialchars($u['username']) ?></td>
        <td style="color:rgba(255,255,255,0.5)"><?= htmlspecialchars($u['email']) ?></td>
        <td><span class="tag tag-<?= $u['user_type'] ?>"><?= ucfirst($u['user_type']) ?></span></td>
        <td style="color:rgba(255,255,255,0.4);font-size:11px"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
        <td>
          <form method="POST" style="display:inline-flex;gap:5px;align-items:center">
            <input type="hidden" name="act" value="change_role">
            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
            <select name="ro" style="padding:4px 6px;font-size:11px;width:auto">
              <option value="student" <?= $u['user_type']==='student'?'selected':'' ?>>Student</option>
              <option value="teacher" <?= $u['user_type']==='teacher'?'selected':'' ?>>Teacher</option>
              <option value="parent"  <?= $u['user_type']==='parent' ?'selected':'' ?>>Parent</option>
            </select>
            <button type="submit" class="btn btn-sm btn-secondary">Set</button>
          </form>
          &nbsp;
          <form method="POST" style="display:inline-flex;gap:5px;align-items:center" onsubmit="return confirm('Reset password for <?= htmlspecialchars(addslashes($u['username'])) ?>?')">
            <input type="hidden" name="act" value="reset_pw">
            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
            <input type="password" name="pw" placeholder="New pw" required style="padding:4px 7px;font-size:11px;width:90px">
            <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-key"></i></button>
          </form>
          &nbsp;
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete user <?= htmlspecialchars(addslashes($u['username'])) ?>? This cannot be undone.')">
            <input type="hidden" name="act" value="delete_user">
            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($users)): ?>
        <tr><td colspan="7" style="text-align:center;color:rgba(255,255,255,0.35);padding:20px">No users found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- Parent-Child Links -->
  <div class="panel">
    <h3><i class="fas fa-link" style="color:#f6ad55"></i> Parent–Child Links</h3>
    <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px">
      <input type="hidden" name="act" value="link_parent">
      <div class="form-group" style="min-width:160px"><label>Parent</label>
        <select name="pid">
          <?php foreach($parents as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></option><?php endforeach; ?>
          <?php if(empty($parents)): ?><option disabled>No parents</option><?php endif; ?>
        </select>
      </div>
      <div class="form-group" style="min-width:160px"><label>Student</label>
        <select name="sid">
          <?php foreach($students as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></option><?php endforeach; ?>
          <?php if(empty($students)): ?><option disabled>No students</option><?php endif; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Link</button>
    </form>
    <div class="tbl-wrap">
    <table>
      <thead><tr><th>Parent</th><th>Student</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($pc_links as $lk_row): ?>
      <tr>
        <td><?= htmlspecialchars($lk_row['pf'].' '.$lk_row['pl']) ?></td>
        <td><?= htmlspecialchars($lk_row['sf'].' '.$lk_row['sl']) ?></td>
        <td>
          <form method="POST" onsubmit="return confirm('Unlink this parent-child relationship?')">
            <input type="hidden" name="act" value="unlink_parent">
            <input type="hidden" name="pid" value="<?= $lk_row['parent_id'] ?>">
            <input type="hidden" name="sid" value="<?= $lk_row['student_id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-unlink"></i> Unlink</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($pc_links)): ?>
        <tr><td colspan="3" style="text-align:center;color:rgba(255,255,255,0.35);padding:16px">No parent-child links.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- ════════════════ PANE: COURSES & LESSONS ════════════════ -->
<div class="pane <?= $active==='courses'?'on':'' ?>" id="pane-courses">
  <div class="sec-head"><h2><i class="fas fa-book-open"></i> Courses &amp; Lessons</h2><p>Manage courses, modules, and lessons.</p></div>

  <!-- Create Course -->
  <div class="panel">
    <h3><i class="fas fa-plus-circle" style="color:#68d391"></i> Create New Course</h3>
    <form method="POST">
      <input type="hidden" name="act" value="create_course">
      <div class="form-row">
        <div class="form-group"><label>Title</label><input type="text" name="ct" required placeholder="Course title"></div>
        <div class="form-group"><label>Category</label><input type="text" name="cc" placeholder="e.g. Literacy, Math"></div>
        <div class="form-group" style="grid-column:1/-1"><label>Description</label><textarea name="cd" placeholder="Course description…"></textarea></div>
      </div>
      <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Create Course</button>
    </form>
  </div>

  <!-- Courses Table -->
  <div class="panel">
    <h3><i class="fas fa-list" style="color:#63b3ed"></i> All Courses</h3>
    <div class="tbl-wrap">
    <table>
      <thead><tr><th>ID</th><th>Title</th><th>Category</th><th>Modules</th><th>Lessons</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($courses as $c): ?>
      <tr>
        <td style="color:rgba(255,255,255,0.35)"><?= $c['id'] ?></td>
        <td><?= htmlspecialchars($c['title']) ?></td>
        <td style="color:rgba(255,255,255,0.5)"><?= htmlspecialchars($c['category'] ?? '—') ?></td>
        <td><?= $c['mc'] ?></td>
        <td><?= $c['lc'] ?></td>
        <td><span class="tag <?= $c['is_active']?'tag-active':'tag-inactive' ?>"><?= $c['is_active']?'Active':'Inactive' ?></span></td>
        <td>
          <form method="POST" style="display:inline">
            <input type="hidden" name="act" value="toggle_course">
            <input type="hidden" name="cid" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-toggle-on"></i> Toggle</button>
          </form>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete course &quot;<?= htmlspecialchars(addslashes($c['title'])) ?>&quot; and all its content?')">
            <input type="hidden" name="act" value="delete_course">
            <input type="hidden" name="cid" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($courses)): ?>
        <tr><td colspan="7" style="text-align:center;color:rgba(255,255,255,0.35);padding:20px">No courses yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- Create Module -->
  <div class="panel">
    <h3><i class="fas fa-folder-plus" style="color:#4fd1c5"></i> Add Module to Course</h3>
    <form method="POST">
      <input type="hidden" name="act" value="create_module">
      <div class="form-row">
        <div class="form-group"><label>Course</label>
          <select name="mcourse_id" required>
            <?php foreach($courses as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option><?php endforeach; ?>
            <?php if(empty($courses)): ?><option disabled>No courses yet</option><?php endif; ?>
          </select>
        </div>
        <div class="form-group"><label>Module Title</label><input type="text" name="mt" required placeholder="e.g. Letters and Sounds"></div>
        <div class="form-group"><label>Order</label><input type="number" name="mo" value="1" min="1" max="999"></div>
        <div class="form-group" style="grid-column:1/-1"><label>Description</label><input type="text" name="md" placeholder="Brief module description"></div>
      </div>
      <button type="submit" class="btn btn-success" <?= empty($courses)?'disabled':'' ?>><i class="fas fa-plus"></i> Add Module</button>
    </form>
  </div>

  <!-- Modules Table -->
  <div class="panel">
    <h3><i class="fas fa-layer-group" style="color:#f6ad55"></i> All Modules</h3>
    <div class="tbl-wrap">
    <table>
      <thead><tr><th>ID</th><th>Course</th><th>Title</th><th>Order</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($modules as $mod): ?>
      <tr>
        <td style="color:rgba(255,255,255,0.35)"><?= $mod['id'] ?></td>
        <td style="color:rgba(255,255,255,0.5)"><?= htmlspecialchars($mod['course_title']) ?></td>
        <td><?= htmlspecialchars($mod['title']) ?></td>
        <td><?= $mod['module_order'] ?></td>
        <td>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete module &quot;<?= htmlspecialchars(addslashes($mod['title'])) ?>&quot; and all its lessons?')">
            <input type="hidden" name="act" value="delete_module">
            <input type="hidden" name="modid" value="<?= $mod['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($modules)): ?>
        <tr><td colspan="5" style="text-align:center;color:rgba(255,255,255,0.35);padding:20px">No modules yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- Add Lesson -->
  <div class="panel">
    <h3><i class="fas fa-plus-circle" style="color:#b794f4"></i> Add Lesson to Module</h3>
    <form method="POST">
      <input type="hidden" name="act" value="create_lesson">
      <div class="form-row">
        <div class="form-group">
          <label>Module</label>
          <select name="mid" required>
            <?php foreach($modules as $mod): ?>
              <option value="<?= $mod['id'] ?>"><?= htmlspecialchars($mod['course_title'].' › '.$mod['title']) ?></option>
            <?php endforeach; ?>
            <?php if(empty($modules)): ?><option disabled>No modules — create one first</option><?php endif; ?>
          </select>
        </div>
        <div class="form-group"><label>Title</label><input type="text" name="lt" required placeholder="Lesson title"></div>
        <div class="form-group"><label>Duration (min)</label><input type="number" name="ldur" value="15" min="1" max="180"></div>
        <div class="form-group"><label>Description</label><input type="text" name="ld" placeholder="Short description"></div>
        <div class="form-group" style="grid-column:1/-1"><label>Content (HTML allowed)</label><textarea name="lc" rows="4" placeholder="Lesson content…"></textarea></div>
      </div>
      <button type="submit" class="btn btn-success" <?= empty($modules)?'disabled':'' ?>><i class="fas fa-plus"></i> Add Lesson</button>
    </form>
  </div>

  <!-- All Lessons Table -->
  <div class="panel">
    <h3><i class="fas fa-play-circle" style="color:#f6ad55"></i> All Lessons</h3>
    <div class="search-bar"><i class="fas fa-search"></i><input type="text" id="lesson-search" placeholder="Search lessons…"></div>
    <div class="tbl-wrap">
    <table id="lessons-table">
      <thead><tr><th>ID</th><th>Course</th><th>Module</th><th>Title</th><th>Duration</th><th>Media</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($lessons_all as $les): ?>
      <tr>
        <td style="color:rgba(255,255,255,0.35)"><?= $les['id'] ?></td>
        <td style="color:rgba(255,255,255,0.5)"><?= htmlspecialchars($les['course_title']) ?></td>
        <td style="color:rgba(255,255,255,0.5)"><?= htmlspecialchars($les['module_title']) ?></td>
        <td><?= htmlspecialchars($les['title']) ?></td>
        <td><?= $les['duration'] ?>m</td>
        <td><?= $les['media_path'] ? '<span class="tag tag-active">'.ucfirst($les['media_type']).'</span>' : '<span style="color:rgba(255,255,255,0.3)">—</span>' ?></td>
        <td>
          <form method="POST" onsubmit="return confirm('Delete lesson &quot;<?= htmlspecialchars(addslashes($les['title'])) ?>&quot;?')">
            <input type="hidden" name="act" value="delete_lesson">
            <input type="hidden" name="lid" value="<?= $les['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($lessons_all)): ?>
        <tr><td colspan="7" style="text-align:center;color:rgba(255,255,255,0.35);padding:20px">No lessons yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- ════════════════ PANE: ASSIGNMENTS ════════════════ -->
<div class="pane <?= $active==='assignments'?'on':'' ?>" id="pane-assignments">
  <div class="sec-head">
    <h2><i class="fas fa-tasks"></i> Assignments
      <?php if($stats['pending_grades']>0): ?><span class="badge-pill" style="font-size:13px"><?= $stats['pending_grades'] ?> pending</span><?php endif; ?>
    </h2>
    <p>Create assignments, manage visibility, and grade student submissions.</p>
  </div>

  <!-- Create Assignment -->
  <div class="panel">
    <h3><i class="fas fa-plus-circle" style="color:#68d391"></i> Create New Assignment</h3>
    <form method="POST">
      <input type="hidden" name="act" value="create_assignment_admin">
      <div class="form-row">
        <div class="form-group"><label>Title</label><input type="text" name="at" required placeholder="Assignment title"></div>
        <div class="form-group"><label>Course (optional)</label>
          <select name="acid">
            <option value="">— No specific course —</option>
            <?php foreach($courses as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Due Date &amp; Time</label><input type="datetime-local" name="adue" required></div>
        <div class="form-group"><label>Points</label><input type="number" name="apt" value="100" min="1" max="1000"></div>
        <div class="form-group" style="grid-column:1/-1"><label>Description</label><textarea name="ad" placeholder="Describe what students need to do…"></textarea></div>
      </div>
      <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Create Assignment</button>
    </form>
  </div>

  <!-- Assignments Table -->
  <div class="panel">
    <h3><i class="fas fa-list" style="color:#63b3ed"></i> All Assignments</h3>
    <div class="tbl-wrap">
    <table>
      <thead><tr><th>ID</th><th>Title</th><th>Course</th><th>Due</th><th>Points</th><th>Subs</th><th>Ungraded</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($assignments_all as $asgn):
        $due_ts = strtotime($asgn['due_date']);
        $due_fmt = $due_ts && $due_ts > 0 ? date('M j, Y', $due_ts) : '—';
      ?>
      <tr>
        <td style="color:rgba(255,255,255,0.35)"><?= $asgn['id'] ?></td>
        <td><?= htmlspecialchars($asgn['title']) ?></td>
        <td style="color:rgba(255,255,255,0.5)"><?= htmlspecialchars($asgn['course_title'] ?? '—') ?></td>
        <td style="font-size:11px;color:rgba(255,255,255,0.5)"><?= $due_fmt ?></td>
        <td><?= $asgn['points'] ?></td>
        <td><?= $asgn['sub_count'] ?></td>
        <td><?= $asgn['ungraded']>0 ? '<span class="tag tag-inactive">'.$asgn['ungraded'].'</span>' : '0' ?></td>
        <td><span class="tag <?= $asgn['is_active']?'tag-active':'tag-inactive' ?>"><?= $asgn['is_active']?'Active':'Inactive' ?></span></td>
        <td>
          <form method="POST" style="display:inline">
            <input type="hidden" name="act" value="toggle_assign">
            <input type="hidden" name="aid" value="<?= $asgn['id'] ?>">
            <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-toggle-on"></i></button>
          </form>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete assignment &quot;<?= htmlspecialchars(addslashes($asgn['title'])) ?>&quot;?')">
            <input type="hidden" name="act" value="delete_assign">
            <input type="hidden" name="aid" value="<?= $asgn['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($assignments_all)): ?>
        <tr><td colspan="9" style="text-align:center;color:rgba(255,255,255,0.35);padding:20px">No assignments yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- Grading Section -->
  <div class="panel">
    <h3><i class="fas fa-star" style="color:#f6ad55"></i> Grade Submissions</h3>
    <?php
    $ungraded_subs = array_filter($all_subs, fn($s) => $s['grade']===null);
    $graded_subs   = array_filter($all_subs, fn($s) => $s['grade']!==null);
    ?>
    <?php if(empty($ungraded_subs)): ?>
      <p style="color:rgba(255,255,255,0.4);font-size:13px;margin-bottom:16px"><i class="fas fa-check-circle" style="color:#68d391"></i> All submissions are graded.</p>
    <?php else: ?>
    <p style="color:rgba(255,255,255,0.5);font-size:12px;margin-bottom:14px"><?= count($ungraded_subs) ?> ungraded submission(s):</p>
    <?php foreach($ungraded_subs as $sub): ?>
    <div class="grade-card">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:10px">
        <div>
          <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($sub['first_name'].' '.$sub['last_name']) ?> <span style="color:rgba(255,255,255,0.4);font-size:12px">(<?= htmlspecialchars($sub['username']) ?>)</span></div>
          <div style="color:rgba(255,255,255,0.5);font-size:12px;margin-top:2px"><?= htmlspecialchars($sub['asgn_title']) ?> &nbsp;·&nbsp; Submitted <?= $sub['submitted_at'] ? date('M j, Y g:ia', strtotime($sub['submitted_at'])) : 'N/A' ?></div>
        </div>
        <span class="tag tag-inactive">Ungraded</span>
      </div>
      <?php if($sub['submission_text']): ?>
      <div style="background:rgba(255,255,255,0.04);border-radius:7px;padding:10px;font-size:12px;color:rgba(255,255,255,0.7);margin-bottom:10px;max-height:100px;overflow-y:auto">
        <?= nl2br(htmlspecialchars(substr($sub['submission_text'],0,400))) ?><?= strlen($sub['submission_text'])>400?'…':'' ?>
      </div>
      <?php endif; ?>
      <form method="POST" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <input type="hidden" name="act" value="grade_sub">
        <input type="hidden" name="subid" value="<?= $sub['id'] ?>">
        <div class="form-group" style="min-width:100px"><label>Grade (0–100)</label><input type="number" name="grade" min="0" max="100" required placeholder="e.g. 85"></div>
        <div class="form-group" style="flex:1;min-width:200px"><label>Feedback (optional)</label><input type="text" name="fb" placeholder="Great work!…"></div>
        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Grade</button>
      </form>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if(!empty($graded_subs)): ?>
    <details style="margin-top:16px">
      <summary>Show <?= count($graded_subs) ?> graded submission(s)</summary>
      <div style="margin-top:10px">
      <?php foreach($graded_subs as $sub): ?>
      <div class="grade-card">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px">
          <div>
            <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($sub['first_name'].' '.$sub['last_name']) ?></div>
            <div style="color:rgba(255,255,255,0.5);font-size:11px"><?= htmlspecialchars($sub['asgn_title']) ?></div>
          </div>
          <div style="text-align:right">
            <span class="tag tag-active">Graded</span>
            <div class="<?= $sub['grade']>=70?'score-good':'score-bad' ?>" style="font-size:18px;font-weight:700"><?= $sub['grade'] ?>/100</div>
          </div>
        </div>
        <?php if($sub['feedback']): ?>
          <div style="font-size:12px;color:rgba(255,255,255,0.5);margin-top:6px;font-style:italic"><?= htmlspecialchars($sub['feedback']) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      </div>
    </details>
    <?php endif; ?>
  </div>
</div>

<!-- ════════════════ PANE: QUIZZES ════════════════ -->
<div class="pane <?= $active==='quizzes'?'on':'' ?>" id="pane-quizzes">
  <div class="sec-head"><h2><i class="fas fa-question-circle"></i> Quiz Management</h2><p>Create quizzes, build questions and answers, and manage existing quizzes.</p></div>

  <!-- Create Quiz -->
  <div class="panel">
    <h3><i class="fas fa-plus-circle" style="color:#68d391"></i> Create New Quiz</h3>
    <form method="POST">
      <input type="hidden" name="act" value="create_quiz">
      <div class="form-row">
        <div class="form-group"><label>Quiz Title</label><input type="text" name="qt" required placeholder="e.g. Alphabet Quiz"></div>
        <div class="form-group"><label>Linked Lesson (optional)</label>
          <select name="qlid">
            <option value="">— Standalone quiz —</option>
            <?php foreach($lessons_all as $les): ?>
              <option value="<?= $les['id'] ?>"><?= htmlspecialchars($les['course_title'].' › '.$les['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Time Limit (min)</label><input type="number" name="qtl" value="10" min="1" max="180"></div>
        <div class="form-group"><label>Passing Score (%)</label><input type="number" name="qps" value="70" min="1" max="100"></div>
        <div class="form-group" style="grid-column:1/-1"><label>Description</label><textarea name="qd" placeholder="Quiz description…" style="min-height:60px"></textarea></div>
      </div>
      <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Create Quiz</button>
    </form>
  </div>

  <!-- Quizzes Table -->
  <div class="panel">
    <h3><i class="fas fa-list" style="color:#b794f4"></i> All Quizzes</h3>
    <div class="tbl-wrap">
    <table>
      <thead><tr><th>ID</th><th>Title</th><th>Linked Lesson</th><th>Questions</th><th>Attempts</th><th>Time</th><th>Pass%</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($quizzes_all as $qz): ?>
      <tr>
        <td style="color:rgba(255,255,255,0.35)"><?= $qz['id'] ?></td>
        <td><?= htmlspecialchars($qz['title']) ?></td>
        <td style="color:rgba(255,255,255,0.5)"><?= htmlspecialchars($qz['lesson_title'] ?? '—') ?></td>
        <td><?= $qz['q_count'] ?></td>
        <td><?= $qz['a_count'] ?></td>
        <td><?= $qz['time_limit'] ?>m</td>
        <td><?= $qz['passing_score'] ?>%</td>
        <td><span class="tag <?= $qz['is_active']?'tag-active':'tag-inactive' ?>"><?= $qz['is_active']?'Active':'Inactive' ?></span></td>
        <td style="display:flex;gap:5px;align-items:center;flex-wrap:wrap">
          <?php if($qz['lesson_id']): ?>
          <a href="quiz-start.php?id=<?= $qz['id'] ?>" target="_blank" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i></a>
          <?php endif; ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="act" value="toggle_quiz">
            <input type="hidden" name="qid" value="<?= $qz['id'] ?>">
            <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-toggle-on"></i></button>
          </form>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete quiz &quot;<?= htmlspecialchars(addslashes($qz['title'])) ?>&quot; and all its attempts?')">
            <input type="hidden" name="act" value="delete_quiz">
            <input type="hidden" name="qid" value="<?= $qz['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($quizzes_all)): ?>
        <tr><td colspan="9" style="text-align:center;color:rgba(255,255,255,0.35);padding:20px">No quizzes yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- Quiz Builder -->
  <?php if(!empty($quizzes_all)): ?>
  <div class="panel">
    <h3><i class="fas fa-tools" style="color:#faf089"></i> Quiz Builder — Add Questions &amp; Answers</h3>
    <p style="color:rgba(255,255,255,0.45);font-size:12px;margin-bottom:16px">Expand a quiz below to manage its questions and answer options.</p>

    <?php foreach($quizzes_all as $qz):
      $questions = $quiz_builder_data[$qz['id']] ?? [];
    ?>
    <details class="quiz-builder-box" <?= ($active==='quizzes' && isset($_POST['qid']) && $_POST['qid']==$qz['id']) ? 'open' : '' ?>>
      <summary class="quiz-builder-summary">
        <span><i class="fas fa-question-circle" style="color:#b794f4;margin-right:8px"></i><?= htmlspecialchars($qz['title']) ?></span>
        <span style="font-size:12px;color:rgba(255,255,255,0.4)"><?= count($questions) ?> question(s)</span>
      </summary>
      <div class="quiz-builder-body">

        <!-- Existing Questions -->
        <?php if(empty($questions)): ?>
          <p style="color:rgba(255,255,255,0.35);font-size:13px;margin-bottom:14px">No questions yet. Add one below.</p>
        <?php else: ?>
        <?php foreach($questions as $qi => $q): ?>
        <div class="question-card">
          <div class="q-head">
            <div>
              <div class="q-text">Q<?= $qi+1 ?>. <?= htmlspecialchars($q['question_text']) ?></div>
              <div style="font-size:11px;color:rgba(255,255,255,0.4);margin-top:3px"><?= $q['points'] ?> pts</div>
            </div>
            <form method="POST" onsubmit="return confirm('Delete this question and all its options?')">
              <input type="hidden" name="act" value="delete_question">
              <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
            </form>
          </div>

          <!-- Options -->
          <?php if(!empty($q['options'])): ?>
          <div style="margin-bottom:10px">
            <?php foreach($q['options'] as $opt): ?>
            <div class="option-row <?= $opt['is_correct']?'is-correct':'' ?>">
              <span style="flex:1"><?= htmlspecialchars($opt['option_text']) ?></span>
              <?php if($opt['is_correct']): ?><span class="tag tag-correct">✓ Correct</span><?php endif; ?>
              <?php if(!$opt['is_correct']): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="act" value="set_correct">
                <input type="hidden" name="option_id" value="<?= $opt['id'] ?>">
                <button type="submit" class="btn btn-sm btn-success" style="font-size:10px;padding:2px 7px">Set Correct</button>
              </form>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this option?')">
                <input type="hidden" name="act" value="delete_option">
                <input type="hidden" name="option_id" value="<?= $opt['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger" style="padding:2px 6px"><i class="fas fa-times"></i></button>
              </form>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
            <p style="font-size:12px;color:rgba(255,255,255,0.35);margin-bottom:8px">No options yet.</p>
          <?php endif; ?>

          <!-- Add Option -->
          <form method="POST" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;padding-top:8px;border-top:1px solid rgba(255,255,255,0.07)">
            <input type="hidden" name="act" value="add_option">
            <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
            <div class="form-group" style="flex:1;min-width:180px"><label>Option Text</label><input type="text" name="otext" required placeholder="Answer option…"></div>
            <div class="form-group" style="width:auto">
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer;margin-top:4px">
                <input type="checkbox" name="is_correct" style="width:auto;margin:0"> Mark as Correct
              </label>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Option</button>
          </form>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Add Question -->
        <form method="POST" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;padding-top:14px;border-top:1px solid rgba(255,255,255,0.1)">
          <input type="hidden" name="act" value="add_question">
          <input type="hidden" name="qid" value="<?= $qz['id'] ?>">
          <div class="form-group" style="flex:1;min-width:220px"><label>New Question Text</label><input type="text" name="qtext" required placeholder="e.g. What letter comes after B?"></div>
          <div class="form-group" style="width:100px"><label>Points</label><input type="number" name="qpts" value="10" min="1" max="100"></div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Question</button>
        </form>
      </div>
    </details>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ════════════════ PANE: POEMS ════════════════ -->
<div class="pane <?= $active==='content'?'on':'' ?>" id="pane-content">
  <div class="sec-head"><h2><i class="fas fa-feather-alt"></i> Poem Library</h2><p>Add new poems and manage the content library.</p></div>

  <!-- Add Poem -->
  <div class="panel">
    <h3><i class="fas fa-plus-circle" style="color:#fc8181"></i> Add New Poem</h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="act" value="create_poem_admin">
      <div class="form-row">
        <div class="form-group"><label>Title</label><input type="text" name="pt" required placeholder="Poem title"></div>
        <div class="form-group"><label>Author</label><input type="text" name="pa" required placeholder="Author name"></div>
        <div class="form-group"><label>Category</label>
          <select name="pcat">
            <option value="nursery">Nursery</option>
            <option value="educational">Educational</option>
            <option value="fun">Fun</option>
            <option value="classic">Classic</option>
          </select>
        </div>
        <div class="form-group"><label>Difficulty</label>
          <select name="pdiff">
            <option value="easy">Easy</option>
            <option value="medium">Medium</option>
            <option value="hard">Hard</option>
          </select>
        </div>
        <div class="form-group"><label>Reading Time (min)</label><input type="number" name="prt" value="1" min="1" max="60"></div>
        <div class="form-group"><label>Cover Image (optional)</label><input type="file" name="poem_img" accept="image/*" style="padding:6px 8px"></div>
        <div class="form-group" style="grid-column:1/-1"><label>Poem Content</label><textarea name="pc" rows="6" required placeholder="Type the poem here…"></textarea></div>
      </div>
      <button type="submit" class="btn btn-success"><i class="fas fa-feather-alt"></i> Add Poem</button>
    </form>
  </div>

  <!-- Poems Table -->
  <div class="panel">
    <h3><i class="fas fa-list" style="color:#fc8181"></i> All Poems (<?= count($poems_all) ?>)</h3>
    <div class="tbl-wrap">
    <table>
      <thead><tr><th>ID</th><th>Title</th><th>Author</th><th>Category</th><th>Difficulty</th><th>Read Time</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($poems_all as $po): ?>
      <tr>
        <td style="color:rgba(255,255,255,0.35)"><?= $po['id'] ?></td>
        <td><?= htmlspecialchars($po['title']) ?></td>
        <td style="color:rgba(255,255,255,0.5)"><?= htmlspecialchars($po['author']) ?></td>
        <td><span class="tag tag-student"><?= ucfirst($po['category']) ?></span></td>
        <td><?= ucfirst($po['difficulty_level']) ?></td>
        <td><?= $po['reading_time'] ?>m</td>
        <td>
          <a href="poem-view.php?id=<?= $po['id'] ?>" target="_blank" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i> View</a>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete poem &quot;<?= htmlspecialchars(addslashes($po['title'])) ?>&quot;?')">
            <input type="hidden" name="act" value="delete_poem">
            <input type="hidden" name="pid" value="<?= $po['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($poems_all)): ?>
        <tr><td colspan="7" style="text-align:center;color:rgba(255,255,255,0.35);padding:20px">No poems in library.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- ════════════════ PANE: SYSTEM & TOOLS ════════════════ -->
<div class="pane <?= $active==='system'?'on':'' ?>" id="pane-system">
  <div class="sec-head"><h2><i class="fas fa-cogs"></i> System &amp; Tools</h2><p>Database stats, server info, and data management controls.</p></div>

  <!-- DB Table Counts -->
  <div class="panel">
    <h3><i class="fas fa-database" style="color:#63b3ed"></i> Database Table Counts</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px">
      <?php foreach($table_counts as $tbl=>$cnt): ?>
      <div style="background:rgba(255,255,255,0.04);border-radius:8px;padding:12px;display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:11px;color:rgba(255,255,255,0.5);font-family:monospace"><?= $tbl ?></span>
        <span style="font-size:16px;font-weight:700;color:#63b3ed"><?= $cnt ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Server Environment -->
  <div class="panel">
    <h3><i class="fas fa-server" style="color:#68d391"></i> Server Environment</h3>
    <div class="health-grid">
      <div class="health-item"><div class="hk">PHP Version</div><div class="hv"><?= PHP_VERSION ?></div></div>
      <div class="health-item"><div class="hk">Server Software</div><div class="hv"><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') ?></div></div>
      <div class="health-item"><div class="hk">DB Host</div><div class="hv"><?= DB_HOST ?></div></div>
      <div class="health-item"><div class="hk">DB Name</div><div class="hv"><?= DB_NAME ?></div></div>
      <div class="health-item"><div class="hk">Upload Max</div><div class="hv"><?= ini_get('upload_max_filesize') ?></div></div>
      <div class="health-item"><div class="hk">Post Max Size</div><div class="hv"><?= ini_get('post_max_size') ?></div></div>
      <div class="health-item"><div class="hk">Memory Limit</div><div class="hv"><?= ini_get('memory_limit') ?></div></div>
      <div class="health-item"><div class="hk">Max Exec Time</div><div class="hv"><?= ini_get('max_execution_time') ?>s</div></div>
    </div>
  </div>

  <!-- Data Controls -->
  <div class="panel">
    <h3><i class="fas fa-broom" style="color:#f6ad55"></i> Data Controls</h3>
    <div style="display:flex;gap:14px;flex-wrap:wrap">
      <form method="POST" onsubmit="return confirm('Clear ALL attendance records? This cannot be undone.')">
        <input type="hidden" name="act" value="clear_attendance">
        <button type="submit" class="btn btn-warning"><i class="fas fa-calendar-times"></i> Clear Attendance (<?= $stats['attendance'] ?> records)</button>
      </form>
      <form method="POST" onsubmit="return confirm('Delete ALL teacher messages?')">
        <input type="hidden" name="act" value="clear_messages">
        <button type="submit" class="btn btn-warning"><i class="fas fa-envelope-open"></i> Clear Messages (<?= $stats['messages'] ?> records)</button>
      </form>
    </div>
  </div>

  <!-- Danger Zone -->
  <div class="panel" style="border-color:rgba(229,62,62,0.4)">
    <h3><i class="fas fa-exclamation-triangle" style="color:#e53e3e"></i> Danger Zone</h3>
    <p style="font-size:13px;color:rgba(255,255,255,0.55);margin-bottom:14px">
      Full reset clears all submissions, quiz attempts, student progress, attendance, and messages.
      User accounts, courses, and content are kept. This resets the platform to a fresh demo state.
    </p>
    <form method="POST" onsubmit="return confirm('⚠️ FULL RESET: This will delete ALL student submissions, quiz attempts, progress records, attendance, and messages. Are you absolutely sure?')">
      <input type="hidden" name="act" value="full_reset">
      <button type="submit" class="btn btn-danger"><i class="fas fa-skull-crossbones"></i> Reset All Demo Data</button>
    </form>
  </div>
</div>

</div><!-- /#main -->

<script>
// ── Tab switcher
function sw(name, el) {
  document.querySelectorAll('.pane').forEach(p => p.classList.remove('on'));
  document.querySelectorAll('button.lk').forEach(b => b.classList.remove('on'));
  const pane = document.getElementById('pane-' + name);
  if (pane) pane.classList.add('on');
  el.classList.add('on');
  const url = new URL(window.location);
  url.searchParams.set('tab', name);
  history.replaceState(null, '', url);
  // Close sidebar on mobile after click
  if (window.innerWidth <= 768) closeSidebar();
}

// ── Auto-dismiss alert
(function() {
  var alertEl = document.getElementById('main-alert');
  if (alertEl) {
    setTimeout(function() {
      alertEl.style.transition = 'opacity .6s ease';
      alertEl.style.opacity = '0';
      setTimeout(function() { alertEl.style.display = 'none'; }, 700);
    }, 5000);
  }
})();

// ── Auto-refresh activity feed every 60 seconds when active
setInterval(function() {
  var pane = document.getElementById('pane-activity');
  if (pane && pane.classList.contains('on')) window.location.reload();
}, 60000);

// ── Mobile sidebar
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('overlay');
const hamburger = document.getElementById('hamburger');
function closeSidebar() {
  sidebar.classList.remove('open');
  overlay.classList.remove('on');
}
hamburger.addEventListener('click', function() {
  sidebar.classList.toggle('open');
  overlay.classList.toggle('on');
});
overlay.addEventListener('click', closeSidebar);

// ── Confirm password validation
const createUserForm = document.getElementById('create-user-form');
if (createUserForm) {
  createUserForm.addEventListener('submit', function(e) {
    const pw  = document.getElementById('new-pw').value;
    const cpw = document.getElementById('confirm-pw').value;
    const msg = document.getElementById('pw-mismatch');
    if (pw !== cpw) {
      e.preventDefault();
      msg.style.display = 'block';
      document.getElementById('confirm-pw').focus();
    } else {
      msg.style.display = 'none';
    }
  });
}

// ── Table search filter (generic)
function filterTable(inputId, tableId) {
  const inp = document.getElementById(inputId);
  if (!inp) return;
  inp.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
    rows.forEach(function(row) {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}
filterTable('user-search',   'users-table');
filterTable('lesson-search', 'lessons-table');
</script>
</body>
</html>
