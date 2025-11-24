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
    
    /**
     * Get general leaderboard for all participants
     * @param string $sort_by Sort by 'unique_quizzes', 'attempts', or 'avg_score' (default: 'avg_score')
     * @param string $order Sort order 'asc' or 'desc' (default: 'desc')
     * @param int $limit Maximum number of results (default: 100)
     * @return array Array of leaderboard entries with user stats
     */
    public static function getGeneralLeaderboard($sort_by = 'avg_score', $order = 'desc', $limit = 100) {
        $db = get_db();
        
        // Validate sort parameters
        $sort_by = in_array($sort_by, ['unique_quizzes', 'attempts', 'avg_score']) ? $sort_by : 'avg_score';
        $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
        $limit = max(1, min(1000, (int)$limit));
        
        // Build ORDER BY clause - use whitelist to prevent SQL injection
        $order_by_map = [
            'unique_quizzes' => 'unique_quizzes_count',
            'attempts' => 'total_attempts',
            'avg_score' => 'avg_percentage'
        ];
        $order_by_field = $order_by_map[$sort_by];
        
        // Validate order_by_field is safe (whitelist check)
        if (!in_array($order_by_field, ['unique_quizzes_count', 'total_attempts', 'avg_percentage'])) {
            $order_by_field = 'avg_percentage';
        }
        
        $stmt = $db->prepare("
            SELECT 
                u.id,
                u.name,
                u.email,
                COUNT(DISTINCT a.quiz_id) as unique_quizzes_count,
                COUNT(a.id) as total_attempts,
                AVG(a.score * 100.0 / NULLIF(a.total_possible_points, 0)) as avg_percentage,
                SUM(a.score) as total_score,
                SUM(a.total_possible_points) as total_possible
            FROM users u
            LEFT JOIN quiz_attempts a ON u.id = a.user_id AND a.completed_at IS NOT NULL
            WHERE u.role = 'participant'
            GROUP BY u.id, u.name, u.email
            HAVING total_attempts > 0
            ORDER BY `$order_by_field` $order, u.name ASC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $leaderboard = [];
        while ($row = $result->fetch_assoc()) {
            $row['avg_percentage'] = $row['avg_percentage'] ? round((float)$row['avg_percentage'], 2) : 0;
            $row['unique_quizzes_count'] = (int)$row['unique_quizzes_count'];
            $row['total_attempts'] = (int)$row['total_attempts'];
            $leaderboard[] = $row;
        }
        
        return $leaderboard;
    }
}
