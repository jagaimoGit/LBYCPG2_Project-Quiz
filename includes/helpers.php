<?php
/**
 * Helper Functions
 * Utility functions for flash messages, validation, and output
 */

/**
 * Set a flash message to be displayed on the next page load
 * @param string $type Message type (success, error, info, warning)
 * @param string $message The message text
 */
function set_flash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear the flash message
 * @return array|null Flash message array with 'type' and 'message', or null
 */
function get_flash() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    
    return null;
}

/**
 * Display flash message if one exists
 * @return void
 */
function display_flash() {
    $flash = get_flash();
    if ($flash) {
        $type = htmlspecialchars($flash['type']);
        $message = htmlspecialchars($flash['message']);
        echo "<div class='flash flash-{$type}'>{$message}</div>";
    }
}

/**
 * Safely output a string with HTML escaping
 * @param string $string The string to escape
 * @return string Escaped string
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate required field
 * @param mixed $value The value to check
 * @return bool True if value is not empty
 */
function require_field($value) {
    return !empty(trim($value));
}

/**
 * Generate a random access code for quizzes
 * @param int $length Length of the code (default 8)
 * @return string Random alphanumeric code
 */
function generate_access_code($length = 8) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

/**
 * Normalize enumeration answer for comparison
 * @param string $answer The answer to normalize
 * @return string Normalized answer (trimmed, lowercase, extra spaces removed)
 */
function normalize_enum_answer($answer) {
    return strtolower(trim(preg_replace('/\s+/', ' ', $answer)));
}

/**
 * Handle image upload for questions
 * @param array $file $_FILES array element
 * @param int $question_id Question ID (for unique naming)
 * @return string|false Image path on success, false on failure
 */
function upload_question_image($file, $question_id = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return false;
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads/question_images/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'q_' . ($question_id ? $question_id . '_' : '') . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'uploads/question_images/' . $filename;
    }
    
    return false;
}

/**
 * Delete question image file
 * @param string $image_path Path to image
 * @return bool Success status
 */
function delete_question_image($image_path) {
    if ($image_path && file_exists(__DIR__ . '/../' . $image_path)) {
        return unlink(__DIR__ . '/../' . $image_path);
    }
    return true; // Return true if file doesn't exist (already deleted)
}

/**
 * Format time duration in seconds to human-readable format
 * @param int $seconds Time in seconds
 * @return string Formatted time (MM:SS or HH:MM:SS)
 */
function format_time_duration($seconds) {
    if ($seconds === null || $seconds < 0) {
        return 'N/A';
    }
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    } else {
        return sprintf('%d:%02d', $minutes, $secs);
    }
}

/**
 * Calculate time duration between two timestamps
 * @param string $start Start timestamp
 * @param string $end End timestamp
 * @return int|null Duration in seconds, or null if invalid
 */
function calculate_time_duration($start, $end) {
    if (empty($start) || empty($end)) {
        return null;
    }
    
    $start_time = strtotime($start);
    $end_time = strtotime($end);
    
    if ($start_time === false || $end_time === false || $end_time < $start_time) {
        return null;
    }
    
    return $end_time - $start_time;
}
