<?php
/**
 * Kamadenu Goushala Platform - AJAX Store & Shopping Cart Handler
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
}

verify_csrf_or_die();

$action = sanitize_input($_POST['action'] ?? 'add');
$productId = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

try {
    switch ($action) {
        case 'add':
            if ($productId <= 0) {
                json_response(['success' => false, 'message' => 'Invalid product ID.'], 400);
            }
            $product = Database::fetchOne("SELECT name FROM products WHERE id = ? AND is_active = 1", [$productId]);
            if (!$product) {
                json_response(['success' => false, 'message' => 'Product is not available.'], 404);
            }

            $cart = Order::addToCart($productId, $quantity);
            json_response([
                'success' => true,
                'message' => 'Added ' . $product['name'] . ' to your cart!',
                'cart'    => $cart
            ]);
            break;

        case 'update':
            if ($productId <= 0) {
                json_response(['success' => false, 'message' => 'Invalid product ID.'], 400);
            }
            $cart = Order::updateQuantity($productId, $quantity);
            json_response([
                'success' => true,
                'message' => 'Cart updated successfully.',
                'cart'    => $cart
            ]);
            break;

        case 'remove':
            if ($productId <= 0) {
                json_response(['success' => false, 'message' => 'Invalid product ID.'], 400);
            }
            $cart = Order::removeFromCart($productId);
            json_response([
                'success' => true,
                'message' => 'Item removed from cart.',
                'cart'    => $cart
            ]);
            break;

        case 'clear':
            Order::clearCart();
            json_response([
                'success' => true,
                'message' => 'Cart cleared.',
                'cart'    => Order::getCart()
            ]);
            break;

        default:
            json_response(['success' => false, 'message' => 'Unknown cart action.'], 400);
    }
} catch (Throwable $t) {
    error_log('Cart AJAX error: ' . $t->getMessage());
    json_response(['success' => false, 'message' => 'Failed to process cart action.'], 500);
}
