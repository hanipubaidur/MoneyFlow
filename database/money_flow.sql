DROP DATABASE IF EXISTS money_flow;
CREATE DATABASE money_flow;
USE money_flow;

-- Table for accounts (bank, e-wallet, cash, etc)
CREATE TABLE accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('bank', 'e-wallet', 'cash', 'other') NOT NULL DEFAULT 'cash',
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default accounts
INSERT INTO accounts (account_name, account_type, description) VALUES
('Cash', 'cash', 'Uang tunai'),
('Blu By BCA', 'bank', 'BLU By BCA'),
('ShopeePay', 'e-wallet', 'Dompet digital ShopeePay'),
('SeaBank', 'e-wallet', 'Dompet digital SeaBank');

-- Create balance tracking table first
CREATE TABLE balance_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    total_balance DECIMAL(15,2) DEFAULT 0,
    total_savings DECIMAL(15,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert initial balance
INSERT INTO balance_tracking (total_balance, total_savings) VALUES (0, 0);

-- Income sources table with expected amount
CREATE TABLE income_sources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    source_name VARCHAR(50) NOT NULL,
    description TEXT,
    expected_amount DECIMAL(15,2) DEFAULT NULL,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_type ENUM('daily', 'weekly', 'monthly', 'yearly') DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Expense categories with budget tracking
CREATE TABLE expense_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) NOT NULL,
    description TEXT,
    is_fixed_expense BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    budget_limit DECIMAL(15,2) DEFAULT NULL,
    color VARCHAR(10) DEFAULT NULL 
);

-- Modify savings_targets status ENUM
CREATE TABLE savings_targets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    target_amount DECIMAL(15,2) NOT NULL,
    current_amount DECIMAL(15,2) DEFAULT 0,
    target_date DATE NULL,
    status ENUM('ongoing', 'achieved', 'cancelled') DEFAULT 'ongoing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transactions table with account_id (asal/tujuan uang)
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('income', 'expense') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    date DATE NOT NULL,
    description TEXT,
    income_source_id INT NULL,
    expense_category_id INT NULL,
    account_id INT NULL, -- NEW: Asal/tujuan uang (bank, e-wallet, cash)
    status ENUM('completed', 'pending', 'cancelled', 'deleted') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    savings_type ENUM('general', 'targeted') DEFAULT 'general',
    FOREIGN KEY (income_source_id) REFERENCES income_sources(id) ON DELETE SET NULL,
    FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL
);

-- Monthly category summaries
CREATE TABLE category_monthly_summaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    total_amount DECIMAL(15,2) DEFAULT 0,
    transaction_count INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id),
    UNIQUE KEY year_month_category (year, month, category_id)
);

-- Fix views with proper JOIN conditions
CREATE VIEW v_income_summary AS
SELECT 
    i.id,
    i.source_name,
    COUNT(t.id) as transaction_count,
    COALESCE(SUM(t.amount), 0) as total_amount,
    MAX(t.date) as last_transaction
FROM income_sources i
LEFT JOIN transactions t ON i.id = t.income_source_id 
    AND t.type = 'income' 
    AND t.status = 'completed'
GROUP BY i.id, i.source_name;

CREATE VIEW v_expense_summary AS
SELECT 
    e.id,
    e.category_name,
    COUNT(t.id) as transaction_count,
    COALESCE(SUM(t.amount), 0) as total_amount
FROM expense_categories e
LEFT JOIN transactions t ON e.id = t.expense_category_id 
    AND t.type = 'expense' 
    AND t.status = 'completed'
GROUP BY e.id, e.category_name;

CREATE OR REPLACE VIEW v_savings_summary AS
SELECT 
    COALESCE(SUM(CASE WHEN savings_type = 'general' THEN amount ELSE 0 END), 0) as general_savings,
    COALESCE(SUM(CASE WHEN savings_type = 'targeted' THEN amount ELSE 0 END), 0) as targeted_savings,
    COALESCE(SUM(amount), 0) as total_savings
FROM transactions t
JOIN expense_categories ec ON t.expense_category_id = ec.id
WHERE t.type = 'expense' 
AND t.status = 'completed'
AND ec.category_name = 'Savings';

-- Insert default categories (tetap sama)
INSERT INTO income_sources (source_name, description) VALUES
('Salary', 'Monthly salary income'),
('Bonus', 'Performance and year-end bonuses'),
('Freelance', 'Freelance project income'),
('Investment', 'Return on investments'),
('Other', 'Other income sources');

INSERT INTO expense_categories (category_name, description) VALUES
('Housing', 'Rent and utilities'),
('Food', 'Groceries and dining'),
('Transportation', 'Fuel and public transport'),
('Healthcare', 'Medical expenses'),
('Entertainment', 'Recreation and hobbies'),
('Shopping', 'Personal shopping'),
('Education', 'Books and courses'),
('Savings', 'Money set aside for savings'),
('Debt/Loan', 'Debt and loan payments'),
('Other', 'Miscellaneous expenses');

-- Triggers (single trigger for balance updates)
DELIMITER //

CREATE TRIGGER after_transaction_insert
AFTER INSERT ON transactions
FOR EACH ROW
BEGIN
    -- Update monthly summaries
    IF NEW.type = 'expense' AND NEW.status = 'completed' THEN
        INSERT INTO category_monthly_summaries (
            category_id, year, month, total_amount, transaction_count
        ) 
        SELECT 
            NEW.expense_category_id,
            YEAR(NEW.date),
            MONTH(NEW.date),
            NEW.amount,
            1
        FROM expense_categories ec
        WHERE ec.id = NEW.expense_category_id
        ON DUPLICATE KEY UPDATE
            total_amount = total_amount + NEW.amount,
            transaction_count = transaction_count + 1;
    END IF;

    -- Update balance tracking with accurate total calculation
    IF NEW.status = 'completed' THEN
        UPDATE balance_tracking SET
            total_balance = (
                SELECT COALESCE(SUM(
                    CASE 
                        WHEN type = 'income' AND status = 'completed' THEN amount 
                        WHEN type = 'expense' AND status = 'completed' THEN -amount
                        ELSE 0
                    END
                ), 0)
                FROM transactions
            ),
            total_savings = CASE
                WHEN NEW.expense_category_id IN (SELECT id FROM expense_categories WHERE category_name = 'Savings')
                THEN total_savings + NEW.amount
                ELSE total_savings
            END
        WHERE id = 1;
    END IF;
END//

DELIMITER ;