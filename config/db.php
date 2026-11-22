<?php
/**
 * Database Connection & Utility Helper
 * Group 4: Banking Account & Transaction Management System
 * CSE311L - North South University
 */

// Enable error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', 0);

$db = null;
$dbError = null;

function getDatabaseConfig() {
    // Check if DATABASE_URL or MYSQL_URL is provided (typical in cloud environments like Vercel / Railway / Heroku)
    $dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
    if ($dbUrl) {
        $parsed = parse_url($dbUrl);
        return [
            'host' => $parsed['host'] ?? 'localhost',
            'port' => $parsed['port'] ?? 3306,
            'user' => $parsed['user'] ?? 'root',
            'pass' => $parsed['pass'] ?? '',
            'name' => ltrim($parsed['path'] ?? 'group4_banking', '/'),
        ];
    }

    return [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: 3306,
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'name' => getenv('DB_NAME') ?: 'group4_banking',
    ];
}

function getDBConnection() {
    global $db, $dbError;

    if ($db !== null) {
        return $db;
    }

    $config = getDatabaseConfig();

    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_TIMEOUT            => 3,
        ];

        $db = new PDO($dsn, $config['user'], $config['pass'], $options);
        $dbError = null;
        return $db;
    } catch (PDOException $e) {
        $db = null;
        $dbError = $e->getMessage();
        return null;
    }
}

// Initial connection attempt
getDBConnection();

function isDBConnected() {
    global $db;
    return ($db !== null);
}

function formatCurrency($amount) {
    if ($amount === null || $amount === '') return 'BDT 0.00';
    return 'BDT ' . number_format((float)$amount, 2);
}

function formatDate($dateString) {
    if (!$dateString) return '—';
    $time = strtotime($dateString);
    if (!$time) return htmlspecialchars($dateString);
    return date('d M Y, h:i A', $time);
}
