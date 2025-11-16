<?php
/**
 * Authentication and Authorization Utilities
 * Handles session management and user authentication checks
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/UserModel.php';

/**
 * Get the current logged-in user, or null if not logged in
 * @return array|null User record or null
 */
function current_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return UserModel::getById($_SESSION['user_id']);
}

/**
 * Check if user is logged in, redirect to login if not
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

/**
 * Check if current user is a host, redirect or show error if not
 */
function require_host() {
    require_login();
    
    $user = current_user();
    if (!$user || $user['role'] !== 'host') {
        header('Location: index.php');
        exit;
    }
}

/**
 * Check if current user has a specific role
 * @param string $role The role to check ('host' or 'participant')
 * @return bool
 */
function has_role($role) {
    $user = current_user();
    return $user && $user['role'] === $role;
}

/**
 * Check if current user is a host
 * @return bool
 */
function is_host() {
    return has_role('host');
}

/**
 * Check if current user is a participant
 * @return bool
 */
function is_participant() {
    return has_role('participant');
}
