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

// Get and validate system settings
$currency = filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_STRING);
$date_format = filter_input(INPUT_POST, 'date_format', FILTER_SANITIZE_STRING);
$timezone = filter_input(INPUT_POST, 'timezone', FILTER_SANITIZE_STRING);

// Validate currency
$valid_currencies = ['USD', 'EUR', 'GBP'];
if (!in_array($currency, $valid_currencies)) {
    echo json_encode(['success' => false, 'message' => 'Invalid currency selected']);
    exit();
}

// Validate date format
$valid_date_formats = ['Y-m-d', 'd-m-Y', 'm-d-Y'];
if (!in_array($date_format, $valid_date_formats)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format selected']);
    exit();
}

// Validate timezone
$valid_timezones = ['UTC', 'EST', 'PST'];
if (!in_array($timezone, $valid_timezones)) {
    echo json_encode(['success' => false, 'message' => 'Invalid timezone selected']);
    exit();
}

// Update system settings
$query = "UPDATE users SET 
          currency = ?,
          date_format = ?,
          timezone = ?
          WHERE id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("sssi", $currency, $date_format, $timezone, $_SESSION['user']);

if ($stmt->execute()) {
    // Update session data
    $_SESSION['user_currency'] = $currency;
    $_SESSION['user_date_format'] = $date_format;
    $_SESSION['user_timezone'] = $timezone;
    
    echo json_encode(['success' => true, 'message' => 'System settings updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update system settings']);
}

$conn->close();
?> 