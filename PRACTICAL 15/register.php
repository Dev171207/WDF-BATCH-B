
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
    $message = "Invalid details. Please check your registration form.";
    $success = false;
} else {
    $stmt = $conn->prepare(
        "SELECT student_id FROM students WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $message = "This email is already registered. Please use another email.";
        $success = false;
        $stmt->close();
    } else {
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
            $message = "Your StudentHub account has been created successfully.";
            $success = true;
        } else {
            if ($stmt->errno == 1062) {
                $message = "This email is already registered.";
            } else {
                $message = "Registration failed. Please try again.";
            }
            $success = false;
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Registration</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: #fbfaf8;
            color: #292929;
            display: flex;
            flex-direction: column;
        }

        header {
            background: #8c1515;
            color: white;
            padding: 22px 8%;
        }

        header h2 {
            margin: 0;
            font-size: 25px;
            letter-spacing: 1px;
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 45px 18px;
        }

        .result-card {
            background: white;
            width: 100%;
            max-width: 520px;
            padding: 38px 32px;
            border: 1px solid #e8e2dc;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        }

        .symbol {
            width: 65px;
            height: 65px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            background: <?php echo $success ? "#e8f5e9" : "#fbe9e7"; ?>;
            color: <?php echo $success ? "#24743a" : "#a32620"; ?>;
        }

        h1 {
            margin: 0 0 14px;
            font-size: 27px;
            color: <?php echo $success ? "#8c1515" : "#a32620"; ?>;
        }

        p {
            line-height: 1.7;
            color: #666;
            margin-bottom: 26px;
        }

        .button {
            display: inline-block;
            padding: 13px 23px;
            background: #8c1515;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 15px;
            transition: background 0.2s;
        }

        .button:hover {
            background: #650d0d;
        }

        footer {
            text-align: center;
            padding: 18px;
            color: #777;
            font-size: 13px;
            border-top: 1px solid #e8e2dc;
        }

        @media (max-width: 480px) {
            .result-card {
                padding: 30px 20px;
            }

            h1 {
                font-size: 23px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h2>STUDENT HUB</h2>
    </header>

    <main>
        <div class="result-card">
            <div class="symbol">
                <?php echo $success ? "&#10003;" : "&#33;"; ?>
            </div>

            <h1>
                <?php echo $success ? "Registration Successful!" : "Registration Unsuccessful"; ?>
            </h1>

            <p><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></p>

            <?php if ($success) { ?>
                <a class="button" href="Home.html">Return to StudentHub</a>
            <?php } else { ?>
                <a class="button" href="Register.html">Try Again</a>
            <?php } ?>
        </div>
    </main>

    <footer>
        StudentHub | Dev Pandya
    </footer>
</body>
</html>
```