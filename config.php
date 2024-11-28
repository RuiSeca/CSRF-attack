<?php
// config.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define database path constant
define('DB_PATH', __DIR__ . '/DataBase/bank.db');

// Function to establish and return a database connection
function getDatabase() {
    try {
        // Check if SQLite3 extension is enabled
        if (!extension_loaded('sqlite3')) {
            throw new Exception("SQLite3 extension is not enabled. Please enable it in your php.ini file.");
        }
        
        // Check if database exists
        if (!file_exists(DB_PATH)) {
            throw new Exception("Database file not found. Please run create_db.php to create the database first.");
        }
        
        $db = new SQLite3(DB_PATH);
        $db->enableExceptions(true);
        return $db;
    } catch (Exception $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Function to get PDO connection (for some operations that need PDO)
function getPDODatabase() {
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}