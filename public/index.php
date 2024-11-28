<?php
// public/index.php
require_once '../config.php';
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSRF Attack Demo</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>CSRF Attack Demonstration</h1>
            <p>This demonstration shows how Cross-Site Request Forgery (CSRF) attacks work and how to protect against them.</p>
        </header>

        <main>
            <div class="error">
                ⚠️ Educational Purpose Only: This demonstration is for learning about web security.
            </div>

            <div class="form-container">
                <h2>Test the CSRF Attack:</h2>
                <ol style="text-align: left; margin: 20px 0;">
                    <li>First, <?php if ($isLoggedIn): ?>
                        <a href="transfer.php">visit the transfer page</a>
                    <?php else: ?>
                        <a href="login.php">log in</a> with username: user, password: pass
                    <?php endif; ?></li>
                    <li>Check your initial balance</li>
                    <li>Open the malicious page in a new tab</li>
                    <li>Return to see the changes</li>
                </ol>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                <div class="error" style="margin: 0;">
                    <h3>Vulnerable Version</h3>
                    <p>No CSRF protection:</p>
                    <a href="transfer.php">Try Vulnerable Transfer</a>
                </div>

                <div class="success" style="margin: 0;">
                    <h3>Secure Version</h3>
                    <p>Includes CSRF protection:</p>
                    <a href="secure_transfer.php">Try Secure Transfer</a>
                </div>
            </div>

            <p><a href="../attack/evil.html" target="_blank" class="attack-link">Launch Attack Demo</a></p>

            <?php if ($isLoggedIn): ?>
                <p><a href="logout.php">Logout</a></p>
            <?php endif; ?>
        </main>

        <footer>
            <p>Implementation Details:</p>
            <ul style="list-style: none; margin: 10px 0;">
                <li>• Built with PHP and SQLite</li>
                <li>• Shows both vulnerable and protected implementations</li>
                <li>• Demonstrates CSRF token protection</li>
            </ul>
            <p>&copy; 2024 CSRF Demo</p>
        </footer>
    </div>

    <script src="js/script.js"></script>
</body>
</html>