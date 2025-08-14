<?php 
   include('../includes/header.php');
   
   // Database connection assumed in $pdo
   
   // Dates for calculations (same as before)
   $today = date('Y-m-d');
   $yesterday = date('Y-m-d', strtotime('-1 day'));
   $this_month_start = date('Y-m-01');
   $this_month_end = date('Y-m-t');
   $last_month_start = date('Y-m-01', strtotime('first day of last month'));
   $last_month_end = date('Y-m-t', strtotime('last day of last month'));
   
   // 1. Total Customers this month and last month
   $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE DATE(created_at) BETWEEN ? AND ?");
   $stmt->execute([$this_month_start, $this_month_end]);
   $total_customers_this_month = $stmt->fetchColumn();
   
   $stmt->execute([$last_month_start, $last_month_end]);
   $total_customers_last_month = $stmt->fetchColumn();
   
   $total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
   
   $customers_growth = $total_customers_last_month > 0 ? (($total_customers_this_month - $total_customers_last_month) / $total_customers_last_month) * 100 : 0;
   $customers_growth = round($customers_growth, 1);
   
   // 2. Total Employees (all time) and new hires last 30 days
   $total_employees_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'employee'");
   $total_employees_stmt->execute();
   $total_employees = $total_employees_stmt->fetchColumn();
   
   $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
   $new_hires_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'employee' AND DATE(created_at) >= ?");
   $new_hires_stmt->execute([$thirty_days_ago]);
   $new_hires = $new_hires_stmt->fetchColumn();
   
   // 3. Today's Earnings and yesterday's earnings
   $stmt = $pdo->prepare("SELECT SUM(discounted_amount) FROM invoices WHERE DATE(created_at) = ?");
   $stmt->execute([$today]);
   $todays_earnings = $stmt->fetchColumn() ?: 0;
   
   $stmt->execute([$yesterday]);
   $yesterdays_earnings = $stmt->fetchColumn() ?: 0;
   
   $earnings_growth = $yesterdays_earnings > 0 ? (($todays_earnings - $yesterdays_earnings) / $yesterdays_earnings) * 100 : 0;
   $earnings_growth = round($earnings_growth, 1);
   
   // 4. Total Incentives and growth vs last month incentives
   $stmt = $pdo->prepare("SELECT SUM(discounted_amount) FROM invoices WHERE DATE(created_at) BETWEEN ? AND ?");
   $stmt->execute([$this_month_start, $this_month_end]);
   $discounted_this_month = $stmt->fetchColumn() ?: 0;
   
   $stmt->execute([$last_month_start, $last_month_end]);
   $discounted_last_month = $stmt->fetchColumn() ?: 0;
   
   $total_incentives = $discounted_this_month * 0.05;
   $last_month_incentives = $discounted_last_month * 0.05;
   
   $incentives_growth = $last_month_incentives > 0 ? (($total_incentives - $last_month_incentives) / $last_month_incentives) * 100 : 0;
   $incentives_growth = round($incentives_growth, 1);
   ?>
<div class="flex flex-col md:flex-row min-h-screen">
   <?php include('../includes/sidebar.php'); ?>
   <div class="flex-1 md:ml-64 flex flex-col">
      <?php include('../includes/navbar.php'); ?>
      <main class="p-4 md:p-6 flex-grow bg-gray-50">
         <!-- KPI Cards -->
         <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Customers -->
            <div class="bg-white p-6 rounded-xl shadow border border-gray-100 hover:shadow-lg transition fade-in">
               <div class="flex justify-between items-start">
                  <div>
                     <p class="text-sm font-medium text-gray-500">Total Customers</p>
                     <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= number_format($total_customers) ?></h3>
                     <p class="text-xs mt-2 flex items-center <?= $customers_growth >= 0 ? 'text-green-500' : 'text-red-500' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $customers_growth >= 0 ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3' ?>" />
                        </svg>
                        <?= abs($customers_growth) ?>% from last month
                     </p>
                  </div>
                  <div class="p-3 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                     </svg>
                  </div>
               </div>
            </div>
            <!-- Total Employees -->
            <div class="bg-white p-6 rounded-xl shadow border border-gray-100 hover:shadow-lg transition fade-in">
               <div class="flex justify-between items-start">
                  <div>
                     <p class="text-sm font-medium text-gray-500">Total Employees</p>
                     <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= number_format($total_employees) ?></h3>
                     <p class="text-xs text-green-500 mt-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                        <?= $new_hires ?> new hires (30 days)
                     </p>
                  </div>
                  <div class="p-3 rounded-lg bg-green-50 text-green-600 flex items-center justify-center">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                     </svg>
                  </div>
               </div>
            </div>
            <!-- Today's Earnings -->
            <div class="bg-white p-6 rounded-xl shadow border border-gray-100 hover:shadow-lg transition fade-in">
               <div class="flex justify-between items-start">
                  <div>
                     <p class="text-sm font-medium text-gray-500">Today's Earnings</p>
                     <h3 class="text-2xl font-bold text-gray-800 mt-1">₹<?= number_format($todays_earnings, 2) ?></h3>
                     <p class="text-xs mt-2 flex items-center <?= $earnings_growth >= 0 ? 'text-green-500' : 'text-red-500' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $earnings_growth >= 0 ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3' ?>" />
                        </svg>
                        <?= abs($earnings_growth) ?>% from yesterday
                     </p>
                  </div>
                  <div class="p-3 rounded-lg bg-yellow-50 text-yellow-600 flex items-center justify-center">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                     </svg>
                  </div>
               </div>
            </div>
            <!-- Total Incentives -->
            <div class="bg-white p-6 rounded-xl shadow border border-gray-100 hover:shadow-lg transition fade-in">
               <div class="flex justify-between items-start">
                  <div>
                     <p class="text-sm font-medium text-gray-500">Total Incentives</p>
                     <h3 class="text-2xl font-bold text-gray-800 mt-1">₹<?= number_format($total_incentives, 2) ?></h3>
                     <p class="text-xs mt-2 flex items-center <?= $incentives_growth >= 0 ? 'text-green-500' : 'text-red-500' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $incentives_growth >= 0 ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3' ?>" />
                        </svg>
                        <?= abs($incentives_growth) ?>% from last month
                     </p>
                  </div>
                  <div class="p-3 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                     </svg>
                  </div>
               </div>
            </div>
         </div>
         <section class="bg-white rounded-xl p-6 shadow mb-8 flex flex-col lg:flex-row gap-6">
            <div class="flex-1">
               <div class="flex justify-between items-center mb-4">
                  <h2 class="text-lg font-bold text-gray-800">Revenue (₹)</h2>
                  <select id="revenueRange" class="rounded-md border border-gray-300 p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                     <option value="weekly">Weekly (Last 6 Weeks)</option>
                     <option value="quarterly">Quarterly (This Year)</option>
                     <option value="yearly" selected>Yearly (12 Months)</option>
                  </select>
               </div>
               <canvas id="revenueChart" class="w-full h-60"></canvas>
            </div>
            <div class="flex-1">
               <div class="flex justify-between items-center mb-4">
                  <h2 class="text-lg font-bold text-gray-800">Appointments</h2>
                  <select id="appointmentsRange" class="rounded-md border border-gray-300 p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                     <option value="weekly">Weekly (Last 6 Weeks)</option>
                     <option value="quarterly">Quarterly (This Year)</option>
                     <option value="yearly" selected>Yearly (12 Months)</option>
                  </select>
               </div>
               <canvas id="appointmentsChart" class="w-full h-60"></canvas>
            </div>
         </section>
      </main>
   </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
   document.addEventListener('DOMContentLoaded', function () {
     // Initialize charts with empty data
     const revenueCtx = document.getElementById('revenueChart').getContext('2d');
     const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
   
     let revenueChart = new Chart(revenueCtx, {
       type: 'bar',
       data: {
         labels: [],
         datasets: [{
           label: 'Revenue',
           data: [],
           backgroundColor: 'rgba(59, 130, 246, 0.5)',
           borderColor: 'rgba(59, 130, 246, 1)',
           borderWidth: 1
         }]
       },
       options: {
         scales: {
           y: { beginAtZero: true }
         }
       }
     });
   
     let appointmentsChart = new Chart(appointmentsCtx, {
       type: 'bar',
       data: {
         labels: [],
         datasets: [{
           label: 'Appointments',
           data: [],
           backgroundColor: 'rgba(16, 185, 129, 0.5)',
           borderColor: 'rgba(16, 185, 129, 1)',
           borderWidth: 1
         }]
       },
       options: {
         scales: {
           y: { beginAtZero: true }
         }
       }
     });
   
     // Fetch and update function for Revenue
     function fetchRevenue(days) {
       fetch(`../actions/revenue.php?days=${days}`)
         .then(res => res.json())
         .then(data => {
           revenueChart.data.labels = data.labels;
           revenueChart.data.datasets[0].data = data.values;
           revenueChart.update();
         })
         .catch(console.error);
     }
   
     // Fetch and update function for Appointments
     function fetchAppointments(days) {
       fetch(`../actions/appointments.php?days=${days}`)
         .then(res => res.json())
         .then(data => {
           appointmentsChart.data.labels = data.labels;
           appointmentsChart.data.datasets[0].data = data.values;
           appointmentsChart.update();
         })
         .catch(console.error);
     }
   
     // Initial fetch for 30 days
     fetchRevenue(30);
     fetchAppointments(30);
   
     // Event listeners for selects
     document.getElementById('revenueRange').addEventListener('change', (e) => {
       fetchRevenue(e.target.value);
     });
   
     document.getElementById('appointmentsRange').addEventListener('change', (e) => {
       fetchAppointments(e.target.value);
     });
   });
</script>