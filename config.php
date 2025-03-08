<?php
// config.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session at the beginning of the config file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'sql207.infinityfree.com');
define('DB_NAME', 'if0_37845296_csrf');
define('DB_USER', 'if0_37845296');
define('DB_PASS', 'W2Kbll9jxdUh');

// Function to establish and return a database connection
function getDatabase() {
    try {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($db->connect_error) {
            throw new Exception("Connection failed: " . $db->connect_error);
        }
        
        return $db;
    } catch (Exception $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Function to get PDO connection (for some operations that need PDO)
function getPDODatabase() {
    try {
        $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}