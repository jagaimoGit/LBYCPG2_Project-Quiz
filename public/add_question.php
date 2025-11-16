<?php
/**
 * Add Question Page (for collaborative quizzes)
 * Allows participants to suggest questions for collaborative quizzes
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/QuizModel.php';
require_once __DIR__ . '/../models/QuestionModel.php';

require_login();

$current_user = current_user();
$quiz_id = $_POST['quiz_id'] ?? null;
$errors = [];

if (!$quiz_id) {
    set_flash('error', 'Quiz ID required.');
    header('Location: index.php');
    exit;
}

$quiz = QuizModel::getById($quiz_id);

if (!$quiz || !$quiz['is_active'] || !$quiz['is_collaborative']) {
    set_flash('error', 'This quiz does not accept participant questions.');
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = trim($_POST['question_text'] ?? '');
    $question_type = $_POST['question_type'] ?? 'mcq';
    $points = 1; // Default points for participant-submitted questions
    
    if (!require_field($question_text)) {
        $errors[] = 'Question text is required.';
    }
    
    if ($question_type === 'mcq') {
        $options = [];
        $correct_option = '';
        for ($i = 1; $i <= 4; $i++) {
            $option = trim($_POST["option_$i"] ?? '');
            if ($option) {
                $options[] = $option;
                if (isset($_POST['correct_option']) && $_POST['correct_option'] == $i) {
                    $correct_option = $option;
                }
            }
        }
        
        if (count($options) < 2) {
            $errors[] = 'At least 2 options are required for MCQ.';
        } elseif (empty($correct_option)) {
            $errors[] = 'Please select a correct option.';
        } else {
            $options_json = json_encode($options);
            // Participant-submitted questions start as unapproved (is_approved = 0)
            if (QuestionModel::create($quiz_id, $current_user['id'], 'mcq', $question_text, $options_json, $correct_option, $points, false)) {
                set_flash('success', 'Question submitted! It will be reviewed by the host.');
                header('Location: play_quiz.php?quiz_id=' . $quiz_id);
                exit;
            } else {
                $errors[] = 'Failed to submit question.';
            }
        }
    } elseif ($question_type === 'enum') {
        $correct_answer = trim($_POST['correct_answer'] ?? '');
        if (!require_field($correct_answer)) {
            $errors[] = 'Correct answer is required for enumeration.';
        } else {
            // Participant-submitted questions start as unapproved
            if (QuestionModel::create($quiz_id, $current_user['id'], 'enum', $question_text, null, $correct_answer, $points, false)) {
                set_flash('success', 'Question submitted! It will be reviewed by the host.');
                header('Location: play_quiz.php?quiz_id=' . $quiz_id);
                exit;
            } else {
                $errors[] = 'Failed to submit question.';
            }
        }
    } elseif ($question_type === 'identification') {
        $correct_answer = trim($_POST['identification_answer'] ?? '');
        if (!require_field($correct_answer)) {
            $errors[] = 'Correct answer is required for identification.';
        } else {
            // Participant-submitted questions start as unapproved
            if (QuestionModel::create($quiz_id, $current_user['id'], 'identification', $question_text, null, $correct_answer, $points, false)) {
                set_flash('success', 'Question submitted! It will be reviewed by the host.');
                header('Location: play_quiz.php?quiz_id=' . $quiz_id);
                exit;
            } else {
                $errors[] = 'Failed to submit question.';
            }
        }
    }
}

// If we get here, there were errors
if (!empty($errors)) {
    set_flash('error', implode(' ', $errors));
}
header('Location: play_quiz.php?quiz_id=' . $quiz_id);
exit;
