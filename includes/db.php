<?php
session_start();  // Start session at the very beginning

// Load environment variables from .env (if using vlucas/phpdotenv or similar library)
require_once __DIR__ . '../../vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env if exists
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Database configuration
$host     = $_ENV['DB_HOST'] ?? 'localhost';
$db       = $_ENV['DB_NAME'] ?? 'u866300656_headturner';
$user     = $_ENV['DB_USER'] ?? 'u866300656_headturner';
$pass     = $_ENV['DB_PASS'] ?? 'U5dCYCVq5=Qx';
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return results as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('Database Connection Error: ' . $e->getMessage());
    die('Database connection failed. Please try again later.');
}
?>