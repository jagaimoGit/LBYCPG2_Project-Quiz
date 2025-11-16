<?php
/**
 * Shared Header Template
 * Includes navigation and basic HTML structure
 */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
$current_user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) : 'LSQuiz'; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo"><a href="index.php">LSQuiz</a></h1>
                <nav class="main-nav">
                    <?php if ($current_user): ?>
                        <a href="index.php">Main</a>
                        <?php if (is_host()): ?>
                            <a href="create_quiz.php">Create Quiz</a>
                            <a href="browse_quizzes.php">Browse Quizzes</a>
                        <?php else: ?>
                            <a href="browse_quizzes.php">Browse Quizzes</a>
                        <?php endif; ?>
                        <a href="profile.php">Profile</a>
                        <a href="logout.php">Logout</a>
                        <span class="user-info"><?php echo e($current_user['name']); ?> (<?php echo e($current_user['role']); ?>)</span>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                        <a href="register.php">Register</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
    <main class="main-content">
        <?php display_flash(); ?>
