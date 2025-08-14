<?php
   include('../includes/header.php');
   
   // Helper functions
   function validRole($role) {
       $validRoles = ['admin', 'receptionist', 'employee'];
       return in_array(strtolower($role), $validRoles);
   }
   
   function validStatus($status) {
       return in_array($status, [0, 1], true);
   }
   
   function getUserById($pdo, $id) {
       $stmt = $pdo->prepare("SELECT id, name, email, role, status, created_at FROM users WHERE id = ?");
       $stmt->execute([$id]);
       return $stmt->fetch(PDO::FETCH_ASSOC);
   }
   
   // Handle form submissions
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       $action = $_POST['action'] ?? '';
       
       try {
           switch ($action) {
               case 'add':
                   $name = trim($_POST['name'] ?? '');
                   $email = trim($_POST['email'] ?? '');
                   $password = $_POST['password'] ?? '';
                   $role = strtolower(trim($_POST['role'] ?? ''));
                   $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
   
                   // Validation
                   if (empty($name) || empty($email) || empty($password) || empty($role)) {
                       $_SESSION['error'] = 'All fields are required';
                       header("Location: user-management.php?action=add");
                       exit();
                   }
   
                   if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                       $_SESSION['error'] = 'Invalid email format';
                       header("Location: user-management.php?action=add");
                       exit();
                   }
   
                   if (!validRole($role)) {
                       $_SESSION['error'] = 'Invalid role selected';
                       header("Location: user-management.php?action=add");
                       exit();
                   }
   
                   if (!validStatus($status)) {
                       $status = 0;
                   }
   
                   if (strlen($password) < 8 || !preg_match('/\d/', $password)) {
                       $_SESSION['error'] = 'Password must be at least 8 characters with at least one number';
                       header("Location: user-management.php?action=add");
                       exit();
                   }
   
                   // Check if email exists
                   $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                   $stmt->execute([$email]);
                   if ($stmt->fetch()) {
                       $_SESSION['error'] = 'Email already exists';
                       header("Location: user-management.php?action=add");
                       exit();
                   }
   
                   // Hash password and insert
                   $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                   $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
                   $stmt->execute([$name, $email, $passwordHash, $role, $status]);
   
                   $_SESSION['success'] = 'User added successfully';
                   header("Location: user-management.php");
                   exit();
   
               case 'edit':
                   $id = (int)($_POST['id'] ?? 0);
                   $name = trim($_POST['name'] ?? '');
                   $email = trim($_POST['email'] ?? '');
                   $password = $_POST['password'] ?? '';
                   $role = strtolower(trim($_POST['role'] ?? ''));
                   $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
   
                   // Validation
                   if (empty($id) || empty($name) || empty($email) || empty($role)) {
                       $_SESSION['error'] = 'All required fields must be filled';
                       header("Location: user-management.php?action=edit&id=$id");
                       exit();
                   }
   
                   if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                       $_SESSION['error'] = 'Invalid email format';
                       header("Location: user-management.php?action=edit&id=$id");
                       exit();
                   }
   
                   if (!validRole($role)) {
                       $_SESSION['error'] = 'Invalid role selected';
                       header("Location: user-management.php?action=edit&id=$id");
                       exit();
                   }
   
                   if (!validStatus($status)) {
                       $status = 0;
                   }
   
                   // Check if email exists for another user
                   $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                   $stmt->execute([$email, $id]);
                   if ($stmt->fetch()) {
                       $_SESSION['error'] = 'Email already exists for another user';
                       header("Location: user-management.php?action=edit&id=$id");
                       exit();
                   }
   
                   // Update user
                   if (!empty($password)) {
                       if (strlen($password) < 8 || !preg_match('/\d/', $password)) {
                           $_SESSION['error'] = 'Password must be at least 8 characters with at least one number';
                           header("Location: user-management.php?action=edit&id=$id");
                           exit();
                       }
                       $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                       $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ?, status = ? WHERE id = ?");
                       $stmt->execute([$name, $email, $passwordHash, $role, $status, $id]);
                   } else {
                       $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
                       $stmt->execute([$name, $email, $role, $status, $id]);
                   }
   
                   $_SESSION['success'] = 'User updated successfully';
                   header("Location: user-management.php");
                   exit();
   
               case 'delete':
                   $id = (int)($_POST['id'] ?? 0);
                   if (empty($id)) {
                       $_SESSION['error'] = 'Invalid user ID';
                       header("Location: user-management.php");
                       exit();
                   }
   
                   // Prevent self-deletion
                   if ($id === $_SESSION['user_id']) {
                       $_SESSION['error'] = 'You cannot delete your own account';
                       header("Location: user-management.php");
                       exit();
                   }
   
                   $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                   $stmt->execute([$id]);
   
                   $_SESSION['success'] = 'User deleted successfully';
                   header("Location: user-management.php");
                   exit();
           }
       } catch (PDOException $e) {
           $_SESSION['error'] = 'Database error: ' . $e->getMessage();
           header("Location: user-management.php");
           exit();
       }
   }
   
   // Get all users for listing // Pagination setup
   $limit = 10; // users per page
   $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
   $offset = ($page - 1) * $limit;
   
   // Count total users
   $totalStmt = $pdo->query("SELECT COUNT(*) FROM users");
   $totalUsers = $totalStmt->fetchColumn();
   $totalPages = ceil($totalUsers / $limit);
   
   // Fetch paginated users
   $stmt = $pdo->prepare("SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
   $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
   $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
   $stmt->execute();
   $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
   
   
   // Get user for editing/viewing if requested
   $editUser = null;
   if (isset($_GET['action'])) {
       if ($_GET['action'] === 'edit' || $_GET['action'] === 'view') {
           $id = (int)($_GET['id'] ?? 0);
           if ($id) {
               $editUser = getUserById($pdo, $id);
               if (!$editUser) {
                   $_SESSION['error'] = 'User not found';
                   header("Location: user-management.php");
                   exit();
               }
           }
       }
   }
   
   ?>
<div class="min-h-auto bg-gray-50">
   <?php include('../includes/sidebar.php'); ?>
   <div class="ml-0 md:ml-64 flex flex-col min-h-screen">
      <?php include('../includes/navbar.php'); ?>
      <main class="p-4 md:p-6 flex-grow">
         <!-- Notifications -->
         <?php if (isset($_SESSION['error'])): ?>
         <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($_SESSION['error']) ?></span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none'">
               <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                  <title>Close</title>
                  <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
               </svg>
            </span>
         </div>
         <?php unset($_SESSION['error']); ?>
         <?php endif; ?>
         <?php if (isset($_SESSION['success'])): ?>
         <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($_SESSION['success']) ?></span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none'">
               <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                  <title>Close</title>
                  <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
               </svg>
            </span>
         </div>
         <?php unset($_SESSION['success']); ?>
         <?php endif; ?>
         <div class="flex flex-col space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
               <div>
                  <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
                  <p class="text-gray-600 mt-1">Manage all system users and their permissions</p>
               </div>
               <a href="user-management.php?action=add" class="mt-4 md:mt-0 flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg shadow-sm transition-colors duration-200">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                     <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                  </svg>
                  <span>Add User</span>
               </a>
            </div>
            <?php if (!isset($_GET['action'])): ?>
            <!-- Search Bar -->
            <div class="w-full max-w-md">
               <input type="text" id="searchUserInput" placeholder="Search users by name or email..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                  onkeyup="filterUsers()" aria-label="Search users" >
            </div>
            <?php endif; ?>
            <!-- User Form (add/edit/view) logic unchanged -->
            <?php if (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit'])): ?>
            <div class="bg-white overflow-hidden rounded-xl shadow-sm p-6">
               <h2 class="text-2xl font-bold text-gray-800 mb-6">
                  <?= $_GET['action'] === 'add' ? 'Add New User' : 'Edit User' ?>
               </h2>
               <form method="POST" action="user-management.php" class="space-y-4">
                  <input type="hidden" name="action" value="<?= $_GET['action'] ?>">
                  <?php if ($_GET['action'] === 'edit' && isset($editUser)): ?>
                  <input type="hidden" name="id" value="<?= htmlspecialchars($editUser['id']) ?>">
                  <?php endif; ?>
                  <div>
                     <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                     <input type="text" id="name" name="name" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                        placeholder="Enter full name" value="<?= isset($editUser) ? htmlspecialchars($editUser['name']) : '' ?>"
                        required  >
                  </div>
                  <div>
                     <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                     <input type="email" id="email" name="email" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                        placeholder="Enter email address"value="<?= isset($editUser) ? htmlspecialchars($editUser['email']) : '' ?>"
                        required >
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                     <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select id="role" name="role" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                           required >
                           <option value="">Select Role</option>
                           <option value="admin" <?= (isset($editUser) && $editUser['role'] === 'admin') ? 'selected' : '' ?>>Administrator</option>
                           <option value="receptionist" <?= (isset($editUser) && $editUser['role'] === 'receptionist') ? 'selected' : '' ?>>Receptionist</option>
                           <option value="employee" <?= (isset($editUser) && $editUser['role'] === 'employee') ? 'selected' : '' ?>>Employee</option>
                        </select>
                     </div>
                     <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select 
                           id="status" name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                           required >
                           <option value="1" <?= (isset($editUser)) && $editUser['status'] == 1 ? 'selected' : '' ?>>Active</option>
                           <option value="0" <?= (isset($editUser)) && $editUser['status'] == 0 ? 'selected' : '' ?>>Inactive</option>
                        </select>
                     </div>
                  </div>
                  <div>
                     <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                     Password <?= $_GET['action'] === 'edit' ? '(Leave blank to keep current)' : '' ?>
                     </label>
                     <div class="relative">
                        <input 
                           type="password"  id="password"  name="password" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition pr-10"
                           placeholder="Enter password" <?= $_GET['action'] === 'add' ? 'required' : '' ?> >
                        <button 
                           type="button" 
                           onclick="togglePasswordVisibility('password')"
                           class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600" >
                           <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                              <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                              <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                           </svg>
                        </button>
                     </div>
                     <p class="mt-1 text-xs text-gray-500">Minimum 8 characters with at least one number</p>
                  </div>
                  <div class="flex justify-end space-x-3 pt-2">
                     <a href="user-management.php" class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                     Cancel   </a>
                     <button type="submit" class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 flex items-center space-x-1" >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                           <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Save User</span>
                     </button>
                  </div>
               </form>
            </div>
            <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($editUser)): ?>
            <div class="bg-white overflow-hidden rounded-xl shadow-sm p-6">
               <h2 class="text-2xl font-bold text-gray-800 mb-6">User Details</h2>
               <div class="space-y-4">
                  <div>
                     <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                     <p class="text-gray-900"><?= htmlspecialchars($editUser['name']) ?></p>
                  </div>
                  <div>
                     <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                     <p class="text-gray-900"><?= htmlspecialchars($editUser['email']) ?></p>
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                     <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <p class="text-gray-900 capitalize"><?= htmlspecialchars($editUser['role']) ?></p>
                     </div>
                     <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <p class="text-gray-900">
                           <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $editUser['status'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                           <?= $editUser['status'] ? 'status' : 'Instatus' ?>
                           </span>
                        </p>
                     </div>
                  </div>
                  <div>
                     <label class="block text-sm font-medium text-gray-700 mb-1">Member Since</label>
                     <p class="text-gray-900"><?= date('F j, Y', strtotime($editUser['created_at'])) ?></p>
                  </div>
                  <div class="flex justify-end pt-2">
                     <a href="user-management.php" class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                     Back to Users </a>
                  </div>
               </div>
            </div>
            <?php else: ?>
            <!-- Users Table -->
            <div class="bg-white overflow-hidden rounded-xl shadow-sm">
               <div class="overflow-x-auto">
                  <table id="usersTable" class="min-w-full divide-y divide-gray-200">
                     <thead class="bg-gray-50">
                        <tr>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                           <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member Since</th>
                           <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                     </thead>
                     <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                           <td class="px-4 md:px-6 py-3 whitespace-nowrap">
                              <div class="flex items-center space-x-3 md:space-x-4">
                                 <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-medium text-lg">
                                    <?= strtoupper(substr($user['name'], 0, 1)) . (strpos($user['name'], ' ') !== false ? strtoupper(substr($user['name'], strpos($user['name'], ' ') + 1, 1)) : '') ?>
                                 </div>
                                 <div class="flex flex-col overflow-hidden">
                                    <div class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($user['name']) ?></div>
                                    <div class="text-xs text-gray-500 truncate max-w-xs md:max-w-sm"><?= htmlspecialchars($user['email']) ?></div>
                                 </div>
                              </div>
                           </td>
                           <td class="px-4 md:px-6 py-3 whitespace-nowrap">
                              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 capitalize"><?= htmlspecialchars($user['role']) ?></span>
                           </td>
                           <td class="px-4 md:px-6 py-3 whitespace-nowrap">
                              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $user['status'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                              <?= $user['status'] ? 'Active' : 'Inactive' ?>
                              </span>
                           </td>
                           <td class="px-4 md:px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                              <?= date('M j, Y', strtotime($user['created_at'])) ?>
                           </td>
                           <td class="px-4 md:px-6 py-3 whitespace-nowrap text-right text-sm font-medium">
                              <div class="flex justify-end space-x-3">
                                 <a href="user-management.php?action=edit&id=<?= $user['id'] ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                       <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                    </svg>
                                 </a>
                                 <a href="user-management.php?action=view&id=<?= $user['id'] ?>" class="text-gray-400 hover:text-gray-600" title="View">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                       <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                       <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                    </svg>
                                 </a>
                                 <form method="POST" action="user-management.php" class="inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <button type="submit" onclick="return confirm('Are you sure you want to delete this user?')" class="text-red-500 hover:text-red-700" title="Delete">
                                       <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                          <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 10-2 0v6a1 1 0 102 0V8z" clip-rule="evenodd" />
                                       </svg>
                                    </button>
                                 </form>
                              </div>
                           </td>
                        </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>
               <!-- Pagination (if you have server side, integrate here) --> <!-- Pagination -->
               <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center space-y-2 md:space-y-0">
                  <div class="text-sm text-gray-700">
                     Showing <?= count($users) ?> of <?= $totalUsers ?> users
                  </div>
                  <nav class="inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                     <?php if ($page > 1): ?>
                     <a href="?page=<?= $page - 1 ?>" class="px-3 py-1 border border-gray-300 bg-white text-gray-500 hover:bg-gray-100 rounded-l-md">Previous</a>
                     <?php endif; ?>
                     <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                     <a href="?page=<?= $i ?>" class="px-3 py-1 border border-gray-300 <?= $i === $page ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">
                     <?= $i ?>
                     </a>
                     <?php endfor; ?>
                     <?php if ($page < $totalPages): ?>
                     <a href="?page=<?= $page + 1 ?>" class="px-3 py-1 border border-gray-300 bg-white text-gray-500 hover:bg-gray-100 rounded-r-md">Next</a>
                     <?php endif; ?>
                  </nav>
               </div>
            </div>
            <?php endif; ?>
         </div>
      </main>
   </div>
</div>
<script>
   function togglePasswordVisibility(fieldId) {
      const field = document.getElementById(fieldId);
      field.type = field.type === 'password' ? 'text' : 'password';
   }
   
   function filterUsers() {
   const input = document.getElementById('searchUserInput');
   const filter = input.value.toLowerCase();
   const table = document.getElementById('usersTable');
   const trs = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
   
   for (let i = 0; i < trs.length; i++) {
      const tdName = trs[i].getElementsByTagName('td')[0];
      const tdEmail = tdName.querySelector('div > div:nth-child(2)');
      const nameText = tdName.textContent.toLowerCase();
      const emailText = tdEmail.textContent.toLowerCase();
   
      if (nameText.indexOf(filter) > -1 || emailText.indexOf(filter) > -1) {
          trs[i].style.display = '';
      } else {
          trs[i].style.display = 'none';
      }
   }
   }
</script>
<?php include('../includes/footer.php'); ?>