<?php
/**
 * Import Quiz Page
 * Host-only page for importing quizzes from JSON or CSV files
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/QuizModel.php';
require_once __DIR__ . '/../models/QuestionModel.php';

$page_title = 'Import Quiz - LSQuiz';
require_host();

$current_user = current_user();
$errors = [];
$success = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['quiz_file'])) {
    if ($_FILES['quiz_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['quiz_file']['name'];
        $file_tmp = $_FILES['quiz_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Determine file type and process accordingly
        if ($file_ext === 'json') {
            // JSON import
            $file_content = file_get_contents($file_tmp);
            $data = json_decode($file_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Invalid JSON file: ' . json_last_error_msg();
            } elseif (!isset($data['title'])) {
                $errors[] = 'JSON file must contain a "title" field.';
            } else {
                $title = $data['title'];
                $description = $data['description'] ?? null;
                $theme = 'light'; // Only light theme available
                $difficulty = $data['difficulty'] ?? 'medium';
                if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
                    $difficulty = 'medium';
                }
                $is_collaborative = $data['is_collaborative'] ?? false;
                $is_active = false;
                $access_code = generate_access_code();
                
                $quiz_id = QuizModel::create($current_user['id'], $title, $description, $access_code, $theme, $difficulty, $is_collaborative, $is_active);
                
                if ($quiz_id && isset($data['questions']) && is_array($data['questions'])) {
                    $imported_count = 0;
                    foreach ($data['questions'] as $q) {
                        if (!isset($q['question_text']) || !isset($q['type']) || !isset($q['correct_answer'])) {
                            continue;
                        }
                        
                        $type = $q['type'];
                        $question_text = $q['question_text'];
                        $correct_answer = $q['correct_answer'];
                        $points = $q['points'] ?? 1;
                        $options_json = null;
                        
                        if ($type === 'mcq' && isset($q['options']) && is_array($q['options'])) {
                            $options_json = json_encode($q['options']);
                        }
                        
                        if (QuestionModel::create($quiz_id, $current_user['id'], $type, $question_text, $options_json, $correct_answer, $points, true)) {
                            $imported_count++;
                        }
                    }
                    
                    set_flash('success', "Quiz imported successfully! {$imported_count} questions imported.");
                    header('Location: create_quiz.php?quiz_id=' . $quiz_id);
                    exit;
                } elseif ($quiz_id) {
                    set_flash('success', 'Quiz imported successfully! (No questions found in file.)');
                    header('Location: create_quiz.php?quiz_id=' . $quiz_id);
                    exit;
                } else {
                    $errors[] = 'Failed to create quiz.';
                }
            }
        } elseif ($file_ext === 'csv') {
            // CSV import
            $handle = fopen($file_tmp, 'r');
            if ($handle === false) {
                $errors[] = 'Could not read CSV file.';
            } else {
                // Read first row for quiz metadata
                $header = fgetcsv($handle);
                if ($header === false || count($header) < 2) {
                    $errors[] = 'Invalid CSV format. First row must contain quiz metadata.';
                } else {
                    // Parse quiz metadata from first row
                    // Format: QUIZ_TITLE, QUIZ_DESCRIPTION, DIFFICULTY, IS_COLLABORATIVE
                    $title = trim($header[0]);
                    $description = isset($header[1]) ? trim($header[1]) : '';
                    $theme = 'light'; // Only light theme available
                    $difficulty = isset($header[2]) ? strtolower(trim($header[2])) : 'medium';
                    if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
                        $difficulty = 'medium';
                    }
                    $is_collaborative = isset($header[3]) && strtolower(trim($header[3])) === 'true';
                    
                    if (empty($title)) {
                        $errors[] = 'Quiz title is required in first row.';
                    } else {
                        $is_active = false;
                        $access_code = generate_access_code();
                        $quiz_id = QuizModel::create($current_user['id'], $title, $description, $access_code, $theme, $difficulty, $is_collaborative, $is_active);
                        
                        if ($quiz_id) {
                            // Read question headers (second row)
                            $question_headers = fgetcsv($handle);
                            if ($question_headers === false) {
                                $errors[] = 'CSV must have question headers in second row.';
                            } else {
                                // Expected headers: type, question_text, option1, option2, option3, option4, correct_answer, points
                                $imported_count = 0;
                                
                                // Read questions (starting from row 3)
                                while (($row = fgetcsv($handle)) !== false) {
                                    if (count($row) < 3) continue; // Skip incomplete rows
                                    
                                    $type = strtolower(trim($row[0] ?? ''));
                                    $question_text = trim($row[1] ?? '');
                                    
                                    if (empty($question_text) || !in_array($type, ['mcq', 'enum'])) {
                                        continue;
                                    }
                                    
                                    $options_json = null;
                                    $correct_answer = '';
                                    $points = 1;
                                    
                                    if ($type === 'mcq') {
                                        // For MCQ: option1, option2, option3, option4, correct_answer, points
                                        $options = [];
                                        for ($i = 2; $i <= 5; $i++) {
                                            $opt = trim($row[$i] ?? '');
                                            if (!empty($opt)) {
                                                $options[] = $opt;
                                            }
                                        }
                                        
                                        if (count($options) < 2) {
                                            continue; // Skip if less than 2 options
                                        }
                                        
                                        $correct_answer = trim($row[6] ?? '');
                                        $points = isset($row[7]) ? (int)trim($row[7]) : 1;
                                        
                                        if (empty($correct_answer) || !in_array($correct_answer, $options)) {
                                            continue; // Skip if correct answer not in options
                                        }
                                        
                                        $options_json = json_encode($options);
                                    } else {
                                        // For Enum: correct_answer is in column 6 (same as MCQ), points in column 7
                                        $correct_answer = trim($row[6] ?? '');
                                        $points = isset($row[7]) ? (int)trim($row[7]) : 1;
                                        
                                        if (empty($correct_answer)) {
                                            continue;
                                        }
                                    }
                                    
                                    if (QuestionModel::create($quiz_id, $current_user['id'], $type, $question_text, $options_json, $correct_answer, $points, true)) {
                                        $imported_count++;
                                    }
                                }
                                
                                fclose($handle);
                                
                                set_flash('success', "Quiz imported successfully! {$imported_count} questions imported.");
                                header('Location: create_quiz.php?quiz_id=' . $quiz_id);
                                exit;
                            }
                        } else {
                            $errors[] = 'Failed to create quiz.';
                        }
                    }
                }
                if (is_resource($handle)) {
                    fclose($handle);
                }
            }
        } else {
            $errors[] = 'Unsupported file format. Please upload a JSON or CSV file.';
        }
    } else {
        $errors[] = 'File upload error.';
    }
}
?>
<div class="container">
    <h1>Import Quiz</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="flash flash-error">
            <ul style="margin: 0; padding-left: 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="card" style="max-width: 800px; margin: 2rem auto;">
        <div class="card-header">
            <h2>Upload Quiz File</h2>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="quiz_file">Select JSON or CSV File *</label>
                <input type="file" id="quiz_file" name="quiz_file" accept=".json,.csv,application/json,text/csv" required>
                <small style="color: #666;">Upload a JSON file exported from LSQuiz or a CSV file in the correct format.</small>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Import Quiz</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        
        <div style="margin-top: 2rem;">
            <h3>JSON Format:</h3>
            <div style="padding: 1rem; background-color: #f8f9fa; border-radius: 4px; margin-bottom: 2rem;">
                <pre style="background-color: white; padding: 1rem; border-radius: 4px; overflow-x: auto; margin: 0;">{
  "title": "Quiz Title",
  "description": "Quiz Description",
  "difficulty": "medium",
  "is_collaborative": false,
  "questions": [
    {
      "type": "mcq",
      "question_text": "Question?",
      "options": ["Option A", "Option B", "Option C", "Option D"],
      "correct_answer": "Option A",
      "points": 1
    },
    {
      "type": "enum",
      "question_text": "Question?",
      "correct_answer": "Answer",
      "points": 1
    }
  ]
}</pre>
            </div>
            
            <h3>CSV Format:</h3>
            <div style="padding: 1rem; background-color: #f8f9fa; border-radius: 4px;">
                <p><strong>Row 1 (Quiz Metadata):</strong> QUIZ_TITLE, QUIZ_DESCRIPTION, DIFFICULTY, IS_COLLABORATIVE</p>
                <p><strong>Row 2 (Question Headers):</strong> type, question_text, option1, option2, option3, option4, correct_answer, points</p>
                <p><strong>Row 3+ (Questions):</strong> One question per row</p>
                
                <h4 style="margin-top: 1rem;">CSV Example:</h4>
                <pre style="background-color: white; padding: 1rem; border-radius: 4px; overflow-x: auto; margin: 0;">General Knowledge Quiz,Test your knowledge,medium,false
type,question_text,option1,option2,option3,option4,correct_answer,points
mcq,What is the capital of France?,Paris,London,Berlin,Madrid,Paris,1
mcq,What is 2+2?,3,4,5,6,4,1
enum,What is the largest planet in our solar system?,Jupiter,1</pre>
                
                <p style="margin-top: 1rem;"><strong>Notes:</strong></p>
                <ul style="margin-left: 1.5rem;">
                    <li>For MCQ: Fill option1-option4, correct_answer must match one of the options</li>
                    <li>For Enum: Leave option1-option4 empty, put correct_answer in column 7</li>
                    <li>Points column is optional (defaults to 1)</li>
                    <li>DIFFICULTY should be: easy, medium, or hard</li>
                    <li>IS_COLLABORATIVE should be: true or false</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
