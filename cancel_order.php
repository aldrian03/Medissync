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

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and validate order ID
        $order_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($order_id <= 0) {
            throw new Exception('Invalid order ID');
        }

        // Start transaction
        $conn->begin_transaction();

        // Check if order exists and is not already cancelled or delivered
        $check_query = "SELECT status FROM orders WHERE id = ?";
        $stmt = $conn->prepare($check_query);
        if (!$stmt) {
            throw new Exception('Failed to prepare order check query: ' . $conn->error);
        }
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Order not found');
        }

        $order = $result->fetch_assoc();
        if ($order['status'] === 'cancelled') {
            throw new Exception('Order is already cancelled');
        }
        if ($order['status'] === 'delivered') {
            throw new Exception('Cannot cancel a delivered order');
        }

        // Update order status to cancelled
        $update_query = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            throw new Exception('Failed to prepare update query: ' . $conn->error);
        }
        $stmt->bind_param("i", $order_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to cancel order: ' . $stmt->error);
        }

        // Add tracking information
        $tracking_query = "INSERT INTO order_tracking (order_id, status, location, notes) VALUES (?, 'cancelled', 'Order Cancelled', 'Order has been cancelled')";
        $stmt = $conn->prepare($tracking_query);
        if (!$stmt) {
            throw new Exception('Failed to prepare tracking insert query: ' . $conn->error);
        }
        $stmt->bind_param("i", $order_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to add tracking information: ' . $stmt->error);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Order cancelled successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Log error
        error_log("Order cancellation error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?> 