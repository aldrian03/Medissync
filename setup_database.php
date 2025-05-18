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

// Read and execute SQL file
$sql = file_get_contents('create_tables.sql');

// Split SQL file into individual queries
$queries = array_filter(array_map('trim', explode(';', $sql)));

$success = true;
$errors = [];

foreach ($queries as $query) {
    if (!empty($query)) {
        if (!$conn->query($query)) {
            $success = false;
            $errors[] = "Error executing query: " . $conn->error . "\nQuery: " . $query;
        }
    }
}

if ($success) {
    echo "Database tables created successfully!";
} else {
    echo "Errors occurred while creating tables:\n";
    foreach ($errors as $error) {
        echo $error . "\n";
    }
}

$conn->close();
?> 