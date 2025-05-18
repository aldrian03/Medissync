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
    // Get and sanitize input data
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $patient_name = $conn->real_escape_string($_POST['patient_name']);
    $medicine = $conn->real_escape_string($_POST['medicine']);
    $dosage = $conn->real_escape_string($_POST['dosage']);
    
    // Validate required fields
    if ($id <= 0 || empty($patient_name) || empty($medicine) || empty($dosage)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }

    // Check if prescription exists
    $check_query = "SELECT id FROM prescriptions WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Prescription not found']);
        exit();
    }

    // Check if medicine exists in inventory
    $check_query = "SELECT quantity FROM inventory WHERE medicine_name = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $medicine);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected medicine not found in inventory']);
        exit();
    }

    // Update prescription
    $query = "UPDATE prescriptions SET patient_name = ?, medicine = ?, dosage = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $patient_name, $medicine, $dosage, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Prescription updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update prescription: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?> 