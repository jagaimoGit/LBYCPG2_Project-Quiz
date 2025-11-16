<?php
/**
 * Script to add 10 sample quizzes to the database
 * Run this once to populate sample data
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/QuizModel.php';
require_once __DIR__ . '/../models/QuestionModel.php';

// Find or create the user
$email = 'juan_porferio_napiza@dlsu.edu.ph';
$username = 'jpazi';
$user = UserModel::getByEmail($email);

if (!$user) {
    // Create the user if it doesn't exist
    $password_hash = password_hash('password123', PASSWORD_DEFAULT);
    $user_id = UserModel::create($username, $email, $password_hash, 'host');
    if ($user_id) {
        $user = UserModel::getById($user_id);
        echo "Created user: $username\n";
    } else {
        die("Failed to create user\n");
    }
} else {
    $user_id = $user['id'];
    echo "Using existing user: $username (ID: $user_id)\n";
}

// Sample quizzes data
$sample_quizzes = [
    [
        'title' => 'Science Fundamentals',
        'description' => 'Test your knowledge of basic science concepts including physics, chemistry, and biology.',
        'difficulty' => 'easy',
        'questions' => [
            ['type' => 'mcq', 'text' => 'What is the chemical symbol for water?', 'options' => ['H2O', 'CO2', 'O2', 'NaCl'], 'correct' => 'H2O', 'points' => 1],
            ['type' => 'mcq', 'text' => 'What planet is known as the Red Planet?', 'options' => ['Venus', 'Mars', 'Jupiter', 'Saturn'], 'correct' => 'Mars', 'points' => 1],
            ['type' => 'enum', 'text' => 'Name the process by which plants make their food.', 'correct' => 'Photosynthesis', 'points' => 2],
            ['type' => 'identification', 'text' => 'What is the smallest unit of matter?', 'correct' => 'Atom', 'points' => 2],
        ]
    ],
    [
        'title' => 'World History',
        'description' => 'Explore major events and figures from world history.',
        'difficulty' => 'medium',
        'questions' => [
            ['type' => 'mcq', 'text' => 'In which year did World War II end?', 'options' => ['1943', '1944', '1945', '1946'], 'correct' => '1945', 'points' => 2],
            ['type' => 'enum', 'text' => 'Name the ancient wonder of the world that was a lighthouse.', 'correct' => 'Lighthouse of Alexandria', 'points' => 3],
            ['type' => 'identification', 'text' => 'Who wrote "The Art of War"?', 'correct' => 'Sun Tzu', 'points' => 2],
            ['type' => 'mcq', 'text' => 'Which empire was ruled by Julius Caesar?', 'options' => ['Greek', 'Roman', 'Byzantine', 'Ottoman'], 'correct' => 'Roman', 'points' => 2],
        ]
    ],
    [
        'title' => 'Mathematics Challenge',
        'description' => 'Test your mathematical skills with algebra, geometry, and calculus problems.',
        'difficulty' => 'hard',
        'questions' => [
            ['type' => 'mcq', 'text' => 'What is the derivative of x²?', 'options' => ['x', '2x', 'x²', '2x²'], 'correct' => '2x', 'points' => 3],
            ['type' => 'enum', 'text' => 'What is the value of π (pi) to two decimal places?', 'correct' => '3.14', 'points' => 2],
            ['type' => 'identification', 'text' => 'What is the square root of 144?', 'correct' => '12', 'points' => 2],
            ['type' => 'mcq', 'text' => 'What is the area of a circle with radius 5?', 'options' => ['10π', '25π', '50π', '100π'], 'correct' => '25π', 'points' => 3],
        ]
    ],
    [
        'title' => 'Literature & Arts',
        'description' => 'Questions about famous books, authors, and artistic movements.',
        'difficulty' => 'medium',
        'questions' => [
            ['type' => 'mcq', 'text' => 'Who wrote "1984"?', 'options' => ['George Orwell', 'Aldous Huxley', 'Ray Bradbury', 'J.D. Salinger'], 'correct' => 'George Orwell', 'points' => 2],
            ['type' => 'enum', 'text' => 'Name the Shakespeare play about a Danish prince.', 'correct' => 'Hamlet', 'points' => 3],
            ['type' => 'identification', 'text' => 'What art movement is Pablo Picasso associated with?', 'correct' => 'Cubism', 'points' => 2],
            ['type' => 'mcq', 'text' => 'Which novel begins with "It was the best of times, it was the worst of times"?', 'options' => ['Great Expectations', 'A Tale of Two Cities', 'Oliver Twist', 'David Copperfield'], 'correct' => 'A Tale of Two Cities', 'points' => 3],
        ]
    ],
    [
        'title' => 'Geography Explorer',
        'description' => 'Test your knowledge of countries, capitals, and geographical features.',
        'difficulty' => 'easy',
        'questions' => [
            ['type' => 'mcq', 'text' => 'What is the capital of Australia?', 'options' => ['Sydney', 'Melbourne', 'Canberra', 'Perth'], 'correct' => 'Canberra', 'points' => 1],
            ['type' => 'enum', 'text' => 'Name the longest river in the world.', 'correct' => 'Nile', 'points' => 2],
            ['type' => 'identification', 'text' => 'What is the smallest country in the world?', 'correct' => 'Vatican City', 'points' => 2],
            ['type' => 'mcq', 'text' => 'Which continent is the Sahara Desert located in?', 'options' => ['Asia', 'Africa', 'Australia', 'South America'], 'correct' => 'Africa', 'points' => 1],
        ]
    ],
    [
        'title' => 'Computer Science Basics',
        'description' => 'Fundamentals of programming, algorithms, and computer systems.',
        'difficulty' => 'medium',
        'questions' => [
            ['type' => 'mcq', 'text' => 'What does HTML stand for?', 'options' => ['HyperText Markup Language', 'High-Level Text Markup', 'Home Tool Markup Language', 'Hyperlink Text Markup'], 'correct' => 'HyperText Markup Language', 'points' => 2],
            ['type' => 'enum', 'text' => 'Name a popular programming language created by Guido van Rossum.', 'correct' => 'Python', 'points' => 2],
            ['type' => 'identification', 'text' => 'What data structure follows LIFO (Last In First Out) principle?', 'correct' => 'Stack', 'points' => 3],
            ['type' => 'mcq', 'text' => 'What is the time complexity of binary search?', 'options' => ['O(n)', 'O(log n)', 'O(n²)', 'O(1)'], 'correct' => 'O(log n)', 'points' => 3],
        ]
    ],
    [
        'title' => 'Sports & Games',
        'description' => 'Questions about various sports, athletes, and game rules.',
        'difficulty' => 'easy',
        'questions' => [
            ['type' => 'mcq', 'text' => 'How many players are on a basketball team on the court?', 'options' => ['4', '5', '6', '7'], 'correct' => '5', 'points' => 1],
            ['type' => 'enum', 'text' => 'In which sport is the term "home run" used?', 'correct' => 'Baseball', 'points' => 2],
            ['type' => 'identification', 'text' => 'What is the maximum score in a single frame of bowling?', 'correct' => '10', 'points' => 2],
            ['type' => 'mcq', 'text' => 'Which country won the FIFA World Cup in 2018?', 'options' => ['Brazil', 'Germany', 'France', 'Argentina'], 'correct' => 'France', 'points' => 2],
        ]
    ],
    [
        'title' => 'Chemistry Essentials',
        'description' => 'Test your knowledge of chemical elements, compounds, and reactions.',
        'difficulty' => 'hard',
        'questions' => [
            ['type' => 'mcq', 'text' => 'What is the atomic number of carbon?', 'options' => ['4', '6', '8', '12'], 'correct' => '6', 'points' => 2],
            ['type' => 'enum', 'text' => 'Name the process where a solid turns directly into a gas.', 'correct' => 'Sublimation', 'points' => 3],
            ['type' => 'identification', 'text' => 'What is the pH of pure water?', 'correct' => '7', 'points' => 2],
            ['type' => 'mcq', 'text' => 'Which gas makes up approximately 78% of Earth\'s atmosphere?', 'options' => ['Oxygen', 'Nitrogen', 'Carbon Dioxide', 'Argon'], 'correct' => 'Nitrogen', 'points' => 2],
        ]
    ],
    [
        'title' => 'Music & Entertainment',
        'description' => 'Questions about music genres, instruments, and famous artists.',
        'difficulty' => 'medium',
        'questions' => [
            ['type' => 'mcq', 'text' => 'How many strings does a standard guitar have?', 'options' => ['4', '5', '6', '7'], 'correct' => '6', 'points' => 1],
            ['type' => 'enum', 'text' => 'Name the composer of "The Four Seasons".', 'correct' => 'Vivaldi', 'points' => 3],
            ['type' => 'identification', 'text' => 'What is the musical term for gradually getting louder?', 'correct' => 'Crescendo', 'points' => 2],
            ['type' => 'mcq', 'text' => 'Which instrument family does the violin belong to?', 'options' => ['Brass', 'Woodwind', 'String', 'Percussion'], 'correct' => 'String', 'points' => 2],
        ]
    ],
    [
        'title' => 'Biology & Life Sciences',
        'description' => 'Explore the world of living organisms, cells, and biological processes.',
        'difficulty' => 'hard',
        'questions' => [
            ['type' => 'mcq', 'text' => 'What is the powerhouse of the cell?', 'options' => ['Nucleus', 'Mitochondria', 'Ribosome', 'Golgi Apparatus'], 'correct' => 'Mitochondria', 'points' => 2],
            ['type' => 'enum', 'text' => 'Name the process by which DNA is copied.', 'correct' => 'Replication', 'points' => 3],
            ['type' => 'identification', 'text' => 'How many chambers does a human heart have?', 'correct' => '4', 'points' => 1],
            ['type' => 'mcq', 'text' => 'Which blood type is known as the universal donor?', 'options' => ['A', 'B', 'AB', 'O'], 'correct' => 'O', 'points' => 3],
        ]
    ],
];

// Add quizzes
$added_count = 0;
foreach ($sample_quizzes as $quiz_data) {
    $access_code = generate_access_code();
    $quiz_id = QuizModel::create(
        $user_id,
        $quiz_data['title'],
        $quiz_data['description'],
        $access_code,
        'light',
        $quiz_data['difficulty'],
        false, // not collaborative
        true   // active
    );
    
    if ($quiz_id) {
        // Add questions
        foreach ($quiz_data['questions'] as $q_data) {
            $options_json = null;
            $correct_answer = $q_data['correct'];
            
            if ($q_data['type'] === 'mcq') {
                $options_json = json_encode($q_data['options']);
            }
            
            QuestionModel::create(
                $quiz_id,
                $user_id,
                $q_data['type'],
                $q_data['text'],
                $options_json,
                $correct_answer,
                $q_data['points'],
                true // approved
            );
        }
        $added_count++;
        echo "Added quiz: {$quiz_data['title']} (ID: $quiz_id)\n";
    } else {
        echo "Failed to add quiz: {$quiz_data['title']}\n";
    }
}

echo "\nCompleted! Added $added_count quizzes.\n";

