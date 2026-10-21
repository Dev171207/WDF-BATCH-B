
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
    <title>Student Dashboard - StudentHub</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fbfaf8;
            color: #333;
        }

        header {
            background: white;
            padding: 25px 40px;
            border-bottom: 1px solid #ddd;
        }

        header h1 {
            color: #8c1515;
            margin: 0 0 8px;
            font-size: 28px;
        }

        header p {
            margin: 0;
            color: #666;
        }

        main {
            max-width: 900px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .welcome-card {
            background: white;
            padding: 35px;
            border-radius: 10px;
            border-top: 5px solid #8c1515;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .welcome-card h2 {
            color: #8c1515;
            margin-top: 0;
        }

        .welcome-card p {
            line-height: 1.7;
            color: #555;
        }

        .student-name {
            font-weight: bold;
            color: #8c1515;
        }

        .buttons {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .buttons a {
            text-decoration: none;
            padding: 12px 22px;
            border-radius: 5px;
            background: #8c1515;
            color: white;
        }

        .buttons a:hover {
            background: #650d0d;
        }

        .buttons .home {
            background: #555;
        }

        .buttons .home:hover {
            background: #333;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            header {
                padding: 22px;
            }

            header h1 {
                font-size: 23px;
            }

            .welcome-card {
                padding: 25px;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1>StudentHub</h1>
        <p>Student Dashboard</p>
    </header>

    <main>
        <div class="welcome-card">
            <h2>Welcome to StudentHub!</h2>

            <p>Hello, <span class="student-name"><?php echo htmlspecialchars($_SESSION["name"], ENT_QUOTES, "UTF-8"); ?></span></p>

            <p>You are successfully logged in as a student.</p>

            <p>Welcome to your student portal. You can use the navigation below to continue.</p>

            <div class="buttons">
                <a href="Home.html" class="home">Home</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </main>

    <footer>
        StudentHub | Dev Pandya
    </footer>
</body>
</html>
```