<?php
header('Content-Type: application/json');
include('../includes/db.php');

$range = $_GET['days'] ?? 'yearly';

if ($range === 'weekly') {
    $sql = "
        SELECT YEARWEEK(created_at, 1) AS yearweek, COUNT(*) AS total
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
        $values[] = (int)$row['total'];
    }
} elseif ($range === 'quarterly') {
    $year = date('Y');
    $sql = "
        SELECT QUARTER(created_at) AS quarter, COUNT(*) AS total
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
        $values[(int)$row['quarter'] - 1] = (int)$row['total'];
    }
} else { // yearly
    $year = date('Y');
    $sql = "
        SELECT MONTH(created_at) AS month, COUNT(*) AS total
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
        $values[$index] = (int)$row['total'];
    }
}

echo json_encode(['labels' => $labels, 'values' => $values]);
