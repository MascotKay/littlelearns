<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'littlelearners');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

if (session_status() === PHP_SESSION_NONE) session_start();

function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        return null;
    }
}

function isLoggedIn()  { return isset($_SESSION['user_id']); }
function requireLogin() { if (!isLoggedIn()) { header('Location: login.php'); exit(); } }

// ── Admin credentials (single hardcoded super-admin) ────────────────────────
define('ADMIN_USERNAME', 'MascotNuel');
define('ADMIN_PASSWORD', 'paddy_1');

function isAdmin() {
    return isset($_SESSION['user_id'])
        && isset($_SESSION['is_admin'])
        && $_SESSION['is_admin'] === true
        && isset($_SESSION['username'])
        && $_SESSION['username'] === ADMIN_USERNAME;
}

function requireAdmin() {
    if (!isAdmin()) { header('Location: login.php'); exit(); }
}

/**
 * Called on every admin login attempt.
 * Always does an UPSERT so a stale/mismatched hash is replaced with a
 * fresh server-generated one — guarantees login works on any PHP installation.
 */
function provisionAdminUser($pdo) {
    // Extend ENUM — safe to run repeatedly, silently ignored if already done
    try {
        $pdo->exec("ALTER TABLE users
                    MODIFY COLUMN user_type
                    ENUM('student','teacher','parent','admin')
                    NOT NULL DEFAULT 'student'");
    } catch (PDOException $e) {}

    // Fresh hash generated on THIS server — always matches password_verify()
    $hash = password_hash(ADMIN_PASSWORD, PASSWORD_BCRYPT, ['cost' => 10]);

    // UPSERT: insert if missing, update hash+type if row exists
    try {
        $pdo->prepare(
            "INSERT INTO users (username, email, password, user_type, first_name, last_name)
             VALUES (?, 'mascot@sproutlearn.admin', ?, 'admin', 'Mascot', 'Nuel')
             ON DUPLICATE KEY UPDATE
                 password   = VALUES(password),
                 user_type  = 'admin',
                 first_name = 'Mascot',
                 last_name  = 'Nuel'"
        )->execute([ADMIN_USERNAME, $hash]);
    } catch (PDOException $e) {
        // ENUM alter not yet reflected — fall back to 'teacher' as stored type
        // (isAdmin() uses session flag, not DB column, so login still works)
        $pdo->prepare(
            "INSERT INTO users (username, email, password, user_type, first_name, last_name)
             VALUES (?, 'mascot@sproutlearn.admin', ?, 'teacher', 'Mascot', 'Nuel')
             ON DUPLICATE KEY UPDATE
                 password   = VALUES(password),
                 first_name = 'Mascot',
                 last_name  = 'Nuel'"
        )->execute([ADMIN_USERNAME, $hash]);
    }
}

// File upload handler
function uploadFile($file, $subdir = 'general') {
    $targetDir = UPLOAD_DIR . $subdir . '/';
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm', 'video/ogg'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > $maxSize) return null;
    if (!in_array($file['type'], $allowedTypes)) return null;
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $targetPath = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/' . $subdir . '/' . $filename;
    }
    return null;
}

// ========== USER ==========
function getUser($id) {
    $pdo = getDBConnection(); if(!$pdo) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ========== COURSES & LESSONS ==========
function getCourses() {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $stmt = $pdo->query("SELECT * FROM courses WHERE is_active=1 ORDER BY order_index");
    return $stmt->fetchAll();
}

function getCourse($id) {
    $pdo = getDBConnection(); if(!$pdo) return null;
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id=?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getCourseModules($course_id) {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE course_id=? ORDER BY module_order");
    $stmt->execute([$course_id]);
    return $stmt->fetchAll();
}

function getModuleLessons($module_id) {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE module_id=? ORDER BY lesson_order");
    $stmt->execute([$module_id]);
    return $stmt->fetchAll();
}

function getLesson($lesson_id) {
    $pdo = getDBConnection(); if(!$pdo) return null;
    $stmt = $pdo->prepare("SELECT l.*, m.title AS module_title, c.title AS course_title, c.id as course_id FROM lessons l JOIN modules m ON l.module_id=m.id JOIN courses c ON m.course_id=c.id WHERE l.id=?");
    $stmt->execute([$lesson_id]);
    return $stmt->fetch();
}

function updateLessonMedia($lesson_id, $file) {
    $pdo = getDBConnection(); if(!$pdo) return false;
    $path = uploadFile($file, 'lessons');
    if (!$path) return false;
    $mediaType = strpos($file['type'], 'image/') === 0 ? 'image' : 'video';
    $stmt = $pdo->prepare("UPDATE lessons SET media_path=?, media_type=? WHERE id=?");
    return $stmt->execute([$path, $mediaType, $lesson_id]);
}

function markLessonComplete($student_id, $lesson_id) {
    $pdo = getDBConnection(); if(!$pdo) return false;
    $stmt = $pdo->prepare("INSERT INTO student_progress (student_id, lesson_id, completed, completed_at) VALUES (?,?,1,NOW()) ON DUPLICATE KEY UPDATE completed=1, completed_at=NOW()");
    return $stmt->execute([$student_id, $lesson_id]);
}

function isLessonComplete($student_id, $lesson_id) {
    $pdo = getDBConnection(); if(!$pdo) return false;
    $stmt = $pdo->prepare("SELECT completed FROM student_progress WHERE student_id=? AND lesson_id=?");
    $stmt->execute([$student_id, $lesson_id]);
    $row = $stmt->fetch();
    return $row && $row['completed'];
}

// ========== ASSIGNMENTS ==========
function getAssignments($student_id, $course_id=null) {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $sql = "SELECT a.*, c.title as course_title FROM assignments a LEFT JOIN courses c ON a.course_id=c.id WHERE a.is_active=1";
    $params = [];
    if($course_id) { $sql .= " AND a.course_id=?"; $params[]=$course_id; }
    $sql .= " ORDER BY a.due_date ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $assignments = $stmt->fetchAll();
    foreach($assignments as &$a) {
        $stmt2 = $pdo->prepare("SELECT id, grade, submission_text, file_path FROM assignment_submissions WHERE assignment_id=? AND student_id=?");
        $stmt2->execute([$a['id'], $student_id]);
        $sub = $stmt2->fetch();
        $a['submitted'] = !!$sub;
        $a['grade'] = $sub ? $sub['grade'] : null;
        $a['submission_text'] = $sub ? $sub['submission_text'] : null;
        $a['submission_file'] = $sub ? $sub['file_path'] : null;
    }
    return $assignments;
}

function getAssignment($id) {
    $pdo = getDBConnection(); if(!$pdo) return null;
    $stmt = $pdo->prepare("SELECT a.*, c.title as course_title FROM assignments a LEFT JOIN courses c ON a.course_id=c.id WHERE a.id=?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createAssignment($title, $description, $due_date, $file = null) {
    $pdo = getDBConnection(); if(!$pdo) return false;
    $attachmentPath = null;
    $attachmentType = null;
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $attachmentPath = uploadFile($file, 'assignments');
        if ($attachmentPath) {
            $attachmentType = strpos($file['type'], 'image/') === 0 ? 'image' : 'video';
        }
    }
    $stmt = $pdo->prepare("INSERT INTO assignments (title, description, due_date, attachment_path, attachment_type) VALUES (?,?,?,?,?)");
    return $stmt->execute([$title, $description, $due_date, $attachmentPath, $attachmentType]);
}

function submitAssignment($assignment_id, $student_id, $text, $file = null) {
    $pdo = getDBConnection(); if(!$pdo) return false;
    $filePath = null;
    $fileType = null;
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $filePath = uploadFile($file, 'submissions');
        if ($filePath) {
            $fileType = strpos($file['type'], 'image/') === 0 ? 'image' : 'video';
        }
    }
    $stmt = $pdo->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, file_path, file_type, submitted_at) VALUES (?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE submission_text=?, file_path=?, file_type=?, submitted_at=NOW(), grade=NULL, feedback=NULL, graded_at=NULL");
    return $stmt->execute([$assignment_id, $student_id, $text, $filePath, $fileType, $text, $filePath, $fileType]);
}

function gradeAssignment($submission_id, $grade, $feedback) {
    $pdo = getDBConnection(); if(!$pdo) return false;
    $stmt = $pdo->prepare("UPDATE assignment_submissions SET grade=?, feedback=?, graded_at=NOW() WHERE id=?");
    return $stmt->execute([$grade, $feedback, $submission_id]);
}

function getSubmissionsForAssignment($assignment_id) {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $stmt = $pdo->prepare("SELECT s.*, u.first_name, u.last_name, u.username FROM assignment_submissions s JOIN users u ON s.student_id=u.id WHERE s.assignment_id=? ORDER BY s.submitted_at DESC");
    $stmt->execute([$assignment_id]);
    return $stmt->fetchAll();
}

// ========== ATTENDANCE ==========
function markAttendance($student_id, $course_id, $status='present') {
    $pdo = getDBConnection(); if(!$pdo) return false;
    $stmt = $pdo->prepare("INSERT INTO attendance (student_id, course_id, date, status) VALUES (?,?,CURDATE(),?) ON DUPLICATE KEY UPDATE status=?");
    return $stmt->execute([$student_id, $course_id, $status, $status]);
}

function getAttendance($student_id, $course_id=null) {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $sql = "SELECT a.*, c.title as course_title FROM attendance a JOIN courses c ON a.course_id=c.id WHERE a.student_id=?";
    $params = [$student_id];
    if($course_id) { $sql .= " AND a.course_id=?"; $params[]=$course_id; }
    $sql .= " ORDER BY a.date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ========== POEMS ==========
function getPoems($category=null, $difficulty=null) {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $sql = "SELECT * FROM poems WHERE 1=1";
    $params = [];
    if($category) { $sql .= " AND category=?"; $params[]=$category; }
    if($difficulty) { $sql .= " AND difficulty_level=?"; $params[]=$difficulty; }
    $sql .= " ORDER BY title";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getPoem($id) {
    $pdo = getDBConnection(); if(!$pdo) return null;
    $stmt = $pdo->prepare("SELECT * FROM poems WHERE id=?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createPoem($title, $author, $category, $difficulty, $reading_time, $content, $file = null) {
    $pdo = getDBConnection(); if(!$pdo) return false;
    // ensure image column exists
    try { $pdo->exec("ALTER TABLE poems ADD COLUMN image_path VARCHAR(255) NULL"); } catch(PDOException $e) {}
    $imagePath = null;
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $imagePath = uploadFile($file, 'poems');
    }
    $stmt = $pdo->prepare("INSERT INTO poems (title, author, category, difficulty_level, reading_time, content, image_path) VALUES (?,?,?,?,?,?,?)");
    return $stmt->execute([$title, $author, $category, $difficulty, $reading_time, $content, $imagePath]);
}

// ========== QUIZZES ==========
function getQuiz($lesson_id) {
    $pdo = getDBConnection(); if(!$pdo) return null;
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE lesson_id=? AND is_active=1 LIMIT 1");
    $stmt->execute([$lesson_id]);
    return $stmt->fetch();
}

function getQuizById($id) {
    $pdo = getDBConnection(); if(!$pdo) return null;
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id=?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllQuizzes($course_id=null) {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $sql = "SELECT q.*, c.title as course_title, l.title as lesson_title,
            (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id=q.id) as question_count
            FROM quizzes q
            LEFT JOIN lessons l ON q.lesson_id=l.id
            LEFT JOIN modules m ON l.module_id=m.id
            LEFT JOIN courses c ON m.course_id=c.id
            WHERE q.is_active=1";
    $params = [];
    if($course_id) { $sql .= " AND c.id=?"; $params[]=$course_id; }
    $sql .= " ORDER BY q.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getQuizQuestions($quiz_id) {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id=? ORDER BY question_order");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll();
    foreach($questions as &$q) {
        $stmt2 = $pdo->prepare("SELECT * FROM quiz_options WHERE question_id=? ORDER BY option_order");
        $stmt2->execute([$q['id']]);
        $q['options'] = $stmt2->fetchAll();
    }
    return $questions;
}

function updateQuizQuestionImage($question_id, $file) {
    $pdo = getDBConnection(); if(!$pdo) return false;
    try { $pdo->exec("ALTER TABLE quiz_questions ADD COLUMN image_path VARCHAR(255) NULL"); } catch(PDOException $e) {}
    $path = uploadFile($file, 'quizzes');
    if (!$path) return false;
    $stmt = $pdo->prepare("UPDATE quiz_questions SET image_path=? WHERE id=?");
    return $stmt->execute([$path, $question_id]);
}

function saveQuizAttempt($quiz_id, $student_id, $score, $total_questions, $correct) {
    $pdo = getDBConnection(); if(!$pdo) return false;
    $stmt = $pdo->prepare("INSERT INTO quiz_attempts (quiz_id, student_id, score, total_questions, correct_answers, attempted_at) VALUES (?,?,?,?,?,NOW())");
    if($stmt->execute([$quiz_id, $student_id, $score, $total_questions, $correct])) {
        return $pdo->lastInsertId();
    }
    return false;
}

function getQuizAttempts($student_id, $quiz_id=null) {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $sql = "SELECT qa.*, q.title as quiz_title FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id=q.id WHERE qa.student_id=?";
    $params = [$student_id];
    if($quiz_id) { $sql .= " AND qa.quiz_id=?"; $params[]=$quiz_id; }
    $sql .= " ORDER BY qa.attempted_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ========== PROGRESS ==========
function getStudentProgress($student_id, $course_id=null) {
    $pdo = getDBConnection(); if(!$pdo) return 0;
    if($course_id) {
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT l.id) as total FROM lessons l JOIN modules m ON l.module_id=m.id WHERE m.course_id=?");
        $stmt->execute([$course_id]);
        $total = $stmt->fetch()['total'];
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT sp.lesson_id) as completed FROM student_progress sp JOIN lessons l ON sp.lesson_id=l.id JOIN modules m ON l.module_id=m.id WHERE sp.student_id=? AND sp.completed=1 AND m.course_id=?");
        $stmt->execute([$student_id, $course_id]);
        $completed = $stmt->fetch()['completed'];
    } else {
        $stmt = $pdo->query("SELECT COUNT(id) as total FROM lessons");
        $total = $stmt->fetch()['total'];
        $stmt = $pdo->prepare("SELECT COUNT(lesson_id) as completed FROM student_progress WHERE student_id=? AND completed=1");
        $stmt->execute([$student_id]);
        $completed = $stmt->fetch()['completed'];
    }
    return $total>0 ? round(($completed/$total)*100) : 0;
}

function getStudentScores($student_id) {
    $pdo = getDBConnection(); if(!$pdo) return ['assignments'=>[], 'quizzes'=>[]];
    $stmt = $pdo->prepare("SELECT a.title, asub.grade, a.points, c.title as course_title FROM assignment_submissions asub JOIN assignments a ON asub.assignment_id=a.id LEFT JOIN courses c ON a.course_id=c.id WHERE asub.student_id=? AND asub.grade IS NOT NULL ORDER BY asub.submitted_at DESC");
    $stmt->execute([$student_id]);
    $assignments = $stmt->fetchAll();
    $stmt2 = $pdo->prepare("SELECT q.title, qa.score, q.passing_score, c.title as course_title FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id=q.id LEFT JOIN lessons l ON q.lesson_id=l.id LEFT JOIN modules m ON l.module_id=m.id LEFT JOIN courses c ON m.course_id=c.id WHERE qa.student_id=? ORDER BY qa.attempted_at DESC");
    $stmt2->execute([$student_id]);
    $quizzes = $stmt2->fetchAll();
    return ['assignments'=>$assignments, 'quizzes'=>$quizzes];
}

// ========== PARENT-CHILD ==========
function getChildren($parent_id) {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $stmt = $pdo->prepare("SELECT u.* FROM parent_child pc JOIN users u ON pc.student_id=u.id WHERE pc.parent_id=?");
    $stmt->execute([$parent_id]);
    return $stmt->fetchAll();
}

function isParentOf($parent_id, $student_id) {
    $pdo = getDBConnection(); if(!$pdo) return false;
    $stmt = $pdo->prepare("SELECT id FROM parent_child WHERE parent_id=? AND student_id=?");
    $stmt->execute([$parent_id, $student_id]);
    return $stmt->fetch() !== false;
}

// ========== TEACHER QUERIES ==========
function sendTeacherQuery($teacher_id, $student_id, $message) {
    $pdo = getDBConnection(); if(!$pdo) return false;
    $stmt = $pdo->prepare("INSERT INTO teacher_queries (teacher_id, student_id, message) VALUES (?,?,?)");
    return $stmt->execute([$teacher_id, $student_id, $message]);
}

function getQueriesForStudent($student_id) {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $stmt = $pdo->prepare("SELECT tq.*, u.first_name, u.last_name FROM teacher_queries tq JOIN users u ON tq.teacher_id=u.id WHERE tq.student_id=? ORDER BY tq.created_at DESC");
    $stmt->execute([$student_id]);
    return $stmt->fetchAll();
}

function getAllQueriesForTeacher($teacher_id) {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $stmt = $pdo->prepare("SELECT tq.*, u.first_name, u.last_name as student_name FROM teacher_queries tq JOIN users u ON tq.student_id=u.id WHERE tq.teacher_id=? ORDER BY tq.created_at DESC");
    $stmt->execute([$teacher_id]);
    return $stmt->fetchAll();
}

function getAllLessons() {
    $pdo = getDBConnection(); if(!$pdo) return [];
    $stmt = $pdo->prepare("SELECT l.*, m.title as module_title, c.title as course_title FROM lessons l JOIN modules m ON l.module_id=m.id JOIN courses c ON m.course_id=c.id ORDER BY c.title, m.module_order, l.lesson_order");
    $stmt->execute();
    return $stmt->fetchAll();
}

function resetDemoData() {
    $pdo = getDBConnection(); if(!$pdo) return false;
    $pdo->exec("DELETE FROM assignment_submissions");
    $pdo->exec("DELETE FROM quiz_attempts");
    $pdo->exec("DELETE FROM student_progress");
    $pdo->exec("DELETE FROM attendance");
    $pdo->exec("DELETE FROM teacher_queries");
    return true;
}
?>