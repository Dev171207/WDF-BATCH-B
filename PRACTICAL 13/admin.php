
<?php
require "auth.php";

if ($_SESSION["role"] != "admin") {
    http_response_code(403);
    exit("Access denied. Admins only.");
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h1>StudentHub Admin Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?></p>
    <p>You have administrator access.</p>
    <a href="logout.php">Logout</a>
</body>
</html>
