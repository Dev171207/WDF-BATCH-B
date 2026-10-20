<?php
require "db_mysqli.php";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$message = "";
$messageType = "success";
$statusMessages = [
    "added" => "Student added successfully.",
    "updated" => "Student updated successfully.",
    "deleted" => "Student deleted successfully.",
    "error" => "Operation failed. Check the details and try again."
];

if (isset($_GET["status"]) && isset($statusMessages[$_GET["status"]])) {
    $message = $statusMessages[$_GET["status"]];
    if ($_GET["status"] === "error") {
        $messageType = "error";
    }
}

$courses = ["CSE", "CE", "IT", "AIML"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "add" || $action === "update") {
        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $course = $_POST["course"] ?? "";
        $year = (string)($_POST["year"] ?? "");

        if (
            $name === "" ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !in_array($course, $courses, true) ||
            !in_array($year, ["1", "2", "3", "4"], true)
        ) {
            header("Location: students.php?status=error");
            exit;
        }

        try {
            $yearNumber = (int)$year;

            if ($action === "add") {
                $stmt = $conn->prepare(
                    "INSERT INTO students (name, email, course, year) VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param("sssi", $name, $email, $course, $yearNumber);
            } else {
                $id = (int)($_POST["student_id"] ?? 0);
                if ($id < 1) {
                    header("Location: students.php?status=error");
                    exit;
                }

                $stmt = $conn->prepare(
                    "UPDATE students SET name = ?, email = ?, course = ?, year = ? WHERE student_id = ?"
                );
                $stmt->bind_param("sssii", $name, $email, $course, $yearNumber, $id);
            }

            $stmt->execute();
            $stmt->close();
            header("Location: students.php?status=" . ($action === "add" ? "added" : "updated"));
            exit;
        } catch (mysqli_sql_exception $e) {
            header("Location: students.php?status=error");
            exit;
        }
    }

    if ($action === "delete") {
        $id = (int)($_POST["student_id"] ?? 0);

        if ($id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $deleted = $stmt->affected_rows > 0;
                $stmt->close();

                header("Location: students.php?status=" . ($deleted ? "deleted" : "error"));
                exit;
            } catch (mysqli_sql_exception $e) {
                // A student linked to registrations may not be deletable until those registrations are handled.
                header("Location: students.php?status=error");
                exit;
            }
        }

        header("Location: students.php?status=error");
        exit;
    }
}

$editStudent = null;
if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    if ($id > 0) {
        $stmt = $conn->prepare(
            "SELECT student_id, name, email, course, year FROM students WHERE student_id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $editStudent = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$search = trim($_GET["search"] ?? "");
$filter = $_GET["course"] ?? "";
if (!in_array($filter, ["", "CSE", "CE", "IT", "AIML"], true)) {
    $filter = "";
}

$perPage = 10;
$page = max(1, (int)($_GET["page"] ?? 1));
$searchValue = "%" . $search . "%";

$where = "(name LIKE ? OR email LIKE ? OR course LIKE ?)";
if ($filter !== "") {
    $where .= " AND course = ?";
}

$countSql = "SELECT COUNT(*) AS total FROM students WHERE " . $where;
$countStmt = $conn->prepare($countSql);
if ($filter !== "") {
    $countStmt->bind_param("ssss", $searchValue, $searchValue, $searchValue, $filter);
} else {
    $countStmt->bind_param("sss", $searchValue, $searchValue, $searchValue);
}
$countStmt->execute();
$totalRows = (int)$countStmt->get_result()->fetch_assoc()["total"];
$countStmt->close();

$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT student_id, name, email, course, year FROM students WHERE " . $where .
       " ORDER BY student_id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($filter !== "") {
    $stmt->bind_param("ssssii", $searchValue, $searchValue, $searchValue, $filter, $perPage, $offset);
} else {
    $stmt->bind_param("sssii", $searchValue, $searchValue, $searchValue, $perPage, $offset);
}
$stmt->execute();
$students = $stmt->get_result();

function pageUrl($pageNumber, $search, $filter) {
    return "students.php?" . http_build_query([
        "search" => $search,
        "course" => $filter,
        "page" => $pageNumber
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - StudentHub</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #fbfaf8; color: #222; }
        header { background: white; border-top: 5px solid #8c1515; border-bottom: 1px solid #ddd; padding: 20px; text-align: center; }
        h1, h2 { color: #8c1515; }
        main { width: 90%; max-width: 1100px; margin: 30px auto; min-height: 500px; }
        .panel { background: white; padding: 18px; margin: 20px 0; border: 1px solid #e2dfdc; border-radius: 7px; }
        input, select { padding: 10px; margin: 5px; border: 1px solid #ccc; border-radius: 4px; }
        button, .edit, .page-link { display: inline-block; background: #8c1515; color: white; border: none; padding: 9px 13px; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .delete, .secondary { background: #555; }
        .message { background: #eee; padding: 12px; border-radius: 4px; }
        .message.error { background: #ffebee; color: #8c1515; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #8c1515; color: white; }
        td form { display: inline; }
        .pagination { display: flex; gap: 7px; flex-wrap: wrap; margin-top: 18px; align-items: center; }
        .current { background: #222; }
        .muted { color: #666; font-size: 14px; }
        footer { background: #650d0d; color: white; text-align: center; padding: 20px; }
        @media(max-width: 700px) { table { font-size: 12px; } th, td { padding: 5px; } input, select { max-width: 100%; } }
    </style>
</head>
<body>
<header>
    <h1>STUDENT HUB</h1>
    <p>Student Management System</p>
    <a href="Home.html">Home</a>
</header>

<main>
    <?php if ($message !== ""): ?>
        <p class="message <?php echo h($messageType); ?>"><?php echo h($message); ?></p>
    <?php endif; ?>

    <section class="panel">
        <h2><?php echo $editStudent ? "Edit Student" : "Add Student"; ?></h2>
        <form method="post" action="students.php">
            <input type="hidden" name="action" value="<?php echo $editStudent ? "update" : "add"; ?>">
            <?php if ($editStudent): ?>
                <input type="hidden" name="student_id" value="<?php echo (int)$editStudent["student_id"]; ?>">
            <?php endif; ?>
            <input type="text" name="name" placeholder="Student Name" maxlength="100" required
                   value="<?php echo h($editStudent["name"] ?? ""); ?>">
            <input type="email" name="email" placeholder="Email" maxlength="100" required
                   value="<?php echo h($editStudent["email"] ?? ""); ?>">
            <select name="course" required>
                <option value="">Select Course</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?php echo h($c); ?>" <?php echo (($editStudent["course"] ?? "") === $c) ? "selected" : ""; ?>>
                        <?php echo h($c); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="year" required>
                <option value="">Select Year</option>
                <?php for ($y = 1; $y <= 4; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo ((string)($editStudent["year"] ?? "") === (string)$y) ? "selected" : ""; ?>>
                        Year <?php echo $y; ?>
                    </option>
                <?php endfor; ?>
            </select>
            <button type="submit"><?php echo $editStudent ? "Update Student" : "Add Student"; ?></button>
            <?php if ($editStudent): ?><a class="page-link secondary" href="students.php">Cancel</a><?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <h2>Search and Filter</h2>
        <form method="get" action="students.php">
            <input type="text" name="search" placeholder="Search name, email, or course" value="<?php echo h($search); ?>">
            <select name="course">
                <option value="">All Courses</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?php echo h($c); ?>" <?php echo $filter === $c ? "selected" : ""; ?>>
                        <?php echo h($c); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Search</button>
            <a class="page-link secondary" href="students.php">Clear</a>
        </form>

        <h2>Student Records</h2>
        <p class="muted">
            Showing <?php echo $totalRows === 0 ? 0 : $offset + 1; ?>–<?php echo min($offset + $perPage, $totalRows); ?>
            of <?php echo $totalRows; ?> student(s).
        </p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Course</th><th>Year</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($students->num_rows > 0): ?>
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo (int)$student["student_id"]; ?></td>
                            <td><?php echo h($student["name"]); ?></td>
                            <td><?php echo h($student["email"]); ?></td>
                            <td><?php echo h($student["course"]); ?></td>
                            <td><?php echo (int)$student["year"]; ?></td>
                            <td>
                                <a class="edit" href="students.php?edit=<?php echo (int)$student["student_id"]; ?>">Edit</a>
                                <form method="post" action="students.php" style="display:inline"
                                      onsubmit="return confirm('Are you sure you want to delete this student?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="student_id" value="<?php echo (int)$student["student_id"]; ?>">
                                    <button class="delete" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">No students found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Pagination">
                <?php if ($page > 1): ?>
                    <a class="page-link secondary" href="<?php echo h(pageUrl($page - 1, $search, $filter)); ?>">Previous</a>
                <?php endif; ?>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a class="page-link <?php echo $p === $page ? "current" : "secondary"; ?>"
                       href="<?php echo h(pageUrl($p, $search, $filter)); ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a class="page-link secondary" href="<?php echo h(pageUrl($page + 1, $search, $filter)); ?>">Next</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
</main>

<footer>
    <h3>STUDENT HUB PORTAL</h3>
    <p>Dev Pandya</p>
</footer>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
