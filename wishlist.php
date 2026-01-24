<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: " . get_url('login?redirect=wishlist'));
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch Wishlist Items
$products = fetch_all("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN wishlist w ON p.id = w.product_id 
    JOIN categories c ON p.category_id = c.id 
    WHERE w.user_id = ? AND p.is_active = 1
    ORDER BY w.created_at DESC
", [$user_id]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage your favorite Driyum mountain treats. Save your top sun-dried snacks and hokh suin for later.">
    <title>My Wishlist - DRIYUM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo get_url('assets/css/chunky.css'); ?>">
</head>
<body class="bg-[#FFFBEB] font-['Outfit']">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 py-12 min-h-[60vh]">
        
        <!-- HEADER -->
        <header class="mb-12 anim-up text-center md:text-left">
            <h1 class="uppercase">My Wishlist <span class="text-red-500" aria-hidden="true">❤️</span></h1>
            <p class="text-gray-500 font-bold uppercase tracking-widest text-[10px]">Your saved mountain treats</p>
        </header>

        <?php if (empty($products)): ?>
            <section class="bg-white rounded-[50px] p-20 text-center border-2 border-dashed border-gray-100 anim-up relative overflow-hidden">
                <div class="relative z-10">
                    <div class="text-8xl mb-8 animate-pulse" aria-hidden="true">📦</div>
                    <h2 class="uppercase">Your wishlist is empty!</h2>
                    <p class="text-gray-400 font-medium mb-10 max-w-sm mx-auto leading-relaxed">It looks like you haven't saved any snacks yet. Let's find some favorites!</p>
                    <a href="<?php echo get_url('shop'); ?>" class="btn-chunky bg-black text-white px-12 py-5 shadow-2xl hover:bg-[#19DC7E] hover:text-black transition-all inline-block">Explore the Shop</a>
                </div>
                <!-- Decor -->
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-gray-50 rounded-full" aria-hidden="true"></div>
            </section>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 anim-up delay-100">
                <?php foreach ($products as $p): ?>
                    <article class="product-card group bg-white rounded-[40px] p-6 shadow-sm border border-gray-100 relative hover:border-[#19DC7E] hover:shadow-2xl transition-all duration-500">
                        
                        <!-- Actions Sidebar -->
                        <div class="absolute right-4 top-4 flex flex-col gap-3 z-10 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-4 group-hover:translate-x-0">
                            <button onclick="toggleWishlist(<?php echo $p['id']; ?>, this)" class="wishlist-btn active w-12 h-12 bg-white rounded-2xl shadow-lg flex items-center justify-center text-red-500 hover:scale-110 transition active:scale-95" aria-label="Remove from wishlist">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>

                        <!-- Image Area -->
                        <div class="relative w-full aspect-square bg-[#F3F4F6] rounded-[32px] overflow-hidden mb-6 group-hover:scale-95 transition-transform duration-500">
                            <a href="<?php echo product_url($p['slug']); ?>" aria-label="View <?php echo htmlspecialchars($p['name']); ?>">
                                <img src="<?php echo get_url(ltrim($p['image'], './')); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" width="300" height="300" class="w-full h-full object-contain p-4 group-hover:scale-110 transition-transform duration-700">
                            </a>
                            <?php if ($p['discount_percentage'] > 0): ?>
                                <div class="absolute top-4 left-4 bg-[#FF4D4D] text-white text-[10px] font-black px-3 py-1 rounded-full shadow-lg">
                                    -<?php echo $p['discount_percentage']; ?>%
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Product Info -->
                        <div class="space-y-4">
                            <div>
                                <span class="text-[9px] font-black uppercase text-[#19DC7E] tracking-widest"><?php echo htmlspecialchars($p['category_name']); ?></span>
                                <h3 class="text-xl font-['Fredoka'] font-black text-gray-900 group-hover:text-[#19DC7E] transition-colors leading-tight">
                                    <a href="<?php echo product_url($p['slug']); ?>"><?php echo htmlspecialchars($p['name']); ?></a>
                                </h3>
                            </div>

                            <div class="flex items-center justify-between pt-2">
                                <div class="flex flex-col">
                                    <span class="text-2xl font-['Fredoka'] font-black text-gray-900">₹<?php echo number_format($p['price']); ?></span>
                                    <?php if ($p['original_price'] > $p['price']): ?>
                                        <span class="text-[10px] text-gray-400 line-through font-bold">₹<?php echo number_format($p['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <button onclick="addToCart(<?php echo $p['id']; ?>, this)" class="w-14 h-14 bg-black text-white rounded-[20px] flex items-center justify-center hover:bg-[#19DC7E] hover:text-black transition-all shadow-xl active:scale-90" aria-label="Add to bag">
                                    <i class="fas fa-shopping-bag text-lg"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>
