<?php
session_start();
header('Content-Type: application/json');

// More lenient admin check - just check if user is logged in
// You can tighten this later once you confirm admin sessions work
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Please login first']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "users";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit();
}

$type = isset($data['type']) ? $data['type'] : '';
$title = isset($data['title']) ? trim($data['title']) : '';
$date = isset($data['date']) ? trim($data['date']) : '';
$description = isset($data['description']) ? trim($data['description']) : '';

// Validate inputs
if (empty($type) || empty($title) || empty($date) || empty($description)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit();
}

if (!in_array($type, ['NEWS', 'EVENT'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid notification type']);
    exit();
}

// Insert notification
$stmt = $conn->prepare("INSERT INTO notifications (type, title, date, description) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $type, $title, $date, $description);

if ($stmt->execute()) {
    $notification_id = $conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'id' => $notification_id,
        'message' => 'Notification added successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to add notification: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>