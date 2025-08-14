<?php
   include('../includes/header.php');
   
   // Initialize response variables
   $error = '';
   $success = '';
   
   // Pagination setup
   $per_page = 8;
   $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
   $offset = ($current_page - 1) * $per_page;
   
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       // ====== CREATE INVOICE ======
       if (isset($_POST['create_invoice'])) {
           try {
               $pdo->beginTransaction();
   
               // Calculate totals
               $total_amount = 0;
               $services_total = 0;
               $products_total = 0;
               
               // Calculate services total
               if (!empty($_POST['services'])) {
                   foreach ($_POST['services'] as $service) {
                       if (!empty($service['service_id']) && !empty($service['employee_id'])) {
                           $service_price = $service['price'];
                           $quantity = $service['quantity'] ?? 1;
                           $discount_value = $service['discount_value'] ?? 0;
                           
                           if ($service['discount_type'] === 'flat') {
                               $service_price -= $discount_value;
                           } elseif ($service['discount_type'] === 'percent') {
                               $service_price -= ($service_price * $discount_value / 100);
                           }
                           
                           $services_total += ($service_price * $quantity);
                       }
                   }
               }
               
               // Calculate products total
               if (!empty($_POST['products'])) {
                   foreach ($_POST['products'] as $product) {
                       if (!empty($product['product_id'])) {
                           $product_price = $product['price'];
                           $quantity = $product['quantity'] ?? 1;
                           $discount_value = $product['discount_value'] ?? 0;
                           
                           if ($product['discount_type'] === 'flat') {
                               $product_price -= $discount_value;
                           } elseif ($product['discount_type'] === 'percent') {
                               $product_price -= ($product_price * $discount_value / 100);
                           }
                           
                           $products_total += ($product_price * $quantity);
                       }
                   }
               }
               
               $total_amount = $services_total + $products_total;
               $discounted_amount = $total_amount;
               
               // Apply invoice-level discount if any
               if ($_POST['discount_type'] !== 'none' && !empty($_POST['discount_value'])) {
                   $discount_value = (float)$_POST['discount_value'];
                   if ($_POST['discount_type'] === 'flat') {
                       $discounted_amount = max(0, $total_amount - $discount_value);
                   } elseif ($_POST['discount_type'] === 'percent') {
                       $discounted_amount = $total_amount * (1 - min(100, $discount_value) / 100);
                   }
               }
   
               // Insert invoice
               $stmt = $pdo->prepare("INSERT INTO invoices 
                   (customer_id, created_by, discount_type, total_amount, discounted_amount)
                   VALUES (?, ?, ?, ?, ?)");
               $stmt->execute([
                   $_POST['customer_id'],
                   $_SESSION['user_id'],
                   $_POST['discount_type'],
                   $total_amount,
                   $discounted_amount
               ]);
   
               $invoice_id = $pdo->lastInsertId();
   
               // Insert invoice services
               if (!empty($_POST['services'])) {
                   foreach ($_POST['services'] as $service) {
                       if (!empty($service['service_id']) && !empty($service['employee_id'])) {
                           $stmt = $pdo->prepare("INSERT INTO invoice_services (
                               invoice_id, service_id, employee_id, product_id, quantity, 
                               discount_type, discount_value, price
                           ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
   
                           $stmt->execute([
                               $invoice_id,
                               $service['service_id'],
                               $service['employee_id'],
                               null, // product_id is null for services
                               $service['quantity'] ?? 1,
                               $service['discount_type'] ?? 'none',
                               $service['discount_value'] ?? 0.00,
                               $service['price']
                           ]);
                       }
                   }
               }
   
               // Insert invoice products
               if (!empty($_POST['products'])) {
                   foreach ($_POST['products'] as $product) {
                       if (!empty($product['product_id'])) {
                           $stmt = $pdo->prepare("INSERT INTO invoice_services (
                               invoice_id, service_id, employee_id, product_id, quantity, 
                               discount_type, discount_value, price
                           ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
   
                           $stmt->execute([
                               $invoice_id,
                               null, // service_id is null for products
                               $product['employee_id'] ?? null, // employee is optional for products
                               $product['product_id'],
                               $product['quantity'] ?? 1,
                               $product['discount_type'] ?? 'none',
                               $product['discount_value'] ?? 0.00,
                               $product['price']
                           ]);
                           
                           // Update product stock
                           $stmt = $pdo->prepare("UPDATE product SET stock_quantity = stock_quantity - ? WHERE id = ?");
                           $stmt->execute([$product['quantity'] ?? 1, $product['product_id']]);
                       }
                   }
               }
   
               $pdo->commit();
               $success = "Invoice created successfully!";
           } catch (PDOException $e) {
               $pdo->rollBack();
               $error = "Error creating invoice: " . $e->getMessage();
           }
       }
   
       // ====== UPDATE INVOICE ======
       elseif (isset($_POST['update_invoice'])) {
           try {
               $pdo->beginTransaction();
   
               $invoice_id = $_POST['invoice_id'];
   
               // Calculate totals
               $total_amount = 0;
               $services_total = 0;
               $products_total = 0;
               
               // Calculate services total
               if (!empty($_POST['services'])) {
                   foreach ($_POST['services'] as $service) {
                       if (!empty($service['service_id']) && !empty($service['employee_id'])) {
                           $service_price = $service['price'];
                           $quantity = $service['quantity'] ?? 1;
                           $discount_value = $service['discount_value'] ?? 0;
                           
                           if ($service['discount_type'] === 'flat') {
                               $service_price -= $discount_value;
                           } elseif ($service['discount_type'] === 'percent') {
                               $service_price -= ($service_price * $discount_value / 100);
                           }
                           
                           $services_total += ($service_price * $quantity);
                       }
                   }
               }
               
               // Calculate products total
               if (!empty($_POST['products'])) {
                   foreach ($_POST['products'] as $product) {
                       if (!empty($product['product_id'])) {
                           $product_price = $product['price'];
                           $quantity = $product['quantity'] ?? 1;
                           $discount_value = $product['discount_value'] ?? 0;
                           
                           if ($product['discount_type'] === 'flat') {
                               $product_price -= $discount_value;
                           } elseif ($product['discount_type'] === 'percent') {
                               $product_price -= ($product_price * $discount_value / 100);
                           }
                           
                           $products_total += ($product_price * $quantity);
                       }
                   }
               }
               
               $total_amount = $services_total + $products_total;
               $discounted_amount = $total_amount;
               
               // Apply invoice-level discount if any
               if ($_POST['discount_type'] !== 'none' && !empty($_POST['discount_value'])) {
                   $discount_value = (float)$_POST['discount_value'];
                   if ($_POST['discount_type'] === 'flat') {
                       $discounted_amount = max(0, $total_amount - $discount_value);
                   } elseif ($_POST['discount_type'] === 'percent') {
                       $discounted_amount = $total_amount * (1 - min(100, $discount_value) / 100);
                   }
               }
   
               // Update invoice
               $stmt = $pdo->prepare("UPDATE invoices SET 
                   customer_id = ?, 
                   discount_type = ?, 
                   total_amount = ?, 
                   discounted_amount = ?
                   WHERE id = ?");
               $stmt->execute([
                   $_POST['customer_id'],
                   $_POST['discount_type'],
                   $total_amount,
                   $discounted_amount,
                   $invoice_id
               ]);
   
               // Remove existing invoice services and products
               $stmt = $pdo->prepare("DELETE FROM invoice_services WHERE invoice_id = ?");
               $stmt->execute([$invoice_id]);
   
               // Re-insert updated services
               if (!empty($_POST['services'])) {
                   foreach ($_POST['services'] as $service) {
                       if (!empty($service['service_id']) && !empty($service['employee_id'])) {
                           $stmt = $pdo->prepare("INSERT INTO invoice_services (
                               invoice_id, service_id, employee_id, product_id, quantity, 
                               discount_type, discount_value, price
                           ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
   
                           $stmt->execute([
                               $invoice_id,
                               $service['service_id'],
                               $service['employee_id'],
                               null,
                               $service['quantity'] ?? 1,
                               $service['discount_type'] ?? 'none',
                               $service['discount_value'] ?? 0.00,
                               $service['price']
                           ]);
                       }
                   }
               }
   
               // Re-insert updated products
               if (!empty($_POST['products'])) {
                   foreach ($_POST['products'] as $product) {
                       if (!empty($product['product_id'])) {
                           $stmt = $pdo->prepare("INSERT INTO invoice_services (
                               invoice_id, service_id, employee_id, product_id, quantity, 
                               discount_type, discount_value, price
                           ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
   
                           $stmt->execute([
                               $invoice_id,
                               null,
                               $product['employee_id'] ?? null,
                               $product['product_id'],
                               $product['quantity'] ?? 1,
                               $product['discount_type'] ?? 'none',
                               $product['discount_value'] ?? 0.00,
                               $product['price']
                           ]);
                       }
                   }
               }
   
               $pdo->commit();
               $success = "Invoice updated successfully!";
           } catch (PDOException $e) {
               $pdo->rollBack();
               $error = "Error updating invoice: " . $e->getMessage();
           }
       }
   
       // ====== DELETE INVOICE ======
       elseif (isset($_POST['delete_invoice'])) {
           try {
               $pdo->beginTransaction();
   
               // First, get product quantities to restore stock
               $stmt = $pdo->prepare("SELECT product_id, quantity FROM invoice_services WHERE invoice_id = ? AND product_id IS NOT NULL");
               $stmt->execute([$_POST['invoice_id']]);
               $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
               
               // Restore product stock
               foreach ($products as $product) {
                   $stmt = $pdo->prepare("UPDATE product SET stock_quantity = stock_quantity + ? WHERE id = ?");
                   $stmt->execute([$product['quantity'], $product['product_id']]);
               }
   
               // Delete associated services and products
               $stmt = $pdo->prepare("DELETE FROM invoice_services WHERE invoice_id = ?");
               $stmt->execute([$_POST['invoice_id']]);
   
               // Delete invoice
               $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
               $stmt->execute([$_POST['invoice_id']]);
   
               $pdo->commit();
               $success = "Invoice deleted successfully!";
           } catch (PDOException $e) {
               $pdo->rollBack();
               $error = "Error deleting invoice: " . $e->getMessage();
           }
       }
   }
   
   // ====== FETCH DATA FOR DISPLAY ======
   
   try {
       // Total count for pagination
       $total_stmt = $pdo->query("SELECT COUNT(*) FROM invoices");
       $total_invoices = $total_stmt->fetchColumn();
       $total_pages = ceil($total_invoices / $per_page);
   
       // Fetch paginated invoices with customer info
       $stmt = $pdo->prepare("
           SELECT i.id, i.created_at, i.total_amount, i.discounted_amount, i.discount_type,
                  c.name AS customer_name, c.email AS customer_email ,c.phone AS customer_phone
           FROM invoices i
           JOIN customers c ON i.customer_id = c.id
           ORDER BY i.created_at DESC
           LIMIT :limit OFFSET :offset
       ");
       $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
       $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
       $stmt->execute();
       $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
   
       // Fetch customers, services, products, and employees
       $customers = $pdo->query("SELECT id, name, phone FROM customers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
       $services = $pdo->query("SELECT id, name, cost FROM services ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
       $products = $pdo->query("SELECT id, name, price, stock_quantity FROM product ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
       $employees = $pdo->query("SELECT id, name FROM users WHERE role = 'employee' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
   
   } catch (PDOException $e) {
       $error = "Error fetching data: " . $e->getMessage();
   }
   ?>
<div class="min-h-screen bg-gray-50">
    <?php include('../includes/sidebar.php'); ?>
    <div class="md:ml-64">
        <?php include('../includes/navbar.php'); ?>
        <main class="p-4 md:p-6">
            <!-- Notifications -->
            <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <div class="space-y-6">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Invoice Management</h1>
                        <p class="text-gray-600 mt-1">Create, view and manage customer invoices</p>
                    </div>
                    <button 
                        data-modal-target="createInvoiceModal"
                        data-modal-toggle="createInvoiceModal"
                        class="flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg shadow-sm transition-colors duration-200 w-full md:w-auto"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        <span>Create Invoice</span>
                    </button>
                </div>
                
                <!-- Invoices Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($invoices as $invoice): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-indigo-600">INV-<?php echo str_pad($invoice['id'], 3, '0', STR_PAD_LEFT); ?></div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                                <span class="text-indigo-600 font-medium"><?php echo substr($invoice['customer_name'], 0, 1); ?></span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($invoice['customer_name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($invoice['customer_email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($invoice['created_at'])); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        ₹<?php echo number_format($invoice['discounted_amount'], 2); ?>
                                        <?php if ($invoice['discount_type'] !== 'none'): ?>
                                        <span class="text-xs text-gray-500 line-through">₹<?php echo number_format($invoice['total_amount'], 2); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Paid</span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <button 
                                                data-modal-target="editInvoiceModal<?php echo $invoice['id']; ?>"
                                                data-modal-toggle="editInvoiceModal<?php echo $invoice['id']; ?>"
                                                class="text-indigo-600 hover:text-indigo-900 p-1" 
                                                title="Edit"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                            </button>
                                            <button 
                                                data-modal-target="viewInvoiceModal<?php echo $invoice['id']; ?>"
                                                data-modal-toggle="viewInvoiceModal<?php echo $invoice['id']; ?>"
                                                class="text-gray-600 hover:text-gray-900 p-1" 
                                                title="View"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                            <button 
                                                data-modal-target="deleteInvoiceModal<?php echo $invoice['id']; ?>"
                                                data-modal-toggle="deleteInvoiceModal<?php echo $invoice['id']; ?>"
                                                class="text-red-500 hover:text-red-700 p-1" 
                                                title="Delete"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                            <a 
                                                href="generate_pdf.php?invoice_id=<?php echo $invoice['id']; ?>" 
                                                class="text-green-600 hover:text-green-800 p-1" 
                                                title="Download"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php
                        $start = $offset + 1;
                        $end = $offset + count($invoices);
                    ?>
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 rounded-b-xl">
                        <div class="flex-1 flex flex-col md:flex-row items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?= $start ?></span> to <span class="font-medium"><?= $end ?></span> of 
                                    <span class="font-medium"><?= $total_invoices ?></span> results
                                </p>
                            </div>
                            <div>
                                <nav class="inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                    <?php if ($current_page > 1): ?>
                                    <a href="?page=<?= $current_page - 1 ?>" class="px-3 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        Previous
                                    </a>
                                    <?php endif; ?>
                                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                    <a href="?page=<?= $p ?>" class="px-3 py-2 border border-gray-300 <?= $p == $current_page ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500' ?> text-sm font-medium hover:bg-gray-50">
                                        <?= $p ?>
                                    </a>
                                    <?php endfor; ?>
                                    <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?= $current_page + 1 ?>" class="px-3 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        Next
                                    </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Create Invoice Modal -->
<div id="createInvoiceModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-4xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">Create Invoice</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center" data-modal-hide="createInvoiceModal">
                    <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            
            <!-- Modal body -->
            <form method="POST" id="invoiceForm">
                <div class="p-4 md:p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="customer_id" class="block mb-2 text-sm font-medium text-gray-900">Customer</label>
                            <select id="customer_id" name="customer_id" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                <option value="" selected disabled>Select Customer</option>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>">
                                    <?php echo htmlspecialchars($customer['name']); ?> (<?php echo htmlspecialchars($customer['phone']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="invoice_date" class="block mb-2 text-sm font-medium text-gray-900">Date</label>
                            <input 
                                type="date" 
                                id="invoice_date" 
                                name="invoice_date" 
                                value="<?php echo date('Y-m-d'); ?>" 
                                required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5"
                            >
                        </div>
                    </div>
                    
                    <!-- Services Section -->
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Services</h3>
                            <button type="button" id="addServiceBtn" class="flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                <span>Add Service</span>
                            </button>
                        </div>
                        <div id="serviceContainer" class="space-y-4">
                            <!-- Service rows will be added here by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Products Section -->
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Products</h3>
                            <button type="button" id="addProductBtn" class="flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                <span>Add Product</span>
                            </button>
                        </div>
                        <div id="productContainer" class="space-y-4">
                            <!-- Product rows will be added here by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Discount Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-200 pt-4 mt-4">
                        <div>
                            <label for="discount_type" class="block mb-2 text-sm font-medium text-gray-900">Discount Type</label>
                            <select id="discount_type" name="discount_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                <option value="none" selected>No Discount</option>
                                <option value="flat">Flat Amount (₹)</option>
                                <option value="percent">Percentage (%)</option>
                            </select>
                        </div>
                        <div>
                            <label for="discount_value" class="block mb-2 text-sm font-medium text-gray-900">Discount Value</label>
                            <input 
                                type="number" 
                                min="0" 
                                id="discount_value" 
                                name="discount_value" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" 
                                disabled 
                                placeholder="Enter discount"
                            >
                        </div>
                    </div>
                    
                    <!-- Summary Section -->
                    <div class="border-t border-gray-200 pt-4 mt-4 space-y-2">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-500">Subtotal:</div>
                            <div class="text-gray-700 font-medium">₹ <span id="invoiceSubtotal">0.00</span></div>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-500">Discount:</div>
                            <div class="text-gray-700 font-medium">- ₹ <span id="invoiceDiscount">0.00</span></div>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                            <div class="text-lg font-bold text-gray-800">Total:</div>
                            <div class="text-2xl font-bold text-indigo-600">₹ <span id="invoiceTotal">0.00</span></div>
                        </div>
                    </div>
                    
                    <!-- Hidden fields for form submission -->
                    <input type="hidden" name="total_amount" id="total_amount" value="0">
                    <input type="hidden" name="discounted_amount" id="discounted_amount" value="0">
                </div>
                
                <!-- Modal footer -->
                <div class="flex items-center justify-end p-4 md:p-6 border-t border-gray-200 rounded-b gap-3">
                    <button 
                        type="button" 
                        data-modal-hide="createInvoiceModal" 
                        class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        name="create_invoice"
                        class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 flex items-center gap-1"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Save Invoice</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Invoice Modals -->
<?php foreach ($invoices as $invoice): ?>
<?php
    $invoice_details = $pdo->prepare("
        SELECT i.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
               ivs.id AS item_id, ivs.service_id, ivs.product_id, ivs.employee_id, ivs.quantity,
               ivs.discount_type, ivs.discount_value, ivs.price AS item_price,
               s.name AS service_name, s.cost AS service_cost,
               p.name AS product_name, p.price AS product_price,
               u.name AS employee_name
        FROM invoices i
        JOIN customers c ON i.customer_id = c.id
        LEFT JOIN invoice_services ivs ON i.id = ivs.invoice_id
        LEFT JOIN services s ON ivs.service_id = s.id
        LEFT JOIN product p ON ivs.product_id = p.id
        LEFT JOIN users u ON ivs.employee_id = u.id
        WHERE i.id = ?
    ");
    $invoice_details->execute([$invoice['id']]);
    $invoice_items = $invoice_details->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Edit Modal -->
<div id="editInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-4xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Edit Invoice - INV-<?php echo str_pad($invoice['id'], 3, '0', STR_PAD_LEFT); ?>
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center" data-modal-hide="editInvoiceModal<?php echo $invoice['id']; ?>">
                    <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            
            <!-- Modal body -->
            <form method="POST">
                <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                <div class="p-4 md:p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="customer_id_edit_<?php echo $invoice['id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">Customer</label>
                            <select id="customer_id_edit_<?php echo $invoice['id']; ?>" name="customer_id" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                <option value="" disabled>Select Customer</option>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>" <?php echo (!empty($invoice_items) && $invoice_items[0]['customer_id'] == $customer['id'] ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($customer['name']); ?> (<?php echo htmlspecialchars($customer['phone']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="invoice_date_edit_<?php echo $invoice['id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">Date</label>
                            <input 
                                type="date" 
                                id="invoice_date_edit_<?php echo $invoice['id']; ?>" 
                                name="invoice_date" 
                                value="<?php echo !empty($invoice_items) ? date('Y-m-d', strtotime($invoice_items[0]['created_at'])) : date('Y-m-d'); ?>" 
                                required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5"
                            >
                        </div>
                    </div>
                    
                    <!-- Services Section -->
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Services</h3>
                            <button 
                                type="button" 
                                id="addServiceBtnEdit<?php echo $invoice['id']; ?>" 
                                class="flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                <span>Add Service</span>
                            </button>
                        </div>
                        <div id="serviceContainerEdit<?php echo $invoice['id']; ?>" class="space-y-4">
                            <?php 
                                $service_counter = 0;
                                foreach ($invoice_items as $index => $item): 
                                    if ($item['service_id']): 
                                        $service_counter++;
                            ?>
                            <div class="p-4 border border-gray-200 rounded-lg bg-gray-50 service-row" id="serviceRowEdit<?php echo $invoice['id']; ?>_<?php echo $service_counter; ?>">
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="text-sm font-medium text-gray-700">Service #<?php echo $service_counter; ?></h4>
                                    <button type="button" class="remove-service text-red-500 hover:text-red-700" data-row="Edit<?php echo $invoice['id']; ?>_<?php echo $service_counter; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Service</label>
                                        <select name="services[<?php echo $service_counter; ?>][service_id]" required class="service-select bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                            <option value="" disabled>Select Service</option>
                                            <?php foreach ($services as $s): ?>
                                            <option value="<?php echo $s['id']; ?>" data-price="<?php echo $s['cost']; ?>" <?php echo $item['service_id'] == $s['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($s['name']); ?> (₹<?php echo number_format($s['cost'], 2); ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Assigned Employee</label>
                                        <select name="services[<?php echo $service_counter; ?>][employee_id]" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                            <option value="" disabled>Select Employee</option>
                                            <?php foreach ($employees as $employee): ?>
                                            <option value="<?php echo $employee['id']; ?>" <?php echo $item['employee_id'] == $employee['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($employee['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Price (₹)</label>
                                        <input type="number" min="0" step="0.01" 
                                            name="services[<?php echo $service_counter; ?>][price]" 
                                            value="<?php echo isset($item['price']) ? $item['price'] : ''; ?>" 
                                            class="service-price bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" 
                                            readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Qty</label>
                                        <input type="number" min="1" step="1" name="services[<?php echo $service_counter; ?>][quantity]" value="<?php echo $item['quantity']; ?>" class="service-qty bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Discount Type</label>
                                        <select name="services[<?php echo $service_counter; ?>][discount_type]" class="service-discount-type bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                            <option value="none" <?php echo $item['discount_type'] === 'none' ? 'selected' : ''; ?>>No Discount</option>
                                            <option value="flat" <?php echo $item['discount_type'] === 'flat' ? 'selected' : ''; ?>>Flat Amount</option>
                                            <option value="percent" <?php echo $item['discount_type'] === 'percent' ? 'selected' : ''; ?>>Percentage</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Discount Value</label>
                                        <input type="number" min="0" step="0.01" name="services[<?php echo $service_counter; ?>][discount_value]" value="<?php echo $item['discount_value']; ?>" class="service-discount-value bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" <?php echo $item['discount_type'] === 'none' ? 'disabled' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                    endif;
                                endforeach; 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Products Section -->
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Products</h3>
                            <button 
                                type="button" 
                                id="addProductBtnEdit<?php echo $invoice['id']; ?>" 
                                class="flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                <span>Add Product</span>
                            </button>
                        </div>
                        <div id="productContainerEdit<?php echo $invoice['id']; ?>" class="space-y-4">
                            <?php 
                                $product_counter = 0;
                                foreach ($invoice_items as $index => $item): 
                                    if ($item['product_id']): 
                                        $product_counter++;
                            ?>
                            <div class="p-4 border border-gray-200 rounded-lg bg-gray-50 product-row" id="productRowEdit<?php echo $invoice['id']; ?>_<?php echo $product_counter; ?>">
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="text-sm font-medium text-gray-700">Product #<?php echo $product_counter; ?></h4>
                                    <button type="button" class="remove-product text-red-500 hover:text-red-700" data-row="Edit<?php echo $invoice['id']; ?>_<?php echo $product_counter; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Product</label>
                                        <select name="products[<?php echo $product_counter; ?>][product_id]" required class="product-select bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                            <option value="" disabled>Select Product</option>
                                            <?php foreach ($products as $p): ?>
                                            <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>" data-stock="<?php echo $p['stock_quantity']; ?>" <?php echo $item['product_id'] == $p['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($p['name']); ?> (₹<?php echo number_format($p['price'], 2); ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Assigned Employee</label>
                                        <select name="products[<?php echo $product_counter; ?>][employee_id]" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                            <option value="">None</option>
                                            <?php foreach ($employees as $employee): ?>
                                            <option value="<?php echo $employee['id']; ?>" <?php echo $item['employee_id'] == $employee['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($employee['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Price (₹)</label>
                                        <input type="number" min="0" step="0.01" 
                                            name="products[<?php echo $product_counter; ?>][price]" 
                                            value="<?php echo isset($item['price']) ? $item['price'] : ''; ?>" 
                                            class="product-price bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" 
                                            readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Qty</label>
                                        <input type="number" min="1" step="1" name="products[<?php echo $product_counter; ?>][quantity]" value="<?php echo $item['quantity']; ?>" class="product-qty bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                        <p class="text-xs text-gray-500 mt-1 stock-info">
                                            <?php if ($item['product_id']): ?>
                                                <?php 
                                                    $product_stock = $pdo->prepare("SELECT stock_quantity FROM product WHERE id = ?");
                                                    $product_stock->execute([$item['product_id']]);
                                                    $stock = $product_stock->fetchColumn();
                                                    echo "In stock: $stock";
                                                ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Discount Type</label>
                                        <select name="products[<?php echo $product_counter; ?>][discount_type]" class="product-discount-type bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                                            <option value="none" <?php echo $item['discount_type'] === 'none' ? 'selected' : ''; ?>>No Discount</option>
                                            <option value="flat" <?php echo $item['discount_type'] === 'flat' ? 'selected' : ''; ?>>Flat Amount</option>
                                            <option value="percent" <?php echo $item['discount_type'] === 'percent' ? 'selected' : ''; ?>>Percentage</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Discount Value</label>
                                        <input type="number" min="0" step="0.01" name="products[<?php echo $product_counter; ?>][discount_value]" value="<?php echo $item['discount_value']; ?>" class="product-discount-value bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" <?php echo $item['discount_type'] === 'none' ? 'disabled' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                    endif;
                                endforeach; 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Discount Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-200 pt-4 mt-4">
                        <div>
                            <label for="discount_type_edit_<?php echo $invoice['id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">Invoice Discount Type</label>
                            <select 
                                id="discount_type_edit_<?php echo $invoice['id']; ?>" 
                                name="discount_type" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5"
                            >
                                <option value="none" <?php echo $invoice['discount_type'] === 'none' ? 'selected' : ''; ?>>No Discount</option>
                                <option value="flat" <?php echo $invoice['discount_type'] === 'flat' ? 'selected' : ''; ?>>Flat Amount (₹)</option>
                                <option value="percent" <?php echo $invoice['discount_type'] === 'percent' ? 'selected' : ''; ?>>Percentage (%)</option>
                            </select>
                        </div>
                        <div>
                            <label for="discount_value_edit_<?php echo $invoice['id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">Invoice Discount Value</label>
                            <input 
                                type="number" 
                                min="0" 
                                id="discount_value_edit_<?php echo $invoice['id']; ?>" 
                                name="discount_value" 
                                value="<?php 
                                    if ($invoice['discount_type'] === 'flat') {
                                        echo $invoice['total_amount'] - $invoice['discounted_amount'];
                                    } elseif ($invoice['discount_type'] === 'percent') {
                                        echo ($invoice['total_amount'] - $invoice['discounted_amount']) / $invoice['total_amount'] * 100;
                                    }
                                ?>" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" 
                                <?php echo $invoice['discount_type'] === 'none' ? 'disabled' : ''; ?> 
                                placeholder="Enter discount"
                            />
                        </div>
                    </div>
                    
                    <!-- Summary Section -->
                    <div class="border-t border-gray-200 pt-4 mt-4 space-y-2">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-500">Subtotal:</div>
                            <div class="text-gray-700 font-medium">₹ <span id="invoiceSubtotalEdit<?php echo $invoice['id']; ?>"><?php echo number_format($invoice['total_amount'], 2); ?></span></div>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-500">Discount:</div>
                            <div class="text-gray-700 font-medium">- ₹ <span id="invoiceDiscountEdit<?php echo $invoice['id']; ?>"><?php echo number_format($invoice['total_amount'] - $invoice['discounted_amount'], 2); ?></span></div>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                            <div class="text-lg font-bold text-gray-800">Total:</div>
                            <div class="text-2xl font-bold text-indigo-600">₹ <span id="invoiceTotalEdit<?php echo $invoice['id']; ?>"><?php echo number_format($invoice['discounted_amount'], 2); ?></span></div>
                        </div>
                    </div>
                    
                    <!-- Hidden fields for form submission -->
                    <input type="hidden" name="total_amount" id="total_amount_edit_<?php echo $invoice['id']; ?>" value="<?php echo $invoice['total_amount']; ?>">
                    <input type="hidden" name="discounted_amount" id="discounted_amount_edit_<?php echo $invoice['id']; ?>" value="<?php echo $invoice['discounted_amount']; ?>">
                </div>
                
                <!-- Modal footer -->
                <div class="flex items-center justify-end p-4 md:p-6 border-t border-gray-200 rounded-b gap-3">
                    <button 
                        type="button" 
                        data-modal-hide="editInvoiceModal<?php echo $invoice['id']; ?>" 
                        class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        name="update_invoice"
                        class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 flex items-center gap-1"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Update Invoice</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Invoice Modal -->
<div id="viewInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-4xl max-h-full">
        <div class="relative bg-white rounded-lg shadow-lg border border-gray-200">
            <!-- Modal header with salon branding -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t bg-pink-50">
                <div class="flex items-center space-x-3">
                    <div class="bg-pink-100 p-2 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Headturner Burdwan</h3>
                        <p class="text-xs text-gray-600">115 B.C Road, Near Kalitala, Bardhaman, West Bengal 713101</p>
                    </div>
                </div>
                <div class="text-right">
                    <h3 class="text-xl font-semibold text-pink-700">
                        Invoice #INV-<?php echo str_pad($invoice['id'], 3, '0', STR_PAD_LEFT); ?>
                    </h3>
                    <p class="text-xs text-gray-600">Ph: 074773 60944</p>
                </div>
            </div>
            
            <button type="button" class="absolute top-3 right-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center" data-modal-hide="viewInvoiceModal<?php echo $invoice['id']; ?>">
                <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
                <span class="sr-only">Close modal</span>
            </button>
            
            <!-- Modal body -->
            <div class="p-4 md:p-6 space-y-6">
                <!-- Invoice Header -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2 bg-gray-50 p-3 rounded-lg">
                        <h4 class="text-sm font-medium text-pink-600 uppercase tracking-wider">Bill To</h4>
                        <div class="text-gray-900">
                            <p class="font-medium"><?php echo htmlspecialchars($invoice['customer_name']); ?></p>
                            <p class="text-sm"><?php echo htmlspecialchars($invoice['customer_email']); ?></p>
                            <p class="text-sm"><?php echo htmlspecialchars($invoice['customer_phone']); ?></p>
                        </div>
                    </div>
                    <div class="space-y-2 bg-gray-50 p-3 rounded-lg">
                        <h4 class="text-sm font-medium text-pink-600 uppercase tracking-wider">Invoice Details</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <span class="text-gray-500">Invoice #</span>
                            <span class="text-gray-900 font-medium">INV-<?php echo str_pad($invoice['id'], 3, '0', STR_PAD_LEFT); ?></span>
                            <span class="text-gray-500">Date</span>
                            <span class="text-gray-900"><?php echo date('M j, Y', strtotime($invoice['created_at'])); ?></span>
                            <span class="text-gray-500">Status</span>
                        </div>
                    </div>
                    <div class="space-y-2 bg-gray-50 p-3 rounded-lg">
                        <h4 class="text-sm font-medium text-pink-600 uppercase tracking-wider">Salon Info</h4>
                        <div class="text-gray-900 text-sm">
                            <p>115 B.C Road, Near Kalitala</p>
                            <p>Bardhaman, West Bengal 713101</p>
                            <p>Ph: 074773 60944</p>
                        </div>
                    </div>
                </div>
                
                <!-- Items Table -->
                <div class="mt-6 border border-gray-200 rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-pink-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-pink-700 uppercase tracking-wider">Item</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-pink-700 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-pink-700 uppercase tracking-wider">Stylist</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-pink-700 uppercase tracking-wider">Price</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-pink-700 uppercase tracking-wider">Qty</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-pink-700 uppercase tracking-wider">Discount</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-pink-700 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($invoice_items as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($item['service_id'] ? $item['service_name'] : $item['product_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $item['service_id'] ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo $item['service_id'] ? 'Service' : 'Product'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($item['employee_name'] ?: 'None'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                    ₹<?php echo number_format($item['item_price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                    <?php if ($item['discount_type'] !== 'none'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <?php echo $item['discount_type'] === 'percent' ? 
                                                number_format($item['discount_value'], 2) . '%' : 
                                                '₹' . number_format($item['discount_value'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">
                                    <?php 
                                        $subtotal = $item['item_price'] * $item['quantity'];
                                        if ($item['discount_type'] === 'flat') {
                                            $subtotal -= $item['discount_value'];
                                        } elseif ($item['discount_type'] === 'percent') {
                                            $subtotal -= ($subtotal * $item['discount_value'] / 100);
                                        }
                                        echo '₹' . number_format($subtotal, 2);
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary Section -->
                <div class="ml-auto w-full md:w-1/2 space-y-3 mt-6">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Subtotal:</span>
                        <span class="text-gray-900">₹<?php echo number_format($invoice['total_amount'], 2); ?></span>
                    </div>
                    <?php if ($invoice['discount_type'] !== 'none'): ?>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Discount (<?php echo $invoice['discount_type'] === 'flat' ? 'Flat' : 'Percentage'; ?>):</span>
                        <span class="text-red-600">- ₹<?php echo number_format($invoice['subtotal'] - $invoice['discounted_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="text-base font-medium text-gray-900">Total:</span>
                        <span class="text-lg font-bold text-pink-600">₹<?php echo number_format($invoice['discounted_amount'], 2); ?></span>
                    </div>
                </div>
                
                <!-- Thank you message -->
                <div class="mt-6 p-3 bg-pink-50 rounded-lg text-center">
                    <p class="text-pink-700 font-medium">Thank you for choosing Headturner Burdwan!</p>
                    <p class="text-xs text-gray-600 mt-1">We appreciate your business and look forward to serving you again.</p>
                </div>
            </div>
            
            <!-- Modal footer -->
            <div class="flex items-center justify-between p-4 md:p-6 border-t border-gray-200 rounded-b bg-gray-50">
                <div class="text-sm text-gray-500">
                    <p>Invoice generated on <?php echo date('M j, Y \a\t g:i A', strtotime($invoice['created_at'])); ?></p>
                    <p class="text-xs mt-1">© <?php echo date('Y'); ?> Headturner Burdwan. All rights reserved.</p>
                </div>
                <div class="flex gap-3">
                    <button 
                        type="button" 
                        data-modal-hide="viewInvoiceModal<?php echo $invoice['id']; ?>" 
                        class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                    >
                        Close
                    </button>
                    <a 
                        href="generate_pdf.php?invoice_id=<?php echo $invoice['id']; ?>" 
                        class="px-4 py-2.5 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors duration-200 flex items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        Download PDF
                    </a>
                    <button 
                        onclick="window.print()" 
                        class="px-4 py-2.5 border border-pink-600 text-pink-600 rounded-lg hover:bg-pink-50 transition-colors duration-200 flex items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Invoice Modal -->
<div id="deleteInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Delete Invoice
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center" data-modal-hide="deleteInvoiceModal<?php echo $invoice['id']; ?>">
                    <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            
            <!-- Modal body -->
            <form method="POST">
                <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                <div class="p-4 md:p-6 space-y-4">
                    <p class="text-gray-700">Are you sure you want to delete invoice <span class="font-bold">INV-<?php echo str_pad($invoice['id'], 3, '0', STR_PAD_LEFT); ?></span> for customer <span class="font-bold"><?php echo htmlspecialchars($invoice['customer_name']); ?></span>?</p>
                    <p class="text-red-600">This action cannot be undone.</p>
                </div>
                
                <!-- Modal footer -->
                <div class="flex items-center justify-end p-4 md:p-6 border-t border-gray-200 rounded-b gap-3">
                    <button 
                        type="button" 
                        data-modal-hide="deleteInvoiceModal<?php echo $invoice['id']; ?>" 
                        class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        name="delete_invoice"
                        class="px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 flex items-center gap-1"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>Delete Invoice</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
    // Service and Product counters
    let serviceCounter = 0;
    let productCounter = 0;
    let editServiceCounters = {};
    let editProductCounters = {};
    
    // Initialize first service row on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Add first service row to create form
        document.getElementById('addServiceBtn').click();
        
        // Initialize edit forms with their service and product counts
        <?php foreach ($invoices as $invoice): ?>
            editServiceCounters['<?php echo $invoice['id']; ?>'] = document.querySelectorAll('#serviceContainerEdit<?php echo $invoice['id']; ?> .service-row').length;
            editProductCounters['<?php echo $invoice['id']; ?>'] = document.querySelectorAll('#productContainerEdit<?php echo $invoice['id']; ?> .product-row').length;
            
            // Set up event listeners for edit form elements
            const editDiscountType<?php echo $invoice['id']; ?> = document.getElementById('discount_type_edit_<?php echo $invoice['id']; ?>');
            const editDiscountValue<?php echo $invoice['id']; ?> = document.getElementById('discount_value_edit_<?php echo $invoice['id']; ?>');
            
            if (editDiscountType<?php echo $invoice['id']; ?>) {
                editDiscountType<?php echo $invoice['id']; ?>.addEventListener('change', function() {
                    if (this.value === 'none') {
                        editDiscountValue<?php echo $invoice['id']; ?>.disabled = true;
                        editDiscountValue<?php echo $invoice['id']; ?>.value = '';
                    } else {
                        editDiscountValue<?php echo $invoice['id']; ?>.disabled = false;
                    }
                    calculateTotal('Edit<?php echo $invoice['id']; ?>');
                });
            }
            
            if (editDiscountValue<?php echo $invoice['id']; ?>) {
                editDiscountValue<?php echo $invoice['id']; ?>.addEventListener('input', function() {
                    calculateTotal('Edit<?php echo $invoice['id']; ?>');
                });
            }
        <?php endforeach; ?>
    });
    
    // Add service row to create form
    document.getElementById('addServiceBtn').addEventListener('click', function() {
        serviceCounter++;
        addServiceRow(serviceCounter, 'serviceContainer');
    });
    
    // Add product row to create form
    document.getElementById('addProductBtn').addEventListener('click', function() {
        productCounter++;
        addProductRow(productCounter, 'productContainer');
    });
    
    // Add service row to edit forms
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id && e.target.id.startsWith('addServiceBtnEdit')) {
            const invoiceId = e.target.id.replace('addServiceBtnEdit', '');
            if (!editServiceCounters[invoiceId]) {
                editServiceCounters[invoiceId] = document.querySelectorAll(`#serviceContainerEdit${invoiceId} .service-row`).length;
            }
            editServiceCounters[invoiceId]++;
            addServiceRow(editServiceCounters[invoiceId], `serviceContainerEdit${invoiceId}`, `Edit${invoiceId}_`);
        }
    });
    
    // Add product row to edit forms
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id && e.target.id.startsWith('addProductBtnEdit')) {
            const invoiceId = e.target.id.replace('addProductBtnEdit', '');
            if (!editProductCounters[invoiceId]) {
                editProductCounters[invoiceId] = document.querySelectorAll(`#productContainerEdit${invoiceId} .product-row`).length;
            }
            editProductCounters[invoiceId]++;
            addProductRow(editProductCounters[invoiceId], `productContainerEdit${invoiceId}`, `Edit${invoiceId}_`);
        }
    });
    
    // Function to add a service row
    function addServiceRow(counter, containerId, prefix = '') {
        const serviceRow = `
            <div class="p-4 border border-gray-200 rounded-lg bg-gray-50 service-row" id="serviceRow${prefix}${counter}">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="text-sm font-medium text-gray-700">Service #${counter}</h4>
                    <button type="button" class="remove-service text-red-500 hover:text-red-700" data-row="${prefix}${counter}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Service</label>
                        <select name="services[${counter}][service_id]" required class="service-select bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                            <option value="" selected disabled>Select Service</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['cost']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> (₹<?php echo number_format($service['cost'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Assigned Employee</label>
                        <select name="services[${counter}][employee_id]" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                            <option value="" selected disabled>Select Employee</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Price (₹)</label>
                        <input type="number" min="0" step="0.01" name="services[${counter}][price]" class="service-price bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" readonly>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Qty</label>
                        <input type="number" min="1" step="1" name="services[${counter}][quantity]" value="1" class="service-qty bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Discount Type</label>
                        <select name="services[${counter}][discount_type]" class="service-discount-type bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                            <option value="none" selected>No Discount</option>
                            <option value="flat">Flat Amount</option>
                            <option value="percent">Percentage</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Discount Value</label>
                        <input type="number" min="0" step="0.01" name="services[${counter}][discount_value]" class="service-discount-value bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" disabled>
                    </div>
                </div>
            </div>
        `;
        document.getElementById(containerId).insertAdjacentHTML('beforeend', serviceRow);
    }
    
    // Function to add a product row
    function addProductRow(counter, containerId, prefix = '') {
        const productRow = `
            <div class="p-4 border border-gray-200 rounded-lg bg-gray-50 product-row" id="productRow${prefix}${counter}">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="text-sm font-medium text-gray-700">Product #${counter}</h4>
                    <button type="button" class="remove-product text-red-500 hover:text-red-700" data-row="${prefix}${counter}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Product</label>
                        <select name="products[${counter}][product_id]" required class="product-select bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                            <option value="" selected disabled>Select Product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>" data-stock="<?php echo $product['stock_quantity']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> (₹<?php echo number_format($product['price'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Assigned Employee</label>
                        <select name="products[${counter}][employee_id]" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                            <option value="">None</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Price (₹)</label>
                        <input type="number" min="0" step="0.01" name="products[${counter}][price]" class="product-price bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" readonly>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Qty</label>
                        <input type="number" min="1" step="1" name="products[${counter}][quantity]" value="1" class="product-qty bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        <p class="text-xs text-gray-500 mt-1 stock-info"></p>
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Discount Type</label>
                        <select name="products[${counter}][discount_type]" class="product-discount-type bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                            <option value="none" selected>No Discount</option>
                            <option value="flat">Flat Amount</option>
                            <option value="percent">Percentage</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Discount Value</label>
                        <input type="number" min="0" step="0.01" name="products[${counter}][discount_value]" class="product-discount-value bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" disabled>
                    </div>
                </div>
            </div>
        `;
        document.getElementById(containerId).insertAdjacentHTML('beforeend', productRow);
    }
    
    // Remove service row
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-service')) {
            const rowId = e.target.closest('.remove-service').getAttribute('data-row');
            const rowElement = document.getElementById(`serviceRow${rowId}`);
            if (rowElement) {
                rowElement.remove();
                calculateTotal(rowId.includes('Edit') ? rowId.split('_')[0] : '');
            }
        }
    });
    
    // Remove product row
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-product')) {
            const rowId = e.target.closest('.remove-product').getAttribute('data-row');
            const rowElement = document.getElementById(`productRow${rowId}`);
            if (rowElement) {
                rowElement.remove();
                calculateTotal(rowId.includes('Edit') ? rowId.split('_')[0] : '');
            }
        }
    });
    
    // Service select change handler
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('service-select')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const priceInput = e.target.closest('.service-row').querySelector('.service-price');
            priceInput.value = price;
            
            // Determine which total to update based on the container
            const containerId = e.target.closest('[id^="serviceContainer"]').id;
            if (containerId.startsWith('serviceContainerEdit')) {
                const invoiceId = containerId.replace('serviceContainerEdit', '');
                calculateTotal(`Edit${invoiceId}`);
            } else {
                calculateTotal();
            }
        }
        
        // Product select change handler
        if (e.target.classList.contains('product-select')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const stock = selectedOption.getAttribute('data-stock');
            const priceInput = e.target.closest('.product-row').querySelector('.product-price');
            const stockInfo = e.target.closest('.product-row').querySelector('.stock-info');
            
            priceInput.value = price;
            if (stock) {
                stockInfo.textContent = `In stock: ${stock}`;
            }
            
            // Determine which total to update based on the container
            const containerId = e.target.closest('[id^="productContainer"]').id;
            if (containerId.startsWith('productContainerEdit')) {
                const invoiceId = containerId.replace('productContainerEdit', '');
                calculateTotal(`Edit${invoiceId}`);
            } else {
                calculateTotal();
            }
        }
        
        // Service discount type change handler
        if (e.target.classList.contains('service-discount-type')) {
            const discountValueInput = e.target.closest('.service-row').querySelector('.service-discount-value');
            if (e.target.value === 'none') {
                discountValueInput.disabled = true;
                discountValueInput.value = '';
            } else {
                discountValueInput.disabled = false;
            }
            
            // Determine which total to update based on the container
            const containerId = e.target.closest('[id^="serviceContainer"]') ? 
                e.target.closest('[id^="serviceContainer"]').id : 
                e.target.closest('[id^="productContainer"]').id;
                
            if (containerId.startsWith('serviceContainerEdit') || containerId.startsWith('productContainerEdit')) {
                const invoiceId = containerId.replace('serviceContainerEdit', '').replace('productContainerEdit', '');
                calculateTotal(`Edit${invoiceId}`);
            } else {
                calculateTotal();
            }
        }
        
        // Product discount type change handler
        if (e.target.classList.contains('product-discount-type')) {
            const discountValueInput = e.target.closest('.product-row').querySelector('.product-discount-value');
            if (e.target.value === 'none') {
                discountValueInput.disabled = true;
                discountValueInput.value = '';
            } else {
                discountValueInput.disabled = false;
            }
            
            // Determine which total to update based on the container
            const containerId = e.target.closest('[id^="serviceContainer"]') ? 
                e.target.closest('[id^="serviceContainer"]').id : 
                e.target.closest('[id^="productContainer"]').id;
                
            if (containerId.startsWith('serviceContainerEdit') || containerId.startsWith('productContainerEdit')) {
                const invoiceId = containerId.replace('serviceContainerEdit', '').replace('productContainerEdit', '');
                calculateTotal(`Edit${invoiceId}`);
            } else {
                calculateTotal();
            }
        }
    });
    
    // Input change handlers for quantity and discount values
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('service-qty') || 
            e.target.classList.contains('service-discount-value') ||
            e.target.classList.contains('product-qty') || 
            e.target.classList.contains('product-discount-value')) {
            
            // Determine which total to update based on the container
            let containerId = '';
            if (e.target.closest('[id^="serviceContainer"]')) {
                containerId = e.target.closest('[id^="serviceContainer"]').id;
            } else if (e.target.closest('[id^="productContainer"]')) {
                containerId = e.target.closest('[id^="productContainer"]').id;
            }
            
            if (containerId.startsWith('serviceContainerEdit') || containerId.startsWith('productContainerEdit')) {
                const invoiceId = containerId.replace('serviceContainerEdit', '').replace('productContainerEdit', '');
                calculateTotal(`Edit${invoiceId}`);
            } else {
                calculateTotal();
            }
        }
    });
    
    // Discount type change handler for create form
    document.getElementById('discount_type').addEventListener('change', function() {
        const discountValue = document.getElementById('discount_value');
        if (this.value === 'none') {
            discountValue.disabled = true;
            discountValue.value = '';
        } else {
            discountValue.disabled = false;
        }
        calculateTotal();
    });
    
    // Discount value change handler for create form
    document.getElementById('discount_value').addEventListener('input', calculateTotal);
    
    // Calculate total
    function calculateTotal(prefix = '') {
        let servicesSubtotal = 0;
        let productsSubtotal = 0;
        
        // Calculate services subtotal
        const serviceRows = prefix 
            ? document.querySelectorAll(`#serviceContainer${prefix} .service-row`)
            : document.querySelectorAll('#serviceContainer .service-row');
        
        serviceRows.forEach(row => {
            const price = parseFloat(row.querySelector('.service-price').value) || 0;
            const quantity = parseInt(row.querySelector('.service-qty').value) || 1;
            const discountType = row.querySelector('.service-discount-type').value;
            const discountValue = parseFloat(row.querySelector('.service-discount-value').value) || 0;
            
            let itemTotal = price * quantity;
            
            if (discountType === 'flat') {
                itemTotal -= discountValue;
            } else if (discountType === 'percent') {
                itemTotal -= (itemTotal * discountValue / 100);
            }
            
            servicesSubtotal += itemTotal;
        });
        
        // Calculate products subtotal
        const productRows = prefix 
            ? document.querySelectorAll(`#productContainer${prefix} .product-row`)
            : document.querySelectorAll('#productContainer .product-row');
        
        productRows.forEach(row => {
            const price = parseFloat(row.querySelector('.product-price').value) || 0;
            const quantity = parseInt(row.querySelector('.product-qty').value) || 1;
            const discountType = row.querySelector('.product-discount-type').value;
            const discountValue = parseFloat(row.querySelector('.product-discount-value').value) || 0;
            
            let itemTotal = price * quantity;
            
            if (discountType === 'flat') {
                itemTotal -= discountValue;
            } else if (discountType === 'percent') {
                itemTotal -= (itemTotal * discountValue / 100);
            }
            
            productsSubtotal += itemTotal;
        });
        
        const subtotal = servicesSubtotal + productsSubtotal;
        let discount = 0;
        let total = subtotal;
        
        // Apply invoice-level discount if any
        if (prefix) {
            const invoiceId = prefix.replace('Edit', '');
            const discountType = document.getElementById(`discount_type_edit_${invoiceId}`).value;
            const discountValue = parseFloat(document.getElementById(`discount_value_edit_${invoiceId}`).value) || 0;
            
            if (discountType === 'flat') {
                discount = Math.min(discountValue, subtotal);
                total = subtotal - discount;
            } else if (discountType === 'percent') {
                discount = subtotal * (Math.min(discountValue, 100) / 100);
                total = subtotal - discount;
            }
            
            // Update display for edit form
            document.getElementById(`invoiceSubtotalEdit${invoiceId}`).textContent = subtotal.toFixed(2);
            document.getElementById(`invoiceDiscountEdit${invoiceId}`).textContent = discount.toFixed(2);
            document.getElementById(`invoiceTotalEdit${invoiceId}`).textContent = total.toFixed(2);
            
            // Update hidden fields for form submission
            document.getElementById(`total_amount_edit_${invoiceId}`).value = subtotal.toFixed(2);
            document.getElementById(`discounted_amount_edit_${invoiceId}`).value = total.toFixed(2);
        } else {
            const discountType = document.getElementById('discount_type').value;
            const discountValue = parseFloat(document.getElementById('discount_value').value) || 0;
            
            if (discountType === 'flat') {
                discount = Math.min(discountValue, subtotal);
                total = subtotal - discount;
            } else if (discountType === 'percent') {
                discount = subtotal * (Math.min(discountValue, 100) / 100);
                total = subtotal - discount;
            }
            
            // Update display for create form
            document.getElementById('invoiceSubtotal').textContent = subtotal.toFixed(2);
            document.getElementById('invoiceDiscount').textContent = discount.toFixed(2);
            document.getElementById('invoiceTotal').textContent = total.toFixed(2);
            
            // Update hidden fields for form submission
            document.getElementById('total_amount').value = subtotal.toFixed(2);
            document.getElementById('discounted_amount').value = total.toFixed(2);
        }
    }
</script>
<!-- Flowbite JS for modals -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
<?php include('../includes/footer.php'); ?>