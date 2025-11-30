<?php
/** 
* Configuration File
* This file contains the database configuration settings used throughout the application.
*
*/

//This shows all PHP errors and warnings in the browser
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); //MySQL username
define('DB_PASS', ''); //MySQL password
define('DB_NAME', 'project_manager'); //Name of our database

// Create database connection
function getDB() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                //Set error mode to exceptions so we can catch and handle errors properly
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                //Set default fetch mode to associative array for easier data access
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    // isset() checks if the session variable exists and is not null
    return isset($_SESSION['user_id']);
}

// Require login for protected pages
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

//Sanitise output to prevent XSS
function h($string) {
    // htmlspecialchars() converts special characters to HTML entities
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
