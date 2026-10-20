
<?php
session_start();

require_once "db_mysqli.php";

if (!isset($_SESSION["student_id"]) &&
    isset($_COOKIE["remember_token"])) {

    $token = $_COOKIE["remember_token"];

    if (preg_match('/^[a-f0-9]{64}$/', $token)) {
        $tokenHash = hash("sha256", $token);

        $stmt = $conn->prepare(
            "SELECT student_id, name, role
             FROM students
             WHERE remember_token_hash = ?
             AND remember_token_expires > NOW()"
        );

        $stmt->bind_param("s", $tokenHash);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            $newToken = bin2hex(random_bytes(32));
            $newHash = hash("sha256", $newToken);
            $expiry = date(
                "Y-m-d H:i:s",
                time() + 30 * 24 * 60 * 60
            );

            $update = $conn->prepare(
                "UPDATE students
                 SET remember_token_hash = ?,
                     remember_token_expires = ?
                 WHERE student_id = ?"
            );

            $update->bind_param(
                "ssi",
                $newHash,
                $expiry,
                $user["student_id"]
            );

            $update->execute();
            $update->close();

            session_regenerate_id(true);

            $_SESSION["student_id"] = $user["student_id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["last_activity"] = time();

            setcookie("remember_token", $newToken, [
                "expires" => time() + 30 * 24 * 60 * 60,
                "path" => "/",
                "secure" => !empty($_SERVER["HTTPS"]),
                "httponly" => true,
                "samesite" => "Lax"
            ]);
        }

        $stmt->close();
    }
}

if (!isset($_SESSION["student_id"])) {
    $conn->close();
    header("Location: Login.html");
    exit;
}

if (!isset($_SESSION["last_activity"]) ||
    time() - $_SESSION["last_activity"] > 900) {

    $id = $_SESSION["student_id"];

    $stmt = $conn->prepare(
        "UPDATE students
         SET remember_token_hash = NULL,
             remember_token_expires = NULL
         WHERE student_id = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION = [];
    session_destroy();

    setcookie("remember_token", "", [
        "expires" => time() - 3600,
        "path" => "/",
        "secure" => !empty($_SERVER["HTTPS"]),
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    $conn->close();
    exit("Session timed out. <a href='Login.html'>Login again</a>");
}

$_SESSION["last_activity"] = time();
?>
