<?php
require_once 'config/config.php';

// Direct database connection to see raw data
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current date
$currentDate = date('Y-m-d');
echo "<h1>Database Direct Check</h1>";
echo "<p>Current Date: $currentDate</p>";

// Get table structure
echo "<h2>Medicines Table Structure</h2>";
$result = $conn->query("DESCRIBE medicines");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row["Field"] . "</td>";
    echo "<td>" . $row["Type"] . "</td>";
    echo "<td>" . $row["Null"] . "</td>";
    echo "<td>" . $row["Key"] . "</td>";
    echo "<td>" . $row["Default"] . "</td>";
    echo "<td>" . $row["Extra"] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Get all medicines
echo "<h2>All Medicines</h2>";
$result = $conn->query("SELECT * FROM medicines");
echo "<table border='1'><tr>";
// Get field names
$fields = $result->fetch_fields();
foreach($fields as $field) {
    echo "<th>" . $field->name . "</th>";
}
echo "</tr>";
// Get data
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    foreach($row as $key => $value) {
        echo "<td>" . $value . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Try different date formats
echo "<h2>Date Format Tests</h2>";
$result = $conn->query("SELECT id, name, expiration_date, 
                        STR_TO_DATE(expiration_date, '%m/%d/%Y') as parsed_date1,
                        STR_TO_DATE(expiration_date, '%Y-%m-%d') as parsed_date2,
                        DATE_FORMAT(STR_TO_DATE(expiration_date, '%m/%d/%Y'), '%Y-%m-%d') as formatted_date,
                        CASE 
                            WHEN STR_TO_DATE(expiration_date, '%m/%d/%Y') <= '$currentDate' THEN 'EXPIRED'
                            WHEN STR_TO_DATE(expiration_date, '%m/%d/%Y') <= DATE_ADD('$currentDate', INTERVAL 30 DAY) THEN 'EXPIRING SOON'
                            ELSE 'OK'
                        END as status
                        FROM medicines");

echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Expiration Date (Raw)</th><th>Parsed as m/d/Y</th><th>Parsed as Y-m-d</th><th>Formatted Date</th><th>Status</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row["id"] . "</td>";
    echo "<td>" . $row["name"] . "</td>";
    echo "<td>" . $row["expiration_date"] . "</td>";
    echo "<td>" . $row["parsed_date1"] . "</td>";
    echo "<td>" . $row["parsed_date2"] . "</td>";
    echo "<td>" . $row["formatted_date"] . "</td>";
    echo "<td>" . $row["status"] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Close connection
$conn->close();
?>
