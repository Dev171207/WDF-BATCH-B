
<?php
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    exit("Please submit the contact form first.");
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$message = trim($_POST["message"] ?? "");

$errors = [];

if ($name == "") {
    $errors[] = "Please enter your name.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email.";
}

if ($message == "") {
    $errors[] = "Please enter your message.";
}

if (!empty($errors)) {
    $title = "Form Submission Failed";
    $messageText = implode("<br>", array_map("htmlspecialchars", $errors));
} else {
    $name = strip_tags($name);
    $message = strip_tags($message);

    $file = fopen("contacts.csv", "a");

    if ($file == false) {
        exit("Unable to save your details.");
    }

    if (flock($file, LOCK_EX)) {
        fputcsv($file, [$name, $email, $message]);
        flock($file, LOCK_UN);
        fclose($file);

        $title = "Contact Form Submitted Successfully!";
        $messageText = "Thank you! Your contact details have been saved.";
    } else {
        fclose($file);
        $title = "Unable to Submit";
        $messageText = "Unable to save your details. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Hub - Contact Status</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fbfaf8;
            color: #222;
        }

        header {
            background: white;
            border-top: 5px solid #8c1515;
            padding: 20px;
            text-align: center;
        }

        header h2 {
            color: #8c1515;
        }

        .box {
            background: white;
            width: 450px;
            max-width: 85%;
            margin: 80px auto;
            padding: 35px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 5px 20px #ddd;
        }

        .box h1 {
            color: #8c1515;
        }

        .box p {
            line-height: 1.6;
        }

        a {
            display: inline-block;
            margin: 10px;
            padding: 12px 20px;
            background: #8c1515;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        a:hover {
            background: #650d0d;
        }

        footer {
            background: #650d0d;
            color: white;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>

<header>
    <h2>STUDENT HUB</h2>
</header>

<div class="box">
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <p><?php echo $messageText; ?></p>
    <a href="Contact.html">Back to Contact</a>
    <a href="Home.html">Home</a>
</div>

<footer>
    <h3>STUDENT HUB PORTAL</h3>
    <p>Dev Pandya</p>
</footer>

</body>
</html>
