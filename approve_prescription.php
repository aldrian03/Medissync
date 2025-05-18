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
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get prescription details
            $stmt = $conn->prepare("SELECT medicine, dosage FROM prescriptions WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $prescription = $result->fetch_assoc();
            
            if (!$prescription) {
                throw new Exception("Prescription not found");
            }
            
            // Update prescription status
            $stmt = $conn->prepare("UPDATE prescriptions SET status = 'approved', approved_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Prescription approved successfully']);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?> 