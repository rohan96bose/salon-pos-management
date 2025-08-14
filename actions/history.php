<?php
header('Content-Type: application/json');
include('../includes/db.php'); // Assumes you have $pdo

$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

if (!$customer_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
    exit;
}

try {
    $sql = "SELECT id, invoice_date, discount_type, total_amount, discounted_amount
            FROM invoices
            WHERE customer_id = :customer_id
            ORDER BY invoice_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':customer_id' => $customer_id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $history = [];
    $totalDiscountedRaw = 0;

    foreach ($invoices as $invoice) {
        $invoice_id = $invoice['id'];
        $date = date('Y-m-d', strtotime($invoice['invoice_date']));
        $discount_type = $invoice['discount_type'] ?? 'none';
        $total = number_format($invoice['total_amount'], 2);
        $discountedRaw = $invoice['discounted_amount'] ?? 0;
        $discounted = number_format($discountedRaw, 2);
        $totalDiscountedRaw += $discountedRaw;

        $sqlServices = "
            SELECT s.name AS service_name, u.name AS employee_name
            FROM invoice_services ins
            LEFT JOIN services s ON ins.service_id = s.id
            LEFT JOIN users u ON ins.employee_id = u.id
            WHERE ins.invoice_id = :invoice_id
        ";
        $stmtServices = $pdo->prepare($sqlServices);
        $stmtServices->execute([':invoice_id' => $invoice_id]);
        $services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);

        $detailsArr = [];
        foreach ($services as $srv) {
            $srvName = $srv['service_name'] ?? 'Unknown Service';
            $empName = $srv['employee_name'] ?? 'Unknown Employee';
            $detailsArr[] = "$srvName by $empName";
        }

        $details = $detailsArr ? implode(', ', $detailsArr) : 'Invoice created (no services recorded)';

        $history[] = [
            'invoice_id' => $invoice_id,
            'date' => $date,
            'details' => $details,
            'discount_type' => $discount_type,
            'total_amount' => $total,
            'discounted_amount' => $discounted
        ];
    }

    echo json_encode([
        'success' => true,
        'history' => $history,
        'total_discounted' => number_format($totalDiscountedRaw, 2)
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
