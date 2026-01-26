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

<?php 
if (function_exists('render_seo_tags')) {
    $page_type = $page_type ?? 'website';
    render_seo_tags($page_title, $page_description, $page_image, $page_type);
} else {
    echo "<title>" . ($page_title ? "$page_title | DRIYUM" : "DRIYUM") . "</title>";
}
?>

<!-- Frameworks -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- Core Styling with Cache Busting -->
<link rel="stylesheet" href="<?php echo get_url('assets/css/chunky.css'); ?>?v=<?php echo time(); ?>">

<!-- JS Config & Core Logic -->
<script>
    const BASE_URL = "<?php echo get_url(''); ?>";
</script>
<script src="<?php echo get_url('assets/js/chunky.js'); ?>?v=<?php echo time(); ?>" defer></script>
