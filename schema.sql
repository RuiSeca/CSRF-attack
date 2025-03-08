-- Drop existing tables if they exist
DROP TABLE IF EXISTS transfers;
DROP TABLE IF EXISTS users;

-- Create users table with all necessary fields
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    balance DECIMAL(10,2) NOT NULL DEFAULT 1000.00,
    secure_balance DECIMAL(10,2) NOT NULL DEFAULT 1000.00,
    last_secure_access DATETIME,
    CHECK (balance >= 0)
) ENGINE=InnoDB;

-- Create transfers table with security tracking
CREATE TABLE transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_user INT NOT NULL,
    to_user INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_secure BOOLEAN DEFAULT 0,
    FOREIGN KEY(from_user) REFERENCES users(id),
    FOREIGN KEY(to_user) REFERENCES users(id),
    CHECK (amount > 0)
) ENGINE=InnoDB;

-- Create indexes for better performance
CREATE INDEX idx_transfers_from_user ON transfers(from_user);
CREATE INDEX idx_transfers_to_user ON transfers(to_user);
CREATE INDEX idx_transfers_timestamp ON transfers(timestamp);
CREATE INDEX idx_users_username ON users(username);

-- Insert default test users
INSERT IGNORE INTO users (id, username, password, balance, secure_balance) VALUES 
(1, 'user', 'pass', 1000.00, 1000.00),
(2, 'attacker', 'evil', 0.00, 0.00);

-- Create trigger to update secure_balance on secure transfers
CREATE TRIGGER update_secure_balance_after_transfer
AFTER INSERT ON transfers
FOR EACH ROW
BEGIN
    IF NEW.is_secure = 1 THEN
        UPDATE users 
        SET secure_balance = secure_balance - NEW.amount
        WHERE id = NEW.from_user;
        
        UPDATE users 
        SET secure_balance = secure_balance + NEW.amount
        WHERE id = NEW.to_user;
    END IF;
END;

-- Create trigger to prevent negative balances
CREATE TRIGGER prevent_negative_balance
BEFORE UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.balance < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Insufficient funds';
    END IF;
    IF NEW.secure_balance < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Insufficient secure funds';
    END IF;
END;