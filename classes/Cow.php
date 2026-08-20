<?php
/**
 * Kamadenu Goushala Platform - Cow Model & Data Access
 */

declare(strict_types=1);

class Cow {
    /**
     * Get paginated and multi-filtered list of cows.
     */
    public static function getAll(array $filters = [], int $page = 1, int $perPage = 12): array {
        $where = ["c.status != 'deceased'"];
        $params = [];

        if (!empty($filters['breed'])) {
            if (is_numeric($filters['breed'])) {
                $where[] = "c.breed_id = ?";
                $params[] = (int)$filters['breed'];
            } else {
                $where[] = "b.slug = ?";
                $params[] = $filters['breed'];
            }
        }

        if (!empty($filters['gender'])) {
            $where[] = "c.gender = ?";
            $params[] = $filters['gender'];
        }

        if (!empty($filters['health_status'])) {
            $where[] = "c.health_status = ?";
            $params[] = $filters['health_status'];
        }

        if (!empty($filters['status'])) {
            $where[] = "c.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(c.name LIKE ? OR c.cow_code LIKE ? OR c.rescue_story LIKE ? OR c.description LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $whereClause = implode(' AND ', $where);

        // Sorting
        $orderBy = match($filters['sort'] ?? 'featured') {
            'newest'     => 'c.id DESC',
            'oldest'     => 'c.id ASC',
            'name_asc'   => 'c.name ASC',
            'rescue_asc' => 'c.rescue_date ASC',
            default      => 'c.is_featured DESC, c.id DESC'
        };
        
        // Count total matching
        $countSql = "
            SELECT COUNT(*) 
            FROM cows c 
            JOIN cow_breeds b ON c.breed_id = b.id 
            WHERE {$whereClause}
        ";
        $total = (int)Database::fetchColumn($countSql, $params);

        // Fetch paginated records
        $offset = max(0, ($page - 1) * $perPage);
        $sql = "
            SELECT c.*, b.name AS breed_name, b.slug AS breed_slug 
            FROM cows c 
            JOIN cow_breeds b ON c.breed_id = b.id 
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
     * Get complete cow profile by slug or ID with related entities.
     */
    public static function findBySlug(string $slug): ?array {
        $sql = "
            SELECT c.*, b.name AS breed_name, b.slug AS breed_slug, b.origin_region AS breed_origin, b.characteristics AS breed_characteristics 
            FROM cows c 
            JOIN cow_breeds b ON c.breed_id = b.id 
            WHERE c.slug = ? OR c.cow_code = ?
        ";
        $cow = Database::fetchOne($sql, [$slug, $slug]);
        if (!$cow) {
            return null;
        }

        // Fetch gallery images
        $cow['images'] = Database::fetchAll("SELECT * FROM cow_images WHERE cow_id = ? ORDER BY is_primary DESC, id ASC", [$cow['id']]);
        
        // Fetch medical records
        $cow['medical_records'] = Database::fetchAll("SELECT * FROM cow_medical_records WHERE cow_id = ? ORDER BY visit_date DESC", [$cow['id']]);

        // Fetch vaccinations
        $cow['vaccinations'] = Database::fetchAll("SELECT * FROM cow_vaccinations WHERE cow_id = ? ORDER BY vaccination_date DESC", [$cow['id']]);

        // Fetch caretaker notes
        $cow['notes'] = Database::fetchAll("
            SELECT cn.*, u.name AS recorded_by_name
            FROM cow_notes cn
            LEFT JOIN users u ON cn.user_id = u.id
            WHERE cn.cow_id = ?
            ORDER BY cn.note_date DESC
        ", [$cow['id']]);

        // Fetch active adoption or sponsor count
        $cow['is_adopted'] = (bool)Database::fetchColumn("SELECT COUNT(*) FROM adoptions WHERE cow_id = ? AND status = 'active'", [$cow['id']]);
        $cow['sponsors_count'] = (int)Database::fetchColumn("SELECT COUNT(*) FROM sponsors WHERE cow_id = ? AND status = 'active'", [$cow['id']]);

        // Fetch related cows of same breed
        $cow['related_cows'] = Database::fetchAll("
            SELECT c.*, b.name AS breed_name 
            FROM cows c 
            JOIN cow_breeds b ON c.breed_id = b.id 
            WHERE c.breed_id = ? AND c.id != ? AND c.status != 'deceased'
            ORDER BY c.is_featured DESC, c.id DESC 
            LIMIT 4
        ", [$cow['breed_id'], $cow['id']]);

        return $cow;
    }
}
