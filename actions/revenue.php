<?php
header('Content-Type: application/json');
include('../includes/db.php');

$range = $_GET['days'] ?? 'yearly';

if ($range === 'weekly') {
    $sql = "
        SELECT YEARWEEK(created_at, 1) AS yearweek, SUM(discounted_amount) AS total
        FROM invoices
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 WEEK)
        GROUP BY yearweek
        ORDER BY yearweek ASC
    ";
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $values = [];
    foreach ($results as $row) {
        $labels[] = 'Week ' . substr($row['yearweek'], -2);
        $values[] = (float)$row['total'];
    }
} elseif ($range === 'quarterly') {
    $year = date('Y');
    $sql = "
        SELECT QUARTER(created_at) AS quarter, SUM(discounted_amount) AS total
        FROM invoices
        WHERE YEAR(created_at) = ?
        GROUP BY quarter
        ORDER BY quarter
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$year]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = ['Q1', 'Q2', 'Q3', 'Q4'];
    $values = [0, 0, 0, 0];
    foreach ($results as $row) {
        $q = (int)$row['quarter'] - 1;
        $values[$q] = (float)$row['total'];
    }
} else { // yearly = 12-month breakdown
    $year = date('Y');
    $sql = "
        SELECT MONTH(created_at) AS month, SUM(discounted_amount) AS total
        FROM invoices
        WHERE YEAR(created_at) = ?
        GROUP BY month
        ORDER BY month
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$year]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $values = array_fill(0, 12, 0);

    foreach ($results as $row) {
        $index = (int)$row['month'] - 1;
        $values[$index] = (float)$row['total'];
    }
}

echo json_encode(['labels' => $labels, 'values' => $values]);
