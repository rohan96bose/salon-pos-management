<div id="customerModal" class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4 transition-opacity">
   <div class="bg-white rounded-xl shadow-xl w-full max-w-md transform transition-all">
      <div class="p-6">
         <div class="flex items-center justify-between mb-4">
            <h3 id="modalTitle" class="text-xl font-bold text-gray-800">Add Customer</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500 p-1 rounded-full hover:bg-gray-100 transition-colors">
               <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
               </svg>
            </button>
         </div>
         <form id="customerForm" method="post">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="customerId">
            <div class="space-y-4">
               <div>
                  <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                  <input name="name" id="name" required 
                     class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" 
                     placeholder="John Doe" />
               </div>
               <div>
                  <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                  <input name="phone" id="phone" required 
                     class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" 
                     placeholder="9876543210" />
                  <p class="text-xs text-gray-500 mt-1">10-15 digits only</p>
               </div>
               <div>
                  <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email (optional)</label>
                  <input type="email" name="email" id="email" 
                     class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" 
                     placeholder="john@example.com" />
               </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
               <button type="button" onclick="closeModal()" 
                  class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                  Cancel
               </button>
               <button type="submit" 
                  class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm">
                  Save Customer
               </button>
            </div>
         </form>
      </div>
   </div>
</div>