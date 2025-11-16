<?php
/**
 * Export Quiz Page
 * Host-only page for exporting quizzes as CSV files
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/QuizModel.php';
require_once __DIR__ . '/../models/QuestionModel.php';

require_host();

$current_user = current_user();
$quiz_id = $_GET['quiz_id'] ?? null;

if (!$quiz_id) {
    set_flash('error', 'Quiz ID required.');
    header('Location: index.php');
    exit;
}

$quiz = QuizModel::getById($quiz_id);

if (!$quiz || !QuizModel::isOwner($quiz_id, $current_user['id'])) {
    set_flash('error', 'Quiz not found or access denied.');
    header('Location: index.php');
    exit;
}

// Fetch questions
$questions = QuestionModel::getByQuiz($quiz_id, false);

// Build CSV content
$csv_lines = [];

// Header row with quiz metadata
$csv_lines[] = [
    'TITLE',
    'DESCRIPTION',
    'DIFFICULTY',
    'IS_COLLABORATIVE',
    'QUESTION_TYPE',
    'QUESTION_TEXT',
    'OPTIONS',
    'CORRECT_ANSWER',
    'POINTS'
];

// Quiz metadata row (first data row)
$csv_lines[] = [
    $quiz['title'],
    $quiz['description'] ?? '',
    $quiz['difficulty'] ?? 'medium',
    $quiz['is_collaborative'] ? '1' : '0',
    '', // Empty for metadata row
    '', // Empty for metadata row
    '', // Empty for metadata row
    '', // Empty for metadata row
    ''  // Empty for metadata row
];

// Question rows
foreach ($questions as $q) {
    $options_str = '';
    if ($q['type'] === 'mcq' && $q['options_json']) {
        $options = json_decode($q['options_json'], true);
        if (is_array($options)) {
            $options_str = implode('|', $options);
        }
    }
    
    $csv_lines[] = [
        '', // Empty for question rows (metadata already in row 2)
        '', // Empty for question rows
        '', // Empty for question rows
        '', // Empty for question rows
        $q['type'],
        $q['question_text'],
        $options_str,
        $q['correct_answer'],
        $q['points']
    ];
}

// Convert to CSV format
$output = fopen('php://temp', 'r+');
foreach ($csv_lines as $row) {
    fputcsv($output, $row);
}
rewind($output);
$csv_content = stream_get_contents($output);
fclose($output);

// Output CSV file
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="quiz_' . $quiz_id . '_' . date('Y-m-d') . '.csv"');
echo $csv_content;
exit;
