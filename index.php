<?php
/**
 * Dashboard & Banking Management Portal
 * Banking Account & Transaction Management System
 * Powers live sections using the exact queries from the project specifications.
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/config/db.php';

$pdo = getDBConnection();

$stats = [
    'customers' => 0,
    'accounts' => 0,
    'total_balance' => 0.00,
    'total_loans' => 0.00,
    'branches' => 0,
    'transactions' => 0,
];

$loanOverdueList = [];
$amlAlerts = [];
$unifiedTxnHistory = [];
$largeTxns = [];

if ($pdo) {
    try {
        // Core Counts
        $stats['customers'] = (int)$pdo->query("SELECT COUNT(*) FROM CUSTOMER")->fetchColumn();
        $stats['accounts'] = (int)$pdo->query("SELECT COUNT(*) FROM ACCOUNT WHERE Status = 'Active'")->fetchColumn();
        $stats['total_balance'] = (float)$pdo->query("SELECT COALESCE(SUM(Balance), 0) FROM ACCOUNT")->fetchColumn();
        $stats['total_loans'] = (float)$pdo->query("SELECT COALESCE(SUM(LoanAmount), 0) FROM LOAN")->fetchColumn();
        $stats['branches'] = (int)$pdo->query("SELECT COUNT(*) FROM BRANCH")->fetchColumn();
        
        $depCount = (int)$pdo->query("SELECT COUNT(*) FROM DEPOSIT")->fetchColumn();
        $wthCount = (int)$pdo->query("SELECT COUNT(*) FROM WITHDRAWAL")->fetchColumn();
        $trfCount = (int)$pdo->query("SELECT COUNT(*) FROM TRANSFER")->fetchColumn();
        $stats['transactions'] = $depCount + $wthCount + $trfCount;

        // QUERY 9 from PDF: Outstanding loans and overdue payments
        $q9_sql = "
            SELECT l.LoanID, c.CustomerID, c.FirstName, c.LastName, l.LoanAmount,
             COALESCE(SUM(p.Amount),0) AS TotalPaid,
             GREATEST(l.LoanAmount-COALESCE(SUM(p.Amount),0),0) AS OutstandingAmount,
             l.DueDate,
             CASE WHEN l.DueDate < CURDATE()
             AND l.LoanAmount-COALESCE(SUM(p.Amount),0) > 0
             THEN 'Overdue' ELSE 'Not overdue' END AS PaymentStatus
            FROM LOAN l
            JOIN CUSTOMER c ON c.CustomerID=l.CustomerID
            LEFT JOIN PAYMENT p ON p.LoanID=l.LoanID
            WHERE l.LoanStatus IN ('Active','Defaulted')
            GROUP BY l.LoanID, c.CustomerID, c.FirstName, c.LastName, l.LoanAmount, l.DueDate
            HAVING OutstandingAmount > 0
            ORDER BY l.DueDate
            LIMIT 5;
        ";
        $loanOverdueList = $pdo->query($q9_sql)->fetchAll(PDO::FETCH_ASSOC);

        // QUERY 8 from PDF: Customers with unusually frequent or high-value transactions (AML alert)
        $q8_sql = "
            SELECT c.CustomerID, c.FirstName, c.LastName,
             COUNT(*) AS OutgoingTransactionCount,
             SUM(t.Amount) AS TotalOutgoing
            FROM CUSTOMER c
            JOIN ACCOUNT a ON a.CustomerID=c.CustomerID
            JOIN (
             SELECT AccountID, Amount, WithdrawalDate AS TxnDate FROM WITHDRAWAL
             UNION ALL
             SELECT FromAccountID, Amount, TransferDate
             FROM TRANSFER WHERE Status='Completed'
            ) t ON t.AccountID=a.AccountID
            WHERE t.TxnDate >= '2026-10-01' AND t.TxnDate < '2026-11-01'
            GROUP BY c.CustomerID, c.FirstName, c.LastName
            HAVING COUNT(*) >= 5 OR SUM(t.Amount) >= 50000
            ORDER BY TotalOutgoing DESC;
        ";
        $amlAlerts = $pdo->query($q8_sql)->fetchAll(PDO::FETCH_ASSOC);

        // QUERY 6.1 from PDF: Transaction history for account 1
        $q6_sql = "
            SELECT AccountID, TxnDate, TransactionType, Amount, Details
            FROM (
             SELECT AccountID, DepositDate AS TxnDate, 'Deposit' AS TransactionType,
              Amount, PaymentMethod AS Details FROM DEPOSIT
             UNION ALL
             SELECT AccountID, WithdrawalDate, 'Withdrawal', Amount, PaymentMethod
             FROM WITHDRAWAL
             UNION ALL
             SELECT FromAccountID, TransferDate, 'Transfer sent', Amount,
              CONCAT('To account ',ToAccountID)
             FROM TRANSFER WHERE Status='Completed'
             UNION ALL
             SELECT ToAccountID, TransferDate, 'Transfer received', Amount,
              CONCAT('From account ',FromAccountID)
             FROM TRANSFER WHERE Status='Completed'
            ) AS History
            WHERE AccountID=1 ORDER BY TxnDate DESC
            LIMIT 6;
        ";
        $unifiedTxnHistory = $pdo->query($q6_sql)->fetchAll(PDO::FETCH_ASSOC);

        // QUERY 6.4 from PDF: Large deposits/withdrawals (>= 20,000)
        $q64_sql = "
            SELECT 'Deposit' AS TxnType, AccountID, Amount, DepositDate AS TxnDate
            FROM DEPOSIT WHERE Amount >= 20000
            UNION ALL
            SELECT 'Withdrawal', AccountID, Amount, WithdrawalDate
            FROM WITHDRAWAL WHERE Amount >= 20000
            ORDER BY TxnDate DESC
            LIMIT 5;
        ";
        $largeTxns = $pdo->query($q64_sql)->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        // Fallback
    }
} else {
    // Demo fallback values matching database
    $stats = [
        'customers' => 6,
        'accounts' => 8,
        'total_balance' => 545000.00,
        'total_loans' => 5000000.00,
        'branches' => 4,
        'transactions' => 26,
    ];
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 style="font-size: 1.6rem; color: #0f3460; font-weight: 700;">Banking Account &amp; Transaction Management System</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">
            Real-time management portal powered directly by MySQL database <code>group4_banking</code>.
        </p>
    </div>
    <div>
        <a href="tables.php" class="btn btn-primary">Browse All Tables &rarr;</a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-card-title">Registered Customers</span>
        <span class="stat-card-value"><?php echo number_format($stats['customers']); ?></span>
        <span class="stat-card-meta">KYC Verified Profiles</span>
    </div>
    <div class="stat-card">
        <span class="stat-card-title">Active Bank Accounts</span>
        <span class="stat-card-value"><?php echo number_format($stats['accounts']); ?></span>
        <span class="stat-card-meta">Savings, Checking, FD</span>
    </div>
    <div class="stat-card">
        <span class="stat-card-title">Total Account Balance</span>
        <span class="stat-card-value" style="font-size: 1.4rem;"><?php echo formatCurrency($stats['total_balance']); ?></span>
        <span class="stat-card-meta">Total Liquid Deposits</span>
    </div>
    <div class="stat-card">
        <span class="stat-card-title">Active Loans Disbursed</span>
        <span class="stat-card-value" style="font-size: 1.4rem;"><?php echo formatCurrency($stats['total_loans']); ?></span>
        <span class="stat-card-meta">Credit Facilities</span>
    </div>
    <div class="stat-card">
        <span class="stat-card-title">Total Operations</span>
        <span class="stat-card-value"><?php echo number_format($stats['transactions']); ?></span>
        <span class="stat-card-meta">Deposits, Withdrawals, Transfers</span>
    </div>
    <div class="stat-card">
        <span class="stat-card-title">Physical Branches</span>
        <span class="stat-card-value"><?php echo number_format($stats['branches']); ?></span>
        <span class="stat-card-meta">Dhaka Metro Network</span>
    </div>
</div>

<!-- SECTION 1: Overdue Loans & Risk Monitoring (Powered by Query 9 from PDF) -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <div>
            <h3 style="display: inline-block; margin-right: 8px;">Loan Risk &amp; Overdue Status</h3>
            <span style="font-size: 12px; color: #64748b;">(Evaluates outstanding balance against maturity date)</span>
        </div>
        <a href="tables.php?t=LOAN" class="btn btn-secondary" style="font-size: 11.5px; padding: 3px 8px;">View All Loans</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (!empty($loanOverdueList)): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Loan ID</th>
                            <th>Borrower Name</th>
                            <th>Principal Amount</th>
                            <th>Total Repaid</th>
                            <th>Outstanding Debt</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loanOverdueList as $loan): ?>
                            <tr>
                                <td><code>#<?php echo $loan['LoanID']; ?></code></td>
                                <td><strong><?php echo htmlspecialchars($loan['FirstName'] . ' ' . $loan['LastName']); ?></strong></td>
                                <td><?php echo formatCurrency($loan['LoanAmount']); ?></td>
                                <td><?php echo formatCurrency($loan['TotalPaid']); ?></td>
                                <td><strong><?php echo formatCurrency($loan['OutstandingAmount']); ?></strong></td>
                                <td><?php echo htmlspecialchars($loan['DueDate']); ?></td>
                                <td>
                                    <?php if ($loan['PaymentStatus'] === 'Overdue'): ?>
                                        <span class="badge badge-danger">Overdue</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Not Overdue</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="padding: 16px; color: #64748b;">Connect to MySQL database to display live loan risk evaluations.</p>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
    <!-- SECTION 2: AML High-Value Outgoing Alerts (Powered by Query 8 from PDF) -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>AML High-Value Outgoing Alerts</h3>
                <span style="font-size: 11px; color: #64748b;">Threshold: &ge; 5 outgoing txns or &ge; BDT 50,000</span>
            </div>
            <span class="badge badge-warning">October 2026</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (!empty($amlAlerts)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Outgoing Count</th>
                            <th>Total Outgoing</th>
                            <th>Risk Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($amlAlerts as $alert): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($alert['FirstName'] . ' ' . $alert['LastName']); ?></strong></td>
                                <td><?php echo $alert['OutgoingTransactionCount']; ?> operations</td>
                                <td><strong><?php echo formatCurrency($alert['TotalOutgoing']); ?></strong></td>
                                <td><span class="badge badge-danger">Flagged</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="padding: 16px; color: #64748b;">No high-value risk alerts triggered.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 3: Large Transactions Alert (Powered by Query 6.4 from PDF) -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Large Transactions (&ge; BDT 20,000)</h3>
                <span style="font-size: 11px; color: #64748b;">Automated central alert ledger</span>
            </div>
            <span class="badge badge-primary">High-Value</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (!empty($largeTxns)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Account</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($largeTxns as $lt): ?>
                            <tr>
                                <td>
                                    <span class="badge <?php echo ($lt['TxnType'] === 'Deposit') ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo htmlspecialchars($lt['TxnType']); ?>
                                    </span>
                                </td>
                                <td><code>AC #<?php echo $lt['AccountID']; ?></code></td>
                                <td><strong><?php echo formatCurrency($lt['Amount']); ?></strong></td>
                                <td style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars($lt['TxnDate']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="padding: 16px; color: #64748b;">No transactions currently meet the high-value threshold.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SECTION 4: Unified Transaction History (Powered by Query 6.1 from PDF) -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <div>
            <h3>Unified Transaction History (Account #1)</h3>
            <span style="font-size: 11px; color: #64748b;">Chronological audit combining deposits, withdrawals, and inter-account transfers</span>
        </div>
        <a href="tables.php?t=ACCOUNT" class="btn btn-secondary" style="font-size: 11.5px; padding: 3px 8px;">Inspect Accounts</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (!empty($unifiedTxnHistory)): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Transaction Date</th>
                            <th>Operation Type</th>
                            <th>Amount</th>
                            <th>Method / Counterparty Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unifiedTxnHistory as $txn): ?>
                            <tr>
                                <td style="font-size: 12px; color: #475569;"><?php echo htmlspecialchars($txn['TxnDate']); ?></td>
                                <td>
                                    <?php 
                                    $type = $txn['TransactionType'];
                                    $badge = 'badge-secondary';
                                    if ($type === 'Deposit' || strpos($type, 'received') !== false) {
                                        $badge = 'badge-success';
                                    } elseif ($type === 'Withdrawal' || strpos($type, 'sent') !== false) {
                                        $badge = 'badge-danger';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($type); ?></span>
                                </td>
                                <td><strong><?php echo formatCurrency($txn['Amount']); ?></strong></td>
                                <td><?php echo htmlspecialchars($txn['Details']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="padding: 16px; color: #64748b;">Connect to MySQL to view unified transaction history.</p>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION 5: Core Database Tables -->
<div class="card">
    <div class="card-header">
        <h3>Database Table Directory</h3>
        <span class="badge badge-secondary">8 Core Entities</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Primary Key</th>
                    <th>Foreign Keys</th>
                    <th>Description</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>CUSTOMER</code></td>
                    <td><span class="badge badge-primary">CustomerID</span></td>
                    <td>&mdash;</td>
                    <td>Customer identity, email, contact numbers, and KYC residential addresses.</td>
                    <td style="text-align: right;"><a href="tables.php?t=CUSTOMER" class="btn btn-secondary" style="font-size: 12px; padding: 3px 10px;">Browse Records</a></td>
                </tr>
                <tr>
                    <td><code>BRANCH</code></td>
                    <td><span class="badge badge-primary">BranchID</span></td>
                    <td>&mdash;</td>
                    <td>Bank branch office network across metropolitan divisions.</td>
                    <td style="text-align: right;"><a href="tables.php?t=BRANCH" class="btn btn-secondary" style="font-size: 12px; padding: 3px 10px;">Browse Records</a></td>
                </tr>
                <tr>
                    <td><code>ACCOUNT</code></td>
                    <td><span class="badge badge-primary">AccountID</span></td>
                    <td><span class="badge badge-secondary">CustomerID</span>, <span class="badge badge-secondary">BranchID</span></td>
                    <td>Bank accounts, balance ledgers, account types, and operational status.</td>
                    <td style="text-align: right;"><a href="tables.php?t=ACCOUNT" class="btn btn-secondary" style="font-size: 12px; padding: 3px 10px;">Browse Records</a></td>
                </tr>
                <tr>
                    <td><code>DEPOSIT</code></td>
                    <td><span class="badge badge-primary">DepositID</span></td>
                    <td><span class="badge badge-secondary">AccountID</span>, <span class="badge badge-secondary">BranchID</span></td>
                    <td>Historical incoming cash, cheque, and electronic wire deposits.</td>
                    <td style="text-align: right;"><a href="tables.php?t=DEPOSIT" class="btn btn-secondary" style="font-size: 12px; padding: 3px 10px;">Browse Records</a></td>
                </tr>
                <tr>
                    <td><code>WITHDRAWAL</code></td>
                    <td><span class="badge badge-primary">WithdrawalID</span></td>
                    <td><span class="badge badge-secondary">AccountID</span>, <span class="badge badge-secondary">BranchID</span></td>
                    <td>Outbound debit cash withdrawals via ATM, counters, and online platforms.</td>
                    <td style="text-align: right;"><a href="tables.php?t=WITHDRAWAL" class="btn btn-secondary" style="font-size: 12px; padding: 3px 10px;">Browse Records</a></td>
                </tr>
                <tr>
                    <td><code>TRANSFER</code></td>
                    <td><span class="badge badge-primary">TransferID</span></td>
                    <td><span class="badge badge-secondary">FromAccountID</span>, <span class="badge badge-secondary">ToAccountID</span></td>
                    <td>Direct inter-account fund transfers with double foreign keys.</td>
                    <td style="text-align: right;"><a href="tables.php?t=TRANSFER" class="btn btn-secondary" style="font-size: 12px; padding: 3px 10px;">Browse Records</a></td>
                </tr>
                <tr>
                    <td><code>LOAN</code></td>
                    <td><span class="badge badge-primary">LoanID</span></td>
                    <td><span class="badge badge-secondary">CustomerID</span>, <span class="badge badge-secondary">BranchID</span></td>
                    <td>Disbursed loan portfolios, interest rates, maturities, and loan statuses.</td>
                    <td style="text-align: right;"><a href="tables.php?t=LOAN" class="btn btn-secondary" style="font-size: 12px; padding: 3px 10px;">Browse Records</a></td>
                </tr>
                <tr>
                    <td><code>PAYMENT</code></td>
                    <td><span class="badge badge-primary">PaymentID</span></td>
                    <td><span class="badge badge-secondary">LoanID</span>, <span class="badge badge-secondary">AccountID</span></td>
                    <td>Installment repayments against sanctioned bank loans.</td>
                    <td style="text-align: right;"><a href="tables.php?t=PAYMENT" class="btn btn-secondary" style="font-size: 12px; padding: 3px 10px;">Browse Records</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
