<?php
/**
 * Kamadenu Goushala Platform - AJAX Cow Filter Endpoint
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $breedSlug = isset($_GET['breed']) && $_GET['breed'] !== 'all' ? sanitize_input($_GET['breed']) : null;
    $filterType = isset($_GET['filter']) ? sanitize_input($_GET['filter']) : null;
    $limit = isset($_GET['limit']) ? max(1, min(24, (int)$_GET['limit'])) : 8;

    $where = ["c.status != 'deceased'"];
    $params = [];

    if ($breedSlug) {
        $where[] = "b.slug = ?";
        $params[] = $breedSlug;
    }

    if ($filterType === 'rescued') {
        $where[] = "c.rescue_date IS NOT NULL";
    } elseif ($filterType === 'adoptable') {
        $where[] = "c.status = 'active'";
    } elseif ($filterType === 'featured') {
        $where[] = "c.is_featured = 1";
    }

    $whereClause = implode(' AND ', $where);

    $sql = "
        SELECT c.id, c.cow_code, c.name, c.slug, c.gender, c.health_status, c.status,
               c.rescue_date, c.rescue_story, c.description, c.main_image,
               b.name AS breed_name, b.slug AS breed_slug
        FROM cows c
        JOIN cow_breeds b ON c.breed_id = b.id
        WHERE {$whereClause}
        ORDER BY c.is_featured DESC, c.id ASC
        LIMIT {$limit}
    ";

    $cows = Database::fetchAll($sql, $params);

    $items = [];
    foreach ($cows as $cow) {
        $healthClass = match($cow['health_status']) {
            'under_treatment' => 'badge-health-treatment',
            'elderly_care'   => 'badge-health-elderly',
            'recovering'     => 'badge-health-recovering',
            default          => 'badge-health-healthy'
        };

        $items[] = [
            'id'            => $cow['id'],
            'cow_code'      => $cow['cow_code'],
            'name'          => $cow['name'],
            'slug'          => $cow['slug'],
            'breed_name'    => $cow['breed_name'],
            'breed_slug'    => $cow['breed_slug'],
            'gender'        => ucfirst(str_replace('_', ' ', $cow['gender'])),
            'health_status' => ucfirst(str_replace('_', ' ', $cow['health_status'])),
            'health_class'  => $healthClass,
            'status'        => $cow['status'],
            'rescue_date'   => format_date($cow['rescue_date']),
            'excerpt'       => mb_strimwidth($cow['rescue_story'] ?? $cow['description'] ?? '', 0, 95, '...'),
            'image_url'     => image_url($cow['main_image'] ?? null, 'cows', 'placeholder-cow.jpg'),
            'url'           => BASE_URL . '/cow-details.php?slug=' . urlencode($cow['slug']),
            'adopt_url'     => BASE_URL . '/adopt.php?cow_id=' . $cow['id']
        ];
    }

    echo json_encode([
        'success' => true,
        'count'   => count($items),
        'cows'    => $items
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to filter cows: ' . $t->getMessage()
    ]);
}
