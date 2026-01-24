<?php
header('Content-Type: application/xml; charset=utf-8');
require_once 'config/database.php';
require_once 'includes/functions.php';

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Static Pages -->
    <url>
        <loc><?php echo FULL_BASE_URL; ?></loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo get_url('shop'); ?></loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?php echo get_url('about'); ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?php echo get_url('contact'); ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?php echo get_url('features'); ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>

    <!-- Dynamic Products -->
    <?php
    $products = fetch_all("SELECT slug, updated_at FROM products WHERE is_active = 1");
    foreach($products as $p):
    ?>
    <url>
        <loc><?php echo product_url($p['slug']); ?></loc>
        <lastmod><?php echo date('Y-m-d', strtotime($p['updated_at'])); ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

    <!-- Dynamic Categories -->
    <?php
    $categories = fetch_all("SELECT slug FROM categories");
    foreach($categories as $c):
    ?>
    <url>
        <loc><?php echo category_url($c['slug']); ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>
</urlset>
