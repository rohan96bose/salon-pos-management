<?php include('../includes/header.php'); ?>
<div class="min-h-screen bg-gray-50">
   <?php include('../includes/sidebar.php'); ?>
   <div class="lg:ml-64 flex flex-col">
      <?php include('../includes/navbar.php'); ?>
      <main class="p-4 md:p-6">
         <div class="flex flex-col space-y-6">
            <!-- Page Header -->
            <div class="bg-white p-4 md:p-6 rounded-xl shadow-sm border border-gray-100">
               <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                  <div>
                     <div class="flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Employee Incentive History</h1>
                     </div>
                     <p class="text-gray-600 mt-1 text-sm md:text-base">Track and manage employee incentives with detailed breakdowns</p>
                  </div>
                  <div class="mt-3 md:mt-0">
                     <div class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Last updated: <?= htmlspecialchars(date('d M Y, h:i A')) ?>
                     </div>
                  </div>
               </div>
            </div>
            <!-- Filter Card -->
            <div class="bg-white p-4 md:p-6 rounded-xl shadow-sm border border-gray-100">
               <form method="POST" class="space-y-4 md:space-y-0">
                  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                     <div>
                        <label for="fromDate" class="block text-sm font-medium text-gray-700 mb-1.5">From Date</label>
                        <div class="relative">
                           <input type="date" id="fromDate" name="fromDate" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required value="<?= htmlspecialchars($_POST['fromDate'] ?? date('Y-m-d', strtotime('-7 days'))) ?>">
                           <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                              </svg>
                           </div>
                        </div>
                     </div>
                     <div>
                        <label for="toDate" class="block text-sm font-medium text-gray-700 mb-1.5">To Date</label>
                        <div class="relative">
                           <input type="date" id="toDate" name="toDate" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required value="<?= htmlspecialchars($_POST['toDate'] ?? date('Y-m-d')) ?>">
                           <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                              </svg>
                           </div>
                        </div>
                     </div>
                     <div>
                        <label for="filterEmployee" class="block text-sm font-medium text-gray-700 mb-1.5">Employee</label>
                        <div class="relative">
                           <select id="filterEmployee" name="filterEmployee" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition appearance-none">
                              <option value="">All Employees</option>
                              <?php
                              $empStmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'employee'");
                              $empStmt->execute();
                              while ($emp = $empStmt->fetch(PDO::FETCH_ASSOC)) {
                                  $selected = (isset($_POST['filterEmployee']) && $_POST['filterEmployee'] == $emp['id']) ? 'selected' : '';
                                  echo sprintf(
                                      '<option value="%s" %s>%s</option>',
                                      htmlspecialchars($emp['id']),
                                      $selected,
                                      htmlspecialchars($emp['name'])
                                  );
                              }
                              ?>
                           </select>
                           <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                              </svg>
                           </div>
                        </div>
                     </div>
                     <div class="flex items-end space-x-2">
                        <button type="submit" class="h-[42px] bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center justify-center space-x-2 transition-colors shadow-sm">
                           <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                           </svg>
                           <span>Filter</span>
                        </button>
                        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="h-[42px] border border-gray-300 hover:bg-gray-50 px-4 py-2 rounded-lg flex items-center justify-center transition-colors shadow-sm">
                           <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                           </svg>
                           <span class="ml-1">Reset</span>
                        </a>
                     </div>
                  </div>
               </form>
            </div>
            <!-- Results Card -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
               <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                     <thead class="bg-gray-50">
                        <tr>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customers</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total (₹)</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Incentive</th>
                           <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                     </thead>
                     <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            $fromDate = $_POST['fromDate'] ?? '';
                            $toDate = $_POST['toDate'] ?? '';
                            $filterEmployee = $_POST['filterEmployee'] ?? '';
                            
                            // Validate dates
                            if (!strtotime($fromDate) || !strtotime($toDate)) {
                                echo '<tr><td colspan="6" class="px-6 py-12 text-center text-red-500">Invalid date range selected</td></tr>';
                            } else {
                                $stmt = $pdo->prepare("
                                    SELECT isv.*, i.id as invoice_id, i.invoice_date, i.created_at, i.customer_id,
                                    i.discount_type AS inv_dtype, i.overall_discount_value
                                    FROM invoice_services isv
                                    JOIN invoices i ON i.id = isv.invoice_id
                                    WHERE i.invoice_date BETWEEN :from AND :to
                                    ORDER BY i.invoice_date DESC, i.created_at DESC
                                ");
                                $stmt->execute([':from' => $fromDate, ':to' => $toDate]);
                                $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                                $empStmt = $pdo->prepare("SELECT id, name FROM users WHERE role='employee'");
                                $empStmt->execute();
                                $empNames = $empStmt->fetchAll(PDO::FETCH_KEY_PAIR);
                            
                                $aggregated = [];
                            
                                foreach ($services as $svc) {
                                    $eids = array_filter(array_map('trim', explode(',', $svc['employee_id'])));
                                    if ($filterEmployee && !in_array($filterEmployee, $eids)) continue;
                            
                                    $base = (float)$svc['price'];
                                    if ($svc['discount_type'] === 'percent') {
                                        $base -= $base * ($svc['discount_value'] / 100);
                                    } elseif ($svc['discount_type'] === 'flat') {
                                        $base -= $svc['discount_value'];
                                    }
                                    if ($svc['inv_dtype'] === 'percent') {
                                        $base -= $base * ($svc['overall_discount_value'] / 100);
                                    } elseif ($svc['inv_dtype'] === 'flat') {
                                        $base -= $svc['overall_discount_value'];
                                    }
                                    $base = max($base, 0);
                                    $split = $base / max(count($eids), 1); // Avoid division by zero
                                    $incent = $split * 0.05;
                            
                                    foreach ($eids as $eid) {
                                        $key = $eid . '|' . $svc['invoice_date'];
                                        if (!isset($aggregated[$key])) {
                                            $aggregated[$key] = [
                                                'eid' => $eid,
                                                'name' => $empNames[$eid] ?? 'Unknown',
                                                'date' => $svc['invoice_date'],
                                                'customers' => [],
                                                'total' => 0,
                                                'incentive' => 0,
                                                'details' => [],
                                            ];
                                        }
                                        $aggregated[$key]['customers'][$svc['customer_id']] = true;
                                        $aggregated[$key]['total'] += $split;
                                        $aggregated[$key]['incentive'] += $incent;
                                        
                                        // Get customer name safely
                                        $custStmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
                                        $custStmt->execute([$svc['customer_id']]);
                                        $customerName = $custStmt->fetchColumn() ?? 'Unknown';
                                        
                                        // Get service/product name safely
                                        $serviceName = '-';
                                        if ($svc['service_id']) {
                                            $serviceStmt = $pdo->prepare("SELECT name FROM services WHERE id = ?");
                                            $serviceStmt->execute([$svc['service_id']]);
                                            $serviceName = $serviceStmt->fetchColumn() ?? '-';
                                        }
                                        
                                        $productName = '-';
                                        if ($svc['product_id']) {
                                            $productStmt = $pdo->prepare("SELECT name FROM product WHERE id = ?");
                                            $productStmt->execute([$svc['product_id']]);
                                            $productName = $productStmt->fetchColumn() ?? '-';
                                        }
                                        
                                        $sharedWith = array_map(function($id) use ($empNames) {
                                            return $empNames[$id] ?? 'Unknown';
                                        }, $eids);
                                        
                                        $aggregated[$key]['details'][] = [
                                            'invoice_id' => 'INV-' . str_pad($svc['invoice_id'], 3, '0', STR_PAD_LEFT),
                                            'customer' => $customerName,
                                            'service' => $serviceName,
                                            'product' => $productName,
                                            'unit_price' => $svc['price'],
                                            'discount_type' => $svc['discount_type'],
                                            'discount_value' => $svc['discount_value'],
                                            'invoice_discount_type' => $svc['inv_dtype'],
                                            'overall_discount_value' => $svc['overall_discount_value'],
                                            'final_amount' => round($split, 2),
                                            'shareemployee' => implode(', ', $sharedWith),
                                        ];
                                    }
                                }
                            
                                if (empty($aggregated)) {
                                    echo '<tr><td colspan="6" class="px-6 py-12 text-center">
                                       <div class="flex flex-col items-center justify-center space-y-3 text-gray-400">
                                          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                          </svg>
                                          <div class="text-sm font-medium">No records found for the selected criteria</div>
                                          <p class="text-xs max-w-xs text-center">Try adjusting your filters or select a different date range</p>
                                       </div>
                                    </td></tr>';
                                } else {
                                    foreach ($aggregated as $row) {
                                        $custCount = count($row['customers']);
                                        $detailsJson = htmlspecialchars(json_encode($row['details'], JSON_HEX_APOS|JSON_HEX_QUOT));
                                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                           <td class="px-6 py-4 whitespace-nowrap">
                              <div class="flex items-center min-w-max">
                                 <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <span class="text-indigo-600 font-medium"><?= htmlspecialchars(substr($row['name'], 0, 1)) ?></span>
                                 </div>
                                 <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['name']) ?></div>
                                 </div>
                              </div>
                           </td>
                           <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars(date('d M Y', strtotime($row['date']))) ?></td>
                           <td class="px-6 py-4 whitespace-nowrap">
                              <div class="flex items-center">
                                 <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                 <?= $custCount ?> customer<?= $custCount !== 1 ? 's' : '' ?>
                                 </span>
                              </div>
                           </td>
                           <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">₹<?= number_format($row['total'], 2) ?></td>
                           <td class="px-6 py-4 whitespace-nowrap">
                              <div class="text-sm font-medium text-green-600 flex items-center">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                 </svg>
                                 ₹<?= number_format($row['incentive'], 2) ?>
                              </div>
                           </td>
                           <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                              <button class="text-indigo-600 hover:text-indigo-900 hover:underline transition-colors flex items-center"
                                 onclick="viewDetails(
                                 '<?= addslashes(htmlspecialchars($row['name'])) ?>',
                                 '<?= $row['date'] ?>',
                                 <?= $detailsJson ?>,
                                 <?= $row['total'] ?>,
                                 <?= $row['incentive'] ?>
                                 )">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                 </svg>
                                 Details
                              </button>
                           </td>
                        </tr>
                        <?php
                                    }
                                }
                            }
                        } else {
                            echo '<tr><td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center space-y-3 text-gray-400">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                              </svg>
                              <div class="text-sm font-medium">Select date range and click Filter to view records</div>
                              <p class="text-xs max-w-xs text-center">Choose a date range and optionally filter by employee to see incentive details</p>
                            </div>
                            </td></tr>';
                        }
                        ?>
                     </tbody>
                  </table>
               </div>
            </div>
         </div>
      </main>
   </div>
</div>
<!-- Modal -->
<div id="detailModal" class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center hidden opacity-0 transition-opacity duration-300 z-50">
   <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl p-6 relative mx-4 max-h-[90vh] flex flex-col">
      <button onclick="closeDetailModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition rounded-full p-1 hover:bg-gray-100">
         <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
         </svg>
      </button>
      <div class="flex items-start justify-between mb-4">
         <div>
            <h2 id="modalTitle" class="text-xl font-semibold text-gray-800"></h2>
            <p class="text-sm text-gray-500 mt-1">Detailed breakdown of services and incentives</p>
         </div>
         <div class="flex items-center space-x-2">
            <div class="px-2.5 py-1 rounded-md bg-indigo-100 text-indigo-800 text-xs font-medium">
               <span id="modalCustomerCount">0</span> customers
            </div>
            <div class="px-2.5 py-1 rounded-md bg-green-100 text-green-800 text-xs font-medium">
               <span id="modalServiceCount">0</span> services
            </div>
         </div>
      </div>
      <div class="border-t border-b border-gray-200 py-3 my-2 -mx-6 px-6 bg-gray-50">
         <div class="flex justify-between items-center">
            <div>
               <span class="text-sm text-gray-600">Total Amount:</span>
               <span id="modalTotal" class="ml-2 text-lg font-semibold text-gray-800">₹0.00</span>
            </div>
            <div>
               <span class="text-sm text-gray-600">Incentive (5%):</span>
               <span id="modalIncentive" class="ml-2 text-lg font-semibold text-green-600">₹0.00</span>
            </div>
         </div>
      </div>
      <div class="flex-1 overflow-y-auto">
         <table class="min-w-full text-sm">
            <thead class="sticky top-0 bg-white border-b border-gray-200">
               <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice ID</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service/Product</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discounts</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Final Amount</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shared With</th>
               </tr>
            </thead>
            <tbody id="modalDetailsBody" class="divide-y divide-gray-200">
               <!-- Filled dynamically -->
            </tbody>
         </table>
      </div>
      <div class="border-t border-gray-200 pt-4 mt-4 -mx-6 px-6">
         <button onclick="closeDetailModal()" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors shadow-sm">
         Close
         </button>
      </div>
   </div>
</div>
<script>
   // Store details in a global variable to prevent JSON parsing issues
   let currentDetails = null;
   
   function viewDetails(name, date, details, total, incentive) {
       currentDetails = {
           name: name,
           date: date,
           details: details,
           total: total,
           incentive: incentive
       };
       
       const modal = document.getElementById('detailModal');
       const title = document.getElementById('modalTitle');
       const tbody = document.getElementById('modalDetailsBody');
       const totalEl = document.getElementById('modalTotal');
       const incentiveEl = document.getElementById('modalIncentive');
       const customerCountEl = document.getElementById('modalCustomerCount');
       const serviceCountEl = document.getElementById('modalServiceCount');
   
       // Count unique customers
       const uniqueCustomers = new Set();
       details.forEach(item => uniqueCustomers.add(item.customer));
       
       title.textContent = `${name} • ${formatDate(date)}`;
       tbody.innerHTML = ''; // Clear old rows
       customerCountEl.textContent = uniqueCustomers.size;
       serviceCountEl.textContent = details.length;
   
       details.forEach(item => {
           const row = document.createElement('tr');
           row.className = 'hover:bg-gray-50';
           
           // Determine if it's a service or product
           const serviceProduct = item.service !== '-' ? item.service : item.product;
           
           row.innerHTML = `
               <td class="px-4 py-3 whitespace-nowrap text-gray-700">${item.invoice_id.toString().padStart(5, '0')}</td>
               <td class="px-4 py-3 whitespace-nowrap text-gray-900">${escapeHtml(item.customer)}</td>
               <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(serviceProduct)}</td>
               <td class="px-4 py-3 whitespace-nowrap text-gray-700">₹${parseFloat(item.unit_price).toFixed(2)}</td>
               <td class="px-4 py-3 text-gray-700">
                   ${item.discount_type ? `
                       <div class="flex items-center">
                           <span class="inline-block w-2 h-2 rounded-full mr-1 ${item.discount_type === 'percent' ? 'bg-blue-500' : 'bg-purple-500'}"></span>
                           ${escapeHtml(item.discount_type)} ${item.discount_value}
                       </div>
                   ` : '-'}
                   ${item.invoice_discount_type ? `
                       <div class="flex items-center mt-1">
                           <span class="inline-block w-2 h-2 rounded-full mr-1 bg-indigo-500"></span>
                           Invoice: ${escapeHtml(item.invoice_discount_type)} ${item.overall_discount_value}
                       </div>
                   ` : ''}
               </td>
               <td class="px-4 py-3 whitespace-nowrap text-gray-900 font-medium">₹${parseFloat(item.final_amount).toFixed(2)}</td>
               <td class="px-4 py-3 text-gray-700">${escapeHtml(item.shareemployee)}</td>
           `;
           tbody.appendChild(row);
       });
   
       totalEl.textContent = total.toFixed(2);
       incentiveEl.textContent = incentive.toFixed(2);
       
       // Show modal with animation
       modal.classList.remove('hidden');
       document.body.classList.add('overflow-hidden');
       setTimeout(() => {
           modal.classList.add('opacity-100');
       }, 10);
   }
   
   function closeDetailModal() {
       const modal = document.getElementById('detailModal');
       modal.classList.remove('opacity-100');
       setTimeout(() => {
           modal.classList.add('hidden');
           document.body.classList.remove('overflow-hidden');
       }, 300);
   }
   
   function formatDate(dateStr) {
       const options = { year: 'numeric', month: 'short', day: 'numeric' };
       return new Date(dateStr).toLocaleDateString(undefined, options);
   }
   
   function escapeHtml(unsafe) {
       if (typeof unsafe !== 'string') return unsafe;
       return unsafe
           .replace(/&/g, "&amp;")
           .replace(/</g, "&lt;")
           .replace(/>/g, "&gt;")
           .replace(/"/g, "&quot;")
           .replace(/'/g, "&#039;");
   }
   
   // Initialize modal behavior
   document.addEventListener('DOMContentLoaded', function() {
       const modal = document.getElementById('detailModal');
       
       // Close modal when clicking outside
       modal.addEventListener('click', function(e) {
           if (e.target === this) {
               closeDetailModal();
           }
       });
       
       // Close modal with Escape key
       document.addEventListener('keydown', function(e) {
           if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
               closeDetailModal();
           }
       });
       
       // Reopen modal with current details if needed
       window.reopenModal = function() {
           if (currentDetails) {
               viewDetails(
                   currentDetails.name,
                   currentDetails.date,
                   currentDetails.details,
                   currentDetails.total,
                   currentDetails.incentive
               );
           }
       };
   });
   
   // Ensure all view details buttons work after AJAX/filter operations
   document.addEventListener('click', function(e) {
       if (e.target.closest('[onclick^="viewDetails"]')) {
           e.preventDefault();
           const onclick = e.target.closest('[onclick^="viewDetails"]').getAttribute('onclick');
           const fn = new Function(onclick.replace('viewDetails(', '').replace(')', ''));
           fn();
       }
   });
</script>
<?php include('../includes/footer.php'); ?>