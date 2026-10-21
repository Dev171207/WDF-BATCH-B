
<?php
session_start();

require "db_mysqli.php";

if (isset($_SESSION["student_id"])) {
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
}

$conn->close();

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(session_name(), "", [
        "expires" => time() - 3600,
        "path" => $params["path"],
        "domain" => $params["domain"],
        "secure" => $params["secure"],
        "httponly" => $params["httponly"],
        "samesite" => "Lax"
    ]);
}

setcookie("remember_token", "", [
    "expires" => time() - 3600,
    "path" => "/",
    "secure" => !empty($_SERVER["HTTPS"]),
    "httponly" => true,
    "samesite" => "Lax"
]);

session_destroy();

header("Location: Login.html");
exit;
?>
