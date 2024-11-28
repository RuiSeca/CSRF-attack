<?php
// check_balance.php
require_once 'config.php';

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $result = $db->query('SELECT username, balance FROM users');
    echo "Current balances:\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['username']}: \${$row['balance']}\n";
    }
    
    echo "\nRecent transfers:\n";
    $transfers = $db->query('
        SELECT t.*, u1.username as from_user, u2.username as to_user 
        FROM transfers t 
        JOIN users u1 ON t.from_user = u1.id 
        JOIN users u2 ON t.to_user = u2.id 
        ORDER BY t.timestamp DESC 
        LIMIT 5
    ');
    
    while ($transfer = $transfers->fetch(PDO::FETCH_ASSOC)) {
        echo "From {$transfer['from_user']} to {$transfer['to_user']}: \${$transfer['amount']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}