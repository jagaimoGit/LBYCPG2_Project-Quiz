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
                <button class="hamburger" id="hamburger" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <nav class="main-nav" id="main-nav">
                    <div class="nav-content">
                        <?php if ($current_user): ?>
                            <a href="index.php" class="nav-link">Main</a>
                            <?php if (is_host()): ?>
                                <a href="create_quiz.php" class="nav-link">Create Quiz</a>
                                <a href="browse_quizzes.php" class="nav-link">Browse Quizzes</a>
                            <?php else: ?>
                                <a href="browse_quizzes.php" class="nav-link">Browse Quizzes</a>
                            <?php endif; ?>
                            <a href="general_leaderboard.php" class="nav-link">Leaderboard</a>
                            <a href="profile.php" class="nav-link">Profile</a>
                            <a href="logout.php" class="nav-link">Logout</a>
                            <div class="user-info">
                                <span class="user-name"><?php echo e($current_user['name']); ?></span>
                                <span class="user-role"><?php echo e($current_user['role']); ?></span>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="nav-link">Login</a>
                            <a href="register.php" class="nav-link">Register</a>
                        <?php endif; ?>
                    </div>
                </nav>
            </div>
        </div>
    </header>
    <script>
        // Hamburger menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.getElementById('hamburger');
            const nav = document.getElementById('main-nav');
            const body = document.body;
            
            hamburger.addEventListener('click', function() {
                hamburger.classList.toggle('active');
                nav.classList.toggle('active');
                body.classList.toggle('menu-open');
            });
            
            // Close menu when clicking on a link
            const navLinks = nav.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    hamburger.classList.remove('active');
                    nav.classList.remove('active');
                    body.classList.remove('menu-open');
                });
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInsideNav = nav.contains(event.target);
                const isClickOnHamburger = hamburger.contains(event.target);
                
                if (!isClickInsideNav && !isClickOnHamburger && nav.classList.contains('active')) {
                    hamburger.classList.remove('active');
                    nav.classList.remove('active');
                    body.classList.remove('menu-open');
                }
            });
        });
    </script>
    <main class="main-content">
        <?php display_flash(); ?>
