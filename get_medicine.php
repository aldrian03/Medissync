<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "medlog";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID not provided']);
    exit();
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($medicine = $result->fetch_assoc()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'medicine' => $medicine]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Medicine not found']);
}

$stmt->close();
$conn->close();
?> 