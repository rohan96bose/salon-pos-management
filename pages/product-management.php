<?php
include('../includes/header.php');

// Utility functions
function validPrice($price) { return is_numeric($price) && $price >= 0; }
function getProductById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT id, name, description, price, stock_quantity FROM product WHERE id = ?");
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
            $price = $_POST['price'] ?? '';
            $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);

            if (empty($name) || $price === '') {
                $_SESSION['error'] = 'Product name and price are required';
                header("Location: product-management.php" . ($action === 'edit' ? "?action=edit&id=$id" : "?action=add"));
                exit;
            }
            if (!validPrice($price)) {
                $_SESSION['error'] = 'Invalid price format';
                header("Location: product-management.php" . ($action === 'edit' ? "?action=edit&id=$id" : "?action=add"));
                exit;
            }
            if ($stock_quantity < 0) {
                $_SESSION['error'] = 'Stock quantity cannot be negative';
                header("Location: product-management.php" . ($action === 'edit' ? "?action=edit&id=$id" : "?action=add"));
                exit;
            }

            // Check for duplicate product name excluding current id
            $stmt = $pdo->prepare("SELECT id FROM product WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'Product name already exists';
                header("Location: product-management.php" . ($action === 'edit' ? "?action=edit&id=$id" : "?action=add"));
                exit;
            }

            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO product (name, description, price, stock_quantity) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $stock_quantity]);
                $_SESSION['success'] = 'Product added successfully';
            } else {
                $stmt = $pdo->prepare("UPDATE product SET name = ?, description = ?, price = ?, stock_quantity = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $stock_quantity, $id]);
                $_SESSION['success'] = 'Product updated successfully';
            }

            header("Location: product-management.php");
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $_SESSION['error'] = 'Invalid product ID';
                header("Location: product-management.php");
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM product WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = 'Product deleted successfully';
            header("Location: product-management.php");
            exit;
        }

        $_SESSION['error'] = 'Invalid action';
        header("Location: product-management.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        header("Location: product-management.php");
        exit;
    }
}

// --- Search & Pagination Setup ---
$search = trim($_GET['search'] ?? '');
$page = max((int)($_GET['page'] ?? 1), 1);
$limit = 5;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];
if ($search !== '') {
    $whereClause = "WHERE name LIKE :search1 OR description LIKE :search2";
    $params[':search1'] = "%$search%";
    $params[':search2'] = "%$search%";
}

// Count total
$countSql = "SELECT COUNT(*) FROM product $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalRecords / $limit);

// Fetch paginated products
$sql = "SELECT id, name, description, price, stock_quantity FROM product $whereClause ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load product for edit/view
$editProduct = null;
if (in_array($_GET['action'] ?? '', ['edit', 'view']) && isset($_GET['id'])) {
    $editProduct = getProductById($pdo, (int)$_GET['id']);
    if (!$editProduct) {
        $_SESSION['error'] = 'Product not found';
        header("Location: product-management.php");
        exit;
    }
}

// Alert helper
function showAlert() {
    if (!empty($_SESSION['error'])) {
        echo '<div class="bg-red-100 text-red-800 p-3 rounded mb-4">'.htmlspecialchars($_SESSION['error']).'</div>';
        unset($_SESSION['error']);
    } elseif (!empty($_SESSION['success'])) {
        echo '<div class="bg-green-100 text-green-800 p-3 rounded mb-4">'.htmlspecialchars($_SESSION['success']).'</div>';
        unset($_SESSION['success']);
    }
}
?>
<div class="min-h-auto bg-gray-50 flex flex-col md:flex-row">

    <!-- Sidebar -->
    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <div class="flex-1 md:ml-64 flex flex-col min-h-auto">

        <?php include('../includes/navbar.php'); ?>

        <main class="p-6 flex-1 overflow-auto">

            <div class="flex flex-col space-y-6">

                <!-- Header and Add Button -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Product Management</h1>
                        <p class="text-gray-600 mt-1">Manage all products, pricing, and stock</p>
                    </div>
                    <button id="addProductBtn" 
                            class="mt-4 md:mt-0 flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg shadow-sm transition-colors duration-200" 
                            type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        <span>Add Product</span>
                    </button>
                </div>

                <?php showAlert(); ?>

                <!-- Search -->
                <div class="bg-white p-4 rounded-xl shadow-sm max-w-md mx-auto md:mx-0">
                    <form method="get" action="product-management.php" class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>"
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </form>
                </div>

                <!-- Products Table -->
                <div class="bg-white overflow-hidden rounded-xl shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($products) === 0): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No products found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($product['id']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($product['name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($product['description']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right">₹<?= number_format($product['price'], 2) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right"><?= (int)$product['stock_quantity'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-1">
                                            <button type="button" 
                                                    class="editBtn text-indigo-600 hover:text-indigo-900"
                                                    data-id="<?= $product['id'] ?>"
                                                    data-name="<?= htmlspecialchars($product['name']) ?>"
                                                    data-description="<?= htmlspecialchars($product['description']) ?>"
                                                    data-price="<?= $product['price'] ?>"
                                                    data-stock-quantity="<?= $product['stock_quantity'] ?>"
                                            ><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg></button>
                                            <form method="post" action="product-management.php" class="inline" onsubmit="return confirm('Are you sure to delete this product?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">   <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="px-6 py-4 flex justify-center space-x-2" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(['page' => $page - 1, 'search' => $search]) ?>" class="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300">Prev</a>
                            <?php endif; ?>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <a href="?<?= http_build_query(['page' => $p, 'search' => $search]) ?>" 
                                   class="px-3 py-1 rounded <?= $p === $page ? 'bg-indigo-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?<?= http_build_query(['page' => $page + 1, 'search' => $search]) ?>" class="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300">Next</a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal -->
<div id="productModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
        <button id="closeModal" class="absolute top-3 right-3 text-gray-500 hover:text-gray-900" aria-label="Close modal">
            &times;
        </button>
        <h2 id="modalTitle" class="text-xl font-semibold mb-4">Add Product</h2>

        <form id="productForm" method="post" action="product-management.php" class="space-y-4">
            <input type="hidden" name="action" value="add" id="formAction">
            <input type="hidden" name="id" id="productId" value="">

            <div>
                <label for="name" class="block font-medium text-gray-700">Product Name</label>
                <input type="text" name="name" id="name" required class="mt-1 block w-full border border-gray-300 rounded-md p-2" maxlength="100" />
            </div>

            <div>
                <label for="description" class="block font-medium text-gray-700">Description</label>
                <textarea name="description" id="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md p-2"></textarea>
            </div>

            <div>
                <label for="price" class="block font-medium text-gray-700">Price ($)</label>
                <input type="number" name="price" id="price" step="0.01" min="0" required class="mt-1 block w-full border border-gray-300 rounded-md p-2" />
            </div>

            <div>
                <label for="stock_quantity" class="block font-medium text-gray-700">Stock Quantity</label>
                <input type="number" name="stock_quantity" id="stock_quantity" min="0" required class="mt-1 block w-full border border-gray-300 rounded-md p-2" value="0" />
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" id="cancelBtn" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('productModal');
    const addBtn = document.getElementById('addProductBtn');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('productForm');
    const formActionInput = document.getElementById('formAction');
    const productIdInput = document.getElementById('productId');
    const nameInput = document.getElementById('name');
    const descriptionInput = document.getElementById('description');
    const priceInput = document.getElementById('price');
    const stockQuantityInput = document.getElementById('stock_quantity');

    function openModal(action = 'add', product = {}) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (action === 'add') {
            modalTitle.textContent = 'Add Product';
            formActionInput.value = 'add';
            productIdInput.value = '';
            nameInput.value = '';
            descriptionInput.value = '';
            priceInput.value = '';
            stockQuantityInput.value = 0;
        } else if (action === 'edit') {
            modalTitle.textContent = 'Edit Product';
            formActionInput.value = 'edit';
            productIdInput.value = product.id || '';
            nameInput.value = product.name || '';
            descriptionInput.value = product.description || '';
            priceInput.value = product.price || '';
            stockQuantityInput.value = product.stock_quantity || 0;
        }
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    addBtn.addEventListener('click', () => openModal('add'));

    closeModalBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // Edit buttons
    document.querySelectorAll('.editBtn').forEach(button => {
        button.addEventListener('click', () => {
            openModal('edit', {
                id: button.dataset.id,
                name: button.dataset.name,
                description: button.dataset.description,
                price: button.dataset.price,
                stock_quantity: button.dataset.stockQuantity
            });
        });
    });

    // Close modal on Escape key
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
</script>

<?php
include('../includes/footer.php');
?>
