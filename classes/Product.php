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

        $whereClause = implode(' AND ', $where);

        $orderBy = match($filters['sort'] ?? 'featured') {
            'price_low'  => 'COALESCE(p.discount_price, p.price) ASC',
            'price_high' => 'COALESCE(p.discount_price, p.price) DESC',
            'name_asc'   => 'p.name ASC',
            default      => 'p.is_featured DESC, p.id ASC'
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
            SELECT p.*, pc.name AS category_name, pc.slug AS category_slug 
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
            SELECT p.*, pc.name AS category_name, pc.slug AS category_slug 
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
            SELECT p.*, pc.name AS category_name 
            FROM products p 
            JOIN product_categories pc ON p.category_id = pc.id 
            WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 
            ORDER BY p.is_featured DESC, p.id ASC 
            LIMIT 4
        ", [$product['category_id'], $product['id']]);

        return $product;
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
