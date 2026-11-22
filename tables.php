<?php
/**
 * Database Tables Viewer
 * Group 4: Banking Account & Transaction Management System
 */
$pageTitle = 'Database Tables';
require_once __DIR__ . '/config/db.php';

$validTables = [
    'CUSTOMER' => 'Customer Details & KYC Profiles',
    'BRANCH' => 'Bank Branch Offices',
    'ACCOUNT' => 'Customer Accounts & Live Balances',
    'DEPOSIT' => 'Deposit Transaction Logs',
    'WITHDRAWAL' => 'Withdrawal Transaction Logs',
    'TRANSFER' => 'Inter-Account Fund Transfers',
    'LOAN' => 'Loan Facilities & Terms',
    'PAYMENT' => 'Loan Installment Repayments',
    'ACCOUNT_AUDIT_LOG' => 'Automated Trigger Audit Log'
];

$selectedTable = isset($_GET['t']) && array_key_exists($_GET['t'], $validTables) ? $_GET['t'] : 'CUSTOMER';

$pdo = getDBConnection();
$rows = [];
$columns = [];
$tableError = null;
$totalCount = 0;

if ($pdo) {
    try {
        // Fetch rows
        $stmt = $pdo->query("SELECT * FROM `{$selectedTable}` LIMIT 100");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch column names
        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
        } else {
            $colStmt = $pdo->query("DESCRIBE `{$selectedTable}`");
            $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN);
        }

        $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$selectedTable}`");
        $totalCount = (int)$countStmt->fetchColumn();
    } catch (Exception $e) {
        $tableError = $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 style="font-size: 1.5rem; color: #0f3460; font-weight: 700;">Database Table Explorer</h1>
        <p style="color: #64748b; font-size: 13.5px;">Live inspection of records and schema from MySQL database <code>group4_banking</code>.</p>
    </div>
    <div>
        <span class="badge badge-primary" style="font-size: 13px; padding: 6px 12px;">
            Active Table: <strong><?php echo htmlspecialchars($selectedTable); ?></strong> (<?php echo $totalCount; ?> rows)
        </span>
    </div>
</div>

<!-- Table Selection Tabs -->
<div style="display: flex; gap: 6px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 16px;">
    <?php foreach ($validTables as $tName => $tDesc): ?>
        <a href="tables.php?t=<?php echo urlencode($tName); ?>" 
           class="btn <?php echo ($selectedTable === $tName) ? 'btn-primary' : 'btn-secondary'; ?>"
           style="font-size: 13px; padding: 7px 12px; white-space: nowrap;">
            <code><?php echo $tName; ?></code>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h3 style="display: inline-block; margin-right: 10px;"><code><?php echo htmlspecialchars($selectedTable); ?></code></h3>
            <span style="color: #64748b; font-size: 13px;">&mdash; <?php echo htmlspecialchars($validTables[$selectedTable]); ?></span>
        </div>
        <div>
            <span class="badge badge-secondary"><?php echo count($columns); ?> Columns</span>
            <span class="badge badge-success"><?php echo $totalCount; ?> Total Rows</span>
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if ($tableError): ?>
            <div class="alert alert-warning" style="margin: 20px;">
                <strong>Query Error:</strong> <?php echo htmlspecialchars($tableError); ?>
            </div>
        <?php elseif (!$pdo): ?>
            <div style="padding: 24px; text-align: center; color: #64748b;">
                <p><strong>Database is currently in offline mode.</strong></p>
                <p style="font-size: 13px; margin-top: 6px;">Start MySQL in XAMPP or connect a remote MySQL instance to retrieve live records from <code><?php echo htmlspecialchars($selectedTable); ?></code>.</p>
                <div style="margin-top: 14px;">
                    <a href="database/group4_banking.sql" download class="btn btn-primary" style="font-size: 12px;">Download Complete .sql Dump</a>
                </div>
            </div>
        <?php elseif (empty($rows)): ?>
            <div style="padding: 24px; text-align: center; color: #64748b;">
                <p>No records currently found in <code><?php echo htmlspecialchars($selectedTable); ?></code>.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <th><?php echo htmlspecialchars($col); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($columns as $col): 
                                    $val = $row[$col] ?? null;
                                ?>
                                    <td>
                                        <?php 
                                        if ($val === null) {
                                            echo '<span style="color: #94a3b8; font-style: italic;">NULL</span>';
                                        } elseif (strpos($col, 'Balance') !== false || strpos($col, 'Amount') !== false) {
                                            echo '<strong>' . formatCurrency($val) . '</strong>';
                                        } elseif (strpos($col, 'Date') !== false || strpos($col, 'At') !== false) {
                                            echo '<span style="font-size: 12.5px; color: #475569;">' . htmlspecialchars($val) . '</span>';
                                        } elseif ($col === 'Status' || $col === 'LoanStatus') {
                                            $badgeClass = ($val === 'Active' || $val === 'Completed') ? 'badge-success' : (($val === 'Frozen' || $val === 'Defaulted') ? 'badge-danger' : 'badge-warning');
                                            echo '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($val) . '</span>';
                                        } else {
                                            echo htmlspecialchars($val);
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
