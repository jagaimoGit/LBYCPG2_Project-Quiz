<?php
/**
 * Play Quiz Page
 * Allows participants (and hosts) to take a quiz
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/QuizModel.php';
require_once __DIR__ . '/../models/QuestionModel.php';
require_once __DIR__ . '/../models/AttemptModel.php';

$page_title = 'Play Quiz - LSQuiz';
require_login();

$current_user = current_user();
$is_host = is_host();
$is_host_test = isset($_GET['host_test']) && $_GET['host_test'] == '1' && $is_host;
$quiz_id = $_GET['quiz_id'] ?? null;
$access_code = $_GET['access_code'] ?? null;

// Handle access code lookup
if ($access_code && !$quiz_id) {
    $quiz = QuizModel::getByAccessCode($access_code);
    if ($quiz) {
        $quiz_id = $quiz['id'];
    } else {
        set_flash('error', 'Quiz not found with that access code.');
        header('Location: index.php');
        exit;
    }
}

if (!$quiz_id) {
    set_flash('error', 'Quiz ID or access code required.');
    header('Location: index.php');
    exit;
}

$quiz = QuizModel::getById($quiz_id);

if (!$quiz) {
    set_flash('error', 'Quiz not found.');
    header('Location: index.php');
    exit;
}

if (!$quiz['is_active']) {
    set_flash('error', 'This quiz is not currently active.');
    header('Location: index.php');
    exit;
}

// For host test mode, don't create/save attempts
$attempt = null;
if (!$is_host_test) {
    // Check for incomplete attempt or create new one
    $attempt = AttemptModel::getIncompleteAttempt($quiz_id, $current_user['id']);
}

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $questions = QuestionModel::getByQuiz($quiz_id);
    $score = 0;
    $total_points = 0;
    $answers_data = [];
    
    foreach ($questions as $question) {
        $total_points += $question['points'];
        $answer_text = $_POST['answer_' . $question['id']] ?? '';
        $is_correct = false;
        
        if ($question['type'] === 'mcq') {
            // For MCQ, compare answer text directly
            $is_correct = (trim($answer_text) === trim($question['correct_answer']));
        } elseif ($question['type'] === 'enum' || $question['type'] === 'identification') {
            // For Enumeration and Identification, normalize and compare (case-insensitive)
            $normalized_answer = normalize_enum_answer($answer_text);
            $normalized_correct = normalize_enum_answer($question['correct_answer']);
            $is_correct = ($normalized_answer === $normalized_correct);
        } else {
            $is_correct = false;
        }
        
        if ($is_correct) {
            $score += $question['points'];
        }
        
        $answers_data[] = [
            'question' => $question,
            'answer_text' => $answer_text,
            'is_correct' => $is_correct
        ];
        
        // Only save to database if not host test mode
        if (!$is_host_test && $attempt) {
            AttemptModel::saveAnswer($attempt['id'], $question['id'], $answer_text, $is_correct);
        }
    }
    
    // For host test mode, show results without saving
    if ($is_host_test) {
        // Store results in session for display
        $_SESSION['host_test_results'] = [
            'quiz_id' => $quiz_id,
            'score' => $score,
            'total_points' => $total_points,
            'answers' => $answers_data,
            'quiz_title' => $quiz['title']
        ];
        header('Location: results_dashboard.php?host_test=1');
        exit;
    }
    
    // Complete the attempt (only for non-host-test)
    if ($attempt) {
        AttemptModel::complete($attempt['id'], $score);
        set_flash('success', 'Quiz submitted successfully!');
        header('Location: results_dashboard.php?attempt_id=' . $attempt['id'] . '&show_rating=1');
        exit;
    }
}

// Create new attempt if none exists (only for non-host-test)
if (!$is_host_test && !$attempt) {
    // Calculate total possible points at time of attempt
    $questions = QuestionModel::getByQuiz($quiz_id);
    $total_possible_points = 0;
    foreach ($questions as $question) {
        $total_possible_points += $question['points'];
    }
    
    $attempt_id = AttemptModel::create($quiz_id, $current_user['id'], $total_possible_points);
    if ($attempt_id) {
        $attempt = AttemptModel::getById($attempt_id);
    }
    
    if (!$attempt) {
        set_flash('error', 'Failed to start quiz attempt.');
        header('Location: index.php');
        exit;
    }
    
    // Check if already completed
    if ($attempt['completed_at']) {
        set_flash('info', 'You have already completed this quiz.');
        header('Location: results_dashboard.php?attempt_id=' . $attempt['id']);
        exit;
    }
}

// Load questions
$questions = QuestionModel::getByQuiz($quiz_id);
if (empty($questions)) {
    set_flash('error', 'This quiz has no questions yet.');
    header('Location: index.php');
    exit;
}

?>
<div class="quiz-wrapper">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1><?php echo e($quiz['title']); ?></h1>
                <?php if ($quiz['description']): ?>
                    <p><?php echo e($quiz['description']); ?></p>
                <?php endif; ?>
                <p><strong>Questions:</strong> <?php echo count($questions); ?></p>
            </div>
            
            <?php if ($is_host_test): ?>
                <div class="card" style="background: #FFD700; border: 4px solid #1a1a1a; margin-bottom: 1.5rem; padding: 1rem;">
                    <p style="margin: 0; font-weight: 700; color: #1a1a1a;">
                        ⚠️ <strong>Host Test Mode:</strong> Your score will not be saved.
                    </p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" onsubmit="return confirm('Submit your answers? You cannot change them after submission.');">
                <input type="hidden" name="submit_quiz" value="1">
                <?php if ($is_host_test): ?>
                    <input type="hidden" name="host_test" value="1">
                <?php endif; ?>
                
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-item">
                        <div class="question-text">
                            <strong>Question <?php echo $index + 1; ?>:</strong> <?php echo e($question['question_text']); ?>
                            <span style="color: #666; font-size: 0.9rem;">(<?php echo $question['points']; ?> point<?php echo $question['points'] != 1 ? 's' : ''; ?>)</span>
                        </div>
                        
                        <?php if ($question['type'] === 'mcq'): 
                            $options = json_decode($question['options_json'], true);
                            if (is_array($options)):
                        ?>
                            <div class="question-options">
                                <?php foreach ($options as $opt_index => $option): ?>
                                    <div class="question-option">
                                        <label>
                                            <input type="radio" name="answer_<?php echo $question['id']; ?>" value="<?php echo e($option); ?>" required>
                                            <?php echo e($option); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php 
                            endif; // end inner if (is_array($options))
                        else: // else for outer if ($question['type'] === 'mcq')
                        ?>
                            <div class="form-group" style="margin-top: 1rem;">
                                <input type="text" name="answer_<?php echo $question['id']; ?>" placeholder="Enter your answer" required style="max-width: 500px;">
                            </div>
                        <?php endif; // end outer if ($question['type'] === 'mcq') ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="form-group" style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary btn-large" style="font-size: 1.2rem; padding: 1rem 2rem;">Submit Quiz</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
