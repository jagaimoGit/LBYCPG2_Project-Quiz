<?php
/**
 * Browse Quizzes Page
 * Allows participants and hosts to search and browse all available quizzes
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/QuizModel.php';
require_once __DIR__ . '/../models/QuestionModel.php';
require_once __DIR__ . '/../models/RatingModel.php';
require_once __DIR__ . '/../models/AttemptModel.php';

$page_title = 'Browse Quizzes - LSQuiz';
require_login();

$current_user = current_user();
$is_host = is_host();

// Get search and filter parameters
$search = trim($_GET['search'] ?? '');
$difficulty = $_GET['difficulty'] ?? '';
$my_quizzes_only = isset($_GET['my_quizzes']) && $_GET['my_quizzes'] == '1' && $is_host;
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'desc';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get quizzes with filters
$search_param = !empty($search) ? $search : null;
$difficulty_param = !empty($difficulty) ? $difficulty : null;
$owner_id = $my_quizzes_only ? $current_user['id'] : null;
$quizzes = QuizModel::getActiveQuizzes($search_param, $difficulty_param, $per_page, $offset, $sort_by, $sort_order, $owner_id);
$total_quizzes = QuizModel::countActiveQuizzes($search_param, $difficulty_param, $owner_id);
$total_pages = ceil($total_quizzes / $per_page);

?>
<div class="container">
    <h1>Browse Quizzes</h1>
    
    <!-- Search and Filter Form -->
    <div class="card">
        <div class="card-header">
            <h2>Search & Filter</h2>
        </div>
        <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr <?php echo $is_host ? '1fr' : ''; ?> 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="search">Search Quizzes</label>
                <input type="text" id="search" name="search" value="<?php echo e($search); ?>" placeholder="Search by title or description...">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="difficulty">Difficulty</label>
                <select id="difficulty" name="difficulty">
                    <option value="">All Difficulties</option>
                    <option value="easy" <?php echo $difficulty === 'easy' ? 'selected' : ''; ?>>Easy</option>
                    <option value="medium" <?php echo $difficulty === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="hard" <?php echo $difficulty === 'hard' ? 'selected' : ''; ?>>Hard</option>
                </select>
            </div>
            <?php if ($is_host): ?>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="my_quizzes" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" id="my_quizzes" name="my_quizzes" value="1" <?php echo $my_quizzes_only ? 'checked' : ''; ?> style="width: auto; margin: 0;">
                        <span>My Quizzes Only</span>
                    </label>
                </div>
            <?php endif; ?>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="sort_by">Sort By</label>
                <select id="sort_by" name="sort_by">
                    <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Date Created</option>
                    <option value="rating" <?php echo $sort_by === 'rating' ? 'selected' : ''; ?>>Rating</option>
                    <option value="plays" <?php echo $sort_by === 'plays' ? 'selected' : ''; ?>>Number of Plays</option>
                    <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Title</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="sort_order">Order</label>
                <select id="sort_order" name="sort_order">
                    <option value="desc" <?php echo $sort_order === 'desc' ? 'selected' : ''; ?>>Descending</option>
                    <option value="asc" <?php echo $sort_order === 'asc' ? 'selected' : ''; ?>>Ascending</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search || $difficulty || $my_quizzes_only || $sort_by !== 'created_at' || $sort_order !== 'desc'): ?>
                    <a href="browse_quizzes.php" class="btn btn-secondary" style="margin-top: 0.5rem; display: block;">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Results Info -->
    <?php if ($search || $difficulty): ?>
        <div style="margin: 1rem 0; font-weight: 600;">
            Found <?php echo $total_quizzes; ?> quiz<?php echo $total_quizzes != 1 ? 'zes' : ''; ?>
            <?php if ($search): ?>
                matching "<?php echo e($search); ?>"
            <?php endif; ?>
            <?php if ($difficulty): ?>
                with difficulty: <?php echo ucfirst($difficulty); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Quiz Grid -->
    <?php if (empty($quizzes)): ?>
        <div class="card">
            <p style="text-align: center; padding: 2rem; font-size: 1.1rem;">
                <?php if ($search || $difficulty): ?>
                    No quizzes found matching your criteria. <a href="browse_quizzes.php">Browse all quizzes</a>
                <?php else: ?>
                    No active quizzes available at the moment.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($quizzes as $quiz): 
                $question_count = QuestionModel::countByQuiz($quiz['id']);
            ?>
                <div class="card" style="display: flex; flex-direction: row; align-items: center; gap: 1.5rem; padding: 1.25rem;">
                    <div style="flex: 1; min-width: 0;">
                        <h3 style="margin: 0 0 0.5rem 0; font-size: 1.4rem; font-weight: 900;"><?php echo e($quiz['title']); ?></h3>
                        <?php if ($quiz['description']): ?>
                            <p style="margin: 0 0 0.75rem 0; color: #666; line-height: 1.4; font-size: 0.95rem;"><?php echo e($quiz['description']); ?></p>
                        <?php endif; ?>
                        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center;">
                            <p style="margin: 0; font-weight: 600; font-size: 0.9rem;"><strong>Owner:</strong> <?php echo e($quiz['owner_name']); ?></p>
                            <p style="margin: 0; font-weight: 600; font-size: 0.9rem;">
                                <strong>Difficulty:</strong> 
                                <span style="text-transform: capitalize; padding: 0.25rem 0.5rem; border: 2px solid #1a1a1a; background-color: 
                                    <?php 
                                    $diff = strtolower($quiz['difficulty'] ?? 'medium');
                                    echo $diff === 'easy' ? '#00FF88' : ($diff === 'hard' ? '#FF3366' : '#FFD700');
                                    ?>; font-weight: 700; font-size: 0.85rem;">
                                    <?php echo e($quiz['difficulty'] ?? 'Medium'); ?>
                                </span>
                            </p>
                            <?php 
                            $play_count = AttemptModel::getPlayCount($quiz['id']);
                            $avg_rating = RatingModel::getAverageRating($quiz['id']);
                            $rating_count = RatingModel::getRatingCount($quiz['id']);
                            ?>
                            <p style="margin: 0; font-weight: 600; font-size: 0.9rem;"><strong>Questions:</strong> <?php echo $question_count; ?></p>
                            <p style="margin: 0; font-weight: 600; font-size: 0.9rem;"><strong>Plays:</strong> <?php echo $play_count; ?></p>
                            <?php if ($avg_rating): ?>
                                <p style="margin: 0; font-weight: 600; font-size: 0.9rem;">
                                    <strong>Rating:</strong> 
                                    <span style="color: #FFD700;"><?php echo str_repeat('★', round($avg_rating)); ?></span>
                                    <span style="color: #666;"><?php echo number_format($avg_rating, 1); ?></span>
                                    <span style="color: #666; font-size: 0.8rem;">(<?php echo $rating_count; ?>)</span>
                                </p>
                            <?php else: ?>
                                <p style="margin: 0; font-weight: 600; font-size: 0.9rem; color: #666;"><strong>Rating:</strong> No ratings yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="flex-shrink: 0;">
                        <?php if ($is_host): ?>
                            <button type="button" onclick="showHostQuizWarning(<?php echo $quiz['id']; ?>)" class="btn btn-primary" style="white-space: nowrap;">Test Quiz</button>
                        <?php else: ?>
                            <a href="play_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-primary" style="white-space: nowrap;">Start Quiz</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem; flex-wrap: wrap;">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn btn-secondary">Previous</a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="btn btn-secondary">1</a>
                    <?php if ($start_page > 2): ?>
                        <span style="padding: 0.75rem 1rem; border: 3px solid #1a1a1a; background: #fff;">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span style="padding: 0.75rem 1rem; border: 3px solid #1a1a1a; background: #00D9FF; color: #1a1a1a; font-weight: 800;"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="btn btn-secondary"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span style="padding: 0.75rem 1rem; border: 3px solid #1a1a1a; background: #fff;">...</span>
                    <?php endif; ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="btn btn-secondary"><?php echo $total_pages; ?></a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn btn-secondary">Next</a>
                <?php endif; ?>
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

