-- =====================================================================
-- North South University
-- Department of Electrical and Computer Engineering
-- Course: CSE311L - Database Management Systems Lab (Section 1)
-- Group 4: Banking Account & Transaction Management System
-- Database Name: group4_banking
-- Exported SQL Script with Full Schema, Constraints, Sample Data,
-- Views, Stored Procedures, Functions, Triggers, and Role Grants
-- =====================================================================

DROP DATABASE IF EXISTS group4_banking;
CREATE DATABASE group4_banking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE group4_banking;

-- =====================================================================
-- 1. TABLE CREATION & INTEGRITY CONSTRAINTS
-- =====================================================================

-- Table 1: CUSTOMER
CREATE TABLE CUSTOMER (
    CustomerID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    PhoneNumber VARCHAR(20) NOT NULL,
    Address VARCHAR(255) NOT NULL,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table 2: BRANCH
CREATE TABLE BRANCH (
    BranchID INT AUTO_INCREMENT PRIMARY KEY,
    BranchName VARCHAR(100) NOT NULL UNIQUE,
    Location VARCHAR(100) NOT NULL,
    Address VARCHAR(255) NOT NULL,
    PhoneNumber VARCHAR(20) NOT NULL
) ENGINE=InnoDB;

-- Table 3: ACCOUNT
CREATE TABLE ACCOUNT (
    AccountID INT AUTO_INCREMENT PRIMARY KEY,
    AccountNumber VARCHAR(20) NOT NULL UNIQUE,
    AccountType ENUM('Savings', 'Checking', 'Fixed Deposit') NOT NULL DEFAULT 'Savings',
    Balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    OpenDate DATE NOT NULL,
    Status ENUM('Active', 'Inactive', 'Frozen', 'Closed') NOT NULL DEFAULT 'Active',
    CustomerID INT NOT NULL,
    BranchID INT NOT NULL,
    CONSTRAINT chk_account_balance CHECK (Balance >= 0.00),
    CONSTRAINT fk_account_customer FOREIGN KEY (CustomerID) REFERENCES CUSTOMER(CustomerID) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_account_branch FOREIGN KEY (BranchID) REFERENCES BRANCH(BranchID) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table 4: DEPOSIT
CREATE TABLE DEPOSIT (
    DepositID INT AUTO_INCREMENT PRIMARY KEY,
    AccountID INT NOT NULL,
    BranchID INT NOT NULL,
    Amount DECIMAL(15,2) NOT NULL,
    DepositDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PaymentMethod ENUM('Cash', 'Cheque', 'Online', 'Wire Transfer') NOT NULL DEFAULT 'Cash',
    CONSTRAINT chk_deposit_amount CHECK (Amount > 0.00),
    CONSTRAINT fk_deposit_account FOREIGN KEY (AccountID) REFERENCES ACCOUNT(AccountID) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_deposit_branch FOREIGN KEY (BranchID) REFERENCES BRANCH(BranchID) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table 5: WITHDRAWAL
CREATE TABLE WITHDRAWAL (
    WithdrawalID INT AUTO_INCREMENT PRIMARY KEY,
    AccountID INT NOT NULL,
    BranchID INT NOT NULL,
    Amount DECIMAL(15,2) NOT NULL,
    WithdrawalDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PaymentMethod ENUM('ATM', 'Cash Counter', 'Online', 'Cheque') NOT NULL DEFAULT 'Cash Counter',
    CONSTRAINT chk_withdrawal_amount CHECK (Amount > 0.00),
    CONSTRAINT fk_withdrawal_account FOREIGN KEY (AccountID) REFERENCES ACCOUNT(AccountID) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_withdrawal_branch FOREIGN KEY (BranchID) REFERENCES BRANCH(BranchID) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table 6: TRANSFER
CREATE TABLE TRANSFER (
    TransferID INT AUTO_INCREMENT PRIMARY KEY,
    FromAccountID INT NOT NULL,
    ToAccountID INT NOT NULL,
    Amount DECIMAL(15,2) NOT NULL,
    TransferDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('Pending', 'Completed', 'Failed', 'Cancelled') NOT NULL DEFAULT 'Completed',
    CONSTRAINT chk_transfer_amount CHECK (Amount > 0.00),
    CONSTRAINT chk_transfer_distinct_accounts CHECK (FromAccountID <> ToAccountID),
    CONSTRAINT fk_transfer_from_account FOREIGN KEY (FromAccountID) REFERENCES ACCOUNT(AccountID) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_transfer_to_account FOREIGN KEY (ToAccountID) REFERENCES ACCOUNT(AccountID) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table 7: LOAN
CREATE TABLE LOAN (
    LoanID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT NOT NULL,
    BranchID INT NOT NULL,
    LoanType ENUM('Home Loan', 'Car Loan', 'Personal Loan', 'Business Loan', 'Education Loan') NOT NULL,
    LoanAmount DECIMAL(15,2) NOT NULL,
    InterestRate DECIMAL(5,2) NOT NULL,
    StartDate DATE NOT NULL,
    DueDate DATE NOT NULL,
    LoanStatus ENUM('Active', 'Paid Off', 'Defaulted', 'Under Review') NOT NULL DEFAULT 'Active',
    CONSTRAINT chk_loan_amount CHECK (LoanAmount > 0.00),
    CONSTRAINT chk_interest_rate CHECK (InterestRate >= 0.00),
    CONSTRAINT chk_loan_dates CHECK (DueDate > StartDate),
    CONSTRAINT fk_loan_customer FOREIGN KEY (CustomerID) REFERENCES CUSTOMER(CustomerID) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_loan_branch FOREIGN KEY (BranchID) REFERENCES BRANCH(BranchID) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table 8: PAYMENT (Loan Repayment)
CREATE TABLE PAYMENT (
    PaymentID INT AUTO_INCREMENT PRIMARY KEY,
    LoanID INT NOT NULL,
    AccountID INT NOT NULL,
    Amount DECIMAL(15,2) NOT NULL,
    PaymentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PaymentMethod ENUM('Auto-Debit', 'Cheque', 'Online', 'Cash') NOT NULL DEFAULT 'Auto-Debit',
    CONSTRAINT chk_payment_amount CHECK (Amount > 0.00),
    CONSTRAINT fk_payment_loan FOREIGN KEY (LoanID) REFERENCES LOAN(LoanID) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_payment_account FOREIGN KEY (AccountID) REFERENCES ACCOUNT(AccountID) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table 9: ACCOUNT_AUDIT_LOG (Supporting Trigger Demonstration)
CREATE TABLE ACCOUNT_AUDIT_LOG (
    LogID INT AUTO_INCREMENT PRIMARY KEY,
    AccountID INT NOT NULL,
    OldBalance DECIMAL(15,2) NOT NULL,
    NewBalance DECIMAL(15,2) NOT NULL,
    ChangeAmount DECIMAL(15,2) NOT NULL,
    ChangeType VARCHAR(50) NOT NULL,
    ChangedAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- 2. SAMPLE DATA INSERTION
-- =====================================================================

-- Insert Branches
INSERT INTO BRANCH (BranchID, BranchName, Location, Address, PhoneNumber) VALUES
(1, 'Dhaka Main Corporate Branch', 'Motijheel', '12 Motijheel C/A, Dhaka-1000', '+880-2-9561234'),
(2, 'Gulshan Premier Branch', 'Gulshan-2', 'Plot 45, Road 11, Gulshan-2, Dhaka', '+880-2-9884567'),
(3, 'Dhanmondi Retail Branch', 'Dhanmondi', 'House 27, Road 4, Dhanmondi, Dhaka', '+880-2-9117890'),
(4, 'Uttara Commercial Branch', 'Uttara', 'Sector 3, Jashimuddin Ave, Uttara, Dhaka', '+880-2-8951122');

-- Insert Customers
INSERT INTO CUSTOMER (CustomerID, FirstName, LastName, Email, PhoneNumber, Address) VALUES
(1, 'Tanvir', 'Hossain', 'tanvir.hossain@email.com', '+8801711000001', 'House 14, Road 5, Bashundhara R/A, Dhaka'),
(2, 'Farhana', 'Rahman', 'farhana.rahman@email.com', '+8801811000002', 'Flat 4B, Sector 7, Uttara, Dhaka'),
(3, 'Tahmid', 'Chowdhury', 'tahmid.c@email.com', '+8801911000003', 'Road 12, Banani DOHS, Dhaka'),
(4, 'Sadia', 'Islam', 'sadia.islam@email.com', '+8801611000004', 'Block C, Lalmatia, Dhaka'),
(5, 'Mahir', 'Kamal', 'mahir.kamal@email.com', '+8801511000005', 'Road 8, Dhanmondi, Dhaka'),
(6, 'Nusrat', 'Jahan', 'nusrat.jahan@email.com', '+8801311000006', 'Plot 9, Mirpur DOHS, Dhaka');

-- Insert Accounts (Customers having multiple accounts)
INSERT INTO ACCOUNT (AccountID, AccountNumber, AccountType, Balance, OpenDate, Status, CustomerID, BranchID) VALUES
(1, 'AC-1001-01', 'Savings', 75000.00, '2024-01-15', 'Active', 1, 1),
(2, 'AC-1001-02', 'Checking', 120000.00, '2024-03-20', 'Active', 1, 1),
(3, 'AC-1002-01', 'Savings', 45000.00, '2024-02-10', 'Active', 2, 2),
(4, 'AC-1002-02', 'Fixed Deposit', 250000.00, '2024-05-01', 'Active', 2, 2),
(5, 'AC-1003-01', 'Checking', 18000.00, '2024-04-12', 'Active', 3, 3),
(6, 'AC-1004-01', 'Savings', 92000.00, '2024-06-18', 'Active', 4, 3),
(7, 'AC-1004-02', 'Checking', 3500.00, '2024-07-22', 'Active', 4, 1),
(8, 'AC-1005-01', 'Savings', 500.00, '2024-08-05', 'Frozen', 5, 4),
(9, 'AC-1006-01', 'Savings', 110000.00, '2024-09-10', 'Active', 6, 4);

-- Insert Deposits
INSERT INTO DEPOSIT (DepositID, AccountID, BranchID, Amount, DepositDate, PaymentMethod) VALUES
(1, 1, 1, 25000.00, '2026-10-02 10:15:00', 'Cash'),
(2, 1, 1, 30000.00, '2026-10-15 14:30:00', 'Online'),
(3, 1, 1, 15000.00, '2026-10-28 11:00:00', 'Cheque'),
(4, 2, 1, 50000.00, '2026-10-05 09:45:00', 'Wire Transfer'),
(5, 2, 1, 22000.00, '2026-10-20 16:15:00', 'Cash'),
(6, 3, 2, 12000.00, '2026-10-10 12:00:00', 'Cash'),
(7, 4, 2, 100000.00, '2026-10-01 10:00:00', 'Online'),
(8, 5, 3, 5000.00, '2026-10-12 11:30:00', 'Cash'),
(9, 6, 3, 40000.00, '2026-10-14 15:00:00', 'Online'),
(10, 9, 4, 65000.00, '2026-10-18 13:20:00', 'Cheque'),
(11, 1, 1, 5000.00, '2026-11-05 10:30:00', 'Online');

-- Insert Withdrawals
INSERT INTO WITHDRAWAL (WithdrawalID, AccountID, BranchID, Amount, WithdrawalDate, PaymentMethod) VALUES
(1, 1, 1, 5000.00, '2026-10-04 11:30:00', 'ATM'),
(2, 1, 1, 20000.00, '2026-10-18 15:45:00', 'Cash Counter'),
(3, 2, 1, 25000.00, '2026-10-08 12:10:00', 'Cheque'),
(4, 2, 1, 28000.00, '2026-10-22 14:00:00', 'Online'),
(5, 3, 2, 8000.00, '2026-10-15 16:30:00', 'ATM'),
(6, 5, 3, 3000.00, '2026-10-16 10:00:00', 'ATM'),
(7, 6, 3, 2000.00, '2026-10-25 11:15:00', 'ATM'),
(8, 7, 1, 1500.00, '2026-10-26 13:40:00', 'ATM');

-- Insert Transfers
INSERT INTO TRANSFER (TransferID, FromAccountID, ToAccountID, Amount, TransferDate, Status) VALUES
(1, 1, 3, 4000.00, '2026-10-06 14:00:00', 'Completed'),
(2, 2, 5, 10000.00, '2026-10-12 11:20:00', 'Completed'),
(3, 3, 1, 2500.00, '2026-10-16 10:15:00', 'Completed'),
(4, 2, 6, 15000.00, '2026-10-24 16:00:00', 'Completed'),
(5, 6, 1, 3000.00, '2026-10-27 12:45:00', 'Completed'),
(6, 1, 2, 8000.00, '2026-10-30 09:30:00', 'Completed'),
(7, 1, 3, 1200.00, '2026-11-08 11:00:00', 'Completed');

-- Insert Loans
INSERT INTO LOAN (LoanID, CustomerID, BranchID, LoanType, LoanAmount, InterestRate, StartDate, DueDate, LoanStatus) VALUES
(1, 1, 1, 'Home Loan', 2500000.00, 8.50, '2024-01-01', '2034-01-01', 'Active'),
(2, 2, 2, 'Car Loan', 800000.00, 9.00, '2024-03-01', '2029-03-01', 'Active'),
(3, 3, 3, 'Personal Loan', 150000.00, 11.50, '2024-05-15', '2025-05-15', 'Defaulted'),
(4, 4, 3, 'Business Loan', 1200000.00, 10.00, '2023-06-01', '2026-06-01', 'Active'),
(5, 5, 4, 'Education Loan', 300000.00, 7.50, '2023-01-10', '2025-01-10', 'Active'),
(6, 6, 4, 'Personal Loan', 50000.00, 12.00, '2024-08-01', '2025-02-01', 'Active');

-- Insert Payments (Loan Repayments)
INSERT INTO PAYMENT (PaymentID, LoanID, AccountID, Amount, PaymentDate, PaymentMethod) VALUES
(1, 1, 1, 35000.00, '2026-10-05 10:00:00', 'Auto-Debit'),
(2, 1, 1, 35000.00, '2026-11-05 10:00:00', 'Auto-Debit'),
(3, 2, 3, 18000.00, '2026-10-10 11:00:00', 'Auto-Debit'),
(4, 3, 5, 20000.00, '2024-08-20 14:00:00', 'Cash'),
(5, 4, 6, 50000.00, '2026-10-15 12:00:00', 'Online'),
(6, 5, 8, 40000.00, '2024-02-15 09:30:00', 'Cheque');

-- =====================================================================
-- 3. VIEWS (Question 11 Requirement)
-- =====================================================================

-- View 1: Customer Account & Branch Summary View
CREATE OR REPLACE VIEW v_CustomerAccountSummary AS
SELECT 
    c.CustomerID,
    CONCAT(c.FirstName, ' ', c.LastName) AS CustomerFullName,
    c.Email,
    c.PhoneNumber,
    a.AccountID,
    a.AccountNumber,
    a.AccountType,
    a.Balance,
    a.Status AS AccountStatus,
    b.BranchName,
    b.Location AS BranchLocation
FROM CUSTOMER c
JOIN ACCOUNT a ON c.CustomerID = a.CustomerID
JOIN BRANCH b ON a.BranchID = b.BranchID;

-- View 2: Outstanding Loan & Repayment Status View
CREATE OR REPLACE VIEW v_OutstandingLoanReport AS
SELECT 
    l.LoanID,
    CONCAT(c.FirstName, ' ', c.LastName) AS BorrowerName,
    c.PhoneNumber,
    l.LoanType,
    l.LoanAmount,
    COALESCE(SUM(p.Amount), 0) AS TotalPaid,
    GREATEST(l.LoanAmount - COALESCE(SUM(p.Amount), 0), 0) AS OutstandingBalance,
    l.DueDate,
    l.LoanStatus,
    CASE 
        WHEN l.DueDate < CURDATE() AND (l.LoanAmount - COALESCE(SUM(p.Amount), 0)) > 0 THEN 'OVERDUE'
        ELSE 'CURRENT'
    END AS DueStatus
FROM LOAN l
JOIN CUSTOMER c ON l.CustomerID = c.CustomerID
LEFT JOIN PAYMENT p ON l.LoanID = p.LoanID
GROUP BY l.LoanID, c.CustomerID, c.FirstName, c.LastName, c.PhoneNumber, l.LoanType, l.LoanAmount, l.DueDate, l.LoanStatus;

-- =====================================================================
-- 4. STORED PROCEDURES (Question 12 Requirement)
-- =====================================================================

DELIMITER $$

-- Procedure to securely transfer money with atomic transaction handling
DROP PROCEDURE IF EXISTS sp_PerformFundTransfer$$
CREATE PROCEDURE sp_PerformFundTransfer(
    IN p_FromAccountID INT,
    IN p_ToAccountID INT,
    IN p_Amount DECIMAL(15,2),
    OUT p_StatusCode INT,
    OUT p_StatusMessage VARCHAR(255)
)
proc_label: BEGIN
    DECLARE v_SenderStatus VARCHAR(20);
    DECLARE v_ReceiverStatus VARCHAR(20);
    DECLARE v_SenderBalance DECIMAL(15,2);

    -- Exit handler for any unexpected SQL exception
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_StatusCode = 500;
        SET p_StatusMessage = 'Transaction rolled back due to internal database error.';
    END;

    -- Basic validation
    IF p_FromAccountID = p_ToAccountID THEN
        SET p_StatusCode = 400;
        SET p_StatusMessage = 'Error: Origin and destination accounts cannot be the same.';
        LEAVE proc_label;
    END IF;

    IF p_Amount <= 0 THEN
        SET p_StatusCode = 400;
        SET p_StatusMessage = 'Error: Transfer amount must be positive.';
        LEAVE proc_label;
    END IF;

    START TRANSACTION;

    -- Lock and inspect sender account
    SELECT Balance, Status INTO v_SenderBalance, v_SenderStatus
    FROM ACCOUNT WHERE AccountID = p_FromAccountID FOR UPDATE;

    IF v_SenderStatus IS NULL OR v_SenderStatus <> 'Active' THEN
        ROLLBACK;
        SET p_StatusCode = 404;
        SET p_StatusMessage = 'Error: Sender account is invalid or not active.';
        LEAVE proc_label;
    END IF;

    IF v_SenderBalance < p_Amount THEN
        ROLLBACK;
        SET p_StatusCode = 402;
        SET p_StatusMessage = CONCAT('Error: Insufficient funds. Available: ', v_SenderBalance, ', Requested: ', p_Amount);
        LEAVE proc_label;
    END IF;

    -- Lock and inspect receiver account
    SELECT Status INTO v_ReceiverStatus
    FROM ACCOUNT WHERE AccountID = p_ToAccountID FOR UPDATE;

    IF v_ReceiverStatus IS NULL OR v_ReceiverStatus <> 'Active' THEN
        ROLLBACK;
        SET p_StatusCode = 404;
        SET p_StatusMessage = 'Error: Receiver account is invalid or not active.';
        LEAVE proc_label;
    END IF;

    -- Debit sender
    UPDATE ACCOUNT SET Balance = Balance - p_Amount WHERE AccountID = p_FromAccountID;

    -- Credit receiver
    UPDATE ACCOUNT SET Balance = Balance + p_Amount WHERE AccountID = p_ToAccountID;

    -- Record in TRANSFER table
    INSERT INTO TRANSFER (FromAccountID, ToAccountID, Amount, TransferDate, Status)
    VALUES (p_FromAccountID, p_ToAccountID, p_Amount, NOW(), 'Completed');

    COMMIT;
    SET p_StatusCode = 200;
    SET p_StatusMessage = CONCAT('Success: Transferred BDT ', p_Amount, ' from AC #', p_FromAccountID, ' to AC #', p_ToAccountID);
END$$

DELIMITER ;

-- =====================================================================
-- 5. FUNCTIONS (Question 13 Requirement)
-- =====================================================================

DELIMITER $$

-- Function 1: Calculate outstanding loan balance for a specific loan
DROP FUNCTION IF EXISTS fn_CalculateOutstandingLoan$$
CREATE FUNCTION fn_CalculateOutstandingLoan(p_LoanID INT)
RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_LoanAmount DECIMAL(15,2);
    DECLARE v_TotalPaid DECIMAL(15,2);
    DECLARE v_Outstanding DECIMAL(15,2);

    SELECT LoanAmount INTO v_LoanAmount FROM LOAN WHERE LoanID = p_LoanID;
    
    IF v_LoanAmount IS NULL THEN
        RETURN 0.00;
    END IF;

    SELECT COALESCE(SUM(Amount), 0.00) INTO v_TotalPaid FROM PAYMENT WHERE LoanID = p_LoanID;
    
    SET v_Outstanding = v_LoanAmount - v_TotalPaid;
    
    IF v_Outstanding < 0 THEN
        SET v_Outstanding = 0.00;
    END IF;

    RETURN v_Outstanding;
END$$

-- Function 2: Aggregate total deposits balance across all accounts of a customer
DROP FUNCTION IF EXISTS fn_GetCustomerTotalBalance$$
CREATE FUNCTION fn_GetCustomerTotalBalance(p_CustomerID INT)
RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_TotalBal DECIMAL(15,2);

    SELECT COALESCE(SUM(Balance), 0.00) INTO v_TotalBal
    FROM ACCOUNT
    WHERE CustomerID = p_CustomerID AND Status = 'Active';

    RETURN v_TotalBal;
END$$

DELIMITER ;

-- =====================================================================
-- 6. TRIGGERS (Question 14 Requirement)
-- =====================================================================

DELIMITER $$

-- Trigger 1: Automatically update Account Balance after a Deposit is inserted
DROP TRIGGER IF EXISTS trg_AfterDepositUpdateBalance$$
CREATE TRIGGER trg_AfterDepositUpdateBalance
AFTER INSERT ON DEPOSIT
FOR EACH ROW
BEGIN
    UPDATE ACCOUNT
    SET Balance = Balance + NEW.Amount
    WHERE AccountID = NEW.AccountID;
END$$

-- Trigger 2: Audit account balance changes into ACCOUNT_AUDIT_LOG
DROP TRIGGER IF EXISTS trg_AuditAccountBalanceChange$$
CREATE TRIGGER trg_AuditAccountBalanceChange
AFTER UPDATE ON ACCOUNT
FOR EACH ROW
BEGIN
    IF OLD.Balance <> NEW.Balance THEN
        INSERT INTO ACCOUNT_AUDIT_LOG (
            AccountID, 
            OldBalance, 
            NewBalance, 
            ChangeAmount, 
            ChangeType, 
            ChangedAt
        ) VALUES (
            NEW.AccountID,
            OLD.Balance,
            NEW.Balance,
            (NEW.Balance - OLD.Balance),
            CASE 
                WHEN NEW.Balance > OLD.Balance THEN 'CREDIT'
                ELSE 'DEBIT'
            END,
            NOW()
        );
    END IF;
END$$

DELIMITER ;

-- =====================================================================
-- 7. GRANT AND USER PRIVILEGES (Question 15 Requirement)
-- Note: User creation syntax for MySQL environment
-- =====================================================================

-- Ensure MySQL privilege tables are healthy (repairs any corrupted indexes in XAMPP)
REPAIR TABLE mysql.db;
REPAIR TABLE mysql.user;
FLUSH PRIVILEGES;

-- Create bank staff roles/users
CREATE USER IF NOT EXISTS 'bank_teller'@'localhost' IDENTIFIED BY 'TellerPass2026!';
CREATE USER IF NOT EXISTS 'bank_manager'@'localhost' IDENTIFIED BY 'ManagerPass2026!';
CREATE USER IF NOT EXISTS 'bank_auditor'@'localhost' IDENTIFIED BY 'AuditorPass2026!';

-- Teller Privileges: View customers, accounts, branches; insert daily deposits & withdrawals
GRANT SELECT ON group4_banking.CUSTOMER TO 'bank_teller'@'localhost';
GRANT SELECT ON group4_banking.ACCOUNT TO 'bank_teller'@'localhost';
GRANT SELECT ON group4_banking.BRANCH TO 'bank_teller'@'localhost';
GRANT INSERT, SELECT ON group4_banking.DEPOSIT TO 'bank_teller'@'localhost';
GRANT INSERT, SELECT ON group4_banking.WITHDRAWAL TO 'bank_teller'@'localhost';
GRANT SELECT ON group4_banking.v_CustomerAccountSummary TO 'bank_teller'@'localhost';

-- Manager Privileges: Full CRUD on operational tables and execute stored procedures
GRANT ALL PRIVILEGES ON group4_banking.* TO 'bank_manager'@'localhost';

-- Auditor Privileges: Read-only access to records, views, and audit trail
GRANT SELECT ON group4_banking.ACCOUNT_AUDIT_LOG TO 'bank_auditor'@'localhost';
GRANT SELECT ON group4_banking.v_CustomerAccountSummary TO 'bank_auditor'@'localhost';
GRANT SELECT ON group4_banking.v_OutstandingLoanReport TO 'bank_auditor'@'localhost';
GRANT SELECT ON group4_banking.TRANSFER TO 'bank_auditor'@'localhost';
GRANT SELECT ON group4_banking.LOAN TO 'bank_auditor'@'localhost';
GRANT SELECT ON group4_banking.PAYMENT TO 'bank_auditor'@'localhost';

FLUSH PRIVILEGES;

-- =====================================================================
-- END OF DATABASE SCRIPT
-- =====================================================================
