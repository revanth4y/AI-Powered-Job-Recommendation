<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get user type
$user_type = $_SESSION['user_type'] ?? '';
?>

<div class="dashboard-sidebar">
    <div class="sidebar-header">
        <h2>Job Portal</h2>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <?php if ($user_type == 'admin'): ?>
                <li><a href="dashboard/admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="assignments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'assignments.php' ? 'active' : ''; ?>"><i class="fas fa-tasks"></i> Assignments</a></li>
                <li><a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="jobs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'jobs.php' ? 'active' : ''; ?>"><i class="fas fa-briefcase"></i> Jobs</a></li>
                <li><a href="admin/settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a></li>
            <?php elseif ($user_type == 'company'): ?>
                <li><a href="dashboard/company.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'company.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="assignments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'assignments.php' ? 'active' : ''; ?>"><i class="fas fa-tasks"></i> Assignments</a></li>
                <li><a href="company_jobs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'company_jobs.php' ? 'active' : ''; ?>"><i class="fas fa-briefcase"></i> My Jobs</a></li>
                <li><a href="candidates.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'candidates.php' ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Candidates</a></li>
            <?php elseif ($user_type == 'job_seeker'): ?>
                <li><a href="dashboard/job_seeker.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'job_seeker.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="assignments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'assignments.php' ? 'active' : ''; ?>"><i class="fas fa-tasks"></i> Assignments</a></li>
                <li><a href="job_search.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'job_search.php' ? 'active' : ''; ?>"><i class="fas fa-search"></i> Find Jobs</a></li>
                <li><a href="my_applications.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_applications.php' ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> My Applications</a></li>
                <li><a href="resume.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'resume.php' ? 'active' : ''; ?>"><i class="fas fa-file"></i> Resume</a></li>
            <?php endif; ?>
            <li><a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>"><i class="fas fa-user-circle"></i> Profile</a></li>
            <!-- Use JS logout handler to call API and redirect -->
            <li><a href="#" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
</div>
