<?php
require "auth.php";
require "db_mysqli.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function tableExists($conn, $table) {
    $allowed = ["students", "events", "registrations", "audit_logs"];
    if (!in_array($table, $allowed, true)) return false;
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function columnExists($conn, $table, $column) {
    $allowed = ["students", "events", "registrations", "audit_logs"];
    if (!in_array($table, $allowed, true)) return false;
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function countRows($conn, $table, $where = "") {
    $allowed = ["students", "events", "registrations"];
    if (!in_array($table, $allowed, true) || !tableExists($conn, $table)) return 0;
    $sql = "SELECT COUNT(*) AS total FROM `$table`";
    if ($where !== "") $sql .= " WHERE " . $where;
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_assoc();
    return (int)($row["total"] ?? 0);
}

$notice = "";
$noticeType = "success";

if (tableExists($conn, "audit_logs")) {
    $actorName = $_SESSION["name"] ?? "Admin";
    $actorRole = $_SESSION["role"] ?? "admin";
    $action = "dashboard_view";
    $details = "Admin opened the StudentHub dashboard.";
    if (columnExists($conn, "audit_logs", "actor_name") && columnExists($conn, "audit_logs", "actor_role")) {
        $stmt = $conn->prepare("INSERT INTO audit_logs (actor_name, actor_role, action, details) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $actorName, $actorRole, $action, $details);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO audit_logs (action, details) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $action, $details);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$totalAccounts = countRows($conn, "students");
$totalStudents = (tableExists($conn, "students") && columnExists($conn, "students", "role"))
    ? countRows($conn, "students", "role = 'student'") : $totalAccounts;
$totalAdmins = (tableExists($conn, "students") && columnExists($conn, "students", "role"))
    ? countRows($conn, "students", "role = 'admin'") : 0;
$totalEvents = countRows($conn, "events");
$totalRegistrations = countRows($conn, "registrations");

$latest = [];
if (tableExists($conn, "registrations") && tableExists($conn, "students") && tableExists($conn, "events")) {
    $sql = "SELECT r.registration_id, r.registration_date, s.name AS student_name, s.email AS student_email, e.event_name
            FROM registrations r
            LEFT JOIN students s ON s.student_id = r.student_id
            LEFT JOIN events e ON e.event_id = r.event_id
            ORDER BY r.registration_date DESC, r.registration_id DESC LIMIT 10";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) $latest[] = $row;
    }
}

$monthlyRegistrations = [];
$chartAvailable = tableExists($conn, "registrations") && columnExists($conn, "registrations", "registration_date");
$monthStart = new DateTime("first day of this month");
$monthStart->modify("-11 months");
$nextMonth = new DateTime("first day of next month");

for ($i = 0; $i < 12; $i++) {
    $month = clone $monthStart;
    $month->modify("+$i months");
    $key = $month->format("Y-m");
    $monthlyRegistrations[$key] = [
        "label" => $month->format("M Y"),
        "total" => 0
    ];
}

if ($chartAvailable) {
    $startDate = $monthStart->format("Y-m-d");
    $endDate = $nextMonth->format("Y-m-d");
    $stmt = $conn->prepare("SELECT DATE_FORMAT(registration_date, '%Y-%m') AS month_key, COUNT(*) AS total
                            FROM registrations
                            WHERE registration_date >= ? AND registration_date < ?
                            GROUP BY DATE_FORMAT(registration_date, '%Y-%m')
                            ORDER BY month_key");
    if ($stmt) {
        $stmt->bind_param("ss", $startDate, $endDate);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $key = $row["month_key"];
                if (isset($monthlyRegistrations[$key])) {
                    $monthlyRegistrations[$key]["total"] = (int)$row["total"];
                }
            }
        }
        $stmt->close();
    }
}

$maxMonthlyRegistrations = 0;
foreach ($monthlyRegistrations as $monthData) {
    if ($monthData["total"] > $maxMonthlyRegistrations) {
        $maxMonthlyRegistrations = $monthData["total"];
    }
}

$logs = [];
if (tableExists($conn, "audit_logs")) {
    $hasActor = columnExists($conn, "audit_logs", "actor_name");
    $hasRole = columnExists($conn, "audit_logs", "actor_role");
    $hasCreated = columnExists($conn, "audit_logs", "created_at");
    $hasId = columnExists($conn, "audit_logs", "id");
    $selectActor = $hasActor ? "actor_name" : "'Admin' AS actor_name";
    $selectRole = $hasRole ? "actor_role" : "'admin' AS actor_role";
    $orderBy = $hasCreated ? "created_at" : ($hasId ? "id" : "action");
    $result = $conn->query("SELECT $selectActor, $selectRole, action, details, " . ($hasCreated ? "created_at" : ($hasId ? "id AS created_at" : "action AS created_at")) . " FROM audit_logs ORDER BY $orderBy DESC LIMIT 8");
    if ($result) while ($row = $result->fetch_assoc()) $logs[] = $row;
}

if (isset($_GET["audit_test"]) && $_GET["audit_test"] === "1" && tableExists($conn, "audit_logs")) {
    $notice = "Audit log is available. Reload the dashboard to record a fresh dashboard-view entry.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StudentHub Admin Dashboard</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#fbfaf8;color:#222}
.top{background:#fff;color:#222;padding:20px 5%;display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;border-top:5px solid #8c1515;border-bottom:1px solid #ddd}
.top h1{font-size:24px;margin:0;color:#8c1515}
.top p{margin:6px 0 0;color:#555}
.top a{color:#8c1515;text-decoration:none;border:1px solid #8c1515;padding:9px 13px;border-radius:5px}
.top a:hover{background:#8c1515;color:#fff}
.layout{display:flex;min-height:calc(100vh - 82px)}
nav{width:220px;flex-shrink:0;background:#fff;padding:22px 14px;border-right:1px solid #ddd}
nav strong{display:block;padding:0 10px 12px;color:#777;font-size:12px;text-transform:uppercase}
nav a{display:block;color:#222;text-decoration:none;padding:11px 10px;border-radius:5px;margin-bottom:5px}
nav a:hover,nav a.active{background:#f7eaea;color:#8c1515}
.content{padding:26px;width:100%;max-width:1400px;margin:0 auto}
.welcome{margin-top:0;color:#8c1515}
.cards{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:16px;margin:20px 0 26px}
.card,.panel{background:#fff;border:1px solid #eee;border-radius:8px;padding:20px;box-shadow:0 3px 10px #ddd}
.card{border-top:4px solid #8c1515}
.card span{font-size:14px;color:#666}
.card strong{display:block;font-size:30px;margin-top:10px;color:#8c1515}
.grid{display:grid;grid-template-columns:1.2fr 1fr;gap:18px}
.panel{margin-bottom:20px;overflow:hidden}
.panel h2{font-size:18px;margin:0 0 16px;color:#8c1515}
.scroll{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{text-align:left;padding:11px 9px;border-bottom:1px solid #eee;vertical-align:top}
th{background:#fbfaf8;color:#444;font-weight:600}
.muted{color:#777}
.notice{padding:12px 15px;background:#f7eaea;border:1px solid #e6c4c4;border-radius:5px;margin-bottom:18px}
.empty{padding:14px;color:#777}
.pill{display:inline-block;background:#f7eaea;color:#8c1515;padding:4px 8px;border-radius:20px;font-size:12px}
.chart-wrap{width:100%;overflow-x:auto;padding-top:10px}
.chart{height:260px;min-width:650px;display:flex;align-items:flex-end;gap:12px;padding:15px 8px 0;border-bottom:1px solid #ddd}
.bar-column{flex:1;min-width:35px;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:7px}
.bar-value{font-size:12px;color:#555}
.bar-track{height:190px;width:100%;display:flex;align-items:flex-end;justify-content:center}
.bar{width:70%;max-width:42px;min-height:3px;background:#8c1515;border-radius:5px 5px 0 0}
.bar-label{font-size:11px;color:#555;white-space:nowrap;transform:rotate(-35deg);transform-origin:center top;margin-top:8px}
.chart-note{font-size:13px;color:#777;margin:12px 0 0}
footer{background:#650d0d;color:#fff;text-align:center;padding:20px;margin-top:25px}
@media(max-width:900px){.cards{grid-template-columns:repeat(2,minmax(140px,1fr))}.grid{grid-template-columns:1fr}nav{width:175px}}
@media(max-width:600px){.layout{display:block}nav{width:100%;display:flex;gap:5px;overflow-x:auto;padding:10px;border-right:0;border-bottom:1px solid #ddd}nav strong{display:none}nav a{white-space:nowrap;margin:0}.content{padding:16px}.cards{gap:10px}.card{padding:15px}.card strong{font-size:25px}.top{padding:18px 5%}.top h1{font-size:21px}.chart{min-width:620px}}
</style>
</head>
<body>
<header class="top"><div><h1>StudentHub Admin Dashboard</h1><p>Welcome, <?= e($_SESSION["name"] ?? "Admin") ?> · <span class="pill">Administrator</span></p></div><a href="logout.php">Logout</a></header>
<div class="layout">
<nav aria-label="Admin menu"><strong>Navigation</strong><a class="active" href="admin_dashboard.php">Dashboard</a><a href="admin.php">Admin Home</a><a href="students.php">Student Management</a><a href="events_ajax.php">Event Management</a><a href="#latest">Latest Registrations</a><a href="#monthly-chart">Monthly Chart</a><a href="#audit">Audit Log</a></nav>
<main class="content">
<h2 class="welcome">Overview</h2>
<?php if ($notice !== ""): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>
<section class="cards">
<div class="card"><span>Total Accounts</span><strong><?= $totalAccounts ?></strong><small class="muted">Rows in students table</small></div>
<div class="card"><span>Students</span><strong><?= $totalStudents ?></strong><small class="muted">Role = student</small></div>
<div class="card"><span>Events</span><strong><?= $totalEvents ?></strong><small class="muted">All events</small></div>
<div class="card"><span>Registrations</span><strong><?= $totalRegistrations ?></strong><small class="muted">Event registrations</small></div>
</section>
<section class="panel" id="monthly-chart">
<h2>Event Registrations by Month</h2>
<?php if (!$chartAvailable): ?>
<p class="empty">The registrations table or registration_date column was not found, so the chart cannot be displayed.</p>
<?php else: ?>
<div class="chart-wrap"><div class="chart" role="img" aria-label="Bar chart showing registrations for the last 12 months">
<?php foreach ($monthlyRegistrations as $monthData): ?>
<div class="bar-column">
<span class="bar-value"><?= (int)$monthData["total"] ?></span>
<div class="bar-track"><div class="bar" style="height:<?= $maxMonthlyRegistrations > 0 ? max(2, ($monthData["total"] / $maxMonthlyRegistrations) * 100) : 2 ?>%"></div></div>
<span class="bar-label"><?= e($monthData["label"]) ?></span>
</div>
<?php endforeach; ?>
</div></div>
<p class="chart-note">Shows registrations from <?= e($monthStart->format("F Y")) ?> to <?= e((clone $nextMonth)->modify("-1 day")->format("F Y")) ?>. The chart updates from MySQL when the dashboard is opened.</p>
<?php endif; ?>
</section>
<div class="grid">
<section class="panel" id="latest"><h2>Latest Registrations</h2><?php if (!$latest): ?><p class="empty">No registrations found, or the required tables are empty.</p><?php else: ?><div class="scroll"><table><thead><tr><th>Student</th><th>Event</th><th>Date</th></tr></thead><tbody><?php foreach ($latest as $item): ?><tr><td><?= e($item["student_name"] ?? "Unknown student") ?><br><small class="muted"><?= e($item["student_email"] ?? "") ?></small></td><td><?= e($item["event_name"] ?? "Unknown event") ?></td><td><?= e($item["registration_date"] ?? "") ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="panel"><h2>Account Summary</h2><p>Total accounts: <strong><?= $totalAccounts ?></strong></p><p>Student accounts: <strong><?= $totalStudents ?></strong></p><p>Admin accounts: <strong><?= $totalAdmins ?></strong></p><p class="muted">Your current schema stores accounts in the students table; there is no separate users table.</p></section>
</div>
<section class="panel" id="audit"><h2>Recent Audit Log</h2><?php if (!tableExists($conn, "audit_logs")): ?><p class="empty">Audit table is not installed yet. Run the SQL file included with this practical.</p><?php elseif (!$logs): ?><p class="empty">No audit entries yet. Refresh this dashboard after installing the audit table.</p><?php else: ?><div class="scroll"><table><thead><tr><th>When / ID</th><th>Actor</th><th>Role</th><th>Action</th><th>Details</th></tr></thead><tbody><?php foreach ($logs as $log): ?><tr><td><?= e($log["created_at"] ?? "") ?></td><td><?= e($log["actor_name"] ?? "Admin") ?></td><td><?= e($log["actor_role"] ?? "admin") ?></td><td><?= e($log["action"] ?? "") ?></td><td><?= e($log["details"] ?? "") ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<p class="muted">Dashboard statistics are loaded from MySQL each time this page opens.</p>
</main></div>
<footer>StudentHub</footer>
</body>
</html>
