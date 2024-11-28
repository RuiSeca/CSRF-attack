<?php
// public/secure_transfer.php
session_start();
require_once '../config.php';
require_once '../csrf_protection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDatabase();
$error = null;
$success = null;

// Initialize secure balance on first visit
if (!isset($_SESSION['secure_balance'])) {
    $stmt = $db->prepare('SELECT secure_balance FROM users WHERE id = :id');
    $stmt->bindValue(':id', $_SESSION['user_id']);
    $result = $stmt->execute();
    $_SESSION['secure_balance'] = $result->fetchArray()['secure_balance'];
}

// Check for unauthorized transfers
$stmt = $db->prepare('
    SELECT t.*, u.username as recipient_name 
    FROM transfers t
    JOIN users u ON t.to_user = u.id
    WHERE t.from_user = :user_id 
    AND t.is_secure = 0 
    AND t.timestamp > (SELECT COALESCE(last_secure_access, datetime("now", "-1 minute")) 
    FROM users WHERE id = :user_id)
    ORDER BY t.timestamp DESC
');
$stmt->bindValue(':user_id', $_SESSION['user_id']);
$result = $stmt->execute();
$unauthorizedTransfer = $result->fetchArray(SQLITE3_ASSOC);

if ($unauthorizedTransfer) {
    try {
        $db->exec('BEGIN TRANSACTION');
        
        // Reverse unauthorized transfer
        $stmt = $db->prepare('UPDATE users SET balance = secure_balance, last_secure_access = datetime("now") WHERE id = :user_id');
        $stmt->bindValue(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        $stmt = $db->prepare('UPDATE users SET balance = balance - :amount WHERE id = :to_user');
        $stmt->bindValue(':amount', $unauthorizedTransfer['amount']);
        $stmt->bindValue(':to_user', $unauthorizedTransfer['to_user']);
        $stmt->execute();
        
        $db->exec('COMMIT');
        $error = "⚠️ Unauthorized transfer detected and reversed. Your balance has been restored.";
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        $error = "Error handling unauthorized transfer: " . $e->getMessage();
    }
}

// Get current balance and recent transactions
$stmt = $db->prepare('SELECT balance, secure_balance FROM users WHERE id = :id');
$stmt->bindValue(':id', $_SESSION['user_id']);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);
$balance = $user['secure_balance'];

// Get recent transactions
$stmt = $db->prepare('
    SELECT t.*, u.username as recipient_name,
           CASE WHEN t.is_secure = 1 THEN "Secure" ELSE "Unsecure" END as security_status
    FROM transfers t
    JOIN users u ON t.to_user = u.id
    WHERE t.from_user = :user_id
    ORDER BY t.timestamp DESC LIMIT 5
');
$stmt->bindValue(':user_id', $_SESSION['user_id']);
$result = $stmt->execute();
$recentTransactions = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $recentTransactions[] = $row;
}

// Generate CSRF token
$csrf_token = CSRFProtection::generateToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Transfer - CSRF Demo</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Secure Fund Transfer <span class="security-badge">Protected</span></h2>
        
        <?php if ($error): ?>
            <div class="warning-banner">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <div class="balance-container">
            <div class="balance">
                Current Balance: $<?= number_format($balance, 2) ?>
            </div>
            <?php if ($unauthorizedTransfer): ?>
                <div class="balance-history">
                    Unauthorized transfer of $<?= number_format($unauthorizedTransfer['amount'], 2) ?> 
                    to <?= htmlspecialchars($unauthorizedTransfer['recipient_name']) ?> 
                    has been reversed
                </div>
            <?php endif; ?>
        </div>

        <form method="POST" onsubmit="return validateForm()" class="transfer-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
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

        <?php if (!empty($recentTransactions)): ?>
            <div class="transaction-history">
                <h3>Recent Transactions</h3>
                <?php foreach ($recentTransactions as $transaction): ?>
                    <div class="transaction-item">
                        <div>
                            To: <?= htmlspecialchars($transaction['recipient_name']) ?>
                            <span class="security-badge"><?= $transaction['security_status'] ?></span>
                        </div>
                        <div class="transaction-amount debit">
                            -$<?= number_format($transaction['amount'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="security-info">
            This page is protected against CSRF attacks. All transfers are monitored and unauthorized transactions will be reversed.
        </div>

        <div class="links">
            <a href="index.php">Back to Home</a> | 
            <a href="transfer.php">View Vulnerable Page</a> | 
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <script>
    function validateForm() {
        const amount = parseFloat(document.getElementById('amount').value);
        const toUser = document.getElementById('to_user').value;

        if (isNaN(amount) || amount <= 0 || amount > 5000) {
            alert('Amount must be between $1 and $5000');
            return false;
        }

        if (!toUser || isNaN(parseInt(toUser))) {
            alert('Please enter a valid recipient account number');
            return false;
        }

        return confirm('Are you sure you want to transfer $' + amount + ' to account ' + toUser + '?');
    }
    </script>
</body>
</html>