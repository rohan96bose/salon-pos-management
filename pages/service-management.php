<?php
   include('../includes/header.php');
   
   // Utility functions
   function validCost($cost) { return is_numeric($cost) && $cost >= 0; }
   function getServiceById($pdo, $id) {
       $stmt = $pdo->prepare("SELECT id, name, description, cost FROM services WHERE id = ?");
       $stmt->execute([$id]);
       return $stmt->fetch(PDO::FETCH_ASSOC);
   }
   
   // --- Handle POST: add, edit, delete ---
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       $action = $_POST['action'] ?? '';
       try {
           if ($action === 'add' || $action === 'edit') {
               $id = (int)($_POST['id'] ?? 0);
               $name = trim($_POST['name'] ?? '');
               $description = trim($_POST['description'] ?? '');
               $cost = $_POST['cost'] ?? '';
   
               if (empty($name) || $cost === '') {
                   $_SESSION['error'] = 'Service name and cost are required';
                   header("Location: service-management.php" . ($action === 'edit' ? "?action=edit&id=$id" : "?action=add"));
                   exit;
               }
               if (!validCost($cost)) {
                   $_SESSION['error'] = 'Invalid cost format';
                   header("Location: service-management.php" . ($action === 'edit' ? "?action=edit&id=$id" : "?action=add"));
                   exit;
               }
   
               $stmt = $pdo->prepare("SELECT id FROM services WHERE name = ? AND id != ?");
               $stmt->execute([$name, $id]);
               if ($stmt->fetch()) {
                   $_SESSION['error'] = 'Service name already exists';
                   header("Location: service-management.php" . ($action === 'edit' ? "?action=edit&id=$id" : "?action=add"));
                   exit;
               }
   
               if ($action === 'add') {
                   $stmt = $pdo->prepare("INSERT INTO services (name, description, cost) VALUES (?, ?, ?)");
                   $stmt->execute([$name, $description, $cost]);
                   $_SESSION['success'] = 'Service added successfully';
               } else {
                   $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, cost = ? WHERE id = ?");
                   $stmt->execute([$name, $description, $cost, $id]);
                   $_SESSION['success'] = 'Service updated successfully';
               }
   
               header("Location: service-management.php");
               exit;
           }
   
           if ($action === 'delete') {
               $id = (int)($_POST['id'] ?? 0);
               if (!$id) {
                   $_SESSION['error'] = 'Invalid service ID';
                   header("Location: service-management.php");
                   exit;
               }
               $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
               $stmt->execute([$id]);
               $_SESSION['success'] = 'Service deleted successfully';
               header("Location: service-management.php");
               exit;
           }
   
           $_SESSION['error'] = 'Invalid action';
           header("Location: service-management.php");
           exit;
   
       } catch (PDOException $e) {
           $_SESSION['error'] = 'Database error: ' . $e->getMessage();
           header("Location: service-management.php");
           exit;
       }
   }
   
   // --- Search & Pagination Setup ---
   $search = trim($_GET['search'] ?? '');
   $page = max((int)($_GET['page'] ?? 1), 1);
   $limit = 10; // Increased from 5 to 10 for better mobile view
   $offset = ($page - 1) * $limit;
   
   $whereClause = '';
   $params = [];
   if ($search !== '') {
       $whereClause = "WHERE name LIKE :search1 OR description LIKE :search2";
       $params[':search1'] = "%$search%";
       $params[':search2'] = "%$search%";
   }
   
   // Count total
   $countSql = "SELECT COUNT(*) FROM services $whereClause";
   $countStmt = $pdo->prepare($countSql);
   $countStmt->execute($params);
   $totalRecords = (int)$countStmt->fetchColumn();
   $totalPages = (int)ceil($totalRecords / $limit);
   
   // Fetch paginated services
   $sql = "SELECT id, name, description, cost FROM services $whereClause ORDER BY id DESC LIMIT :limit OFFSET :offset";
   $stmt = $pdo->prepare($sql);
   foreach ($params as $key => $val) {
       $stmt->bindValue($key, $val, PDO::PARAM_STR);
   }
   $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
   $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
   $stmt->execute();
   $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
   
   // Load service for edit/view
   $editService = null;
   if (in_array($_GET['action'] ?? '', ['edit', 'view']) && isset($_GET['id'])) {
       $editService = getServiceById($pdo, (int)$_GET['id']);
       if (!$editService) {
           $_SESSION['error'] = 'Service not found';
           header("Location: service-management.php");
           exit;
       }
   }
   
   // Alert helper
   function showAlert() {
       if (!empty($_SESSION['error'])) {
           echo '<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r">';
           echo '<div class="flex items-center">';
           echo '<div class="flex-shrink-0 text-red-500">';
           echo '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">';
           echo '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />';
           echo '</svg>';
           echo '</div>';
           echo '<div class="ml-3">';
           echo '<p class="text-sm text-red-700">'.htmlspecialchars($_SESSION['error']).'</p>';
           echo '</div>';
           echo '</div>';
           echo '</div>';
           unset($_SESSION['error']);
       } elseif (!empty($_SESSION['success'])) {
           echo '<div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-r">';
           echo '<div class="flex items-center">';
           echo '<div class="flex-shrink-0 text-green-500">';
           echo '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">';
           echo '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />';
           echo '</svg>';
           echo '</div>';
           echo '<div class="ml-3">';
           echo '<p class="text-sm text-green-700">'.htmlspecialchars($_SESSION['success']).'</p>';
           echo '</div>';
           echo '</div>';
           echo '</div>';
           unset($_SESSION['success']);
       }
   }
   ?>
<div class="min-h-screen bg-gray-50 flex flex-col md:flex-row">
   <!-- Sidebar -->
   <?php include('../includes/sidebar.php'); ?>
   <!-- Main Content -->
   <div class="flex-1 md:ml-64 flex flex-col min-h-screen">
      <?php include('../includes/navbar.php'); ?>
      <main class="p-4 md:p-6 flex-1 overflow-auto">
         <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header and Add Button -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
               <div>
                  <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Service Management</h1>
                  <p class="text-gray-600 mt-1">Manage all salon services and pricing</p>
               </div>
               <button id="addServiceBtn" 
                  class="inline-flex items-center justify-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" 
                  type="button">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                     <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                  </svg>
                  <span>Add Service</span>
               </button>
            </div>
            <?php showAlert(); ?>
            <!-- Search and Filter -->
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
               <form method="get" action="service-management.php" class="relative max-w-md">
                  <div class="flex items-center">
                     <div class="relative flex-grow">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                           <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                              <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                           </svg>
                        </div>
                        <input type="text" name="search" placeholder="Search services..." value="<?= htmlspecialchars($search) ?>"
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none">
                     </div>
                     <?php if ($search): ?>
                     <a href="service-management.php" class="ml-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition-colors duration-200">
                     Clear
                     </a>
                     <?php endif; ?>
                  </div>
               </form>
            </div>
            <!-- Services Table -->
            <div class="bg-white overflow-hidden rounded-lg shadow-sm border border-gray-200">
               <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                     <thead class="bg-gray-50">
                        <tr>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Description</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                           <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                     </thead>
                     <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($services)): ?>
                        <?php foreach ($services as $service): ?>
                        <tr class="hover:bg-gray-50">
                           <td class="px-6 py-4 whitespace-nowrap">
                              <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($service['name']) ?></div>
                              <div class="text-sm text-gray-500 sm:hidden"><?= htmlspecialchars(substr($service['description'], 0, 30)) ?><?= strlen($service['description']) > 30 ? '...' : '' ?></div>
                           </td>
                           <td class="px-6 py-4 whitespace-normal text-sm text-gray-500 hidden sm:table-cell">
                              <?= htmlspecialchars($service['description']) ?>
                           </td>
                           <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                              ₹<?= number_format($service['cost'], 2) ?>
                           </td>
                           <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                              <button
                                 class="editBtn text-indigo-600 hover:text-indigo-900 p-1 rounded-full hover:bg-indigo-50 transition-colors duration-200"
                                 data-id="<?= htmlspecialchars($service['id']) ?>"
                                 data-name="<?= htmlspecialchars($service['name']) ?>"
                                 data-desc="<?= htmlspecialchars($service['description']) ?>"
                                 data-cost="<?= htmlspecialchars($service['cost']) ?>"
                                 type="button"
                                 aria-label="Edit service"
                                 >
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                 </svg>
                              </button>
                              <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                 <input type="hidden" name="id" value="<?= htmlspecialchars($service['id']) ?>">
                                 <input type="hidden" name="action" value="delete">
                                 <button type="submit" class="text-red-600 hover:text-red-900 p-1 rounded-full hover:bg-red-50 transition-colors duration-200" aria-label="Delete service">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                       <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                 </button>
                              </form>
                           </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                           <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                              <div class="flex flex-col items-center justify-center py-8">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                 </svg>
                                 <h3 class="mt-2 text-sm font-medium text-gray-700">No services found</h3>
                                 <p class="mt-1 text-sm text-gray-500">Try adjusting your search or add a new service</p>
                              </div>
                           </td>
                        </tr>
                        <?php endif; ?>
                     </tbody>
                  </table>
               </div>
            </div>
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 rounded-b-lg">
               <div class="flex-1 flex items-center justify-between">
                  <div>
                     <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?= ($offset + 1) ?></span>
                        to <span class="font-medium"><?= min($offset + $limit, $totalRecords) ?></span>
                        of <span class="font-medium"><?= $totalRecords ?></span> services
                     </p>
                  </div>
                  <div>
                     <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                        <a href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>"
                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                           <span class="sr-only">Previous</span>
                           <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                              <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                           </svg>
                        </a>
                        <?php endif; ?>
                        <?php 
                           // Show page numbers with ellipsis
                           $neighboringPages = 2;
                           $startPage = max(1, $page - $neighboringPages);
                           $endPage = min($totalPages, $page + $neighboringPages);
                           
                           if ($startPage > 1) {
                               echo '<a href="?search='.urlencode($search).'&page=1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                               if ($startPage > 2) {
                                   echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                               }
                           }
                           
                           for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"
                           class="<?= $i == $page ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                        <?= $i ?>
                        </a>
                        <?php endfor;
                           if ($endPage < $totalPages) {
                               if ($endPage < $totalPages - 1) {
                                   echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                               }
                               echo '<a href="?search='.urlencode($search).'&page='.$totalPages.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$totalPages.'</a>';
                           }
                           ?>
                        <?php if ($page < $totalPages): ?>
                        <a href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>"
                           class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                           <span class="sr-only">Next</span>
                           <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                           </svg>
                        </a>
                        <?php endif; ?>
                     </nav>
                  </div>
               </div>
            </div>
            <?php endif; ?>
         </div>
      </main>
   </div>
   <!-- Modal for Add/Edit -->
   <div id="serviceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-50 transition-opacity duration-300">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md transform transition-all duration-300">
         <div class="px-6 py-4 border-b border-gray-200">
            <h2 id="modalTitle" class="text-xl font-semibold text-gray-800">Add Service</h2>
         </div>
         <form id="serviceForm" method="post" action="service-management.php" class="space-y-4 p-6">
            <input type="hidden" name="id" id="serviceId" value="">
            <input type="hidden" name="action" id="formAction" value="add">
            <div>
               <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Service Name *</label>
               <input type="text" name="name" id="name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
            </div>
            <div>
               <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
               <textarea name="description" id="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
            </div>
            <div>
               <label for="cost" class="block text-sm font-medium text-gray-700 mb-1">Cost *</label>
               <div class="mt-1 relative rounded-md shadow-sm">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                     <span class="text-gray-500 sm:text-sm">₹</span>
                  </div>
                  <input type="number" step="0.01" min="0" name="cost" id="cost" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 py-2 sm:text-sm border-gray-300 rounded-md" placeholder="0.00" required>
                  <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                     <span class="text-gray-500 sm:text-sm">INR</span>
                  </div>
               </div>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
               <button type="button" id="cancelBtn" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
               Cancel
               </button>
               <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
               Save Service
               </button>
            </div>
         </form>
      </div>
   </div>
</div>
<script>
   const addBtn = document.getElementById('addServiceBtn');
   const modal = document.getElementById('serviceModal');
   const modalTitle = document.getElementById('modalTitle');
   const form = document.getElementById('serviceForm');
   const formAction = document.getElementById('formAction');
   const serviceId = document.getElementById('serviceId');
   const nameInput = document.getElementById('name');
   const descriptionInput = document.getElementById('description');
   const costInput = document.getElementById('cost');
   const cancelBtn = document.getElementById('cancelBtn');
   
   // Show modal for adding new service
   addBtn.addEventListener('click', () => {
       modalTitle.textContent = 'Add Service';
       formAction.value = 'add';
       serviceId.value = '';
       nameInput.value = '';
       descriptionInput.value = '';
       costInput.value = '';
       modal.classList.remove('hidden');
       document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
   });
   
   // Show modal for editing service
   document.querySelectorAll('.editBtn').forEach(button => {
       button.addEventListener('click', () => {
           modalTitle.textContent = 'Edit Service';
           formAction.value = 'edit';
           serviceId.value = button.dataset.id;
           nameInput.value = button.dataset.name;
           descriptionInput.value = button.dataset.desc;
           costInput.value = button.dataset.cost;
           modal.classList.remove('hidden');
           document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
       });
   });
   
   // Close modal
   function closeModal() {
       modal.classList.add('hidden');
       document.body.style.overflow = 'auto'; // Re-enable scrolling
   }
   
   cancelBtn.addEventListener('click', closeModal);
   
   // Close modal if clicking outside the modal content or pressing ESC
   modal.addEventListener('click', (e) => {
       if (e.target === modal) {
           closeModal();
       }
   });
   
   document.addEventListener('keydown', (e) => {
       if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
           closeModal();
       }
   });
</script>
<?php include('../includes/footer.php'); ?>