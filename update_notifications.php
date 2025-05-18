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

// Get notification preferences
$email_notifications = isset($_POST['emailNotifications']) ? 1 : 0;
$low_stock_alerts = isset($_POST['lowStockAlerts']) ? 1 : 0;
$order_updates = isset($_POST['orderUpdates']) ? 1 : 0;

// Update notification preferences
$query = "UPDATE users SET 
          email_notifications = ?,
          low_stock_alerts = ?,
          order_updates = ?
          WHERE id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $email_notifications, $low_stock_alerts, $order_updates, $_SESSION['user']);

if ($stmt->execute()) {
    // Update session data
    $_SESSION['user_email_notifications'] = $email_notifications;
    $_SESSION['user_low_stock_alerts'] = $low_stock_alerts;
    $_SESSION['user_order_updates'] = $order_updates;
    
    echo json_encode(['success' => true, 'message' => 'Notification preferences updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update notification preferences']);
}

$conn->close();
?> 