<?php
header('Content-Type: application/json');
// Same-origin only so session cookies are sent

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

session_start();
requireLogin();

try {
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['role'] ?? $_SESSION['user_type'];
    
    if ($user_type !== 'job_seeker') {
        throw new Exception('Only job seekers can view recommendations');
    }
    
    // Get AI recommendations for the user
    $sql = "SELECT ar.*, j.title, j.description, j.location, j.job_type, j.salary_min, j.salary_max,
            j.requirements, j.benefits, c.company_name, c.industry,
            ar.match_reasons, ar.recommendation_score
            FROM ai_recommendations ar
            JOIN jobs j ON ar.job_id = j.id
            JOIN companies c ON j.company_id = c.id
            WHERE ar.user_id = ? AND ar.status = 'active'
            ORDER BY ar.recommendation_score DESC
            LIMIT 20";
    
    $recommendations = $db->fetchAll($sql, [$user_id]);
    
    // If no AI recommendations exist, generate some basic ones
    if (empty($recommendations)) {
        $recommendations = generateBasicRecommendations($user_id);
    }
    
    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations,
        'count' => count($recommendations)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Generate basic job recommendations when AI recommendations aren't available
 */
function generateBasicRecommendations($user_id) {
    global $db;
    
    // Get user's profile for basic matching
    $profile = $db->fetch("SELECT * FROM job_seekers WHERE user_id = ?", [$user_id]);
    
    // Get available jobs
    $sql = "SELECT j.*, c.company_name, c.industry
            FROM jobs j
            JOIN companies c ON j.company_id = c.id
            WHERE j.status = 'active'
            ORDER BY j.created_at DESC
            LIMIT 10";
    
    $jobs = $db->fetchAll($sql);
    
    $recommendations = [];
    foreach ($jobs as $job) {
        $score = calculateBasicMatchScore($job, $profile);
        $reasons = generateMatchReasons($job, $profile);
        
        $recommendations[] = [
            'job_id' => $job['id'],
            'title' => $job['title'],
            'description' => $job['description'],
            'location' => $job['location'],
            'job_type' => $job['job_type'],
            'salary_min' => $job['salary_min'],
            'salary_max' => $job['salary_max'],
            'requirements' => $job['requirements'],
            'benefits' => $job['benefits'],
            'company_name' => $job['company_name'],
            'industry' => $job['industry'],
            'recommendation_score' => $score,
            'match_reasons' => json_encode($reasons),
            'status' => 'active'
        ];
    }
    
    // Sort by score
    usort($recommendations, function($a, $b) {
        return $b['recommendation_score'] <=> $a['recommendation_score'];
    });
    
    return $recommendations;
}

/**
 * Calculate basic match score between job and profile
 */
function calculateBasicMatchScore($job, $profile) {
    $score = 50; // Base score
    
    if ($profile) {
        // Match skills
        if (!empty($profile['skills']) && !empty($job['requirements'])) {
            $profileSkills = json_decode($profile['skills'], true) ?? [];
            $jobRequirements = strtolower($job['requirements']);
            
            $matchingSkills = 0;
            foreach ($profileSkills as $skill) {
                if (is_string($skill)) {
                    $skillName = strtolower($skill);
                } else if (isset($skill['name'])) {
                    $skillName = strtolower($skill['name']);
                } else {
                    continue;
                }
                
                if (strpos($jobRequirements, $skillName) !== false) {
                    $matchingSkills++;
                }
            }
            
            if ($matchingSkills > 0) {
                $score += min(30, $matchingSkills * 10);
            }
        }
        
        // Match experience level
        if (!empty($profile['experience_years']) && !empty($job['requirements'])) {
            $experience = $profile['experience_years'];
            $requirements = strtolower($job['requirements']);
            
            if ($experience >= 5 && strpos($requirements, 'senior') !== false) {
                $score += 15;
            } elseif ($experience >= 2 && strpos($requirements, 'mid') !== false) {
                $score += 15;
            } elseif ($experience < 2 && (strpos($requirements, 'entry') !== false || strpos($requirements, 'junior') !== false)) {
                $score += 15;
            }
        }
    }
    
    return min(95, $score); // Cap at 95%
}

/**
 * Generate match reasons
 */
function generateMatchReasons($job, $profile) {
    $reasons = [];
    
    if ($profile) {
        if (!empty($profile['skills'])) {
            $reasons[] = "Your skills align with job requirements";
        }
        
        if (!empty($profile['experience_years'])) {
            $experience = $profile['experience_years'];
            if ($experience >= 3) {
                $reasons[] = "Your {$experience} years of experience matches the role";
            } else {
                $reasons[] = "Great opportunity to grow your career";
            }
        }
        
        if (!empty($profile['location']) && !empty($job['location'])) {
            $reasons[] = "Job location aligns with your preferences";
        }
    }
    
    // Add generic reasons if none found
    if (empty($reasons)) {
        $reasons[] = "New opportunity in " . ($job['industry'] ?? 'your field');
        $reasons[] = "Growing company with career potential";
    }
    
    return $reasons;
}
?>