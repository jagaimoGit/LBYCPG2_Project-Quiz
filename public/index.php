<?php
/**
 * Main Page
 * Displays quiz list for hosts (their quizzes) or participants (active quizzes)
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/QuizModel.php';
require_once __DIR__ . '/../models/QuestionModel.php';
require_once __DIR__ . '/../models/AttemptModel.php';
require_once __DIR__ . '/../models/RatingModel.php';

$page_title = 'LSQuiz - Main';
require_login();

$current_user = current_user();
$is_host = is_host();
?>
<div class="container">
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;">Welcome, <?php echo e($current_user['name']); ?>!</h1>
        <div style="display: inline-block; padding: 0.5rem 1rem; border: 3px solid #1a1a1a; background: #FFD700; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
            <?php echo e($current_user['role']); ?>
        </div>
    </div>
    
    <?php if ($is_host): ?>
        <!-- Host View: Show owned quizzes -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2>My Quizzes</h2>
                <a href="create_quiz.php" class="btn btn-primary">Create New Quiz</a>
            </div>
            
            <?php
            $quizzes = QuizModel::getByOwner($current_user['id']);
            if (empty($quizzes)):
            ?>
                <p>You haven't created any quizzes yet. <a href="create_quiz.php">Create your first quiz</a>!</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Difficulty</th>
                            <th>Questions</th>
                            <th>Rating</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quizzes as $quiz): 
                            $question_count = QuestionModel::countByQuiz($quiz['id'], false);
                            $avg_rating = RatingModel::getAverageRating($quiz['id']);
                            $rating_count = RatingModel::getRatingCount($quiz['id']);
                        ?>
                            <tr>
                                <td><strong><?php echo e($quiz['title']); ?></strong></td>
                                <td>
                                    <?php if ($quiz['is_active']): ?>
                                        <span style="color: green;">Active</span>
                                    <?php else: ?>
                                        <span style="color: red;">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="text-transform: capitalize; padding: 0.25rem 0.5rem; border: 2px solid #1a1a1a; background-color: 
                                        <?php 
                                        $diff = strtolower($quiz['difficulty'] ?? 'medium');
                                        echo $diff === 'easy' ? '#00FF88' : ($diff === 'hard' ? '#FF3366' : '#FFD700');
                                        ?>; font-weight: 700;">
                                        <?php echo e($quiz['difficulty'] ?? 'Medium'); ?>
                                    </span>
                                </td>
                                <td><?php echo $question_count; ?></td>
                                <td>
                                    <?php if ($avg_rating): ?>
                                        <span style="color: #FFD700; font-size: 1.1rem;"><?php echo str_repeat('★', round($avg_rating)); ?></span>
                                        <span style="color: #666; font-size: 0.9rem;"><?php echo number_format($avg_rating, 1); ?></span>
                                        <span style="color: #666; font-size: 0.8rem;">(<?php echo $rating_count; ?>)</span>
                                    <?php else: ?>
                                        <span style="color: #999;">No ratings yet</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($quiz['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                                        <a href="create_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-small btn-primary">Edit</a>
                                        <a href="export_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-small btn-secondary">Export</a>
                                        <a href="results_dashboard.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-small btn-success">Results</a>
                                        <?php if ($quiz['is_active']): ?>
                                            <button type="button" onclick="showHostQuizWarning(<?php echo $quiz['id']; ?>)" class="btn btn-small" style="background: #FFD700; border: 3px solid #1a1a1a; color: #1a1a1a; font-weight: 700;">Test Quiz</button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($quiz['access_code']): ?>
                                        <small style="display: block; margin-top: 0.5rem;">Code: <?php echo e($quiz['access_code']); ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Host View: Also show available quizzes to test -->
        <div class="card mt-3">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Available Quizzes (Test Mode)</h2>
                <a href="browse_quizzes.php" class="btn btn-primary">Browse All Quizzes</a>
            </div>
            
            <?php
            // Show top 5 most played quizzes
            $quizzes = QuizModel::getTopMostPlayed(5);
            $total_quizzes = QuizModel::countActiveQuizzes();
            
            if (empty($quizzes)):
            ?>
                <p>No active quizzes available at the moment.</p>
            <?php else: ?>
                <div class="grid grid-3">
                    <?php foreach ($quizzes as $quiz): 
                        $question_count = QuestionModel::countByQuiz($quiz['id']);
                    ?>
                        <div class="card" style="display: flex; flex-direction: column;">
                            <div style="flex: 1;">
                                <h3 style="margin-bottom: 0.75rem; font-size: 1.3rem;"><?php echo e($quiz['title']); ?></h3>
                                <?php if ($quiz['description']): ?>
                                    <p style="margin-bottom: 1rem; color: #666; line-height: 1.5; font-size: 0.9rem;"><?php echo e(mb_substr($quiz['description'], 0, 100)); ?><?php echo mb_strlen($quiz['description']) > 100 ? '...' : ''; ?></p>
                                <?php endif; ?>
                                <div style="margin-bottom: 0.75rem;">
                                    <p style="margin: 0.25rem 0; font-weight: 600; font-size: 0.9rem;"><strong>Owner:</strong> <?php echo e($quiz['owner_name']); ?></p>
                                    <p style="margin: 0.25rem 0; font-weight: 600; font-size: 0.9rem;">
                                        <strong>Difficulty:</strong> 
                                        <span style="text-transform: capitalize; padding: 0.25rem 0.5rem; border: 2px solid #1a1a1a; background-color: 
                                            <?php 
                                            $diff = strtolower($quiz['difficulty'] ?? 'medium');
                                            echo $diff === 'easy' ? '#00FF88' : ($diff === 'hard' ? '#FF3366' : '#FFD700');
                                            ?>; font-weight: 700; font-size: 0.85rem;">
                                            <?php echo e($quiz['difficulty'] ?? 'Medium'); ?>
                                        </span>
                                    </p>
                                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                        <p style="margin: 0; font-weight: 600; font-size: 0.9rem;"><strong>Questions:</strong> <?php echo $question_count; ?></p>
                                        <p style="margin: 0; font-weight: 600; font-size: 0.9rem;"><strong>Plays:</strong> <?php echo (int)($quiz['play_count'] ?? 0); ?></p>
                                        <?php if ($quiz['average_rating']): ?>
                                            <p style="margin: 0; font-weight: 600; font-size: 0.9rem;">
                                                <strong>Rating:</strong> 
                                                <span style="color: #FFD700;"><?php echo str_repeat('★', round($quiz['average_rating'])); ?></span>
                                                <span style="color: #666;"><?php echo number_format($quiz['average_rating'], 1); ?></span>
                                                <span style="color: #666; font-size: 0.8rem;">(<?php echo (int)($quiz['rating_count'] ?? 0); ?>)</span>
                                            </p>
                                        <?php else: ?>
                                            <p style="margin: 0; font-weight: 600; font-size: 0.9rem; color: #666;"><strong>Rating:</strong> No ratings yet</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: auto; padding-top: 1rem;">
                                <button type="button" onclick="showHostQuizWarning(<?php echo $quiz['id']; ?>)" class="btn btn-primary" style="width: 100%; text-align: center;">Test Quiz</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_quizzes > 5): ?>
                    <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 4px solid #1a1a1a;">
                        <p style="font-weight: 700; margin-bottom: 1rem;">Showing top 5 most played quizzes of <?php echo $total_quizzes; ?> total</p>
                        <a href="browse_quizzes.php" class="btn btn-primary btn-large">View All Quizzes</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <!-- Participant View: Quick preview and link to browse -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Available Quizzes</h2>
                <a href="browse_quizzes.php" class="btn btn-primary">Browse All Quizzes</a>
            </div>
            
            <?php
            // Show top 5 most played quizzes
            $quizzes = QuizModel::getTopMostPlayed(5);
            $total_quizzes = QuizModel::countActiveQuizzes();
            
            if (empty($quizzes)):
            ?>
                <p>No active quizzes available at the moment.</p>
            <?php else: ?>
                <div class="grid grid-3">
                    <?php foreach ($quizzes as $quiz): 
                        $question_count = QuestionModel::countByQuiz($quiz['id']);
                    ?>
                        <div class="card" style="display: flex; flex-direction: column;">
                            <div style="flex: 1;">
                                <h3 style="margin-bottom: 0.75rem; font-size: 1.3rem;"><?php echo e($quiz['title']); ?></h3>
                                <?php if ($quiz['description']): ?>
                                    <p style="margin-bottom: 1rem; color: #666; line-height: 1.5; font-size: 0.9rem;"><?php echo e(mb_substr($quiz['description'], 0, 100)); ?><?php echo mb_strlen($quiz['description']) > 100 ? '...' : ''; ?></p>
                                <?php endif; ?>
                                <div style="margin-bottom: 0.75rem;">
                                    <p style="margin: 0.25rem 0; font-weight: 600; font-size: 0.9rem;"><strong>Owner:</strong> <?php echo e($quiz['owner_name']); ?></p>
                                    <p style="margin: 0.25rem 0; font-weight: 600; font-size: 0.9rem;">
                                        <strong>Difficulty:</strong> 
                                        <span style="text-transform: capitalize; padding: 0.25rem 0.5rem; border: 2px solid #1a1a1a; background-color: 
                                            <?php 
                                            $diff = strtolower($quiz['difficulty'] ?? 'medium');
                                            echo $diff === 'easy' ? '#00FF88' : ($diff === 'hard' ? '#FF3366' : '#FFD700');
                                            ?>; font-weight: 700; font-size: 0.85rem;">
                                            <?php echo e($quiz['difficulty'] ?? 'Medium'); ?>
                                        </span>
                                    </p>
                                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                        <p style="margin: 0; font-weight: 600; font-size: 0.9rem;"><strong>Questions:</strong> <?php echo $question_count; ?></p>
                                        <p style="margin: 0; font-weight: 600; font-size: 0.9rem;"><strong>Plays:</strong> <?php echo (int)($quiz['play_count'] ?? 0); ?></p>
                                        <?php if ($quiz['average_rating']): ?>
                                            <p style="margin: 0; font-weight: 600; font-size: 0.9rem;">
                                                <strong>Rating:</strong> 
                                                <span style="color: #FFD700;"><?php echo str_repeat('★', round($quiz['average_rating'])); ?></span>
                                                <span style="color: #666;"><?php echo number_format($quiz['average_rating'], 1); ?></span>
                                                <span style="color: #666; font-size: 0.8rem;">(<?php echo (int)($quiz['rating_count'] ?? 0); ?>)</span>
                                            </p>
                                        <?php else: ?>
                                            <p style="margin: 0; font-weight: 600; font-size: 0.9rem; color: #666;"><strong>Rating:</strong> No ratings yet</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: auto; padding-top: 1rem;">
                                <?php if ($is_host): ?>
                                    <button type="button" onclick="showHostQuizWarning(<?php echo $quiz['id']; ?>)" class="btn btn-primary" style="width: 100%; text-align: center;">Test Quiz</button>
                                <?php else: ?>
                                    <a href="play_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-primary" style="width: 100%; text-align: center;">Start Quiz</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_quizzes > 5): ?>
                    <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 4px solid #1a1a1a;">
                        <p style="font-weight: 700; margin-bottom: 1rem;">Showing top 5 most played quizzes of <?php echo $total_quizzes; ?> total</p>
                        <a href="browse_quizzes.php" class="btn btn-primary btn-large">View All Quizzes</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Search by Access Code -->
        <div class="card mt-3">
            <div class="card-header">
                <h2>Join Quiz by Code</h2>
            </div>
            <form method="GET" action="play_quiz.php">
                <div class="form-group">
                    <label for="access_code">Quiz Access Code</label>
                    <input type="text" id="access_code" name="access_code" placeholder="Enter quiz code" required style="max-width: 300px;">
                </div>
                <button type="submit" class="btn btn-primary">Join Quiz</button>
            </form>
        </div>
        
        <!-- Previous Attempts -->
        <?php
        $attempts = AttemptModel::getByUser($current_user['id']);
        if (!empty($attempts)):
        ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h2>My Previous Attempts</h2>
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
                        <?php foreach ($attempts as $attempt): ?>
                            <tr>
                                <td><?php echo e($attempt['quiz_title']); ?></td>
                                <td><strong><?php echo $attempt['score']; ?></strong></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($attempt['completed_at'])); ?></td>
                                <td>
                                    <a href="results_dashboard.php?attempt_id=<?php echo $attempt['id']; ?>" class="btn btn-small btn-primary">View Results</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Host Quiz Warning Modal -->
<?php if ($is_host): ?>
    <div id="hostQuizWarningModal" class="modal hidden">
        <div class="modal-overlay" onclick="closeHostQuizWarning()"></div>
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Test Quiz as Host</h2>
                <button type="button" class="modal-close" onclick="closeHostQuizWarning()">&times;</button>
            </div>
            <div style="padding: 2rem;">
                <p style="font-size: 1.1rem; margin-bottom: 1.5rem; font-weight: 700; line-height: 1.6;">
                    As a host, you can test this quiz to preview the experience, but your score will not be saved or counted in the quiz statistics.
                </p>
                <p style="margin-bottom: 1.5rem; color: #666;">
                    Would you like to proceed with testing this quiz?
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <a href="" id="proceedToQuiz" class="btn btn-primary">Yes, Test Quiz</a>
                    <button type="button" onclick="closeHostQuizWarning()" class="btn btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function showHostQuizWarning(quizId) {
        document.getElementById('proceedToQuiz').href = 'play_quiz.php?quiz_id=' + quizId + '&host_test=1';
        document.getElementById('hostQuizWarningModal').classList.remove('hidden');
    }
    
    function closeHostQuizWarning() {
        document.getElementById('hostQuizWarningModal').classList.add('hidden');
    }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
