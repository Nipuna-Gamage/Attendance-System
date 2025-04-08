<?php
// Database configuration
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'if0_38670627_attendance_system';

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . $DB_HOST . ";dbname=" . $DB_NAME, $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . $DB_NAME);
    $pdo->exec("USE " . $DB_NAME);
    
    // Drop and recreate users table to add role column
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'manager', 'employee') NOT NULL DEFAULT 'employee',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create employees table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        department VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create attendance table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        date DATE NOT NULL,
        status ENUM('Present', 'Absent') NOT NULL,
        check_in_time TIME,
        check_out_time TIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id),
        UNIQUE KEY unique_attendance (employee_id, date)
    )");
    
    // Create default admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (username, password, role) VALUES ('admin', '$adminPassword', 'admin')");
    
    // Check if sample employees exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees");
    if ($stmt->fetchColumn() == 0) {
        // Create sample employees
        $pdo->exec("INSERT INTO employees (name, email, phone, department) VALUES 
            ('John Doe', 'john@example.com', '1234567890', 'IT'),
            ('Jane Smith', 'jane@example.com', '0987654321', 'HR'),
            ('Mike Johnson', 'mike@example.com', '5555555555', 'Finance')
        ");
        
        // Create sample manager user
        $managerPassword = password_hash('manager123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, password, role) VALUES ('manager', '$managerPassword', 'manager')");
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Application configuration
define('SITE_NAME', 'Attendance Management System');
define('SITE_URL', 'http://localhost/Attendance%20System');

// Time zone setting
date_default_timezone_set('Asia/Colombo');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Constants for attendance status
define('STATUS_PRESENT', 'Present');
define('STATUS_ABSENT', 'Absent');
define('STATUS_LATE', 'Late');

// Constants for user roles
define('ROLE_ADMIN', 'admin');
define('ROLE_EMPLOYEE', 'employee');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_PATH', 'uploads/');

// Pagination settings
define('ITEMS_PER_PAGE', 10);

// Security settings
define('HASH_COST', 12); // For password_hash()
define('TOKEN_EXPIRY', 3600); // 1 hour in seconds

// Email settings (if needed)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('MAIL_FROM', '');
define('MAIL_FROM_NAME', SITE_NAME);
?> 
