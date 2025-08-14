<div id="historyModal" class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4 transition-opacity">
   <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col transform transition-all">
      <div class="flex items-center justify-between border-b border-gray-200 p-6">
         <h3 id="historyTitle" class="text-xl font-bold text-gray-800">Customer History</h3>
         <button onclick="closeHistoryModal()" class="text-gray-400 hover:text-gray-500 p-1 rounded-full hover:bg-gray-100 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
         </button>
      </div>
      <div class="overflow-y-auto p-6">
         <div id="historyContent" class="text-gray-700">
            Loading history...
         </div>
      </div>
      <div class="border-t border-gray-200 p-4 bg-gray-50">
         <div class="flex justify-end">
            <button onclick="closeHistoryModal()" class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
               Close
            </button>
         </div>
      </div>
   </div>
</div>