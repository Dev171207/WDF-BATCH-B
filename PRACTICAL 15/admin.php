```php
<?php
require "auth.php";

if ($_SESSION["role"] != "admin") {
    http_response_code(403);
    exit("Access denied. Admins only.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StudentHub</title>

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
            padding: 24px 40px;
            border-bottom: 1px solid #e5e5e5;
        }

        header h1 {
            margin: 0 0 8px;
            color: #8c1515;
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

        .admin-card {
            background: white;
            padding: 35px;
            border-radius: 10px;
            border-top: 5px solid #8c1515;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .admin-card h2 {
            color: #8c1515;
            margin-top: 0;
        }

        .admin-card p {
            color: #555;
            line-height: 1.7;
        }

        .admin-name {
            color: #8c1515;
            font-weight: bold;
        }

        .buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .buttons a {
            display: inline-block;
            padding: 12px 22px;
            background: #8c1515;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .buttons a:hover {
            background: #650d0d;
        }

        .buttons .logout {
            background: #555;
        }

        .buttons .logout:hover {
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

            .admin-card {
                padding: 25px;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1>StudentHub</h1>
        <p>Admin Portal</p>
    </header>

    <main>
        <section class="admin-card">
            <h2>Welcome to the Admin Dashboard</h2>

            <p>Welcome, <span class="admin-name"><?php echo htmlspecialchars($_SESSION["name"], ENT_QUOTES, "UTF-8"); ?></span></p>

            <p>You have administrator access to StudentHub.</p>

            <p>Use the dashboard to manage and monitor the portal.</p>

            <div class="buttons">
                <a href="admin_dashboard.php">Open Admin Dashboard</a>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </section>
    </main>

    <footer>
        StudentHub | Dev Pandya
    </footer>
</body>
</html>
```