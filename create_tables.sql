-- Create suppliers table
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    contact_number VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    medicine_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery_date DATE NOT NULL,
    notes TEXT,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_name) REFERENCES suppliers(name) ON DELETE RESTRICT
);

-- Create order_tracking table
CREATE TABLE IF NOT EXISTS order_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL,
    location VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Insert sample suppliers
INSERT INTO suppliers (name, contact_number, email, address) VALUES
('MediSupply Co.', '+1234567890', 'contact@medisupply.com', '123 Medical Street, Healthcare City'),
('PharmaDist Inc.', '+1987654321', 'info@pharmadist.com', '456 Pharmacy Avenue, Medical District'),
('HealthCare Supplies', '+1122334455', 'sales@healthcaresupplies.com', '789 Health Road, Wellness City');

-- Insert sample orders
INSERT INTO orders (supplier_name, medicine_name, quantity, order_date, expected_delivery_date, status) VALUES
('MediSupply Co.', 'Paracetamol 500mg', 1000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'pending'),
('PharmaDist Inc.', 'Amoxicillin 250mg', 500, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'processing'),
('HealthCare Supplies', 'Ibuprofen 400mg', 750, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'shipped');

-- Insert sample tracking information
INSERT INTO order_tracking (order_id, status, location, notes) VALUES
(1, 'pending', 'Warehouse', 'Order received'),
(2, 'processing', 'Distribution Center', 'Order being processed'),
(3, 'shipped', 'In Transit', 'Package dispatched'); 