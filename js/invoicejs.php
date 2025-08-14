<script>
    // Service counter
    let serviceCounter = 0;
    let editServiceCounters = {};
    
    // Add service row to create form
    document.getElementById('addServiceBtn').addEventListener('click', function() {
        serviceCounter++;
        addServiceRow(serviceCounter, 'serviceContainer');
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
    
    // Function to add a service row
    function addServiceRow(counter, containerId, prefix = '') {
        const serviceRow = `
            <div class="p-4 border border-gray-200 rounded-lg bg-gray-50 mb-4 service-row" id="serviceRow${prefix}${counter}">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="text-sm font-medium text-gray-700">Service #${counter}</h4>
                    <button type="button" class="remove-service text-red-500 hover:text-red-700" data-row="${prefix}${counter}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <div class="flex flex-col md:flex-row md:space-x-4 space-y-4 md:space-y-0">
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-sm text-gray-700 mb-1">Service</label>
                        <select name="services[${counter}][service_id]" required class="service-select bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                            <option value="" selected disabled>-- Select Service --</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['cost']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> (₹<?php echo number_format($service['cost'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-sm text-gray-700 mb-1">Assigned Employee</label>
                        <select name="services[${counter}][employee_id]" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                            <option value="" selected disabled>-- Select Employee --</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w-28 min-w-[100px]">
                        <label class="block text-sm text-gray-700 mb-1">Price (₹)</label>
                        <input type="number" min="0" step="0.01" name="services[${counter}][price]" class="service-price bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" readonly>
                    </div>
                </div>
            </div>
        `;
        document.getElementById(containerId).insertAdjacentHTML('beforeend', serviceRow);
    }
    
    // Remove service row
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-service')) {
            const rowId = e.target.closest('.remove-service').getAttribute('data-row');
            const rowElement = document.getElementById(`serviceRow${rowId}`);
            if (rowElement) {
                rowElement.remove();
                // Determine which total to update based on the container
                if (rowId.startsWith('Edit')) {
                    const invoiceId = rowId.replace('Edit', '').split('_')[0];
                    calculateTotal(`Edit${invoiceId}_`);
                } else {
                    calculateTotal();
                }
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
                calculateTotal(`Edit${invoiceId}_`);
            } else {
                calculateTotal();
            }
        }
    });

    //========================PRODUCT==============================//
    // Product counter
let productCounter = 0;
let editProductCounters = {};

// Add product row to create form
document.getElementById('addProductBtn').addEventListener('click', function () {
    productCounter++;
    addProductRow(productCounter, 'productContainer');
});

// Add product row to edit forms
document.addEventListener('click', function (e) {
    if (e.target && e.target.id && e.target.id.startsWith('addProductBtnEdit')) {
        const invoiceId = e.target.id.replace('addProductBtnEdit', '');
        if (!editProductCounters[invoiceId]) {
            editProductCounters[invoiceId] = document.querySelectorAll(`#productContainerEdit${invoiceId} .product-row`).length;
        }
        editProductCounters[invoiceId]++;
        addProductRow(editProductCounters[invoiceId], `productContainerEdit${invoiceId}`, `Edit${invoiceId}_`);
    }
});

// Function to add a product row
function addProductRow(counter, containerId, prefix = '') {
    const productRow = `
        <div class="p-4 border border-gray-200 rounded-lg bg-gray-50 mb-4 product-row" id="productRow${prefix}${counter}">
            <div class="flex justify-between items-center mb-3">
                <h4 class="text-sm font-medium text-gray-700">Product #${counter}</h4>
                <button type="button" class="remove-product text-red-500 hover:text-red-700" data-row="${prefix}${counter}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div class="flex flex-col md:flex-row md:space-x-4 space-y-4 md:space-y-0">
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm text-gray-700 mb-1">Product</label>
                    <select name="products[${counter}][product_id]" required class="product-select bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        <option value="" selected disabled>-- Select Product --</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?> (₹<?php echo number_format($product['price'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w-28 min-w-[100px]">
                    <label class="block text-sm text-gray-700 mb-1">Price (₹)</label>
                    <input type="number" min="0" step="0.01" name="products[${counter}][price]" class="product-price bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" readonly>
                </div>
                <div class="w-28 min-w-[100px]">
                    <label class="block text-sm text-gray-700 mb-1">Qty</label>
                    <input type="number" min="1" step="1" name="products[${counter}][quantity]" class="product-qty bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" value="1">
                </div>
            </div>
        </div>
    `;
    document.getElementById(containerId).insertAdjacentHTML('beforeend', productRow);
}

// Remove product row
document.addEventListener('click', function (e) {
    if (e.target.closest('.remove-product')) {
        const rowId = e.target.closest('.remove-product').getAttribute('data-row');
        const rowElement = document.getElementById(`productRow${rowId}`);
        if (rowElement) {
            rowElement.remove();
            // Determine which total to update based on the container
            if (rowId.startsWith('Edit')) {
                const invoiceId = rowId.replace('Edit', '').split('_')[0];
                calculateTotal(`Edit${invoiceId}_`);
            } else {
                calculateTotal();
            }
        }
    }
});

// Product select change handler
document.addEventListener('change', function (e) {
    if (e.target.classList.contains('product-select')) {
        const selectedOption = e.target.options[e.target.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        const priceInput = e.target.closest('.product-row').querySelector('.product-price');
        priceInput.value = price;

        // Determine which total to update based on the container
        const containerId = e.target.closest('[id^="productContainer"]').id;
        if (containerId.startsWith('productContainerEdit')) {
            const invoiceId = containerId.replace('productContainerEdit', '');
            calculateTotal(`Edit${invoiceId}_`);
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
    
    // Discount type change handlers for edit forms
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id && e.target.id.startsWith('discount_type_edit_')) {
            const invoiceId = e.target.id.replace('discount_type_edit_', '');
            const discountValue = document.getElementById(`discount_value_edit_${invoiceId}`);
            if (e.target.value === 'none') {
                discountValue.disabled = true;
                discountValue.value = '';
            } else {
                discountValue.disabled = false;
            }
            calculateTotal(`Edit${invoiceId}_`);
        }
    });
    
    // Discount value change handlers for edit forms
    document.addEventListener('input', function(e) {
        if (e.target && e.target.id && e.target.id.startsWith('discount_value_edit_')) {
            const invoiceId = e.target.id.replace('discount_value_edit_', '');
            calculateTotal(`Edit${invoiceId}_`);
        }
    });
    
    // Discount value change handler for create form
    document.getElementById('discount_value').addEventListener('input', calculateTotal);
    
    // Calculate total
    function calculateTotal(prefix = '') {
        let subtotal = 0;
        
        // Sum all service prices
        const serviceRows = prefix 
            ? document.querySelectorAll(`#serviceContainer${prefix.replace('_', '')} .service-price`)
            : document.querySelectorAll('#serviceContainer .service-price');
        
        serviceRows.forEach(input => {
            if (input.value) {
                subtotal += parseFloat(input.value);
            }
        });
        
        // Calculate discount
        const discountType = prefix
            ? document.getElementById(`discount_type_edit_${prefix.replace('Edit', '').replace('_', '')}`).value
            : document.getElementById('discount_type').value;
        
        const discountValue = prefix
            ? parseFloat(document.getElementById(`discount_value_edit_${prefix.replace('Edit', '').replace('_', '')}`).value) || 0
            : parseFloat(document.getElementById('discount_value').value) || 0;
        
        let discount = 0;
        
        if (discountType === 'flat') {
            discount = Math.min(discountValue, subtotal);
        } else if (discountType === 'percent') {
            discount = subtotal * (Math.min(discountValue, 100) / 100);
        }
        
        const total = subtotal - discount;
        
        // Update display
        if (prefix) {
            const invoiceId = prefix.replace('Edit', '').replace('_', '');
            document.getElementById(`invoiceSubtotalEdit${invoiceId}`).textContent = subtotal.toFixed(2);
            document.getElementById(`invoiceDiscountEdit${invoiceId}`).textContent = discount.toFixed(2);
            document.getElementById(`invoiceTotalEdit${invoiceId}`).textContent = total.toFixed(2);
            
            // Update hidden fields for form submission
            document.getElementById(`total_amount_edit_${invoiceId}`).value = subtotal.toFixed(2);
            document.getElementById(`discounted_amount_edit_${invoiceId}`).value = total.toFixed(2);
        } else {
            document.getElementById('invoiceSubtotal').textContent = subtotal.toFixed(2);
            document.getElementById('invoiceDiscount').textContent = discount.toFixed(2);
            document.getElementById('invoiceTotal').textContent = total.toFixed(2);
            
            // Update hidden fields for form submission
            document.getElementById('total_amount').value = subtotal.toFixed(2);
            document.getElementById('discounted_amount').value = total.toFixed(2);
        }
    }
    
    // Initialize first service row on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('addServiceBtn').click();
        
        // Initialize edit forms with their service counts
        <?php foreach ($invoices as $invoice): ?>
            editServiceCounters['<?php echo $invoice['id']; ?>'] = document.querySelectorAll('#serviceContainerEdit<?php echo $invoice['id']; ?> .service-row').length;
            
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
                    calculateTotal('Edit<?php echo $invoice['id']; ?>_');
                });
            }
            
            if (editDiscountValue<?php echo $invoice['id']; ?>) {
                editDiscountValue<?php echo $invoice['id']; ?>.addEventListener('input', function() {
                    calculateTotal('Edit<?php echo $invoice['id']; ?>_');
                });
            }
        <?php endforeach; ?>
    });
</script>