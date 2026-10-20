
<?php
session_start();

require "db_mysqli.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: Login.html");
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

$stmt = $conn->prepare(
    "SELECT student_id, name, email, password_hash, role
     FROM students WHERE email = ?"
);

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user["password_hash"])) {
        session_regenerate_id(true);

        $_SESSION["student_id"] = $user["student_id"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["role"] = $user["role"];
        $_SESSION["last_activity"] = time();

        if (isset($_POST["remember_me"])) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash("sha256", $token);
            $expiry = date("Y-m-d H:i:s", time() + 30 * 24 * 60 * 60);

            $save = $conn->prepare(
                "UPDATE students
                 SET remember_token_hash = ?,
                     remember_token_expires = ?
                 WHERE student_id = ?"
            );

            $save->bind_param(
                "ssi",
                $tokenHash,
                $expiry,
                $user["student_id"]
            );

            $save->execute();
            $save->close();

            setcookie("remember_token", $token, [
                "expires" => time() + 30 * 24 * 60 * 60,
                "path" => "/",
                "secure" => !empty($_SERVER["HTTPS"]),
                "httponly" => true,
                "samesite" => "Lax"
            ]);
        }

        $stmt->close();
        $conn->close();

        if ($user["role"] == "admin") {
            header("Location: admin.php");
        } else {
            header("Location: dashboard.php");
        }

        exit;
    }
}

$stmt->close();
$conn->close();

exit("Invalid email or password. <a href='Login.html'>Try again</a>");
?>
