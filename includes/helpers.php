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
