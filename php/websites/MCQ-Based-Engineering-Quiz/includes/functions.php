<?php
/**
 * General application helper functions
 */

/**
 * Sanitize input to prevent XSS attacks
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if a request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Generate a JSON response
 */
function jsonResponse($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

/**
 * Calculate quiz score
 */
function calculateScore($userAnswers, $questions) {
    $score = 0;
    $total = count($userAnswers);
    
    foreach ($userAnswers as $id => $answer) {
        foreach ($questions as $question) {
            if ($question['id'] == $id && $answer == $question['correct']) {
                $score++;
                break;
            }
        }
    }
    
    return [
        'score' => $score,
        'total' => $total,
        'percentage' => $total > 0 ? round(($score / $total) * 100) : 0
    ];
} 