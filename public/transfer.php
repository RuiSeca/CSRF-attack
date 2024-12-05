<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDatabase();
$error = null;
$success = null;

// Get current user's balances
$stmt = $db->prepare('SELECT balance, secure_balance FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$balance = $user['balance'];

// Get list of available users for the transfer form
$stmt = $db->prepare('
    SELECT id, username 
    FROM users 
    WHERE id != ? 
    ORDER BY 
        CASE 
            WHEN id = 2 THEN 0  -- Show attacker first
            ELSE 1 
        END,
        username ASC
');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$available_users = [];
while ($row = $result->fetch_assoc()) {
    $available_users[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $to_user = intval($_POST['to_user']);

    // First check if recipient exists
    $stmt = $db->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->bind_param('i', $to_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result->fetch_assoc()) {
        $error = "Recipient user does not exist";
    } elseif ($amount <= 0 || $amount > 5000) {
        $error = "Amount must be between $1 and $5000";
    } elseif ($amount > $balance) {
        $error = "Insufficient funds";
    } else {
        $db->begin_transaction();
        
        try {
            // Update sender's regular balance only
            $stmt = $db->prepare('UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?');
            $stmt->bind_param('ddi', $amount, $_SESSION['user_id'], $amount);
            $stmt->execute();
            
            // Update receiver's regular balance only
            $stmt = $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
            $stmt->bind_param('di', $amount, $to_user);
            $stmt->execute();
            
            // Record the non-secure transfer
            $stmt = $db->prepare('INSERT INTO transfers (from_user, to_user, amount, is_secure) VALUES (?, ?, ?, 0)');
            $stmt->bind_param('iid', $_SESSION['user_id'], $to_user, $amount);
            $stmt->execute();
            
            $db->commit();
            $success = "Regular transfer successful!";
            
            // Update displayed balance
            $stmt = $db->prepare('SELECT balance, secure_balance FROM users WHERE id = ?');
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $balance = $user['balance'];
            
        } catch (Exception $e) {
            $db->rollback();
            $error = "Transfer failed: " . $e->getMessage();
        }
    }
}

// Get recent transactions
$stmt = $db->prepare('
    SELECT t.*, u.username as recipient_name
    FROM transfers t
    JOIN users u ON t.to_user = u.id
    WHERE t.from_user = ? AND t.is_secure = 0
    ORDER BY t.timestamp DESC LIMIT 5
');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$recentTransactions = [];
while ($row = $result->fetch_assoc()) {
    $recentTransactions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regular Fund Transfer</title>
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

        .warning-badge {
            display: inline-block;
            padding: 4px 8px;
            background: #dc3545;
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
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        button:hover {
            background: #0056b3;
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

        .security-info {
            margin-top: 20px;
            padding: 15px;
            background: #fff3f3;
            border-radius: 8px;
            color: #dc3545;
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
        <h2>Regular Fund Transfer <span class="warning-badge">Vulnerable to CSRF</span></h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="balance-container">
            <div class="balance">
                Regular Balance: $<?= number_format($user['balance'], 2) ?><br>
                Secure Balance: $<?= number_format($user['secure_balance'], 2) ?>
            </div>
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
                       class="form-control"
                       required 
                       placeholder="Enter amount">
            </div>
            
            <div class="form-group">
                <label for="to_user">Select Recipient:</label>
                <select id="to_user" name="to_user" required class="form-control">
                    <option value="">Choose recipient...</option>
                    <option value="2" style="color: #dc3545; font-weight: bold;">
                        attacker (for testing CSRF attack)
                    </option>
                    <?php foreach ($available_users as $recipient): 
                        if ($recipient['id'] != 2):
                    ?>
                        <option value="<?= htmlspecialchars($recipient['id']) ?>">
                            <?= htmlspecialchars($recipient['username']) ?> 
                            (ID: <?= htmlspecialchars($recipient['id']) ?>)
                        </option>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </select>
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
                        </div>
                        <div class="transaction-amount debit">
                            -$<?= number_format($transaction['amount'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="security-info">
            ⚠️ This page is vulnerable to CSRF attacks. Use the secure transfer page for protected transactions.
        </div>

        <div class="links">
            <p><a href="index.php">Back to Home</a> | 
               <a href="secure_transfer.php">View Secure Page</a> | 
               <a href="logout.php">Logout</a></p>
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