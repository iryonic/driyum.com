<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Filter Logic from URL
$cat_slug = isset($_GET['cat']) ? $_GET['cat'] : null;
$search = isset($_GET['q']) ? trim($_GET['q']) : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Pagination
$limit = 20;
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page - 1) * $limit;

// Build Query
$sql_base = " FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE p.is_active = 1";

$where_clause = "";
$params = [];
$types = "";

if ($cat_slug) {
    if ($cat_slug === 'best-sellers') {
         $where_clause .= " AND p.is_featured = 1";
    } else {
         $cat_row = fetch_one("SELECT id FROM categories WHERE slug = ?", [$cat_slug]);
         if ($cat_row) {
             if ($cat_slug === 'combos') {
                 $where_clause .= " AND (p.category_id = ? OR p.is_combo = 1)";
                 $params[] = $cat_row['id'];
                 $types .= "i";
             } else {
                 $where_clause .= " AND p.category_id = ?";
                 $params[] = $cat_row['id'];
                 $types .= "i";
             }
         }
    }
}

if ($search) {
    $where_clause .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
}

// Total Count for Pagination
$count_res = fetch_one("SELECT COUNT(*) as total" . $sql_base . $where_clause, $params);
$total_items = $count_res['total'];
$total_pages = ceil($total_items / $limit);

// Sorting
$order_by = "";
switch ($sort) {
    case 'price_asc': $order_by = " ORDER BY CASE WHEN p.stock > 0 THEN 0 ELSE 1 END ASC, p.price ASC"; break;
    case 'price_desc': $order_by = " ORDER BY CASE WHEN p.stock > 0 THEN 0 ELSE 1 END ASC, p.price DESC"; break;
    case 'newest': $order_by = " ORDER BY CASE WHEN p.stock > 0 THEN 0 ELSE 1 END ASC, p.created_at DESC"; break;
    default: $order_by = " ORDER BY CASE WHEN p.stock > 0 THEN 0 ELSE 1 END ASC, p.sort_order ASC, p.id DESC"; break;
}

$sql = "SELECT p.*, c.name as category_name" . $sql_base . $where_clause . $order_by . " LIMIT $limit OFFSET $offset";
$products = fetch_all($sql, $params);
$categories = fetch_all("SELECT * FROM categories ORDER BY sort_order ASC");

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
    <?php 
    $page_title = 'The Snack Shop';
    if($search) $page_title = "Searching for '$search'";
    elseif($cat_slug) {
        $cat_display = str_replace('-', ' ', $cat_slug);
        $page_title = ucwords($cat_display) . " Collection";
    }
    
    $page_description = "Browse our collection of premium, healthy snacks. Fresh from the valley, 100% organic snacks delivered to your doorstep.";
    if($cat_slug) $page_description = "Explore our premium " . str_replace('-', ' ', $cat_slug) . " collection. Hand-picked and organic Kashmiri delicacies.";
    
    include 'includes/head.php'; 
    ?>

    <style>
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: var(--color-bg); }
        ::-webkit-scrollbar-thumb { background: var(--color-primary); border-radius: 10px; border: 3px solid var(--color-bg); }
        
        .card-color-1 { background-color: #E0F2FE; border-color: #BAE6FD !important; } /* Sky */
        .card-color-2 { background-color: #DCFCE7; border-color: #BBF7D0 !important; } /* Green */
        .card-color-3 { background-color: #FEF9C3; border-color: #FEF08A !important; } /* Yellow */
        .card-color-4 { background-color: #FFEDD5; border-color: #FED7AA !important; } /* Orange */
        .card-color-5 { background-color: #F3E8FF; border-color: #E9D5FF !important; } /* Purple */
        .card-color-6 { background-color: #FFE4E6; border-color: #FECDD3 !important; } /* Rose */
    </style>
</head>
<body class="bg-white">

    <?php include 'includes/header.php'; ?>

    <!-- ENHANCED HERO HEADER -->
    <header class="relative py-10 text-center px-4 overflow-hidden">
        <!-- Subtle Gradient -->
        <div class="absolute inset-0 bg-gradient-to-b from-green-50/30 to-transparent -z-10"></div>

        <div class="container mx-auto relative z-10">
            <h1 class="text-5xl md:text-7xl font-heading font-black text-gray-900 mb-4 tracking-tight">
                <?php 
                    if ($search) echo "Searching: <span class='text-[#24B25D]'>'$search'</span>";
                    elseif ($cat_slug) echo str_replace('-', ' ', $cat_slug) . "<span class='text-[#24B25D]'>.</span>";
                    else echo "The <span class='text-[#24B25D]'>Snack</span> Shop";
                ?>
            </h1>
           
        </div>
    </header>

    <div class="container mx-auto px-4 pb-20 flex flex-col lg:flex-row gap-8 lg:gap-12">
        
        <!-- SIDEBAR FILTERS (Desktop) -->
        <aside class="w-full lg:w-72 flex-shrink-0 hidden lg:block">
            <div class="sticky top-24 space-y-8">
                <div class="card-chunky p-6 bg-white/50 backdrop-blur-xl border-white/40">
                    <h3 class="font-bold text-xl mb-5 font-heading flex items-center gap-2 text-gray-900">
                        <i class="fas fa-filter text-[#24B25D] text-sm"></i> Categories
                    </h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="<?php echo get_url('shop'); ?>" class="flex items-center justify-between py-3 px-4 rounded-2xl font-bold font-sans transition-all <?php echo !$cat_slug ? 'bg-[#24B25D] text-black shadow-lg shadow-green-500/20 scale-105' : 'text-gray-500 hover:bg-white hover:text-[#24B25D] hover:translate-x-1'; ?>">
                                <span>All Packs</span>
                                <?php if(!$cat_slug): ?> <i class="fas fa-check-circle"></i> <?php endif; ?>
                            </a>
                        </li>
                        <?php foreach ($categories as $c): ?>
                        <li>
                            <a href="<?php echo category_url($c['slug']); ?>" class="flex items-center justify-between py-3 px-4 rounded-2xl font-bold font-sans transition-all <?php echo $cat_slug === $c['slug'] ? 'bg-[#24B25D] text-black shadow-lg shadow-green-500/20 scale-105' : 'text-gray-500 hover:bg-white hover:text-[#24B25D] hover:translate-x-1'; ?>">
                                <span><?php echo $c['name']; ?></span>
                                <?php if($cat_slug === $c['slug']): ?> <i class="fas fa-check-circle"></i> <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="card-chunky p-6">
                    <h3 class="font-bold text-xl mb-4 font-heading">Sort Collection</h3>
                    <div class="relative group">
                        <select onchange="window.location.href=this.value" class="input-chunky text-sm p-4 bg-gray-50 border-gray-100 appearance-none cursor-pointer">
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'default'])); ?>" <?php echo $sort == 'default' ? 'selected' : ''; ?>>Featured / Default</option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest Arrival</option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_asc'])); ?>" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_desc'])); ?>" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none group-hover:text-[#24B25D] transition"></i>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MOBILE FILTER DROPDOWN -->
        <div class="lg:hidden w-full">
            <select onchange="window.location.href=this.value" class="input-chunky mb-6">
                <option value="<?php echo get_url('shop'); ?>">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?php echo category_url($c['slug']); ?>" <?php echo $cat_slug === $c['slug'] ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- PRODUCT GRID -->
        <div class="flex-1 scroll-reveal">
            <?php if (count($products) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php 
                    $i = 0;
                    $default_colors = ['#E0F2FE', '#DCFCE7', '#FEF3C7', '#FEE2E2', '#F3E8FF', '#FFEDD5'];
                    foreach ($products as $p): 
                        $db_color = !empty($p['bg_color']) ? $p['bg_color'] : null;
                        $color_raw = $db_color ?: $default_colors[$i % count($default_colors)];
                        $i++;
                    ?>
                        <!-- Premium Boutique Card -->
                        <div class="group relative anim-up" style="animation-delay: <?php echo $i * 50; ?>ms">
                            <a href="<?php echo product_url($p['slug']); ?>" class="block h-full group/card transition-transform duration-500 hover:scale-[1.02]">
                                <!-- Outer Tinted Container -->
                                <div class="rounded-[2.5rem] p-3 h-full flex flex-col shadow-sm border border-[#004F42]/5 transition-all duration-500 group-hover/card:shadow-xl" style="background-color: <?php echo $color_raw; ?>;">
                                    
                                    <!-- Inner White Card -->
                                    <div class="bg-white rounded-[2rem] p-4 px-6 flex flex-col flex-1 h-full shadow-sm">
                                        
                                        <!-- Product Image -->
                                        <div class="relative w-full aspect-[4/3] rounded-2xl overflow-hidden mb-6 bg-gray-50/50">
                                            <img src="<?php echo get_url(ltrim($p['image'], './')); ?>" 
                                                 loading="lazy"
                                                 alt="<?php echo htmlspecialchars($p['name']); ?>"
                                                 class="w-full h-full object-contain group-hover/card:scale-110 transition-transform duration-1000 <?php echo $p['stock'] <= 0 ? 'grayscale opacity-75' : ''; ?>">
                                            
                                            <!-- deal badge -->
                                            <?php if(isset($p['discount_percentage']) && $p['discount_percentage'] > 0): ?>
                                                <div class="absolute top-4 left-4 bg-[#EDB02C] text-white text-[9px] font-black px-3 py-1.5 rounded-lg shadow-md">
                                                    -<?php echo $p['discount_percentage']; ?>%
                                                </div>
                                            <?php endif; ?>

                                            <!-- Stock Status -->
                                            <?php if($p['stock'] <= 0): ?>
                                                <div class="absolute inset-0 bg-white/40 backdrop-blur-[2px] flex items-center justify-center">
                                                    <span class="bg-white text-black text-[10px] font-black px-4 py-2 rounded-full shadow-xl uppercase tracking-widest border border-gray-100">out of stock</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Stars & Branding -->
                                        <div class="flex gap-1 mb-3">
                                            <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                            <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                            <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                            <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                            <i class="fas fa-star text-[#EDB12B] text-[10px]"></i>
                                        </div>

                                        <!-- Title & Specs -->
                                        <div class="mb-6">
                                            <h3 class="text-2xl font-black text-[#004F42] leading-tight mb-2 group-hover/card:text-[#24B25D] transition-colors"><?php echo $p['name']; ?></h3>
                                            <p class="text-gray-400 text-[10px] font-bold uppercase tracking-[0.2em]">
                                                <?php echo !empty($p['category_name']) ? $p['category_name'] : 'Premium Natural Selection'; ?>
                                            </p>
                                        </div>

                                        <!-- Interactive Price Strip -->
                                        <div class="flex items-center justify-between mb-6 mt-auto">
                                            <div class="flex flex-col">
                                                <?php if(isset($p['original_price']) && $p['original_price'] > $p['price']): ?>
                                                    <span class="text-[11px] text-gray-300 font-bold line-through">MRP ₹<?php echo $p['original_price']; ?></span>
                                                <?php endif; ?>
                                                <span class="text-4xl font-black text-black leading-none tracking-tighter">
                                                    ₹<?php echo $p['price']; ?>
                                                </span>
                                            </div>
                                            <!-- Quick Actions -->
                                            <div class="flex gap-2">
                                                <button onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist(<?php echo $p['id']; ?>, this)" class="w-12 h-12 bg-gray-50 text-gray-300 rounded-xl flex items-center justify-center hover:bg-white hover:text-red-500 transition-all border border-gray-100">
                                                    <i class="<?php echo in_array($p['id'], $wishlist_ids) ? 'fas text-red-500' : 'far'; ?> fa-heart text-sm"></i>
                                                </button>
                                                <?php if($p['stock'] <= 0): ?>
                                                    <button disabled class="w-12 h-12 bg-gray-100 text-gray-300 rounded-xl flex items-center justify-center cursor-not-allowed" title="Out of stock">
                                                        <i class="fas fa-ban text-sm"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="event.preventDefault(); event.stopPropagation(); addToCart(<?php echo $p['id']; ?>, this)" class="w-12 h-12 bg-black text-[#24B25D] rounded-xl flex items-center justify-center hover:bg-[#24B25D] hover:text-white transition-all shadow-md active:scale-90">
                                                        <i class="fas fa-shopping-bag text-sm"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Buy Now Action -->
                                        <?php if($p['stock'] <= 0): ?>
                                            <button disabled 
                                                class="w-full bg-gray-100 text-gray-400 py-4 rounded-xl md:rounded-2xl font-black text-xs uppercase tracking-widest cursor-not-allowed">
                                                Out of Stock
                                            </button>
                                        <?php else: ?>
                                            <button onclick="event.preventDefault(); event.stopPropagation(); quickBuy(<?php echo $p['id']; ?>, this)" 
                                                class="w-full bg-[#24B25D] hover:bg-[#004F42] text-white py-4 rounded-xl md:rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-md active:scale-95 group/buy">
                                                Quick Buy — <i class="fas fa-bolt ml-1 group-hover/buy:animate-pulse"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div></div>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-16 flex justify-center items-center gap-3">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page - 1])); ?>" class="w-12 h-12 rounded-2xl bg-white border-2 border-gray-100 flex items-center justify-center text-gray-900 hover:border-[#24B25D] hover:text-[#24B25D] transition-all">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $i])); ?>" 
                               class="w-12 h-12 rounded-2xl flex items-center justify-center font-bold font-heading transition-all
                               <?php echo $page == $i ? 'bg-[#24B25D] text-black shadow-lg shadow-green-200 scale-110' : 'bg-white border-2 border-gray-100 text-gray-500 hover:border-[#24B25D] hover:text-[#24B25D]'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page + 1])); ?>" class="w-12 h-12 rounded-2xl bg-white border-2 border-gray-100 flex items-center justify-center text-gray-900 hover:border-[#24B25D] hover:text-[#24B25D] transition-all">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-20 bg-white rounded-[30px] border-2 border-gray-100">
                    <div class="text-6xl mb-4">🥝</div>
                    <h3 class="text-2xl font-bold font-heading text-gray-900">No snacks found here.</h3>
                    <p class="text-gray-500 mb-6">Try a different category or search term.</p>
                    <a href="<?php echo get_url('shop'); ?>" class="btn-chunky btn-primary">View All Products</a>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>

</body>
</html>


