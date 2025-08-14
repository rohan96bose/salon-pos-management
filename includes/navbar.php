<?php

  $userName = $_SESSION['user_name'] ?? 'User';
  $userRole = $_SESSION['user_role'] ?? 'employee';
  $initial = strtoupper(substr($userName, 0, 1));
  $roleLabel = ucfirst($userName);
?>
<header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center border-b sticky top-0 z-10">
  <h1 class="text-xl font-semibold text-gray-800 flex items-center">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
    </svg>
    Dashboard
  </h1>
  <div class="flex items-center space-x-4">
    <div class="relative">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500 hover:text-blue-500 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
    </div>
   <div class="flex items-center space-x-2">
  <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium">
    <?= htmlspecialchars($initial) ?>
  </div>
  <span class="text-gray-700"><?= htmlspecialchars($roleLabel) ?></span>
</div>

  </div>
</header>
