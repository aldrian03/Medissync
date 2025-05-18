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
        // Get and sanitize input data
        $supplier_name = $conn->real_escape_string($_POST['supplier_name']);
        $medicine_name = $conn->real_escape_string($_POST['medicine_name']);
        $quantity = (int)$_POST['quantity'];
        $order_date = $_POST['order_date'];
        
        // Validate required fields
        if (empty($supplier_name) || empty($medicine_name) || $quantity <= 0 || empty($order_date)) {
            throw new Exception('All required fields must be filled');
        }

        // Start transaction
        $conn->begin_transaction();

        // Check if supplier exists
        $check_query = "SELECT id FROM suppliers WHERE name = ?";
        $stmt = $conn->prepare($check_query);
        if (!$stmt) {
            throw new Exception('Failed to prepare supplier check query: ' . $conn->error);
        }
        $stmt->bind_param("s", $supplier_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Selected supplier not found');
        }

        // Check if medicine exists in inventory
        $check_query = "SELECT id FROM inventory WHERE medicine_name = ?";
        $stmt = $conn->prepare($check_query);
        if (!$stmt) {
            throw new Exception('Failed to prepare medicine check query: ' . $conn->error);
        }
        $stmt->bind_param("s", $medicine_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Selected medicine not found in inventory');
        }

        // Insert order
        $query = "INSERT INTO orders (supplier_name, medicine_name, quantity, order_date, status) 
                  VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare order insert query: ' . $conn->error);
        }
        $stmt->bind_param("ssis", $supplier_name, $medicine_name, $quantity, $order_date);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert order: ' . $stmt->error);
        }

        $order_id = $conn->insert_id;

        // Insert initial tracking information
        $tracking_query = "INSERT INTO order_tracking (order_id, status, location, notes) VALUES (?, 'pending', 'Order Placed', 'Order has been placed successfully')";
        $stmt = $conn->prepare($tracking_query);
        if (!$stmt) {
            throw new Exception('Failed to prepare tracking insert query: ' . $conn->error);
        }
        $stmt->bind_param("i", $order_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert tracking information: ' . $stmt->error);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Order placed successfully',
            'order_id' => $order_id
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Log error
        error_log("Order placement error: " . $e->getMessage());
        
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