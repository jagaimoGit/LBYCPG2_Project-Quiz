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



// Quiz metadata row (first data row)
$csv_lines[] = [
    $quiz['title'],
    $quiz['description'] ?? '',
    $quiz['difficulty'] ?? 'medium',
    $quiz['is_collaborative'] ? 'TRUE' : 'FALSE',
    '', // Empty for metadata row
    '', // Empty for metadata row
    '', // Empty for metadata row
    '', // Empty for metadata row
    ''  // Empty for metadata row
];

$csv_lines[] = [
    'type',
    'question_text' ,
    'option1',
    'option2',
    'option3',
    'option4',
     'correct_answer',
     'points'];


// Question rows
foreach ($questions as $q) 
    {
    $options_str = '';
    if ($q['type'] === 'mcq' && $q['options_json']) {
        $options = json_decode($q['options_json'], true);
        if (is_array($options)) {
            $options_str = implode('|', $options);
        }
    }

    list($option1, $option2, $option3, $option4) = explode('|', $options_str);

    
    $csv_lines[] = [
        $q['type'],
        $q['question_text'],
        $option1,
        $option2,
         $option3,
        $option4,
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
ob_clean();          
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="quiz_' . $quiz_id . '_' . date('Y-m-d') . '.csv"');
echo $csv_content;
exit;
