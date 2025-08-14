<!-- Mobile hamburger button - place this in your navbar or top bar -->
<button id="sidebarToggle" class="md:hidden fixed top-4 left-4 z-50 p-2 rounded-md bg-white shadow-md focus:outline-none" aria-label="Toggle sidebar">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
  </svg>
</button>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white border-r shadow-sm transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-40 md:z-auto min-h-screen">
  <div class="p-6 flex items-center space-x-2">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
    </svg>
    <span class="text-xl font-bold text-blue-600">SalonPro</span>
  </div>
  <nav class="space-y-1 px-4 mt-6">
    <!-- your sidebar links here unchanged -->
<?php if ($_SESSION['user_role'] === 'admin'): ?>
  <a href="dashboard.php" class="sidebar-link flex items-center px-4 py-3 rounded-lg bg-blue-50 text-blue-600 font-medium">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
    </svg>
    Dashboard
  </a>
<?php endif; ?>
        <a href="service-management.php" class="sidebar-link flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
      </svg>
      Services
    </a>
<a href="product-management.php" class="sidebar-link flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4M4 10v10a1 1 0 001 1h14a1 1 0 001-1V10M12 21V9" />
  </svg>
  Products
</a>


    <a href="user-management.php" class="sidebar-link flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
      </svg>
      Employees
    </a>
    <a href="customer-management.php" class="sidebar-link flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
      </svg>
      Customers
    </a>
    <a href="invoice-management.php" class="sidebar-link flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
      </svg>
      Invoices
    </a>
    <a href="employee-incentive-history.php" class="sidebar-link flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      Incentives
    </a>
<a href="logout.php" class="sidebar-link flex items-center px-4 py-3 rounded-lg hover:bg-red-50 text-red-600">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
  </svg>
  Logout
</a>
  </nav>
</aside>

<!-- Backdrop for mobile when sidebar is open -->
<div id="sidebarBackdrop" class="fixed inset-0 bg-black bg-opacity-30 hidden z-30 md:hidden"></div>


<script>
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  const backdrop = document.getElementById('sidebarBackdrop');

  function openSidebar() {
    sidebar.classList.remove('-translate-x-full');
    backdrop.classList.remove('hidden');
  }
  function closeSidebar() {
    sidebar.classList.add('-translate-x-full');
    backdrop.classList.add('hidden');
  }

  toggleBtn.addEventListener('click', () => {
    if (sidebar.classList.contains('-translate-x-full')) {
      openSidebar();
    } else {
      closeSidebar();
    }
  });

  backdrop.addEventListener('click', closeSidebar);
</script>
