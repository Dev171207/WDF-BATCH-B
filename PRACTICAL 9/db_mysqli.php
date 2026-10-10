
<?php
$conn = new mysqli("localhost", "root", "", "studenthub");

if ($conn->connect_error) {
    exit("Database connection failed.");
}

$conn->set_charset("utf8mb4");
?>
