<?php
session_start();

/*
  Self-contained takeQuiz.php
  - No external config.php required
  - Adjust DB credentials below if needed
*/

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "lsquiz";

// Create DB connection
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("DB connection error: " . $conn->connect_error);
}

// Determine selected quiz (GET param or session)
if (isset($_GET['quiz'])) {
    $quizParam = $_GET['quiz'];
    // sanitize to allow only letters, numbers and underscore and hyphen
    $quizName = preg_replace("/[^a-zA-Z0-9_\-]/", "", $quizParam);
    $_SESSION['selected_quiz'] = $quizName;
} elseif (isset($_SESSION['selected_quiz'])) {
    $quizName = $_SESSION['selected_quiz'];
} else {
    die("No quiz selected. Go back to <a href='SelectQuiz.php'>SelectQuiz.php</a>.");
}

// Verify that the table exists in the database
$tblEscaped = $conn->real_escape_string($quizName);
$check = $conn->query("SHOW TABLES LIKE '{$tblEscaped}'");
if (!$check || $check->num_rows == 0) {
    // table not found
    die("Quiz table <b>" . htmlspecialchars($quizName) . "</b> not found. Return to <a href='SelectQuiz.php'>quiz list</a>.");
}

// Use session keys scoped by quiz so multiple quizzes won't conflict
$idxKey = "quiz_{$quizName}_index";
$startedKey = "quiz_{$quizName}_started";
$answersKey = "quiz_{$quizName}_answers";

// Load questions once into session (so DB not re-queried every request)
if (!isset($_SESSION["quiz_{$quizName}_questions"])) {
    $questions = [];
    $sql = "SELECT ItemNumber, Answers, Question FROM `{$tblEscaped}` ORDER BY ItemNumber ASC";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $questions[] = $r;
        }
    }
    $_SESSION["quiz_{$quizName}_questions"] = $questions;
} else {
    $questions = $_SESSION["quiz_{$quizName}_questions"];
}

$totalQuestions = count($questions);
if ($totalQuestions === 0) {
    die("This quiz has no questions. Return to <a href='SelectQuiz.php'>quiz list</a>.");
}

// Initialize session state
if (!isset($_SESSION[$idxKey])) $_SESSION[$idxKey] = 0;
if (!isset($_SESSION[$answersKey])) $_SESSION[$answersKey] = [];

// Handle form actions (use POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start quiz
    if (isset($_POST['start'])) {
        $_SESSION[$startedKey] = true;
        $_SESSION[$idxKey] = 0;
        $_SESSION[$answersKey] = [];
        // redirect to avoid resubmit
        header("Location: takeQuiz.php");
        exit;
    }

    // Submit answer
    if (isset($_POST['submit'])) {
        $userAnswer = trim($_POST['answer'] ?? '');
        if ($userAnswer !== '') {
            // store answer indexed by ItemNumber (safer than numeric index)
            $currentIndex = $_SESSION[$idxKey];
            if (isset($questions[$currentIndex])) {
                $itemNo = $questions[$currentIndex]['ItemNumber'];
                $_SESSION[$answersKey][$itemNo] = $userAnswer;
            }
            $_SESSION[$idxKey]++; // move to next
        }
        header("Location: takeQuiz.php");
        exit;
    }

    // Skip
    if (isset($_POST['skip'])) {
        $_SESSION[$idxKey]++;
        header("Location: takeQuiz.php");
        exit;
    }

    // Reset / Cancel
    if (isset($_POST['reset'])) {
        unset($_SESSION[$startedKey], $_SESSION[$idxKey], $_SESSION[$answersKey], $_SESSION["quiz_{$quizName}_questions"], $_SESSION['selected_quiz']);
        header("Location: SelectQuiz.php");
        exit;
    }
}

// If quiz not started show Start screen
if (empty($_SESSION[$startedKey])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title><?php echo htmlspecialchars($quizName); ?> — Start Quiz</title>
        <style>
            body{font-family:Arial, sans-serif;background:#f4f4f4;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
            .card{background:#fff;padding:28px;border-radius:12px;box-shadow:0 8px 20px rgba(0,0,0,0.12);text-align:center;width:420px;}
            .start-btn{padding:12px 26px;font-size:16px;border-radius:8px;background:#3f51b5;color:#fff;border:none;cursor:pointer}
        </style>
    </head>
    <body>
        <div class="card">
            <h2><?php echo htmlspecialchars($quizName); ?></h2>
            <p>Ready to start the quiz? Questions will appear one at a time.</p>
            <form method="post">
                <button class="start-btn" name="start" type="submit">Start Quiz</button>
            </form>
            <p style="margin-top:12px"><a href="SelectQuiz.php">Back to quiz list</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Quiz has started — check if finished
$currentIndex = intval($_SESSION[$idxKey]);
if ($currentIndex >= $totalQuestions) {
    // Quiz complete — simple summary of answers
    $storedAnswers = $_SESSION[$answersKey] ?? [];
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title><?php echo htmlspecialchars($quizName); ?> — Completed</title>
        <style>
            body{font-family:Arial, sans-serif;background:#f4f4f4;padding:30px;}
            .card{background:#fff;padding:20px;border-radius:10px;max-width:800px;margin:20px auto;box-shadow:0 8px 18px rgba(0,0,0,0.08)}
            table{width:100%;border-collapse:collapse}
            th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}
            .actions{margin-top:16px}
            .btn{padding:8px 12px;border-radius:6px;border:none;cursor:pointer}
            .btn-primary{background:#3f51b5;color:#fff}
            .btn-danger{background:#f44336;color:#fff}
        </style>
    </head>
    <body>
        <div class="card">
            <h2>Quiz Completed: <?php echo htmlspecialchars($quizName); ?></h2>
            <p>You reached the end of the quiz. Below are your answers (ItemNumber => Answer):</p>

            <table>
                <thead><tr><th>ItemNumber</th><th>Question</th><th>Your answer</th></tr></thead>
                <tbody>
                    <?php
                    foreach ($questions as $q) {
                        $item = $q['ItemNumber'];
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($item) . "</td>";
                        echo "<td>" . htmlspecialchars($q['Question']) . "</td>";
                        echo "<td>" . htmlspecialchars($storedAnswers[$item] ?? '') . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>

            <div class="actions">
                <form method="post" style="display:inline">
                    <button class="btn btn-primary" name="reset" type="submit">Finish & Return to List</button>
                </form>
                <form method="post" style="display:inline;margin-left:8px">
                    <button class="btn btn-danger" name="reset" type="submit">Reset & Return</button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Otherwise show current question
$currentQ = $questions[$currentIndex];
$displayNumber = $currentIndex + 1;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($quizName); ?> — Question <?php echo $displayNumber; ?></title>
    <style>
        body{font-family:Arial, sans-serif;background:#f5f5f5;padding:28px;}
        .card{background:#fff;padding:22px;border-radius:10px;max-width:760px;margin:12px auto;box-shadow:0 10px 22px rgba(0,0,0,0.08)}
        .qtitle{font-size:18px;margin-bottom:12px}
        input[type="text"]{width:100%;padding:12px;border-radius:8px;border:1px solid #ccc;font-size:15px}
        .controls{display:flex;justify-content:space-between;margin-top:14px}
        .btn{padding:10px 16px;border-radius:8px;border:none;cursor:pointer}
        .btn-submit{background:#4caf50;color:#fff}
        .btn-skip{background:#f44336;color:#fff}
        .small{font-size:13px;color:#666;margin-top:8px}
    </style>
</head>
<body>
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <h3><?php echo htmlspecialchars($quizName); ?></h3>
            <div class="small">Question <?php echo $displayNumber; ?> of <?php echo $totalQuestions; ?></div>
        </div>

        <div class="qtitle"><?php echo nl2br(htmlspecialchars($currentQ['Question'])); ?></div>

        <form method="post" onsubmit="return true;">
            <input type="text" name="answer" id="answerBox" placeholder="Type your answer here" autocomplete="off">

            <div class="controls">
                <button type="submit" name="skip" class="btn btn-skip">Skip →</button>
                <button type="submit" name="submit" id="submitBtn" class="btn btn-submit" disabled>Submit →</button>
            </div>
        </form>
        <div style="margin-top:12px">
            <a href="SelectQuiz.php">Back to quiz list</a>
        </div>
    </div>

<script>
// enable Submit only when textbox has content
const answerBox = document.getElementById('answerBox');
const submitBtn = document.getElementById('submitBtn');

answerBox.addEventListener('input', function(){
    submitBtn.disabled = answerBox.value.trim() === '';
});
</script>
</body>
</html>
