CREATE DATABASE user_db;  -- Make sure this matches your original database name
USE user_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,  -- Adding a name column as per your original structure
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a sample user (password is 'admin123' hashed using bcrypt)
INSERT INTO users (name, email, password) 
VALUES ('Doctor', 'doctor@gmail.com', '$2y$10$Vw9zrZXGzA18FUd6V.0mI9a1l.h0uRUgIbPcu6DW.9H.CYa5rH.O');
