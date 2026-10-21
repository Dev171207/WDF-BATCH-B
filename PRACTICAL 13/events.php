<?php
require "auth.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

require "db_mysqli.php";

$message = "";
$messageType = "success";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function redirectWithMessage($status) {
    header("Location: events.php?status=" . urlencode($status));
    exit;
}

$statusMessages = [
    "added" => ["Event added successfully.", "success"],
    "updated" => ["Event updated successfully.", "success"],
    "deleted" => ["Event deleted successfully.", "success"],
    "invalid" => ["Please check the event details and try again.", "error"],
    "upload_error" => ["Poster upload failed. Use JPG, PNG, or WEBP up to 2 MB.", "error"],
    "error" => ["The action could not be completed. Please try again.", "error"],
    "linked" => ["This event cannot be deleted because registrations are linked to it. Remove those registrations first.", "error"]
];

if (isset($_GET["status"]) && isset($statusMessages[$_GET["status"]])) {
    [$message, $messageType] = $statusMessages[$_GET["status"]];
}

$categories = ["Technical", "Cultural", "Sports", "Workshop", "Other"];
$statuses = ["Open", "Closed"];
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "event_uploads" . DIRECTORY_SEPARATOR;
$uploadWebPath = "event_uploads/";

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

function removePoster($filename, $uploadDir) {
    if ($filename && basename($filename) === $filename) {
        $path = $uploadDir . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function savePoster($file, $uploadDir) {
    if (!isset($file) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return ["ok" => true, "filename" => null];
    }
    if ($file["error"] !== UPLOAD_ERR_OK || $file["size"] > 2 * 1024 * 1024) {
        return ["ok" => false, "filename" => null];
    }

    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!is_uploaded_file($file["tmp_name"])) {
        return ["ok" => false, "filename" => null];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);
    if (!isset($allowed[$mime])) {
        return ["ok" => false, "filename" => null];
    }

    $imageInfo = @getimagesize($file["tmp_name"]);
    if ($imageInfo === false) {
        return ["ok" => false, "filename" => null];
    }

    $filename = bin2hex(random_bytes(16)) . "." . $allowed[$mime];
    if (!move_uploaded_file($file["tmp_name"], $uploadDir . $filename)) {
        return ["ok" => false, "filename" => null];
    }

    return ["ok" => true, "filename" => $filename];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "delete") {
        $id = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);
        if (!$id) {
            redirectWithMessage("error");
        }

        $stmt = $conn->prepare("SELECT poster FROM events WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $oldEvent = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$oldEvent) {
            redirectWithMessage("error");
        }

        try {
            $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $deleted = $stmt->affected_rows > 0;
            $stmt->close();

            if ($deleted) {
                removePoster($oldEvent["poster"] ?? "", $uploadDir);
                redirectWithMessage("deleted");
            }
            redirectWithMessage("error");
        } catch (mysqli_sql_exception $ex) {
            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
            redirectWithMessage("linked");
        }
    }

    if ($action === "add" || $action === "update") {
        $id = (int)($_POST["event_id"] ?? 0);
        $eventName = trim($_POST["event_name"] ?? "");
        $eventDate = trim($_POST["event_date"] ?? "");
        $category = trim($_POST["category"] ?? "");
        $venue = trim($_POST["venue"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $eventStatus = trim($_POST["event_status"] ?? "Open");

        $dateValid = DateTime::createFromFormat("Y-m-d", $eventDate);
        $dateValid = $dateValid && $dateValid->format("Y-m-d") === $eventDate;

        if ($eventName === "" || strlen($eventName) > 100 || !$dateValid ||
            !in_array($category, $categories, true) || $venue === "" ||
            strlen($venue) > 100 || !in_array($eventStatus, $statuses, true) ||
            strlen($description) > 2000) {
            redirectWithMessage("invalid");
        }

        $oldPoster = null;
        if ($action === "update") {
            if ($id <= 0) {
                redirectWithMessage("error");
            }
            $stmt = $conn->prepare("SELECT poster FROM events WHERE event_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$existing) {
                redirectWithMessage("error");
            }
            $oldPoster = $existing["poster"] ?? null;
        }

        $upload = savePoster($_FILES["poster"] ?? null, $uploadDir);
        if (!$upload["ok"]) {
            redirectWithMessage("upload_error");
        }
        $newPoster = $upload["filename"] ?? $oldPoster;

        try {
            if ($action === "add") {
                $stmt = $conn->prepare(
                    "INSERT INTO events (event_name, event_date, category, venue, description, poster, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("sssssss", $eventName, $eventDate, $category, $venue, $description, $newPoster, $eventStatus);
                $stmt->execute();
                $stmt->close();
                redirectWithMessage("added");
            } else {
                $stmt = $conn->prepare(
                    "UPDATE events SET event_name=?, event_date=?, category=?, venue=?, description=?, poster=?, status=?
                     WHERE event_id=?"
                );
                $stmt->bind_param("sssssssi", $eventName, $eventDate, $category, $venue, $description, $newPoster, $eventStatus, $id);
                $stmt->execute();
                $stmt->close();

                if ($upload["filename"] && $oldPoster) {
                    removePoster($oldPoster, $uploadDir);
                }
                redirectWithMessage("updated");
            }
        } catch (mysqli_sql_exception $ex) {
            if ($upload["filename"]) {
                removePoster($upload["filename"], $uploadDir);
            }
            redirectWithMessage("error");
        }
    }
}

$editEvent = null;
if (isset($_GET["edit"])) {
    $id = filter_input(INPUT_GET, "edit", FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $editEvent = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$search = trim($_GET["search"] ?? "");
$filterCategory = trim($_GET["category"] ?? "");
$filterStatus = trim($_GET["event_status"] ?? "");

if (!in_array($filterCategory, array_merge([""], $categories), true)) $filterCategory = "";
if (!in_array($filterStatus, array_merge([""], $statuses), true)) $filterStatus = "";

$page = max(1, (int)($_GET["page"] ?? 1));
$perPage = 6;
$searchLike = "%" . $search . "%";
$where = [];
$params = [];
$types = "";

if ($search !== "") {
    $where[] = "(event_name LIKE ? OR venue LIKE ? OR category LIKE ?)";
    $params[] = $searchLike; $params[] = $searchLike; $params[] = $searchLike;
    $types .= "sss";
}
if ($filterCategory !== "") {
    $where[] = "category = ?";
    $params[] = $filterCategory;
    $types .= "s";
}
if ($filterStatus !== "") {
    $where[] = "status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}
$whereSql = $where ? " WHERE " . implode(" AND ", $where) : "";

$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM events" . $whereSql);
if ($types !== "") $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalEvents = (int)$countStmt->get_result()->fetch_assoc()["total"];
$countStmt->close();

$totalPages = max(1, (int)ceil($totalEvents / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$listSql = "SELECT event_id, event_name, event_date, category, venue, description, poster, status
            FROM events" . $whereSql . " ORDER BY event_date DESC, event_id DESC LIMIT ? OFFSET ?";
$listStmt = $conn->prepare($listSql);
$listTypes = $types . "ii";
$listParams = $params;
$listParams[] = $perPage;
$listParams[] = $offset;
$listStmt->bind_param($listTypes, ...$listParams);
$listStmt->execute();
$events = $listStmt->get_result();

function pageUrl($page, $search, $category, $status) {
    return "events.php?" . http_build_query([
        "page" => $page,
        "search" => $search,
        "category" => $category,
        "event_status" => $status
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Event Management - StudentHub</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#f7f7f9;color:#222}
header{background:#fff;border-top:5px solid #8c1515;border-bottom:1px solid #ddd;padding:18px 5%;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
header h1{color:#8c1515;margin:0;font-size:24px}header a{color:#8c1515;text-decoration:none;margin-left:12px}
main{width:92%;max-width:1200px;margin:28px auto}.card{background:#fff;border:1px solid #e4e4e4;border-radius:10px;padding:20px;margin-bottom:22px;box-shadow:0 2px 8px #00000008}
h2{margin-top:0;color:#8c1515}label{display:block;font-weight:bold;margin:12px 0 6px}
input,select,textarea{width:100%;padding:10px;border:1px solid #ccc;border-radius:5px;font:inherit}
textarea{min-height:85px;resize:vertical}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
button,.btn{display:inline-block;border:0;border-radius:5px;padding:10px 14px;background:#8c1515;color:#fff;text-decoration:none;cursor:pointer;font:inherit}
.secondary{background:#555}.danger{background:#a11}.message{padding:12px;border-radius:5px;margin-bottom:18px}.success{background:#e5f7e9;color:#17652a}.error{background:#fde8e8;color:#8a1616}
.filters{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end}
.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;background:#fff}th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left;vertical-align:middle}th{background:#8c1515;color:#fff}
.poster{width:90px;height:65px;object-fit:cover;border-radius:4px}.actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}.inline{display:inline}.pagination{display:flex;gap:8px;align-items:center;margin-top:16px;flex-wrap:wrap}.pagination a{padding:8px 12px;border:1px solid #ddd;border-radius:4px;text-decoration:none;color:#8c1515;background:#fff}.pagination .current{background:#8c1515;color:#fff}
.hint{font-size:13px;color:#666}.current-poster{max-width:180px;display:block;margin-top:8px}
@media(max-width:800px){.filters{grid-template-columns:1fr 1fr}.grid{grid-template-columns:1fr}header{align-items:flex-start}}
</style>
</head>
<body>
<header>
  <div><h1>STUDENTHUB</h1><div>Admin · Event Management</div></div>
  <nav><a href="admin.php">Admin Dashboard</a><a href="Home.html">Home</a><a href="logout.php">Logout</a></nav>
</header>
<main>
<?php if ($message !== ""): ?><div class="message <?= e($messageType) ?>"><?= e($message) ?></div><?php endif; ?>

<section class="card">
<h2><?= $editEvent ? "Edit Event" : "Add New Event" ?></h2>
<form method="post" action="events.php" enctype="multipart/form-data">
<input type="hidden" name="action" value="<?= $editEvent ? "update" : "add" ?>">
<?php if ($editEvent): ?><input type="hidden" name="event_id" value="<?= (int)$editEvent["event_id"] ?>"><?php endif; ?>
<div class="grid">
<div><label for="event_name">Event name *</label><input id="event_name" name="event_name" maxlength="100" required value="<?= e($editEvent["event_name"] ?? "") ?>"></div>
<div><label for="event_date">Event date *</label><input id="event_date" type="date" name="event_date" required value="<?= e($editEvent["event_date"] ?? "") ?>"></div>
<div><label for="category">Category *</label><select id="category" name="category" required>
<?php foreach ($categories as $cat): ?><option value="<?= e($cat) ?>" <?= (($editEvent["category"] ?? "") === $cat) ? "selected" : "" ?>><?= e($cat) ?></option><?php endforeach; ?>
</select></div>
<div><label for="venue">Venue *</label><input id="venue" name="venue" maxlength="100" required value="<?= e($editEvent["venue"] ?? "") ?>"></div>
<div><label for="event_status">Status *</label><select id="event_status" name="event_status" required>
<?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= (($editEvent["status"] ?? "Open") === $s) ? "selected" : "" ?>><?= e($s) ?></option><?php endforeach; ?>
</select></div>
<div><label for="poster">Event poster (JPG, PNG, WEBP; max 2 MB)</label><input id="poster" type="file" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><div class="hint">Leave empty when editing to keep the current poster.</div>
<?php if (!empty($editEvent["poster"])): ?><img class="current-poster" src="<?= e($uploadWebPath . basename($editEvent["poster"])) ?>" alt="Current event poster"><?php endif; ?>
</div>
</div>
<label for="description">Description (optional, max 2000 characters)</label><textarea id="description" name="description" maxlength="2000"><?= e($editEvent["description"] ?? "") ?></textarea>
<p class="hint">Allowed posters: JPG/JPEG, PNG, WEBP. Server checks the actual file type and size, not just the filename.</p>
<button type="submit"><?= $editEvent ? "Save Changes" : "Add Event" ?></button>
<?php if ($editEvent): ?> <a class="btn secondary" href="events.php">Cancel Edit</a><?php endif; ?>
</form>
</section>

<section class="card">
<h2>Events</h2>
<form class="filters" method="get" action="events.php">
<div><label for="search">Search events</label><input id="search" name="search" placeholder="Name, venue, category" value="<?= e($search) ?>"></div>
<div><label for="filter-category">Category</label><select id="filter-category" name="category"><option value="">All categories</option><?php foreach ($categories as $cat): ?><option value="<?= e($cat) ?>" <?= $filterCategory === $cat ? "selected" : "" ?>><?= e($cat) ?></option><?php endforeach; ?></select></div>
<div><label for="filter-status">Status</label><select id="filter-status" name="event_status"><option value="">All statuses</option><?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= $filterStatus === $s ? "selected" : "" ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
<div><button type="submit">Search / Filter</button></div>
</form>
<p class="hint">Found <?= $totalEvents ?> event(s).</p>
<div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Poster</th><th>Event</th><th>Date</th><th>Category</th><th>Venue</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php if ($events->num_rows === 0): ?><tr><td colspan="8">No events found.</td></tr><?php endif; ?>
<?php while ($event = $events->fetch_assoc()): ?>
<tr>
<td><?= (int)$event["event_id"] ?></td>
<td><?php if (!empty($event["poster"])): ?><img class="poster" src="<?= e($uploadWebPath . basename($event["poster"])) ?>" alt="Poster for <?= e($event["event_name"]) ?>"><?php else: ?>No poster<?php endif; ?></td>
<td><strong><?= e($event["event_name"]) ?></strong><?php if (!empty($event["description"])): ?><div class="hint"><?= e($event["description"]) ?></div><?php endif; ?></td>
<td><?= e($event["event_date"]) ?></td><td><?= e($event["category"]) ?></td><td><?= e($event["venue"]) ?></td><td><?= e($event["status"]) ?></td>
<td><div class="actions"><a class="btn" href="events.php?edit=<?= (int)$event["event_id"] ?>">Edit</a>
<form class="inline" method="post" action="events.php" onsubmit="return confirm('Delete this event? This cannot be undone.');">
<input type="hidden" name="action" value="delete"><input type="hidden" name="event_id" value="<?= (int)$event["event_id"] ?>"><button class="danger" type="submit">Delete</button></form></div></td>
</tr>
<?php endwhile; ?>
</tbody></table></div>
<?php $listStmt->close(); ?>
<?php if ($totalPages > 1): ?><div class="pagination">
<?php if ($page > 1): ?><a href="<?= e(pageUrl($page - 1, $search, $filterCategory, $filterStatus)) ?>">Previous</a><?php endif; ?>
<span>Page <?= $page ?> of <?= $totalPages ?></span>
<?php if ($page < $totalPages): ?><a href="<?= e(pageUrl($page + 1, $search, $filterCategory, $filterStatus)) ?>">Next</a><?php endif; ?>
</div><?php endif; ?>
</section>
</main>
</body>
</html>
<?php $conn->close(); ?>
