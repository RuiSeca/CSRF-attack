<?php
// create_db.php

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set the database path
$db_dir = __DIR__ . '/DataBase';
$db_file = $db_dir . '/bank.db';

// Create DataBase directory if it doesn't exist
if (!is_dir($db_dir)) {
    mkdir($db_dir, 0777, true);
}

// Remove existing database if it exists
if (file_exists($db_file)) {
    unlink($db_file);
}

try {
    // Create new PDO connection
    $db = new PDO("sqlite:$db_file");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute the schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) {
        throw new Exception("Could not read schema.sql file");
    }
    
    // Execute the schema
    $db->exec($schema);
    
    echo "Database created successfully in: $db_file\n";
    
    // Verify the database setup
    echo "\nVerifying database setup:\n";
    
    // Check users
    $result = $db->query('SELECT username, balance, secure_balance FROM users');
    echo "\nUsers:\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "Username: {$row['username']}, ".
             "Balance: \${$row['balance']}, ".
             "Secure Balance: \${$row['secure_balance']}\n";
    }
    
    // Check tables
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    echo "\nTables created:\n";
    while ($row = $result->fetch(PDO::FETCH_COLUMN)) {
        echo "- $row\n";
    }
    
    // Check views
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='view'");
    echo "\nViews created:\n";
    while ($row = $result->fetch(PDO::FETCH_COLUMN)) {
        echo "- $row\n";
    }
    
    // Check triggers
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='trigger'");
    echo "\nTriggers created:\n";
    while ($row = $result->fetch(PDO::FETCH_COLUMN)) {
        echo "- $row\n";
    }
    
    echo "\nDatabase setup completed successfully!\n";
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}