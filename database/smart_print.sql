-- Create database
CREATE DATABASE IF NOT EXISTS smart_print CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_print;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('student','admin') NOT NULL DEFAULT 'student',
    balance DECIMAL(10,2) NOT NULL DEFAULT 25.00,
    department VARCHAR(100)
);

-- Printers table
CREATE TABLE printers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(150) NOT NULL,
    status ENUM('online','offline','busy') NOT NULL DEFAULT 'online',
    queue INT NOT NULL DEFAULT 0
);

-- Print jobs table
CREATE TABLE print_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_name VARCHAR(200) NOT NULL,
    pages INT NOT NULL,
    copies INT NOT NULL,
    color_mode ENUM('bw','color') NOT NULL,
    paper_size ENUM('A4','Letter') NOT NULL,
    printer_id INT NOT NULL,
    status ENUM('pending','printing','completed','failed') NOT NULL DEFAULT 'pending',
    cost DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (printer_id) REFERENCES printers(id)
);

-- Sample data
INSERT INTO users (email, name, role, balance, department) VALUES
('john@college.edu', 'John Doe', 'student', 50.00, 'Computer Science'),
('jane@college.edu', 'Jane Smith', 'student', 25.50, 'Engineering'),
('admin@college.edu', 'Admin User', 'admin', 0.00, 'IT Services');

INSERT INTO printers (name, location, status, queue) VALUES
('Library Printer A', 'Main Library, Floor 2', 'online', 2),
('Computer Lab B', 'Science Building, Room 201', 'online', 0),
('Student Center', 'Student Union, Floor 1', 'busy', 5),
('Engineering Lab', 'Engineering Building, Room 105', 'offline', 0);
