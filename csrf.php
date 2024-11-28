<?php
// csrf.php - Vulnerable transfer page (for demonstration)
session_start();
require_once 'config.php';
$db = getDatabase();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture the transfer data
    $amount = floatval($_POST['amount']);
    $to_user = intval($_POST['to_user']);
    
    // Begin transaction
    $db->exec('BEGIN TRANSACTION');
    
    try {
        // Update sender's balance
        $stmt = $db->prepare('UPDATE users SET balance = balance - :amount WHERE id = :user_id AND balance >= :amount');
        $stmt->bindValue(':amount', $amount);
        $stmt->bindValue(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        // Update receiver's balance
        $stmt = $db->prepare('UPDATE users SET balance = balance + :amount WHERE id = :to_user');
        $stmt->bindValue(':amount', $amount);
        $stmt->bindValue(':to_user', $to_user);
        $stmt->execute();
        
        // Record the transfer
        $stmt = $db->prepare('INSERT INTO transfers (from_user, to_user, amount) VALUES (:from, :to, :amount)');
        $stmt->bindValue(':from', $_SESSION['user_id']);
        $stmt->bindValue(':to', $to_user);
        $stmt->bindValue(':amount', $amount);
        $stmt->execute();
        
        $db->exec('COMMIT');
        $success = 'Transfer successful!';
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        $error = 'Transfer failed: ' . $e->getMessage();
    }
}

// Get current balance
$stmt = $db->prepare('SELECT balance FROM users WHERE id = :id');
$stmt->bindValue(':id', $_SESSION['user_id']);
$result = $stmt->execute();
$balance = $result->fetchArray()['balance'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSRF Attack Demonstration</title>
</head>
<body>
    <h2>CSRF Attack Demonstration - Transfer Money</h2>
    
    <p>Your current balance: $<?= number_format($balance, 2) ?></p>
    
    <?php if (isset($success)): ?>
        <p style="color: green;"><?= $success ?></p>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>
    
    <h3>Transfer Money</h3>
    <form method="POST">
        Amount: <input type="number" name="amount" step="0.01" min="0.01" required><br>
        To User ID: <input type="number" name="to_user" required><br>
        <input type="submit" value="Transfer">
    </form>
    
    <p>Try the CSRF attack demonstration:</p>
    <a href="attack/evil.html" target="_blank" class="attack-link">View Malicious Demo</a>
    
    <p><a href="transfer.php">View Secure Transfer Page (with CSRF Protection)</a></p>
    <p><a href="logout.php">Logout</a></p>
</body>
</html>