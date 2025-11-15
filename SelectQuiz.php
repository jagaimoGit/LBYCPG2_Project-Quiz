<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "lsquiz";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all tables
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Select Quiz</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 40px;
        }

        h2 {
            margin-bottom: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }

        .tile {
            padding: 20px;
            border-radius: 10px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 150px;
            text-decoration: none;
            transition: 0.2s ease-in-out;
        }

        .tile:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 18px rgba(0,0,0,0.2);
        }

        .quiz-title {
            font-size: 18px;
            font-weight: bold;
        }

        .quiz-desc {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body>

<h2>Select a Quiz</h2>

<div class="grid">
    <?php foreach ($tables as $tbl): ?>

        <?php 
        // Choose color consistently based on table name
        $colors = [
            "#3f51b5", "#009688", "#ff5722", 
            "#9c27b0", "#607d8b", "#e91e63",
            "#4caf50", "#f44336"
        ];
        $color = $colors[crc32($tbl) % count($colors)];
        ?>

        <a class="tile" 
           href="takeQuiz.php?quiz=<?php echo urlencode($tbl); ?>" 
           style="background: <?php echo $color; ?>;">
           
            <div class="quiz-title"><?php echo htmlspecialchars($tbl); ?></div>
            <div class="quiz-desc">Click to start this quiz</div>
        </a>

    <?php endforeach; ?>
</div>

</body>
</html>
