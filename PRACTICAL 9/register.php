
<?php
require "db_mysqli.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    exit("Please submit the registration form.");
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$mobile = trim($_POST["mobile"] ?? "");
$password = $_POST["password"] ?? "";
$confirm = $_POST["confirmPassword"] ?? "";
$course = $_POST["course"] ?? "";
$year = $_POST["year"] ?? "";
$gender = $_POST["gender"] ?? "";

if (
    !preg_match("/^[A-Za-z ]+$/", $name) ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !preg_match("/^[0-9]{10}$/", $mobile) ||
    !preg_match("/^(?=.*[A-Z])(?=.*[0-9]).{8,}$/", $password) ||
    $password != $confirm ||
    !in_array($course, ["CSE", "CE", "IT", "AIML"]) ||
    !in_array($year, ["1", "2", "3", "4"]) ||
    !in_array($gender, ["Male", "Female", "Other"]) ||
    !isset($_POST["terms"])
) {
    exit("Invalid details. Please go back and check your form.");
}

$stmt = $conn->prepare(
    "SELECT student_id FROM students WHERE email = ?"
);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    exit("This email is already registered. Please use another email.");
}

$stmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO students
    (name, email, course, year, mobile, gender, password_hash)
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);

$yearNumber = (int)$year;
$stmt->bind_param(
    "sssisss",
    $name,
    $email,
    $course,
    $yearNumber,
    $mobile,
    $gender,
    $hashedPassword
);

if ($stmt->execute()) {
    echo "<h1>Registration Successful!</h1>";
    echo "<p>Your StudentHub account has been created.</p>";
    echo '<a href="Home.html">Return to StudentHub</a>';
} else {
    if ($stmt->errno == 1062) {
        echo "This email is already registered.";
    } else {
        echo "Registration failed. Please try again.";
    }
}

$stmt->close();
$conn->close();
?>
