<?php
/**
 * Full Quiz History Page
 * Shows all quiz attempts with search, filter, and sort functionality
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/AttemptModel.php';
require_once __DIR__ . '/../models/QuizModel.php';
require_once __DIR__ . '/../models/QuestionModel.php';

require_login();

$current_user = current_user();

// Get search and filter parameters
$search = trim($_GET['search'] ?? '');
$difficulty = $_GET['difficulty'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'completed_at';
$sort_order = $_GET['sort_order'] ?? 'desc';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get all attempts for the user
$all_attempts = AttemptModel::getByUser($current_user['id']);

// Filter attempts
$filtered_attempts = [];
foreach ($all_attempts as $attempt) {
    // Search filter
    if (!empty($search)) {
        $quiz_title = strtolower($attempt['quiz_title'] ?? '');
        if (strpos($quiz_title, strtolower($search)) === false) {
            continue;
        }
    }
    
    // Difficulty filter
    if (!empty($difficulty)) {
        $quiz = QuizModel::getById($attempt['quiz_id']);
        if (!$quiz || strtolower($quiz['difficulty'] ?? '') !== strtolower($difficulty)) {
            continue;
        }
    }
    
    $filtered_attempts[] = $attempt;
}

// Sort attempts
usort($filtered_attempts, function($a, $b) use ($sort_by, $sort_order) {
    $result = 0;
    
    switch ($sort_by) {
        case 'quiz_title':
            $result = strcasecmp($a['quiz_title'] ?? '', $b['quiz_title'] ?? '');
            break;
        case 'score':
            $score_a = $a['score'] ?? 0;
            $score_b = $b['score'] ?? 0;
            $result = $score_a <=> $score_b;
            break;
        case 'percentage':
            $total_a = $a['total_possible_points'] ?? 0;
            $total_b = $b['total_possible_points'] ?? 0;
            $perc_a = $total_a > 0 ? ($a['score'] ?? 0) / $total_a : 0;
            $perc_b = $total_b > 0 ? ($b['score'] ?? 0) / $total_b : 0;
            $result = $perc_a <=> $perc_b;
            break;
        case 'time':
            $time_a = calculate_time_duration($a['started_at'] ?? null, $a['completed_at'] ?? null) ?? PHP_INT_MAX;
            $time_b = calculate_time_duration($b['started_at'] ?? null, $b['completed_at'] ?? null) ?? PHP_INT_MAX;
            $result = $time_a <=> $time_b;
            break;
        case 'completed_at':
        default:
            $time_a = strtotime($a['completed_at'] ?? '1970-01-01');
            $time_b = strtotime($b['completed_at'] ?? '1970-01-01');
            $result = $time_a <=> $time_b;
            break;
    }
    
    return $sort_order === 'asc' ? $result : -$result;
});

$total_attempts = count($filtered_attempts);
$total_pages = ceil($total_attempts / $per_page);
$display_attempts = array_slice($filtered_attempts, $offset, $per_page);

$page_title = 'Quiz History - LSQuiz';
?>
<div class="container">
    <h1>Quiz History</h1>
    
    <!-- Search and Filter Form -->
    <div class="card">
        <div class="card-header">
            <h2>Search & Filter</h2>
        </div>
        <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="search">Search Quizzes</label>
                <input type="text" id="search" name="search" value="<?php echo e($search); ?>" placeholder="Search by quiz name...">
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
            <div class="form-group" style="margin-bottom: 0;">
                <label for="sort_by">Sort By</label>
                <select id="sort_by" name="sort_by">
                    <option value="completed_at" <?php echo $sort_by === 'completed_at' ? 'selected' : ''; ?>>Date Completed</option>
                    <option value="quiz_title" <?php echo $sort_by === 'quiz_title' ? 'selected' : ''; ?>>Quiz Name</option>
                    <option value="score" <?php echo $sort_by === 'score' ? 'selected' : ''; ?>>Score</option>
                    <option value="percentage" <?php echo $sort_by === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                    <option value="time" <?php echo $sort_by === 'time' ? 'selected' : ''; ?>>Time</option>
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
                <?php if ($search || $difficulty || $sort_by !== 'completed_at' || $sort_order !== 'desc'): ?>
                    <a href="quiz_history.php" class="btn btn-secondary" style="margin-top: 0.5rem; display: block;">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Results Info -->
    <?php if ($search || $difficulty): ?>
        <div style="margin: 1rem 0; font-weight: 600;">
            Found <?php echo $total_attempts; ?> attempt<?php echo $total_attempts != 1 ? 's' : ''; ?>
            <?php if ($search): ?>
                matching "<?php echo e($search); ?>"
            <?php endif; ?>
            <?php if ($difficulty): ?>
                with difficulty: <?php echo ucfirst($difficulty); ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="margin: 1rem 0; font-weight: 600;">
            Total: <?php echo $total_attempts; ?> attempt<?php echo $total_attempts != 1 ? 's' : ''; ?>
        </div>
    <?php endif; ?>
    
    <!-- History Table -->
    <?php if (empty($display_attempts)): ?>
        <div class="card">
            <p style="text-align: center; padding: 2rem; font-size: 1.1rem;">
                <?php if ($search || $difficulty): ?>
                    No attempts found matching your criteria. <a href="quiz_history.php">View all attempts</a>
                <?php else: ?>
                    You haven't taken any quizzes yet. <a href="browse_quizzes.php">Browse available quizzes</a> to get started!
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Quiz</th>
                        <th>Difficulty</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Time</th>
                        <th>Completed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($display_attempts as $attempt): 
                        $quiz = QuizModel::getById($attempt['quiz_id']);
                        $time_duration = calculate_time_duration($attempt['started_at'] ?? null, $attempt['completed_at'] ?? null);
                        $time_formatted = format_time_duration($time_duration);
                        
                        // Calculate score and percentage
                        $total_possible = $attempt['total_possible_points'] ?? 0;
                        if ($total_possible == 0) {
                            $questions = QuestionModel::getByQuiz($attempt['quiz_id']);
                            foreach ($questions as $q) {
                                $total_possible += $q['points'];
                            }
                        }
                        $percentage = $total_possible > 0 && $attempt['completed_at'] ? round(($attempt['score'] / $total_possible) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo e($attempt['quiz_title']); ?></strong>
                            </td>
                            <td>
                                <?php if ($quiz): 
                                    $diff = strtolower($quiz['difficulty'] ?? 'medium');
                                ?>
                                    <span style="text-transform: capitalize; padding: 0.25rem 0.5rem; border: 2px solid #1a1a1a; background-color: 
                                        <?php echo $diff === 'easy' ? '#00FF88' : ($diff === 'hard' ? '#FF3366' : '#FFD700'); ?>; 
                                        font-weight: 700; font-size: 0.85rem;">
                                        <?php echo e($quiz['difficulty'] ?? 'Medium'); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #666;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($attempt['score'] !== null && $attempt['completed_at']): ?>
                                    <strong><?php echo $attempt['score']; ?> / <?php echo $total_possible; ?></strong>
                                <?php else: ?>
                                    <span style="color: #666;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($attempt['score'] !== null && $attempt['completed_at']): ?>
                                    <strong style="color: <?php echo $percentage >= 80 ? '#00FF88' : ($percentage >= 60 ? '#FFD700' : '#FF3366'); ?>;">
                                        <?php echo $percentage; ?>%
                                    </strong>
                                <?php else: ?>
                                    <span style="color: #666;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo e($time_formatted); ?>
                            </td>
                            <td>
                                <?php echo $attempt['completed_at'] ? date('Y-m-d H:i', strtotime($attempt['completed_at'])) : '<span style="color: #666;">Incomplete</span>'; ?>
                            </td>
                            <td>
                                <?php if ($attempt['completed_at']): ?>
                                    <a href="results_dashboard.php?attempt_id=<?php echo $attempt['id']; ?>" class="btn btn-small btn-primary">View Results</a>
                                    <a href="quiz_leaderboard.php?quiz_id=<?php echo $attempt['quiz_id']; ?>" class="btn btn-small" style="background: #FFD700; border: 3px solid #1a1a1a; color: #1a1a1a; font-weight: 700; margin-left: 0.5rem;">Leaderboard</a>
                                <?php else: ?>
                                    <a href="play_quiz.php?quiz_id=<?php echo $attempt['quiz_id']; ?>" class="btn btn-small btn-success">Continue</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
    
    <div style="margin-top: 2rem;">
        <a href="index.php" class="btn btn-secondary">Back to Main</a>
        <a href="profile.php" class="btn btn-secondary">Back to Profile</a>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

