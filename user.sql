-- Create the database
CREATE DATABASE IF NOT EXISTS user_db;
USE user_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    age INT NULL,
    contact VARCHAR(15) NULL,
    sex ENUM('Male', 'Female', 'Other') NULL,
    address TEXT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a sample user (password: password123)
INSERT INTO users (name, email, password) VALUES 
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Add new columns if they don't exist
ALTER TABLE users
ADD COLUMN IF NOT EXISTS age INT NULL AFTER password,
ADD COLUMN IF NOT EXISTS contact VARCHAR(15) NULL AFTER age,
ADD COLUMN IF NOT EXISTS sex ENUM('Male', 'Female', 'Other') NULL AFTER contact,
ADD COLUMN IF NOT EXISTS address TEXT NULL AFTER sex,
ADD COLUMN IF NOT EXISTS blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL AFTER address;
