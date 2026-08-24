<?php
/**
 * Kamadenu Goushala Platform - Global Page Header
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

// Dynamic SEO Variables
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . get_setting('site_name', 'Kamadenu Goushala') : get_setting('site_name', 'Kamadenu Goushala') . ' – ' . get_setting('site_tagline', 'Vedic Cow Sanctuary');
$metaDescription = $metaDescription ?? 'Kamadenu Goushala is dedicated to the rescue, loving care, and lifelong sanctuary of sacred indigenous Indian cows (Bos Indicus). 80G Tax Exempted.';
$canonicalUrl = $canonicalUrl ?? (BASE_URL . ($_SERVER['REQUEST_URI'] ?? ''));
$ogImage = $ogImage ?? (ASSETS_URL . '/images/og_banner.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- Dynamic SEO Meta Tags -->
    <title><?= e($pageTitle); ?></title>
    <meta name="description" content="<?= e($metaDescription); ?>">
    <link rel="canonical" href="<?= e($canonicalUrl); ?>">
    <meta name="csrf-token" content="<?= e(csrf_token()); ?>">
    
    <!-- Open Graph / Social Sharing -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle); ?>">
    <meta property="og:description" content="<?= e($metaDescription); ?>">
    <meta property="og:url" content="<?= e($canonicalUrl); ?>">
    <meta property="og:image" content="<?= e($ogImage); ?>">
    <meta property="og:site_name" content="<?= e(get_setting('site_name', 'Kamadenu Goushala')); ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,800;1,600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Master Heritage Design System -->
    <link rel="stylesheet" href="<?= ASSETS_URL; ?>/css/style.css">
</head>
<body>


    <?php require_once __DIR__ . '/navbar.php'; ?>

