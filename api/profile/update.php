<?php
header('Content-Type: application/json');
// Same-origin only; allow cookies for session

session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    // Check if user is logged in
    requireLogin();
    
    $user_id = $_SESSION['user_id'];
    
    // Get input (support both JSON and form data)
    $input = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST)) {
            // Form data
            $input = $_POST;
        } else {
            // JSON input
            $json_input = json_decode(file_get_contents('php://input'), true);
            if ($json_input) {
                $input = $json_input;
            }
        }
    }
    
    // Validate required fields
    if (empty($input)) {
        throw new Exception('No data provided');
    }
    
    $db->beginTransaction();
    
    // Update user table (fullname, phone)
    $user_updates = [];
    $user_params = [];
    
    if (isset($input['fullname']) && !empty($input['fullname'])) {
        $user_updates[] = "name = ?";
        $user_params[] = $input['fullname'];
    }
    
    if (isset($input['phone']) && !empty($input['phone'])) {
        $user_updates[] = "phone = ?";
        $user_params[] = $input['phone'];
    }
    
    if (!empty($user_updates)) {
        $user_params[] = $user_id;
        $db->execute(
            "UPDATE users SET " . implode(", ", $user_updates) . " WHERE id = ?",
            $user_params
        );
    }
    // Check if job seeker profile exists
    $profile_exists = $db->fetch("SELECT id FROM job_seekers WHERE user_id = ?", [$user_id]);
    
    // Update or insert job seeker profile
    $profile_data = [
        'location' => $input['location'] ?? '',
        'skills' => $input['skills'] ?? '[]',
        'experience_years' => $input['experience_years'] ?? '',
        'bio' => $input['bio'] ?? ''
    ];
    
    if ($profile_exists) {
        // Update existing profile
        $update_fields = [];
        $update_params = [];
        
        foreach ($profile_data as $field => $value) {
            if (isset($input[$field])) {
                $update_fields[] = "$field = ?";
                $update_params[] = $value;
            }
        }
        
        if (!empty($update_fields)) {
            $update_params[] = $user_id;
            $db->execute(
                "UPDATE job_seekers SET " . implode(", ", $update_fields) . " WHERE user_id = ?",
                $update_params
            );
        }
    } else {
        // Insert new profile
        $profile_data['user_id'] = $user_id;
        $fields = array_keys($profile_data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $db->execute(
            "INSERT INTO job_seekers (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")",
            array_values($profile_data)
        );
    }
    
    $db->commit();
     
     echo json_encode([
         'success' => true,
         'message' => 'Profile updated successfully'
     ]);
} catch (Exception $e) {
     if (isset($db) && $db->inTransaction()) {
         $db->rollBack();
     }
     
     echo json_encode([
         'success' => false,
         'message' => $e->getMessage()
     ]);
}
    
    // Validate education level
    $valid_education_levels = ['high_school', 'diploma', 'bachelor', 'master', 'phd', 'other'];
    $education_level = trim($input['education_level'] ?? '');
    
    if (!empty($education_level) && !in_array($education_level, $valid_education_levels)) {
        throw new Exception('Invalid education level. Please select from: High School, Diploma, Bachelor\'s Degree, Master\'s Degree, PhD, or Other');
    }
    
    // Process skills - convert to JSON format if needed
    $skills_input = trim($input['skills'] ?? '');
    $skills_json = NULL;
    
    if (!empty($skills_input)) {
        // Check if it's already JSON
        if (json_decode($skills_input) !== null) {
            $skills_json = $skills_input;
        } else {
            // Convert comma-separated skills to JSON array
            $skills_array = array_map('trim', explode(',', $skills_input));
            $skills_array = array_filter($skills_array); // Remove empty values
            $skills_json = json_encode($skills_array);
        }
    }
    
    $profile_data = [
        'location' => trim($input['location'] ?? ''),
        'experience_years' => (int)($input['experience_years'] ?? 0),
        'education_level' => empty($education_level) ? NULL : $education_level,
        'skills' => $skills_json,
        'bio' => trim($input['bio'] ?? '')
    ];
    
    if ($profile) {
        // Update existing profile (updated_at will be set automatically)
        $db->execute(
            "UPDATE job_seekers SET location = ?, experience_years = ?, education_level = ?, skills = ?, bio = ? WHERE user_id = ?",
            [
                $profile_data['location'],
                $profile_data['experience_years'],
                $profile_data['education_level'],
                $profile_data['skills'],
                $profile_data['bio'],
                $user_id
            ]
        );
    } else {
        // Create new profile (created_at and updated_at will be set automatically)
        $db->execute(
            "INSERT INTO job_seekers (user_id, location, experience_years, education_level, skills, bio) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $user_id,
                $profile_data['location'],
                $profile_data['experience_years'],
                $profile_data['education_level'],
                $profile_data['skills'],
                $profile_data['bio']
            ]
        );
    }
    
    $db->commit();
    
    // Log activity
    logActivity($user_id, 'profile_updated', 'User updated their profile information');
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
    
} catch (Exception $e) {
    try {
        $db->rollBack();
    } catch (Exception $rollbackError) {
        error_log('Rollback failed: ' . $rollbackError->getMessage());
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>