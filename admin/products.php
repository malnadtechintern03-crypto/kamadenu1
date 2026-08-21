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
                stock_quantity, short_description, description, main_image, is_active, is_featured, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ", [$categoryId, $name, $slug, $sku, $unit, $price, $discountPrice, $stock, $shortDesc, $desc, $finalImage, $isActive, $isFeatured]);

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
                stock_quantity = ?, unit = ?, main_image = ?, is_featured = ?, is_active = ?, 
                updated_at = NOW()
            WHERE id = ?
        ", [
            $categoryId, $sku, $name, $slug, 
            $shortDesc, $desc, $price, $discountPrice, 
            $stock, $unit, $finalImage, $isFeatured, $isActive, 
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

$products = Database::fetchAll("
    SELECT p.*, pc.name AS category_name 
    FROM products p 
    JOIN product_categories pc ON p.category_id = pc.id 
    ORDER BY p.id DESC
");

$categories = Database::fetchAll("SELECT * FROM product_categories ORDER BY name ASC");

$pageTitle = 'Organic Products Store Inventory';

require_once __DIR__ . '/includes/header.php';
?>

<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h4 font-serif text-forest-dark mb-0">Organic Store Products & Inventory (<?= count($products); ?>)</h1>
            <small class="text-muted">Manage Vedic A2 Ghee, herbal cosmetics, and sanctuary products.</small>
        </div>
        <button type="button" class="btn btn-gold rounded-pill px-4 shadow-gold" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-circle me-1"></i> Add New Product
        </button>
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
                            <div class="d-inline-flex align-items-center gap-2">
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
                            <label class="form-label small fw-bold">Product Category *</label>
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
                                <label class="form-label small fw-bold">Product Category *</label>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
