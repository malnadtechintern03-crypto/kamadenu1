<?php
/**
 * Kamadenu Goushala Platform - Order Model & Cart Session Manager
 */

declare(strict_types=1);

class Order {
    /**
     * Get active cart items and calculate financial totals.
     */
    public static function getCart(): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $cart = $_SESSION['cart'] ?? [];
        $items = [];
        $subtotal = 0.0;
        $totalItemsCount = 0;

        foreach ($cart as $productId => $qty) {
            $productId = (int)$productId;
            $qty = max(1, (int)$qty);

            $product = Database::fetchOne("
                SELECT p.*, pc.name AS category_name 
                FROM products p 
                JOIN product_categories pc ON p.category_id = pc.id 
                WHERE p.id = ? AND p.is_active = 1
            ", [$productId]);

            if ($product) {
                $effectivePrice = (float)($product['discount_price'] ?? $product['price']);
                $lineTotal = $effectivePrice * $qty;
                $subtotal += $lineTotal;
                $totalItemsCount += $qty;

                $items[] = [
                    'product_id'      => $product['id'],
                    'name'            => $product['name'],
                    'slug'            => $product['slug'],
                    'sku'             => $product['sku'],
                    'unit'            => $product['unit'],
                    'price'           => (float)$product['price'],
                    'discount_price'  => (float)($product['discount_price'] ?? 0),
                    'effective_price' => $effectivePrice,
                    'quantity'        => $qty,
                    'stock_quantity'  => (int)$product['stock_quantity'],
                    'line_total'      => $lineTotal,
                    'category_name'   => $product['category_name']
                ];
            }
        }

        // Free shipping on orders above ₹ 999
        $shipping = ($subtotal >= 999 || $subtotal === 0.0) ? 0.0 : 99.0;
        $grandTotal = $subtotal + $shipping;

        return [
            'items'       => $items,
            'count'       => $totalItemsCount,
            'subtotal'    => $subtotal,
            'shipping'    => $shipping,
            'grand_total' => $grandTotal
        ];
    }

    /**
     * Add or update product quantity in session cart.
     */
    public static function addToCart(int $productId, int $quantity = 1): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $quantity = max(1, $quantity);
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }

        return self::getCart();
    }

    /**
     * Update exact quantity of a product in session cart.
     */
    public static function updateQuantity(int $productId, int $quantity): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }

        return self::getCart();
    }

    /**
     * Remove product from session cart.
     */
    public static function removeFromCart(int $productId): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION['cart'][$productId]);
        return self::getCart();
    }

    /**
     * Clear entire cart.
     */
    public static function clearCart(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['cart']);
    }

    /**
     * Process and place customer order from cart.
     */
    public static function placeOrder(array $customerData, array $shippingData, string $paymentMethod = 'razorpay'): string {
        $cart = self::getCart();
        if (empty($cart['items'])) {
            throw new RuntimeException('Cannot place an order with an empty shopping cart.');
        }

        Database::beginTransaction();
        try {
            // 1. Create or Find Customer
            $existingCustomer = Database::fetchOne("SELECT id FROM customers WHERE email = ?", [$customerData['email']]);
            if ($existingCustomer) {
                $customerId = (int)$existingCustomer['id'];
                Database::execute("UPDATE customers SET name = ?, phone = ? WHERE id = ?", [
                    $customerData['name'],
                    $customerData['phone'],
                    $customerId
                ]);
            } else {
                $customerId = Database::insert("
                    INSERT INTO customers (name, email, phone)
                    VALUES (?, ?, ?)
                ", [
                    $customerData['name'],
                    $customerData['email'],
                    $customerData['phone']
                ]);
            }

            // 2. Insert Shipping Address
            $addressId = Database::insert("
                INSERT INTO addresses (customer_id, address_line1, address_line2, city, state, pincode, landmark)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [
                $customerId,
                $shippingData['address_line1'],
                $shippingData['address_line2'] ?? null,
                $shippingData['city'],
                $shippingData['state'],
                $shippingData['pincode'],
                $shippingData['landmark'] ?? null
            ]);

            // 3. Create Master Order Record
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $orderId = Database::insert("
                INSERT INTO orders (
                    order_number, customer_id, shipping_address_id, subtotal,
                    shipping_charge, total_amount, payment_status, order_status, customer_notes
                ) VALUES (?, ?, ?, ?, ?, ?, 'paid', 'placed', ?)
            ", [
                $orderNumber,
                $customerId,
                $addressId,
                $cart['subtotal'],
                $cart['shipping'],
                $cart['grand_total'],
                $customerData['notes'] ?? null
            ]);

            // 4. Insert Order Items & Decrement Stock
            foreach ($cart['items'] as $item) {
                Database::insert("
                    INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, total_price)
                    VALUES (?, ?, ?, ?, ?, ?)
                ", [
                    $orderId,
                    $item['product_id'],
                    $item['name'],
                    $item['effective_price'],
                    $item['quantity'],
                    $item['line_total']
                ]);

                // Decrement inventory stock safely
                Database::execute("
                    UPDATE products 
                    SET stock_quantity = GREATEST(0, stock_quantity - ?) 
                    WHERE id = ?
                ", [$item['quantity'], $item['product_id']]);
            }

            // 5. Record Payment Transaction
            $txnId = 'TXN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            Database::insert("
                INSERT INTO payments (transaction_id, reference_type, reference_id, gateway, amount, status, paid_at)
                VALUES (?, 'order', ?, ?, ?, 'captured', NOW())
            ", [
                $txnId,
                $orderId,
                $paymentMethod,
                $cart['grand_total']
            ]);

            Database::commit();

            // Clear session cart
            self::clearCart();

            return $orderNumber;

        } catch (Throwable $t) {
            Database::rollBack();
            error_log('Order placement error: ' . $t->getMessage());
            throw $t;
        }
    }

    /**
     * Fetch complete order details and item list by order number.
     */
    public static function findByOrderNumber(string $orderNumber): ?array {
        $sql = "
            SELECT o.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
                   a.address_line1, a.address_line2, a.city, a.state, a.pincode, a.landmark,
                   p.gateway, p.transaction_id
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            LEFT JOIN addresses a ON o.shipping_address_id = a.id
            LEFT JOIN payments p ON p.reference_type = 'order' AND p.reference_id = o.id
            WHERE o.order_number = ?
        ";
        $order = Database::fetchOne($sql, [$orderNumber]);
        if (!$order) {
            return null;
        }

        $order['items'] = Database::fetchAll("
            SELECT oi.*, p.slug AS product_slug, p.sku, p.unit 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ", [$order['id']]);

        return $order;
    }
}
