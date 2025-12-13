<?php
// Minimal Assignment API: POST create only, JSON responses
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/database.php';

function send_json($code, $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

try {
    // Handle GET request to fetch assignments for the company
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'company') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized: company login required']);
            exit;
        }

        $user_id = (int)$_SESSION['user_id'];
        $db = new Database();

        // Resolve company_id from user_id
        $company = $db->fetch('SELECT id FROM companies WHERE user_id = ?', [$user_id]);
        if (!$company || !isset($company['id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Company profile not found for current user']);
            exit;
        }
        $company_id = (int)$company['id'];

        try {
            // Fetch assignments for jobs owned by this company OR created by this user
            $sql = "SELECT 
                        a.id, a.title, a.description, a.due_date, a.status, a.requires_camera,
                        j.title AS job_title,
                        ucreator.full_name AS creator_name,
                        uassignee.full_name AS assignee_name
                    FROM assignments a
                    JOIN jobs j ON a.job_id = j.id
                    LEFT JOIN users ucreator ON ucreator.id = a.created_by
                    LEFT JOIN users uassignee ON uassignee.id = a.assigned_to
                    WHERE j.company_id = ? OR a.created_by = ?
                    ORDER BY a.created_at DESC";
            $assignments = $db->fetchAll($sql, [$company_id, $user_id]);

            http_response_code(200);
            echo json_encode(['success' => true, 'assignments' => $assignments ?: []]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch assignments: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        send_json(405, [
            'success' => false,
            'message' => 'Method not allowed',
        ]);
    }

    // Ensure authenticated company session
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'company') {
        send_json(401, [
            'success' => false,
            'message' => 'Unauthorized: company login required',
        ]);
    }

    $user_id = (int)$_SESSION['user_id'];
    $db = new Database();

    // Validate inputs (support both JSON and form-encoded)
    $input = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $decoded = json_decode(file_get_contents('php://input'), true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }
    // Fallback to $_POST when not JSON
    $title = trim(($input['title'] ?? ($_POST['title'] ?? '')));
    $description = trim(($input['description'] ?? ($_POST['description'] ?? '')));
    $job_id = isset($input['job_id']) ? (int)$input['job_id'] : (isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0);
    $due_date = trim(($input['due_date'] ?? ($_POST['due_date'] ?? ''))); // YYYY-MM-DD
    $requires_camera = isset($input['requires_camera']) ? (int)$input['requires_camera'] : (isset($_POST['requires_camera']) ? (int)$_POST['requires_camera'] : 0); // 0/1

    if ($title === '' || $job_id <= 0) {
        send_json(400, [
            'success' => false,
            'message' => 'title and job_id are required',
        ]);
    }

    // Get company id for current user
    $company = $db->fetch('SELECT id FROM companies WHERE user_id = ?', [$user_id]);
    if (!$company) {
        send_json(403, [
            'success' => false,
            'message' => 'Company profile not found for current user',
        ]);
    }
    $company_id = (int)$company['id'];

    // Verify job belongs to this company
    $job = $db->fetch('SELECT id, company_id FROM jobs WHERE id = ?', [$job_id]);
    if (!$job) {
        send_json(404, [
            'success' => false,
            'message' => 'Job not found',
        ]);
    }
    if ((int)$job['company_id'] !== $company_id) {
        send_json(403, [
            'success' => false,
            'message' => 'You do not have permission to add assignments to this job',
        ]);
    }

    // Insert assignment
    $params = [
        $job_id,
        $title,
        $description,
        $due_date !== '' ? $due_date : null,
        $user_id,
        $requires_camera ? 1 : 0,
    ];

    $db->execute(
        'INSERT INTO assignments (job_id, title, description, due_date, created_by, requires_camera, status)
         VALUES (?, ?, ?, ?, ?, ?, "pending")',
        $params
    );

    $assignment_id = (int)$db->lastInsertId();

    send_json(200, [
        'success' => true,
        'message' => 'Assignment created successfully',
        'assignment_id' => $assignment_id,
    ]);

} catch (Throwable $e) {
    error_log('Assignment API error: ' . $e->getMessage());
    send_json(500, [
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
    ]);
}
?>

