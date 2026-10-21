
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Failed - StudentHub</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: Arial, sans-serif;
            background: #fbfaf8;
            color: #333;
        }

        header {
            background: white;
            padding: 22px 40px;
            border-bottom: 1px solid #e5e5e5;
        }

        header h1 {
            margin: 0;
            color: #8c1515;
            font-size: 27px;
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 25px;
        }

        .error-card {
            width: 100%;
            max-width: 440px;
            padding: 35px;
            text-align: center;
            background: white;
            border-radius: 10px;
            border-top: 5px solid #8c1515;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .error-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 18px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            background: #fbeaea;
            color: #8c1515;
            font-size: 30px;
        }

        .error-card h2 {
            margin: 0 0 12px;
            color: #8c1515;
        }

        .error-card p {
            line-height: 1.6;
            color: #666;
        }

        .button {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 24px;
            background: #8c1515;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .button:hover {
            background: #650d0d;
        }

        footer {
            padding: 18px;
            text-align: center;
            color: #777;
            font-size: 14px;
        }

        @media (max-width: 500px) {
            header {
                padding: 20px;
            }

            .error-card {
                padding: 25px 20px;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1>StudentHub</h1>
    </header>

    <main>
        <section class="error-card">
            <div class="error-icon" aria-hidden="true">!</div>
            <h2>Login Failed</h2>
            <p>The email or password you entered is incorrect. Please check your details and try again.</p>
            <a href="Login.html" class="button">Back to Login</a>
        </section>
    </main>

    <footer>
        StudentHub | Dev Pandya
    </footer>
</body>
</html>
```