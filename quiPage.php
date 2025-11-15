<?php
session_start();

// Ensure a quiz was selected
if (!isset($_GET["quiz"])) {
    die("Quiz not specified.");
}

$quizName = $_GET["quiz"];

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "lsquiz";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Load all questions from the quiz table
$questions = [];
$q = $conn->query("SELECT ItemNumber, Answers, Question FROM `$quizName` ORDER BY ItemNumber ASC");
while ($row = $q->fetch_assoc()) {
    $questions[] = $row;
}

$total = count($questions);
if ($total == 0) {
    die("This quiz has no questions.");
}

// Track current question using session
if (!isset($_SESSION["current_question"])) {
    $_SESSION["current_question"] = 0;
}

// Handle Submit
if (isset($_POST["submit"])) {
    $answer = trim($_POST["answer"]);

    if ($answer !== "") {
        // Store answer (optional — you may expand this)
        $_SESSION["answers"][$_SESSION["current_question"]] = $answer;

        // Go to next question
        $_SESSION["current_question"]++;
    }
}

// Handle Skip
if (isset($_POST["skip"])) {
    $_SESSION["current_question"]++;
}

// Quiz finished
if ($_SESSION["current_question"] >= $total) {
    echo "<h2>Quiz Complete!</h2>";
    echo "<p>You have reached the end of the quiz.</p>";
    echo "<a href='selectQuiz.php'>Return to Quiz List</a>";

    // Reset for next quiz (optional)
    session_destroy();

    exit;
}

// Get current question
$index = $_SESSION["current_question"];
$current = $questions[$index];
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $quizName; ?> - Question</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 50px;
        }

        .question-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 0 15px rgba(0,0,0,0.15);
        }

        .q-text {
            font-size: 20px;
            margin-bottom: 20px;
        }

        input[type=text] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            border: 1px solid #aaa;
            margin-bottom: 20px;
        }

        .controls {
            display: flex;
            justify-content: space-between;
        }

        .btn {
            padding: 12px 25px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .submit-btn {
            background: #4caf50;
            color: white;
        }

        .submit-btn:disabled {
            background: #95d3a1;
            cursor: not-allowed;
        }

        .skip-btn {
            background: #f44336;
            color: white;
        }
    </style>
</head>
<body>

<div class="question-box">
    <h3>Question <?php echo ($index+1) . " of $total"; ?></h3>

    <div class="q-text">
        <?php echo htmlspecialchars($current["Question"]); ?>
    </div>

    <form method="post">
        <input type="text" name="answer" id="answerBox" placeholder="Type your answer here">

        <div class="controls">
            <button type="submit" name="skip" class="btn skip-btn">
                Skip →
            </button>

            <button type="submit" name="submit" id="submitBtn" class="btn submit-btn" disabled>
                Submit →
            </button>
        </div>
    </form>
</div>

<script>
// Enable the Submit button ONLY when input is not empty
const answerBox = document.getElementById("answerBox");
const submitBtn = document.getElementById("submitBtn");

answerBox.addEventListener("input", () => {
    submitBtn.disabled = answerBox.value.trim() === "";
});
</script>

</body>
</html>
