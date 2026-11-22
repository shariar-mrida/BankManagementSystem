<?php
/**
 * Shared Application Header
 * Banking Account & Transaction Management System
 */
require_once __DIR__ . '/../config/db.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$currentTable = $_GET['t'] ?? '';
$isConnected = isDBConnected();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : ''; ?>Banking Account &amp; Transaction Management System</title>
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<header class="site-header">
    <div class="header-top">
        <div class="uni-brand">
            <span class="uni-badge">CORE BANKING SYSTEM</span>
            <span>Relational Database Management &bull; Database: <strong>group4_banking</strong></span>
        </div>
        <div>
            <?php if ($isConnected): ?>
                <span class="db-status-pill connected" title="Connected to MySQL database group4_banking">
                    <span class="status-dot online"></span> MySQL Connected
                </span>
            <?php else: ?>
                <span class="db-status-pill offline" title="Database offline or not configured. Falling back to diagnostic mode.">
                    <span class="status-dot offline"></span> DB Disconnected
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="header-nav">
        <a href="index.php" class="project-title">
            BANKING<span>PORTAL</span>
        </a>
        <nav>
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="tables.php" class="nav-link <?php echo ($currentPage == 'tables.php' && empty($currentTable)) ? 'active' : ''; ?>">All Tables</a></li>
                <li><a href="tables.php?t=CUSTOMER" class="nav-link <?php echo ($currentTable == 'CUSTOMER') ? 'active' : ''; ?>">Customers</a></li>
                <li><a href="tables.php?t=ACCOUNT" class="nav-link <?php echo ($currentTable == 'ACCOUNT') ? 'active' : ''; ?>">Accounts</a></li>
                <li><a href="tables.php?t=DEPOSIT" class="nav-link <?php echo ($currentTable == 'DEPOSIT') ? 'active' : ''; ?>">Deposits</a></li>
                <li><a href="tables.php?t=WITHDRAWAL" class="nav-link <?php echo ($currentTable == 'WITHDRAWAL') ? 'active' : ''; ?>">Withdrawals</a></li>
                <li><a href="tables.php?t=TRANSFER" class="nav-link <?php echo ($currentTable == 'TRANSFER') ? 'active' : ''; ?>">Transfers</a></li>
                <li><a href="tables.php?t=LOAN" class="nav-link <?php echo ($currentTable == 'LOAN') ? 'active' : ''; ?>">Loans</a></li>
                <li><a href="tables.php?t=PAYMENT" class="nav-link <?php echo ($currentTable == 'PAYMENT') ? 'active' : ''; ?>">Payments</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="container">
<?php if (!$isConnected && isset($dbError) && $dbError): ?>
    <div class="alert alert-warning">
        <div>
            <strong>Database Offline Mode:</strong> Could not establish connection to MySQL (<code><?php echo htmlspecialchars($dbError); ?></code>).<br>
            Please make sure MySQL is started in XAMPP or configure <code>DB_HOST</code> / <code>MYSQL_URL</code>.
        </div>
    </div>
<?php endif; ?>
