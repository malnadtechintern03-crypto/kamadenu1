<?php
/**
 * Kamadenu Goushala Platform - Admin Products Management with Image Upload & Edit
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/UploadService.php';

require_role(['super_admin', 'admin', 'manager']);

$currentUser = get_logged_in_user();

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    verify_csrf_or_die();

    $name = sanitize_input($_POST['name'] ?? '');
    $slug = sanitize_input($_POST['slug'] ?? '') ?: slugify($name);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $sku = sanitize_input($_POST['sku'] ?? '');
    $unit = sanitize_input($_POST['unit'] ?? '500ml');
    $price = (float)($_POST['price'] ?? 0);
    $discountPrice = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
    $stock = max(0, (int)($_POST['stock_quantity'] ?? 50));
    $shortDesc = sanitize_input($_POST['short_description'] ?? '');
    $desc = sanitize_input($_POST['description'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $waSelect = sanitize_input($_POST['whatsapp_number_select'] ?? '');
    $waCustom = sanitize_input($_POST['whatsapp_number'] ?? '');
    $waNumber = ($waSelect === 'custom' || empty($waSelect)) ? $waCustom : $waSelect;
    $waMessage = sanitize_input($_POST['whatsapp_message'] ?? '');

    $uploadedFilename = null;
    if (!empty($_FILES['image']['name'])) {
        try {
            $uploadedFilename = UploadService::upload($_FILES['image'], 'products');
        } catch (Exception $e) {
            set_flash('danger', 'Image upload error: ' . $e->getMessage());
        }
    }

    if (!empty($name) && $categoryId > 0 && $price > 0 && !empty($sku)) {
        $finalImage = $uploadedFilename ?: 'placeholder-product.jpg';

        Database::insert("
            INSERT INTO products (
                category_id, name, slug, sku, unit, price, discount_price,
                stock_quantity, short_description, description, main_image, whatsapp_number, whatsapp_message, is_active, is_featured, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ", [$categoryId, $name, $slug, $sku, $unit, $price, $discountPrice, $stock, $shortDesc, $desc, $finalImage, $waNumber ?: null, $waMessage ?: null, $isActive, $isFeatured]);

        log_activity((int)($currentUser['id'] ?? 0), 'create_product', 'products', null, "Added store product: {$name} ({$sku})");
        set_flash('success', "Product '{$name}' created successfully.");
        header('Location: ' . BASE_URL . '/admin/products.php');
        exit;
    } else {
        set_flash('danger', 'Please fill in all mandatory product fields (Name, SKU, Category, Price).');
    }
}

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    verify_csrf_or_die();

    $productId = (int)($_POST['product_id'] ?? 0);
    $name = sanitize_input($_POST['name'] ?? '');
    $slug = sanitize_input($_POST['slug'] ?? '') ?: slugify($name);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $sku = sanitize_input($_POST['sku'] ?? '');
    $unit = sanitize_input($_POST['unit'] ?? '500ml');
    $price = (float)($_POST['price'] ?? 0);
    $discountPrice = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
    $stock = max(0, (int)($_POST['stock_quantity'] ?? 0));
    $shortDesc = sanitize_input($_POST['short_description'] ?? '');
    $desc = sanitize_input($_POST['description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    $waSelect = sanitize_input($_POST['whatsapp_number_select'] ?? '');
    $waCustom = sanitize_input($_POST['whatsapp_number'] ?? '');
    $waNumber = ($waSelect === 'custom' || empty($waSelect)) ? $waCustom : $waSelect;
    $waMessage = sanitize_input($_POST['whatsapp_message'] ?? '');

    $product = Database::fetchOne("SELECT * FROM products WHERE id = ?", [$productId]);

    if (!$product) {
        set_flash('danger', 'Product not found.');
        header('Location: ' . BASE_URL . '/admin/products.php');
        exit;
    }

    $uploadedFilename = null;
    if (!empty($_FILES['image']['name'])) {
        try {
            $uploadedFilename = UploadService::upload($_FILES['image'], 'products');
            if ($uploadedFilename && !empty($product['main_image']) && $product['main_image'] !== 'placeholder-product.jpg') {
                UploadService::delete($product['main_image'], 'products');
            }
        } catch (Exception $e) {
            set_flash('danger', 'Image upload error: ' . $e->getMessage());
        }
    }

    if (!empty($name) && $categoryId > 0 && $price > 0 && !empty($sku)) {
        $finalImage = $uploadedFilename ?: ($product['main_image'] ?? 'placeholder-product.jpg');

        Database::execute("
            UPDATE products SET 
                category_id = ?, sku = ?, name = ?, slug = ?, 
                short_description = ?, description = ?, price = ?, discount_price = ?, 
                stock_quantity = ?, unit = ?, main_image = ?, whatsapp_number = ?, whatsapp_message = ?, is_featured = ?, is_active = ?, 
                updated_at = NOW()
            WHERE id = ?
        ", [
            $categoryId, $sku, $name, $slug, 
            $shortDesc, $desc, $price, $discountPrice, 
            $stock, $unit, $finalImage, $waNumber ?: null, $waMessage ?: null, $isFeatured, $isActive, 
            $productId
        ]);

        log_activity((int)($currentUser['id'] ?? 0), 'update_product', 'products', $productId, "Updated store product: {$name} ({$sku})");
        set_flash('success', "Product '{$name}' updated successfully.");
        header('Location: ' . BASE_URL . '/admin/products.php');
        exit;
    } else {
        set_flash('danger', 'Please fill in all mandatory product fields (Name, SKU, Category, Price).');
    }
}

// Handle Quick Stock Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    verify_csrf_or_die();
    $prodId = (int)($_POST['product_id'] ?? 0);
    $newStock = max(0, (int)($_POST['stock_quantity'] ?? 0));
    Database::execute("UPDATE products SET stock_quantity = ? WHERE id = ?", [$newStock, $prodId]);
    set_flash('success', 'Stock level updated.');
    header('Location: ' . BASE_URL . '/admin/products.php');
    exit;
}

// Handle Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_product') {
    verify_csrf_or_die();
    $prodId = (int)($_POST['product_id'] ?? 0);
    $product = Database::fetchOne("SELECT * FROM products WHERE id = ?", [$prodId]);
    if ($product) {
        UploadService::delete($product['main_image'], 'products');
        Database::execute("DELETE FROM products WHERE id = ?", [$prodId]);
        log_activity((int)($currentUser['id'] ?? 0), 'delete_product', 'products', $prodId, "Deleted product ID {$prodId}");
        set_flash('success', 'Product deleted successfully.');
    }
    header('Location: ' . BASE_URL . '/admin/products.php');
    exit;
}

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    verify_csrf_or_die();
    
    $name = sanitize_input($_POST['category_name'] ?? '');
    $slug = sanitize_input($_POST['category_slug'] ?? '') ?: slugify($name);
    $description = sanitize_input($_POST['category_description'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    
    if (empty($name)) {
        set_flash('danger', 'Category name is required.');
    } else {
        $existing = Database::fetchOne("SELECT id FROM product_categories WHERE name = ? OR slug = ?", [$name, $slug]);
        if ($existing) {
            set_flash('danger', "A category with the name '{$name}' or slug '{$slug}' already exists.");
        } else {
            $uploadedFilename = null;
            if (!empty($_FILES['category_image']['name'])) {
                try {
                    $uploadedFilename = UploadService::upload($_FILES['category_image'], 'products');
                } catch (Exception $e) {
                    set_flash('warning', 'Category image upload failed: ' . $e->getMessage());
                }
            }
            
            $imagePath = $uploadedFilename ? ('uploads/products/' . $uploadedFilename) : null;
            
            Database::insert("
                INSERT INTO product_categories (name, slug, description, image, display_order, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ", [$name, $slug, $description, $imagePath, $displayOrder]);
            
            log_activity((int)($currentUser['id'] ?? 0), 'create_category', 'product_categories', null, "Added product category: {$name}");
            set_flash('success', "Category '{$name}' created successfully.");
        }
    }
    header('Location: ' . BASE_URL . '/admin/products.php');
    exit;
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_category') {
    verify_csrf_or_die();
    
    $catId = (int)($_POST['category_id'] ?? 0);
    $name = sanitize_input($_POST['category_name'] ?? '');
    $slug = sanitize_input($_POST['category_slug'] ?? '') ?: slugify($name);
    $description = sanitize_input($_POST['category_description'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    
    $category = Database::fetchOne("SELECT * FROM product_categories WHERE id = ?", [$catId]);
    if (!$category) {
        set_flash('danger', 'Category not found.');
    } elseif (empty($name)) {
        set_flash('danger', 'Category name is required.');
    } else {
        $existing = Database::fetchOne("SELECT id FROM product_categories WHERE (name = ? OR slug = ?) AND id != ?", [$name, $slug, $catId]);
        if ($existing) {
            set_flash('danger', "Another category with the name '{$name}' or slug '{$slug}' already exists.");
        } else {
            $uploadedFilename = null;
            if (!empty($_FILES['category_image']['name'])) {
                try {
                    $uploadedFilename = UploadService::upload($_FILES['category_image'], 'products');
                    if ($uploadedFilename && !empty($category['image']) && !str_starts_with($category['image'], 'assets/')) {
                        UploadService::delete(basename($category['image']), 'products');
                    }
                } catch (Exception $e) {
                    set_flash('warning', 'Category image upload failed: ' . $e->getMessage());
                }
            }
            
            $finalImage = $uploadedFilename ? ('uploads/products/' . $uploadedFilename) : ($category['image'] ?? null);
            
            Database::execute("
                UPDATE product_categories SET 
                    name = ?, slug = ?, description = ?, image = ?, display_order = ?
                WHERE id = ?
            ", [$name, $slug, $description, $finalImage, $displayOrder, $catId]);
            
            log_activity((int)($currentUser['id'] ?? 0), 'update_category', 'product_categories', $catId, "Updated product category: {$name}");
            set_flash('success', "Category '{$name}' updated successfully.");
        }
    }
    header('Location: ' . BASE_URL . '/admin/products.php');
    exit;
}

// Handle Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_category') {
    verify_csrf_or_die();
    
    $catId = (int)($_POST['category_id'] ?? 0);
    $category = Database::fetchOne("SELECT * FROM product_categories WHERE id = ?", [$catId]);
    if ($category) {
        $count = (int)Database::fetchColumn("SELECT COUNT(*) FROM products WHERE category_id = ?", [$catId]);
        if ($count > 0) {
            set_flash('danger', "Cannot delete category '{$category['name']}' because {$count} product(s) are assigned to it. Reassign or delete those products first.");
        } else {
            if (!empty($category['image']) && !str_starts_with($category['image'], 'assets/')) {
                UploadService::delete(basename($category['image']), 'products');
            }
            Database::execute("DELETE FROM product_categories WHERE id = ?", [$catId]);
            log_activity((int)($currentUser['id'] ?? 0), 'delete_category', 'product_categories', $catId, "Deleted category: {$category['name']}");
            set_flash('success', "Category '{$category['name']}' was deleted successfully.");
        }
    } else {
        set_flash('danger', 'Category not found.');
    }
    header('Location: ' . BASE_URL . '/admin/products.php');
    exit;
}

$products = Database::fetchAll("
    SELECT p.*, pc.name AS category_name 
    FROM products p 
    JOIN product_categories pc ON p.category_id = pc.id 
    ORDER BY p.id DESC
");

$categories = Database::fetchAll("
    SELECT pc.*, COUNT(p.id) AS product_count 
    FROM product_categories pc 
    LEFT JOIN products p ON pc.id = p.category_id 
    GROUP BY pc.id 
    ORDER BY pc.display_order ASC, pc.id ASC
");

$waNumbersList = get_whatsapp_numbers();

$pageTitle = 'Organic Products Store Inventory';

require_once __DIR__ . '/includes/header.php';
?>

<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h4 font-serif text-forest-dark mb-0">Organic Store Products & Inventory (<?= count($products); ?>)</h1>
            <small class="text-muted">Manage Vedic A2 Ghee, Panchagavya, categories, and sanctuary store items.</small>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-outline-forest rounded-pill px-3 shadow-xs" data-bs-toggle="modal" data-bs-target="#manageCategoriesModal">
                <i class="bi bi-tags-fill me-1"></i> Categories (<?= count($categories); ?>)
            </button>
            <button type="button" class="btn btn-forest rounded-pill px-3 shadow-xs" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle me-1"></i> Add Category
            </button>
            <button type="button" class="btn btn-gold rounded-pill px-4 shadow-gold" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-circle me-1"></i> Add New Product
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-cream-soft">
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No products cataloged yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): 
                        $prodImg = image_url($p['main_image'] ?? null, 'products', 'placeholder-product.jpg');
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 50px; height: 50px; background: var(--color-forest-dark);">
                                    <img src="<?= e($prodImg); ?>" alt="<?= e($p['name']); ?>" class="w-100 h-100 object-fit-cover">
                                </div>
                                <div>
                                    <div class="fw-bold text-forest-dark"><?= e($p['name']); ?></div>
                                    <small class="text-muted"><?= e($p['unit']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-cream text-forest border"><?= e($p['category_name']); ?></span></td>
                        <td><code class="small text-muted"><?= e($p['sku']); ?></code></td>
                        <td>
                            <strong class="font-serif text-forest-dark"><?= format_inr((float)$p['price']); ?></strong>
                            <?php if ($p['discount_price']): ?>
                                <small class="text-muted text-decoration-line-through d-block"><?= format_inr((float)$p['discount_price']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="" class="d-flex align-items-center gap-2">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="update_stock">
                                <input type="hidden" name="product_id" value="<?= $p['id']; ?>">
                                <input type="number" name="stock_quantity" class="form-control form-control-sm text-center" style="width: 70px;" value="<?= $p['stock_quantity']; ?>" min="0">
                                <button type="submit" class="btn btn-outline-forest btn-sm py-0 px-2" title="Save Stock">Save</button>
                            </form>
                        </td>
                        <td>
                            <?php if ($p['is_active']): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border">Draft / Inactive</span>
                            <?php endif; ?>
                            <?php if ($p['is_featured']): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1" title="Featured on Homepage"><i class="bi bi-star-fill"></i></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                <!-- WhatsApp Share & Message Customizer Button -->
                                <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-2 py-1" data-bs-toggle="modal" data-bs-target="#whatsappProductModal<?= $p['id']; ?>" title="Share on WhatsApp with Custom Message">
                                    <i class="bi bi-whatsapp"></i>
                                </button>
                                <a href="<?= BASE_URL; ?>/product-details.php?slug=<?= e($p['slug']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-2 py-1" title="View Public Product">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-outline-forest btn-sm rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#editProductModal<?= $p['id']; ?>" title="Edit Product">
                                    <i class="bi bi-pencil-square"></i> <span>Edit</span>
                                </button>
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this product?');" class="d-inline">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="product_id" value="<?= $p['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-1" title="Delete Product">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-forest-dark text-white p-4">
                <h5 class="modal-title font-serif"><i class="bi bi-box-seam text-gold me-2"></i> Add Organic Store Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL; ?>/admin/products.php" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_product">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Product Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Pure Vedic A2 Gir Cow Bilona Ghee (1000ml)" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">SKU Code *</label>
                            <input type="text" name="sku" class="form-control font-monospace" placeholder="GHEE-GIR-1L" required>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label small fw-bold mb-1">Product Category *</label>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#addCategoryModal" class="small text-forest text-decoration-none fw-bold"><i class="bi bi-plus"></i> New</a>
                            </div>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id']; ?>"><?= e($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Packaging Unit *</label>
                            <input type="text" name="unit" class="form-control" placeholder="e.g. 1000 ml Glass Jar" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Stock Quantity *</label>
                            <input type="number" name="stock_quantity" class="form-control" value="50" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Standard Price (INR) *</label>
                            <input type="number" name="price" class="form-control" placeholder="1850" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Discounted Offer Price (INR)</label>
                            <input type="number" name="discount_price" class="form-control" placeholder="1699" step="0.01">
                        </div>

                        <!-- Product Image Upload -->
                        <div class="col-12">
                            <label class="form-label small fw-bold">Product Photograph (JPG, PNG, WEBP max 5MB)</label>
                            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">URL Slug (Optional)</label>
                            <input type="text" name="slug" class="form-control" placeholder="e.g. a2-gir-cow-bilona-ghee">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Short Summary</label>
                            <input type="text" name="short_description" class="form-control" placeholder="Brief 1-sentence product highlight">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Detailed Description & Vedic Method</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Preparation details, ingredients, Ayurveda references..."></textarea>
                        </div>

                        <!-- WhatsApp Order Routing & Preset Message Slab -->
                        <div class="col-12">
                            <div class="p-3 rounded-4 bg-cream-soft border border-success border-opacity-50">
                                <h6 class="font-serif text-forest-dark mb-2">
                                    <i class="bi bi-whatsapp text-success me-1"></i> WhatsApp Order Route & Custom Preset Message
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-forest-dark">Assigned WhatsApp Order Desk</label>
                                        <select name="whatsapp_number_select" id="addProdWaSelect" class="form-select form-select-sm" onchange="toggleCustomWaInput(this, 'addProdWaCustom')">
                                            <option value="">-- Use Default Platform Line (<?= e(get_primary_whatsapp_number()); ?>) --</option>
                                            <?php foreach ($waNumbersList as $waLine): ?>
                                                <option value="<?= e($waLine['phone']); ?>"><?= e($waLine['label']); ?> (<?= e($waLine['phone']); ?>)<?= !empty($waLine['is_default']) ? ' [Default]' : ''; ?></option>
                                            <?php endforeach; ?>
                                            <option value="custom">+ Enter Custom / Another WhatsApp Number</option>
                                        </select>
                                        <input type="text" name="whatsapp_number" id="addProdWaCustom" class="form-control form-control-sm font-monospace mt-2 d-none" placeholder="e.g. +91 98450 12345">
                                        <small class="text-muted extra-small">Customer "Order via WhatsApp" clicks will route to this number.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label small fw-bold text-forest-dark mb-0">Preset Customer WhatsApp Message</label>
                                            <button type="button" class="btn btn-link btn-xs text-forest text-decoration-none p-0" onclick="autoFillAddProdWaMsg()">
                                                <i class="bi bi-magic me-1"></i> Auto-Generate
                                            </button>
                                        </div>
                                        <textarea name="whatsapp_message" id="addProdWaMsg" class="form-control font-monospace small" rows="3" placeholder="Namaste Kamadenu Goushala! I would like to order this item. Please share payment and delivery details."></textarea>
                                        <small class="text-muted extra-small">Preset message received by admin when user taps WhatsApp order.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="addIsActive" checked>
                                <label class="form-check-label small fw-bold" for="addIsActive">Active in Store Catalog</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="addIsFeatured" checked>
                                <label class="form-check-label small fw-bold" for="addIsFeatured">Featured on Homepage Store</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-cream-soft border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold">Create Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-forest-dark text-white p-4">
                <h5 class="modal-title font-serif"><i class="bi bi-tag-fill text-gold me-2"></i> Add Product Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL; ?>/admin/products.php" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_category">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Category Name *</label>
                            <input type="text" name="category_name" class="form-control" placeholder="e.g. Ayurvedic Hair & Skin Care" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">URL Slug (Optional)</label>
                            <input type="text" name="category_slug" class="form-control" placeholder="e.g. ayurvedic-hair-care">
                            <small class="text-muted">Auto-generated from name if left empty.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="<?= count($categories) + 1; ?>" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Category Image (Optional - JPG, PNG, WEBP max 5MB)</label>
                            <input type="file" name="category_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description (Optional)</label>
                            <textarea name="category_description" class="form-control" rows="3" placeholder="Brief summary of products in this category..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-cream-soft border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-forest rounded-pill px-4 shadow-xs"><i class="bi bi-plus-circle me-1"></i> Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Manage Categories Modal -->
<div class="modal fade" id="manageCategoriesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-forest-dark text-white p-4">
                <h5 class="modal-title font-serif"><i class="bi bi-tags-fill text-gold me-2"></i> Manage Product Categories (<?= count($categories); ?>)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted small mb-0">Organize and configure your store product catalog groupings.</p>
                    <button type="button" class="btn btn-forest btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Category
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-cream-soft">
                            <tr>
                                <th>Category</th>
                                <th>Slug</th>
                                <th>Order</th>
                                <th>Products</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">No categories created yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): 
                                    $catImg = image_url($cat['image'] ?? null, 'products', 'placeholder-product.jpg');
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 38px; height: 38px; background: var(--color-forest-dark);">
                                                <img src="<?= e($catImg); ?>" alt="<?= e($cat['name']); ?>" class="w-100 h-100 object-fit-cover" onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-product.jpg';">
                                            </div>
                                            <div>
                                                <strong class="text-forest-dark d-block"><?= e($cat['name']); ?></strong>
                                                <?php if (!empty($cat['description'])): ?>
                                                    <small class="text-muted line-clamp-1"><?= e(mb_strimwidth($cat['description'], 0, 45, '...')); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><code class="small text-muted"><?= e($cat['slug']); ?></code></td>
                                    <td><span class="badge bg-light text-dark border"><?= (int)$cat['display_order']; ?></span></td>
                                    <td><span class="badge bg-cream text-forest fw-bold border"><?= (int)$cat['product_count']; ?> items</span></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-forest" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?= $cat['id']; ?>" title="Edit Category">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="<?= BASE_URL; ?>/admin/products.php" onsubmit="return confirm('Are you sure you want to delete category \'<?= e(addslashes($cat['name'])); ?>\'?');" class="d-inline">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete_category">
                                                <input type="hidden" name="category_id" value="<?= $cat['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Delete Category" <?= ((int)$cat['product_count'] > 0) ? 'disabled title="Cannot delete: category has products"' : ''; ?>>
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-cream-soft border-0 p-3">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modals -->
<?php foreach ($categories as $cat): 
    $catImg = image_url($cat['image'] ?? null, 'products', 'placeholder-product.jpg');
?>
<div class="modal fade" id="editCategoryModal<?= $cat['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-forest-dark text-white p-4">
                <h5 class="modal-title font-serif"><i class="bi bi-pencil-square text-gold me-2"></i> Edit Category: <?= e($cat['name']); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL; ?>/admin/products.php" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="edit_category">
                <input type="hidden" name="category_id" value="<?= $cat['id']; ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Category Name *</label>
                            <input type="text" name="category_name" class="form-control" value="<?= e($cat['name']); ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">URL Slug</label>
                            <input type="text" name="category_slug" class="form-control" value="<?= e($cat['slug']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="<?= (int)$cat['display_order']; ?>" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Category Photograph</label>
                            <div class="d-flex align-items-center gap-3 p-2 bg-cream-soft rounded-3 border">
                                <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 50px; height: 50px; background: var(--color-forest-dark);">
                                    <img src="<?= e($catImg); ?>" alt="<?= e($cat['name']); ?>" class="w-100 h-100 object-fit-cover" onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-product.jpg';">
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" name="category_image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                    <small class="text-muted">Leave blank to keep existing image.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="category_description" class="form-control" rows="3"><?= e($cat['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-cream-soft border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold"><i class="bi bi-check2 me-1"></i> Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Edit Product Modals -->
<?php if (!empty($products)): ?>
    <?php foreach ($products as $p): 
        $prodImg = image_url($p['main_image'] ?? null, 'products', 'placeholder-product.jpg');
    ?>
    <div class="modal fade" id="editProductModal<?= $p['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-forest-dark text-white p-4">
                    <h5 class="modal-title font-serif"><i class="bi bi-pencil-square text-gold me-2"></i> Edit Product: <?= e($p['name']); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?= BASE_URL; ?>/admin/products.php" enctype="multipart/form-data">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="edit_product">
                    <input type="hidden" name="product_id" value="<?= $p['id']; ?>">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Product Name *</label>
                                <input type="text" name="name" class="form-control" required value="<?= e($p['name']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">SKU Code *</label>
                                <input type="text" name="sku" class="form-control font-monospace" required value="<?= e($p['sku']); ?>">
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label small fw-bold mb-1">Product Category *</label>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#addCategoryModal" class="small text-forest text-decoration-none fw-bold"><i class="bi bi-plus"></i> New</a>
                                </div>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id']; ?>" <?= ($p['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?= e($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Packaging Unit *</label>
                                <input type="text" name="unit" class="form-control" required value="<?= e($p['unit']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Stock Quantity *</label>
                                <input type="number" name="stock_quantity" class="form-control" min="0" required value="<?= (int)$p['stock_quantity']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Standard Price (INR) *</label>
                                <input type="number" name="price" class="form-control" step="0.01" required value="<?= (float)$p['price']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Discounted Offer Price (INR)</label>
                                <input type="number" name="discount_price" class="form-control" step="0.01" value="<?= $p['discount_price'] !== null ? (float)$p['discount_price'] : ''; ?>">
                            </div>

                            <!-- Product Photograph -->
                            <div class="col-12">
                                <label class="form-label small fw-bold">Product Photograph (JPG, PNG, WEBP max 5MB)</label>
                                <div class="d-flex align-items-center gap-3 p-3 bg-cream-soft rounded-3 border">
                                    <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 70px; height: 70px; background: var(--color-forest-dark);">
                                        <img src="<?= e($prodImg); ?>" alt="<?= e($p['name']); ?>" class="w-100 h-100 object-fit-cover">
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                                        <small class="text-muted d-block mt-1">Leave empty to keep current photograph.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">URL Slug</label>
                                <input type="text" name="slug" class="form-control" value="<?= e($p['slug']); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Short Summary</label>
                                <input type="text" name="short_description" class="form-control" value="<?= e($p['short_description'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Detailed Description & Vedic Method</label>
                                <textarea name="description" class="form-control" rows="3"><?= e($p['description'] ?? ''); ?></textarea>
                            </div>

                            <!-- WhatsApp Order Routing & Preset Message Slab -->
                            <div class="col-12">
                                <div class="p-3 rounded-4 bg-cream-soft border border-success border-opacity-50">
                                    <h6 class="font-serif text-forest-dark mb-2">
                                        <i class="bi bi-whatsapp text-success me-1"></i> WhatsApp Order Route & Custom Preset Message
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-forest-dark">Assigned WhatsApp Order Desk</label>
                                            <?php
                                                $curWaNum = $p['whatsapp_number'] ?? '';
                                                $isMatchedInList = false;
                                                foreach ($waNumbersList as $waLine) {
                                                    if (!empty($curWaNum) && $curWaNum === $waLine['phone']) {
                                                        $isMatchedInList = true;
                                                        break;
                                                    }
                                                }
                                                $isCustom = (!empty($curWaNum) && !$isMatchedInList);
                                            ?>
                                            <select name="whatsapp_number_select" class="form-select form-select-sm" onchange="toggleCustomWaInput(this, 'editProdWaCustom<?= $p['id']; ?>')">
                                                <option value="" <?= empty($curWaNum) ? 'selected' : ''; ?>>-- Use Default Platform Line (<?= e(get_primary_whatsapp_number()); ?>) --</option>
                                                <?php foreach ($waNumbersList as $waLine): ?>
                                                    <option value="<?= e($waLine['phone']); ?>" <?= ($curWaNum === $waLine['phone']) ? 'selected' : ''; ?>><?= e($waLine['label']); ?> (<?= e($waLine['phone']); ?>)<?= !empty($waLine['is_default']) ? ' [Default]' : ''; ?></option>
                                                <?php endforeach; ?>
                                                <option value="custom" <?= $isCustom ? 'selected' : ''; ?>>+ Enter Custom / Another WhatsApp Number</option>
                                            </select>
                                            <input type="text" name="whatsapp_number" id="editProdWaCustom<?= $p['id']; ?>" class="form-control form-control-sm font-monospace mt-2 <?= $isCustom ? '' : 'd-none'; ?>" placeholder="e.g. +91 98450 12345" value="<?= e($curWaNum); ?>">
                                            <small class="text-muted extra-small">Customer "Order via WhatsApp" clicks will route to this number.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label small fw-bold text-forest-dark mb-0">Preset Customer WhatsApp Message</label>
                                                <button type="button" class="btn btn-link btn-xs text-forest text-decoration-none p-0" onclick="autoFillEditProdWaMsg(<?= $p['id']; ?>)">
                                                    <i class="bi bi-magic me-1"></i> Auto-Generate
                                                </button>
                                            </div>
                                            <textarea name="whatsapp_message" id="editProdWaMsg<?= $p['id']; ?>" class="form-control font-monospace small" rows="3" placeholder="Namaste Kamadenu Goushala! I would like to order this item. Please share payment and delivery details."><?= e($p['whatsapp_message'] ?? ''); ?></textarea>
                                            <small class="text-muted extra-small">Preset message received by admin when user taps WhatsApp order.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive<?= $p['id']; ?>" <?= $p['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label small fw-bold" for="isActive<?= $p['id']; ?>">Active in Store Catalog</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="isFeatured<?= $p['id']; ?>" <?= $p['is_featured'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label small fw-bold" for="isFeatured<?= $p['id']; ?>">Featured on Homepage Store</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-cream-soft border-0 p-3">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold"><i class="bi bi-check2 me-1"></i> Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- WhatsApp Message Editing & Share Modals for Each Product -->
<?php if (!empty($products)): ?>
    <?php foreach ($products as $p): 
        $prodImg = image_url($p['main_image'] ?? null, 'products', 'placeholder-product.jpg');
        $prodPublicUrl = BASE_URL . '/product-details.php?slug=' . urlencode($p['slug']);
        $priceFormatted = format_inr((float)$p['price']);
        $discPriceFormatted = $p['discount_price'] ? format_inr((float)$p['discount_price']) : '';
        $effectivePrice = $p['discount_price'] ? ($discPriceFormatted . " (Offer Price, Regular MRP: " . $priceFormatted . ")") : $priceFormatted;
        $sitePhone = get_setting('site_phone', '+91 98450 12345');

        $defaultProdMsg = "🙏 *Namaste from Kamadenu Goushala!*\n\n" .
                          "Sacred & Organic Sanctuary Product:\n" .
                          "🌿 *Product:* " . $p['name'] . "\n" .
                          "🏷️ *Category:* " . $p['category_name'] . " (" . $p['unit'] . ")\n" .
                          "💰 *Price:* " . $effectivePrice . "\n" .
                          "📦 *SKU:* " . $p['sku'] . "\n\n" .
                          "✨ *Highlights:* " . mb_strimwidth($p['short_description'] ?: ($p['description'] ?: 'Pure, authentic, cruelty-free Vedic product produced directly at our Nandi Hills sanctuary.'), 0, 140, '...') . "\n\n" .
                          "🛒 *Order Online & Fast Delivery:*\n" .
                          "🔗 " . $prodPublicUrl . "\n\n" .
                          "📞 *Order Helpline / WhatsApp:* " . $sitePhone . "\n" .
                          "🙏 *All proceeds directly support fodder & care for our resident rescued cows.*";
    ?>
    <div class="modal fade" id="whatsappProductModal<?= $p['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-forest-dark text-white p-4">
                    <h5 class="modal-title font-serif">
                        <i class="bi bi-whatsapp text-success me-2 fs-5"></i> WhatsApp Product Share: <?= e($p['name']); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <!-- Product Info Preview Strip -->
                    <div class="d-flex align-items-center gap-3 p-3 bg-cream-soft rounded-3 border mb-3">
                        <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 55px; height: 55px; background: var(--color-forest-dark);">
                            <img src="<?= e($prodImg); ?>" alt="<?= e($p['name']); ?>" class="w-100 h-100 object-fit-cover" onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-product.jpg';">
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <h6 class="font-serif text-forest-dark mb-0 fw-bold"><?= e($p['name']); ?></h6>
                                    <small class="text-muted"><?= e($p['category_name']); ?> &bull; <?= e($p['unit']); ?> &bull; <code><?= e($p['sku']); ?></code></small>
                                </div>
                                <div>
                                    <span class="badge bg-gold text-forest-dark fw-bold"><?= format_inr((float)($p['discount_price'] ?: $p['price'])); ?></span>
                                    <span class="badge bg-success-subtle text-success border ms-1"><?= $p['stock_quantity'] > 0 ? ($p['stock_quantity'] . ' in Stock') : 'Out of Stock'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recipient & Preset Controls -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-forest-dark">Recipient Customer WhatsApp (Optional)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-success"><i class="bi bi-whatsapp"></i></span>
                                <input type="text" id="waProdRecipientPhone<?= $p['id']; ?>" class="form-control font-monospace" placeholder="e.g. 919845012345 (Optional)" oninput="updateProdWaLink(<?= $p['id']; ?>)">
                            </div>
                            <small class="text-muted extra-small">Leave empty to choose recipient or devotee group inside WhatsApp.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-forest-dark">Message Templates / Presets</label>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyProdWaPreset(<?= $p['id']; ?>, 'catalog', <?= json_encode($p['name']); ?>, <?= json_encode($p['sku']); ?>, <?= json_encode($p['category_name']); ?>, <?= json_encode($p['unit']); ?>, <?= json_encode($priceFormatted); ?>, <?= json_encode($discPriceFormatted); ?>, <?= json_encode($prodPublicUrl); ?>, <?= json_encode($sitePhone); ?>)">Catalog Share</button>
                                <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyProdWaPreset(<?= $p['id']; ?>, 'offer', <?= json_encode($p['name']); ?>, <?= json_encode($p['sku']); ?>, <?= json_encode($p['category_name']); ?>, <?= json_encode($p['unit']); ?>, <?= json_encode($priceFormatted); ?>, <?= json_encode($discPriceFormatted); ?>, <?= json_encode($prodPublicUrl); ?>, <?= json_encode($sitePhone); ?>)">Special Offer</button>
                                <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyProdWaPreset(<?= $p['id']; ?>, 'bulk', <?= json_encode($p['name']); ?>, <?= json_encode($p['sku']); ?>, <?= json_encode($p['category_name']); ?>, <?= json_encode($p['unit']); ?>, <?= json_encode($priceFormatted); ?>, <?= json_encode($discPriceFormatted); ?>, <?= json_encode($prodPublicUrl); ?>, <?= json_encode($sitePhone); ?>)">Bulk Order</button>
                                <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyProdWaPreset(<?= $p['id']; ?>, 'benefits', <?= json_encode($p['name']); ?>, <?= json_encode($p['sku']); ?>, <?= json_encode($p['category_name']); ?>, <?= json_encode($p['unit']); ?>, <?= json_encode($priceFormatted); ?>, <?= json_encode($discPriceFormatted); ?>, <?= json_encode($prodPublicUrl); ?>, <?= json_encode($sitePhone); ?>)">Purity & Benefits</button>
                            </div>
                        </div>
                    </div>

                    <!-- Editable WhatsApp Message Body -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-forest-dark mb-0">Customizable WhatsApp Message Text</label>
                            <button type="button" class="btn btn-link btn-xs text-forest text-decoration-none p-0 fw-semibold" onclick="copyProdWaText(<?= $p['id']; ?>)">
                                <i class="bi bi-clipboard me-1"></i> Copy to Clipboard
                            </button>
                        </div>
                        <textarea id="waProdMessageText<?= $p['id']; ?>" class="form-control font-monospace small" rows="9" oninput="updateProdWaLink(<?= $p['id']; ?>)"><?= e($defaultProdMsg); ?></textarea>
                        <small class="text-muted extra-small">You can edit the message, add customer greeting, custom discount, or delivery instructions before sending.</small>
                    </div>

                </div>
                <div class="modal-footer bg-cream-soft border-0 p-3 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success rounded-pill px-3" onclick="copyProdWaText(<?= $p['id']; ?>)">
                            <i class="bi bi-clipboard me-1"></i> Copy Text
                        </button>
                        <a id="waProdSendBtn<?= $p['id']; ?>" href="https://api.whatsapp.com/send?text=<?= rawurlencode($defaultProdMsg); ?>" target="_blank" rel="noopener" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold">
                            <i class="bi bi-whatsapp me-1"></i> Send on WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- JavaScript for Dynamic Product WhatsApp Link & Message Editor -->
<script>
function updateProdWaLink(prodId) {
    const textEl = document.getElementById('waProdMessageText' + prodId);
    const phoneEl = document.getElementById('waProdRecipientPhone' + prodId);
    const sendBtn = document.getElementById('waProdSendBtn' + prodId);
    
    if (!textEl || !sendBtn) return;
    
    const message = encodeURIComponent(textEl.value);
    const rawPhone = phoneEl ? phoneEl.value.replace(/\D/g, '') : '';
    
    if (rawPhone.length > 0) {
        sendBtn.href = 'https://wa.me/' + rawPhone + '?text=' + message;
    } else {
        sendBtn.href = 'https://api.whatsapp.com/send?text=' + message;
    }
}

function copyProdWaText(prodId) {
    const textEl = document.getElementById('waProdMessageText' + prodId);
    if (!textEl) return;
    navigator.clipboard.writeText(textEl.value).then(() => {
        showToast('Product WhatsApp message copied to clipboard!', 'success');
    }).catch(() => {
        textEl.select();
        document.execCommand('copy');
        showToast('Product WhatsApp message copied to clipboard!', 'success');
    });
}

function applyProdWaPreset(prodId, preset, name, sku, category, unit, price, discPrice, url, phone) {
    const textEl = document.getElementById('waProdMessageText' + prodId);
    if (!textEl) return;
    
    const priceText = discPrice ? (discPrice + " (Special Offer Price, MRP: " + price + ")") : price;
    let msg = '';
    
    if (preset === 'offer') {
        msg = "🔥 *SPECIAL SANCTUARY OFFER: " + name + "* 🔥\n\n" +
              "Blessed greetings from Kamadenu Goushala!\n\n" +
              "Grab our pure, authentic " + category + " at a special seva discount:\n" +
              "🌿 *Product:* " + name + "\n" +
              "📦 *Unit:* " + unit + " (" + sku + ")\n" +
              "💰 *Special Price:* " + priceText + "\n\n" +
              "✨ 100% Traditional Vedic preparation with zero chemicals or additives.\n" +
              "🛒 *Order Now with Fast Delivery:*\n" +
              "🔗 " + url + "\n\n" +
              "📞 *Direct WhatsApp Orders:* " + phone + "\n" +
              "🙏 *Jai Gau Mata!*";
    } else if (preset === 'bulk') {
        msg = "📦 *Wholesale & Bulk Seva Inquiry: " + name + "*\n\n" +
              "Namaste! We supply pure Vedic Goushala products for Temples, Ashrams, and Ayurvedic practitioners:\n\n" +
              "🌿 *Item:* " + name + " (" + unit + ")\n" +
              "🏷️ *Category:* " + category + "\n" +
              "💰 *Standard Price:* " + priceText + "\n\n" +
              "For bulk carton discounts and direct sanctuary delivery, connect with our Seva Desk:\n" +
              "🔗 *View Product:* " + url + "\n" +
              "📞 *Helpline / WhatsApp:* " + phone + "\n\n" +
              "🙏 Blessed Seva!";
    } else if (preset === 'benefits') {
        msg = "✨ *Ayurvedic Health Purity: " + name + "*\n\n" +
              "Experience the sacred wellness of authentic Gau Mata products:\n" +
              "🌿 *Product:* " + name + " (" + unit + ")\n" +
              "🌾 *Origin:* Prepared traditionally using pure Desi cow ingredients at Nandi Hills Sanctuary.\n" +
              "💰 *Price:* " + priceText + "\n\n" +
              "Key Benefits: Non-GMO, chemical-free, nutrient-dense, and ethically sourced from protected cows.\n\n" +
              "🛒 *Order Genuine Sanctuary Products:*\n" +
              "🔗 " + url + "\n" +
              "📞 *Seva Helpline:* " + phone + "\n\n" +
              "🙏 *Jai Gau Mata!*";
    } else {
        msg = "🙏 *Namaste from Kamadenu Goushala!*\n\n" +
              "Sacred & Organic Sanctuary Product:\n" +
              "🌿 *Product:* " + name + "\n" +
              "🏷️ *Category:* " + category + " (" + unit + ")\n" +
              "💰 *Price:* " + priceText + "\n" +
              "📦 *SKU:* " + sku + "\n\n" +
              "🛒 *Order Online & Fast Delivery:*\n" +
              "🔗 " + url + "\n\n" +
              "📞 *Order Helpline / WhatsApp:* " + phone + "\n" +
              "🙏 *All proceeds directly support fodder & care for our resident rescued cows.*";
    }
    
    textEl.value = msg;
    updateProdWaLink(prodId);
    showToast('Product template preset applied. You can edit the text before sending.', 'info');
}

function toggleCustomWaInput(selectEl, customInputId) {
    const customInput = document.getElementById(customInputId);
    if (!customInput) return;
    if (selectEl.value === 'custom') {
        customInput.classList.remove('d-none');
        customInput.focus();
    } else {
        customInput.classList.add('d-none');
        if (selectEl.value !== '') {
            customInput.value = selectEl.value;
        }
    }
}

function autoFillAddProdWaMsg() {
    const modal = document.getElementById('addProductModal');
    if (!modal) return;
    const name = modal.querySelector('input[name="name"]').value || 'Organic Product';
    const unit = modal.querySelector('input[name="unit"]').value || 'Standard Pack';
    const price = modal.querySelector('input[name="price"]').value || '0';
    const discPrice = modal.querySelector('input[name="discount_price"]').value;
    const finalPrice = (discPrice && parseFloat(discPrice) > 0) ? ('₹ ' + discPrice) : ('₹ ' + price);
    
    const msg = "🙏 *Namaste Kamadenu Goushala!*\n\nI would like to order *" + name + "* (" + unit + ") priced at " + finalPrice + ".\n\nPlease share payment instructions and home delivery schedule.";
    const targetMsg = document.getElementById('addProdWaMsg');
    if (targetMsg) {
        targetMsg.value = msg;
        showToast('WhatsApp preset message generated!', 'info');
    }
}

function autoFillEditProdWaMsg(prodId) {
    const modal = document.getElementById('editProductModal' + prodId);
    if (!modal) return;
    const name = modal.querySelector('input[name="name"]').value || 'Organic Product';
    const unit = modal.querySelector('input[name="unit"]').value || 'Standard Pack';
    const price = modal.querySelector('input[name="price"]').value || '0';
    const discPrice = modal.querySelector('input[name="discount_price"]').value;
    const finalPrice = (discPrice && parseFloat(discPrice) > 0) ? ('₹ ' + discPrice) : ('₹ ' + price);
    
    const msg = "🙏 *Namaste Kamadenu Goushala!*\n\nI would like to order *" + name + "* (" + unit + ") priced at " + finalPrice + ".\n\nPlease share payment instructions and home delivery schedule.";
    const msgEl = document.getElementById('editProdWaMsg' + prodId);
    if (msgEl) {
        msgEl.value = msg;
        showToast('WhatsApp preset message generated!', 'info');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
