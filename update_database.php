<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "user_db";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add verification columns
$sql = "ALTER TABLE users
        ADD COLUMN IF NOT EXISTS verification_status ENUM('pending', 'verified') DEFAULT 'pending',
        ADD COLUMN IF NOT EXISTS verification_token VARCHAR(255)";

if ($conn->query($sql) === TRUE) {
    echo "Verification columns added successfully<br>";
} else {
    echo "Error adding verification columns: " . $conn->error . "<br>";
}

// Update existing users to be verified
$sql = "UPDATE users SET verification_status = 'verified' WHERE verification_status IS NULL";
if ($conn->query($sql) === TRUE) {
    echo "Existing users updated successfully<br>";
} else {
    echo "Error updating existing users: " . $conn->error . "<br>";
}

$conn->close();
echo "Database update completed. You can now <a href='register.php'>return to registration page</a>.";
?> 