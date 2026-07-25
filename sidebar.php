<div class="logo">
    <h2><i class="fas fa-seedling"></i> <span>SproutLearn</span></h2>
</div>
<div class="nav-menu">
<?php
// isAdmin() is defined in config.php which is always included before sidebar
$_is_admin = function_exists('isAdmin') && isAdmin();
if (!$_is_admin):
?>
    <a href="home.php" class="nav-item <?= basename($_SERVER['PHP_SELF'])=='home.php'?'active':'' ?>"><i class="fas fa-home"></i><span>Dashboard</span></a>
    <a href="courses.php" class="nav-item <?= basename($_SERVER['PHP_SELF'])=='courses.php'?'active':'' ?>"><i class="fas fa-book"></i><span>Courses</span></a>
    <a href="lessons.php" class="nav-item <?= basename($_SERVER['PHP_SELF'])=='lessons.php'?'active':'' ?>"><i class="fas fa-chalkboard-teacher"></i><span>Lessons</span></a>
    <a href="assignments.php" class="nav-item <?= basename($_SERVER['PHP_SELF'])=='assignments.php'?'active':'' ?>"><i class="fas fa-tasks"></i><span>Assignments</span></a>
    <a href="quizzes.php" class="nav-item <?= basename($_SERVER['PHP_SELF'])=='quizzes.php'?'active':'' ?>"><i class="fas fa-brain"></i><span>Quizzes</span></a>
    <a href="poems.php" class="nav-item <?= basename($_SERVER['PHP_SELF'])=='poems.php'?'active':'' ?>"><i class="fas fa-book-open"></i><span>Poems</span></a>
    <a href="progress.php" class="nav-item <?= basename($_SERVER['PHP_SELF'])=='progress.php'?'active':'' ?>"><i class="fas fa-chart-line"></i><span>Progress</span></a>
    <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type']=='parent'): ?>
    <a href="family.php" class="nav-item <?= basename($_SERVER['PHP_SELF'])=='family.php'?'active':'' ?>"><i class="fas fa-users"></i><span>Family</span></a>
    <?php endif; ?>
    <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type']=='teacher'): ?>
    <a href="teacher.php" class="nav-item <?= basename($_SERVER['PHP_SELF'])=='teacher.php'?'active':'' ?>"><i class="fas fa-chalkboard"></i><span>Teacher</span></a>
    <?php endif; ?>
    <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
<?php else: ?>
    <a href="admin.php" class="nav-item <?= basename($_SERVER['PHP_SELF'])=='admin.php'?'active':'' ?>"><i class="fas fa-tachometer-alt"></i><span>Admin Panel</span></a>
    <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
<?php endif; ?>
</div>
