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

// Validate and sanitize input
$full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

// Validate required fields
if (empty($full_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Full name and email are required']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Check if email is already taken by another user
$query = "SELECT id FROM users WHERE email = ? AND id != ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $email, $_SESSION['user']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email is already taken']);
    exit();
}

// Update user profile
$query = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssi", $full_name, $email, $phone, $_SESSION['user']);

if ($stmt->execute()) {
    // Update session data
    $_SESSION['user_full_name'] = $full_name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_phone'] = $phone;
    
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
}

$conn->close();
?> 