
<?php
require "db.php";

echo "<h1>StudentHub Database Connected Successfully!</h1>";

$stmt = $conn->prepare(
    "SELECT name, email, course FROM students WHERE course = :course"
);

$course = "CSE";

$stmt->execute(["course" => $course]);

echo "<h2>CSE Students</h2>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<p>";
    echo htmlspecialchars($row["name"]) . " - ";
    echo htmlspecialchars($row["email"]) . " - ";
    echo htmlspecialchars($row["course"]);
    echo "</p>";
}
?>
