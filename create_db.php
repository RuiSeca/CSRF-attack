<?php
// Database configuration
$db_host = 'sql207.infinityfree.com';
$db_name = 'if0_37845296_csrf';
$db_user = 'if0_37845296';
$db_pass = 'W2Kbll9jxdUh';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Create MySQL PDO connection
    $db = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $db->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    $db->exec("USE `$db_name`");
    
    // Individual SQL statements
    $statements = [
        // Drop tables
        "DROP TABLE IF EXISTS transfers",
        "DROP TABLE IF EXISTS users",
        
        // Create users table
        "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            balance DECIMAL(10,2) NOT NULL DEFAULT 1000.00,
            secure_balance DECIMAL(10,2) NOT NULL DEFAULT 1000.00,
            last_secure_access DATETIME,
            CHECK (balance >= 0)
        ) ENGINE=InnoDB",
        
        // Create transfers table
        "CREATE TABLE transfers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            from_user INT NOT NULL,
            to_user INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_secure BOOLEAN DEFAULT 0,
            FOREIGN KEY(from_user) REFERENCES users(id),
            FOREIGN KEY(to_user) REFERENCES users(id),
            CHECK (amount > 0)
        ) ENGINE=InnoDB",
        
        // Create indexes
        "CREATE INDEX idx_transfers_from_user ON transfers(from_user)",
        "CREATE INDEX idx_transfers_to_user ON transfers(to_user)",
        "CREATE INDEX idx_transfers_timestamp ON transfers(timestamp)",
        "CREATE INDEX idx_users_username ON users(username)",
        
        // Insert default users and test users
        "INSERT IGNORE INTO users (id, username, password, balance, secure_balance) VALUES 
        (1, 'user', 'pass', 1000.00, 1000.00),
        (2, 'attacker', 'evil', 0.00, 0.00),
        (3, 'john_doe', 'test123', 1000.00, 1000.00),
        (4, 'sarah_smith', 'test123', 1500.00, 1500.00),
        (5, 'mike_jones', 'test123', 2000.00, 2000.00),
        (6, 'lisa_brown', 'test123', 1200.00, 1200.00),
        (7, 'david_wilson', 'test123', 1800.00, 1800.00),
        (8, 'emma_davis', 'test123', 900.00, 900.00),
        (9, 'alex_taylor', 'test123', 2500.00, 2500.00)"
    ];
    
    // Execute each statement separately
    foreach ($statements as $statement) {
        $db->exec($statement);
        echo "Executed: " . substr($statement, 0, 50) . "...<br>";
    }
    
    echo "Database setup completed successfully!<br>";
    
    // Verify the database setup
    echo "<br>Verifying database setup:<br>";
    
    // Check users
    $result = $db->query('SELECT username, balance, secure_balance FROM users');
    echo "<br>Users:<br>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "Username: {$row['username']}, " .
             "Balance: \${$row['balance']}, " .
             "Secure Balance: \${$row['secure_balance']}<br>";
    }
    
    // Check tables
    $result = $db->query("SHOW TABLES");
    echo "<br>Tables created:<br>";
    while ($row = $result->fetch(PDO::FETCH_COLUMN)) {
        echo "- $row<br>";
    }

    // Redirect to another page after successful setup
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Database Setup Complete</title>
        <script>
            let countdown = 5; // Set the countdown time in seconds
    
            function updateCountdown() {
                document.getElementById('countdown').innerText = countdown;
                countdown--;
    
                if (countdown < 0) {
                    window.location.href = 'http://crfs.infinityfreeapp.com/public/index.php';
                }
            }
    
            // Update the countdown every second
            setInterval(updateCountdown, 1000);
        </script>
    </head>
    <body>
        <h1>Database Setup Completed Successfully!</h1>
        <p>You will be redirected to the homepage in <span id='countdown'>5</span> seconds.</p>
        <p>If you are not redirected, <a href='http://crfs.infinityfreeapp.com/public/index.php'>click here</a>.</p>
    </body>
    </html>";
    exit;

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>