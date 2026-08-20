<?php
/**
 * Kamadenu Goushala Platform - Dynamic XML Sitemap Generator
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

header('Content-Type: application/xml; charset=utf-8');

// Fetch dynamic catalog items
$cows = Database::fetchAll("SELECT slug FROM cows WHERE status != 'deceased' ORDER BY id ASC");
$breeds = Database::fetchAll("SELECT slug FROM cow_breeds ORDER BY id ASC");
$sevaPrograms = Database::fetchAll("SELECT slug FROM seva_programs WHERE is_active = 1 ORDER BY id ASC");
$products = Database::fetchAll("SELECT slug FROM products WHERE is_active = 1 ORDER BY id ASC");
$posts = Database::fetchAll("SELECT slug FROM blog_posts WHERE is_published = 1 ORDER BY id DESC");

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Static Core Pages -->
    <url>
        <loc><?= BASE_URL; ?>/index.php</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/about.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/cows.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/breeds.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/seva.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/feed.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/adopt.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/sponsor.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/donate.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/products.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/gallery.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/videos.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/blog.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/transparency.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/contact.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/privacy.php</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/terms.php</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/refund.php</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?= BASE_URL; ?>/donation-policy.php</loc>
        <changefreq>yearly</changefreq>
        <priority>0.4</priority>
    </url>

    <!-- Dynamic Cow Details URLs -->
    <?php foreach ($cows as $c): ?>
    <url>
        <loc><?= BASE_URL; ?>/cow-details.php?slug=<?= e($c['slug']); ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

    <!-- Dynamic Breed URLs -->
    <?php foreach ($breeds as $b): ?>
    <url>
        <loc><?= BASE_URL; ?>/breed-details.php?slug=<?= e($b['slug']); ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>

    <!-- Dynamic Seva Programs -->
    <?php foreach ($sevaPrograms as $sp): ?>
    <url>
        <loc><?= BASE_URL; ?>/seva-details.php?slug=<?= e($sp['slug']); ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

    <!-- Dynamic Products -->
    <?php foreach ($products as $p): ?>
    <url>
        <loc><?= BASE_URL; ?>/product-details.php?slug=<?= e($p['slug']); ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

    <!-- Dynamic Blog Stories -->
    <?php foreach ($posts as $bp): ?>
    <url>
        <loc><?= BASE_URL; ?>/blog-details.php?slug=<?= e($bp['slug']); ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>
</urlset>
