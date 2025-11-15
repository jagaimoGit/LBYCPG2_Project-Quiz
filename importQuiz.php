<?php
// Database connection
$host = "localhost";
$user = "root"; // change if needed
$pass = "";     // change if needed
$dbname = "lsquiz";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST["import"])) {
    $tableName = trim($_POST["table_name"]);
    if (empty($tableName)) {
        echo "<p style='color:red;'>Please enter a table name.</p>";
    } elseif (isset($_FILES["file"]) && $_FILES["file"]["error"] == 0) {
        $filename = $_FILES["file"]["tmp_name"];

        // Sanitize table name (avoid SQL injection / invalid chars)
        $tableName = preg_replace("/[^a-zA-Z0-9_]/", "", $tableName);

        // Create the new table
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS `$tableName` (
                `ItemNumber` INT(11) NOT NULL AUTO_INCREMENT,
                `Answers` VARCHAR(255) NOT NULL,
                `Question` TEXT NOT NULL,
                PRIMARY KEY (`ItemNumber`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";

        if (!$conn->query($createTableSQL)) {
            die("<p style='color:red;'>Error creating table: " . $conn->error . "</p>");
        }

        // Read CSV
        if (($handle = fopen($filename, "r")) !== FALSE) {
            $row = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($row == 0) { // Skip header
                    $row++;
                    continue;
                }

                if (count($data) >= 3) {
                    $itemNumber = trim($data[0]);
                    $answers = trim($data[1]);
                    $question = trim($data[2]);

                    // Insert data
                    $stmt = $conn->prepare("INSERT INTO `$tableName` (ItemNumber, Answers, Question) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $itemNumber, $answers, $question);

                    if (!$stmt->execute()) {
                        echo "<p style='color:red;'>Error inserting Item #$itemNumber: " . $stmt->error . "</p>";
                    }
                }
                $row++;
            }
            fclose($handle);
            echo "<p style='color:green;'>Table <b>$tableName</b> created and CSV imported successfully!</p>";
        } else {
            echo "<p style='color:red;'>Failed to open CSV file.</p>";
        }
    } else {
        echo "<p style='color:red;'>Please upload a valid CSV file.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Import CSV - Create New Table</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f4f4f4; }
        form { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input[type=text], input[type=file], input[type=submit] { margin-top: 10px; display: block; }
    </style>
</head>
<body>
    <h2>Import CSV and Create New Table</h2>
    <form method="post" enctype="multipart/form-data">
        <label>Quiz Name:</label>
        <input type="text" name="table_name" placeholder="e.g. math_quiz" required>

        <label>Select CSV File:</label>
        <input type="file" name="file" accept=".csv" required>

        <input type="submit" name="import" value="Import CSV & Create Table">
    </form>
</body>
</html>
