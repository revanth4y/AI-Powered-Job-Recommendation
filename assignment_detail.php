<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

// Get assignment ID
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($assignment_id <= 0) {
    header('Location: dashboard/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = getUserType();

// Get assignment details
try {
    $assignment = $db->fetch(
        "SELECT ja.*, j.title as job_title, j.company_id, 
                c.company_name, c.logo as company_logo,
                u1.name as creator_name, u2.name as assignee_name,
                u3.name as reviewer_name
         FROM job_assignments ja
         JOIN jobs j ON ja.job_id = j.id
         JOIN companies c ON j.company_id = c.id
         LEFT JOIN users u1 ON ja.created_by = u1.id
         LEFT JOIN users u2 ON ja.assigned_to = u2.id
         LEFT JOIN users u3 ON ja.reviewed_by = u3.id
         WHERE ja.id = ?",
        [$assignment_id]
    );

    if (!$assignment) {
        throw new Exception('Assignment not found');
    }

    // Check permissions
    $has_access = false;
    
    if ($user_type === 'admin') {
        $has_access = true;
    } elseif ($user_type === 'company') {
        $company_id = $db->fetch("SELECT id FROM companies WHERE user_id = ?", [$user_id])['id'] ?? 0;
        if ($company_id == $assignment['company_id']) {
            $has_access = true;
        }
    } elseif ($user_type === 'job_seeker') {
        if ($assignment['assigned_to'] == $user_id || $assignment['created_by'] == $user_id) {
            $has_access = true;
        }
    }
    
    if (!$has_access) {
        throw new Exception('You do not have permission to view this assignment');
    }
    
    // Check if user can submit
    $can_submit = ($user_type === 'job_seeker' && $assignment['assigned_to'] == $user_id && 
                  $assignment['status'] === 'pending');
                  
    // Check if user can review
    $can_review = false;
    if ($assignment['status'] === 'submitted') {
        if ($user_type === 'admin') {
            $can_review = true;
        } elseif ($user_type === 'company') {
            $company_id = $db->fetch("SELECT id FROM companies WHERE user_id = ?", [$user_id])['id'] ?? 0;
            if ($company_id == $assignment['company_id']) {
                $can_review = true;
            }
        } elseif ($assignment['created_by'] == $user_id) {
            $can_review = true;
        }
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: dashboard/index.php');
    exit();
}

// Format dates
$created_at = new DateTime($assignment['created_at']);
$due_date = new DateTime($assignment['due_date']);
$submission_date = !empty($assignment['submission_date']) ? new DateTime($assignment['submission_date']) : null;
$reviewed_at = !empty($assignment['reviewed_at']) ? new DateTime($assignment['reviewed_at']) : null;

// Get status class
$status_class = '';
switch ($assignment['status']) {
    case 'pending':
        $status_class = 'warning';
        break;
    case 'submitted':
        $status_class = 'info';
        break;
    case 'approved':
        $status_class = 'success';
        break;
    case 'rejected':
        $status_class = 'danger';
        break;
    default:
        $status_class = 'secondary';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Details - AI Job System</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/responsive.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/assignments.css?v=<?php echo time(); ?>">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2196F3">
    
    <!-- Camera access script -->
    <script src="assets/js/camera-access.js?v=<?php echo time(); ?>" defer></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="content-header">
                <h1>Assignment Details</h1>
                <a href="javascript:history.back()" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            
            <div id="message-container">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="assignment-detail-container">
                <div class="assignment-header">
                    <div class="assignment-title">
                        <h2><?php echo htmlspecialchars($assignment['title']); ?></h2>
                        <span class="badge badge-<?php echo $status_class; ?>">
                            <?php echo ucfirst(htmlspecialchars($assignment['status'])); ?>
                        </span>
                    </div>
                    <div class="assignment-meta">
                        <div class="meta-item">
                            <span class="meta-label">Job:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($assignment['job_title']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Company:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($assignment['company_name']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Created by:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($assignment['creator_name']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Created on:</span>
                            <span class="meta-value"><?php echo $created_at->format('M d, Y'); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Due date:</span>
                            <span class="meta-value"><?php echo $due_date->format('M d, Y'); ?></span>
                        </div>
                        <?php if ($assignment['assignee_name']): ?>
                        <div class="meta-item">
                            <span class="meta-label">Assigned to:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($assignment['assignee_name']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="assignment-content">
                    <h3>Description</h3>
                    <div class="description-box">
                        <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                    </div>
                    
                    <?php if (!empty($assignment['submission'])): ?>
                    <h3>Submission</h3>
                    <div class="submission-box">
                        <div class="submission-meta">
                            <span>Submitted on: <?php echo $submission_date->format('M d, Y h:i A'); ?></span>
                        </div>
                        <div class="submission-content">
                            <?php echo nl2br(htmlspecialchars($assignment['submission'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($assignment['feedback'])): ?>
                    <h3>Feedback</h3>
                    <div class="feedback-box">
                        <div class="feedback-meta">
                            <span>Reviewed by: <?php echo htmlspecialchars($assignment['reviewer_name']); ?></span>
                            <span>Reviewed on: <?php echo $reviewed_at->format('M d, Y h:i A'); ?></span>
                        </div>
                        <div class="feedback-content">
                            <?php echo nl2br(htmlspecialchars($assignment['feedback'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($can_submit): ?>
                    <div class="submission-form-container">
                        <h3>Submit Assignment</h3>
                        <button id="start-assignment-btn" class="btn btn-primary start-assignment-btn">Start Assignment</button>
                        <div id="assignment-content" class="hidden">
                            <form id="submit-assignment-form" data-assignment-id="<?php echo $assignment_id; ?>">
                                <div class="form-group">
                                    <label for="submission">Your Submission</label>
                                    <textarea id="submission" name="submission" class="form-control" rows="6" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Assignment</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($can_review): ?>
                    <div class="review-form-container">
                        <h3>Review Submission</h3>
                        <form id="review-assignment-form" data-assignment-id="<?php echo $assignment_id; ?>">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control" required>
                                    <option value="">Select status</option>
                                    <option value="approved">Approve</option>
                                    <option value="rejected">Reject</option>
                                    <option value="pending">Request Changes</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="feedback">Feedback</label>
                                <textarea id="feedback" name="feedback" class="form-control" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/camera-modal.php'; ?>
    
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/assignments.js?v=<?php echo time(); ?>"></script>
</body>
</html>