<?php
/**
 * Results Dashboard Page
 * Shows results for participants (by attempt) or hosts (aggregated by quiz)
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/QuizModel.php';
require_once __DIR__ . '/../models/AttemptModel.php';
require_once __DIR__ . '/../models/QuestionModel.php';
require_once __DIR__ . '/../models/RatingModel.php';

require_login();

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $attempt_id = (int)($_POST['attempt_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    
    if ($attempt_id && $rating >= 1 && $rating <= 5) {
        $attempt = AttemptModel::getById($attempt_id);
        if ($attempt && AttemptModel::belongsToUser($attempt_id, $current_user['id'])) {
            RatingModel::create($attempt['quiz_id'], $current_user['id'], $attempt_id, $rating);
            set_flash('success', 'Thank you for rating this quiz!');
        }
    }
    
    header('Location: results_dashboard.php?attempt_id=' . $attempt_id);
    exit;
}

$current_user = current_user();
$attempt_id = $_GET['attempt_id'] ?? null;
$quiz_id = $_GET['quiz_id'] ?? null;
$show_rating = isset($_GET['show_rating']) && $_GET['show_rating'] == '1';
$is_host_test = isset($_GET['host_test']) && $_GET['host_test'] == '1' && is_host();

// Host test results view (no saved attempt)
if ($is_host_test && isset($_SESSION['host_test_results'])) {
    $test_results = $_SESSION['host_test_results'];
    unset($_SESSION['host_test_results']);
    $quiz = QuizModel::getById($test_results['quiz_id']);
    
    $page_title = 'Test Results - ' . e($quiz['title']);
    ?>
    <div class="quiz-wrapper">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h1>Test Results (Not Saved)</h1>
                    <h2><?php echo e($quiz['title']); ?></h2>
                </div>
                
                <div class="card" style="background: #FFD700; border: 4px solid #1a1a1a; margin-bottom: 2rem; padding: 1rem;">
                    <p style="margin: 0; font-weight: 700; color: #1a1a1a; font-size: 1.1rem;">
                        ⚠️ This was a test run. Your score was not saved and will not appear in quiz statistics.
                    </p>
                </div>
                
                <div style="text-align: center; padding: 2rem; background-color: #f8f9fa; border-radius: 8px; margin-bottom: 2rem;">
                    <h2 style="font-size: 3rem; margin-bottom: 0.5rem;"><?php echo $test_results['score']; ?> / <?php echo $test_results['total_points']; ?></h2>
                    <p style="font-size: 1.2rem;"><?php echo round(($test_results['score'] / max($test_results['total_points'], 1)) * 100, 1); ?>%</p>
                </div>
                
                <h3>Question Breakdown</h3>
                <?php foreach ($test_results['answers'] as $index => $answer_data): ?>
                    <div class="question-item" style="border-left: 4px solid <?php echo $answer_data['is_correct'] ? '#28a745' : '#dc3545'; ?>;">
                        <div class="question-text">
                            <strong>Question <?php echo $index + 1; ?>:</strong> <?php echo e($answer_data['question']['question_text']); ?>
                            <span style="color: <?php echo $answer_data['is_correct'] ? 'green' : 'red'; ?>; font-weight: bold;">
                                <?php echo $answer_data['is_correct'] ? '✓ Correct' : '✗ Incorrect'; ?>
                            </span>
                        </div>
                        <p><strong>Your Answer:</strong> <?php echo e($answer_data['answer_text']); ?></p>
                        <p><strong>Correct Answer:</strong> <?php echo e($answer_data['question']['correct_answer']); ?></p>
                        <p><strong>Points:</strong> <?php echo $answer_data['question']['points']; ?> (<?php echo $answer_data['is_correct'] ? 'Earned' : 'Not earned'; ?>)</p>
                    </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 2rem;">
                    <a href="browse_quizzes.php" class="btn btn-primary">Back to Browse</a>
                    <a href="index.php" class="btn btn-secondary">Back to Main</a>
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Participant view: show attempt details
if ($attempt_id) {
    $attempt = AttemptModel::getById($attempt_id);
    
    if (!$attempt) {
        set_flash('error', 'Attempt not found.');
        header('Location: index.php');
        exit;
    }
    
    // Check access: user must either own the attempt OR be the host/owner of the quiz
    $can_view = false;
    if (AttemptModel::belongsToUser($attempt_id, $current_user['id'])) {
        // User owns the attempt
        $can_view = true;
    } elseif (is_host()) {
        // Host can view if they own the quiz
        $quiz = QuizModel::getById($attempt['quiz_id']);
        if ($quiz && QuizModel::isOwner($attempt['quiz_id'], $current_user['id'])) {
            $can_view = true;
        }
    }
    
    if (!$can_view) {
        set_flash('error', 'Access denied.');
        header('Location: index.php');
        exit;
    }
    
    $quiz = QuizModel::getById($attempt['quiz_id']);
    $answers = AttemptModel::getAnswersByAttempt($attempt_id);
    
    // Use stored total_possible_points if available, otherwise calculate from answers (for backward compatibility)
    $total_points = $attempt['total_possible_points'] ?? 0;
    if ($total_points == 0) {
        foreach ($answers as $answer) {
            $total_points += $answer['points'];
        }
    }
    
    $page_title = 'Quiz Results - ' . e($quiz['title']);
    ?>
    <div class="quiz-wrapper">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h1>Quiz Results</h1>
                    <h2><?php echo e($quiz['title']); ?></h2>
                </div>
                
                <div style="text-align: center; padding: 2rem; background-color: #f8f9fa; border-radius: 8px; margin-bottom: 2rem;">
                    <h2 style="font-size: 3rem; margin-bottom: 0.5rem;"><?php echo $attempt['score']; ?> / <?php echo $total_points; ?></h2>
                    <p style="font-size: 1.2rem;"><?php echo round(($attempt['score'] / max($total_points, 1)) * 100, 1); ?>%</p>
                    <p>Completed: <?php echo date('Y-m-d H:i', strtotime($attempt['completed_at'])); ?></p>
                </div>
                
                <h3>Question Breakdown</h3>
                <?php foreach ($answers as $index => $answer): ?>
                    <div class="question-item" style="border-left: 4px solid <?php echo $answer['is_correct'] ? '#28a745' : '#dc3545'; ?>;">
                        <div class="question-text">
                            <strong>Question <?php echo $index + 1; ?>:</strong> <?php echo e($answer['question_text']); ?>
                            <span style="color: <?php echo $answer['is_correct'] ? 'green' : 'red'; ?>; font-weight: bold;">
                                <?php echo $answer['is_correct'] ? '✓ Correct' : '✗ Incorrect'; ?>
                            </span>
                        </div>
                        <p><strong>Your Answer:</strong> <?php echo e($answer['answer_text']); ?></p>
                        <p><strong>Correct Answer:</strong> <?php echo e($answer['correct_answer']); ?></p>
                        <p><strong>Points:</strong> <?php echo $answer['points']; ?> (<?php echo $answer['is_correct'] ? 'Earned' : 'Not earned'; ?>)</p>
                    </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 2rem;">
                    <a href="index.php" class="btn btn-primary">Back to Main</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Rating Modal -->
    <?php if ($show_rating && !RatingModel::getByAttempt($attempt_id)): ?>
        <div id="ratingModal" class="modal" style="display: flex !important;">
            <div class="modal-overlay" onclick="closeRatingModal()"></div>
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h2>Like the Quiz?</h2>
                    <button type="button" class="modal-close" onclick="closeRatingModal()">&times;</button>
                </div>
                <form method="POST" action="" id="ratingForm">
                    <input type="hidden" name="attempt_id" value="<?php echo $attempt_id; ?>">
                    <div style="padding: 2rem; text-align: center;">
                        <p style="font-size: 1.2rem; margin-bottom: 1.5rem; font-weight: 700;">Rate this quiz:</p>
                        <div class="star-rating" style="display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label style="cursor: pointer; font-size: 2.5rem; color: #ddd; transition: all 0.2s;">
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" required style="display: none;">
                                    <span class="star" data-rating="<?php echo $i; ?>">★</span>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <div style="display: flex; gap: 1rem; justify-content: center;">
                            <button type="submit" name="submit_rating" class="btn btn-primary">Submit Rating</button>
                            <button type="button" onclick="closeRatingModal()" class="btn btn-secondary">Skip</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            const ratingInputs = document.querySelectorAll('input[name="rating"]');
            
            stars.forEach(star => {
                star.addEventListener('mouseenter', function() {
                    const rating = parseInt(this.dataset.rating);
                    highlightStars(rating);
                });
                
                star.addEventListener('click', function() {
                    const rating = parseInt(this.dataset.rating);
                    document.querySelector('input[name="rating"][value="' + rating + '"]').checked = true;
                    highlightStars(rating);
                });
            });
            
            document.querySelector('.star-rating').addEventListener('mouseleave', function() {
                const selected = document.querySelector('input[name="rating"]:checked');
                if (selected) {
                    highlightStars(parseInt(selected.value));
                } else {
                    highlightStars(0);
                }
            });
            
            function highlightStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.style.color = '#FFD700';
                        star.style.transform = 'scale(1.2)';
                    } else {
                        star.style.color = '#ddd';
                        star.style.transform = 'scale(1)';
                    }
                });
            }
            
            document.getElementById('ratingForm').addEventListener('submit', function(e) {
                const selected = document.querySelector('input[name="rating"]:checked');
                if (!selected) {
                    e.preventDefault();
                    alert('Please select a rating');
                }
            });
        });
        
        function closeRatingModal() {
            document.getElementById('ratingModal').style.display = 'none';
            // Remove show_rating from URL
            window.history.replaceState({}, document.title, window.location.pathname + '?attempt_id=<?php echo $attempt_id; ?>');
        }
        </script>
    <?php endif; ?>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Host view: show aggregated statistics
if ($quiz_id) {
    require_host();
    
    $quiz = QuizModel::getById($quiz_id);
    
    if (!$quiz || !QuizModel::isOwner($quiz_id, $current_user['id'])) {
        set_flash('error', 'Quiz not found or access denied.');
        header('Location: index.php');
        exit;
    }
    
    $stats = AttemptModel::getQuizStatistics($quiz_id);
    $attempts = AttemptModel::getByQuiz($quiz_id);
    $question_stats = AttemptModel::getQuestionStatistics($quiz_id);
    $quiz_rating = RatingModel::getAverageRating($quiz_id);
    $rating_count = RatingModel::getRatingCount($quiz_id);
    
    // Calculate percentage statistics using stored total_possible_points
    $avg_percentage = 0;
    $max_percentage = 0;
    $min_percentage = 0;
    $total_attempts_with_points = 0;
    $total_points_sum = 0;
    $total_possible_sum = 0;
    
    foreach ($attempts as $attempt) {
        $total_possible = $attempt['total_possible_points'] ?? 0;
        if ($total_possible == 0) {
            // Fallback: calculate from current questions
            $questions = QuestionModel::getByQuiz($quiz_id);
            foreach ($questions as $q) {
                $total_possible += $q['points'];
            }
        }
        
        if ($total_possible > 0) {
            $percentage = ($attempt['score'] / $total_possible) * 100;
            $total_points_sum += $attempt['score'];
            $total_possible_sum += $total_possible;
            $total_attempts_with_points++;
            
            if ($total_attempts_with_points == 1) {
                $max_percentage = $percentage;
                $min_percentage = $percentage;
            } else {
                if ($percentage > $max_percentage) {
                    $max_percentage = $percentage;
                }
                if ($percentage < $min_percentage) {
                    $min_percentage = $percentage;
                }
            }
        }
    }
    
    if ($total_attempts_with_points > 0) {
        $avg_percentage = round(($total_points_sum / $total_possible_sum) * 100, 1);
        $max_percentage = round($max_percentage, 1);
        $min_percentage = round($min_percentage, 1);
    }
    
    $page_title = 'Results Dashboard - ' . e($quiz['title']);
    ?>
    <div class="container">
        <h1>Results Dashboard</h1>
        <h2><?php echo e($quiz['title']); ?></h2>
        
        <div class="card">
            <div class="card-header">
                <h2>Overall Statistics</h2>
            </div>
            <div class="grid grid-4">
                <div class="stat-item">
                    <h3 style="font-size: 2rem; margin-bottom: 0.5rem; color: #00D9FF;"><?php echo $stats['attempt_count']; ?></h3>
                    <p style="margin: 0; font-weight: 700;"><strong>Total Attempts</strong></p>
                </div>
                <div class="stat-item">
                    <h3 style="font-size: 2rem; margin-bottom: 0.5rem; color: #00FF88;"><?php echo $avg_percentage; ?>%</h3>
                    <p style="margin: 0; font-weight: 700;"><strong>Average Score</strong></p>
                </div>
                <div class="stat-item">
                    <h3 style="font-size: 2rem; margin-bottom: 0.5rem; color: #FF3366;"><?php echo $max_percentage; ?>% / <?php echo $min_percentage; ?>%</h3>
                    <p style="margin: 0; font-weight: 700;"><strong>Highest / Lowest</strong></p>
                </div>
                <div class="stat-item">
                    <?php if ($quiz_rating): ?>
                        <h3 style="font-size: 2rem; margin-bottom: 0.5rem; color: #FFD700;">
                            <span style="font-size: 1.5rem;"><?php echo str_repeat('★', round($quiz_rating)); ?></span>
                            <?php echo number_format($quiz_rating, 1); ?>
                        </h3>
                        <p style="margin: 0; font-weight: 700;"><strong>Average Rating</strong></p>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #666;">(<?php echo $rating_count; ?> rating<?php echo $rating_count != 1 ? 's' : ''; ?>)</p>
                    <?php else: ?>
                        <h3 style="font-size: 2rem; margin-bottom: 0.5rem; color: #999;">—</h3>
                        <p style="margin: 0; font-weight: 700;"><strong>Average Rating</strong></p>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #666;">No ratings yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($attempts)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h2>Participant Results</h2>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Participant</th>
                            <th>Email</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Rating</th>
                            <th>Completed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attempts as $attempt): 
                            // Use stored total_possible_points if available, otherwise calculate
                            $total_possible = $attempt['total_possible_points'] ?? 0;
                            if ($total_possible == 0) {
                                $questions = QuestionModel::getByQuiz($attempt['quiz_id']);
                                foreach ($questions as $q) {
                                    $total_possible += $q['points'];
                                }
                            }
                            $percentage = $total_possible > 0 ? round(($attempt['score'] / $total_possible) * 100, 1) : 0;
                            $attempt_rating = RatingModel::getByAttempt($attempt['id']);
                        ?>
                            <tr>
                                <td><?php echo e($attempt['user_name']); ?></td>
                                <td><?php echo e($attempt['user_email']); ?></td>
                                <td><strong><?php echo $attempt['score']; ?> / <?php echo $total_possible; ?></strong></td>
                                <td><strong><?php echo $percentage; ?>%</strong></td>
                                <td>
                                    <?php if ($attempt_rating): ?>
                                        <span style="color: #FFD700; font-size: 1.1rem;"><?php echo str_repeat('★', $attempt_rating['rating']); ?></span>
                                        <span style="color: #666; font-size: 0.9rem;">(<?php echo $attempt_rating['rating']; ?>/5)</span>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 0.9rem;">Not rated</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($attempt['completed_at'])); ?></td>
                                <td>
                                    <a href="results_dashboard.php?attempt_id=<?php echo $attempt['id']; ?>" class="btn btn-small btn-primary">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($question_stats)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h2>Question Statistics</h2>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Total Answers</th>
                            <th>Correct</th>
                            <th>Correct %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($question_stats as $q_stat): ?>
                            <tr>
                                <td><?php echo e($q_stat['question_text']); ?></td>
                                <td><?php echo $q_stat['total_answers']; ?></td>
                                <td><?php echo $q_stat['correct_answers']; ?></td>
                                <td><?php echo $q_stat['correct_percentage']; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 2rem;">
            <a href="index.php" class="btn btn-secondary">Back to Main</a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// No attempt_id or quiz_id provided
set_flash('error', 'Invalid request.');
header('Location: index.php');
exit;
