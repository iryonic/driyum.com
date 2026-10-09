<?php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) {
    echo json_encode([
        'success' => true,
        'query' => '',
        'total' => 0,
        'categories' => [
            'quick_links' => [],
            'products' => [],
            'orders' => [],
            'customers' => [],
            'coupons' => []
        ],
        'items' => []
    ]);
    exit;
}

$categories = [
    'quick_links' => [],
    'products' => [],
    'orders' => [],
    'customers' => [],
    'coupons' => []
];
$all_items = [];
$q_lower = strtolower($q);

// 1. Quick Navigation & Admin Actions (Command Palette)
$admin_routes = [
    ['title' => 'Dashboard Overview', 'subtitle' => 'Live visitors, daily revenue & sales pulse', 'url' => 'index.php', 'icon' => 'fas fa-chart-pie', 'keywords' => 'dashboard home overview stats metrics live'],
    ['title' => 'Product Analytics & Insights', 'subtitle' => 'Detailed unit sales, velocity & product stats', 'url' => 'analytics.php', 'icon' => 'fas fa-chart-line', 'keywords' => 'analytics stats product revenue sales velocity insights detailed performance'],
    ['title' => 'Product Catalog', 'subtitle' => 'Manage inventory, weights, tags & pricing', 'url' => 'products.php', 'icon' => 'fas fa-layer-group', 'keywords' => 'products catalog inventory items snacks dry fruit items list'],
    ['title' => 'Add New Product', 'subtitle' => 'Create a new snack or combo listing', 'url' => 'product_form.php', 'icon' => 'fas fa-plus-circle', 'keywords' => 'new product add create item listing upload'],
    ['title' => 'Product Sorting & Merchandising', 'subtitle' => 'Drag & drop order for storefront combos & catalog', 'url' => 'product_sorting.php', 'icon' => 'fas fa-sort-amount-down', 'keywords' => 'sorting rearrange order combos featured storefront catalog drag'],
    ['title' => 'Orders Management', 'subtitle' => 'View, filter, update & process customer orders', 'url' => 'orders.php', 'icon' => 'fas fa-box-open', 'keywords' => 'orders shipments tracking fulfillment dispatch sales invoice'],
    ['title' => 'Batch Shipping Labels', 'subtitle' => 'Generate and print thermal shipping labels in bulk', 'url' => 'generate_batch_shipments.php', 'icon' => 'fas fa-barcode', 'keywords' => 'batch labels print shipping barcodes waybills dispatch'],
    ['title' => 'Abandoned Carts Recovery', 'subtitle' => 'View dropped checkouts & recover shoppers', 'url' => 'abandoned_carts.php', 'icon' => 'fas fa-shopping-cart', 'keywords' => 'abandoned carts recovery checkouts dropped dropoff'],
    ['title' => 'Inventory & Stock Watch', 'subtitle' => 'Low stock alerts and SKU inventory levels', 'url' => 'inventory.php', 'icon' => 'fas fa-boxes-stacked', 'keywords' => 'inventory stock levels low out reorder sku warehouse'],
    ['title' => 'Categories Management', 'subtitle' => 'Organize catalog into product categories', 'url' => 'categories.php', 'icon' => 'fas fa-tags', 'keywords' => 'categories category tags groups sections'],
    ['title' => 'Discount Coupons', 'subtitle' => 'Create promotional codes, percentages & vouchers', 'url' => 'coupons.php', 'icon' => 'fas fa-ticket-alt', 'keywords' => 'coupons discounts promo code vouchers sale offers'],
    ['title' => 'Hero Banners & Slides', 'subtitle' => 'Manage homepage visual banners and promotions', 'url' => 'hero_slides.php', 'icon' => 'fas fa-images', 'keywords' => 'hero slides banners carousel homepage graphics images'],
    ['title' => 'Homepage Layout Sections', 'subtitle' => 'Configure homepage story, trust and promo blocks', 'url' => 'manage_home.php', 'icon' => 'fas fa-laptop-code', 'keywords' => 'homepage sections blocks layout trust highlights'],
    ['title' => 'Customer Reviews & Ratings', 'subtitle' => 'Moderate, approve and feature verified reviews', 'url' => 'reviews.php', 'icon' => 'fas fa-star', 'keywords' => 'reviews ratings feedback testimonials moderation approve'],
    ['title' => 'Customer Database', 'subtitle' => 'Registered users, addresses and purchase history', 'url' => 'users.php', 'icon' => 'fas fa-users', 'keywords' => 'users customers accounts shoppers profiles clients'],
    ['title' => 'Newsletter Subscribers', 'subtitle' => 'Email marketing subscribers list and export', 'url' => 'subscribers.php', 'icon' => 'fas fa-envelope-open-text', 'keywords' => 'subscribers emails newsletter marketing blast list'],
    ['title' => 'Shipping & Delivery Rates', 'subtitle' => 'Configure zones, weight slabs and shipping carriers', 'url' => 'shipping.php', 'icon' => 'fas fa-truck-fast', 'keywords' => 'shipping delivery courier rates weight zones methods pricing'],
    ['title' => 'Store Configuration & Settings', 'subtitle' => 'Payment gateways, branding, contacts and SEO', 'url' => 'settings.php', 'icon' => 'fas fa-sliders-h', 'keywords' => 'settings config store razorpay payment email setup options general'],
    ['title' => 'CMS Pages (About & Legal)', 'subtitle' => 'Edit brand story, heritage, privacy & refund policies', 'url' => 'manage_pages.php', 'icon' => 'fas fa-file-contract', 'keywords' => 'pages about legal terms privacy policy disclaimer content story'],
    ['title' => 'Contact Inquiries', 'subtitle' => 'Customer support messages and inbox', 'url' => 'manage_contact.php', 'icon' => 'fas fa-headset', 'keywords' => 'contact support messages inquiries inbox helpdesk'],
    ['title' => 'Brand Partners', 'subtitle' => 'Manage institutional and retail partner logos', 'url' => 'manage_partners.php', 'icon' => 'fas fa-handshake', 'keywords' => 'partners brands clients corporate retail b2b'],
    ['title' => 'Affiliates Program', 'subtitle' => 'Track referral commissions and partner payouts', 'url' => 'affiliates.php', 'icon' => 'fas fa-share-nodes', 'keywords' => 'affiliates referrals partners commission earnings payouts']
];

foreach ($admin_routes as $route) {
    if (strpos(strtolower($route['title']), $q_lower) !== false || 
        strpos(strtolower($route['keywords']), $q_lower) !== false ||
        strpos(strtolower($route['subtitle']), $q_lower) !== false) {
        $item = [
            'type' => 'quick_link',
            'id' => 'nav_' . substr(md5($route['url']), 0, 8),
            'title' => $route['title'],
            'subtitle' => $route['subtitle'],
            'url' => $route['url'],
            'icon' => $route['icon'],
            'category' => 'Quick Action'
        ];
        $categories['quick_links'][] = $item;
        $all_items[] = $item;
        if (count($categories['quick_links']) >= 4) break;
    }
}

// 2. Search Products
$p_limit = 6;
$p_sql = "SELECT p.id, p.name, p.slug, p.sku, p.price, p.stock, p.is_active, p.image, c.name as cat_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.name LIKE ? OR p.sku LIKE ? OR p.slug LIKE ? OR p.id = ? 
          ORDER BY (p.name LIKE ?) DESC, p.id DESC 
          LIMIT $p_limit";
$products = fetch_all($p_sql, ["%$q%", "%$q%", "%$q%", (int)$q, "$q%"]);

foreach ($products as $p) {
    $stock_status = 'In Stock';
    $stock_color = 'emerald';
    if ($p['stock'] <= 0) {
        $stock_status = 'Out of Stock';
        $stock_color = 'rose';
    } elseif ($p['stock'] < 10) {
        $stock_status = "Low Stock ({$p['stock']})";
        $stock_color = 'amber';
    }

    $subtitle = '₹' . number_format($p['price']);
    if ($p['cat_name']) $subtitle .= ' • ' . $p['cat_name'];
    $subtitle .= ' • ' . ($p['is_active'] ? 'Active' : 'Draft');

    $item = [
        'type' => 'product',
        'id' => $p['id'],
        'title' => $p['name'],
        'subtitle' => $subtitle,
        'badge' => $stock_status,
        'badge_color' => $stock_color,
        'sku' => $p['sku'] ?: ('#P' . $p['id']),
        'price' => '₹' . number_format($p['price']),
        'stock' => $p['stock'],
        'url' => 'product_form.php?id=' . $p['id'],
        'view_url' => '../product/' . urlencode($p['slug']),
        'image' => $p['image'],
        'category' => 'Product'
    ];
    $categories['products'][] = $item;
    $all_items[] = $item;
}

// 3. Search Orders (By Order #, Customer Name, Email, Address, Phone)
$o_limit = 6;
$o_sql = "SELECT o.id, o.order_number, o.total, o.order_status, o.payment_status, o.created_at, 
                 u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          WHERE o.order_number LIKE ? 
             OR o.id = ? 
             OR o.shipping_address LIKE ? 
             OR u.name LIKE ? 
             OR u.email LIKE ? 
             OR u.phone LIKE ? 
          ORDER BY o.id DESC 
          LIMIT $o_limit";
$orders = fetch_all($o_sql, ["%$q%", (int)$q, "%$q%", "%$q%", "%$q%", "%$q%"]);

foreach ($orders as $o) {
    $status_colors = [
        'delivered' => 'emerald',
        'shipped' => 'sky',
        'confirmed' => 'indigo',
        'pending' => 'amber',
        'cancelled' => 'rose',
        'pending_payment' => 'slate'
    ];
    $status_color = $status_colors[$o['order_status']] ?? 'slate';

    $c_name = $o['customer_name'] ?: 'Guest / Customer';
    $order_num = $o['order_number'] ?: ('#' . $o['id']);
    $date = date('d M, Y', strtotime($o['created_at']));

    $item = [
        'type' => 'order',
        'id' => $o['id'],
        'title' => "Order #{$order_num} — {$c_name}",
        'subtitle' => "₹" . number_format($o['total']) . " • {$date} • " . ucfirst(str_replace('_', ' ', $o['order_status'])),
        'badge' => ucfirst(str_replace('_', ' ', $o['order_status'])),
        'badge_color' => $status_color,
        'url' => 'orders.php?q=' . urlencode($order_num),
        'invoice_url' => '../invoice.php?id=' . urlencode($order_num),
        'icon' => 'fas fa-box',
        'category' => 'Order'
    ];
    $categories['orders'][] = $item;
    $all_items[] = $item;
}

// 4. Search Customers / Users
$u_limit = 5;
$u_sql = "SELECT u.id, u.name, u.email, u.phone, u.created_at, 
                 (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
                 (SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = u.id AND order_status != 'cancelled') as total_spend 
          FROM users u 
          WHERE u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.id = ? 
          ORDER BY order_count DESC, u.id DESC 
          LIMIT $u_limit";
$users = fetch_all($u_sql, ["%$q%", "%$q%", "%$q%", (int)$q]);

foreach ($users as $u) {
    $subtitle = $u['email'];
    if ($u['phone']) $subtitle .= ' • ' . $u['phone'];
    $subtitle .= ' • ' . $u['order_count'] . ' orders';

    $item = [
        'type' => 'customer',
        'id' => $u['id'],
        'title' => $u['name'] ?: 'Customer #' . $u['id'],
        'subtitle' => $subtitle,
        'badge' => '₹' . number_format($u['total_spend']) . ' spent',
        'badge_color' => $u['order_count'] > 1 ? 'emerald' : 'slate',
        'url' => 'users.php?q=' . urlencode($u['email'] ?: $u['name']),
        'icon' => 'fas fa-user',
        'category' => 'Customer'
    ];
    $categories['customers'][] = $item;
    $all_items[] = $item;
}

// 5. Search Coupons
$c_limit = 3;
$coupons = fetch_all("SELECT id, code, type, value, is_active FROM coupons WHERE code LIKE ? LIMIT $c_limit", ["%$q%"]);
foreach ($coupons as $c) {
    $val_str = $c['type'] === 'percentage' ? "{$c['value']}% OFF" : "₹{$c['value']} OFF";
    $item = [
        'type' => 'coupon',
        'id' => $c['id'],
        'title' => "Coupon: " . strtoupper($c['code']),
        'subtitle' => $val_str . ' • ' . ($c['is_active'] ? 'Active voucher' : 'Inactive'),
        'badge' => $val_str,
        'badge_color' => $c['is_active'] ? 'amber' : 'slate',
        'url' => 'coupons.php?q=' . urlencode($c['code']),
        'icon' => 'fas fa-ticket-alt',
        'category' => 'Coupon'
    ];
    $categories['coupons'][] = $item;
    $all_items[] = $item;
}

$total = count($all_items);

echo json_encode([
    'success' => true,
    'query' => $q,
    'total' => $total,
    'categories' => $categories,
    'items' => $all_items
]);
