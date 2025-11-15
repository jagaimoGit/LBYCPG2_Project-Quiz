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

// Get all tables (except system tables if needed)
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

// Check if a table is selected
$selectedTable = isset($_POST["table"]) ? $_POST["table"] : null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Imported Quiz Tables</title>
    <style>
        body { font-family: Arial; background:#f5f5f5; padding:40px; }
        .container { background:white; padding:30px; border-radius:8px; width:600px; margin:auto;
                     box-shadow:0 0 10px rgba(0,0,0,0.1);}
        table { border-collapse: collapse; width:100%; margin-top:20px; }
        table, th, td { border:1px solid #ccc; }
        th, td { padding:10px; text-align:left; }
        select, button { padding:8px; margin-top:10px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Select Quiz Table to View</h2>

    <form method="post">
        <label>Select Table:</label><br>
        <select name="table" required>
            <option value="">-- Choose a table --</option>
            <?php foreach ($tables as $tbl): ?>
                <option value="<?php echo $tbl; ?>" <?php if ($selectedTable == $tbl) echo "selected"; ?>>
                    <?php echo $tbl; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <br>
        <button type="submit">View Table</button>
    </form>

<?php
// If a table is selected, show its rows
if ($selectedTable) {
    echo "<h3>Viewing Table: <b>$selectedTable</b></h3>";

    $sql = "SELECT * FROM `$selectedTable`";
    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        echo "<table><tr>";
        // Print column names
        while ($field = $res->fetch_field()) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";

        // Print rows
        while ($row = $res->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data found in this table.</p>";
    }
}
?>

</div>
</body>
</html>
