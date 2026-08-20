<?php
/**
 * Kamadenu Goushala Platform - Cow Breed Model & Data Access
 */

declare(strict_types=1);

class Breed {
    /**
     * Get all breeds with associated resident cow count.
     */
    public static function getAllWithCount(): array {
        $sql = "
            SELECT b.*, COUNT(c.id) AS cow_count
            FROM cow_breeds b
            LEFT JOIN cows c ON b.id = c.breed_id AND c.status != 'deceased'
            GROUP BY b.id, b.name, b.slug, b.origin_region, b.characteristics, b.image, b.description
            ORDER BY b.name ASC
        ";
        return Database::fetchAll($sql);
    }

    /**
     * Find breed by slug with list of active resident cows.
     */
    public static function findBySlug(string $slug): ?array {
        $sql = "SELECT * FROM cow_breeds WHERE slug = ?";
        $breed = Database::fetchOne($sql, [$slug]);
        if (!$breed) {
            return null;
        }

        // Fetch cows belonging to this breed
        $breed['cows'] = Database::fetchAll("
            SELECT c.*, b.name AS breed_name 
            FROM cows c 
            JOIN cow_breeds b ON c.breed_id = b.id 
            WHERE c.breed_id = ? AND c.status != 'deceased'
            ORDER BY c.is_featured DESC, c.id ASC
        ", [$breed['id']]);

        return $breed;
    }
}
