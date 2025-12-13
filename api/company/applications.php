<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

session_start();
requireLogin();

try {
    $user_id = $_SESSION['user_id'];
    if (getUserType() !== 'company') {
        throw new Exception('Companies only');
    }
    $company = $db->fetch("SELECT id FROM companies WHERE user_id = ?", [$user_id]);
    if (!$company) { throw new Exception('Company profile not found'); }
    $company_id = $company['id'];

    $apps = $db->fetchAll(
        "SELECT ja.*, j.title, j.location, u.name as candidate_name, u.email as candidate_email
         FROM job_applications ja
         JOIN jobs j ON ja.job_id = j.id
         JOIN job_seekers js ON ja.job_seeker_id = js.id
         JOIN users u ON js.user_id = u.id
         WHERE j.company_id = ?
         ORDER BY ja.application_date DESC
         LIMIT 200",
        [$company_id]
    );

    echo json_encode(['success' => true, 'applications' => $apps]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>


