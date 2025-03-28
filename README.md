# CSRF Attack Demonstration

This project demonstrates a Cross-Site Request Forgery (CSRF) attack and its mitigation techniques using PHP and SQLite. It includes both a vulnerable transfer page and a secure version to show how CSRF protection works.

## Project Overview

This demonstration shows how a malicious website can trick a user into performing unwanted actions on a website where they're authenticated. In this case, it demonstrates an unauthorized money transfer in a simple banking application.

### Features

- User authentication system
- Vulnerable money transfer page
- Secure money transfer page (with CSRF protection)
- Malicious attack page demonstration
- SQLite database for storing user data and transactions

## Prerequisites

- PHP 8.2 or higher
- SQLite3
- Apache/PHP built-in server
- Web browser

## Installation

1. Clone or download this repository to your local machine:

```bash
git clone [your-repository-url]
# or download and extract the ZIP file
```

2. Navigate to the project directory:

```bash
cd csrf-demo
```

3. Create the database and initial data:

```bash
php create_db.php
```

4. Start the PHP development server:

```bash
php -S localhost:8080
```

## Usage

1. Start the application:

   ```bash
   php -S localhost:8080
   ```

2. Access the application:

   - Open your browser and navigate to: `http://localhost:8080/csrf-demo/public/index.php`

3. Login with test credentials:

   - Username: `user`
   - Password: `pass`

4. Test the CSRF Attack:
   a. Login and note your initial balance ($1000)
   b. Open the malicious page in a new tab: `http://localhost:8080/csrf-demo/attack/evil.html`
   c. Return to your transfer page and observe the balance change

## Security Notes

This project demonstrates:

1. How CSRF attacks work
2. Why CSRF tokens are necessary
3. Basic security practices in web applications

## Testing Flow

1. **Normal Transfer**:

   - Login with test account
   - Go to transfer page
   - Make a manual transfer

2. **CSRF Attack**:

   - Login with test account
   - Open evil.html in new tab
   - Observe automatic transfer

3. **CSRF Protection**:
   - Try the same attack on secure_transfer.php
   - Notice the attack fails

## Common Issues & Solutions

1. Database Error:

   ```bash
   php create_db.php
   ```

2. Permission Issues:

   ```bash
   chmod 777 DataBase
   chmod 666 DataBase/bank.db
   ```

3. Port in Use:
   ```bash
   # Try different port
   php -S localhost:8081
   ```

## Educational Resources

- [OWASP CSRF](https://owasp.org/www-community/attacks/csrf)
- [CSRF Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)

## License

This project is for educational purposes only. Use at your own risk.

## Contributors

- Rui Seca ID -6895803
- Based on PHP CSRF tutorial from [phptutorial.net](https://www.phptutorial.net/php-tutorial/php-csrf/)
#   C S R F - a t t a c k  
 