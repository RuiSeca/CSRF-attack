-- schema.sql
-- First, drop existing tables if they exist to ensure clean slate
DROP TABLE IF EXISTS transfers;
DROP TABLE IF EXISTS users;

-- Create users table with all necessary fields
CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    balance REAL NOT NULL DEFAULT 1000.00,
    secure_balance REAL NOT NULL DEFAULT 1000.00,
    last_secure_access DATETIME,
    CHECK (balance >= 0)
);

-- Create transfers table with security tracking
CREATE TABLE transfers (
    id INTEGER PRIMARY KEY,
    from_user INTEGER NOT NULL,
    to_user INTEGER NOT NULL,
    amount REAL NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_secure BOOLEAN DEFAULT 0,
    FOREIGN KEY(from_user) REFERENCES users(id),
    FOREIGN KEY(to_user) REFERENCES users(id),
    CHECK (amount > 0)
);

-- Create indexes for better performance
CREATE INDEX idx_transfers_from_user ON transfers(from_user);
CREATE INDEX idx_transfers_to_user ON transfers(to_user);
CREATE INDEX idx_transfers_timestamp ON transfers(timestamp);
CREATE INDEX idx_users_username ON users(username);

-- Insert default test users
INSERT OR IGNORE INTO users (id, username, password, balance, secure_balance) VALUES 
(1, 'user', 'pass', 1000.00, 1000.00),
(2, 'attacker', 'evil', 0.00, 0.00);

-- Create view for secure transfers
CREATE VIEW secure_transfers AS
SELECT 
    t.*,
    u1.username as sender,
    u2.username as recipient
FROM transfers t
JOIN users u1 ON t.from_user = u1.id
JOIN users u2 ON t.to_user = u2.id
WHERE t.is_secure = 1;

-- Create view for unauthorized transfers
CREATE VIEW unauthorized_transfers AS
SELECT 
    t.*,
    u1.username as sender,
    u2.username as recipient
FROM transfers t
JOIN users u1 ON t.from_user = u1.id
JOIN users u2 ON t.to_user = u2.id
WHERE t.is_secure = 0;

-- Create trigger to update secure_balance on secure transfers
CREATE TRIGGER update_secure_balance_after_transfer
AFTER INSERT ON transfers
WHEN NEW.is_secure = 1
BEGIN
    UPDATE users 
    SET secure_balance = secure_balance - NEW.amount
    WHERE id = NEW.from_user;
    
    UPDATE users 
    SET secure_balance = secure_balance + NEW.amount
    WHERE id = NEW.to_user;
END;

-- Create trigger to prevent negative balances
CREATE TRIGGER prevent_negative_balance
BEFORE UPDATE ON users
BEGIN
    SELECT CASE
        WHEN NEW.balance < 0 THEN
            RAISE(ABORT, 'Insufficient funds')
        WHEN NEW.secure_balance < 0 THEN
            RAISE(ABORT, 'Insufficient secure funds')
    END;
END;