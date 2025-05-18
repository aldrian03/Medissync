<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "medlog";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

$id = (int)$_GET['id'];

// Fetch order details
$query = "SELECT o.*, s.contact_number, s.email 
          FROM orders o 
          LEFT JOIN suppliers s ON o.supplier_name = s.name 
          WHERE o.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

$order = $result->fetch_assoc();

// Get tracking information
$tracking_query = "SELECT * FROM order_tracking WHERE order_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($tracking_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$tracking_result = $stmt->get_result();
$tracking = $tracking_result->fetch_all(MYSQLI_ASSOC);

$order['tracking'] = $tracking;

echo json_encode(['success' => true, 'order' => $order]);

$conn->close();
?> 