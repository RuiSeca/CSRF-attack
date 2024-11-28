<?php
// public/login.php
require_once '../config.php';
session_start();

// If already logged in, redirect to transfer page
if (isset($_SESSION['user_id'])) {
    header('Location: transfer.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Use the getDatabase function from config.php
        $db = getDatabase();
        
        // Prepare and execute the query
        $stmt = $db->prepare('SELECT id, password FROM users WHERE username = :username');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($user && $password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            session_regenerate_id(true);
            header('Location: transfer.php');
            exit;
        }
        $error = "Invalid username or password.";
    } catch (Exception $e) {
        $error = "Login error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CSRF Demo</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        
        <p>Test credentials:</p>
        <ul>
            <li>Username: user</li>
            <li>Password: pass</li>
        </ul>
        
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>