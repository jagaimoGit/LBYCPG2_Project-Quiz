<?php
/**
 * User Model
 * Handles user-related database operations
 */

require_once __DIR__ . '/../config/db.php';

class UserModel {
    /**
     * Get user by ID
     * @param int $id User ID
     * @return array|null User record or null
     */
    public static function getById($id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get user by email
     * @param string $email Email address
     * @return array|null User record or null
     */
    public static function getByEmail($email) {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Create a new user
     * @param string $name User name
     * @param string $email Email address
     * @param string $password_hash Hashed password
     * @param string $role User role ('host' or 'participant')
     * @return int|false New user ID or false on failure
     */
    public static function create($name, $email, $password_hash, $role = 'participant') {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password_hash, $role);
        
        if ($stmt->execute()) {
            return $db->insert_id;
        } else {
            error_log("User creation failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Update user profile
     * @param int $id User ID
     * @param string $name New name
     * @param string|null $password_hash New password hash (null to keep current)
     * @return bool Success status
     */
    public static function update($id, $name, $password_hash = null) {
        $db = get_db();
        
        if ($password_hash) {
            $stmt = $db->prepare("UPDATE users SET name = ?, password_hash = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $password_hash, $id);
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
        }
        
        if ($stmt->execute()) {
            return true;
        } else {
            error_log("User update failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Verify user credentials
     * @param string $email Email address
     * @param string $password Plain text password
     * @return array|null User record if valid, null otherwise
     */
    public static function verifyCredentials($email, $password) {
        $user = self::getByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return null;
    }
}
