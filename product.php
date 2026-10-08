<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!empty($slug)) {
    $product = get_product_by_slug($slug);
    if ($product) $id = $product['id'];
} else {
    $product = get_product_by_id($id);
}

if (!$product) {
    header("Location: " . get_url('shop'));
    exit;
}

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    if(!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: " . get_url('login')); exit;
    }
    add_review($_SESSION['user_id'], $id, (int)$_POST['rating'], $_POST['comment'] ?? '');
    header("Location: " . $_SERVER['REQUEST_URI'] . "#reviews"); exit;
}

// Handle Admin Review Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reviews'])) {
    if (!is_admin()) {
        header("Location: " . get_url('login')); exit;
    }
    $ids_to_delete = $_POST['review_ids'] ?? [];
    if (!empty($ids_to_delete)) {
        delete_reviews($ids_to_delete);
        set_flash_message("Selected reviews have been removed.", 'success');
    }
    header("Location: " . $_SERVER['REQUEST_URI'] . "#reviews"); exit;
}

if (isset($_GET['delete_review_id']) && is_admin()) {
    delete_reviews((int)$_GET['delete_review_id']);
    set_flash_message("Review removed.", 'success');
    header("Location: " . product_url($product['slug']) . "#reviews"); exit;
}

$reviews = get_product_reviews($id);
$related = get_related_products($id, $product['category_id']);

// Calculate Average Rating
$total_reviews = count($reviews);
$avg_rating = 5.0; // Default benchmark
if ($total_reviews > 0) {
    $sum = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($sum / $total_reviews, 1);
}

// Fetch Gallery Images
$gallery = fetch_all("SELECT * FROM product_images WHERE product_id = $id ORDER BY sort_order ASC, id ASC");
$all_images = [];
if (!empty($product['image'])) {
    $all_images[] = get_url(ltrim($product['image'], './'));
}
foreach ($gallery as $g) {
    if (!empty($g['image_path'])) {
        $all_images[] = get_url(ltrim($g['image_path'], './'));
    }
}
if (empty($all_images)) {
    $all_images[] = get_url('assets/images/placeholder.png');
}

// Cart item count for header badge
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-T3LPLX64');</script>
    <!-- End Google Tag Manager -->

    <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($product['description']), 0, 160)); ?>">
    <meta name="keywords" content="shop dehydrated fruits, dried fruit snacks, healthy snacks online, Driyum shop, <?php echo htmlspecialchars($product['name']); ?>">

    <?php 
    $page_title = $product['name'] . " - Premium Valley Harvest";
    $page_description = $product['description'];
    $page_image = $product['image'];
    $page_type = 'product';
    include 'includes/head.php'; 
    ?>

    <style>
        :root {
            --primary-green: #00875A;
            --primary-green-dark: #00704A;
            --primary-green-light: #EBF8F2;
            --accent-orange: #F97316;
            --accent-orange-hover: #EA580C;
        }

        body {
            background-color: #FFFFFF;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: #1F2937;
        }

        .thumb-active {
            border-color: #00875A !important;
            box-shadow: 0 0 0 2px rgba(0, 135, 90, 0.2);
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .accordion-item.open .accordion-content {
            max-height: 800px;
        }

        .accordion-item.open .accordion-icon {
            transform: rotate(180deg);
        }

        /* Swiper styling for recommended products and reviews */
        .recommended-swiper .swiper-pagination-bullet,
        .reviews-swiper .swiper-pagination-bullet {
            background: #D1D5DB;
            opacity: 0.6;
            width: 7px;
            height: 7px;
            transition: all 0.3s ease;
        }
        .recommended-swiper .swiper-pagination-bullet-active,
        .reviews-swiper .swiper-pagination-bullet-active {
            background: #00875A;
            opacity: 1;
            width: 22px;
            border-radius: 9999px;
        }

        /* Hide scrollbars cleanly */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Rating Star Animation */
        .star-rating i {
            transition: color 0.15s ease, transform 0.15s ease;
        }
        .star-rating i:hover {
            transform: scale(1.15);
        }

        /* Image transition */
        #mainProductImage {
            transition: opacity 0.25s ease, transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
    </style>
</head>
<body class="bg-white min-h-screen text-gray-800 antialiased ">

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T3LPLX64"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<!-- STANDALONE CLEAN HEADER (Replaces global bloated header on product page) -->
<header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm transition-all">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl h-14 sm:h-16 flex items-center justify-between">
        
        <!-- Left: Back Button -->
        <button onclick="window.history.length > 1 ? window.history.back() : window.location.href='<?php echo get_url('shop'); ?>'" class="flex items-center gap-2 text-gray-700 hover:text-black py-1 px-2 -ml-2 rounded-xl hover:bg-gray-100/70 transition font-bold text-xs sm:text-sm">
            <i class="fas fa-arrow-left text-sm sm:text-base"></i>
            <span class="hidden sm:inline">Back to Snacks</span>
        </button>

        <!-- Center: Brand Logo / Name -->
        <a href="<?php echo get_url(''); ?>" class="inline-block transform hover:scale-105 transition">
            <img src="<?php echo get_url('assets/images/logo.svg'); ?>" alt="DRIYUM" class="h-7 sm:h-9 w-auto object-contain">
        </a>

        <!-- Right: Share & Cart -->
        <div class="flex items-center gap-1 sm:gap-2">
            <button onclick="shareProduct()" class="w-9 h-9 sm:w-10 sm:h-10 flex items-center justify-center text-gray-700 hover:text-black rounded-full hover:bg-gray-100 transition" title="Share Product">
                <i class="fas fa-share-nodes text-sm sm:text-base"></i>
            </button>
            <button onclick="openCartSidebar(); return false;" class="w-9 h-9 sm:w-10 sm:h-10 flex items-center justify-center text-gray-700 hover:text-black rounded-full hover:bg-gray-100 transition relative" title="Shopping Bag">
                <i class="fas fa-shopping-bag text-base sm:text-lg"></i>
                <span class="absolute top-1.5 right-1.5 bg-[#00875A] text-white text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center shadow-sm" id="header-cart-count"><?php echo $cart_count; ?></span>
            </button>
        </div>

    </div>
</header>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8 max-w-7xl">
    
    <!-- DESKTOP BREADCRUMBS -->
    <nav class="hidden lg:flex items-center gap-2 mb-6 text-xs font-semibold text-gray-400">
        <a href="<?php echo get_url(''); ?>" class="hover:text-[#00875A] transition">Home</a>
        <span class="text-gray-300">/</span>
        <a href="<?php echo get_url('shop'); ?>" class="hover:text-[#00875A] transition">Valley Snacks</a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-900 font-bold"><?php echo htmlspecialchars($product['name']); ?></span>
    </nav>

    <!-- MAIN PRODUCT SECTION: 2 COLUMNS (Screenshot 1 & 5) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 lg:gap-12 items-start mb-14 lg:mb-20">
        
        <!-- LEFT COLUMN: PRODUCT GALLERY -->
        <div class="lg:col-span-6 space-y-3 sm:space-y-4 lg:sticky lg:top-24">
            <!-- Showcase Card -->
            <div class="relative bg-[#F8F9FA] rounded-2xl sm:rounded-3xl p-4 sm:p-6 lg:p-8 flex items-center justify-center border border-gray-100 overflow-hidden aspect-square sm:aspect-[4/3] lg:aspect-auto lg:min-h-[480px] group shadow-sm">
                
                <!-- Expand / Fullscreen Button -->
                <button onclick="openImageModal()" class="absolute top-3 right-3 sm:top-4 sm:right-4 z-20 w-9 h-9 sm:w-10 sm:h-10 bg-white/90 hover:bg-white text-gray-600 hover:text-black rounded-full shadow-sm border border-gray-200/60 flex items-center justify-center transition hover:scale-105 active:scale-95" title="Expand View">
                    <i class="fas fa-up-right-and-down-left-from-center text-xs"></i>
                </button>

                <!-- Navigation Arrows on Desktop -->
                <?php if (count($all_images) > 1): ?>
                <button onclick="prevImage()" class="hidden lg:flex absolute left-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-white/90 hover:bg-white text-gray-600 hover:text-black rounded-full shadow-sm border border-gray-200/60 items-center justify-center transition hover:scale-110 active:scale-95">
                    <i class="fas fa-chevron-left text-xs"></i>
                </button>
                <button onclick="nextImage()" class="hidden lg:flex absolute right-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-white/90 hover:bg-white text-gray-600 hover:text-black rounded-full shadow-sm border border-gray-200/60 items-center justify-center transition hover:scale-110 active:scale-95">
                    <i class="fas fa-chevron-right text-xs"></i>
                </button>
                <?php endif; ?>

                <!-- Main Image Display -->
                <div class="w-full h-full flex items-center justify-center p-2 sm:p-4">
                    <img id="mainProductImage" src="<?php echo $all_images[0]; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class=" w-auto max-w-full object-contain filter drop-shadow-md select-none group-hover:scale-105 transition-transform duration-500">
                </div>

                <!-- Dots Pagination on Mobile -->
                <?php if (count($all_images) > 1): ?>
                <div class="lg:hidden absolute bottom-3 left-1/2 -translate-x-1/2 z-20 bg-black/35 backdrop-blur-sm px-2.5 py-1 rounded-full flex items-center gap-1.5">
                    <?php foreach ($all_images as $idx => $img): ?>
                        <span class="gallery-dot h-1.5 rounded-full transition-all <?php echo $idx === 0 ? 'bg-white w-4' : 'bg-white/50 w-1.5'; ?>" data-index="<?php echo $idx; ?>"></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Thumbnail Row -->
            <?php if (count($all_images) > 1): ?>
            <div class="flex items-center gap-2 sm:gap-3 overflow-x-auto no-scrollbar py-1">
                <?php foreach ($all_images as $idx => $img): ?>
                    <button onclick="selectImage(<?php echo $idx; ?>)" class="thumb-btn flex-shrink-0 w-14 h-14 sm:w-18 sm:h-18 lg:w-20 lg:h-20 bg-white rounded-xl sm:rounded-2xl p-1 sm:p-1.5 border-2 transition-all <?php echo $idx === 0 ? 'thumb-active' : 'border-gray-200 hover:border-gray-300'; ?>">
                        <img src="<?php echo $img; ?>" alt="Thumb <?php echo $idx + 1; ?>" class="w-full h-full object-contain rounded-lg sm:rounded-xl">
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN: PRODUCT DETAILS & PURCHASE -->
        <div class="lg:col-span-6 space-y-5 sm:space-y-6">
            
            <!-- Header Badges & Title -->
            <div class="space-y-2 sm:space-y-3">
                
                <!-- Product Title -->
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-gray-900 tracking-tight leading-tight">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>

                <!-- Rating & Origin Row -->
                <div class="flex flex-wrap items-center gap-2 sm:gap-3 pt-1">
                    <div class="flex items-center gap-1 bg-[#00875A] text-white px-2 py-0.5 rounded-md text-xs font-black">
                        <span><?php echo number_format($avg_rating, 1); ?></span>
                        <i class="fas fa-star text-[9px]"></i>
                    </div>

                    <a href="#reviews" class="text-xs font-semibold text-gray-500 hover:text-[#00875A] transition">
                        (<?php echo $total_reviews; ?> Customer <?php echo $total_reviews === 1 ? 'Review' : 'Reviews'; ?>)
                    </a>

                    
                </div>
            </div>

            <!-- Product Description Paragraph -->
            <div class="text-xs sm:text-sm text-gray-600 leading-relaxed font-normal">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>

            <div class="border-t border-gray-100 my-2 sm:my-4"></div>

            <!-- DESKTOP BUY & PRICE CARD (Screenshot 5) -->
            <div class="hidden lg:block bg-white rounded-2xl p-6 border border-gray-100 shadow-sm border-t-4 border-t-[#00875A] space-y-5">
                <!-- Large Price -->
                <div class="flex items-baseline gap-3">
                    <span class="text-4xl font-black text-gray-900 tracking-tight">
                        ₹<span id="desktopPriceDisplay"><?php echo $product['price']; ?></span>
                    </span>
                    <?php if($product['original_price'] > $product['price']): ?>
                        <span class="text-lg text-gray-400 line-through font-semibold">₹<?php echo $product['original_price']; ?></span>
                        <span class="text-xs font-black text-rose-500 bg-rose-50 px-2 py-0.5 rounded-full">
                            Save <?php echo round((($product['original_price']-$product['price'])/$product['original_price'])*100); ?>%
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Actions: ADD + and BUY NOW -->
                <div class="grid grid-cols-2 gap-4">
                    <button 
                        onclick="addToCart(<?php echo $id; ?>, this, 1)" 
                        <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                        class="<?php echo $product['stock'] <= 0 ? 'border-gray-200 text-gray-300 cursor-not-allowed' : 'border-2 border-[#00875A] text-[#00875A] hover:bg-[#EBF8F2] active:scale-95'; ?> py-3.5 px-6 rounded-full font-black text-sm uppercase tracking-wider transition-all flex items-center justify-center gap-2">
                        <span>ADD +</span>
                    </button>

                    <button 
                        onclick="quickBuy(<?php echo $id; ?>, this, 1)" 
                        <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                        class="<?php echo $product['stock'] <= 0 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-[#F97316] hover:bg-[#EA580C] text-white shadow-md hover:shadow-lg active:scale-95'; ?> py-3.5 px-6 rounded-full font-black text-sm uppercase tracking-wider transition-all flex items-center justify-center gap-2">
                        <span>BUY NOW</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </button>
                </div>

                <!-- Security Note -->
                <div class="text-center pt-1 text-[11px] font-bold text-gray-400 flex items-center justify-center gap-1.5">
                    <i class="fas fa-shield-halved text-emerald-600"></i>
                    <span>SSL Secured Checkout & Priority Dispatch</span>
                </div>
            </div>

    
            <!-- ACCORDIONS (Screenshot 2 & 5) -->
            <div class="space-y-2.5 sm:space-y-3 pt-2">
                

                <!-- Accordion 2: Nutritional Value Profile (OPEN BY DEFAULT - Screenshot 2) -->
                <div class="accordion-item open bg-white rounded-xl sm:rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
                    <button type="button" onclick="toggleAccordion(this)" class="w-full p-4 px-4.5 sm:px-5 flex items-center justify-between text-left hover:bg-gray-50/50 transition">
                        <div class="flex items-center gap-2.5 sm:gap-3 text-xs sm:text-sm font-bold text-gray-900">
                            <i class="far fa-heart text-[#00875A] text-xs sm:text-sm font-bold"></i>
                            <span>Nutritional Value </span>
                        </div>
                        <i class="fas fa-chevron-down accordion-icon text-gray-400 text-xs transition-transform duration-300"></i>
                    </button>
                    <div class="accordion-content border-t border-gray-100 bg-white">
                        <div class="p-4 sm:p-5 space-y-3.5">
                            <!-- Green Pill Badge (Screenshot 2) -->
                            <div>
                                <span class="inline-block bg-[#F0FDF4] text-[#166534] border border-[#BBF7D0] text-[9.5px] sm:text-[11px] font-black uppercase tracking-wider px-3.5 py-1.5 rounded-full shadow-xs">
                                    Nutritional Values per 100g
                                </span>
                            </div>

                            <!-- Nutrition Table Card with Alternating Stripes -->
                            <div class="rounded-xl sm:rounded-2xl border border-gray-200 overflow-hidden divide-y divide-gray-200/90 text-xs sm:text-sm shadow-xs">
                                <?php
                                $nutrition_rows = [
                                    ['Energy', '299 kcal'],
                                    ['Protein', '3 g'],
                                    ['Carbohydrate', '79 g'],
                                    ['Total Sugars', '59 g'],
                                    ['Added Sugars', '0 g'],
                                    ['Total Fat', '0.5 g'],
                                    ['Saturated Fat', '0 g'],
                                    ['Trans Fat', '0 g'],
                                    ['Dietary Fibre', '4 g'],
                                    ['Sodium', '11 mg']
                                ];

                                if (!empty($product['nutritional_info'])) {
                                    $raw_nut = trim($product['nutritional_info']);
                                    if (strpos($raw_nut, '\"') !== false) $raw_nut = stripslashes($raw_nut);
                                    $nut_json = json_decode($raw_nut, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($nut_json) && !empty($nut_json)) {
                                        $nutrition_rows = [];
                                        foreach ($nut_json as $k => $v) {
                                            if (!empty($v)) $nutrition_rows[] = [$k, $v];
                                        }
                                    }
                                }

                                foreach ($nutrition_rows as $idx => $nrow):
                                ?>
                                <div class="flex items-center justify-between px-4 sm:px-5 py-3 sm:py-3.5 <?php echo ($idx % 2 === 1) ? 'bg-[#F9FAFB]' : 'bg-white'; ?> transition-colors">
                                    <span class="font-bold text-gray-900 text-xs sm:text-sm tracking-tight"><?php echo htmlspecialchars($nrow[0]); ?></span>
                                    <span class="font-semibold text-gray-600 text-xs sm:text-sm text-right font-sans"><?php echo htmlspecialchars($nrow[1]); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Accordion 3: Storage & Freshness (Admin Configurable) -->
                <?php 
                $storage_enabled = get_setting('storage_freshness_enabled', 'on');
                if ($storage_enabled !== 'off'): 
                    $storage_title = get_setting('storage_freshness_title', 'Storage & Freshness');
                    $default_storage_text = "Store in a cool, dry place away from direct sunlight. Once opened, keep in an airtight container or seal the ziplock pouch tightly.\n\nBest consumed within 6 months from packaging date for maximum crunch and natural sweetness.";
                    $storage_content = get_setting('storage_freshness_content', $default_storage_text);
                    $storage_paragraphs = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $storage_content))));
                ?>
                <div class="accordion-item bg-white rounded-xl sm:rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
                    <button type="button" onclick="toggleAccordion(this)" class="w-full p-4 px-4.5 sm:px-5 flex items-center justify-between text-left hover:bg-gray-50/50 transition">
                        <div class="flex items-center gap-2.5 sm:gap-3 text-xs sm:text-sm font-bold text-gray-900">
                            <i class="fas fa-boxes-packing text-[#00875A] text-xs sm:text-sm"></i>
                            <span><?php echo htmlspecialchars($storage_title); ?></span>
                        </div>
                        <i class="fas fa-chevron-down accordion-icon text-gray-400 text-xs transition-transform duration-300"></i>
                    </button>
                    <div class="accordion-content border-t border-gray-100 bg-[#FAFAFA]/50">
                        <div class="p-4 sm:p-5 text-xs text-gray-600 leading-relaxed space-y-2 font-medium">
                            <?php if (!empty($storage_paragraphs)): ?>
                                <?php foreach ($storage_paragraphs as $sp): ?>
                                    <p><?php echo htmlspecialchars_decode(htmlspecialchars($sp, ENT_QUOTES, 'UTF-8')); ?></p>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?php echo nl2br(htmlspecialchars($storage_content)); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>

        </div>
    </div>

    <!-- CUSTOMER RATINGS & WRITE A REVIEW SECTION (Screenshot 3) -->
    <section id="reviews" class="pt-6 sm:pt-10 pb-12 sm:pb-16 border-t border-gray-100 max-w-4xl mx-auto">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight mb-6 sm:mb-8">
            Customer Ratings
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 items-start mb-10 sm:mb-12">
            
            <!-- Rating Score Card (Screenshot 3 top card) -->
            <div class="bg-white rounded-2xl sm:rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm flex flex-col items-center justify-center text-center">
                <span class="text-5xl sm:text-6xl lg:text-7xl font-black text-gray-900 tracking-tighter leading-none mb-3">
                    <?php echo number_format($avg_rating, 1); ?>
                </span>
                
                <div class="flex text-amber-400 text-base sm:text-lg gap-1 mb-2.5">
                    <?php 
                    for($i=1; $i<=5; $i++) {
                        if($i <= floor($avg_rating)) echo '<i class="fas fa-star"></i>';
                        elseif($i <= ceil($avg_rating)) echo '<i class="fas fa-star-half-alt"></i>';
                        else echo '<i class="far fa-star text-gray-200"></i>';
                    }
                    ?>
                </div>

                <p class="text-xs font-bold text-gray-400">
                    Based on <?php echo $total_reviews; ?> customer <?php echo $total_reviews === 1 ? 'review' : 'reviews'; ?>
                </p>
            </div>

            <!-- Write a Review Form Card (Screenshot 3 bottom card) -->
            <div class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-7 border border-gray-100 shadow-sm">
                <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">Write a Review</h3>

                <?php if (is_logged_in()): ?>
                <form action="" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Your Rating</label>
                        <div class="star-rating flex items-center gap-2 text-xl sm:text-2xl text-gray-300">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <i class="far fa-star cursor-pointer star-btn" data-value="<?php echo $i; ?>" onclick="setRating(<?php echo $i; ?>)"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="reviewRatingInput" value="5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Your Experience / Feedback</label>
                        <textarea name="comment" rows="3" required placeholder="How did you like this Product ? Describe taste, packing, and quality..." class="w-full p-3 sm:p-4 rounded-xl sm:rounded-2xl border border-gray-200 text-xs sm:text-sm focus:outline-none focus:border-[#00875A] focus:ring-1 focus:ring-[#00875A] transition resize-none"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-[#00875A] hover:bg-[#00704A] text-white font-extrabold py-3 sm:py-3.5 px-6 rounded-xl transition shadow-md active:scale-98 text-xs sm:text-sm uppercase tracking-wider">
                        Submit Review
                    </button>
                </form>
                <?php else: ?>
                <div class="text-center py-6 space-y-3">
                    <p class="text-xs text-gray-500 font-medium">Please sign in to share your verified review with the community.</p>
                    <a href="<?php echo get_url('login'); ?>" class="inline-block bg-[#00875A] text-white font-extrabold px-6 py-2.5 rounded-xl text-xs uppercase tracking-wider hover:bg-[#00704A] transition shadow-sm">
                        Sign In to Review
                    </a>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- REVIEWS SLIDING CAROUSEL -->
        <?php if (!empty($reviews)): ?>
        <div class="mt-8">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm sm:text-base font-bold text-gray-900">
                    Verified Feedback (<?php echo count($reviews); ?>)
                </h4>
                <?php if (count($reviews) > 1): ?>
                <div class="flex items-center gap-2">
                    <button class="rev-prev-btn w-8 h-8 rounded-full bg-gray-100 hover:bg-[#00875A] text-gray-600 hover:text-white flex items-center justify-center transition text-xs shadow-xs" aria-label="Previous Review">
                        <i class="fas fa-chevron-left text-[10px]"></i>
                    </button>
                    <button class="rev-next-btn w-8 h-8 rounded-full bg-gray-100 hover:bg-[#00875A] text-gray-600 hover:text-white flex items-center justify-center transition text-xs shadow-xs" aria-label="Next Review">
                        <i class="fas fa-chevron-right text-[10px]"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <div class="swiper reviews-swiper pb-8">
                <div class="swiper-wrapper">
                    <?php foreach ($reviews as $rev): ?>
                    <div class="swiper-slide h-auto">
                        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 shadow-sm flex flex-col justify-between h-full space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5 sm:gap-3">
                                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-emerald-100 text-[#00875A] font-black text-xs sm:text-sm flex items-center justify-center flex-shrink-0">
                                        <?php echo strtoupper(substr($rev['user_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h5 class="text-xs sm:text-sm font-bold text-gray-900 leading-tight"><?php echo htmlspecialchars($rev['user_name']); ?></h5>
                                        <span class="text-[9px] sm:text-[10px] text-gray-400 font-medium"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="flex text-amber-400 text-xs gap-0.5 flex-shrink-0">
                                    <?php for($s=1; $s<=5; $s++) echo $s <= $rev['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star text-gray-200"></i>'; ?>
                                </div>
                            </div>
                            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed font-normal">
                                <?php echo nl2br(htmlspecialchars($rev['comment'])); ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination reviews-pagination mt-2"></div>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- RECOMMENDED PRODUCTS SLIDING CAROUSEL (Screenshot 4 - Enhanced Touch Slider) -->
    <?php if (!empty($related)): ?>
    <section class="pt-8 sm:pt-10 pb-16 border-t border-gray-100">
        <div class="flex items-end justify-between mb-6 sm:mb-8">
            <div>
                <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-[#00875A] flex items-center gap-1.5 mb-1">
                    <span class="w-2 h-2 rounded-full bg-[#00875A]"></span>
                    <span>More of What You Love</span>
                </span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                    Recommended For You
                </h2>
              
            </div>

            <!-- Desktop Slider Controls -->
            <div class="hidden sm:flex items-center gap-2">
                <button class="rec-prev-btn w-9 h-9 rounded-full bg-gray-100 hover:bg-[#00875A] text-gray-700 hover:text-white flex items-center justify-center transition shadow-sm" aria-label="Previous">
                    <i class="fas fa-chevron-left text-xs"></i>
                </button>
                <button class="rec-next-btn w-9 h-9 rounded-full bg-gray-100 hover:bg-[#00875A] text-gray-700 hover:text-white flex items-center justify-center transition shadow-sm" aria-label="Next">
                    <i class="fas fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>

        <!-- SWIPER CAROUSEL CONTAINER -->
        <div class="swiper recommended-swiper pb-10">
            <div class="swiper-wrapper">
                <?php foreach ($related as $rel): ?>
                <div class="swiper-slide h-auto">
                    <!-- Beautiful Product Card matching Screenshot 4 -->
                    <div class="bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-4 border border-gray-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between h-full group">
                        
                        <div>
                            <!-- Product Image Container with Badges -->
                            <div class="relative bg-[#F8F9FA] rounded-xl sm:rounded-2xl p-3 sm:p-4 aspect-square flex items-center justify-center mb-3 overflow-hidden">
                               

                                <!-- Quick Preview Eye -->
                                <a href="<?php echo product_url($rel['slug']); ?>" class="absolute top-2.5 right-2.5 w-7 h-7 bg-white/90 hover:bg-white rounded-full flex items-center justify-center text-gray-600 hover:text-black shadow-sm text-xs z-10 transition hover:scale-110" title="Quick View">
                                    <i class="far fa-eye"></i>
                                </a>

                                <a href="<?php echo product_url($rel['slug']); ?>" class="w-full h-full flex items-center justify-center">
                                    <img src="<?php echo get_url(ltrim($rel['image'], './')); ?>" alt="<?php echo htmlspecialchars($rel['name']); ?>" class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-300">
                                </a>
                            </div>

                            <!-- Category & Title -->
                            <div class="space-y-1 mb-2">
                                
                                <a href="<?php echo product_url($rel['slug']); ?>" class="text-sm sm:text-base font-bold text-gray-900 line-clamp-1 hover:text-[#00875A] transition">
                                    <?php echo htmlspecialchars($rel['name']); ?>
                                </a>
                            </div>

                           
                        </div>

                        <!-- Price and ADD + Button -->
                        <div class="flex items-center justify-between pt-2.5 border-t border-gray-100">
                            <span class="text-base sm:text-lg font-black text-gray-900 font-sans">
                                ₹<?php echo $rel['price']; ?>
                            </span>
                            <button onclick="addToCart(<?php echo $rel['id']; ?>, this, 1)" class="border border-[#00875A] text-[#00875A] hover:bg-[#00875A] hover:text-white px-3.5 py-1.5 rounded-full font-black text-xs uppercase tracking-wider transition-all active:scale-95 shadow-xs">
                                ADD +
                            </button>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Mobile Pagination Dots -->
            <div class="swiper-pagination rec-pagination mt-4"></div>
        </div>
    </section>
    <?php endif; ?>


</main>

<!-- MOBILE FLOATING STICKY BOTTOM BAR (Screenshot 1) -->
<div class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-100 p-3 px-4 shadow-[0_-8px_30px_rgba(0,0,0,0.12)] flex items-center justify-between gap-3">
    <!-- Left: Price -->
    <div class="flex flex-col">
        <span class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight leading-none">
            ₹<span id="mobileBottomPrice"><?php echo $product['price']; ?></span>
        </span>
        <span class="text-[9px] font-bold text-emerald-600 mt-0.5">Inclusive of all taxes</span>
    </div>

    <!-- Right: ADD + and BUY NOW buttons -->
    <div class="flex items-center gap-2">
        <button 
            onclick="addToCart(<?php echo $id; ?>, this, 1)" 
            <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
            class="<?php echo $product['stock'] <= 0 ? 'border-gray-200 text-gray-300' : 'border-2 border-[#00875A] text-[#00875A] hover:bg-emerald-50 active:scale-95'; ?> px-4 py-2.5 rounded-full font-black text-xs uppercase tracking-wider transition-all">
            ADD +
        </button>

        <button 
            onclick="quickBuy(<?php echo $id; ?>, this, 1)" 
            <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
            class="<?php echo $product['stock'] <= 0 ? 'bg-gray-200 text-gray-400' : 'bg-[#F97316] text-white hover:bg-[#EA580C] active:scale-95 shadow-md'; ?> px-5 py-2.5 rounded-full font-black text-xs uppercase tracking-wider transition-all flex items-center gap-1">
            <span>BUY NOW</span>
            <i class="fas fa-arrow-right text-[10px]"></i>
        </button>
    </div>
</div>

<!-- FULLSCREEN IMAGE LIGHTBOX MODAL -->
<div id="imageLightboxModal" class="fixed inset-0 z-[100] bg-black/90 hidden backdrop-blur-md flex items-center justify-center p-4">
    <button onclick="closeImageModal()" class="absolute top-6 right-6 text-white text-2xl hover:text-gray-300 transition w-10 h-10 flex items-center justify-center">
        <i class="fas fa-times"></i>
    </button>
    <img id="lightboxImg" src="<?php echo $all_images[0]; ?>" class="max-w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl">
</div>

<!-- TOAST CONTAINER FOR NOTIFICATIONS -->
<div id="toast-container" class="fixed bottom-24 right-4 z-[9999] md:bottom-6"></div>

<!-- SIDEBAR SHOPPING CART (Integrated for standalone product page) -->
<div id="cart-sidebar-overlay" onclick="closeCartSidebar()" class="fixed inset-0 bg-black/60 z-[2000] hidden opacity-0 transition-opacity duration-300 backdrop-blur-sm"></div>
<div id="cart-sidebar" class="fixed top-0 right-0 h-full w-[90%] md:w-[480px] max-w-[480px] bg-[#f8fafc] z-[2001] transform translate-x-full transition-transform duration-400 flex flex-col shadow-2xl">
    <div class="px-6 py-5 flex justify-between items-center bg-white border-b border-gray-100 shrink-0">
        <div>
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[#00875A] block">Your Stash</span>
            <h2 class="text-xl sm:text-2xl font-black text-gray-900">Shopping Bag</h2>
        </div>
        <button onclick="closeCartSidebar()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 hover:bg-black hover:text-white transition-all" aria-label="Close Bag">
            <i class="fas fa-times text-base"></i>
        </button>
    </div>
    
    <div id="cart-items-container" class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
        <!-- Rendered dynamically by chunky.js -->
    </div>
    
    <div class="px-6 py-6 bg-white border-t border-gray-100 shadow-[0_-10px_30px_rgba(0,0,0,0.03)] relative z-20 shrink-0">
        <div class="flex justify-between items-end mb-4">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 block">Subtotal</span>
                <span class="text-xs text-gray-400 font-medium">Shipping calculated at checkout</span>
            </div>
            <span id="cart-total" class="text-2xl sm:text-3xl font-black text-gray-900">₹0</span>
        </div>
        <a href="<?php echo get_url('checkout'); ?>" class="w-full flex items-center justify-between bg-black text-white p-4 sm:p-5 rounded-2xl hover:bg-[#00875A] transition-all group shadow-xl">
            <span class="text-base font-black">Proceed to Checkout</span>
            <i class="fas fa-arrow-right text-sm"></i>
        </a>
    </div>
</div>

<!-- GA4 Tracking -->
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({ ecommerce: null });
window.dataLayer.push({
    event: "view_item",
    ecommerce: {
        currency: "INR",
        value: <?php echo $product['price']; ?>,
        items: [{
            item_id: "<?php echo $product['id']; ?>",
            item_name: "<?php echo htmlspecialchars($product['name']); ?>",
            price: <?php echo $product['price']; ?>,
            item_category: "<?php echo htmlspecialchars($product['category_name'] ?? 'Snacks'); ?>"
        }]
    }
});
</script>

<!-- PRODUCT INTERACTIVE LOGIC & SWIPER INITIALIZATION -->
<script>
const galleryImages = <?php echo json_encode($all_images); ?>;
let activeImageIndex = 0;

function selectImage(index) {
    if (index < 0 || index >= galleryImages.length) return;
    activeImageIndex = index;
    const mainImg = document.getElementById('mainProductImage');
    
    mainImg.style.opacity = '0';
    setTimeout(() => {
        mainImg.src = galleryImages[activeImageIndex];
        mainImg.style.opacity = '1';
    }, 150);

    // Update Thumbs
    document.querySelectorAll('.thumb-btn').forEach((btn, idx) => {
        if (idx === index) {
            btn.classList.add('thumb-active');
            btn.classList.remove('border-gray-200');
        } else {
            btn.classList.remove('thumb-active');
            btn.classList.add('border-gray-200');
        }
    });

    // Update Mobile Dots
    document.querySelectorAll('.gallery-dot').forEach((dot, idx) => {
        if (idx === index) {
            dot.className = 'gallery-dot h-1.5 rounded-full bg-white w-4 transition-all';
        } else {
            dot.className = 'gallery-dot h-1.5 rounded-full bg-white/50 w-1.5 transition-all';
        }
    });
}

function prevImage() {
    let nextIdx = (activeImageIndex - 1 + galleryImages.length) % galleryImages.length;
    selectImage(nextIdx);
}

function nextImage() {
    let nextIdx = (activeImageIndex + 1) % galleryImages.length;
    selectImage(nextIdx);
}

// Lightbox Modal
function openImageModal() {
    const modal = document.getElementById('imageLightboxModal');
    const modalImg = document.getElementById('lightboxImg');
    modalImg.src = galleryImages[activeImageIndex];
    modal.classList.remove('hidden');
}

function closeImageModal() {
    document.getElementById('imageLightboxModal').classList.add('hidden');
}

// Accordion Toggle
function toggleAccordion(button) {
    const item = button.closest('.accordion-item');
    item.classList.toggle('open');
}

// Review Star Rating Selection
function setRating(val) {
    document.getElementById('reviewRatingInput').value = val;
    document.querySelectorAll('.star-btn').forEach(star => {
        const starVal = parseInt(star.getAttribute('data-value'));
        if (starVal <= val) {
            star.className = 'fas fa-star cursor-pointer star-btn text-amber-400';
        } else {
            star.className = 'far fa-star cursor-pointer star-btn text-gray-300';
        }
    });
}

// Share Product Function
function shareProduct() {
    if (navigator.share) {
        navigator.share({
            title: "<?php echo addslashes($product['name']); ?>",
            text: "Check out this authentic Kashmiri harvest on DRIYUM!",
            url: window.location.href
        }).catch(() => {});
    } else {
        navigator.clipboard.writeText(window.location.href).then(() => {
            if (typeof showToast === 'function') {
                showToast('Link copied to clipboard!', 'success');
            } else {
                alert('Product link copied to clipboard!');
            }
        });
    }
}

// Initialize Swiper Carousels
document.addEventListener('DOMContentLoaded', () => {
    // Reviews Carousel (Sliding touch carousel for small and large screens)
    if (typeof Swiper !== 'undefined' && document.querySelector('.reviews-swiper')) {
        new Swiper('.reviews-swiper', {
            slidesPerView: 1.15,
            spaceBetween: 14,
            grabCursor: true,
            pagination: {
                el: '.reviews-pagination',
                clickable: true,
                dynamicBullets: true
            },
            navigation: {
                nextEl: '.rev-next-btn',
                prevEl: '.rev-prev-btn'
            },
            breakpoints: {
                640: {
                    slidesPerView: 1.6,
                    spaceBetween: 16
                },
                768: {
                    slidesPerView: 2,
                    spaceBetween: 18
                },
                1024: {
                    slidesPerView: 2,
                    spaceBetween: 20
                }
            }
        });
    }

    // Recommended Products Carousel
    if (typeof Swiper !== 'undefined' && document.querySelector('.recommended-swiper')) {
        new Swiper('.recommended-swiper', {
            slidesPerView: 1.4,
            spaceBetween: 14,
            grabCursor: true,
            pagination: {
                el: '.rec-pagination',
                clickable: true,
                dynamicBullets: true
            },
            navigation: {
                nextEl: '.rec-next-btn',
                prevEl: '.rec-prev-btn'
            },
            breakpoints: {
                480: {
                    slidesPerView: 2,
                    spaceBetween: 16
                },
                768: {
                    slidesPerView: 3,
                    spaceBetween: 18
                },
                1024: {
                    slidesPerView: 4,
                    spaceBetween: 20
                }
            }
        });
    }
});
</script>
</body>
</html>
