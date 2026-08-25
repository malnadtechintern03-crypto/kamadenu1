<?php
/**
 * Kamadenu Goushala Platform - Product Model & Store Catalog Data Access
 */

declare(strict_types=1);

class Product {
    /**
     * Get paginated and filtered list of active products.
     */
    public static function getAll(array $filters = [], int $page = 1, int $perPage = 12): array {
        $where = ["p.is_active = 1"];
        $params = [];

        if (!empty($filters['category'])) {
            if (is_numeric($filters['category'])) {
                $where[] = "p.category_id = ?";
                $params[] = (int)$filters['category'];
            } else {
                $where[] = "pc.slug = ?";
                $params[] = $filters['category'];
            }
        }

        if (!empty($filters['search'])) {
            $where[] = "(p.name LIKE ? OR p.short_description LIKE ? OR p.sku LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['ordered'])) {
            $where[] = "(SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id AND o.order_status != 'cancelled') > 0";
        }

        $whereClause = implode(' AND ', $where);

        $orderBy = match($filters['sort'] ?? 'featured') {
            'price_low'    => 'COALESCE(p.discount_price, p.price) ASC',
            'price_high'   => 'COALESCE(p.discount_price, p.price) DESC',
            'name_asc'     => 'p.name ASC',
            'most_ordered' => 'total_orders_count DESC, total_ordered_qty DESC, p.id DESC',
            default        => 'p.is_featured DESC, p.id ASC'
        };

        // Count total
        $countSql = "
            SELECT COUNT(*) 
            FROM products p 
            JOIN product_categories pc ON p.category_id = pc.id 
            WHERE {$whereClause}
        ";
        $total = (int)Database::fetchColumn($countSql, $params);

        // Fetch paginated
        $offset = max(0, ($page - 1) * $perPage);
        $sql = "
            SELECT p.*, pc.name AS category_name, pc.slug AS category_slug,
                   (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id AND o.order_status != 'cancelled') AS total_ordered_qty,
                   (SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id AND o.order_status != 'cancelled') AS total_orders_count
            FROM products p 
            JOIN product_categories pc ON p.category_id = pc.id 
            WHERE {$whereClause} 
            ORDER BY {$orderBy} 
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $items = Database::fetchAll($sql, $params);

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int)ceil($total / $perPage)
        ];
    }

    /**
     * Get complete product profile by slug or ID with image gallery.
     */
    public static function findBySlug(string $slug): ?array {
        $sql = "
            SELECT p.*, pc.name AS category_name, pc.slug AS category_slug,
                   (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id AND o.order_status != 'cancelled') AS total_ordered_qty,
                   (SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id AND o.order_status != 'cancelled') AS total_orders_count
            FROM products p 
            JOIN product_categories pc ON p.category_id = pc.id 
            WHERE p.slug = ? OR p.sku = ?
        ";
        $product = Database::fetchOne($sql, [$slug, $slug]);
        if (!$product) {
            return null;
        }

        // Fetch gallery images
        $product['images'] = Database::fetchAll("SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order ASC", [$product['id']]);

        // Fetch related products
        $product['related'] = Database::fetchAll("
            SELECT p.*, pc.name AS category_name,
                   (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id AND o.order_status != 'cancelled') AS total_ordered_qty,
                   (SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id AND o.order_status != 'cancelled') AS total_orders_count
            FROM products p 
            JOIN product_categories pc ON p.category_id = pc.id 
            WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 
            ORDER BY p.is_featured DESC, p.id ASC 
            LIMIT 4
        ", [$product['category_id'], $product['id']]);

        return $product;
    }

    /**
     * Get recent product orders for social proof and transparency stream.
     */
    public static function getRecentlyOrdered(int $limit = 4): array {
        $sql = "
            SELECT oi.product_id, oi.product_name, oi.quantity, oi.unit_price, o.order_number, o.created_at,
                   p.slug, p.main_image, p.unit, pc.name AS category_name
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            JOIN product_categories pc ON p.category_id = pc.id
            WHERE o.order_status != 'cancelled'
            ORDER BY o.created_at DESC
            LIMIT ?
        ";
        return Database::fetchAll($sql, [$limit]);
    }

    /**
     * Get all product categories with active product count.
     */
    public static function getCategories(): array {
        $sql = "
            SELECT pc.*, COUNT(p.id) AS product_count 
            FROM product_categories pc 
            LEFT JOIN products p ON pc.id = p.category_id AND p.is_active = 1 
            GROUP BY pc.id, pc.name, pc.slug, pc.description, pc.image, pc.display_order 
            ORDER BY pc.display_order ASC, pc.name ASC
        ";
        return Database::fetchAll($sql);
    }
}
