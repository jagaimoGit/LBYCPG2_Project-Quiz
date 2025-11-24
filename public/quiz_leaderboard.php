<?php
/**
 * Quiz Leaderboard Page
 * Shows top performers for a specific quiz with sorting options
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/QuizModel.php';
require_once __DIR__ . '/../models/AttemptModel.php';

require_login();

$current_user = current_user();
$quiz_id = $_GET['quiz_id'] ?? null;
$sort_by = $_GET['sort_by'] ?? 'score';
$order = $_GET['order'] ?? 'desc';

if (!$quiz_id) {
    set_flash('error', 'Quiz ID required.');
    header('Location: index.php');
    exit;
}

$quiz = QuizModel::getById($quiz_id);
if (!$quiz) {
    set_flash('error', 'Quiz not found.');
    header('Location: index.php');
    exit;
}

// Get leaderboard data
$leaderboard = AttemptModel::getQuizLeaderboard($quiz_id, $sort_by, $order, 100);

$page_title = 'Leaderboard - ' . e($quiz['title']);
?>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>Leaderboard</h1>
            <h2><?php echo e($quiz['title']); ?></h2>
        </div>
        
        <!-- Sort Controls -->
        <div style="padding: 1.5rem; border-bottom: 4px solid #1a1a1a; background: #f8f9fa;">
            <form method="GET" action="" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label for="sort_by" style="font-weight: 700;">Sort By:</label>
                    <select id="sort_by" name="sort_by" onchange="this.form.submit()" style="padding: 0.5rem; border: 3px solid #1a1a1a; font-weight: 600;">
                        <option value="score" <?php echo $sort_by === 'score' ? 'selected' : ''; ?>>Score</option>
                        <option value="time" <?php echo $sort_by === 'time' ? 'selected' : ''; ?>>Time (Speed)</option>
                    </select>
                </div>
                
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label for="order" style="font-weight: 700;">Order:</label>
                    <select id="order" name="order" onchange="this.form.submit()" style="padding: 0.5rem; border: 3px solid #1a1a1a; font-weight: 600;">
                        <option value="desc" <?php echo $order === 'desc' ? 'selected' : ''; ?>>Descending</option>
                        <option value="asc" <?php echo $order === 'asc' ? 'selected' : ''; ?>>Ascending</option>
                    </select>
                </div>
            </form>
        </div>
        
        <?php if (empty($leaderboard)): ?>
            <div style="padding: 2rem; text-align: center;">
                <p style="font-size: 1.2rem; color: #666;">No completed attempts yet. Be the first!</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Rank</th>
                            <th>Participant</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Time</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaderboard as $index => $entry): 
                            $rank = $index + 1;
                            // Highlight current user's entry
                            $is_current_user = $entry['user_id'] == $current_user['id'];
                            $row_style = $is_current_user ? 'background: #FFD700; font-weight: 700;' : '';
                        ?>
                            <tr style="<?php echo $row_style; ?>">
                                <td>
                                    <?php if ($rank <= 3): ?>
                                        <span style="font-size: 1.5rem;">
                                            <?php echo $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : '🥉'); ?>
                                        </span>
                                    <?php else: ?>
                                        <strong>#<?php echo $rank; ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo e($entry['user_name']); ?></strong>
                                    <?php if ($is_current_user): ?>
                                        <span style="color: #1a1a1a; font-size: 0.9rem;">(You)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $entry['score']; ?> / <?php echo $entry['total_possible_points']; ?></strong>
                                </td>
                                <td>
                                    <strong style="color: <?php echo $entry['percentage'] >= 80 ? '#00FF88' : ($entry['percentage'] >= 60 ? '#FFD700' : '#FF3366'); ?>;">
                                        <?php echo number_format($entry['percentage'], 1); ?>%
                                    </strong>
                                </td>
                                <td>
                                    <strong><?php echo e($entry['time_formatted']); ?></strong>
                                </td>
                                <td>
                                    <?php echo date('Y-m-d H:i', strtotime($entry['completed_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div style="padding: 1.5rem; border-top: 4px solid #1a1a1a;">
            <a href="index.php" class="btn btn-secondary">Back to Main</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

