<?php
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
$unauthorizedTransfer = null;

// Get both balances for display purposes
$stmt = $db->prepare('SELECT balance, secure_balance FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$balance = $user['secure_balance'];
$regularBalance = $user['balance'];

// Get list of available users for the transfer form (excluding attacker)
$stmt = $db->prepare('
    SELECT id, username 
    FROM users 
    WHERE id != ? 
    AND id != 2  -- Exclude attacker from secure transfers
    ORDER BY username ASC
');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$available_users = [];
while ($row = $result->fetch_assoc()) {
    $available_users[] = $row;
}

// Check for unauthorized transfers
$stmt = $db->prepare('
    SELECT t.*, u.username as recipient_name 
    FROM transfers t
    JOIN users u ON t.to_user = u.id
    WHERE t.from_user = ? 
    AND t.is_secure = 0 
    AND t.timestamp > (SELECT COALESCE(last_secure_access, NOW() - INTERVAL 1 MINUTE) 
    FROM users WHERE id = ?)
    ORDER BY t.timestamp DESC
');
$stmt->bind_param('ii', $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$unauthorizedTransfer = $result->fetch_assoc();

if ($unauthorizedTransfer) {
    try {
        $db->begin_transaction();
        
        // Reverse unauthorized transfer
        $stmt = $db->prepare('UPDATE users SET balance = secure_balance, last_secure_access = NOW() WHERE id = ?');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        
        $stmt = $db->prepare('UPDATE users SET balance = balance - ? WHERE id = ?');
        $stmt->bind_param('di', $unauthorizedTransfer['amount'], $unauthorizedTransfer['to_user']);
        $stmt->execute();
        
        $db->commit();
        $error = "⚠️ Unauthorized transfer detected and reversed. Your balance has been restored.";
    } catch (Exception $e) {
        $db->rollback();
        $error = "Error handling unauthorized transfer: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $to_user = intval($_POST['to_user']);
    
    if (!CSRFProtection::verifyToken($_POST['csrf_token'])) {
        $error = "Invalid CSRF token";
    } elseif ($amount <= 0 || $amount > 5000) {
        $error = "Amount must be between $1 and $5000";
    } elseif ($amount > $balance) {
        $error = "Insufficient secure funds";
    } else {
        // First verify that the recipient user exists
        $stmt = $db->prepare('SELECT id FROM users WHERE id = ?');
        $stmt->bind_param('i', $to_user);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Recipient user does not exist";
        } elseif ($to_user === $_SESSION['user_id']) {
            $error = "Cannot transfer to yourself";
        } else {
            $db->begin_transaction();
            
            try {
                // Update sender's secure balance
                $stmt = $db->prepare('UPDATE users SET secure_balance = secure_balance - ? WHERE id = ? AND secure_balance >= ?');
                $stmt->bind_param('ddi', $amount, $_SESSION['user_id'], $amount);
                $stmt->execute();
                
                // Update receiver's secure balance
                $stmt = $db->prepare('UPDATE users SET secure_balance = secure_balance + ? WHERE id = ?');
                $stmt->bind_param('di', $amount, $to_user);
                $stmt->execute();
                
                // Record the secure transfer
                $stmt = $db->prepare('INSERT INTO transfers (from_user, to_user, amount, is_secure) VALUES (?, ?, ?, 1)');
                $stmt->bind_param('iid', $_SESSION['user_id'], $to_user, $amount);
                $stmt->execute();
                
                $db->commit();
                $success = "Secure transfer successful!";
                
                // Refresh both balances after successful transfer
                $stmt = $db->prepare('SELECT balance, secure_balance FROM users WHERE id = ?');
                $stmt->bind_param('i', $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $balance = $user['secure_balance'];
                $regularBalance = $user['balance'];
                
            } catch (Exception $e) {
                $db->rollback();
                $error = "Transfer failed: " . $e->getMessage();
            }
        }
    }
}

// Get both sent and received secure transactions
$stmt = $db->prepare('
    SELECT 
        t.*,
        u.username,
        CASE 
            WHEN t.from_user = ? THEN CONCAT("To: ", u.username)
            ELSE CONCAT("From: ", (SELECT username FROM users WHERE id = t.from_user))
        END as transaction_label
    FROM transfers t
    JOIN users u ON t.to_user = u.id
    WHERE (t.from_user = ? OR t.to_user = ?) 
    AND t.is_secure = 1
    ORDER BY t.timestamp DESC 
    LIMIT 5
');

$stmt->bind_param('iii', $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$recentTransactions = [];
while ($row = $result->fetch_assoc()) {
    $recentTransactions[] = $row;
}

$csrf_token = CSRFProtection::generateToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Transfer - CSRF Demo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        .security-badge {
            display: inline-block;
            padding: 4px 8px;
            background: #28a745;
            color: white;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 8px;
        }

        .error {
            background-color: #fff3f3;
            border: 1px solid #ffdddd;
            color: #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }

        .success {
            background-color: #f0fff4;
            border: 1px solid #dcffe4;
            color: #28a745;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }

        .balance-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
            text-align: center;
        }

        .balance {
            font-size: 1.2em;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
        }

        button {
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        button:hover {
            background: #218838;
        }

        .transaction-history {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .transaction-item {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .transaction-amount {
            font-weight: bold;
        }

        .transaction-amount.debit {
            color: #dc3545;
        }


        .transaction-amount.credit {
         color: #28a745;
        }

        .security-info {
            margin-top: 20px;
            padding: 15px;
            background: #f0fff4;
            border-radius: 8px;
            color: #28a745;
            text-align: center;
        }

        .links {
            margin-top: 20px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .links a {
            color: #007bff;
            text-decoration: none;
            margin: 0 10px;
        }

        .links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Secure Fund Transfer <span class="security-badge">Protected</span></h2>

        <?php if ($unauthorizedTransfer): ?>
            <div class="balance-history">
                Unauthorized transfer of $<?= number_format($unauthorizedTransfer['amount'], 2) ?> 
                to <?= htmlspecialchars($unauthorizedTransfer['recipient_name']) ?> 
                has been reversed
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="balance-container">
            <div class="balance">
                Regular Balance: $<?= number_format($regularBalance, 2) ?><br>
                Secure Balance: $<?= number_format($balance, 2) ?>
            </div>
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
                       class="form-control"
                       required 
                       placeholder="Enter amount">
            </div>
            
            <div class="form-group">
                <label for="to_user">Select Recipient:</label>
                <select id="to_user" name="to_user" required class="form-control">
                    <option value="">Choose recipient...</option>
                    <?php foreach ($available_users as $recipient): ?>
                        <option value="<?= htmlspecialchars($recipient['id']) ?>">
                            <?= htmlspecialchars($recipient['username']) ?> 
                            (ID: <?= htmlspecialchars($recipient['id']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">Transfer Securely</button>
        </form>

        <?php if (!empty($recentTransactions)): ?>
            <div class="transaction-history">
                <h3>Recent Secure Transactions</h3>
                <?php foreach ($recentTransactions as $transaction): ?>
                    <div class="transaction-item">
                        <div>
                            <?= htmlspecialchars($transaction['transaction_label']) ?>
                        </div>
                        <div class="transaction-amount <?= $transaction['from_user'] == $_SESSION['user_id'] ? 'debit' : 'credit' ?>">
                            <?= $transaction['from_user'] == $_SESSION['user_id'] ? '-' : '+' ?>
                            $<?= number_format($transaction['amount'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="security-info">
            This page is protected against CSRF attacks. All transfers are monitored and secured.
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

        if (!toUser) {
            alert('Please select a recipient');
            return false;
        }

        return confirm('Are you sure you want to transfer $' + amount + ' to the selected recipient?');
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