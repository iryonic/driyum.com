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
    add_review($_SESSION['user_id'], $id, (int)$_POST['rating'], $_POST['comment']);
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
$avg_rating = 0;
$is_new_product = true;
if ($total_reviews > 0) {
    $sum = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($sum / $total_reviews, 1);
    $is_new_product = false;
}

// Background Color Logic
$bg_options = ['#FFFBEB', '#F0FDFA', '#FEF2F2', '#F5F3FF', '#ECFDF5', '#FFF7ED', '#FDF2F8'];
$default_bg_colors = ['#E0F2FE', '#DCFCE7', '#FEF3C7', '#FEE2E2', '#F3E8FF', '#FFEDD5'];
$color_base = !empty($product['bg_color']) ? $product['bg_color'] : $bg_options[array_rand($bg_options)];
$page_bg_dark = adjust_brightness($color_base, -10); // Slightly darker for immersion

// Fetch Wishlist IDs for active states
$wishlist_ids = [];
if (isset($_SESSION['user_id'])) {
    $wishlist_res = fetch_all("SELECT product_id FROM wishlist WHERE user_id = ?", [$_SESSION['user_id']]);
    $wishlist_ids = array_column($wishlist_res, 'product_id');
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




<meta name="description" content="Explore our range of premium dehydrated fruits. Healthy, natural, preservative-free fruit snacks available online at Driyum.">

<meta name="keywords" content="shop dehydrated fruits, dried fruit snacks, healthy snacks online, Driyum shop">

    <?php 
    $page_title = $product['name'];
    $page_description = $product['description'];
    $page_image = $product['image'];
    $page_type = 'product';
    include 'includes/head.php'; 
    ?>

    <style>
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #fffbeb; }
        ::-webkit-scrollbar-thumb { background: #24B25D; border-radius: 10px; }
        
        .product-gradient-bg {
            background: <?php echo $page_bg_dark; ?>;
            background: radial-gradient(circle at 70% 30%, <?php echo adjust_brightness($color_base, 20); ?>33 0%, <?php echo $page_bg_dark; ?> 100%);
        }

        /* Desktop Sticky Buy Bar */
        .desktop-sticky-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            z-index: 90;
            transform: translateY(-100%);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 0;
        }
        .desktop-sticky-bar.visible { transform: translateY(0); }


        .thumb-active { 
            border-color: #24B25D !important; swap; }

        .floating-badge {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(5deg); }
        }

        @keyframes imageIn {
            from { opacity: 0; transform: scale(1.1) translateY(10px) rotate(2deg); filter: blur(10px); }
            to { opacity: 1; transform: scale(1) translateY(0) rotate(0deg); filter: blur(0); }
        }
        .image-animate-in { animation: imageIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

        .nutrition-item:hover {
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .sticky-mobile-bar {
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(24px);
                -webkit-backdrop-filter: blur(24px);
                padding: 1.25rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                box-shadow: 0 -15px 40px rgba(0,0,0,0.15);
                z-index: 999;
                animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            }
            @keyframes slideUp { from { transform: translateY(120%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
            body { padding-bottom: 160px !important; }
        }
    </style>
</head>
<body class="product-gradient-bg min-h-screen">

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T3LPLX64"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->


    <?php include 'includes/header.php'; ?>

    <!-- DESKTOP STICKY BUY BAR (Hidden initially) -->
    <div id="desktop-sticky-buy-bar" class="desktop-sticky-bar hidden lg:block">
        <div class="container mx-auto px-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white rounded-xl p-1 border border-gray-100">
                    <img src="<?php echo get_url(ltrim($product['image'], './')); ?>" class="w-full h-full object-contain">
                </div>
                <div>
                    <h4 class="font-black text-sm uppercase tracking-tight text-gray-900"><?php echo $product['name']; ?></h4>
                    <p class="text-[10px] font-bold text-[#24B25D]"><?php echo format_price($product['price']); ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="addToCart(<?php echo $id; ?>, this, 1)" class="bg-black text-white px-8 py-3 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-[#24B25D] hover:text-white transition-all active:scale-95">Add to Bag</button>
                <button onclick="quickBuy(<?php echo $id; ?>, this, 1)" class="bg-[#24B25D] text-white px-8 py-3 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-[#004F42] transition-all active:scale-95">Quick Buy</button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 py-4 relative z-10 font-sans">
        
        <!-- BREADCRUMBS -->
        <nav class="flex items-center gap-2 mb-4 text-[10px] font-black tracking-[0.2em] uppercase text-gray-400">
            <a href="<?php echo get_url(''); ?>" class="hover:text-[#24B25D] transition-colors">Home</a>
            <span class="opacity-30">/</span>
            <a href="<?php echo get_url('shop'); ?>" class="hover:text-[#24B25D] transition-colors">Snacks</a>
            <span class="opacity-30">/</span>
            <span class="text-gray-900"><?php echo $product['name']; ?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 mb-12 lg:mb-16">
            
            <?php
            // Fetch Gallery Images
            $gallery = fetch_all("SELECT * FROM product_images WHERE product_id = $id ORDER BY sort_order");
            ?>
            <!-- LEFT: VISUALS UNIT (STIKCY) -->
            <div class="lg:sticky lg:top-32 h-fit space-y-6">
                <!-- Gallery Section -->
                <div class="flex flex-col lg:flex-row gap-4 md:gap-6">
                <!-- Vertical Thumbs (Desktop) / Horizontal (Mobile) -->
                <div class="order-2 lg:order-1 flex lg:flex-col gap-3 overflow-x-auto lg:overflow-y-auto hide-scrollbar snap-x snap-mandatory lg:max-h-[500px] shrink-0">
                    <div onclick="changeImage('<?php echo get_url(ltrim($product['image'], './')); ?>', this)" class="thumb-item thumb-active w-16 h-16 md:w-20 md:h-20 rounded-[15px] bg-white border-2 border-transparent p-1.5 cursor-pointer shrink-0 transition-all shadow-sm snap-center">
                        <img src="<?php echo get_url(ltrim($product['image'], './')); ?>" class="w-full h-full object-contain">
                    </div>
                    <?php foreach($gallery as $img): ?>
                        <div onclick="changeImage('<?php echo get_url(ltrim($img['image_path'], './')); ?>', this)" class="thumb-item w-16 h-16 md:w-20 md:h-20 rounded-[15px] bg-white border-2 border-transparent p-1.5 cursor-pointer shrink-0 transition-all hover:scale-105 shadow-sm snap-center">
                            <img src="<?php echo get_url(ltrim($img['image_path'], './')); ?>" class="w-full h-full object-contain">
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Main Showcase -->
                <div class="order-1 lg:order-2 flex-1 relative group">
                    <!-- Decor Blobs -->
                    <div class="absolute -top-10 -left-10 w-64 h-64 bg-[#24B25D]/10 rounded-full blur-[100px] animate-pulse"></div>
                    <div class="absolute -bottom-10 -right-0 w-64 h-64 bg-yellow-400/10 rounded-full blur-[100px] animate-pulse delay-1000"></div>

                    <div class="aspect-[4/3] bg-white rounded-[32px] md:rounded-[40px] p-6 md:p-12 border border-white/40 shadow-[0_20px_50px_-15px_rgba(0,0,0,0.05)] flex items-center justify-center relative overflow-hidden backdrop-blur-sm">
                        <!-- Progress Indicator -->
                        <div id="slideshow-progress" class="absolute top-0 left-0 h-1 bg-[#24B25D]/30 w-0 z-30 transition-none"></div>
                        
                        <img id="mainImage" src="<?php echo get_url(ltrim($product['image'], './')); ?>" class="w-full h-full object-contain transform group-hover:scale-105 transition-all duration-[1000ms] cubic-bezier(0.16, 1, 0.3, 1) z-10 drop-shadow-[0_20px_40px_rgba(0,0,0,0.12)] <?php echo $product['stock'] <= 0 ? 'grayscale' : ''; ?>">
                        
                        <div class="absolute top-6 left-6 flex flex-col gap-3 z-30">
                            <?php if($product['is_new']): ?>
                                <span class="bg-black text-white px-3 md:px-5 py-1.5 md:py-2.5 rounded-full text-[8px] md:text-[9px] font-black uppercase tracking-widest shadow-2xl floating-badge">Fresh Drop</span>
                            <?php endif; ?>
                            <span class="bg-[#24B25D] text-white px-3 md:px-5 py-1.5 md:py-2.5 rounded-full text-[8px] md:text-[9px] font-black uppercase tracking-widest shadow-2xl floating-badge delay-700">100% Organic</span>
                        </div>
                    </div>
                </div>
                </div>
                
                <!-- Quick Benefits Grid (Sits below visuals) -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white/50 backdrop-blur-sm p-4 rounded-[24px] border border-white/20 text-center benefit-card transition-all duration-300 hover:bg-white hover:shadow-lg hover:-translate-y-1 group cursor-default">
                        <div class="w-8 h-8 bg-amber-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-seedling text-amber-500 text-sm"></i>
                        </div>
                        <span class="block text-[8px] md:text-[9px] font-black uppercase tracking-widest text-gray-400 group-hover:text-amber-600 transition-colors">All Natural</span>
                    </div>
                    <div class="bg-white/50 backdrop-blur-sm p-4 rounded-[24px] border border-white/20 text-center benefit-card transition-all duration-300 hover:bg-white hover:shadow-lg hover:-translate-y-1 group cursor-default">
                        <div class="w-8 h-8 bg-green-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-apple-whole text-green-500 text-sm"></i>
                        </div>
                        <span class="block text-[8px] md:text-[9px] font-black uppercase tracking-widest text-gray-400 group-hover:text-green-500 transition-colors">100% Real Fruit</span>
                    </div>
                    <div class="bg-white/50 backdrop-blur-sm p-4 rounded-[24px] border border-white/20 text-center benefit-card transition-all duration-300 hover:bg-white hover:shadow-lg hover:-translate-y-1 group cursor-default">
                        <div class="w-8 h-8 bg-rose-50 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-heart text-rose-500 text-sm"></i>
                        </div>
                        <span class="block text-[8px] md:text-[9px] font-black uppercase tracking-widest text-gray-400 group-hover:text-rose-500 transition-colors">No Added Sugar</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT: DETAILS -->
            <div class="lg:pt-2">
              

                <div class="mb-10">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="flex text-yellow-400 text-[10px] md:text-xs gap-0.5">
                            <?php 
                            if ($total_reviews > 0):
                                for($i=1; $i<=5; $i++) {
                                    if($i <= floor($avg_rating)) echo '<i class="fas fa-star"></i>';
                                    elseif($i <= ceil($avg_rating)) echo '<i class="fas fa-star-half-alt"></i>';
                                    else echo '<i class="far fa-star"></i>';
                                }
                            else:
                                echo '<span class="text-[#24B25D] font-black text-[8px] uppercase tracking-widest bg-[#24B25D]/10 px-2 py-0.5 rounded-full">New Drop</span>';
                            endif;
                            ?>
                        </div>
                        <span class="text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2">
                            <?php echo $total_reviews > 0 ? $avg_rating . " (" . $total_reviews . " Reviews)" : "No reviews yet"; ?>
                        </span>
                    </div>
                    <div class="anim-up">
                        <h1 id="product-title-anchor" class="text-[clamp(2.2rem,7vw,4.5rem)] font-heading font-black text-gray-900 mb-2 leading-[0.85] tracking-tighter uppercase whitespace-pre-wrap"><?php echo $product['name']; ?></h1>
                    </div>
                    
                    <div class="flex items-center gap-4 mb-4">
                        <span class="text-5xl sm:text-7xl font-black text-[#004F42] font-heading tracking-tighter leading-none">₹<?php echo $product['price']; ?></span>
                        <?php if($product['original_price'] > $product['price']): ?>
                            <div class="flex flex-col">
                                <span class="text-lg sm:text-xl text-gray-300 line-through font-bold leading-none italic">₹<?php echo $product['original_price']; ?></span>
                                <span class="text-[#FF6B6B] font-black text-[8px] md:text-[9px] uppercase tracking-widest mt-1">Save <?php echo round((($product['original_price']-$product['price'])/$product['original_price'])*100); ?>% Today</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="text-base md:text-lg text-gray-500 font-medium mb-8 leading-relaxed max-w-xl font-heading tracking-tight">
                    <?php echo mb_convert_encoding($product['description'], 'UTF-8', 'ISO-8859-1'); ?>
                </p>

                <!-- Stock Scarcity Bar -->
                <div class="mb-8 max-w-sm">
                    <div class="flex justify-between items-end mb-3">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-900">
                            <?php 
                            if ($product['stock'] <= 0) echo 'Currently Unavailable';
                            elseif ($product['stock'] < 10) echo 'High Demand - Limited Stock';
                            else echo 'Freshly In Stock';
                            ?>
                        </span>
                        <?php if($product['stock'] > 0 && $product['stock'] < 20): ?>
                            <span class="text-[10px] font-black text-[#FF6B6B] animate-pulse">Hurry! Only <?php echo $product['stock']; ?> units left</span>
                        <?php elseif($product['stock'] <= 0): ?>
                            <span class="text-[10px] font-black text-red-500">Restocking Soon</span>
                        <?php else: ?>
                            <span class="text-[10px] font-black text-[#19DC7E]">Premium Quality Guaranteed</span>
                        <?php endif; ?>
                    </div>
                    <div class="h-2.5 bg-white rounded-full overflow-hidden border border-gray-100">
                        <?php 
                        $stock_perc = min(100, max(0, ($product['stock'] / 20) * 100));
                        $bar_color = $product['stock'] < 10 ? 'from-[#FF6B6B] to-[#FF4D4D]' : 'from-[#19DC7E] to-[#16C671]';
                        if ($product['stock'] <= 0) $stock_perc = 0;
                        ?>
                        <div class="h-full bg-gradient-to-r <?php echo $bar_color; ?> rounded-full transition-all duration-1000" style="width: <?php echo $stock_perc; ?>%"></div>
                    </div>
                </div>

                <!-- ADD TO CART & QUANTITY -->
                <div class="flex flex-col gap-3 md:gap-4 mb-8 lg:pr-10">
                    <div class="flex items-center bg-white rounded-[20px] p-1.5 border border-gray-100 shadow-lg w-full sm:w-auto justify-between sm:justify-start">
                        <button onclick="updateQty(-1)" class="w-12 h-12 md:w-14 md:h-14 flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-50 rounded-[15px] transition-all">
                            <i class="fas fa-minus text-[10px]"></i>
                        </button>
                        <input type="number" id="qty" value="1" min="1" class="w-10 md:w-12 text-center font-black text-xl md:text-2xl bg-transparent outline-none pointer-events-none">
                        <button onclick="updateQty(1)" class="w-12 h-12 md:w-14 md:h-14 flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-50 rounded-[15px] transition-all">
                            <i class="fas fa-plus text-[10px]"></i>
                        </button>
                    </div>
                    <div class="flex flex-1 gap-3 md:gap-5 order-first sm:order-none">
                        <button 
                            onclick="addToCart(<?php echo $id; ?>, this, document.getElementById('qty').value)" 
                            <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                            class="flex-1 <?php echo $product['stock'] <= 0 ? 'bg-gray-100 text-gray-300 border-gray-200 cursor-not-allowed' : 'bg-white text-black border-2 border-black hover:bg-[#004F42] hover:border-[#004F42] hover:text-white'; ?> text-base md:text-lg px-6 md:px-8 py-4 md:py-5 rounded-[20px] shadow-lg transition-all duration-500 font-black tracking-tight active:scale-95 group">
                            <?php echo $product['stock'] <= 0 ? 'Out of Bag' : 'Add to Bag'; ?>
                        </button>
                        <?php $is_wishlisted = in_array($id, $wishlist_ids); ?>
                        <button onclick="toggleWishlist(<?php echo $id; ?>, this)" class="w-16 h-16 md:hidden rounded-2xl bg-white border-2 border-gray-100 flex items-center justify-center text-xl <?php echo $is_wishlisted ? 'active text-red-500' : 'text-gray-300'; ?> hover:border-red-500 transition-all shadow-xl">
                            <i class="<?php echo $is_wishlisted ? 'fas' : 'far'; ?> fa-heart"></i>
                        </button>
                    </div>
                    <button 
                        onclick="quickBuy(<?php echo $id; ?>, this, document.getElementById('qty').value)" 
                        <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                        class="w-full sm:flex-1 <?php echo $product['stock'] <= 0 ? 'bg-gray-100 text-gray-300 cursor-not-allowed' : 'bg-[#24B25D] text-white shadow-[0_20px_40px_-10px_rgba(36,178,93,0.3)] hover:scale-[1.02]'; ?> text-base md:text-lg px-8 md:px-12 py-4 md:py-5 rounded-[20px] transition-all duration-500 font-black tracking-tight active:scale-95 group">
                        <?php echo $product['stock'] <= 0 ? 'Sold Out' : 'Quick Buy — <span class="group-hover:translate-x-1 inline-block transition tracking-tighter">₹' . $product['price'] . '</span>'; ?>
                    </button>
                    
                    <button onclick="toggleWishlist(<?php echo $id; ?>, this)" class="hidden md:flex w-16 h-16 rounded-[40px] bg-white border-2 border-gray-100 items-center justify-center text-xl <?php echo $is_wishlisted ? 'active text-red-500' : 'text-gray-300'; ?> hover:border-red-500 hover:text-red-500 transition-all shadow-xl active:scale-90 group/wish">
                        <i class="<?php echo $is_wishlisted ? 'fas' : 'far'; ?> fa-heart group-hover/wish:scale-110 transition-transform"></i>
                    </button>
                </div>

               
                
                <!-- Pincode Checker -->
                <div class="bg-white rounded-[24px] p-6 border border-white/50 shadow-sm mb-6">
                    <h3 class="flex items-center gap-4 text-[10px] font-black uppercase tracking-widest text-gray-900 mb-4 pb-3 border-b border-gray-50">
                        <i class="fas fa-map-marker-alt text-[#19DC7E]"></i> Delivery Check
                    </h3>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" id="pincode_check" placeholder="Enter Pincode" class="flex-1 bg-gray-50 border-none rounded-2xl px-6 py-4 font-bold focus:ring-2 focus:ring-[#19DC7E]">
                        <button onclick="checkPincode()" class="bg-black text-white px-6 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-[#19DC7E] hover:text-black transition-all">Check</button>
                    </div>
                    <div id="pincode_result" class="mt-6 hidden"></div>
                </div>

                <!-- PREMIUM ACCORDIONS -->
                <div class="space-y-4 lg:pr-10">
                    <!-- Ingredients Card -->
                    <div class="bg-white rounded-[24px] p-6 border border-white/50 shadow-sm hover:shadow-lg transition-all duration-500">
                        <h3 class="flex items-center gap-4 text-[10px] font-black uppercase tracking-widest text-gray-900 mb-6 border-b border-gray-50 pb-4">
                            <i class="fas fa-atom text-[#19DC7E]"></i> Composition & Nutrition
                        </h3>
                        
                        <div class="space-y-8">
                            <?php if($product['ingredients']): ?>
                                <div class="anim-up">
                                    <h5 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-5 flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#19DC7E]"></span>
                                        What's Inside?
                                    </h5>
                                    <?php 
                                        $raw_ing = trim($product['ingredients']);
                                        // Handle escaped JSON if it exists
                                        if (strpos($raw_ing, '\"') !== false) $raw_ing = stripslashes($raw_ing);
                                        
                                        $ings = json_decode($raw_ing, true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($ings)): ?>
                                            <div class="flex flex-wrap gap-3">
                                                <?php foreach($ings as $ing): ?>
                                                    <div class="px-5 py-3 bg-white rounded-2xl border border-gray-100 shadow-sm flex items-center gap-3 hover:border-[#19DC7E] hover:shadow-md transition-all group">
                                                        <span class="w-2 h-2 rounded-full bg-[#19DC7E]/20 group-hover:bg-[#19DC7E] transition-colors"></span>
                                                        <span class="text-xs font-black text-gray-700 fredoka uppercase tracking-tight"><?php echo htmlspecialchars($ing); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-xl text-gray-800 font-heading font-medium leading-normal bg-gray-50/50 p-6 rounded-3xl border border-dashed border-gray-100"><?php echo nl2br(htmlspecialchars($product['ingredients'])); ?></p>
                                        <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if($product['nutritional_info']): ?>
                                <div class="anim-up" style="animation-delay: 100ms">
                                    <h5 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-8 flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#19DC7E]"></span>
                                        Nutrition Facts <span class="italic text-[8px] opacity-40 ml-1">(per 100g)</span>
                                    </h5>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <?php 
                                            $raw_nut = trim($product['nutritional_info']);
                                            if (strpos($raw_nut, '\"') !== false) $raw_nut = stripslashes($raw_nut);
                                            
                                            $nut_data = json_decode($raw_nut, true);
                                            
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($nut_data)):
                                                foreach($nut_data as $label => $value):
                                                    if (empty($value)) continue; // Skip empty values

                                                    // Icon & Color Logic
                                                    $l = strtolower($label);
                                                    $icon = 'fa-info-circle'; $color = 'indigo'; $bg = 'bg-indigo-50';
                                                    if (strpos($l, 'energy') !== false) { $icon = 'fa-bolt'; $color = 'yellow-500'; $bg = 'bg-yellow-50'; }
                                                    elseif (strpos($l, 'protein') !== false) { $icon = 'fa-dumbbell'; $color = 'blue-500'; $bg = 'bg-blue-50'; }
                                                    elseif (strpos($l, 'carb') !== false) { $icon = 'fa-wheat-awn'; $color = 'orange-500'; $bg = 'bg-orange-50'; }
                                                    elseif (strpos($l, 'sugar') !== false) { $icon = 'fa-cubes'; $color = 'pink-400'; $bg = 'bg-pink-50'; }
                                                    elseif (strpos($l, 'fat') !== false) { $icon = 'fa-droplet'; $color = 'amber-500'; $bg = 'bg-amber-50'; }
                                                    elseif (strpos($l, 'fiber') !== false) { $icon = 'fa-leaf'; $color = 'green-500'; $bg = 'bg-green-50'; }
                                                    elseif (strpos($l, 'sodium') !== false || strpos($l, 'salt') !== false) { $icon = 'fa-circle-dot'; $color = 'gray-400'; $bg = 'bg-gray-50'; }
                                        ?>
                                            <div class="group bg-white p-5 rounded-[32px] border border-gray-100 hover:border-[#19DC7E] hover:shadow-[0_20px_50px_-20px_rgba(0,0,0,0.05)] transition-all duration-500 flex flex-col items-center text-center relative overflow-hidden">
                                                <div class="w-10 h-10 <?php echo $bg; ?> rounded-2xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110 group-hover:rotate-6">
                                                    <i class="fas <?php echo $icon; ?> <?php echo strpos($color, '-') ? 'text-'.$color : 'text-'.$color.'-500'; ?> text-xs"></i>
                                                </div>
                                                <span class="text-[8px] font-black uppercase tracking-[0.2em] text-gray-400 mb-1"><?php echo htmlspecialchars($label); ?></span>
                                                <span class="text-xl font-black text-gray-900 font-heading tracking-tight"><?php echo htmlspecialchars($value); ?></span>
                                            </div>
                                        <?php 
                                                endforeach;
                                            else:
                                                // Fallback for old data or simple text
                                                $lines = array_filter(explode("\n", str_replace("\r", "", $product['nutritional_info'])));
                                                foreach($lines as $line):
                                                    $parts = explode(':', $line, 2);
                                        ?>
                                            <div class="p-6 bg-gray-50/50 rounded-[32px] border border-transparent hover:bg-white hover:border-gray-100 transition-all">
                                                <?php if(count($parts) === 2): ?>
                                                    <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-1 block"><?php echo htmlspecialchars(trim($parts[0])); ?></span>
                                                    <span class="text-xl font-black text-gray-900 font-heading"><?php echo htmlspecialchars(trim($parts[1])); ?></span>
                                                <?php else: ?>
                                                    <span class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars(trim($line)); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php 
                                                endforeach;
                                            endif;
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Shipping Hint -->
                    <div class="flex items-center gap-6 p-6 bg-[#004F42] text-white rounded-[24px] shadow-lg relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-[#19DC7E] rounded-full blur-[60px] opacity-20 group-hover:opacity-40 transition-opacity"></div>
                        <div class="w-14 h-14 bg-white/10 rounded-full flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform">🚚</div>
                        <div>
                            <h4 class="font-black text-lg uppercase tracking-widest leading-none mb-2">Blazing Delivery</h4>
                            <p class="text-gray-400 text-xs font-medium">Free shipping on orders above ₹499. Naturally packed.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- COMMUNITY STORIES (REVIEWS) -->
        <div id="reviews" class="pt-24 md:pt-40 border-t border-gray-100 px-4 sm:px-0">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col lg:flex-row gap-20 ">
                    <!-- Sidebar Summary -->
                    <div class="lg:w-1/3 lg:sticky lg:top-32">
                        <h2 class="text-4xl sm:text-5xl font-heading font-black text-gray-900 mb-10 leading-[0.8] tracking-tighter anim-up">
                            THE <span class="text-[#24B25D]">DRIYUM</span> <br>DEBATE.
                        </h2>
                        <div class="bg-white rounded-[24px] p-8 md:p-10 shadow-[0_20px_50px_-15px_rgba(0,0,0,0.05)] border border-gray-50 relative overflow-hidden group/card hover:shadow-xl transition-all duration-700 anim-up text-center">
                            <!-- Premium Header Label -->
                            <span class="inline-block text-[9px] font-black uppercase tracking-[0.2em] text-[#24B25D] bg-[#24B25D]/10 px-4 py-1.5 rounded-full mb-8">Community Verdict</span>

                            <div class="flex flex-col items-center mb-10">
                                <div class="inline-flex items-baseline gap-2 mb-2">
                                    <span class="text-7xl md:text-8xl font-black text-gray-900 font-heading tracking-tighter" id="rating-number"><?php echo $total_reviews > 0 ? $avg_rating : '0.0'; ?></span>
                                    <span class="text-xl font-black text-gray-300">/ 5</span>
                                </div>
                                <div class="flex text-yellow-500 gap-1 text-sm mb-2 justify-center">
                                    <?php 
                                    if ($total_reviews > 0):
                                        for($i=1; $i<=5; $i++) {
                                            if($i <= floor($avg_rating)) echo '<i class="fas fa-star"></i>';
                                            elseif($i <= ceil($avg_rating)) echo '<i class="fas fa-star-half-alt"></i>';
                                            else echo '<i class="far fa-star"></i>';
                                        }
                                    else:
                                        for($i=0;$i<5;$i++) echo '<i class="far fa-star text-gray-100"></i>';
                                    endif;
                                    ?>
                                </div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Base on <?php echo $total_reviews; ?> Stories</p>
                            </div>

                           

                            <!-- Thinner Technical Progress Bar -->
                            <div class="px-2 mb-10">
                                <div class="h-1 w-full bg-gray-50 rounded-full overflow-hidden mb-3">
                                    <div id="rating-bar" class="h-full bg-gradient-to-r from-[#24B25D] to-[#17775D] rounded-full shadow-sm transition-all duration-[2s] ease-out" style="width: 0%"></div>
                                </div>
                                <p class="text-[9px] font-black text-gray-300 uppercase tracking-widest">Authenticity Verified</p>
                            </div>

                            <button onclick="document.getElementById('review-form-container').scrollIntoView({behavior:'smooth'})" class="block w-full bg-black text-white py-4 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-[#24B25D] transition-all transform hover:-translate-y-1 active:scale-95 shadow-lg">Tell Your Story</button>
                        </div>
                    </div>

                    <!-- Vertical Story Scroll -->
                    <div class="lg:w-2/3 w-full">
                        <?php if (is_admin() && !empty($reviews)): ?>
                            <div class="mb-8 flex items-center justify-between bg-white p-6 rounded-3xl border border-gray-100 shadow-sm anim-up">
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" id="selectAllReviews" class="w-5 h-5 rounded border-gray-300 text-[#19DC7E] focus:ring-[#19DC7E]">
                                        <span class="text-xs font-black uppercase tracking-widest text-gray-400">Select All</span>
                                    </label>
                                </div>
                                <button type="submit" form="bulkReviewForm" name="delete_reviews" onclick="return confirm('Delete selected reviews forever?')" class="bg-red-50 text-red-500 px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-trash-alt mr-2"></i> Delete Selected
                                </button>
                            </div>
                        <?php endif; ?>

                        <form id="bulkReviewForm" method="POST" class="space-y-12">
                            <input type="hidden" name="delete_reviews" value="1">
                            <?php if(empty($reviews)): ?>
                                <div class="bg-white rounded-3xl p-24 text-center border-2 border-dashed border-gray-100">
                                    <h3 class="text-3xl font-black text-gray-900 mb-4 tracking-tight">No stories told yet.</h3>
                                    <p class="text-gray-400 max-w-xs mx-auto mb-10 text-lg font-medium">Be the pioneer explorer and tell the world how these taste.</p>
                                </div>
                            <?php endif; ?>

                            <?php foreach($reviews as $i => $r): ?>
                                <div class="relative group anim-up" style="animation-delay: <?php echo $i * 100; ?>ms">
                                    <div class="bg-white rounded-[24px] p-8 md:p-12 border border-gray-50 group-hover:border-[#24B25D]/30 shadow-sm group-hover:shadow-[0_60px_100px_-30px_rgba(0,0,0,0.12)] transition-all duration-700 relative z-10">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-8 mb-12">
                                            <div class="flex items-center gap-6">
                                                <?php if (is_admin()): ?>
                                                    <input type="checkbox" name="review_ids[]" value="<?php echo $r['id']; ?>" class="review-selector w-6 h-6 rounded-lg border-gray-100 bg-gray-50 text-[#19DC7E] focus:ring-[#19DC7E] cursor-pointer">
                                                <?php endif; ?>
                                                <div class="w-16 h-16 md:w-20 md:h-20 rounded-[20px] bg-gradient-to-br from-[#24B25D] to-[#14c06e] p-[2px]">
                                                    <div class="w-full h-full bg-white rounded-[26px] flex items-center justify-center font-heading font-black text-2xl text-gray-900">
                                                        <?php echo strtoupper(substr($r['user_name'],0,1)); ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h4 class="font-black text-2xl md:text-3xl text-gray-900 tracking-tighter mb-1"><?php echo htmlspecialchars($r['user_name']); ?></h4>
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-[10px] font-black text-[#24B25D] uppercase tracking-widest">Verified Taster</span>
                                                        <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest">/ <?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <?php if (is_admin()): ?>
                                                    <a href="?slug=<?php echo $slug; ?>&delete_review_id=<?php echo $r['id']; ?>" onclick="return confirm('Erase this story?')" class="w-10 h-10 bg-red-50 text-red-400 rounded-full flex items-center justify-center hover:bg-red-500 hover:text-white transition-all shadow-sm">
                                                        <i class="fas fa-trash-alt text-xs"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <div class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-full">
                                                    <div class="flex text-[#FFD700] text-[10px] gap-0.5">
                                                        <?php for($k=0; $k<$r['rating']; $k++) echo '<i class="fas fa-star"></i>'; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-2xl md:text-4xl text-gray-800 font-heading font-black leading-tight tracking-tighter">
                                            "<?php echo htmlspecialchars($r['comment']); ?>"
                                        </p>
                                    </div>
                                    <?php if($i < count($reviews)-1): ?>
                                        <div class="w-1 h-12 bg-gray-100 mx-auto"></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </form>

                        <script>
                            document.getElementById('selectAllReviews')?.addEventListener('change', function() {
                                document.querySelectorAll('.review-selector').forEach(cb => cb.checked = this.checked);
                            });
                        </script>

                        <!-- Review Form -->
                        <div id="review-form-container" class="mt-32 pt-32 border-t border-gray-100/50">
                            <h3 class="text-3xl sm:text-4xl font-heading font-black text-gray-900 mb-6 tracking-tighter">YOUR <span class="text-[#19DC7E]">VERDICT.</span></h3>
                            <p class="text-gray-400 text-base sm:text-lg font-medium mb-12 max-w-md">Your words echo in the valley. How was the drop?</p>
                            
                            <?php if(is_logged_in()): ?>
                                <form method="POST" class="space-y-12">
                                    <div class="flex flex-wrap gap-4">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <label class="relative cursor-pointer group/star">
                                                <input type="radio" name="rating" value="<?php echo $i; ?>" class="hidden peer" <?php echo $i==5?'checked':''; ?>>
                                                <div class="w-16 h-16 md:w-20 md:h-20 bg-white border-2 border-transparent peer-checked:border-black rounded-2xl md:rounded-3xl flex items-center justify-center text-lg transition-all hover:scale-110 shadow-xl font-black">
                                                    <?php echo $i; ?> ⭐
                                                </div>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                    <textarea name="comment" rows="5" placeholder="Spill the tea... What makes this snack special?" class="w-full bg-white border-none rounded-3xl p-6 md:p-8 font-heading font-medium text-base md:text-lg focus:ring-4 focus:ring-[#24B25D]/10 transition-all placeholder:text-gray-200 shadow-lg"></textarea>
                                    <button type="submit" class="w-full md:w-auto bg-black text-white px-12 md:px-16 py-5 md:py-6 hover:bg-[#24B25D] hover:text-white font-black tracking-widest uppercase rounded-full shadow-xl transition-all active:scale-95">Post Verdict</button>
                                </form>
                            <?php else: ?>
                                <div class="bg-black rounded-3xl p-12 text-center shadow-xl relative overflow-hidden">
                                     <div class="absolute inset-0 bg-gradient-to-br from-[#24B25D]/10 to-transparent"></div>
                                    <h4 class="text-2xl font-black text-white mb-4 tracking-tight italic relative z-10">Explorers Only.</h4>
                                    <p class="text-gray-400 font-medium mb-10 relative z-10">Sign in to share your snacks experience with the community.</p>
                                    <a href="<?php echo get_url('login'); ?>" class="bg-white text-black px-12 py-5 font-black uppercase tracking-widest rounded-xl shadow-xl hover:bg-[#24B25D] transition-colors relative z-10">Sign In Now</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- EXPLORE MORE -->
        <div class="mt-40">
            <div class="flex items-end justify-between mb-16 px-4 sm:px-0">
                <div class="mb-6 md:mb-0">
                    <span class="text-[#24B25D] font-black tracking-widest uppercase text-[9px] md:text-[10px] mb-3 block">Wait, there's more!</span>
                    <h3 class="text-3xl sm:text-3xl font-heading font-black text-gray-900 tracking-tighter leading-none">PEOPLE ALSO GRABBED.</h3>
                </div>
                <a href="<?php echo get_url('shop'); ?>" class="bg-black text-white px-6 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-[#24B25D] hover:text-white transition-all">View All</a>
            </div>
            
            <div class="flex flex-row overflow-x-auto lg:grid lg:grid-cols-4 gap-4 md:gap-8 pb-8 lg:pb-0 hide-scrollbar snap-x snap-mandatory px-4 sm:px-0">
                <?php 
                $i = 0;
                foreach($related as $rp): 
                    $color_raw = $default_bg_colors[$i % count($default_bg_colors)];
                    $i++;
                ?>
                    <div class="flex-none w-[85vw] sm:w-[50%] lg:w-auto group relative anim-up snap-center" style="animation-delay: <?php echo $i * 100; ?>ms">
                        <a href="<?php echo product_url($rp['slug']); ?>" class="block h-full group">
                            <!-- Outer Tinted Container (The Boutique collective Style) -->
                            <div class="rounded-[2rem] md:rounded-[2.5rem] p-2 transition-transform duration-500 group-hover:scale-[1.02] h-full flex flex-col shadow-sm border border-[#004F42]/5" style="background-color: <?php echo $color_raw; ?>;">
                                <!-- Inner White Card -->
                                <div class="bg-white rounded-[1.8rem] md:rounded-[2rem] p-4 px-6 flex flex-col flex-1 h-full shadow-sm">
                                    <!-- Image -->
                                    <div class="relative w-full aspect-square rounded-2xl overflow-hidden mb-4 bg-gray-50/50">
                                        <img src="<?php echo get_url(ltrim($rp['image'], './')); ?>" class="w-full h-full object-contain group-hover:scale-110 transition duration-1000">
                                        <?php if(isset($rp['discount_percentage']) && $rp['discount_percentage'] > 0): ?>
                                            <div class="absolute top-3 left-3 bg-[#EDB02C] text-white text-[8px] font-black px-2 py-1 rounded-md shadow-md">
                                                -<?php echo $rp['discount_percentage']; ?>%
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Stars -->
                                    <div class="flex gap-1 mb-2">
                                        <i class="fas fa-star text-[#EDB12B] text-[8px]"></i>
                                        <i class="fas fa-star text-[#EDB12B] text-[8px]"></i>
                                        <i class="fas fa-star text-[#EDB12B] text-[8px]"></i>
                                        <i class="fas fa-star text-[#EDB12B] text-[8px]"></i>
                                        <i class="fas fa-star text-[#EDB12B] text-[8px]"></i>
                                    </div>
                                    <!-- Info -->
                                    <div class="mb-4">
                                        <h4 class="text-xl font-black text-[#004F42] leading-tight mb-1 group-hover:text-[#24B25D] transition"><?php echo $rp['name']; ?></h4>
                                        <p class="text-gray-400 text-[9px] font-bold uppercase tracking-widest"><?php echo $rp['category_name'] ?? 'Premium Snacks'; ?></p>
                                    </div>
                                    <!-- Price Slot -->
                                    <div class="mt-auto pt-4 flex items-center justify-between">
                                        <span class="text-3xl font-black text-black font-heading leading-none">₹<?php echo $rp['price']; ?></span>
                                        <div class="w-10 h-10 bg-gray-900 text-white rounded-xl flex items-center justify-center group-hover:bg-[#24B25D] transition-colors">
                                            <i class="fas fa-arrow-right text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <script>
    // GA4 View Item Event
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
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

    <?php include 'includes/footer.php'; ?>
    
    <script>
    function updateQty(change) {
        let el = document.getElementById('qty');
        let val = parseInt(el.value) + change;
        if(val < 1) val = 1;
        el.value = val;
    }
    
    let currentThumbIndex = 0;
    const thumbs = document.querySelectorAll('.thumb-item');
    const progressBar = document.getElementById('slideshow-progress');
    let slideshowInterval;
    const slideDuration = 5000;

    function resetProgressBar() {
        progressBar.style.transition = 'none';
        progressBar.style.width = '0%';
        setTimeout(() => {
            progressBar.style.transition = `width ${slideDuration}ms linear`;
            progressBar.style.width = '100%';
        }, 50);
    }

    function startSlideshow() {
        if (thumbs.length <= 1) return;
        resetProgressBar();
        slideshowInterval = setInterval(() => {
            currentThumbIndex = (currentThumbIndex + 1) % thumbs.length;
            const nextThumb = thumbs[currentThumbIndex];
            const nextSrc = nextThumb.querySelector('img').src;
            changeImage(nextSrc, nextThumb, true);
            resetProgressBar();
        }, slideDuration);
    }

    function changeImage(src, thumb, isAuto = false) {
        const img = document.getElementById('mainImage');
        
        // Remove existing animation if any
        img.classList.remove('image-animate-in');
        
        // Liquid out
        img.style.opacity = '0';
        img.style.transform = 'scale(0.9) translateY(-10px) rotate(-1deg)';
        img.style.filter = 'blur(10px)';
        
        setTimeout(() => {
            img.src = src;
            // Pop in
            img.classList.add('image-animate-in');
            img.style.opacity = ''; // Clear inline styles to let class take over
            img.style.transform = '';
            img.style.filter = '';
            
            document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('thumb-active'));
            if(thumb) {
                thumb.classList.add('thumb-active');
                
                // Fix: Scroll only the container, not the whole page
                const container = thumb.parentElement;
                const thumbRect = thumb.getBoundingClientRect();
                const containerRect = container.getBoundingClientRect();
                
                // Calculate offset to center the thumb
                const offset = (thumbRect.left + thumbRect.width / 2) - (containerRect.left + containerRect.width / 2);
                
                container.scrollBy({ left: offset, behavior: 'smooth' });
            }
        }, 200);

        if (!isAuto) {
            clearInterval(slideshowInterval);
            thumbs.forEach((t, i) => { if(t === thumb) currentThumbIndex = i; });
            startSlideshow();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        startSlideshow();
        const showcase = document.getElementById('mainImage').parentElement;
        showcase.addEventListener('mouseenter', () => {
            clearInterval(slideshowInterval);
            progressBar.style.width = '0%'; // Pause visually
        });
        showcase.addEventListener('mouseleave', startSlideshow);

        // Rating Dynamics
        const targetRating = <?php echo (float)$avg_rating; ?>;
        const ratingEl = document.getElementById('rating-number');
        const ratingBar = document.getElementById('rating-bar');
        
        if (ratingEl && targetRating > 0) {
            let current = 0;
            const step = targetRating / 60; // 60 frames
            const counter = setInterval(() => {
                current += step;
                if (current >= targetRating) {
                    current = targetRating;
                    clearInterval(counter);
                }
                ratingEl.textContent = current.toFixed(1);
            }, 16);
            
            setTimeout(() => {
                ratingBar.style.width = (targetRating / 5 * 100) + '%';
            }, 300);
        }
    });

    async function checkPincode() {
        const pincode = document.getElementById('pincode_check').value;
        const resultDiv = document.getElementById('pincode_result');
        const productId = <?php echo $id; ?>;
        
        if(!pincode || pincode.length < 6) {
            alert('Please enter a valid pincode');
            return;
        }

        resultDiv.innerHTML = '<div class="flex items-center gap-3 text-gray-400 font-bold"><i class="fas fa-spinner fa-spin"></i> Checking...</div>';
        resultDiv.classList.remove('hidden');

        try {

            const response = await fetch(`<?php echo get_url('api/shipping.php'); ?>?pincode=${pincode}&product_id=${productId}`);
            const data = await response.json();

            if(data.success && data.methods.length > 0) {
                let html = '<div class="space-y-4">';
                data.methods.forEach(m => {
                    html += `
                        <div class="bg-green-50/50 p-4 rounded-2xl border border-green-100 flex items-center justify-between group hover:bg-green-50 transition-colors">
                            <div>
                                <p class="font-black text-[10px] uppercase tracking-widest text-green-700">${m.display_name}</p>
                                <p class="text-[10px] text-gray-500 font-medium">Delivered in ${m.min_days}-${m.max_days} days</p>
                            </div>
                            <div class="text-right">
                                <p class="font-black text-lg text-gray-900 leading-none">₹${m.cost}</p>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                resultDiv.innerHTML = html;
            } else {
                resultDiv.innerHTML = `<div class="p-4 bg-red-50 text-red-500 rounded-2xl text-[10px] font-black uppercase tracking-widest"><i class="fas fa-exclamation-triangle mr-2"></i> ${data.message || 'Delivery not available'}</div>`;
            }
        } catch(e) {
            resultDiv.innerHTML = '<div class="p-4 bg-red-50 text-red-500 rounded-2xl text-[10px] font-black uppercase tracking-widest">Error checking delivery</div>';
        }
    }

    // Sticky Buy Bar Logic for Desktop
    window.addEventListener('scroll', () => {
        const stickyBar = document.getElementById('desktop-sticky-buy-bar');
        const anchor = document.getElementById('product-title-anchor');
        if (!stickyBar || !anchor) return;

        const rect = anchor.getBoundingClientRect();
        if (rect.top < 0) {
            stickyBar.classList.add('visible');
        } else {
            stickyBar.classList.remove('visible');
        }
    });


    </script>
</body>
</html>
