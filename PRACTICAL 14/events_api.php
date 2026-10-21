<?php
require "auth.php";
require "db_mysqli.php";
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

function respond($code, $success, $message, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge(["success" => $success, "message" => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") respond(403, false, "Access denied. Admins only.");
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $search = trim($_GET["search"] ?? "");
    if (strlen($search) > 100) respond(400, false, "Search must be 100 characters or fewer.");
    if ($search !== "") {
        $like = "%".$search."%";
        $stmt = $conn->prepare("SELECT event_id,event_name,event_date,category,venue FROM events WHERE event_name LIKE ? OR category LIKE ? OR venue LIKE ? ORDER BY event_date DESC,event_id DESC");
        if (!$stmt) respond(500, false, "Could not prepare event search.");
        $stmt->bind_param("sss", $like, $like, $like);
    } else {
        $stmt = $conn->prepare("SELECT event_id,event_name,event_date,category,venue FROM events ORDER BY event_date DESC,event_id DESC");
        if (!$stmt) respond(500, false, "Could not prepare event list.");
    }
    if (!$stmt->execute()) { $stmt->close(); respond(500, false, "Could not load events."); }
    $result = $stmt->get_result(); $events = [];
    while ($row = $result->fetch_assoc()) $events[] = $row;
    $stmt->close();
    respond(200, true, "Events loaded.", ["events" => $events]);
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: GET, POST"); respond(405, false, "Method not allowed. Use GET or POST.");
}
if (stripos($_SERVER["CONTENT_TYPE"] ?? "", "application/json") === false) respond(415, false, "Content-Type must be application/json.");
$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) respond(400, false, "Request body must be valid JSON.");
$action = $input["action"] ?? "";
if (!is_string($action)) respond(400, false, "Invalid action.");

if ($action === "add" || $action === "update") {
    $name = trim((string)($input["event_name"] ?? ""));
    $date = trim((string)($input["event_date"] ?? ""));
    $category = trim((string)($input["category"] ?? ""));
    $venue = trim((string)($input["venue"] ?? ""));
    if ($name === "" || $date === "" || $category === "" || $venue === "") respond(422, false, "All fields are required.");
    if (strlen($name)>100 || strlen($venue)>100 || strlen($category)>50) respond(422, false, "Name and venue max 100 characters; category max 50.");
    $dt = DateTime::createFromFormat("!Y-m-d", $date);
    if (!$dt || $dt->format("Y-m-d") !== $date) respond(422, false, "Enter a valid event date.");
    if (!in_array($category, ["Technical","Cultural","Sports","Workshop","Other"], true)) respond(422, false, "Choose a valid category.");

    if ($action === "add") {
        $stmt = $conn->prepare("INSERT INTO events (event_name,event_date,category,venue) VALUES (?,?,?,?)");
        if (!$stmt) respond(500, false, "Could not prepare event creation.");
        $stmt->bind_param("ssss", $name, $date, $category, $venue);
        if (!$stmt->execute()) { $stmt->close(); respond(500, false, "Could not add event."); }
        $id = $stmt->insert_id; $stmt->close();
        respond(201, true, "Event added successfully.", ["event_id" => $id]);
    }

    $id = filter_var($input["event_id"] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) respond(422, false, "Valid event ID is required for updating.");
    $stmt = $conn->prepare("UPDATE events SET event_name=?,event_date=?,category=?,venue=? WHERE event_id=?");
    if (!$stmt) respond(500, false, "Could not prepare event update.");
    $stmt->bind_param("ssssi", $name, $date, $category, $venue, $id);
    if (!$stmt->execute()) { $stmt->close(); respond(500, false, "Could not update event."); }
    $changed = $stmt->affected_rows; $stmt->close();
    if ($changed === 0) {
        $check = $conn->prepare("SELECT event_id FROM events WHERE event_id=?");
        if (!$check) respond(500, false, "Could not verify event.");
        $check->bind_param("i", $id); $check->execute(); $exists = $check->get_result()->num_rows > 0; $check->close();
        if (!$exists) respond(404, false, "Event not found.");
    }
    respond(200, true, "Event updated successfully.");
}
if ($action === "delete") {
    $id = filter_var($input["event_id"] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) respond(422, false, "Valid event ID is required for deleting.");
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id=?");
    if (!$stmt) respond(500, false, "Could not prepare event deletion.");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        $errno = $stmt->errno; $stmt->close();
        if ($errno === 1451) respond(409, false, "This event has registrations and cannot be deleted.");
        respond(500, false, "Could not delete event.");
    }
    $deleted = $stmt->affected_rows; $stmt->close();
    if (!$deleted) respond(404, false, "Event not found.");
    respond(200, true, "Event deleted successfully.");
}
respond(400, false, "Unknown action. Use add, update, or delete.");
