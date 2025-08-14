<?php
   include('../includes/header.php');



   
   function validatePhone($phone) {
       return preg_match('/^[0-9]{10,15}$/', $phone);
   }
   
   function getCustomerById($pdo, $id) {
       $stmt = $pdo->prepare("SELECT id, name, email, phone FROM customers WHERE id = ?");
       $stmt->execute([$id]);
       return $stmt->fetch(PDO::FETCH_ASSOC);
   }
   
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       $action = $_POST['action'] ?? '';
       try {
           if (in_array($action, ['add', 'edit'])) {
               $id = (int)($_POST['id'] ?? 0);
               $name = trim($_POST['name'] ?? '');
               $email = trim($_POST['email'] ?? '');
               $phone = trim($_POST['phone'] ?? '');
   
               if (!$name || !$phone) {
                   $_SESSION['error'] = 'Name and phone number are required';
               } elseif (!validatePhone($phone)) {
                   $_SESSION['error'] = 'Invalid phone number format';
               } else {
                   // Check for duplicate phone number
                   $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ? AND id != ?");
                   $stmt->execute([$phone, $id]);
                   if ($stmt->fetch()) {
                       $_SESSION['error'] = 'Phone number already exists';
                   } else {
                       if ($action === 'add') {
                           $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)");
                           $stmt->execute([$name, $email, $phone]);
                           $_SESSION['success'] = 'Customer added successfully';
                       } else {
                           $stmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ? WHERE id = ?");
                           $stmt->execute([$name, $email, $phone, $id]);
                           $_SESSION['success'] = 'Customer updated successfully';
                       }
                   }
               }
               header("Location: customer-management.php");
               exit;
           }
   
           if ($action === 'delete') {
               $id = (int)($_POST['id'] ?? 0);
               if ($id) {
                   $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                   $stmt->execute([$id]);
                   $_SESSION['success'] = 'Customer deleted successfully';
               } else {
                   $_SESSION['error'] = 'Invalid ID';
               }
               header("Location: customer-management.php");
               exit;
           }
   
           $_SESSION['error'] = 'Invalid action';
           header("Location: customer-management.php");
           exit;
   
       } catch (PDOException $e) {
           $_SESSION['error'] = 'Database error: ' . $e->getMessage();
           header("Location: customer-management.php");
           exit;
       }
   }
   
   // Filtering and pagination
   $search = trim($_GET['search'] ?? '');
   $statusFilter = $_GET['status'] ?? '';
   $page = max((int)($_GET['page'] ?? 1), 1);
   $limit = 10;
   $offset = ($page - 1) * $limit;
   
   $where = '';
   $params = [];
   
   if ($search !== '') {
       $where = "WHERE c.name LIKE :s1 OR c.email LIKE :s2 OR c.phone LIKE :s3";
       $params[':s1'] = "%$search%";
       $params[':s2'] = "%$search%";
       $params[':s3'] = "%$search%";
   }
   
   // Total count for pagination
   $countSql = "SELECT COUNT(*) FROM customers c $where";
   $countStmt = $pdo->prepare($countSql);
   $countStmt->execute($params);
   $total = (int)$countStmt->fetchColumn();
   $totalPages = (int)ceil($total / $limit);
   
   // Fetch customers and invoice summary
   $sql = "
   SELECT c.id, c.name, c.email, c.phone,
          COUNT(i.id) AS appointment_count,
          MIN(i.created_at) AS first_visited_at,
          MAX(i.created_at) AS last_visited_at,
          COALESCE(SUM(i.discounted_amount), 0) AS total_spent
   FROM customers c
   LEFT JOIN invoices i ON i.customer_id = c.id
   $where
   GROUP BY c.id
   ORDER BY c.id DESC
   LIMIT :limit OFFSET :offset
   ";
   
   
   $stmt = $pdo->prepare($sql);
   foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
   $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
   $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
   $stmt->execute();
   $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
   
   // Categorization logic
   $now = new DateTime();
   foreach ($customers as &$c) {
       $c['category'] = 'red'; // default: inactive
   
       if ($c['total_spent'] >= 8000) {
           $c['category'] = 'star';
       } elseif ($c['appointment_count'] > 0 && $c['last_visited_at']) {
           $first = new DateTime($c['first_visited_at']);
           $last = new DateTime($c['last_visited_at']);
           $months = max(1, ($first->diff($now)->y * 12) + $first->diff($now)->m);
           $avgPerMonth = $c['appointment_count'] / $months;
   
           if ($avgPerMonth >= 1) {
               $c['category'] = 'green'; // Regular
           } elseif ($last >= (clone $now)->modify('-3 months')) {
               $c['category'] = 'orange'; // Occasional
           }
       }
   }
   unset($c);
   
   // Filter after categorization
   if (in_array($statusFilter, ['green', 'orange', 'red', 'star'])) {
       $customers = array_filter($customers, fn($c) => $c['category'] === $statusFilter);
   }
   
   // Flash message helper
   function showAlert() {
       if (!empty($_SESSION['error'])) {
           echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm flex items-center'>
                   <svg class='w-5 h-5 mr-3' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z' clip-rule='evenodd'></path></svg>
                   ".htmlspecialchars($_SESSION['error'])."
                 </div>";
           unset($_SESSION['error']);
       }
   
       if (!empty($_SESSION['success'])) {
           echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center'>
                   <svg class='w-5 h-5 mr-3' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd'></path></svg>
                   ".htmlspecialchars($_SESSION['success'])."
                 </div>";
           unset($_SESSION['success']);
       }
   }
   ?>
<div class="min-h-screen bg-gray-50">
   <?php include('../includes/sidebar.php'); ?>
   <div class="flex-1 md:ml-64">
      <?php include('../includes/navbar.php'); ?>
      <main class="p-4 md:p-6">
         <div class="max-w-7xl mx-auto">
            <?php showAlert(); ?>
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div>
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Customer Management</h2>
        <p class="text-gray-600 mt-1 text-sm md:text-base">Manage your customer records and interactions</p>
    </div>
    <div class="flex flex-col sm:flex-row gap-3">
        <button onclick="openCustomerModal('add')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg flex items-center transition-all duration-200 shadow-sm hover:shadow-md">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Customer
        </button>
        <button onclick="window.location.href='invoice-management.php'" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-4 py-2.5 rounded-lg flex items-center transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
            </svg>
            Create Invoice
        </button>
    </div>
</div>

            <!-- Filter Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
               <div class="p-4 md:p-5 border-b border-gray-200">
                  <form method="get" class="flex flex-col md:flex-row gap-4">
                     <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                           <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                           </svg>
                        </div>
                        <input name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search customers..." 
                           class="pl-10 pr-4 py-2.5 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
                        <input type="hidden" name="page" value="1" />
                     </div>
                     <div class="flex flex-wrap gap-2">
                        <?php foreach([''=>'All','star'=>'Star','green'=>'Regular','orange'=>'Occasional','red'=>'Inactive'] as $k=>$label): ?>
                        <button name="status" value="<?= $k ?>" type="submit"
                           class="px-4 py-2 text-sm font-medium rounded-lg transition-colors <?= $statusFilter === $k ? 
                              ($k==='green'?'bg-green-100 text-green-800 border border-green-200':
                              ($k==='orange'?'bg-orange-100 text-orange-800 border border-orange-200':
                              ($k==='red'?'bg-red-100 text-red-800 border border-red-200':
                              ($k==='star'?'bg-yellow-100 text-yellow-800 border border-yellow-200':
                              'bg-gray-100 text-gray-800 border border-gray-200')))) : 
                              'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' ?>">
                        <?= $label ?>
                        </button>
                        <?php endforeach; ?>
                     </div>
                  </form>
               </div>

               <!-- Customer Table -->
               <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                     <thead class="bg-gray-50">
                        <tr>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visits</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Visit</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                           <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                     </thead>
                     <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($customers)): ?>
                        <tr>
                           <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                              <div class="flex flex-col items-center justify-center">
                                 <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                 </svg>
                                 <p class="mt-3 text-sm font-medium text-gray-600">No customers found</p>
                                 <?php if ($search || $statusFilter): ?>
                                 <p class="text-xs text-gray-500 mt-1">Try adjusting your search or filter criteria</p>
                                 <?php else: ?>
                                 <button onclick="openCustomerModal('add')" class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add your first customer
                                 </button>
                                 <?php endif; ?>
                              </div>
                           </td>
                        </tr>
                        <?php else: foreach ($customers as $i=>$c): 
                           $idx = $offset + $i + 1;
                           $last = $c['last_visited_at'] ? (new DateTime($c['last_visited_at']))->format('M j, Y') : 'Never';
                           $statusColor = match($c['category']) {
                              'star' => 'bg-yellow-100 text-yellow-800',
                              'green' => 'bg-green-100 text-green-800',
                              'orange' => 'bg-orange-100 text-orange-800',
                              'red' => 'bg-red-100 text-red-800',
                              default => 'bg-gray-100 text-gray-800'
                           };
                           $statusLabel = match($c['category']) {
                              'star' => '★ Star',
                              'green' => 'Regular',
                              'orange' => 'Occasional',
                              'red' => 'Inactive',
                              default => 'Unknown'
                           };
                           ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                           <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $idx ?></td>
                           <td class="px-6 py-4">
                              <div class="flex items-center">
                                 <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-medium">
                                    <?= substr(htmlspecialchars($c['name']), 0, 1) ?>
                                 </div>
                                 <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                       <?= htmlspecialchars($c['name']) ?>
                                       <?php if ($c['category'] === 'star'): ?>
                                       <span class="ml-1 text-yellow-500" title="Star Customer">★</span>
                                       <?php endif; ?>
                                    </div>
                                 </div>
                              </div>
                           </td>
                           <td class="px-6 py-4 whitespace-nowrap">
                              <div class="text-sm text-gray-900 flex items-center">
                                 <?php if ($c['phone']): ?>
                                 <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                 </svg>
                                 <?= htmlspecialchars($c['phone']) ?>
                                 <?php else: ?>
                                 <span class="text-gray-400">No phone</span>
                                 <?php endif; ?>
                              </div>
                              <?php if ($c['email']): ?>
                              <div class="text-sm text-gray-500 flex items-center mt-1">
                                 <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                 </svg>
                                 <?= htmlspecialchars($c['email']) ?>
                              </div>
                              <?php endif; ?>
                           </td>
                           <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                              <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-800 font-medium">
                                 <?= $c['appointment_count'] ?: '0' ?>
                              </span>
                           </td>
                           <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                              <?= $last ?>
                           </td>
                           <td class="px-6 py-4 whitespace-nowrap">
                              <span class="px-2.5 py-1 text-xs font-medium rounded-full <?= $statusColor ?>">
                                 <?= $statusLabel ?>
                              </span>
                           </td>
                           <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                              <div class="flex justify-end space-x-3">
                                 <button onclick="openCustomerModal('edit',<?= $c['id'] ?>,'<?= addslashes($c['name']) ?>','<?= addslashes($c['email']) ?>','<?= addslashes($c['phone']) ?>')" 
                                    class="text-blue-600 hover:text-blue-900 flex items-center transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                 </button>
                                 <button onclick="openHistoryModal(<?= $c['id'] ?>, '<?= addslashes($c['name']) ?>')"
                                    class="text-purple-600 hover:text-purple-900 flex items-center transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    History
                                 </button>
                                 <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this customer? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900 flex items-center transition-colors">
                                       <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                       </svg>
                                       Delete
                                    </button>
                                 </form>
                              </div>
                           </td>
                        </tr>
                        <?php endforeach; endif; ?>
                     </tbody>
                  </table>
               </div>

               <?php if ($totalPages > 1): ?>
               <div class="px-6 py-4 border-t border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4">
                  <div class="text-sm text-gray-700">
                     Showing <span class="font-medium"><?= $offset + 1 ?></span> to <span class="font-medium"><?= min($offset + $limit, $total) ?></span> of <span class="font-medium"><?= $total ?></span> customers
                  </div>
                  <nav class="flex flex-wrap gap-2">
                     <?php if ($page > 1): ?>
                     <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" 
                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Previous
                     </a>
                     <?php endif; ?>
                     <?php 
                        // Show limited pagination links
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        if ($start > 1) {
                            echo '<a href="?page=1&search='.urlencode($search).'&status='.urlencode($statusFilter).'" 
                                  class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">1</a>';
                            if ($start > 2) echo '<span class="px-3 py-1.5 text-gray-500">...</span>';
                        }
                        
                        for ($p = $start; $p <= $end; $p++): ?>
                     <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"
                        class="px-3 py-1.5 border rounded-lg text-sm font-medium <?= $p===$page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50' ?> transition-colors">
                        <?= $p ?>
                     </a>
                     <?php endfor;
                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) echo '<span class="px-3 py-1.5 text-gray-500">...</span>';
                            echo '<a href="?page='.$totalPages.'&search='.urlencode($search).'&status='.urlencode($statusFilter).'" 
                                  class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">'.$totalPages.'</a>';
                        }
                        ?>
                     <?php if ($page < $totalPages): ?>
                     <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" 
                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Next
                     </a>
                     <?php endif; ?>
                  </nav>
               </div>
               <?php endif; ?>
            </div>
         </div>
      </main>
   </div>
</div>

<!-- Add/Edit Modal -->
 <?php include('../modals/customer/add.php') ?>

<!-- History Modal -->

 <?php include('../modals/customer/history.php') ?>

<script>
   function openCustomerModal(action, id='', name='', email='', phone='') {
     document.getElementById('formAction').value = action;
     document.getElementById('customerId').value = id;
     document.getElementById('name').value = name || '';
     document.getElementById('email').value = email || '';
     document.getElementById('phone').value = phone || '';
     document.getElementById('modalTitle').textContent = action === 'add' ? 'Add New Customer' : 'Edit Customer';
     
     const modal = document.getElementById('customerModal');
     modal.classList.remove('hidden');
     document.body.classList.add('overflow-hidden');
     
     // Focus on first input field
     setTimeout(() => {
       const firstInput = modal.querySelector('input:not([type="hidden"])');
       if (firstInput) firstInput.focus();
     }, 100);
   }
   
   function closeModal() {
     document.getElementById('customerModal').classList.add('hidden');
     document.body.classList.remove('overflow-hidden');
   }
   
   // Open history modal and fetch data
   function openHistoryModal(id, name) {
    const modal = document.getElementById('historyModal');
    const title = document.getElementById('historyTitle');
    const content = document.getElementById('historyContent');

    title.textContent = `History for ${name}`;
    content.textContent = 'Loading history...';

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

    fetch(`../actions/history.php?customer_id=${encodeURIComponent(id)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.history.length === 0) {
                    content.innerHTML = '<p>No history records found.</p>';
                } else {
                    const rows = data.history.map(item => `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border-b">#${item.invoice_id}</td>
                            <td class="px-4 py-2 border-b">${item.date}</td>
                            <td class="px-4 py-2 border-b">${item.details}</td>
                            <td class="px-4 py-2 border-b">${item.discount_type}</td>
                            <td class="px-4 py-2 border-b text-right">₹${item.total_amount}</td>
                            <td class="px-4 py-2 border-b text-right">₹${item.discounted_amount}</td>
                        </tr>
                    `).join('');

                    content.innerHTML = `
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm border border-gray-200 rounded-lg">
                                <thead class="bg-gray-100 text-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left border-b">Invoice #</th>
                                        <th class="px-4 py-2 text-left border-b">Date</th>
                                        <th class="px-4 py-2 text-left border-b">Service(s)</th>
                                        <th class="px-4 py-2 text-left border-b">Discount</th>
                                        <th class="px-4 py-2 text-right border-b">Total</th>
                                        <th class="px-4 py-2 text-right border-b">Discounted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rows}
                                    <tr class="font-semibold bg-gray-50">
                                        <td colspan="5" class="px-4 py-3 text-right border-t">Total Discounted:</td>
                                        <td class="px-4 py-3 text-right border-t">₹${data.total_discounted}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    `;
                }
            } else {
                content.textContent = 'Failed to load history.';
            }
        })
        .catch(() => {
            content.textContent = 'Failed to load history.';
        });
}

   
   // Close history modal
   function closeHistoryModal() {
       document.getElementById('historyModal').classList.add('hidden');
       document.body.classList.remove('overflow-hidden');
   }
   
   // Close modal when clicking outside
   document.getElementById('customerModal').addEventListener('click', function(e) {
     if (e.target === this) closeModal();
   });
   
   document.getElementById('historyModal').addEventListener('click', function(e) {
     if (e.target === this) closeHistoryModal();
   });
   
   // Close modal with ESC key
   document.addEventListener('keydown', function(e) {
     if (e.key === 'Escape') {
       if (!document.getElementById('customerModal').classList.contains('hidden')) {
         closeModal();
       }
       if (!document.getElementById('historyModal').classList.contains('hidden')) {
         closeHistoryModal();
       }
     }
   });
</script>
<?php include('../includes/footer.php'); ?>