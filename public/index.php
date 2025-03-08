<?php
// public/index.php
require_once '../config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSRF Attack Demo</title>
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

        header {
            text-align: center;
            margin-bottom: 30px;
        }

        header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .form-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
        }

        .form-container h2 {
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .form-container ol {
            padding-left: 20px;
            margin: 20px 0;
        }

        .form-container li {
            margin: 10px 0;
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

        .version-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        a {
            color: #007bff;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .attack-link {
            display: inline-block;
            background-color: #dc3545;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
            transition: background-color 0.2s ease;
        }

        .attack-link:hover {
            background-color: #c82333;
            text-decoration: none;
        }

        footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            color: #6c757d;
        }

        footer ul {
            list-style: none;
            margin: 15px 0;
        }

        footer li {
            margin: 5px 0;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .version-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                <ol>                       
                    <li>
                        First, create the database:
                        <a href="http://crfs.infinityfreeapp.com/create_db.php" target="_blank" rel="noopener noreferrer">Click here</a>.
                    </li>

                    <!-- Step to log in or visit the transfer page -->
                    <li>Secondly, 
                        <?php if ($isLoggedIn): ?>
                            <a href="transfer.php">visit the transfer page</a>.
                        <?php else: ?>
                            <a href="login.php">log in</a> with username: <code>user</code>, password: <code>pass</code>.
                        <?php endif; ?>
                    </li>

                    <!-- Remaining steps -->
                    <li>Check your initial balance.</li>
                    <li>Open the malicious page in a new tab.</li>
                    <li>Return to see the changes.</li>
                </ol>
            </div>

            <div class="version-grid">
                <div class="error">
                    <h3>Vulnerable Version</h3>
                    <p>No CSRF protection:</p>
                    <a href="transfer.php">Try Vulnerable Transfer</a>
                </div>

                <div class="success">
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
            <ul>
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