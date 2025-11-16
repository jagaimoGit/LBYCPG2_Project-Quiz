<?php
/**
 * Quiz Model
 * Handles quiz-related database operations
 */

require_once __DIR__ . '/../config/db.php';

class QuizModel {
    /**
     * Get quiz by ID
     * @param int $id Quiz ID
     * @return array|null Quiz record or null
     */
    public static function getById($id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT q.*, u.name as owner_name FROM quizzes q LEFT JOIN users u ON q.owner_id = u.id WHERE q.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get quiz by access code
     * @param string $access_code Access code
     * @return array|null Quiz record or null
     */
    public static function getByAccessCode($access_code) {
        $db = get_db();
        $stmt = $db->prepare("SELECT q.*, u.name as owner_name FROM quizzes q LEFT JOIN users u ON q.owner_id = u.id WHERE q.access_code = ?");
        $stmt->bind_param("s", $access_code);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get all quizzes owned by a user
     * @param int $owner_id Owner user ID
     * @return array Array of quiz records
     */
    public static function getByOwner($owner_id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM quizzes WHERE owner_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $quizzes = [];
        while ($row = $result->fetch_assoc()) {
            $quizzes[] = $row;
        }
        return $quizzes;
    }
    
    /**
     * Get all active quizzes (for participants and hosts)
     * @param string|null $search Search term for title/description
     * @param string|null $difficulty Filter by difficulty
     * @param int $limit Limit number of results
     * @param int $offset Offset for pagination
     * @param string $sort_by Sort field (rating, plays, created_at, title)
     * @param string $sort_order Sort order (asc, desc)
     * @param int|null $owner_id Filter by owner ID (for "My Quizzes" filter)
     * @return array Array of quiz records with owner names
     */
    public static function getActiveQuizzes($search = null, $difficulty = null, $limit = 20, $offset = 0, $sort_by = 'created_at', $sort_order = 'desc', $owner_id = null) {
        $db = get_db();
        $where = ["q.is_active = 1"];
        $params = [];
        $types = "";
        
        if ($search) {
            $where[] = "(q.title LIKE ? OR q.description LIKE ?)";
            $search_term = "%$search%";
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= "ss";
        }
        
        if ($difficulty && in_array($difficulty, ['easy', 'medium', 'hard'])) {
            $where[] = "q.difficulty = ?";
            $params[] = $difficulty;
            $types .= "s";
        }
        
        if ($owner_id) {
            $where[] = "q.owner_id = ?";
            $params[] = $owner_id;
            $types .= "i";
        }
        
        // Validate sort_by and sort_order
        $valid_sort_fields = ['rating', 'plays', 'created_at', 'title'];
        $sort_by = in_array($sort_by, $valid_sort_fields) ? $sort_by : 'created_at';
        $sort_order = strtolower($sort_order) === 'asc' ? 'ASC' : 'DESC';
        
        // Build ORDER BY clause
        $order_by = "";
        if ($sort_by === 'rating') {
            $order_by = "COALESCE(q.average_rating, 0) $sort_order, q.created_at DESC";
        } elseif ($sort_by === 'plays') {
            $order_by = "play_count $sort_order, q.created_at DESC";
        } elseif ($sort_by === 'title') {
            $order_by = "q.title $sort_order";
        } else {
            $order_by = "q.created_at $sort_order";
        }
        
        $where_clause = implode(" AND ", $where);
        $sql = "SELECT q.*, u.name as owner_name,
                COUNT(DISTINCT a.id) as play_count
                FROM quizzes q 
                LEFT JOIN users u ON q.owner_id = u.id
                LEFT JOIN quiz_attempts a ON q.id = a.quiz_id AND a.completed_at IS NOT NULL
                WHERE $where_clause 
                GROUP BY q.id
                ORDER BY $order_by 
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $quizzes = [];
        while ($row = $result->fetch_assoc()) {
            $quizzes[] = $row;
        }
        return $quizzes;
    }
    
    /**
     * Get top 5 most played quizzes with stats
     * @return array Array of quiz records with play count and rating
     */
    public static function getTopMostPlayed($limit = 5) {
        $db = get_db();
        $sql = "SELECT q.*, u.name as owner_name,
                COUNT(DISTINCT a.id) as play_count,
                q.average_rating,
                (SELECT COUNT(*) FROM quiz_ratings WHERE quiz_id = q.id) as rating_count
                FROM quizzes q
                LEFT JOIN users u ON q.owner_id = u.id
                LEFT JOIN quiz_attempts a ON q.id = a.quiz_id AND a.completed_at IS NOT NULL
                WHERE q.is_active = 1
                GROUP BY q.id
                ORDER BY play_count DESC, q.average_rating DESC
                LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $quizzes = [];
        while ($row = $result->fetch_assoc()) {
            $quizzes[] = $row;
        }
        return $quizzes;
    }
    
    /**
     * Count active quizzes (for pagination)
     * @param string|null $search Search term
     * @param string|null $difficulty Filter by difficulty
     * @param int|null $owner_id Filter by owner ID
     * @return int Count of quizzes
     */
    public static function countActiveQuizzes($search = null, $difficulty = null, $owner_id = null) {
        $db = get_db();
        $where = ["q.is_active = 1"];
        $params = [];
        $types = "";
        
        if ($search) {
            $where[] = "(q.title LIKE ? OR q.description LIKE ?)";
            $search_term = "%$search%";
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= "ss";
        }
        
        if ($difficulty && in_array($difficulty, ['easy', 'medium', 'hard'])) {
            $where[] = "q.difficulty = ?";
            $params[] = $difficulty;
            $types .= "s";
        }
        
        if ($owner_id) {
            $where[] = "q.owner_id = ?";
            $params[] = $owner_id;
            $types .= "i";
        }
        
        $where_clause = implode(" AND ", $where);
        $sql = "SELECT COUNT(*) as count FROM quizzes q WHERE $where_clause";
        
        $stmt = $db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }
    
    /**
     * Create a new quiz
     * @param int $owner_id Owner user ID
     * @param string $title Quiz title
     * @param string|null $description Quiz description
     * @param string $access_code Access code
     * @param string $theme Theme name
     * @param string $difficulty Difficulty level
     * @param bool $is_collaborative Whether quiz allows participant contributions
     * @param bool $is_active Whether quiz is active
     * @return int|false New quiz ID or false on failure
     */
    public static function create($owner_id, $title, $description, $access_code, $theme, $difficulty, $is_collaborative, $is_active) {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO quizzes (owner_id, title, description, access_code, theme, difficulty, is_collaborative, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $is_collab = $is_collaborative ? 1 : 0;
        $is_act = $is_active ? 1 : 0;
        $stmt->bind_param("isssssii", $owner_id, $title, $description, $access_code, $theme, $difficulty, $is_collab, $is_act);
        
        if ($stmt->execute()) {
            return $db->insert_id;
        } else {
            error_log("Quiz creation failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Update quiz
     * @param int $id Quiz ID
     * @param string $title Quiz title
     * @param string|null $description Quiz description
     * @param string $theme Theme name
     * @param string $difficulty Difficulty level
     * @param bool $is_collaborative Whether quiz allows participant contributions
     * @param bool $is_active Whether quiz is active
     * @return bool Success status
     */
    public static function update($id, $title, $description, $theme, $difficulty, $is_collaborative, $is_active) {
        $db = get_db();
        $stmt = $db->prepare("UPDATE quizzes SET title = ?, description = ?, theme = ?, difficulty = ?, is_collaborative = ?, is_active = ? WHERE id = ?");
        $is_collab = $is_collaborative ? 1 : 0;
        $is_act = $is_active ? 1 : 0;
        $stmt->bind_param("ssssiii", $title, $description, $theme, $difficulty, $is_collab, $is_act, $id);
        
        if ($stmt->execute()) {
            return true;
        } else {
            error_log("Quiz update failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Delete quiz (cascade will handle related records)
     * @param int $id Quiz ID
     * @return bool Success status
     */
    public static function delete($id) {
        $db = get_db();
        $stmt = $db->prepare("DELETE FROM quizzes WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return true;
        } else {
            error_log("Quiz deletion failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Duplicate a quiz (clone with new access code)
     * @param int $quiz_id Original quiz ID
     * @param int $new_owner_id New owner ID (can be same as original)
     * @param string $new_access_code New access code
     * @return int|false New quiz ID or false on failure
     */
    public static function duplicate($quiz_id, $new_owner_id, $new_access_code) {
        $original = self::getById($quiz_id);
        if (!$original) {
            return false;
        }
        
        $new_quiz_id = self::create(
            $new_owner_id,
            $original['title'] . ' (Copy)',
            $original['description'],
            $new_access_code,
            $original['theme'],
            $original['difficulty'] ?? 'medium',
            $original['is_collaborative'],
            0 // New duplicate starts as inactive
        );
        
        if ($new_quiz_id) {
            // Duplicate questions
            require_once __DIR__ . '/QuestionModel.php';
            $questions = QuestionModel::getByQuiz($quiz_id);
            foreach ($questions as $question) {
                QuestionModel::create(
                    $new_quiz_id,
                    $question['created_by_user_id'],
                    $question['type'],
                    $question['question_text'],
                    $question['options_json'],
                    $question['correct_answer'],
                    $question['points'],
                    1 // Approved by default for duplicated questions
                );
            }
        }
        
        return $new_quiz_id;
    }
    
    /**
     * Check if user owns a quiz
     * @param int $quiz_id Quiz ID
     * @param int $user_id User ID
     * @return bool True if user owns the quiz
     */
    public static function isOwner($quiz_id, $user_id) {
        $quiz = self::getById($quiz_id);
        return $quiz && $quiz['owner_id'] == $user_id;
    }
}
