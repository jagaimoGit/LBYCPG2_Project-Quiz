<?php
/**
 * Profile Page
 * Allows users to view and edit their profile, see lifetime stats and quiz history
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/AttemptModel.php';
require_once __DIR__ . '/../models/QuizModel.php';
require_once __DIR__ . '/../models/QuestionModel.php';

$page_title = 'Profile - LSQuiz';
require_login();

$current_user = current_user();
$errors = [];
$success = '';
$is_host = is_host();

if ($is_host) {
    // Host Statistics
    $host_quizzes = QuizModel::getByOwner($current_user['id']);
    $total_quizzes = count($host_quizzes);
    
    // Calculate average quiz score across all quizzes
    $total_quiz_score_percentage = 0;
    $quizzes_with_attempts = 0;
    
    foreach ($host_quizzes as $quiz) {
        $stats = AttemptModel::getQuizStatistics($quiz['id']);
        if ($stats['attempt_count'] > 0) {
            // Get total possible points for this quiz
            $total_possible = 0;
            $questions = QuestionModel::getByQuiz($quiz['id']);
            foreach ($questions as $q) {
                $total_possible += $q['points'];
            }
            
            if ($total_possible > 0) {
                // Calculate average percentage for this quiz
                $avg_percentage = ($stats['avg_score'] / $total_possible) * 100;
                $total_quiz_score_percentage += $avg_percentage;
                $quizzes_with_attempts++;
            }
        }
    }
    
    $avg_quiz_score = $quizzes_with_attempts > 0 ? round($total_quiz_score_percentage / $quizzes_with_attempts, 1) : 0;
    } else {
        // Participant Statistics
        $all_attempts = AttemptModel::getByUser($current_user['id']);
        $total_attempts = count($all_attempts);
        $quizzes_taken = [];
        $total_percentage = 0;
        $attempts_with_scores = 0;
        $total_points_earned = 0;
        $total_possible_all = 0;
        $highest_score = 0;
        $lowest_score = PHP_INT_MAX;
        
        foreach ($all_attempts as $attempt) {
            if ($attempt['score'] !== null && $attempt['completed_at']) {
                // Use stored total_possible_points if available, otherwise calculate
                $total_possible = $attempt['total_possible_points'] ?? 0;
                if ($total_possible == 0) {
                    $questions = QuestionModel::getByQuiz($attempt['quiz_id']);
                    foreach ($questions as $q) {
                        $total_possible += $q['points'];
                    }
                }
                
                if ($total_possible > 0) {
                    $percentage = ($attempt['score'] / $total_possible) * 100;
                    $total_percentage += $percentage;
                    $attempts_with_scores++;
                    $total_points_earned += $attempt['score'];
                    $total_possible_all += $total_possible;
                    
                    if ($attempt['score'] > $highest_score) {
                        $highest_score = $attempt['score'];
                    }
                    if ($attempt['score'] < $lowest_score) {
                        $lowest_score = $attempt['score'];
                    }
                }
                
                if (!in_array($attempt['quiz_id'], $quizzes_taken)) {
                    $quizzes_taken[] = $attempt['quiz_id'];
                }
            }
        }
        
        $avg_percentage = $attempts_with_scores > 0 ? round($total_percentage / $attempts_with_scores, 1) : 0;
        $unique_quizzes = count($quizzes_taken);
        $lowest_score = $lowest_score === PHP_INT_MAX ? 0 : $lowest_score;
    }

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!require_field($name)) {
        $errors[] = 'Name is required.';
    }
    
    $update_password = !empty($new_password);
    
    if ($update_password) {
        if (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        } elseif (!password_verify($current_password, $current_user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }
    }
    
    if (empty($errors)) {
        $password_hash = null;
        if ($update_password) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        }
        
        if (UserModel::update($current_user['id'], $name, $password_hash)) {
            $success = 'Profile updated successfully!';
            // Reload user data
            $current_user = UserModel::getById($current_user['id']);
            $_SESSION['user_role'] = $current_user['role'];
        } else {
            $errors[] = 'Failed to update profile.';
        }
    }
}
?>
<div class="container">
    <h1>My Profile</h1>
    
    <!-- Lifetime Statistics -->
    <div class="card">
        <div class="card-header">
            <h2>Lifetime Statistics</h2>
        </div>
        <?php if ($is_host): ?>
            <!-- Host Statistics -->
            <?php
            require_once __DIR__ . '/../models/RatingModel.php';
            // Calculate average rating across all quizzes
            $host_quizzes = QuizModel::getByOwner($current_user['id']);
            $total_ratings = 0;
            $rating_sum = 0;
            foreach ($host_quizzes as $hq) {
                $quiz_rating = RatingModel::getAverageRating($hq['id']);
                $rating_count = RatingModel::getRatingCount($hq['id']);
                if ($quiz_rating) {
                    $rating_sum += $quiz_rating * $rating_count;
                    $total_ratings += $rating_count;
                }
            }
            $avg_quiz_rating = $total_ratings > 0 ? round($rating_sum / $total_ratings, 2) : null;
            ?>
            <div class="grid grid-3">
                <div class="stat-item">
                    <h3 style="font-size: 2rem; margin-bottom: 0.5rem; color: #00D9FF;"><?php echo $total_quizzes; ?></h3>
                    <p style="margin: 0; font-weight: 700;"><strong>Quizzes Created</strong></p>
                </div>
                <div class="stat-item">
                    <h3 style="font-size: 2rem; margin-bottom: 0.5rem; color: #00FF88;"><?php echo $avg_quiz_score; ?>%</h3>
                    <p style="margin: 0; font-weight: 700;"><strong>Average Quiz Score</strong></p>
                </div>
                <div class="stat-item">
                    <?php if ($avg_quiz_rating): ?>
                        <h3 style="font-size: 2rem; margin-bottom: 0.5rem; color: #FFD700;">
                            <span style="font-size: 1.5rem;"><?php echo str_repeat('★', round($avg_quiz_rating)); ?></span>
                            <?php echo number_format($avg_quiz_rating, 1); ?>
                        </h3>
                        <p style="margin: 0; font-weight: 700;"><strong>Average Quiz Rating</strong></p>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #666;">(<?php echo $total_ratings; ?> rating<?php echo $total_ratings != 1 ? 's' : ''; ?>)</p>
                    <?php else: ?>
                        <h3 style="font-size: 2rem; margin-bottom: 0.5rem; color: #999;">—</h3>
                        <p style="margin: 0; font-weight: 700;"><strong>Average Quiz Rating</strong></p>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #666;">No ratings yet</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Participant Statistics -->
            <div class="grid grid-3">
                <div class="stat-item">
                    <h3 style="font-size: 2rem; margin-bottom: 0.5rem; color: #00D9FF;"><?php echo $total_attempts; ?></h3>
                    <p style="margin: 0; font-weight: 700;"><strong>Total Attempts</strong></p>
                </div>
                <div class="stat-item">
                    <h3 style="font-size: 2rem; margin-bottom: 0.5rem; color: #00FF88;"><?php echo $unique_quizzes; ?></h3>
                    <p style="margin: 0; font-weight: 700;"><strong>Unique Quizzes</strong></p>
                </div>
                <div class="stat-item">
                    <h3 style="font-size: 2rem; margin-bottom: 0.5rem; color: #FF3366;"><?php echo $avg_percentage; ?>%</h3>
                    <p style="margin: 0; font-weight: 700;"><strong>Average Score</strong></p>
                </div>
                <?php if ($attempts_with_scores > 0): ?>
                    <div class="stat-item">
                        <h3 style="font-size: 1.75rem; margin-bottom: 0.5rem; color: #00D9FF;"><?php echo $total_points_earned; ?></h3>
                        <p style="margin: 0; font-weight: 700;"><strong>Total Points Earned</strong></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quiz History / Quiz List -->
    <?php if ($is_host): ?>
        <!-- Host: Show their quizzes -->
        <?php if (!empty($host_quizzes)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h2>My Quizzes</h2>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Difficulty</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($host_quizzes as $quiz): ?>
                            <tr>
                                <td><?php echo e($quiz['title']); ?></td>
                                <td>
                                    <span style="text-transform: capitalize; padding: 0.25rem 0.5rem; border-radius: 4px; background-color: 
                                        <?php 
                                        $diff = strtolower($quiz['difficulty'] ?? 'medium');
                                        echo $diff === 'easy' ? '#d4edda' : ($diff === 'hard' ? '#f8d7da' : '#fff3cd');
                                        ?>">
                                        <?php echo e($quiz['difficulty'] ?? 'Medium'); ?>
                                    </span>
                                </td>
                                <td><?php echo $quiz['is_active'] ? '<span style="color: green;">Active</span>' : '<span style="color: red;">Inactive</span>'; ?></td>
                                <td>
                                    <a href="create_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-small btn-primary">Edit</a>
                                    <a href="results_dashboard.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-small btn-success">Results</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Participant: Show quiz history -->
        <?php if (!empty($all_attempts)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h2>Quiz History</h2>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Score</th>
                            <th>Completed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_attempts as $attempt): ?>
                            <tr>
                                <td><?php echo e($attempt['quiz_title']); ?></td>
                                <td>
                                    <?php 
                                    if ($attempt['score'] !== null && $attempt['completed_at']): 
                                        // Use stored total_possible_points if available, otherwise calculate
                                        $total_possible = $attempt['total_possible_points'] ?? 0;
                                        if ($total_possible == 0) {
                                            $questions = QuestionModel::getByQuiz($attempt['quiz_id']);
                                            foreach ($questions as $q) {
                                                $total_possible += $q['points'];
                                            }
                                        }
                                        $percentage = $total_possible > 0 ? round(($attempt['score'] / $total_possible) * 100, 1) : 0;
                                        echo '<strong>' . $attempt['score'] . ' / ' . $total_possible . '</strong> (' . $percentage . '%)';
                                    else:
                                        echo 'N/A';
                                    endif;
                                    ?>
                                </td>
                                <td><?php echo $attempt['completed_at'] ? date('Y-m-d H:i', strtotime($attempt['completed_at'])) : 'Incomplete'; ?></td>
                                <td>
                                    <?php if ($attempt['completed_at']): ?>
                                        <a href="results_dashboard.php?attempt_id=<?php echo $attempt['id']; ?>" class="btn btn-small btn-primary">View Results</a>
                                    <?php else: ?>
                                        <a href="play_quiz.php?quiz_id=<?php echo $attempt['quiz_id']; ?>" class="btn btn-small btn-success">Continue</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card mt-3">
                <p>You haven't taken any quizzes yet. <a href="index.php">Browse available quizzes</a> to get started!</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Profile Edit Form -->
    <div class="card mt-3">
        <div class="card-header">
            <h2>Edit Profile</h2>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="flash flash-error">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="flash flash-success"><?php echo e($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="grid grid-2" style="align-items: start;">
                <div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="<?php echo e($current_user['email']); ?>" disabled>
                        <small style="color: #666;">Email cannot be changed.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" required value="<?php echo e($current_user['name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <input type="text" id="role" value="<?php echo e($current_user['role']); ?>" disabled>
                        <small style="color: #666;">Role cannot be changed.</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Account Created</label>
                        <input type="text" value="<?php echo date('Y-m-d H:i', strtotime($current_user['created_at'])); ?>" disabled>
                    </div>
                </div>
                
                <div>
                    <div style="margin-bottom: 1rem;">
                        <h3 style="margin: 0; font-weight: 800; text-transform: uppercase;">Change Password (Optional)</h3>
                    </div>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter current password to change">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" minlength="6" placeholder="Leave blank to keep current password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6" placeholder="Confirm new password">
                    </div>
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 1.5rem; border-top: 4px solid #1a1a1a; padding-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Update Profile</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
