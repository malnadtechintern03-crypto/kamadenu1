<?php
/**
 * Kamadenu Goushala Platform - AJAX Gallery Loader
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $categorySlug = isset($_GET['category']) && $_GET['category'] !== 'all' ? sanitize_input($_GET['category']) : null;
    $limit = isset($_GET['limit']) ? max(1, min(36, (int)$_GET['limit'])) : 18;

    $where = [];
    $params = [];

    if ($categorySlug) {
        $where[] = "gc.slug = ?";
        $params[] = $categorySlug;
    }

    $whereClause = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sql = "
        SELECT g.*, gc.name AS category_name, gc.slug AS category_slug 
        FROM gallery g 
        JOIN gallery_categories gc ON g.category_id = gc.id 
        {$whereClause} 
        ORDER BY g.display_order ASC, g.id DESC 
        LIMIT {$limit}
    ";

    $items = Database::fetchAll($sql, $params);

    $gallery = [];
    foreach ($items as $item) {
        $gallery[] = [
            'id'            => $item['id'],
            'title'         => $item['title'],
            'image_path'    => ASSETS_URL . '/' . ltrim($item['image_path'], '/'),
            'caption'       => $item['caption'] ?? '',
            'category_name' => $item['category_name'],
            'category_slug' => $item['category_slug']
        ];
    }

    json_response([
        'success' => true,
        'count'   => count($gallery),
        'gallery' => $gallery
    ]);

} catch (Throwable $t) {
    error_log('Gallery AJAX error: ' . $t->getMessage());
    json_response(['success' => false, 'message' => 'Failed to load gallery images.'], 500);
}
