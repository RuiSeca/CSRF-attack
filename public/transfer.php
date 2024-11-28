<?php
// transfer.php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDatabase();

// Get current user's balance
$stmt = $db->prepare('SELECT balance FROM users WHERE id = :id');
$stmt->bindValue(':id', $_SESSION['user_id']);
$result = $stmt->execute();
$balance = $result->fetchArray()['balance'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $to_user = intval($_POST['to_user']);
    
    // Validate amount
    if ($amount <= 0 || $amount > 5000) {
        $error = "Amount must be between $1 and $5000";
    } elseif ($amount > $balance) {
        $error = "Insufficient funds";
    } else {
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
            $success = "Transfer successful!";
            
            // Update balance after transfer
            $stmt = $db->prepare('SELECT balance FROM users WHERE id = :id');
            $stmt->bindValue(':id', $_SESSION['user_id']);
            $result = $stmt->execute();
            $balance = $result->fetchArray()['balance'];
        } catch (Exception $e) {
            $db->exec('ROLLBACK');
            $error = "Transfer failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fund Transfer</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Fund Transfer</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="balance">
            Current Balance: $<?= number_format($balance, 2) ?>
        </div>

        <form method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="amount">Amount (between $1-$5000):</label>
                <input type="number" 
                       id="amount" 
                       name="amount" 
                       min="1" 
                       max="5000" 
                       step="0.01" 
                       required 
                       placeholder="Enter amount">
            </div>
            <div class="form-group">
                <label for="to_user">Recipient Account:</label>
                <input type="text" 
                       id="to_user" 
                       name="to_user" 
                       required 
                       placeholder="Enter recipient account">
            </div>
            <button type="submit">Transfer Now</button>
        </form>

        <div class="links">
            <p><a href="index.php">Back to Home</a> | <a href="logout.php">Logout</a></p>
        </div>
    </div>

    <script>
    function validateForm() {
        const amount = document.getElementById('amount').value;
        const toUser = document.getElementById('to_user').value;

        if (amount <= 0 || amount > 5000) {
            alert('Amount must be between $1 and $5000');
            return false;
        }

        if (!toUser) {
            alert('Please enter a recipient account');
            return false;
        }

        return true;
    }

    // Auto-hide messages after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const messages = document.querySelectorAll('.success, .error');
        messages.forEach(function(message) {
            setTimeout(function() {
                message.style.opacity = '0';
                setTimeout(function() {
                    message.remove();
                }, 500);
            }, 5000);
        });
    });
    </script>
</body>
</html>