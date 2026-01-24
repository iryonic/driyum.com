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

// Background Color Logic
$bg_options = ['#FFFBEB', '#F0FDFA', '#FEF2F2', '#F5F3FF', '#ECFDF5', '#FFF7ED', '#FDF2F8'];
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php render_seo_tags($product['name'], $product['description'], $product['image']); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo get_url('assets/css/chunky.css'); ?>">

    <style>
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #fffbeb; }
        ::-webkit-scrollbar-thumb { background: #19DC7E; border-radius: 10px; }
        
        .product-gradient-bg {
            background: <?php echo $page_bg_dark; ?>;
            background: radial-gradient(circle at 50% 50%, rgba(25, 220, 126, 0.05) 0%, <?php echo $page_bg_dark; ?> 100%);
        }

        .thumb-active { 
            border-color: #19DC7E !important; 
            transform: scale(1.1) rotate(2deg); 
            box-shadow: 0 10px 20px rgba(25, 220, 126, 0.2); 
        }

        .floating-badge {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(5deg); }
        }

        .benefit-card:hover i {
            transform: scale(1.2) rotate(-10deg);
        }

        .nutrition-item:hover {
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .sticky-mobile-bar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(20px);
                padding: 1rem;
                display: flex;
                gap: 1rem;
                box-shadow: 0 -10px 40px rgba(0,0,0,0.08);
                z-index: 9999;
                border-radius: 24px 24px 0 0;
                animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            }
            @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
            body { padding-bottom: 100px; }
        }
    </style>
</head>
<body class="product-gradient-bg min-h-screen">

    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-6 py-8 relative z-10 font-['Outfit']">
        
        <!-- BREADCRUMBS -->
        <nav class="flex items-center gap-2 mb-10 text-[10px] font-black tracking-[0.2em] uppercase text-gray-400">
            <a href="<?php echo get_url(''); ?>" class="hover:text-[#19DC7E] transition-colors">Home</a>
            <span class="opacity-30">/</span>
            <a href="<?php echo get_url('shop'); ?>" class="hover:text-[#19DC7E] transition-colors">Snacks</a>
            <span class="opacity-30">/</span>
            <span class="text-gray-900"><?php echo $product['name']; ?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-20 mb-16 lg:mb-24">
            
            <?php
            // Fetch Gallery Images
            $gallery = fetch_all("SELECT * FROM product_images WHERE product_id = $id ORDER BY sort_order");
            ?>
            <!-- LEFT: VISUALS -->
            <div class="space-y-8 lg:sticky lg:top-32 h-fit">
                <!-- Main Showcase -->
                <div class="relative group">
                    <!-- Decor Blobs -->
                    <div class="absolute -top-10 -left-10 w-64 h-64 bg-[#19DC7E]/10 rounded-full blur-[100px] animate-pulse"></div>
                    <div class="absolute -bottom-10 -right-10 w-64 h-64 bg-yellow-400/10 rounded-full blur-[100px] animate-pulse delay-1000"></div>

                    <div class="aspect-[4/5] bg-white rounded-[40px] md:rounded-[60px] p-6 md:p-16 border border-white/40 shadow-[0_40px_100px_-20px_rgba(0,0,0,0.05)] flex items-center justify-center relative overflow-hidden backdrop-blur-sm">
                        <img id="mainImage" src="<?php echo get_url(ltrim($product['image'], './')); ?>" class="w-full h-full object-contain transform group-hover:scale-110 transition-transform duration-1000 z-10 drop-shadow-[0_20px_50px_rgba(0,0,0,0.15)] <?php echo $product['stock'] <= 0 ? 'grayscale' : ''; ?>">
                        
                        <!-- Premium Interactive Label -->
                        <div class="absolute top-6 right-6 md:top-10 md:right-10 flex flex-col items-end gap-3 z-20">
                            <?php if($product['is_new']): ?>
                                <span class="bg-black text-white px-3 md:px-5 py-1.5 md:py-2.5 rounded-full text-[8px] md:text-[9px] font-black uppercase tracking-widest shadow-2xl floating-badge">Fresh Drop</span>
                            <?php endif; ?>
                            <span class="bg-[#19DC7E] text-black px-3 md:px-5 py-1.5 md:py-2.5 rounded-full text-[8px] md:text-[9px] font-black uppercase tracking-widest shadow-2xl floating-badge delay-700">100% Organic</span>
                        </div>

                        <!-- Zoom Indicator -->
                        <div class="absolute bottom-10 left-10 w-12 h-12 bg-white/80 backdrop-blur-md rounded-full shadow-lg flex items-center justify-center text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity translate-y-4 group-hover:translate-y-0 duration-500 cursor-zoom-in">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                </div>

                <!-- Vertical/Horizontal Gallery Refined -->
                <div class="flex gap-4 overflow-x-auto hide-scrollbar py-2 px-1 snap-x snap-mandatory scroll-pl-1">
                    <div onclick="changeImage('<?php echo get_url(ltrim($product['image'], './')); ?>', this)" class="thumb-item thumb-active w-24 h-24 rounded-[30px] bg-white border-2 border-transparent p-2 cursor-pointer shrink-0 transition-all shadow-sm">
                        <img src="<?php echo get_url(ltrim($product['image'], './')); ?>" class="w-full h-full object-contain">
                    </div>
                    <?php foreach($gallery as $img): ?>
                        <div onclick="changeImage('<?php echo get_url(ltrim($img['image_path'], './')); ?>', this)" class="thumb-item w-24 h-24 rounded-[30px] bg-white border-2 border-transparent p-2 cursor-pointer shrink-0 transition-all hover:scale-105 shadow-sm">
                            <img src="<?php echo get_url(ltrim($img['image_path'], './')); ?>" class="w-full h-full object-contain">
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Quick Benefits Grid -->
                <div class="grid grid-cols-3 gap-4 pt-4">
                    <div class="bg-white/50 backdrop-blur-sm p-5 rounded-[32px] border border-white/20 text-center benefit-card transition-all group">
                        <div class="w-12 h-12 bg-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-3 transition-transform">☀️</div>
                        <span class="block text-[9px] font-black uppercase tracking-widest text-gray-400">Sun Dried</span>
                    </div>
                    <div class="bg-white/50 backdrop-blur-sm p-5 rounded-[32px] border border-white/20 text-center benefit-card transition-all group">
                        <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-3 transition-transform">🍃</div>
                        <span class="block text-[9px] font-black uppercase tracking-widest text-gray-400">Pure Vegan</span>
                    </div>
                    <div class="bg-white/50 backdrop-blur-sm p-5 rounded-[32px] border border-white/20 text-center benefit-card transition-all group">
                        <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-3 transition-transform">📦</div>
                        <span class="block text-[9px] font-black uppercase tracking-widest text-gray-400">Eco Pack</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT: DETAILS -->
            <div class="lg:pt-10">
                <!-- Status & Social Proof Header -->
                <div class="flex items-center gap-4 mb-8">
                    <div class="flex -space-x-3">
                        <img src="https://i.pravatar.cc/100?u=1" class="w-8 h-8 rounded-full border-2 border-white shadow-sm">
                        <img src="https://i.pravatar.cc/100?u=2" class="w-8 h-8 rounded-full border-2 border-white shadow-sm">
                        <img src="https://i.pravatar.cc/100?u=3" class="w-8 h-8 rounded-full border-2 border-white shadow-sm">
                    </div>
                    <span class="text-xs font-bold text-gray-500">
                        <span class="text-black font-black">28 people</span> viewing right now
                    </span>
                </div>

                <div class="mb-10">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="flex text-yellow-400 text-xs gap-0.5">
                            <?php for($i=0;$i<5;$i++) echo '<i class="fas fa-star"></i>'; ?>
                        </div>
                        <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest ml-2">4.9 (<?php echo count($reviews); ?> Reviews)</span>
                    </div>
                    <h1 class="text-4xl sm:text-6xl md:text-8xl font-['Fredoka'] font-black text-gray-900 mb-6 leading-[0.9] tracking-tighter uppercase whitespace-pre-wrap"><?php echo $product['name']; ?></h1>
                    
                    <div class="flex items-center gap-6">
                        <span class="text-5xl sm:text-7xl font-black text-gray-900 font-['Fredoka'] tracking-tighter leading-none">₹<?php echo $product['price']; ?></span>
                        <?php if($product['original_price'] > $product['price']): ?>
                            <div class="flex flex-col">
                                <span class="text-xl sm:text-2xl text-gray-300 line-through font-bold leading-none italic">₹<?php echo $product['original_price']; ?></span>
                                <span class="text-[#FF6B6B] font-black text-[9px] uppercase tracking-widest mt-1">Save <?php echo round((($product['original_price']-$product['price'])/$product['original_price'])*100); ?>% Today</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="text-xl sm:text-2xl text-gray-500 font-medium mb-12 leading-relaxed max-w-xl font-['Fredoka'] tracking-tight">
                    <?php echo $product['description']; ?>
                </p>

                <!-- Stock Scarcity Bar -->
                <div class="mb-12 max-w-sm">
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
                <div class="flex flex-col sm:flex-row gap-5 mb-16 lg:pr-10">
                    <div class="flex items-center bg-white rounded-[40px] p-2 border-2 border-transparent shadow-xl ring-1 ring-gray-100">
                        <button onclick="updateQty(-1)" class="w-16 h-16 flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-50 rounded-full transition-all">
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <input type="number" id="qty" value="1" min="1" class="w-14 text-center font-black text-2xl bg-transparent outline-none pointer-events-none">
                        <button onclick="updateQty(1)" class="w-16 h-16 flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-50 rounded-full transition-all">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                    <button 
                        onclick="addToCart(<?php echo $id; ?>, this, document.getElementById('qty').value)" 
                        <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                        class="flex-1 <?php echo $product['stock'] <= 0 ? 'bg-gray-100 text-gray-300 border-gray-200 cursor-not-allowed' : 'bg-white text-black border-2 border-black hover:bg-amber-400 hover:border-amber-500 hover:text-white'; ?> text-lg px-8 py-6 rounded-[40px] shadow-xl transition-all duration-500 font-black tracking-tight active:scale-95 group">
                        <?php echo $product['stock'] <= 0 ? 'Out of Bag' : 'Add to Bag'; ?>
                    </button>
                    <button 
                        onclick="quickBuy(<?php echo $id; ?>, this, document.getElementById('qty').value)" 
                        <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                        class="flex-1 <?php echo $product['stock'] <= 0 ? 'bg-gray-100 text-gray-300 cursor-not-allowed' : 'bg-[#19DC7E] text-black shadow-[0_30px_60px_-15px_rgba(25,220,126,0.3)] hover:scale-105'; ?> text-lg px-12 py-6 rounded-[40px] transition-all duration-500 font-black tracking-tight active:scale-95 group">
                        <?php echo $product['stock'] <= 0 ? 'Sold Out' : 'Quick Buy — <span class="group-hover:translate-x-1 inline-block transition">₹' . $product['price'] . '</span>'; ?>
                    </button>
                    
                    <?php $is_wishlisted = in_array($id, $wishlist_ids); ?>
                    <button onclick="toggleWishlist(<?php echo $id; ?>, this)" class="w-16 h-16 rounded-[40px] bg-white border-2 border-gray-100 flex items-center justify-center text-xl <?php echo $is_wishlisted ? 'active text-red-500' : 'text-gray-300'; ?> hover:border-red-500 hover:text-red-500 transition-all shadow-xl active:scale-90 group/wish">
                        <i class="<?php echo $is_wishlisted ? 'fas' : 'far'; ?> fa-heart group-hover/wish:scale-110 transition-transform"></i>
                    </button>
                </div>

                <!-- STICKY MOBILE BAR -->
                <div class="sticky-mobile-bar md:hidden">
                    <div class="flex-1">
                        <p class="text-[10px] font-black uppercase text-gray-400 mb-1">Total</p>
                        <p class="text-2xl font-black text-gray-900">₹<?php echo $product['price']; ?></p>
                    </div>
                    <button onclick="addToCart(<?php echo $id; ?>, this, 1)" class="bg-[#111827] text-white rounded-3xl font-black uppercase tracking-widest px-8 py-5 shadow-xl active:bg-[#19DC7E] active:text-black transition-all">
                        Quick Add
                    </button>
                </div>
                
                <!-- Pincode Checker -->
                <div class="bg-white rounded-[40px] p-8 border border-white/50 shadow-sm mb-6">
                    <h3 class="flex items-center gap-4 text-sm font-black uppercase tracking-widest text-gray-900 mb-6 pb-4 border-b border-gray-50">
                        <i class="fas fa-map-marker-alt text-[#19DC7E]"></i> Delivery Check
                    </h3>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" id="pincode_check" placeholder="Enter Pincode" class="flex-1 bg-gray-50 border-none rounded-2xl px-6 py-4 font-bold focus:ring-2 focus:ring-[#19DC7E]">
                        <button onclick="checkPincode()" class="bg-black text-white px-6 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-[#19DC7E] hover:text-black transition-all">Check</button>
                    </div>
                    <div id="pincode_result" class="mt-6 hidden"></div>
                </div>

                <!-- PREMIUM ACCORDIONS -->
                <div class="space-y-6 lg:pr-10">
                    <!-- Ingredients Card -->
                    <div class="bg-white rounded-[40px] p-8 border border-white/50 shadow-sm hover:shadow-xl transition-all duration-500">
                        <h3 class="flex items-center gap-4 text-sm font-black uppercase tracking-widest text-gray-900 mb-8 border-b border-gray-50 pb-6">
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
                                            <p class="text-xl text-gray-800 font-['Fredoka'] font-medium leading-normal bg-gray-50/50 p-6 rounded-3xl border border-dashed border-gray-100"><?php echo nl2br(htmlspecialchars($product['ingredients'])); ?></p>
                                        <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if($product['nutritional_info']): ?>
                                <div class="anim-up" style="animation-delay: 100ms">
                                    <h5 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-6 flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-400"></span>
                                        Nutrition Facts <span class="italic text-[8px] opacity-50 ml-1">(per 100g)</span>
                                    </h5>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                        <?php 
                                            $raw_nut = trim($product['nutritional_info']);
                                            if (strpos($raw_nut, '\"') !== false) $raw_nut = stripslashes($raw_nut);
                                            
                                            $nut_data = json_decode($raw_nut, true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($nut_data)):
                                                foreach($nut_data as $label => $value):
                                        ?>
                                            <div class="bg-gray-50/50 p-5 rounded-[28px] border border-transparent hover:border-orange-200 hover:bg-white transition-all text-center">
                                                <span class="text-[8px] font-black uppercase tracking-[0.15em] text-gray-400 block mb-2"><?php echo htmlspecialchars($label); ?></span>
                                                <span class="text-2xl font-black text-gray-900 font-['Fredoka'] tracking-tighter"><?php echo htmlspecialchars($value); ?></span>
                                            </div>
                                        <?php 
                                                endforeach;
                                            else:
                                                // Fallback for old multi-line text
                                                $lines = array_filter(explode("\n", str_replace("\r", "", $product['nutritional_info'])));
                                                foreach($lines as $line):
                                                    $parts = explode(':', $line, 2);
                                        ?>
                                            <div class="p-5 bg-gray-50/50 rounded-3xl border border-transparent hover:bg-white transition-all">
                                                <?php if(count($parts) === 2): ?>
                                                    <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-1 block"><?php echo htmlspecialchars(trim($parts[0])); ?></span>
                                                    <span class="text-xl font-black text-gray-900 font-['Fredoka']"><?php echo htmlspecialchars(trim($parts[1])); ?></span>
                                                <?php else: ?>
                                                    <span class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars(trim($line)); ?></span>
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
                    <div class="flex items-center gap-6 p-8 bg-black text-white rounded-[40px] shadow-2xl relative overflow-hidden group">
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
        <div id="reviews" class="pt-40 border-t border-gray-100">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col lg:flex-row gap-20 items-start">
                    <!-- Sidebar Summary -->
                    <div class="lg:w-1/3 lg:sticky lg:top-32">
                        <h2 class="text-6xl sm:text-8xl font-['Fredoka'] font-black text-gray-900 mb-10 leading-[0.8] tracking-tighter anim-up">
                            THE <br><span class="text-[#19DC7E]">DRIYUM</span> <br>DEBATE.
                        </h2>
                        <div class="bg-white rounded-[60px] p-12 shadow-[0_50px_100px_-20px_rgba(0,0,0,0.06)] border border-gray-50 relative overflow-hidden group hover:shadow-2xl transition-all duration-700 anim-up text-center">
                            <div class="inline-flex items-baseline gap-2 mb-4">
                                <span class="text-8xl font-black text-gray-900 font-['Fredoka'] tracking-tighter">4.9</span>
                                <span class="text-2xl font-black text-[#19DC7E]">/5</span>
                            </div>
                            <div class="flex justify-center text-[#FFD700] text-lg gap-1 mb-6">
                                <?php for($i=0;$i<5;$i++) echo '<i class="fas fa-star drop-shadow-sm"></i>'; ?>
                            </div>
                            <div class="h-1.5 w-full bg-gray-50 rounded-full overflow-hidden mb-10">
                                <div class="h-full bg-gradient-to-r from-[#19DC7E] to-[#14c06e] w-[98%] rounded-full shadow-[0_0_10px_rgba(25,220,126,0.3)]"></div>
                            </div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-10">Authentic Stories</p>

                            <button onclick="document.getElementById('review-form-container').scrollIntoView({behavior:'smooth'})" class="w-full bg-gray-50 hover:bg-black hover:text-white py-6 rounded-3xl text-[10px] font-black uppercase tracking-widest transition-all">Share Your Experience</button>
                        </div>
                    </div>

                    <!-- Vertical Story Scroll -->
                    <div class="lg:w-2/3 w-full">
                        <?php if (is_admin() && !empty($reviews)): ?>
                            <div class="mb-8 flex items-center justify-between bg-white p-6 rounded-[30px] border border-gray-100 shadow-sm anim-up">
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
                                <div class="bg-white rounded-[50px] p-24 text-center border-2 border-dashed border-gray-100">
                                    <h3 class="text-3xl font-black text-gray-900 mb-4 tracking-tight">No stories told yet.</h3>
                                    <p class="text-gray-400 max-w-xs mx-auto mb-10 text-lg font-medium">Be the pioneer explorer and tell the world how these taste.</p>
                                </div>
                            <?php endif; ?>

                            <?php foreach($reviews as $i => $r): ?>
                                <div class="relative group anim-up" style="animation-delay: <?php echo $i * 100; ?>ms">
                                    <div class="bg-white rounded-[50px] p-8 md:p-16 border border-gray-50 group-hover:border-[#19DC7E]/30 shadow-sm group-hover:shadow-[0_60px_100px_-30px_rgba(0,0,0,0.12)] transition-all duration-700 relative z-10">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-8 mb-12">
                                            <div class="flex items-center gap-6">
                                                <?php if (is_admin()): ?>
                                                    <input type="checkbox" name="review_ids[]" value="<?php echo $r['id']; ?>" class="review-selector w-6 h-6 rounded-lg border-gray-100 bg-gray-50 text-[#19DC7E] focus:ring-[#19DC7E] cursor-pointer">
                                                <?php endif; ?>
                                                <div class="w-16 h-16 md:w-20 md:h-20 rounded-[28px] bg-gradient-to-br from-[#19DC7E] to-[#14c06e] p-[2px]">
                                                    <div class="w-full h-full bg-white rounded-[26px] flex items-center justify-center font-['Fredoka'] font-black text-2xl text-gray-900">
                                                        <?php echo strtoupper(substr($r['user_name'],0,1)); ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h4 class="font-black text-2xl md:text-3xl text-gray-900 tracking-tighter mb-1"><?php echo htmlspecialchars($r['user_name']); ?></h4>
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-[10px] font-black text-[#19DC7E] uppercase tracking-widest">Verified Taster</span>
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
                                        <p class="text-3xl md:text-5xl text-gray-800 font-['Fredoka'] font-black leading-tight tracking-tighter">
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
                            <h3 class="text-4xl sm:text-5xl font-['Fredoka'] font-black text-gray-900 mb-6 tracking-tighter">YOUR <span class="text-[#19DC7E]">VERDICT.</span></h3>
                            <p class="text-gray-400 text-lg sm:text-xl font-medium mb-12 max-w-md">Your words echo in the valley. How was the drop?</p>
                            
                            <?php if(is_logged_in()): ?>
                                <form method="POST" class="space-y-12">
                                    <div class="flex flex-wrap gap-4">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <label class="relative cursor-pointer group/star">
                                                <input type="radio" name="rating" value="<?php echo $i; ?>" class="hidden peer" <?php echo $i==5?'checked':''; ?>>
                                                <div class="w-16 h-16 md:w-20 md:h-20 bg-white border-2 border-transparent peer-checked:border-black rounded-2xl md:rounded-[28px] flex items-center justify-center text-lg transition-all hover:scale-110 shadow-xl font-black">
                                                    <?php echo $i; ?> ⭐
                                                </div>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                    <textarea name="comment" rows="5" placeholder="Spill the tea... What makes this snack special?" class="w-full bg-white border-none rounded-[30px] md:rounded-[40px] p-6 md:p-10 font-['Fredoka'] font-medium text-lg md:text-2xl focus:ring-4 focus:ring-[#19DC7E]/10 transition-all placeholder:text-gray-200 shadow-xl"></textarea>
                                    <button type="submit" class="w-full md:w-auto bg-black text-white px-12 md:px-20 py-6 md:py-8 hover:bg-[#19DC7E] hover:text-black font-black tracking-widest uppercase rounded-3xl md:rounded-full shadow-2xl transition-all active:scale-95">Post Verdict</button>
                                </form>
                            <?php else: ?>
                                <div class="bg-black rounded-[50px] p-16 text-center shadow-2xl relative overflow-hidden">
                                     <div class="absolute inset-0 bg-gradient-to-br from-[#19DC7E]/10 to-transparent"></div>
                                    <h4 class="text-3xl font-black text-white mb-6 tracking-tight italic relative z-10">Explorers Only.</h4>
                                    <p class="text-gray-400 font-medium mb-12 relative z-10">Sign in to share your snacks experience with the community.</p>
                                    <a href="<?php echo get_url('login'); ?>" class="bg-white text-black px-12 py-5 font-black uppercase tracking-widest rounded-3xl shadow-xl hover:bg-[#19DC7E] transition-colors relative z-10">Sign In Now</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- EXPLORE MORE -->
        <div class="mt-40">
            <div class="flex items-end justify-between mb-16">
                <div class="mb-10 md:mb-0">
                    <span class="text-[#19DC7E] font-black tracking-widest uppercase text-[10px] md:text-xs mb-4 block">Wait, there's more!</span>
                    <h3 class="text-4xl sm:text-6xl font-['Fredoka'] font-black text-gray-900 tracking-tighter leading-none">PEOPLE <br>ALSO GRABBED.</h3>
                </div>
                <a href="<?php echo get_url('shop'); ?>" class="bg-black text-white px-8 py-4 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-[#19DC7E] hover:text-black transition-all">Hunt All</a>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
                <?php 
                $colors = ['card-color-1', 'card-color-2', 'card-color-3', 'card-color-4'];
                foreach($related as $i => $rp): 
                    $current_color = $colors[$i % count($colors)];
                ?>
                    <div class="group relative anim-up" style="animation-delay: <?php echo $i * 100; ?>ms">
                        <a href="<?php echo product_url($rp['slug']); ?>" class="block">
                            <div class="<?php echo $current_color; ?> rounded-[40px] aspect-square mb-6 overflow-hidden relative p-8 border-2 border-transparent group-hover:border-[#19DC7E] transition-all duration-500 shadow-sm hover:shadow-2xl">
                                <img src="<?php echo get_url(ltrim($rp['image'], './')); ?>" class="w-full h-full object-contain group-hover:scale-110 transition duration-1000">
                                <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition duration-500"></div>
                            </div>
                            <h4 class="text-2xl font-black font-['Fredoka'] text-gray-900 mb-2 truncate group-hover:text-[#19DC7E] transition"><?php echo $rp['name']; ?></h4>
                            <div>
                                <span class="text-3xl font-black text-gray-900 font-['Fredoka']">₹<?php echo $rp['price']; ?></span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
    function updateQty(change) {
        let el = document.getElementById('qty');
        let val = parseInt(el.value) + change;
        if(val < 1) val = 1;
        el.value = val;
    }
    
    function changeImage(src, thumb) {
        document.getElementById('mainImage').src = src;
        document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('thumb-active'));
        if(thumb) thumb.classList.add('thumb-active');
    }

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

    </script>
</body>
</html>
