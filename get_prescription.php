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
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid prescription ID']);
        exit();
    }

    // Get prescription details
    $query = "SELECT * FROM prescriptions WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'prescription' => [
                'id' => $row['id'],
                'patient_name' => $row['patient_name'],
                'medicine' => $row['medicine'],
                'dosage' => $row['dosage']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Prescription not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No prescription ID provided']);
}

$conn->close();
?> 