<?php
/**
 * Question Model
 * Handles question-related database operations
 */

require_once __DIR__ . '/../config/db.php';

class QuestionModel {
    /**
     * Get question by ID
     * @param int $id Question ID
     * @return array|null Question record or null
     */
    public static function getById($id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get all questions for a quiz
     * @param int $quiz_id Quiz ID
     * @param bool $approved_only Only return approved questions (default: true)
     * @return array Array of question records
     */
    public static function getByQuiz($quiz_id, $approved_only = true) {
        $db = get_db();
        if ($approved_only) {
            $stmt = $db->prepare("SELECT * FROM questions WHERE quiz_id = ? AND is_approved = 1 ORDER BY id ASC");
        } else {
            $stmt = $db->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
        }
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $questions = [];
        while ($row = $result->fetch_assoc()) {
            $questions[] = $row;
        }
        return $questions;
    }
    
    /**
     * Create a new question
     * @param int $quiz_id Quiz ID
     * @param int $created_by_user_id User ID who created the question
     * @param string $type Question type ('mcq' or 'enum')
     * @param string $question_text Question text
     * @param string|null $options_json JSON string of options (for MCQ)
     * @param string $correct_answer Correct answer
     * @param int $points Points for this question
     * @param bool $is_approved Whether question is approved (default: true)
     * @return int|false New question ID or false on failure
     */
    public static function create($quiz_id, $created_by_user_id, $type, $question_text, $options_json, $correct_answer, $points = 1, $is_approved = true) {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO questions (quiz_id, created_by_user_id, type, question_text, options_json, correct_answer, points, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $is_app = $is_approved ? 1 : 0;
        $stmt->bind_param("iissssii", $quiz_id, $created_by_user_id, $type, $question_text, $options_json, $correct_answer, $points, $is_app);
        
        if ($stmt->execute()) {
            return $db->insert_id;
        } else {
            error_log("Question creation failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Update question
     * @param int $id Question ID
     * @param string $question_text Question text
     * @param string|null $options_json JSON string of options (for MCQ)
     * @param string $correct_answer Correct answer
     * @param int $points Points for this question
     * @param string|null $type Question type (optional, for changing type)
     * @return bool Success status
     */
    public static function update($id, $question_text, $options_json, $correct_answer, $points, $type = null) {
        $db = get_db();
        if ($type !== null) {
            $stmt = $db->prepare("UPDATE questions SET type = ?, question_text = ?, options_json = ?, correct_answer = ?, points = ? WHERE id = ?");
            $stmt->bind_param("ssssii", $type, $question_text, $options_json, $correct_answer, $points, $id);
        } else {
            $stmt = $db->prepare("UPDATE questions SET question_text = ?, options_json = ?, correct_answer = ?, points = ? WHERE id = ?");
            $stmt->bind_param("sssii", $question_text, $options_json, $correct_answer, $points, $id);
        }
        
        if ($stmt->execute()) {
            return true;
        } else {
            error_log("Question update failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Delete question
     * @param int $id Question ID
     * @return bool Success status
     */
    public static function delete($id) {
        $db = get_db();
        $stmt = $db->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return true;
        } else {
            error_log("Question deletion failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Approve a question (set is_approved = 1)
     * @param int $id Question ID
     * @return bool Success status
     */
    public static function approve($id) {
        $db = get_db();
        $stmt = $db->prepare("UPDATE questions SET is_approved = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return true;
        } else {
            error_log("Question approval failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Get count of questions for a quiz
     * @param int $quiz_id Quiz ID
     * @param bool $approved_only Only count approved questions
     * @return int Question count
     */
    public static function countByQuiz($quiz_id, $approved_only = true) {
        $db = get_db();
        if ($approved_only) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM questions WHERE quiz_id = ? AND is_approved = 1");
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM questions WHERE quiz_id = ?");
        }
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }
}
