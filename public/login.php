<?php
// public/login.php
require_once '../config.php';

// If already logged in, redirect to transfer page
if (isset($_SESSION['user_id'])) {
    header('Location: transfer.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $db = getDatabase();
        
        $stmt = $db->prepare('SELECT id, password FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
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
            max-width: 400px;
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

        .error {
            background-color: #fff3f3;
            border: 1px solid #ffdddd;
            color: #dc3545;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
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

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-bottom: 20px;
        }

        button:hover {
            background-color: #0056b3;
        }

        p {
            margin: 20px 0 10px 0;
            color: #666;
        }

        ul {
            list-style: none;
            margin-bottom: 20px;
            padding-left: 20px;
        }

        li {
            margin: 5px 0;
            color: #666;
        }

        a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.2s ease;
            display: inline-block;
        }

        a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        

        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
        }

        .credentials-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin: 20px 0;
        border: 1px solid #e9ecef;
}

        .credentials-toggle {
            color: #007bff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .credentials-toggle:hover {
            color: #0056b3;
        }

        .credentials-content {
            display: none;
            margin-top: 10px;
        }

        .credentials-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }

        .credentials-table th,
        .credentials-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .credentials-table th {
            background-color: #f1f3f5;
            color: #495057;
        }

        .credentials-table tr:last-child td {
            border-bottom: none;
        }

        .credentials-table tr:hover {
            background-color: #f1f3f5;
        }

        .copy-btn {
            padding: 2px 6px;
            font-size: 12px;
            background: #e9ecef;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 5px;
        }

        .copy-btn:hover {
            background: #dee2e6;
        }
    </style>
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

        <div class="credentials-section">
            <div class="credentials-toggle" onclick="toggleCredentials()">
                <span>🔑 Test Credentials</span>
                <span id="toggle-icon">▼</span>
            </div>
            <div id="credentials-content" class="credentials-content">
                <div style="font-size: 0.9em; color: #666; margin-bottom: 10px;">
                    All test accounts have initial balance to test the transfer.
                </div>
                <table class="credentials-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>user</td>
                            <td>pass</td>
                            <td>Main Test User</td>
                        </tr>
                        <tr>
                            <td>john_doe</td>
                            <td>test123</td>
                            <td>Test User</td>
                        </tr>
                        <tr>
                            <td>sarah_smith</td>
                            <td>test123</td>
                            <td>Test User</td>
                        </tr>
                        <tr>
                            <td>mike_jones</td>
                            <td>test123</td>
                            <td>Test User</td>
                        </tr>
                        <tr>
                            <td>lisa_brown</td>
                            <td>test123</td>
                            <td>Test User</td>
                        </tr>
                        <tr>
                            <td>david_wilson</td>
                            <td>test123</td>
                            <td>Test User</td>
                        </tr>
                        <tr>
                            <td>emma_davis</td>
                            <td>test123</td>
                            <td>Test User</td>
                        </tr>
                        <tr>
                            <td>alex_taylor</td>
                            <td>test123</td>
                            <td>Test User</td>
                        </tr>
                        <tr style="color: #dc3545;">
                            <td>attacker</td>
                            <td>evil</td>
                            <td>Attacker Account</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        function toggleCredentials() {
            const content = document.getElementById('credentials-content');
            const icon = document.getElementById('toggle-icon');
            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
                icon.textContent = '▲';
            } else {
                content.style.display = 'none';
                icon.textContent = '▼';
            }
        }

        // Show credentials if URL contains ?show_credentials=true
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.search.includes('show_credentials=true')) {
                document.getElementById('credentials-content').style.display = 'block';
                document.getElementById('toggle-icon').textContent = '▲';
            }
        });

   </script>
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>