<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
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
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        try {
            // Start transaction
            $conn->begin_transaction();

            // Get order details including medicine name
            $stmt = $conn->prepare("SELECT o.medicine_name, o.quantity, o.status FROM orders o WHERE o.id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            
            if (!$order) {
                throw new Exception("Order not found");
            }
            
            if ($order['status'] === 'delivered') {
                throw new Exception("Order is already marked as delivered");
            }

            // Update order status to delivered
            $updateStmt = $conn->prepare("UPDATE orders SET status = 'delivered' WHERE id = ?");
            if (!$updateStmt) {
                throw new Exception("Failed to prepare update statement: " . $conn->error);
            }
            
            $updateStmt->bind_param("i", $id);
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update order status: " . $updateStmt->error);
            }

            // Check if medicine exists in inventory
            $checkStmt = $conn->prepare("SELECT id, quantity FROM inventory WHERE medicine_name = ?");
            $checkStmt->bind_param("s", $order['medicine_name']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $existingMedicine = $checkResult->fetch_assoc();

            if ($existingMedicine) {
                // Update existing medicine quantity
                $updateInventoryStmt = $conn->prepare("UPDATE inventory SET quantity = quantity + ? WHERE medicine_name = ?");
                $updateInventoryStmt->bind_param("is", $order['quantity'], $order['medicine_name']);
                if (!$updateInventoryStmt->execute()) {
                    throw new Exception("Failed to update existing medicine quantity: " . $updateInventoryStmt->error);
                }
            } else {
                // Insert new medicine
                $insertInventoryStmt = $conn->prepare("INSERT INTO inventory (medicine_name, quantity, expiry_date) VALUES (?, ?, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR))");
                $insertInventoryStmt->bind_param("si", $order['medicine_name'], $order['quantity']);
                if (!$insertInventoryStmt->execute()) {
                    throw new Exception("Failed to add new medicine to inventory: " . $insertInventoryStmt->error);
                }
            }
            
            // Get updated counts
            $countsQuery = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
                FROM orders";
            
            $countsResult = $conn->query($countsQuery);
            $counts = $countsResult->fetch_assoc();
            
            // Commit transaction
            $conn->commit();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Order marked as received and inventory updated successfully',
                'counts' => $counts,
                'medicine_name' => $order['medicine_name'],
                'quantity_added' => $order['quantity']
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?> 