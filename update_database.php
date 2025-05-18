<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "medlog";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add status column
$sql = "ALTER TABLE prescriptions 
        ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'unapproved') DEFAULT 'pending',
        ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL";

if ($conn->query($sql) === TRUE) {
    echo "Status column added successfully<br>";
} else {
    echo "Error adding status column: " . $conn->error . "<br>";
}

// Update existing records
$sql = "UPDATE prescriptions SET status = 'pending' WHERE status IS NULL";
if ($conn->query($sql) === TRUE) {
    echo "Existing records updated successfully<br>";
} else {
    echo "Error updating records: " . $conn->error . "<br>";
}

$conn->close();
echo "Database update completed. You can now <a href='prescriptions.php'>return to prescriptions page</a>.";
?> 