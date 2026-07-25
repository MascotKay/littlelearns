-- ============================================================
-- SproutLearn (Little Learners) — Complete Database
-- Import this single file to set up the entire application.
--
-- Usage (phpMyAdmin or MySQL CLI):
--   mysql -u root -p < database.sql
--   OR paste into phpMyAdmin > SQL tab
--
-- Admin login:  username = MascotNuel  |  password = paddy_1  (see config.php ADMIN_PASSWORD)
-- Demo logins:  student / teacher / parent  (password = password123)
-- ============================================================

CREATE DATABASE IF NOT EXISTS littlelearners
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE littlelearners;

-- ============================================================
-- TABLES
-- ============================================================

-- ── Users ──────────────────────────────────────────────────
-- user_type includes 'admin' from the start — no ALTER needed
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  UNIQUE NOT NULL,
    email      VARCHAR(100) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    user_type  ENUM('student','teacher','parent','admin') NOT NULL DEFAULT 'student',
    first_name VARCHAR(50)  NOT NULL,
    last_name  VARCHAR(50)  NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── Courses / Modules / Lessons ────────────────────────────
CREATE TABLE IF NOT EXISTS courses (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(150) NOT NULL,
    description TEXT,
    category    VARCHAR(80)  DEFAULT NULL,
    order_index INT          DEFAULT 0,
    is_active   TINYINT(1)   DEFAULT 1,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS modules (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    course_id    INT          NOT NULL,
    title        VARCHAR(150) NOT NULL,
    description  TEXT,
    module_order INT          DEFAULT 0,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lessons (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    module_id    INT          NOT NULL,
    title        VARCHAR(150) NOT NULL,
    description  TEXT,
    content      TEXT,
    duration     INT          DEFAULT 10,
    media_path   VARCHAR(255) NULL,
    media_type   ENUM('image','video') NULL,
    lesson_order INT          DEFAULT 0,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
);

-- ── Assignments ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS assignments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    course_id       INT          NULL,
    title           VARCHAR(150) NOT NULL,
    description     TEXT,
    due_date        DATETIME     NOT NULL,
    points          INT          DEFAULT 100,
    attachment_path VARCHAR(255) NULL,
    attachment_type ENUM('image','video') NULL,
    is_active       TINYINT(1)   DEFAULT 1,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS assignment_submissions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id   INT          NOT NULL,
    student_id      INT          NOT NULL,
    submission_text TEXT,
    file_path       VARCHAR(255) NULL,
    file_type       ENUM('image','video') NULL,
    grade           DECIMAL(5,1) NULL,
    feedback        TEXT,
    submitted_at    TIMESTAMP    NULL,
    graded_at       TIMESTAMP    NULL,
    UNIQUE KEY uniq_assignment_student (assignment_id, student_id),
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id)    REFERENCES users(id)       ON DELETE CASCADE
);

-- ── Attendance ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS attendance (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT  NOT NULL,
    course_id  INT  NOT NULL,
    date       DATE NOT NULL,
    status     ENUM('present','absent','late') DEFAULT 'present',
    UNIQUE KEY uniq_student_course_date (student_id, course_id, date),
    FOREIGN KEY (student_id) REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE
);

-- ── Student Progress ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS student_progress (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    student_id   INT       NOT NULL,
    lesson_id    INT       NOT NULL,
    completed    TINYINT(1) DEFAULT 0,
    completed_at TIMESTAMP  NULL,
    UNIQUE KEY uniq_student_lesson (student_id, lesson_id),
    FOREIGN KEY (student_id) REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (lesson_id)  REFERENCES lessons(id) ON DELETE CASCADE
);

-- ── Poems ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS poems (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(150) NOT NULL,
    author           VARCHAR(100) NOT NULL,
    category         ENUM('nursery','educational','fun','classic') NOT NULL,
    difficulty_level ENUM('easy','medium','hard') NOT NULL,
    reading_time     INT  DEFAULT 1,
    content          TEXT NOT NULL,
    image_path       VARCHAR(255) NULL
);

-- ── Quizzes ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS quizzes (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id     INT          NULL,
    title         VARCHAR(150) NOT NULL,
    description   TEXT,
    time_limit    INT          DEFAULT 10,
    passing_score INT          DEFAULT 70,
    is_active     TINYINT(1)   DEFAULT 1,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS quiz_questions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id        INT  NOT NULL,
    question_text  TEXT NOT NULL,
    points         INT  DEFAULT 10,
    question_order INT  DEFAULT 0,
    image_path     VARCHAR(255) NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS quiz_options (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    question_id  INT          NOT NULL,
    option_text  VARCHAR(255) NOT NULL,
    is_correct   TINYINT(1)   DEFAULT 0,
    option_order INT          DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS quiz_attempts (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id          INT          NOT NULL,
    student_id       INT          NOT NULL,
    score            DECIMAL(5,1) NOT NULL,
    total_questions  INT          NOT NULL,
    correct_answers  INT          NOT NULL,
    attempted_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id)    REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id)   ON DELETE CASCADE
);

-- ── Parent–Child ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS parent_child (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    parent_id  INT NOT NULL,
    student_id INT NOT NULL,
    UNIQUE KEY uniq_parent_student (parent_id, student_id),
    FOREIGN KEY (parent_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Teacher Queries / Messages ─────────────────────────────
CREATE TABLE IF NOT EXISTS teacher_queries (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT  NOT NULL,
    student_id INT  NOT NULL,
    message    TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- ── Users ──────────────────────────────────────────────────
-- All demo passwords = "password123"
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.YeYudDdvB.ZL3qPLNH.x3ULsR3Mp.Bxxe
--
-- Admin password = "paddy_1"  (configured in config.php ADMIN_PASSWORD)
-- Hash generated at bcrypt cost 12 via PHP password_hash()
-- Note: provisionAdminUser() regenerates the hash on every admin login,
-- so even if this hash is stale the login will always work correctly.

INSERT INTO users (username, email, password, user_type, first_name, last_name) VALUES
-- Demo accounts
('teacher',  'teacher@sproutlearn.test',  '$2y$10$92IXUNpkjO0rOQ5byMi.YeYudDdvB.ZL3qPLNH.x3ULsR3Mp.Bxxe', 'teacher', 'Jamie',  'Carter'),
('student',  'student@sproutlearn.test',  '$2y$10$92IXUNpkjO0rOQ5byMi.YeYudDdvB.ZL3qPLNH.x3ULsR3Mp.Bxxe', 'student', 'Alex',   'Rivera'),
('parent',   'parent@sproutlearn.test',   '$2y$10$92IXUNpkjO0rOQ5byMi.YeYudDdvB.ZL3qPLNH.x3ULsR3Mp.Bxxe', 'parent',  'Morgan', 'Rivera'),
('student2', 'student2@sproutlearn.test', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYudDdvB.ZL3qPLNH.x3ULsR3Mp.Bxxe', 'student', 'Sam',    'Lee'),
-- Admin account  (username: MascotNuel | password: Mascot_1)
('MascotNuel', 'mascot@sproutlearn.admin', '$2y$12$6T1xVGm3ORYh7aK0BmPQiuL4a3nQwW9RKC2Xf8pMtJ5hD1kZuV3oW', 'admin', 'Mascot', 'Nuel')
ON DUPLICATE KEY UPDATE id = id; -- no-op if rows already exist

-- Parent → Child link (Morgan is Alex's parent)
INSERT INTO parent_child (parent_id, student_id) VALUES (3, 2)
ON DUPLICATE KEY UPDATE id = id;

-- ── Courses ────────────────────────────────────────────────
INSERT INTO courses (title, description, category, order_index, is_active) VALUES
('Reading Adventures',  'Build foundational reading skills through fun stories and activities.', 'Literacy',    1, 1),
('Number Explorers',    'Introduction to numbers, counting, and basic math operations.',         'Mathematics', 2, 1),
('Science Discoveries', 'Hands-on exploration of the world around us.',                         'Science',     3, 1)
ON DUPLICATE KEY UPDATE id = id;

-- ── Modules ────────────────────────────────────────────────
INSERT INTO modules (course_id, title, description, module_order) VALUES
(1, 'Letters and Sounds', 'Master the alphabet and letter sounds.',     1),
(1, 'Sight Words',        'Learn the most common words by sight.',      2),
(2, 'Counting 1-20',      'Count objects confidently up to 20.',        1),
(2, 'Addition Basics',    'Understand and practise simple addition.',    2),
(3, 'Plants and Animals', 'Discover the living world around you.',      1)
ON DUPLICATE KEY UPDATE id = id;

-- ── Lessons ────────────────────────────────────────────────
INSERT INTO lessons (module_id, title, description, content, duration, lesson_order) VALUES
(1, 'The Alphabet Song',    'Learn the alphabet through song and rhythm.',
 '<p>Today we will sing the <strong>alphabet song</strong> together and practise recognising each letter.</p>
<p>Say each letter out loud as you follow along — <em>A, B, C, D, E, F, G…</em></p>
<ul><li>A is for Apple</li><li>B is for Ball</li><li>C is for Cat</li></ul>',
 15, 1),

(1, 'Letter Sounds A-E',   'Practise the sounds each letter makes.',
 '<p>Let''s practise the <strong>sounds</strong> for the first five letters with fun examples.</p>
<ul>
  <li><strong>A</strong> — /æ/ as in <em>apple</em></li>
  <li><strong>B</strong> — /b/ as in <em>ball</em></li>
  <li><strong>C</strong> — /k/ as in <em>cat</em></li>
  <li><strong>D</strong> — /d/ as in <em>dog</em></li>
  <li><strong>E</strong> — /ɛ/ as in <em>egg</em></li>
</ul>',
 15, 2),

(2, 'Common Sight Words',  'Learn the most frequently used words.',
 '<p><strong>Sight words</strong> are words you should recognise instantly without sounding them out.</p>
<p>Common examples: <em>the, and, is, it, in, on, at, to, a, I</em></p>
<p>Practise by reading them on flashcards every day!</p>',
 20, 1),

(3, 'Counting to 10',      'Learn to count objects up to 10.',
 '<p>Count the objects around you — apples, stars, fingers!</p>
<p><strong>1, 2, 3, 4, 5, 6, 7, 8, 9, 10</strong></p>
<p>Try counting backwards from 10 to 1 as a fun challenge.</p>',
 15, 1),

(3, 'Counting 11-20',      'Continue counting practice.',
 '<p>Great work counting to 10! Now let''s go all the way to <strong>20</strong>.</p>
<p><strong>11, 12, 13, 14, 15, 16, 17, 18, 19, 20</strong></p>
<p>Notice a pattern? Each number just adds one more!</p>',
 15, 2),

(4, 'Adding Small Numbers', 'Introduction to simple addition.',
 '<p>Addition means putting numbers <strong>together</strong>.</p>
<p>1 + 1 = <strong>2</strong> &nbsp;|&nbsp; 2 + 3 = <strong>5</strong> &nbsp;|&nbsp; 4 + 4 = <strong>8</strong></p>
<p>Use your fingers to help — count the first number, then keep counting for the second!</p>',
 20, 1),

(5, 'Animal Habitats',     'Where do different animals live?',
 '<p>Every animal has a <strong>habitat</strong> — a place that gives it food, water, and shelter.</p>
<ul>
  <li>🌲 <strong>Forest</strong> — foxes, deer, owls</li>
  <li>🌊 <strong>Ocean</strong> — fish, dolphins, sharks</li>
  <li>🏜️ <strong>Desert</strong> — camels, lizards, scorpions</li>
</ul>
<p>Can you think of another animal and where it lives?</p>',
 20, 1)
ON DUPLICATE KEY UPDATE id = id;

-- ── Assignments ────────────────────────────────────────────
INSERT INTO assignments (course_id, title, description, due_date, points, is_active) VALUES
(1, 'Draw Your Favourite Letter',
 'Draw a picture of something that starts with your favourite letter and upload a photo.',
 DATE_ADD(NOW(), INTERVAL 7 DAY),  100, 1),

(2, 'Counting Practice Sheet',
 'Count the objects around your house and write down the numbers you find.',
 DATE_ADD(NOW(), INTERVAL 5 DAY),  100, 1),

(3, 'Animal Habitat Poster',
 'Create a poster showing an animal and its habitat. Upload a photo of your finished work.',
 DATE_ADD(NOW(), INTERVAL 10 DAY), 100, 1)
ON DUPLICATE KEY UPDATE id = id;

-- ── Poems ──────────────────────────────────────────────────
INSERT INTO poems (title, author, category, difficulty_level, reading_time, content) VALUES
('Twinkle Twinkle Little Star', 'Traditional', 'nursery', 'easy', 1,
 'Twinkle, twinkle, little star,
How I wonder what you are!
Up above the world so high,
Like a diamond in the sky.'),

('The Months of the Year', 'Anonymous', 'educational', 'medium', 2,
 'January, February, March, and April,
May and June, then comes July,
August, September, October too,
November, December, the year is through!'),

('Jump and Hop', 'Anonymous', 'fun', 'easy', 1,
 'Jump, jump, jump so high,
Hop, hop, hop to the sky,
Run around and touch the ground,
Then sit quietly without a sound.'),

('The Owl and the Pussycat (excerpt)', 'Edward Lear', 'classic', 'hard', 3,
 'The Owl and the Pussycat went to sea
In a beautiful pea-green boat,
They took some honey, and plenty of money,
Wrapped up in a five-pound note.')
ON DUPLICATE KEY UPDATE id = id;

-- ── Quizzes ────────────────────────────────────────────────
INSERT INTO quizzes (lesson_id, title, description, time_limit, passing_score, is_active) VALUES
(1, 'Alphabet Quiz',  'Test your knowledge of the alphabet!', 10, 70, 1),
(4, 'Counting Quiz',  'How well can you count?',              10, 70, 1)
ON DUPLICATE KEY UPDATE id = id;

-- ── Quiz Questions ─────────────────────────────────────────
INSERT INTO quiz_questions (quiz_id, question_text, points, question_order) VALUES
(1, 'What letter comes after B?',          10, 1),
(1, 'Which letter is a vowel?',            10, 2),
(2, 'How many apples are in a group of 5?', 10, 1),
(2, 'What number comes after 7?',          10, 2)
ON DUPLICATE KEY UPDATE id = id;

-- ── Quiz Options ───────────────────────────────────────────
INSERT INTO quiz_options (question_id, option_text, is_correct, option_order) VALUES
-- Q1: What letter comes after B?
(1, 'A', 0, 1),
(1, 'C', 1, 2),
(1, 'D', 0, 3),
-- Q2: Which letter is a vowel?
(2, 'B', 0, 1),
(2, 'A', 1, 2),
(2, 'C', 0, 3),
-- Q3: How many apples in a group of 5?
(3, '4', 0, 1),
(3, '5', 1, 2),
(3, '6', 0, 3),
-- Q4: What number comes after 7?
(4, '6', 0, 1),
(4, '8', 1, 2),
(4, '9', 0, 3)
ON DUPLICATE KEY UPDATE id = id;

-- ============================================================
-- END OF FILE
-- Drop admin-setup.sql — it is no longer needed.
-- Everything is here in this single file.
-- ============================================================
