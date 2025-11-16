<?php
/**
 * Database Connection Configuration
 * Provides a singleton MySQLi connection to MySQL/MariaDB
 * Automatically creates database and tables if they don't exist
 */

// Database configuration - update these values for your environment
define('DB_HOST', 'localhost');
define('DB_NAME', 'lsquiz');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get a MySQLi database connection instance (singleton pattern)
 * Automatically creates database and tables if they don't exist
 * @return mysqli The database connection
 */
function get_db() {
    static $mysqli = null;
    
    if ($mysqli === null) {
        // First connect without selecting a database
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
        
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            die("Database connection failed. Please check your configuration.");
        }
        
        // Create database if it doesn't exist
        $conn->query("CREATE DATABASE IF NOT EXISTS " . $conn->real_escape_string(DB_NAME) . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Select the database
        $conn->select_db(DB_NAME);
        
        // Set charset
        $conn->set_charset(DB_CHARSET);
        
        $mysqli = $conn;
        
        // Create tables if they don't exist
        setup_database($mysqli);
    }
    
    return $mysqli;
}

/**
 * Setup database tables if they don't exist
 * @param mysqli $mysqli Database connection
 */
function setup_database($mysqli) {
    $schema = "
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('host', 'participant') NOT NULL DEFAULT 'participant',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_role (role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    
    CREATE TABLE IF NOT EXISTS quizzes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        owner_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        access_code VARCHAR(20) UNIQUE,
        theme VARCHAR(50) NOT NULL DEFAULT 'light',
        difficulty VARCHAR(20) NOT NULL DEFAULT 'medium',
        is_collaborative TINYINT(1) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_owner_id (owner_id),
        INDEX idx_is_active (is_active),
        INDEX idx_access_code (access_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    
    CREATE TABLE IF NOT EXISTS questions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        quiz_id INT NOT NULL,
        created_by_user_id INT NOT NULL,
        type ENUM('mcq', 'enum', 'identification') NOT NULL,
        question_text TEXT NOT NULL,
        options_json TEXT,
        correct_answer TEXT NOT NULL,
        points INT NOT NULL DEFAULT 1,
        is_approved TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_quiz_id (quiz_id),
        INDEX idx_is_approved (is_approved),
        INDEX idx_created_by_user_id (created_by_user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    
    CREATE TABLE IF NOT EXISTS quiz_attempts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        quiz_id INT NOT NULL,
        user_id INT NOT NULL,
        started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME,
        score INT,
        total_possible_points INT,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_quiz_id (quiz_id),
        INDEX idx_user_id (user_id),
        INDEX idx_completed_at (completed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    
    CREATE TABLE IF NOT EXISTS quiz_answers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        attempt_id INT NOT NULL,
        question_id INT NOT NULL,
        answer_text TEXT NOT NULL,
        is_correct TINYINT(1) NOT NULL,
        FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
        INDEX idx_attempt_id (attempt_id),
        INDEX idx_question_id (question_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    
    CREATE TABLE IF NOT EXISTS quiz_ratings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        quiz_id INT NOT NULL,
        user_id INT NOT NULL,
        attempt_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_quiz_attempt (user_id, quiz_id, attempt_id),
        INDEX idx_quiz_id (quiz_id),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Temporarily disable foreign key checks for initial setup
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Execute each statement separately (multi_query can be problematic with foreign keys)
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if (!$mysqli->query($statement)) {
                // Ignore errors for tables that already exist
                if (strpos($mysqli->error, 'already exists') === false && 
                    strpos($mysqli->error, 'Duplicate key name') === false) {
                    error_log("Database setup error: " . $mysqli->error);
                }
            }
        }
    }
    
    // Re-enable foreign key checks
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Add missing columns if they don't exist (for existing databases)
    add_missing_columns($mysqli);
}

/**
 * Add missing columns to existing tables (migration helper)
 * @param mysqli $mysqli Database connection
 */
function add_missing_columns($mysqli) {
    // Check if is_approved column exists in questions table
    $result = $mysqli->query("SHOW COLUMNS FROM questions LIKE 'is_approved'");
    if ($result->num_rows == 0) {
        $mysqli->query("ALTER TABLE questions ADD COLUMN is_approved TINYINT(1) NOT NULL DEFAULT 1 AFTER points");
        $mysqli->query("ALTER TABLE questions ADD INDEX idx_is_approved (is_approved)");
    }
    
    // Check if is_collaborative column exists in quizzes table
    $result = $mysqli->query("SHOW COLUMNS FROM quizzes LIKE 'is_collaborative'");
    if ($result->num_rows == 0) {
        $mysqli->query("ALTER TABLE quizzes ADD COLUMN is_collaborative TINYINT(1) NOT NULL DEFAULT 0 AFTER theme");
    }
    
    // Check if theme column exists in quizzes table
    $result = $mysqli->query("SHOW COLUMNS FROM quizzes LIKE 'theme'");
    if ($result->num_rows == 0) {
        $mysqli->query("ALTER TABLE quizzes ADD COLUMN theme VARCHAR(50) NOT NULL DEFAULT 'light' AFTER access_code");
    }
    
    // Check if difficulty column exists in quizzes table
    $result = $mysqli->query("SHOW COLUMNS FROM quizzes LIKE 'difficulty'");
    if ($result->num_rows == 0) {
        $mysqli->query("ALTER TABLE quizzes ADD COLUMN difficulty VARCHAR(20) NOT NULL DEFAULT 'medium' AFTER theme");
    }
    
    // Update question type ENUM to include 'identification' if needed
    $result = $mysqli->query("SHOW COLUMNS FROM questions WHERE Field = 'type'");
    if ($result && $row = $result->fetch_assoc()) {
        $type_definition = $row['Type'];
        if (strpos($type_definition, 'identification') === false) {
            $mysqli->query("ALTER TABLE questions MODIFY COLUMN type ENUM('mcq', 'enum', 'identification') NOT NULL");
        }
    }

    // Check if total_possible_points column exists in quiz_attempts table
    $result = $mysqli->query("SHOW COLUMNS FROM quiz_attempts LIKE 'total_possible_points'");
    if ($result->num_rows == 0) {
        $mysqli->query("ALTER TABLE quiz_attempts ADD COLUMN total_possible_points INT AFTER score");
    }
    
    // Check if average_rating column exists in quizzes table
    $result = $mysqli->query("SHOW COLUMNS FROM quizzes LIKE 'average_rating'");
    if ($result->num_rows == 0) {
        $mysqli->query("ALTER TABLE quizzes ADD COLUMN average_rating DECIMAL(3,2) DEFAULT NULL AFTER difficulty");
    }
}
