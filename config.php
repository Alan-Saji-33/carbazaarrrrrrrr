<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "carbazaar_db";

// Initialize session with secure settings
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

// Database connection with error handling
try {
    // Connect without database to create it if needed
    $temp_conn = new mysqli($db_host, $db_user, $db_pass);
    if ($temp_conn->connect_error) {
        throw new Exception("Initial connection failed: " . $temp_conn->connect_error);
    }

    // Create database if not exists
    $create_db = "CREATE DATABASE IF NOT EXISTS $db_name";
    if (!$temp_conn->query($create_db)) {
        throw new Exception("Error creating database: " . $temp_conn->error);
    }
    $temp_conn->close();

    // Connect with database selected
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die($e->getMessage());
}

// Create tables if they don't exist
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(15) NULL,
        location VARCHAR(100) NULL,
        user_type ENUM('admin', 'seller', 'buyer') NOT NULL,
        is_verified BOOLEAN DEFAULT FALSE,
        verification_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL,
        rejection_reason TEXT NULL,
        aadhaar_path VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "cars" => "CREATE TABLE IF NOT EXISTS cars (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL,
        model VARCHAR(100) NOT NULL,
        brand VARCHAR(50) NOT NULL,
        year INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        km_driven INT NOT NULL,
        fuel_type ENUM('Petrol', 'Diesel', 'Electric', 'Hybrid', 'CNG') NOT NULL,
        transmission ENUM('Automatic', 'Manual') NOT NULL,
        location VARCHAR(100) NOT NULL,
        ownership ENUM('First', 'Second', 'Third', 'Fourth+') NOT NULL,
        insurance VARCHAR(100) NOT NULL,
        image_paths TEXT NOT NULL,
        description TEXT,
        is_sold BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "favorites" => "CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        car_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
        UNIQUE KEY unique_favorite (user_id, car_id)
    )",

    "reports" => "CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        car_id INT NOT NULL,
        reason TEXT NOT NULL,
        status ENUM('pending', 'resolved', 'dismissed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
    )",

    "verifications" => "CREATE TABLE IF NOT EXISTS verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        aadhaar_path VARCHAR(255) NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        rejection_reason TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

foreach ($tables as $table_name => $sql) {
    if (!$conn->query($sql)) {
        die("Error creating table $table_name: " . $conn->error);
    }
}

// Create admin user if not exists
$admin_check = $conn->query("SELECT * FROM users WHERE username = 'admin' AND user_type = 'admin'");
if ($admin_check->num_rows == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin_email = 'admin@carbazaar.com';
    $admin_sql = "INSERT INTO users (username, password, email, user_type, is_verified) VALUES ('admin', '$admin_password', '$admin_email', 'admin', TRUE)";
    if (!$conn->query($admin_sql)) {
        die("Error creating admin user: " . $conn->error);
    }
}
?>
