<?php
/**
 * General Leaderboard Page
 * Shows top performing participants across all quizzes
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/UserModel.php';

require_login();

$current_user = current_user();
$sort_by = $_GET['sort_by'] ?? 'avg_score';
$order = $_GET['order'] ?? 'desc';

// Get leaderboard data
$leaderboard = UserModel::getGeneralLeaderboard($sort_by, $order, 100);

$page_title = 'General Leaderboard';
?>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>General Leaderboard</h1>
            <p>Top performing participants across all quizzes</p>
        </div>
        
        <!-- Sort Controls -->
        <div style="padding: 1.5rem; border-bottom: 4px solid #1a1a1a; background: #f8f9fa;">
            <form method="GET" action="" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label for="sort_by" style="font-weight: 700;">Sort By:</label>
                    <select id="sort_by" name="sort_by" onchange="this.form.submit()" style="padding: 0.5rem; border: 3px solid #1a1a1a; font-weight: 600;">
                        <option value="avg_score" <?php echo $sort_by === 'avg_score' ? 'selected' : ''; ?>>Average Score</option>
                        <option value="unique_quizzes" <?php echo $sort_by === 'unique_quizzes' ? 'selected' : ''; ?>>Unique Quizzes</option>
                        <option value="attempts" <?php echo $sort_by === 'attempts' ? 'selected' : ''; ?>>Total Attempts</option>
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
                <p style="font-size: 1.2rem; color: #666;">No participants with completed attempts yet.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Rank</th>
                            <th>Participant</th>
                            <th>Unique Quizzes</th>
                            <th>Total Attempts</th>
                            <th>Average Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaderboard as $index => $entry): 
                            $rank = $index + 1;
                            // Highlight current user's entry (if participant)
                            $is_current_user = $entry['id'] == $current_user['id'] && $current_user['role'] === 'participant';
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
                                    <strong><?php echo e($entry['name']); ?></strong>
                                    <?php if ($is_current_user): ?>
                                        <span style="color: #1a1a1a; font-size: 0.9rem;">(You)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $entry['unique_quizzes_count']; ?></strong>
                                </td>
                                <td>
                                    <strong><?php echo $entry['total_attempts']; ?></strong>
                                </td>
                                <td>
                                    <strong style="color: <?php echo $entry['avg_percentage'] >= 80 ? '#00FF88' : ($entry['avg_percentage'] >= 60 ? '#FFD700' : '#FF3366'); ?>;">
                                        <?php echo number_format($entry['avg_percentage'], 1); ?>%
                                    </strong>
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


