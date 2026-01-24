<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Filter Logic from URL
$cat_slug = isset($_GET['cat']) ? $_GET['cat'] : null;
$search = isset($_GET['q']) ? trim($_GET['q']) : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

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
             $where_clause .= " AND p.category_id = ?";
             $params[] = $cat_row['id'];
             $types .= "i";
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
    case 'price_asc': $order_by = " ORDER BY p.price ASC"; break;
    case 'price_desc': $order_by = " ORDER BY p.price DESC"; break;
    default: $order_by = " ORDER BY p.created_at DESC"; break;
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
    $page_title = 'Shop Snacks';
    $page_description = "Browse our collection of premium, sun-dried healthy snacks. Fresh from the valley, 100% organic.";
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
    <header class="relative pt-16 pb-12 text-center px-4 overflow-hidden">
        <!-- Subtle Gradient -->
        <div class="absolute inset-0 bg-gradient-to-b from-green-50/30 to-transparent -z-10"></div>

        <div class="container mx-auto relative z-10">
            <h1 class="text-5xl md:text-7xl font-['Fredoka'] font-black text-gray-900 mb-4 tracking-tight">
                <?php 
                    if ($search) echo "Searching: <span class='text-[#19DC7E]'>'$search'</span>";
                    elseif ($cat_slug) echo str_replace('-', ' ', $cat_slug) . "<span class='text-[#19DC7E]'>.</span>";
                    else echo "The <span class='text-[#19DC7E]'>Snack</span> Shop";
                ?>
            </h1>
            <p class="text-lg md:text-xl text-gray-400 font-['Outfit'] font-medium max-w-2xl mx-auto">
                Discover the pure taste of nature. Hand-picked, sun-dried, and delivered fresh from the valley.
            </p>
        </div>
    </header>

    <div class="container mx-auto px-4 pb-20 flex flex-col md:flex-row gap-8">
        
        <!-- SIDEBAR FILTERS (Desktop) -->
        <aside class="w-full md:w-64 flex-shrink-0 hidden md:block">
            <div class="sticky top-24 space-y-8">
                <div class="card-chunky p-6 bg-white/50 backdrop-blur-xl border-white/40">
                    <h3 class="font-bold text-xl mb-5 font-['Fredoka'] flex items-center gap-2 text-gray-900">
                        <i class="fas fa-filter text-[#19DC7E] text-sm"></i> Categories
                    </h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="<?php echo get_url('shop'); ?>" class="flex items-center justify-between py-3 px-4 rounded-2xl font-bold font-['Outfit'] transition-all <?php echo !$cat_slug ? 'bg-[#19DC7E] text-black shadow-lg shadow-green-500/20 scale-105' : 'text-gray-500 hover:bg-white hover:text-[#19DC7E] hover:translate-x-1'; ?>">
                                <span>All Packs</span>
                                <?php if(!$cat_slug): ?> <i class="fas fa-check-circle"></i> <?php endif; ?>
                            </a>
                        </li>
                        <?php foreach ($categories as $c): ?>
                        <li>
                            <a href="<?php echo category_url($c['slug']); ?>" class="flex items-center justify-between py-3 px-4 rounded-2xl font-bold font-['Outfit'] transition-all <?php echo $cat_slug === $c['slug'] ? 'bg-[#19DC7E] text-black shadow-lg shadow-green-500/20 scale-105' : 'text-gray-500 hover:bg-white hover:text-[#19DC7E] hover:translate-x-1'; ?>">
                                <span><?php echo $c['name']; ?></span>
                                <?php if($cat_slug === $c['slug']): ?> <i class="fas fa-check-circle"></i> <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="card-chunky p-6">
                    <h3 class="font-bold text-xl mb-4 font-['Fredoka']">Sort Collection</h3>
                    <div class="relative group">
                        <select onchange="window.location.href=this.value" class="input-chunky text-sm p-4 bg-gray-50 border-gray-100 appearance-none cursor-pointer">
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest Arrival</option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_asc'])); ?>" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_desc'])); ?>" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none group-hover:text-[#19DC7E] transition"></i>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MOBILE FILTER DROPDOWN -->
        <div class="md:hidden w-full">
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php 
                    $color_count = 1;
                    $default_colors = ['#E0F2FE', '#DCFCE7', '#FEF9C3', '#FFEDD5', '#F3E8FF', '#FFE4E6'];
                    foreach ($products as $p): 
                        $db_color = !empty($p['bg_color']) ? $p['bg_color'] : null;
                        $color_raw = $db_color ?: $default_colors[($color_count - 1) % 6];
                        $card_bg = adjust_brightness($color_raw, -15); // Darken for depth
                        $color_count++;
                    ?>
                        <!-- Premium Product Card -->
                        <div class="group relative rounded-[50px] hover:shadow-[0_45px_90px_rgba(0,0,0,0.15)] transition-all duration-700 overflow-hidden border-4 border-white/50 hover:border-white h-full flex flex-col anim-up" style="background-color: <?php echo $card_bg; ?>;">
                            
                            <!-- White Overlay (Fades out on hover for fuller color) -->
                            <div class="absolute inset-0 bg-white/60 group-hover:bg-white/0 transition-colors duration-700 pointer-events-none"></div>
                            
                            <!-- Dynamic Glow Highlight (Appears on hover) -->
                            <div class="absolute -inset-1 bg-gradient-to-tr from-white/30 via-transparent to-white/30 opacity-0 group-hover:opacity-100 transition-opacity duration-700 blur-2xl z-0"></div>

                            <!-- Link Wrapper -->
                            <a href="<?php echo product_url($p['slug']); ?>" class="block flex-1 relative p-2 z-10">
                                
                                <!-- Image Area with 3D Float -->
                                <div class="bg-white/80 rounded-[42px] aspect-square mb-6 overflow-hidden relative flex items-center justify-center group-hover:bg-white transition-all duration-700 shadow-inner group-hover:shadow-none">
                                    <!-- Background Bloom -->
                                    <div class="absolute inset-0 bg-gradient-to-tr from-white via-transparent to-white/50 opacity-0 group-hover:opacity-100 transition duration-700 z-0"></div>
                                    
                                    <img src="<?php echo get_url(ltrim($p['image'], './')); ?>" class="w-4/5 h-4/5 object-contain transform group-hover:scale-110 group-hover:-rotate-6 group-hover:-translate-y-4 transition duration-700 ease-out z-10 filter drop-shadow-[0_10px_10px_rgba(0,0,0,0.05)] group-hover:drop-shadow-[0_30px_30px_rgba(0,0,0,0.1)] <?php echo $p['stock'] <= 0 ? 'grayscale' : ''; ?>">
                                    
                                    <!-- Badges -->
                                    <div class="absolute top-6 left-6 flex flex-col gap-2 z-20 items-start">
                                        <?php if(isset($p['is_new']) && $p['is_new']): ?>
                                            <span class="bg-[#19DC7E] text-black text-[10px] font-black px-4 py-1.5 rounded-full shadow-lg shadow-green-200 uppercase tracking-widest backdrop-blur-md transform group-hover:scale-110 transition-transform">NEW ✨</span>
                                        <?php endif; ?>
                                        <?php if(isset($p['discount_percentage']) && $p['discount_percentage'] > 0): ?>
                                            <span class="bg-black text-white text-[10px] font-black px-4 py-1.5 rounded-full shadow-lg h-8 flex items-center justify-center uppercase tracking-widest transform group-hover:rotate-12 transition-transform">-<?php echo $p['discount_percentage']; ?>% OFF</span>
                                        <?php endif; ?>
                                        
                                        <!-- Stock Indicator Badge -->
                                        <?php if($p['stock'] <= 0): ?>
                                            <span class="bg-red-500 text-white text-[10px] font-black px-4 py-1.5 rounded-full shadow-lg uppercase tracking-widest">Sold Out</span>
                                        <?php elseif($p['stock'] < 10): ?>
                                            <span class="bg-amber-500 text-white text-[10px] font-black px-4 py-1.5 rounded-full shadow-lg uppercase tracking-widest animate-pulse">Low Stock</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Heart Icon -->
                                    <?php $is_wishlisted = in_array($p['id'], $wishlist_ids); ?>
                                    <button onclick="event.preventDefault(); toggleWishlist(<?php echo $p['id']; ?>, this)" class="absolute top-5 right-5 w-12 h-12 bg-white rounded-[18px] flex items-center justify-center shadow-lg <?php echo $is_wishlisted ? 'active text-red-500' : 'text-gray-300'; ?> hover:text-red-500 hover:scale-110 transition-all duration-300 z-20 group/heart active:scale-90 border border-gray-50">
                                        <i class="<?php echo $is_wishlisted ? 'fas' : 'far'; ?> fa-heart group-hover/heart:animate-bounce"></i>
                                    </button>
                                </div>

                                <!-- Content -->
                                <div class="px-6 pb-2 relative">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-[#19DC7E] opacity-50"></span>
                                            <p class="text-gray-400 text-[10px] font-black uppercase tracking-[0.2em] font-['Outfit']"><?php echo htmlspecialchars($p['category_name'] ?: 'Kashmir Special'); ?></p>
                                        </div>
                                        <h3 class="text-2xl font-black font-['Fredoka'] text-gray-900 group-hover:text-black transition leading-none py-1"><?php echo $p['name']; ?></h3>
                                        <p class="text-gray-400 text-xs mb-4 font-['Outfit'] line-clamp-2 min-h-[32px] font-medium"><?php echo $p['description']; ?></p>
                                        <div class="flex text-yellow-400 text-[10px] gap-1 mt-1">
                                            <i class="fas fa-star text-[8px]"></i><i class="fas fa-star text-[8px]"></i><i class="fas fa-star text-[8px]"></i><i class="fas fa-star text-[8px]"></i><i class="fas fa-star text-[8px]"></i>
                                            <span class="text-gray-400 text-[9px] font-black uppercase tracking-widest ml-1">(4.9)</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            
                            <!-- Glass Action Bar (Floating at bottom) -->
                            <div class="px-3 pb-4 md:px-5 md:pb-5 pt-2 md:pt-3 mt-auto z-20 relative">
                                <div class="bg-white rounded-[24px] md:rounded-[30px] p-2 md:p-3 flex items-center justify-between border-2 border-transparent group-hover:border-white group-hover:shadow-[0_15px_40px_rgba(0,0,0,0.08)] transition-all duration-500">
                                     
                                     <!-- Price -->
                                     <div class="pl-2 md:pl-4 flex flex-col leading-none">
                                        <?php if(isset($p['original_price']) && $p['original_price'] > $p['price']): ?>
                                            <span class="text-[9px] md:text-[11px] text-gray-400 font-bold line-through decoration-red-400/50 block mb-0.5">₹<?php echo $p['original_price']; ?></span>
                                        <?php endif; ?>
                                        <span class="text-xl md:text-3xl font-black text-gray-900 font-['Fredoka'] tracking-tighter">₹<?php echo $p['price']; ?></span>
                                     </div>

                                     <div class="flex gap-1 md:gap-2">
                                        <!-- Quick Buy -->
                                        <button onclick="event.stopPropagation(); quickBuy(<?php echo $p['id']; ?>, this)" 
                                            <?php echo $p['stock'] <= 0 ? 'disabled' : ''; ?>
                                            class="w-10 h-10 md:w-14 md:h-14 rounded-xl md:rounded-2xl <?php echo $p['stock'] <= 0 ? 'bg-gray-100 text-gray-300' : 'bg-amber-50 text-amber-500 hover:bg-amber-400 hover:text-white hover:scale-105 active:scale-95'; ?> flex items-center justify-center transition-all duration-300 group/btn" title="Flash Buy">
                                            <i class="fas fa-bolt text-sm md:text-xl group-hover/btn:animate-pulse"></i>
                                        </button>
                                        <!-- Add Cart -->
                                        <button onclick="event.stopPropagation(); addToCart(<?php echo $p['id']; ?>, this)" 
                                            <?php echo $p['stock'] <= 0 ? 'disabled' : ''; ?>
                                            class="w-10 h-10 md:w-14 md:h-14 rounded-xl md:rounded-2xl <?php echo $p['stock'] <= 0 ? 'bg-gray-100 text-gray-300' : 'bg-black text-[#19DC7E] hover:bg-[#19DC7E] hover:text-black hover:scale-105 active:scale-95'; ?> flex items-center justify-center transition-all duration-300 group/btn">
                                            <i class="fas fa-shopping-bag text-sm md:text-xl group-hover/btn:rotate-12 transition-transform"></i>
                                        </button>
                                     </div>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-16 flex justify-center items-center gap-3">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page - 1])); ?>" class="w-12 h-12 rounded-2xl bg-white border-2 border-gray-100 flex items-center justify-center text-gray-900 hover:border-[#19DC7E] hover:text-[#19DC7E] transition-all">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $i])); ?>" 
                               class="w-12 h-12 rounded-2xl flex items-center justify-center font-bold font-['Fredoka'] transition-all
                               <?php echo $page == $i ? 'bg-[#19DC7E] text-black shadow-lg shadow-green-200 scale-110' : 'bg-white border-2 border-gray-100 text-gray-500 hover:border-[#19DC7E] hover:text-[#19DC7E]'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page + 1])); ?>" class="w-12 h-12 rounded-2xl bg-white border-2 border-gray-100 flex items-center justify-center text-gray-900 hover:border-[#19DC7E] hover:text-[#19DC7E] transition-all">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-20 bg-white rounded-[30px] border-2 border-gray-100">
                    <div class="text-6xl mb-4">🥝</div>
                    <h3 class="text-2xl font-bold font-['Fredoka'] text-gray-900">No snacks found here.</h3>
                    <p class="text-gray-500 mb-6">Try a different category or search term.</p>
                    <a href="<?php echo get_url('shop'); ?>" class="btn-chunky btn-primary">View All Products</a>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>

</body>
</html>
