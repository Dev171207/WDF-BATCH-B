

<?php
require "auth.php";

if ($_SESSION["role"] != "student") {
    http_response_code(403);
    exit("Access denied. Students only.");
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
</head>
<body>
    <h1>Welcome to StudentHub</h1>
    <p>Hello, <?php echo htmlspecialchars($_SESSION["name"]); ?></p>
    <p>You are logged in as a student.</p>
    <a href="logout.php">Logout</a>
</body>
</html>
