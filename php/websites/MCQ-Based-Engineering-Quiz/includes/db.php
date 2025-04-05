<?php
/**
 * JSON database handling functions
 */

/**
 * Get all questions from the database
 */
function getAllQuestions() {
    if (!file_exists(QUESTIONS_FILE)) {
        return [];
    }
    
    $data = file_get_contents(QUESTIONS_FILE);
    $questions = json_decode($data, true);
    
    return isset($questions['questions']) ? $questions['questions'] : [];
}

/**
 * Get questions filtered by tags
 */
function getQuestionsByTags($tags, $limit = 5) {
    $allQuestions = getAllQuestions();
    $filteredQuestions = [];
    
    // Make sure limit is an integer - check for 'random' string case
    if ($limit === 'random') {
        $limit = rand(5, 20);
        error_log("Random limit generated: " . $limit);
    } else {
        $limit = intval($limit);
        if ($limit <= 0) {
            $limit = 5;
        }
    }
    
    // Debug log for troubleshooting
    error_log("getQuestionsByTags requested with limit: " . $limit);
    
    // Filter questions based on tags
    foreach ($allQuestions as $question) {
        $hasTag = false;
        foreach ($tags as $tag) {
            if (in_array($tag, $question['tags'])) {
                $hasTag = true;
                break;
            }
        }
        
        if ($hasTag) {
            $filteredQuestions[] = $question;
        }
    }
    
    // Randomize and limit questions
    shuffle($filteredQuestions);
    $limitedQuestions = array_slice($filteredQuestions, 0, $limit);
    
    // Double-check that we're returning the right number
    error_log("Returning " . count($limitedQuestions) . " questions");
    
    return $limitedQuestions;
}

/**
 * Get all available tags from questions
 */
function getAllTags() {
    $questions = getAllQuestions();
    $tags = [];
    
    foreach ($questions as $question) {
        foreach ($question['tags'] as $tag) {
            if (!in_array($tag, $tags)) {
                $tags[] = $tag;
            }
        }
    }
    
    sort($tags);
    return $tags;
}

/**
 * Save quiz result
 */
function saveResult($result) {
    if (!file_exists(RESULTS_FILE)) {
        $data = ['results' => []];
    } else {
        $json = file_get_contents(RESULTS_FILE);
        $data = json_decode($json, true);
    }
    
    $result['timestamp'] = time();
    $data['results'][] = $result;
    
    file_put_contents(RESULTS_FILE, json_encode($data, JSON_PRETTY_PRINT));
    return true;
}

/**
 * Get all results from the database
 */
function getAllResults() {
    if (!file_exists(RESULTS_FILE)) {
        return [];
    }
    
    $data = file_get_contents(RESULTS_FILE);
    $results = json_decode($data, true);
    
    $allResults = isset($results['results']) ? $results['results'] : [];
    
    // Sort by timestamp (most recent first)
    usort($allResults, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    return $allResults;
}

/**
 * Get quiz presets
 */
function getPresets() {
    if (!file_exists(PRESETS_FILE)) {
        return [];
    }
    
    $data = file_get_contents(PRESETS_FILE);
    $presets = json_decode($data, true);
    
    return isset($presets['presets']) ? $presets['presets'] : [];
}

/**
 * Get preset by ID
 */
function getPresetById($presetId) {
    $presets = getPresets();
    
    foreach ($presets as $preset) {
        if ($preset['id'] == $presetId) {
            return $preset;
        }
    }
    
    return null;
} 