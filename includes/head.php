<?php
/**
 * DRIYUM - Global Header Meta & Assets
 * This file centralizes all CSS, JS config, and Meta tags.
 */

// SEO Variables (Defaults if not set)
$page_title = $page_title ?? '';
$page_description = $page_description ?? 'Redefining the art of snacking with premium, indulgence. Naturally sweet, unapologetically bold.';
$page_image = $page_image ?? 'assets/images/og-image.jpg';

// In some files, description might be passed via local variables - we try to detect
if (!isset($page_title) && isset($title)) $page_title = $title;
if (!isset($page_description) && isset($description)) $page_description = $description;
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="<?php echo get_url('assets/images/logoicon.png'); ?>">
<link rel="apple-touch-icon" href="<?php echo get_url('assets/images/logoicon.png'); ?>">

<!-- Critical Resource Preconnects -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com">
<link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,200..900;1,200..900&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

<?php 
if (function_exists('render_seo_tags')) {
    $page_type = $page_type ?? 'website';
    render_seo_tags($page_title, $page_description, $page_image, $page_type);
} else {
    echo "<title>" . ($page_title ? "$page_title | DRIYUM" : "DRIYUM") . "</title>";
}
?>

<!-- Optimized Asset Loading -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>

<link rel="stylesheet" href="<?php echo get_url('assets/css/chunky.css'); ?>?v=1.0.3">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<!-- JS Config & Core Logic -->
<script>
    const BASE_URL = "<?php echo get_url(''); ?>";
    const FREE_SHIPPING_THRESHOLD = <?php echo get_setting('free_shipping_threshold', 499); ?>;
</script>
<script src="<?php echo get_url('assets/js/chunky.js'); ?>?v=1.0.3" defer></script>


