<?php
/**
 * Attempt Model
 * Handles quiz attempt and answer-related database operations
 */

require_once __DIR__ . '/../config/db.php';

class AttemptModel {
    /**
     * Get attempt by ID
     * @param int $id Attempt ID
     * @return array|null Attempt record or null
     */
    public static function getById($id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT a.*, q.title as quiz_title, u.name as user_name FROM quiz_attempts a LEFT JOIN quizzes q ON a.quiz_id = q.id LEFT JOIN users u ON a.user_id = u.id WHERE a.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get incomplete attempt for user and quiz
     * @param int $quiz_id Quiz ID
     * @param int $user_id User ID
     * @return array|null Attempt record or null
     */
    public static function getIncompleteAttempt($quiz_id, $user_id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM quiz_attempts WHERE quiz_id = ? AND user_id = ? AND completed_at IS NULL ORDER BY started_at DESC LIMIT 1");
        $stmt->bind_param("ii", $quiz_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get all attempts for a quiz
     * @param int $quiz_id Quiz ID
     * @return array Array of attempt records
     */
    public static function getByQuiz($quiz_id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT a.*, u.name as user_name, u.email as user_email FROM quiz_attempts a LEFT JOIN users u ON a.user_id = u.id WHERE a.quiz_id = ? AND a.completed_at IS NOT NULL ORDER BY a.completed_at DESC");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $attempts = [];
        while ($row = $result->fetch_assoc()) {
            $attempts[] = $row;
        }
        return $attempts;
    }
    
    /**
     * Get all attempts by a user
     * @param int $user_id User ID
     * @return array Array of attempt records
     */
    public static function getByUser($user_id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT a.*, q.title as quiz_title FROM quiz_attempts a LEFT JOIN quizzes q ON a.quiz_id = q.id WHERE a.user_id = ? ORDER BY a.completed_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $attempts = [];
        while ($row = $result->fetch_assoc()) {
            $attempts[] = $row;
        }
        return $attempts;
    }
    
    /**
     * Create a new attempt
     * @param int $quiz_id Quiz ID
     * @param int $user_id User ID
     * @param int $total_possible_points Total possible points at time of attempt
     * @return int|false New attempt ID or false on failure
     */
    public static function create($quiz_id, $user_id, $total_possible_points = null) {
        $db = get_db();
        if ($total_possible_points !== null) {
            $stmt = $db->prepare("INSERT INTO quiz_attempts (quiz_id, user_id, total_possible_points) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $quiz_id, $user_id, $total_possible_points);
        } else {
            $stmt = $db->prepare("INSERT INTO quiz_attempts (quiz_id, user_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $quiz_id, $user_id);
        }
        
        if ($stmt->execute()) {
            return $db->insert_id;
        } else {
            error_log("Attempt creation failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Complete an attempt and set score
     * @param int $attempt_id Attempt ID
     * @param int $score Total score
     * @return bool Success status
     */
    public static function complete($attempt_id, $score) {
        $db = get_db();
        $stmt = $db->prepare("UPDATE quiz_attempts SET completed_at = NOW(), score = ? WHERE id = ?");
        $stmt->bind_param("ii", $score, $attempt_id);
        
        if ($stmt->execute()) {
            return true;
        } else {
            error_log("Attempt completion failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Save an answer for a question in an attempt
     * @param int $attempt_id Attempt ID
     * @param int $question_id Question ID
     * @param string $answer_text Answer text
     * @param bool $is_correct Whether answer is correct
     * @return int|false New answer ID or false on failure
     */
    public static function saveAnswer($attempt_id, $question_id, $answer_text, $is_correct) {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO quiz_answers (attempt_id, question_id, answer_text, is_correct) VALUES (?, ?, ?, ?)");
        $is_corr = $is_correct ? 1 : 0;
        $stmt->bind_param("iisi", $attempt_id, $question_id, $answer_text, $is_corr);
        
        if ($stmt->execute()) {
            return $db->insert_id;
        } else {
            error_log("Answer saving failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Get all answers for an attempt
     * @param int $attempt_id Attempt ID
     * @return array Array of answer records with question details
     */
    public static function getAnswersByAttempt($attempt_id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT qa.*, q.question_text, q.type, q.correct_answer, q.points FROM quiz_answers qa LEFT JOIN questions q ON qa.question_id = q.id WHERE qa.attempt_id = ? ORDER BY q.id ASC");
        $stmt->bind_param("i", $attempt_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $answers = [];
        while ($row = $result->fetch_assoc()) {
            $answers[] = $row;
        }
        return $answers;
    }
    
    /**
     * Get aggregated statistics for a quiz
     * @param int $quiz_id Quiz ID
     * @return array Statistics (attempt_count, avg_score, max_score, min_score)
     */
    public static function getQuizStatistics($quiz_id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT COUNT(*) as attempt_count, AVG(score) as avg_score, MAX(score) as max_score, MIN(score) as min_score FROM quiz_attempts WHERE quiz_id = ? AND completed_at IS NOT NULL");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return [
            'attempt_count' => (int)$row['attempt_count'],
            'avg_score' => $row['avg_score'] ? round((float)$row['avg_score'], 2) : 0,
            'max_score' => (int)$row['max_score'],
            'min_score' => (int)$row['min_score']
        ];
    }
    
    /**
     * Get per-question statistics for a quiz
     * @param int $quiz_id Quiz ID
     * @return array Array of question statistics
     */
    public static function getQuestionStatistics($quiz_id) {
        $db = get_db();
        $stmt = $db->prepare("
            SELECT 
                q.id,
                q.question_text,
                COUNT(qa.id) as total_answers,
                SUM(qa.is_correct) as correct_answers,
                ROUND(SUM(qa.is_correct) * 100.0 / COUNT(qa.id), 2) as correct_percentage
            FROM questions q
            LEFT JOIN quiz_answers qa ON q.id = qa.question_id
            LEFT JOIN quiz_attempts a ON qa.attempt_id = a.id
            WHERE q.quiz_id = ? AND a.completed_at IS NOT NULL
            GROUP BY q.id, q.question_text
            ORDER BY q.id ASC
        ");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        return $stats;
    }
    
    /**
     * Check if attempt belongs to user
     * @param int $attempt_id Attempt ID
     * @param int $user_id User ID
     * @return bool True if attempt belongs to user
     */
    public static function belongsToUser($attempt_id, $user_id) {
        $attempt = self::getById($attempt_id);
        return $attempt && $attempt['user_id'] == $user_id;
    }
    
    /**
     * Get play count for a quiz
     * @param int $quiz_id Quiz ID
     * @return int Number of completed attempts
     */
    public static function getPlayCount($quiz_id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM quiz_attempts WHERE quiz_id = ? AND completed_at IS NOT NULL");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }
}
