<?php
/**
 * Rating Model
 * Handles quiz rating-related database operations
 */

require_once __DIR__ . '/../config/db.php';

class RatingModel {
    /**
     * Create a new rating
     * @param int $quiz_id Quiz ID
     * @param int $user_id User ID
     * @param int $attempt_id Attempt ID
     * @param int $rating Rating (1-5)
     * @return bool Success
     */
    public static function create($quiz_id, $user_id, $attempt_id, $rating) {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO quiz_ratings (quiz_id, user_id, attempt_id, rating) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = ?");
        $stmt->bind_param("iiiii", $quiz_id, $user_id, $attempt_id, $rating, $rating);
        $result = $stmt->execute();
        
        if ($result) {
            // Update quiz average rating
            self::updateQuizAverageRating($quiz_id);
        }
        
        return $result;
    }
    
    /**
     * Get rating by user and quiz
     * @param int $quiz_id Quiz ID
     * @param int $user_id User ID
     * @return array|null Rating record or null
     */
    public static function getByUserAndQuiz($quiz_id, $user_id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM quiz_ratings WHERE quiz_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("ii", $quiz_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get rating by attempt
     * @param int $attempt_id Attempt ID
     * @return array|null Rating record or null
     */
    public static function getByAttempt($attempt_id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM quiz_ratings WHERE attempt_id = ?");
        $stmt->bind_param("i", $attempt_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Update quiz average rating
     * @param int $quiz_id Quiz ID
     */
    public static function updateQuizAverageRating($quiz_id) {
        $db = get_db();
        $stmt = $db->prepare("UPDATE quizzes SET average_rating = (SELECT AVG(rating) FROM quiz_ratings WHERE quiz_id = ?) WHERE id = ?");
        $stmt->bind_param("ii", $quiz_id, $quiz_id);
        $stmt->execute();
    }
    
    /**
     * Get average rating for a quiz
     * @param int $quiz_id Quiz ID
     * @return float|null Average rating or null
     */
    public static function getAverageRating($quiz_id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT AVG(rating) as avg_rating FROM quiz_ratings WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['avg_rating'] ? round((float)$row['avg_rating'], 2) : null;
    }
    
    /**
     * Get rating count for a quiz
     * @param int $quiz_id Quiz ID
     * @return int Rating count
     */
    public static function getRatingCount($quiz_id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM quiz_ratings WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }
}

