<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Strict Admin Access Check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// -------------------------------------------------------------
// 1. FILTER PARAMETERS & SANITIZATION
// -------------------------------------------------------------
$range = isset($_GET['range']) ? sanitize_input($_GET['range']) : '30';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$stock_filter = isset($_GET['stock']) ? sanitize_input($_GET['stock']) : 'all';
$search_query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'revenue';
$sort_dir = isset($_GET['dir']) && strtolower($_GET['dir']) === 'asc' ? 'ASC' : 'DESC';

$excluded_statuses = "'cancelled', 'pending_payment'";

// Build Date Filter Clauses
$date_sql = "";
$days_count = 30;

if ($range === 'today') {
    $date_sql = "AND DATE(o.created_at) = CURDATE()";
    $range_label = "Today";
    $days_count = 1;
} elseif ($range === '7') {
    $date_sql = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $range_label = "Last 7 Days";
    $days_count = 7;
} elseif ($range === '30') {
    $date_sql = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $range_label = "Last 30 Days";
    $days_count = 30;
} elseif ($range === '90') {
    $date_sql = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
    $range_label = "Last 90 Days";
    $days_count = 90;
} elseif ($range === '365') {
    $date_sql = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    $range_label = "Past 1 Year";
    $days_count = 365;
} else {
    $range = 'all';
    $date_sql = "";
    $range_label = "All Time";
    $earliest_order = fetch_one("SELECT MIN(created_at) as min_date FROM orders WHERE order_status NOT IN ($excluded_statuses)")['min_date'] ?? null;
    $days_count = $earliest_order ? max(1, (int)round((time() - strtotime($earliest_order)) / 86400)) : 30;
}

// -------------------------------------------------------------
// 2. CSV EXPORT HANDLER
// -------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_where = ["1=1"];
    $export_params = [];

    if ($category_filter > 0) {
        $export_where[] = "p.category_id = ?";
        $export_params[] = $category_filter;
    }
    if ($stock_filter === 'out') {
        $export_where[] = "p.stock <= 0";
    } elseif ($stock_filter === 'low') {
        $export_where[] = "p.stock > 0 AND p.stock < 10";
    } elseif ($stock_filter === 'healthy') {
        $export_where[] = "p.stock >= 10";
    }
    if ($search_query !== '') {
        $export_where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.id = ?)";
        $export_params[] = "%$search_query%";
        $export_params[] = "%$search_query%";
        $export_params[] = (int)$search_query;
    }
    $export_where_sql = implode(" AND ", $export_where);

    $export_sql = "
        SELECT 
            p.id,
            p.name,
            p.sku,
            c.name as category_name,
            p.price,
            p.stock,
            p.is_active,
            COALESCE(sales.units_sold, 0) as units_sold,
            COALESCE(sales.revenue_generated, 0) as revenue_generated,
            COALESCE(sales.order_count, 0) as order_count,
            COALESCE(rev.avg_rating, 5.0) as avg_rating,
            COALESCE(rev.reviews_count, 0) as reviews_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN (
            SELECT 
                oi.product_id,
                SUM(oi.quantity) as units_sold,
                SUM(oi.subtotal) as revenue_generated,
                COUNT(DISTINCT oi.order_id) as order_count
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.order_status NOT IN ($excluded_statuses) $date_sql
            GROUP BY oi.product_id
        ) sales ON p.id = sales.product_id
        LEFT JOIN (
            SELECT 
                product_id, 
                ROUND(AVG(rating), 1) as avg_rating, 
                COUNT(id) as reviews_count
            FROM reviews
            WHERE is_approved = 1
            GROUP BY product_id
        ) rev ON p.id = rev.product_id
        WHERE $export_where_sql
        ORDER BY revenue_generated DESC
    ";
    $export_rows = fetch_all($export_sql, $export_params);
    $total_export_rev = array_sum(array_column($export_rows, 'revenue_generated'));

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=driyum_sales_report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    
    // Simple, human-friendly CSV column headers
    fputcsv($output, [
        'ID',
        'Product Name',
        'SKU',
        'Category',
        'Customer Rating',
        'Reviews Count',
        'Price (INR)',
        'Items Sold',
        'Money Made (INR)',
        'Share of Sales (%)',
        'Orders Count',
        'Units in Stock',
        'Stock Value (INR)',
        'Daily Sales Rate',
        'Estimated Days Left',
        'Status'
    ], ',', '"', "\\");

    foreach ($export_rows as $row) {
        $share = $total_export_rev > 0 ? round(($row['revenue_generated'] / $total_export_rev) * 100, 1) : 0;
        $daily = $days_count > 0 ? round($row['units_sold'] / $days_count, 2) : 0;
        $days_left = ($daily > 0) ? ceil($row['stock'] / $daily) : ($row['stock'] <= 0 ? 0 : 'No sales');

        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['sku'] ?: ('#P' . $row['id']),
            $row['category_name'] ?: 'Uncategorized',
            $row['avg_rating'],
            $row['reviews_count'],
            $row['price'],
            $row['units_sold'],
            $row['revenue_generated'],
            $share . '%',
            $row['order_count'],
            $row['stock'],
            $row['stock'] * $row['price'],
            $daily . ' per day',
            $days_left,
            $row['is_active'] ? 'Active' : 'Draft'
        ], ',', '"', "\\");
    }
    fclose($output);
    exit;
}

// Include Header (Navbar, Sidebar with #29e258, Omni Search)
include 'includes/header.php';

// -------------------------------------------------------------
// 3. STORE-WIDE METRICS (PERIOD TOTALS)
// -------------------------------------------------------------
$store_summary = fetch_one("
    SELECT 
        COUNT(o.id) as total_orders,
        COALESCE(SUM(o.total), 0) as total_revenue,
        COALESCE(SUM(o.discount), 0) as total_discount,
        COALESCE(SUM(o.shipping_cost), 0) as total_shipping
    FROM orders o 
    WHERE o.order_status NOT IN ($excluded_statuses) $date_sql
");
$total_orders_period = (int)($store_summary['total_orders'] ?? 0);
$total_store_revenue = (float)($store_summary['total_revenue'] ?? 0);
$total_store_discount = (float)($store_summary['total_discount'] ?? 0);

// Total items sold store-wide in period
$total_store_items_sold = (int)(fetch_one("
    SELECT COALESCE(SUM(oi.quantity), 0) as q
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_status NOT IN ($excluded_statuses) $date_sql
")['q'] ?? 0);

// Total product sales alone (sum of product item subtotals)
$total_store_product_sales = (float)(fetch_one("
    SELECT COALESCE(SUM(oi.subtotal), 0) as s
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_status NOT IN ($excluded_statuses) $date_sql
")['s'] ?? 0);

// Warehouse Inventory Totals
$catalog_stats = fetch_one("
    SELECT 
        COUNT(id) as total_products,
        SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as out_of_stock,
        SUM(CASE WHEN stock > 0 AND stock < 10 THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN stock >= 10 THEN 1 ELSE 0 END) as healthy_stock,
        COALESCE(SUM(stock), 0) as total_units_in_stock,
        COALESCE(SUM(stock * price), 0) as total_stock_valuation
    FROM products
    WHERE is_active = 1
");

// -------------------------------------------------------------
// 4. DETAILED PRODUCT METRICS QUERY WITH FILTERS & SORT
// -------------------------------------------------------------
$params = [];
$where_clauses = ["1=1"];

if ($category_filter > 0) {
    $where_clauses[] = "p.category_id = ?";
    $params[] = $category_filter;
}
if ($stock_filter === 'out') {
    $where_clauses[] = "p.stock <= 0";
} elseif ($stock_filter === 'low') {
    $where_clauses[] = "p.stock > 0 AND p.stock < 10";
} elseif ($stock_filter === 'healthy') {
    $where_clauses[] = "p.stock >= 10";
}
if ($search_query !== '') {
    $where_clauses[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.id = ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = (int)$search_query;
}

$where_sql = implode(" AND ", $where_clauses);

// Dynamic Sorting Map
$sort_columns = [
    'revenue' => 'revenue_generated',
    'units' => 'units_sold',
    'stock' => 'p.stock',
    'price' => 'p.price',
    'name' => 'p.name',
    'orders' => 'order_count'
];
$order_by_col = $sort_columns[$sort_by] ?? 'revenue_generated';

$products_query = "
    SELECT 
        p.id,
        p.name,
        p.slug,
        p.sku,
        p.image,
        p.price,
        p.original_price,
        p.discount_percentage,
        p.stock,
        p.is_active,
        p.is_featured,
        p.is_combo,
        c.name as category_name,
        COALESCE(sales.units_sold, 0) as units_sold,
        COALESCE(sales.revenue_generated, 0) as revenue_generated,
        COALESCE(sales.order_count, 0) as order_count,
        COALESCE(rev.avg_rating, 5.0) as avg_rating,
        COALESCE(rev.reviews_count, 0) as reviews_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN (
        SELECT 
            oi.product_id,
            SUM(oi.quantity) as units_sold,
            SUM(oi.subtotal) as revenue_generated,
            COUNT(DISTINCT oi.order_id) as order_count
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.order_status NOT IN ($excluded_statuses) $date_sql
        GROUP BY oi.product_id
    ) sales ON p.id = sales.product_id
    LEFT JOIN (
        SELECT 
            product_id, 
            ROUND(AVG(rating), 1) as avg_rating, 
            COUNT(id) as reviews_count
        FROM reviews
        WHERE is_approved = 1
        GROUP BY product_id
    ) rev ON p.id = rev.product_id
    WHERE $where_sql
    ORDER BY $order_by_col $sort_dir, p.id DESC
";

$product_stats = fetch_all($products_query, $params);

// Calculate Totals for Currently Filtered View
$filtered_units_sold = array_sum(array_column($product_stats, 'units_sold'));
$filtered_revenue = array_sum(array_column($product_stats, 'revenue_generated'));
$top_earner = !empty($product_stats) ? $product_stats[0] : null;
$is_view_filtered = ($category_filter > 0 || $stock_filter !== 'all' || $search_query !== '');

// Categories for Dropdown Filter
$categories = fetch_all("SELECT * FROM categories ORDER BY name ASC");

// -------------------------------------------------------------
// 5. CHARTS & DEEPER BUSINESS INTELLIGENCE
// -------------------------------------------------------------

// Average Spent Per Order (AOV)
$period_aov = $total_orders_period > 0 ? round($total_store_revenue / $total_orders_period) : 0;

// Average Items Per Order (Basket Size)
$items_per_order = $total_orders_period > 0 ? round($total_store_items_sold / $total_orders_period, 1) : 0;

// True Repeat Buyers (Registered + Guest Accounts Normalized by Email)
$customer_stats = fetch_one("
    SELECT 
        COUNT(DISTINCT customer_email) as total_unique_buyers,
        SUM(CASE WHEN order_count > 1 THEN 1 ELSE 0 END) as repeat_buyers_count
    FROM (
        SELECT 
            LOWER(TRIM(COALESCE(
                u.email, 
                CASE WHEN JSON_VALID(o.shipping_address) = 1 THEN JSON_UNQUOTE(JSON_EXTRACT(o.shipping_address, '$.email')) ELSE NULL END, 
                CONCAT('order_', o.id)
            ))) as customer_email,
            COUNT(o.id) as order_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.order_status NOT IN ($excluded_statuses) $date_sql
        GROUP BY customer_email
    ) t
");
$total_unique_buyers = (int)($customer_stats['total_unique_buyers'] ?? 0);
$repeat_customers_count = (int)($customer_stats['repeat_buyers_count'] ?? 0);
$repeat_rate = $total_unique_buyers > 0 ? round(($repeat_customers_count / $total_unique_buyers) * 100, 1) : 0;

// Discounts Given Through Coupons
$period_discounts = fetch_one("
    SELECT 
        COALESCE(SUM(o.discount), 0) as total_discount_amount, 
        COUNT(CASE WHEN o.discount > 0 THEN 1 END) as discount_orders_count
    FROM orders o
    WHERE o.order_status NOT IN ($excluded_statuses) $date_sql
") ?? ['total_discount_amount' => 0, 'discount_orders_count' => 0];

// Affiliate & Partner Sales
$affiliate_stats = fetch_one("
    SELECT 
        COALESCE(SUM(o.total), 0) as total_sales, 
        COALESCE(SUM(o.affiliate_commission), 0) as total_commission
    FROM orders o
    WHERE o.affiliate_id IS NOT NULL AND o.order_status NOT IN ($excluded_statuses) $date_sql
") ?? ['total_sales' => 0, 'total_commission' => 0];

// Approved Customer Reviews
$review_stats = fetch_one("
    SELECT 
        COUNT(id) as total_reviews, 
        COALESCE(ROUND(AVG(rating), 1), 5.0) as avg_rating
    FROM reviews
    WHERE is_approved = 1
") ?? ['total_reviews' => 0, 'avg_rating' => 5.0];

// Abandoned / Unpaid Carts
$abandoned_date_sql = "";
if ($range === 'today') $abandoned_date_sql = "WHERE DATE(created_at) = CURDATE()";
elseif ($range === '7') $abandoned_date_sql = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
elseif ($range === '30') $abandoned_date_sql = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
elseif ($range === '90') $abandoned_date_sql = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
elseif ($range === '365') $abandoned_date_sql = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";

$abandoned_count = (int)(fetch_one("SELECT COUNT(id) as c FROM abandoned_carts $abandoned_date_sql")['c'] ?? 0);

// Timeline Trend Data (Daily Sales Money & Items Sold)
$timeline_points = [];
if ($range === 'today') {
    for ($h = 0; $h <= 23; $h++) {
        $hr_key = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
        $timeline_points[$hr_key] = ['label' => $hr_key, 'revenue' => 0, 'units' => 0];
    }
    $hourly_sales = fetch_all("
        SELECT 
            DATE_FORMAT(o.created_at, '%H:00') as hr, 
            COALESCE(SUM(oi.subtotal), 0) as rev, 
            COALESCE(SUM(oi.quantity), 0) as units
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE DATE(o.created_at) = CURDATE() AND o.order_status NOT IN ($excluded_statuses)
        GROUP BY hr
    ");
    foreach ($hourly_sales as $hs) {
        if (isset($timeline_points[$hs['hr']])) {
            $timeline_points[$hs['hr']]['revenue'] = (float)$hs['rev'];
            $timeline_points[$hs['hr']]['units'] = (int)$hs['units'];
        }
    }
} elseif (in_array($range, ['7', '30'])) {
    $num_days = (int)$range;
    for ($i = $num_days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $lbl = $range === '7' ? date('D, d M', strtotime($d)) : date('d M', strtotime($d));
        $timeline_points[$d] = ['label' => $lbl, 'revenue' => 0, 'units' => 0];
    }
    $daily_sales = fetch_all("
        SELECT 
            DATE(o.created_at) as dt, 
            COALESCE(SUM(oi.subtotal), 0) as rev, 
            COALESCE(SUM(oi.quantity), 0) as units
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL $num_days DAY) AND o.order_status NOT IN ($excluded_statuses)
        GROUP BY dt
    ");
    foreach ($daily_sales as $ds) {
        if (isset($timeline_points[$ds['dt']])) {
            $timeline_points[$ds['dt']]['revenue'] = (float)$ds['rev'];
            $timeline_points[$ds['dt']]['units'] = (int)$ds['units'];
        }
    }
} else {
    // 90 days, 1 year or All Time: Group by month (last 12 months)
    for ($i = 11; $i >= 0; $i--) {
        $ym = date('Y-m', strtotime("-$i months"));
        $lbl = date('M Y', strtotime("-$i months"));
        $timeline_points[$ym] = ['label' => $lbl, 'revenue' => 0, 'units' => 0];
    }
    $monthly_sales = fetch_all("
        SELECT 
            DATE_FORMAT(o.created_at, '%Y-%m') as ym, 
            COALESCE(SUM(oi.subtotal), 0) as rev, 
            COALESCE(SUM(oi.quantity), 0) as units
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR) AND o.order_status NOT IN ($excluded_statuses)
        GROUP BY ym
    ");
    foreach ($monthly_sales as $ms) {
        if (isset($timeline_points[$ms['ym']])) {
            $timeline_points[$ms['ym']]['revenue'] = (float)$ms['rev'];
            $timeline_points[$ms['ym']]['units'] = (int)$ms['units'];
        }
    }
}
$timeline_chart_data = array_values($timeline_points);

// Top 5 Products for Bar Chart
$top_products_chart = array_slice(array_filter($product_stats, fn($p) => $p['revenue_generated'] > 0), 0, 5);

// Sales by Category for Doughnut Chart
$cat_revenue_breakdown = fetch_all("
    SELECT 
        c.name, 
        COALESCE(SUM(oi.subtotal), 0) as revenue,
        COALESCE(SUM(oi.quantity), 0) as units
    FROM categories c
    JOIN products p ON c.id = p.category_id
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_status NOT IN ($excluded_statuses) $date_sql
    GROUP BY c.id
    ORDER BY revenue DESC
");

// Combo Packs vs Single Packs Performance
$combo_stats = fetch_one("
    SELECT 
        COALESCE(SUM(CASE WHEN p.is_combo = 1 THEN oi.subtotal ELSE 0 END), 0) as combo_revenue,
        COALESCE(SUM(CASE WHEN p.is_combo = 1 THEN oi.quantity ELSE 0 END), 0) as combo_units,
        COALESCE(SUM(CASE WHEN p.is_combo = 0 THEN oi.subtotal ELSE 0 END), 0) as single_revenue,
        COALESCE(SUM(CASE WHEN p.is_combo = 0 THEN oi.quantity ELSE 0 END), 0) as single_units,
        COUNT(DISTINCT CASE WHEN p.is_combo = 1 THEN p.id END) as combo_count,
        COUNT(DISTINCT CASE WHEN p.is_combo = 0 THEN p.id END) as single_count
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.order_status NOT IN ($excluded_statuses) $date_sql
    WHERE p.is_active = 1
");

// In-Stock Items With Zero Sales in Period
$deadstock_products = array_values(array_filter($product_stats, fn($p) => $p['units_sold'] == 0 && $p['stock'] > 0));
$deadstock_capital = array_sum(array_map(fn($p) => $p['stock'] * $p['price'], $deadstock_products));

// Low Stock Alert (Sorted by lowest stock remaining)
$urgent_reorder_list = array_values(array_filter($product_stats, fn($p) => $p['stock'] < 10));
usort($urgent_reorder_list, fn($a, $b) => $a['stock'] <=> $b['stock']);
$urgent_reorder_list = array_slice($urgent_reorder_list, 0, 4);

// Most Popular Products by Volume Sold
$volume_movers = array_values(array_filter($product_stats, fn($p) => $p['units_sold'] > 0));
usort($volume_movers, fn($a, $b) => $b['units_sold'] <=> $a['units_sold']);
$volume_movers = array_slice($volume_movers, 0, 4);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- PAGE TITLE & DATE CONTROLS -->
<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
            <i class="fas fa-chart-line text-[#004f42]"></i>
            Product Sales & Stock Report
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">Simple, clear numbers on what is selling, total money made, and items that need restocking.</p>
    </div>

    <!-- Date Range Picker & CSV Download Button -->
    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
        <!-- Date Range Buttons -->
        <div class="bg-white p-1 rounded-xl border border-slate-200 shadow-2xs flex items-center gap-1 text-xs font-semibold">
            <a href="analytics.php?range=today<?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $stock_filter !== 'all' ? '&stock=' . urlencode($stock_filter) : ''; ?>" class="px-3 py-1.5 rounded-lg transition-colors <?php echo $range === 'today' ? 'bg-[#004f42] text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">Today</a>
            <a href="analytics.php?range=7<?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $stock_filter !== 'all' ? '&stock=' . urlencode($stock_filter) : ''; ?>" class="px-3 py-1.5 rounded-lg transition-colors <?php echo $range === '7' ? 'bg-[#004f42] text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">7 Days</a>
            <a href="analytics.php?range=30<?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $stock_filter !== 'all' ? '&stock=' . urlencode($stock_filter) : ''; ?>" class="px-3 py-1.5 rounded-lg transition-colors <?php echo $range === '30' ? 'bg-[#004f42] text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">30 Days</a>
            <a href="analytics.php?range=90<?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $stock_filter !== 'all' ? '&stock=' . urlencode($stock_filter) : ''; ?>" class="px-3 py-1.5 rounded-lg transition-colors <?php echo $range === '90' ? 'bg-[#004f42] text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">90 Days</a>
            <a href="analytics.php?range=365<?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $stock_filter !== 'all' ? '&stock=' . urlencode($stock_filter) : ''; ?>" class="px-3 py-1.5 rounded-lg transition-colors <?php echo $range === '365' ? 'bg-[#004f42] text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">1 Year</a>
            <a href="analytics.php?range=all<?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $stock_filter !== 'all' ? '&stock=' . urlencode($stock_filter) : ''; ?>" class="px-3 py-1.5 rounded-lg transition-colors <?php echo $range === 'all' ? 'bg-[#004f42] text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">All Time</a>
        </div>

        <!-- Download CSV with current filters preserved -->
        <?php
        $export_query_params = http_build_query([
            'export' => 'csv',
            'range' => $range,
            'category' => $category_filter,
            'stock' => $stock_filter,
            'q' => $search_query
        ]);
        ?>
        <a href="analytics.php?<?php echo $export_query_params; ?>" class="btn-admin btn-admin-secondary text-xs" title="Download Excel or CSV file of this report">
            <i class="fas fa-file-csv text-emerald-600"></i> Download CSV
        </a>
    </div>
</div>

<!-- ROW 1: PRIMARY SALES & INVENTORY NUMBERS (6 CARDS) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    
    <!-- 1. Total Sales (Money Made) -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Sales</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#004f42] flex items-center justify-center text-xs">
                <i class="fas fa-rupee-sign"></i>
            </div>
        </div>
        <div>
            <div class="text-xl font-extrabold text-slate-900 leading-none">₹<?php echo number_format($filtered_revenue, 0); ?></div>
            <p class="text-[11px] text-slate-400 mt-1.5 truncate">
                <?php if ($is_view_filtered): ?>
                    Filtered items (All: ₹<?php echo number_format($total_store_product_sales, 0); ?>)
                <?php else: ?>
                    <?php echo htmlspecialchars($range_label); ?> sales
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- 2. Items Sold -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Items Sold</span>
            <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-xs">
                <i class="fas fa-boxes-stacked"></i>
            </div>
        </div>
        <div>
            <div class="text-xl font-extrabold text-slate-900 leading-none"><?php echo number_format($filtered_units_sold); ?></div>
            <p class="text-[11px] text-slate-400 mt-1.5">Packs & jars sold</p>
        </div>
    </div>

    <!-- 3. Items Per Customer Order -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Items Per Order</span>
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                <i class="fas fa-basket-shopping"></i>
            </div>
        </div>
        <div>
            <div class="text-xl font-extrabold text-slate-900 leading-none">
                <?php echo $items_per_order; ?>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">Average items per basket</p>
        </div>
    </div>

    <!-- 4. Top Selling Product -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Top Product</span>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                <i class="fas fa-crown"></i>
            </div>
        </div>
        <div class="min-w-0">
            <div class="text-base font-bold text-slate-900 truncate" title="<?php echo htmlspecialchars($top_earner['name'] ?? 'None'); ?>">
                <?php echo htmlspecialchars($top_earner['name'] ?? 'No sales'); ?>
            </div>
            <p class="text-[11px] text-emerald-600 font-semibold mt-1">
                ₹<?php echo number_format($top_earner['revenue_generated'] ?? 0); ?> in sales
            </p>
        </div>
    </div>

    <!-- 5. Total Warehouse Stock Value -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Stock Value</span>
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs">
                <i class="fas fa-warehouse"></i>
            </div>
        </div>
        <div>
            <div class="text-xl font-extrabold text-slate-900 leading-none">₹<?php echo number_format($catalog_stats['total_stock_valuation'] ?? 0, 0); ?></div>
            <p class="text-[11px] text-slate-400 mt-1.5"><?php echo number_format($catalog_stats['total_units_in_stock'] ?? 0); ?> items in warehouse</p>
        </div>
    </div>

    <!-- 6. Stock Health Status -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Stock Status</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                <i class="fas fa-cubes"></i>
            </div>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-emerald-600"><?php echo (int)($catalog_stats['healthy_stock'] ?? 0); ?> in stock</span>
                <span class="text-xs text-slate-300">/</span>
                <span class="text-xs font-bold text-rose-600"><?php echo (int)($catalog_stats['out_of_stock'] ?? 0); ?> out</span>
            </div>
            <p class="text-[11px] text-amber-600 font-semibold mt-1">
                <?php echo (int)($catalog_stats['low_stock'] ?? 0); ?> items low (&lt;10 left)
            </p>
        </div>
    </div>

</div>

<!-- ROW 2: ORDER & CUSTOMER INTELLIGENCE (6 CARDS) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    
    <!-- 7. Average Order Bill (AOV) -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Average Bill</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs">
                <i class="fas fa-receipt"></i>
            </div>
        </div>
        <div>
            <div class="text-xl font-extrabold text-slate-900 leading-none">₹<?php echo number_format($period_aov, 0); ?></div>
            <p class="text-[11px] text-slate-400 mt-1.5">Average customer spent</p>
        </div>
    </div>

    <!-- 8. Repeat Buyers -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Repeat Buyers</span>
            <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-xs">
                <i class="fas fa-rotate-right"></i>
            </div>
        </div>
        <div>
            <div class="text-xl font-extrabold text-slate-900 leading-none"><?php echo $repeat_rate; ?>%</div>
            <p class="text-[11px] text-slate-400 mt-1.5">
                <?php echo $repeat_customers_count; ?> of <?php echo $total_unique_buyers; ?> buyers bought again
            </p>
        </div>
    </div>

    <!-- 9. Discounts Given -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Discounts Given</span>
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                <i class="fas fa-tags"></i>
            </div>
        </div>
        <div>
            <div class="text-xl font-extrabold text-slate-900 leading-none">₹<?php echo number_format($period_discounts['total_discount_amount'] ?? 0, 0); ?></div>
            <p class="text-[11px] text-slate-400 mt-1.5">Saved across <?php echo (int)($period_discounts['discount_orders_count'] ?? 0); ?> coupon orders</p>
        </div>
    </div>

    <!-- 10. Partner / Affiliate Sales -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Affiliate Sales</span>
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                <i class="fas fa-user-check"></i>
            </div>
        </div>
        <div>
            <div class="text-xl font-extrabold text-slate-900 leading-none">₹<?php echo number_format($affiliate_stats['total_sales'] ?? 0, 0); ?></div>
            <p class="text-[11px] text-indigo-600 font-semibold mt-1.5">₹<?php echo number_format($affiliate_stats['total_commission'] ?? 0, 0); ?> commission paid</p>
        </div>
    </div>

    <!-- 11. Customer Reviews Rating -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Customer Rating</span>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center text-xs">
                <i class="fas fa-star"></i>
            </div>
        </div>
        <div>
            <div class="text-xl font-extrabold text-slate-900 leading-none flex items-center gap-1.5">
                <span><?php echo number_format($review_stats['avg_rating'] ?? 5.0, 1); ?></span>
                <span class="text-xs text-amber-500"><i class="fas fa-star"></i></span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5"><?php echo (int)($review_stats['total_reviews'] ?? 0); ?> verified buyer reviews</p>
        </div>
    </div>

    <!-- 12. Carts Left Without Buying -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Unpaid Carts</span>
            <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xs">
                <i class="fas fa-cart-arrow-down"></i>
            </div>
        </div>
        <div>
            <div class="text-xl font-extrabold text-slate-900 leading-none"><?php echo $abandoned_count; ?></div>
            <a href="abandoned_carts.php" class="text-[11px] text-orange-600 font-semibold mt-1.5 hover:underline flex items-center gap-1">
                <span>View Carts</span> <i class="fas fa-arrow-right text-[9px]"></i>
            </a>
        </div>
    </div>

</div>

<!-- TIMELINE CHART: DAILY SALES AND ITEMS SOLD -->
<div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs mb-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
        <div>
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-chart-area text-[#004f42]"></i>
                Sales Money & Items Sold Over Time
            </h3>
            <p class="text-xs text-slate-400">See day-by-day sales in rupees alongside the total number of items sold.</p>
        </div>
        <div class="flex items-center gap-4 text-xs">
            <span class="flex items-center gap-1.5 font-semibold text-slate-700">
                <span class="w-3 h-3 rounded bg-[#004f42]"></span> Money Earned (₹)
            </span>
            <span class="flex items-center gap-1.5 font-semibold text-slate-700">
                <span class="w-3 h-3 rounded bg-sky-400"></span> Items Sold (Count)
            </span>
        </div>
    </div>
    <div class="h-72">
        <canvas id="timelineSalesChart"></canvas>
    </div>
</div>

<!-- COMPARISON CHARTS: TOP PRODUCTS & CATEGORIES -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    
    <!-- Top 5 Best-Selling Products Bar Chart -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-chart-bar text-[#004f42]"></i>
                    Top 5 Best-Selling Products
                </h3>
                <p class="text-xs text-slate-400">Products that generated the most sales money in this period</p>
            </div>
            <span class="text-xs font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">
                Top 5
            </span>
        </div>
        <div class="h-64">
            <canvas id="topProductsChart"></canvas>
        </div>
    </div>

    <!-- Sales by Category Doughnut Chart -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-pie-chart text-emerald-600"></i>
                    Sales by Category
                </h3>
                <p class="text-xs text-slate-400">Which categories bring in the most money</p>
            </div>
        </div>
        <div class="h-64 flex items-center justify-center">
            <canvas id="categoryShareChart"></canvas>
        </div>
    </div>

</div>

<!-- 4 BUSINESS INSIGHT PANELS (SIMPLE WORDS) -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    
    <!-- Panel 1: Most Popular Products (By Volume) -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-fire text-amber-500"></i> Most Popular Products
                </span>
                <span class="text-[10px] bg-amber-50 text-amber-700 font-bold px-1.5 py-0.5 rounded border border-amber-200">Most Sold</span>
            </div>
            <div class="space-y-2.5">
                <?php if (empty($volume_movers)): ?>
                    <p class="text-xs text-slate-400 py-3 text-center">No items sold in this period yet</p>
                <?php else: foreach ($volume_movers as $vm): ?>
                    <div class="flex items-center justify-between text-xs">
                        <div class="min-w-0 pr-2">
                            <a href="product_form.php?id=<?php echo $vm['id']; ?>" class="font-semibold text-slate-800 hover:text-[#004f42] truncate block">
                                <?php echo htmlspecialchars($vm['name']); ?>
                            </a>
                            <span class="text-[10px] text-slate-400">₹<?php echo number_format($vm['price']); ?> per pack</span>
                        </div>
                        <span class="font-extrabold text-[#004f42] shrink-0 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">
                            <?php echo number_format($vm['units_sold']); ?> sold
                        </span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Panel 2: In-Stock Items With Zero Sales -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-pause-circle text-purple-500"></i> In Stock (Zero Sales)
                </span>
                <span class="text-[10px] bg-purple-50 text-purple-700 font-bold px-1.5 py-0.5 rounded border border-purple-200" title="Total rupee value of unsold stock">
                    ₹<?php echo number_format($deadstock_capital, 0); ?>
                </span>
            </div>
            <div class="space-y-2.5">
                <?php if (empty($deadstock_products)): ?>
                    <p class="text-xs text-emerald-600 py-3 text-center font-semibold">
                        <i class="fas fa-check-circle mr-1"></i> Great! Every stocked item has sales.
                    </p>
                <?php else: foreach (array_slice($deadstock_products, 0, 4) as $dp): ?>
                    <div class="flex items-center justify-between text-xs">
                        <div class="min-w-0 pr-2">
                            <a href="product_form.php?id=<?php echo $dp['id']; ?>" class="font-semibold text-slate-800 hover:text-purple-700 truncate block">
                                <?php echo htmlspecialchars($dp['name']); ?>
                            </a>
                            <span class="text-[10px] text-slate-400"><?php echo $dp['stock']; ?> items sitting in stock</span>
                        </div>
                        <span class="font-bold text-purple-700 shrink-0 bg-purple-50 px-2 py-0.5 rounded-md border border-purple-100">
                            ₹<?php echo number_format($dp['stock'] * $dp['price']); ?>
                        </span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Panel 3: Low Stock Warning -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-triangle-exclamation text-rose-500"></i> Low Stock Alert
                </span>
                <span class="text-[10px] bg-rose-50 text-rose-700 font-bold px-1.5 py-0.5 rounded border border-rose-200">
                    &lt; 10 left
                </span>
            </div>
            <div class="space-y-2.5">
                <?php if (empty($urgent_reorder_list)): ?>
                    <p class="text-xs text-emerald-600 py-3 text-center font-semibold">
                        <i class="fas fa-check-circle mr-1"></i> All stock levels are healthy!
                    </p>
                <?php else: foreach ($urgent_reorder_list as $ur): ?>
                    <div class="flex items-center justify-between text-xs">
                        <div class="min-w-0 pr-2">
                            <a href="product_form.php?id=<?php echo $ur['id']; ?>" class="font-semibold text-slate-800 hover:text-rose-700 truncate block">
                                <?php echo htmlspecialchars($ur['name']); ?>
                            </a>
                            <span class="text-[10px] text-slate-400">SKU: <?php echo htmlspecialchars($ur['sku'] ?: '#P'.$ur['id']); ?></span>
                        </div>
                        <span class="font-black <?php echo $ur['stock'] <= 0 ? 'text-rose-700 bg-rose-50 border-rose-200' : 'text-amber-700 bg-amber-50 border-amber-200'; ?> shrink-0 px-2 py-0.5 rounded-md border text-[11px]">
                            <?php echo $ur['stock'] <= 0 ? 'Sold out' : $ur['stock'] . ' left'; ?>
                        </span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Panel 4: Combo Packs vs Single Packs -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-box-open text-sky-600"></i> Combo Packs vs Singles
                </span>
                <span class="text-[10px] bg-sky-50 text-sky-700 font-bold px-1.5 py-0.5 rounded border border-sky-200">Comparison</span>
            </div>
            <div class="space-y-3 text-xs">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-semibold text-slate-700">🎁 Combo Sets</span>
                        <span class="font-extrabold text-slate-900">₹<?php echo number_format($combo_stats['combo_revenue'] ?? 0); ?></span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span><?php echo (int)($combo_stats['combo_units'] ?? 0); ?> combos sold</span>
                        <span><?php echo (int)($combo_stats['combo_count'] ?? 0); ?> combo listings</span>
                    </div>
                </div>
                <div class="pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-semibold text-slate-700">📦 Single Packs</span>
                        <span class="font-extrabold text-slate-900">₹<?php echo number_format($combo_stats['single_revenue'] ?? 0); ?></span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span><?php echo (int)($combo_stats['single_units'] ?? 0); ?> singles sold</span>
                        <span><?php echo (int)($combo_stats['single_count'] ?? 0); ?> single listings</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- MASTER PRODUCTS TABLE -->
<div class="admin-card p-0 overflow-hidden">
    
    <!-- Table Controls Filter Bar -->
    <div class="p-4 border-b border-slate-200 bg-slate-50/70 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <h2 class="text-sm font-bold text-slate-900">All Products & Performance Details</h2>
            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-bold"><?php echo count($product_stats); ?> products</span>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="analytics.php" class="flex flex-wrap items-center gap-2 text-xs">
            <input type="hidden" name="range" value="<?php echo htmlspecialchars($range); ?>">

            <!-- Search input -->
            <div class="relative">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search product or SKU..." class="admin-input pl-7 py-1 text-xs w-44 sm:w-56">
            </div>

            <!-- Category Filter -->
            <select name="category" onchange="this.form.submit()" class="admin-select py-1 px-2 text-xs font-semibold w-36">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Stock Filter -->
            <select name="stock" onchange="this.form.submit()" class="admin-select py-1 px-2 text-xs font-semibold w-32">
                <option value="all" <?php echo $stock_filter === 'all' ? 'selected' : ''; ?>>All Stock</option>
                <option value="healthy" <?php echo $stock_filter === 'healthy' ? 'selected' : ''; ?>>In Stock (10+)</option>
                <option value="low" <?php echo $stock_filter === 'low' ? 'selected' : ''; ?>>Low Stock (&lt;10)</option>
                <option value="out" <?php echo $stock_filter === 'out' ? 'selected' : ''; ?>>Sold Out</option>
            </select>

            <!-- Sort By Dropdown with Simple English Labels -->
            <select name="sort" onchange="this.form.submit()" class="admin-select py-1 px-2 text-xs font-semibold w-40">
                <option value="revenue" <?php echo $sort_by === 'revenue' ? 'selected' : ''; ?>>Sort: Most Money Made</option>
                <option value="units" <?php echo $sort_by === 'units' ? 'selected' : ''; ?>>Sort: Most Items Sold</option>
                <option value="stock" <?php echo $sort_by === 'stock' ? 'selected' : ''; ?>>Sort: Units in Stock</option>
                <option value="price" <?php echo $sort_by === 'price' ? 'selected' : ''; ?>>Sort: Price</option>
                <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Sort: Product Name</option>
            </select>

            <button type="submit" class="btn-admin btn-admin-primary py-1 px-2.5 text-xs">
                Filter
            </button>
            <?php if ($category_filter || $stock_filter !== 'all' || $search_query): ?>
                <a href="analytics.php?range=<?php echo urlencode($range); ?>" class="btn-admin btn-admin-secondary py-1 px-2 text-xs" title="Reset all filters">
                    <i class="fas fa-undo"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Master Table with Alternate Striped Rows -->
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th class="w-12 text-center">#</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th class="text-center">Rating</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Items Sold</th>
                    <th class="text-right">Money Made</th>
                    <th class="text-center">% of Sales</th>
                    <th class="text-center">Orders</th>
                    <th class="text-center">In Stock</th>
                    <th class="text-right">Stock Value</th>
                    <th class="text-center">Daily Sales</th>
                    <th class="text-center">Days Left</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($product_stats)): ?>
                    <tr>
                        <td colspan="15" class="p-12 text-center text-slate-400">
                            <i class="fas fa-boxes-stacked text-3xl mb-2 text-slate-300"></i>
                            <p class="text-sm font-semibold text-slate-700">No products match your filter</p>
                            <p class="text-xs text-slate-400 mt-1">Try resetting the category, stock level, or search query.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $rank = 1;
                    $max_units = max(array_column($product_stats, 'units_sold') ?: [1]);
                    if ($max_units <= 0) $max_units = 1;

                    foreach ($product_stats as $p): 
                        $rev_share = $filtered_revenue > 0 ? ($p['revenue_generated'] / $filtered_revenue) * 100 : 0;
                        $units_bar_pct = min(100, round(($p['units_sold'] / $max_units) * 100));
                        $stock_val = $p['stock'] * $p['price'];
                        $daily_velocity = $days_count > 0 ? ($p['units_sold'] / $days_count) : 0;
                        
                        // Simple, human-friendly days left indicator
                        if ($p['stock'] <= 0) {
                            $days_left_text = "Sold out";
                            $days_left_badge = "text-rose-700 bg-rose-50 border-rose-200 font-bold";
                            $days_left_sub = "0 left";
                        } elseif ($daily_velocity <= 0) {
                            $days_left_text = "No sales yet";
                            $days_left_badge = "text-slate-600 bg-slate-100 border-slate-200";
                            $days_left_sub = "Not moving";
                        } else {
                            $calc_days = (int)ceil($p['stock'] / $daily_velocity);
                            if ($calc_days <= 7) {
                                $days_left_text = "~{$calc_days}d left";
                                $days_left_badge = "text-rose-700 bg-rose-50 border-rose-200 font-extrabold";
                                $days_left_sub = "Runs out soon!";
                            } elseif ($calc_days <= 30) {
                                $days_left_text = "~{$calc_days}d left";
                                $days_left_badge = "text-amber-800 bg-amber-50 border-amber-200 font-bold";
                                $days_left_sub = "Restock soon";
                            } else {
                                $days_left_text = "{$calc_days}+ days";
                                $days_left_badge = "text-emerald-800 bg-emerald-50 border-emerald-200 font-semibold";
                                $days_left_sub = "Plenty left";
                            }
                        }
                    ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <!-- Rank -->
                            <td class="text-center text-xs font-bold text-slate-400">
                                <?php echo $rank++; ?>
                            </td>

                            <!-- Product Info -->
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden shrink-0">
                                        <?php if ($p['image']): ?>
                                            <img src="../<?php echo htmlspecialchars($p['image']); ?>" alt="" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-slate-400 text-xs">
                                                <i class="fas fa-box"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <a href="product_form.php?id=<?php echo $p['id']; ?>" class="font-bold text-slate-900 hover:text-[#004f42] text-xs block truncate max-w-xs transition-colors">
                                            <?php echo htmlspecialchars($p['name']); ?>
                                        </a>
                                        <div class="flex items-center gap-1.5 mt-0.5 text-[11px] text-slate-400">
                                            <span>SKU: <?php echo htmlspecialchars($p['sku'] ?: ('#P' . $p['id'])); ?></span>
                                            <?php if ($p['is_combo']): ?>
                                                <span class="px-1 py-0.2 rounded bg-purple-50 text-purple-700 text-[10px] font-bold border border-purple-200">Combo</span>
                                            <?php endif; ?>
                                            <?php if ($p['is_featured']): ?>
                                                <span class="px-1 py-0.2 rounded bg-amber-50 text-amber-700 text-[10px] font-bold border border-amber-200">Featured</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="text-xs text-slate-600 font-medium">
                                <?php echo htmlspecialchars($p['category_name'] ?: 'Uncategorized'); ?>
                            </td>

                            <!-- Rating -->
                            <td class="text-center text-xs">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-amber-50 text-amber-800 font-bold border border-amber-200 text-[11px]">
                                    <i class="fas fa-star text-amber-500 text-[10px]"></i>
                                    <span><?php echo number_format($p['avg_rating'], 1); ?></span>
                                    <span class="text-slate-400 font-normal text-[9px]">(<?php echo (int)$p['reviews_count']; ?>)</span>
                                </span>
                            </td>

                            <!-- Price -->
                            <td class="text-right font-bold text-slate-900 text-xs">
                                ₹<?php echo number_format($p['price']); ?>
                                <?php if ($p['original_price'] > $p['price']): ?>
                                    <span class="block text-[10px] line-through text-slate-400 font-normal">₹<?php echo number_format($p['original_price']); ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Items Sold with Visual Popularity Bar -->
                            <td class="text-right">
                                <div class="font-bold text-slate-900 text-xs">
                                    <?php echo number_format($p['units_sold']); ?>
                                </div>
                                <div class="w-16 h-1.5 bg-slate-100 rounded-full ml-auto mt-1 overflow-hidden" title="Relative popularity: <?php echo $units_bar_pct; ?>%">
                                    <div class="h-full bg-[#004f42] rounded-full" style="width: <?php echo $units_bar_pct; ?>%"></div>
                                </div>
                            </td>

                            <!-- Money Made -->
                            <td class="text-right font-extrabold text-[#004f42] text-xs">
                                ₹<?php echo number_format($p['revenue_generated']); ?>
                            </td>

                            <!-- Share % of Sales -->
                            <td class="text-center">
                                <span class="px-1.5 py-0.5 rounded text-[11px] font-bold <?php echo $rev_share >= 15 ? 'bg-emerald-100 text-emerald-900' : ($rev_share >= 5 ? 'bg-slate-100 text-slate-800' : 'text-slate-500'); ?>">
                                    <?php echo number_format($rev_share, 1); ?>%
                                </span>
                            </td>

                            <!-- Orders Count -->
                            <td class="text-center font-semibold text-slate-700 text-xs">
                                <?php echo (int)$p['order_count']; ?>
                            </td>

                            <!-- Units in Stock -->
                            <td class="text-center">
                                <?php if ($p['stock'] <= 0): ?>
                                    <span class="admin-badge admin-badge-danger">Sold out</span>
                                <?php elseif ($p['stock'] < 10): ?>
                                    <span class="admin-badge admin-badge-warning"><?php echo $p['stock']; ?> left</span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-success"><?php echo $p['stock']; ?> in stock</span>
                                <?php endif; ?>
                            </td>

                            <!-- Stock Value -->
                            <td class="text-right font-semibold text-slate-700 text-xs">
                                ₹<?php echo number_format($stock_val); ?>
                            </td>

                            <!-- Daily Sales -->
                            <td class="text-center text-xs font-semibold text-slate-600">
                                <?php echo number_format($daily_velocity, 2); ?>/day
                            </td>

                            <!-- Days Left (Simple Language) -->
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] border <?php echo $days_left_badge; ?>" title="<?php echo htmlspecialchars($days_left_sub); ?>">
                                    <?php echo $days_left_text; ?>
                                </span>
                            </td>

                            <!-- Active / Draft Status -->
                            <td class="text-center">
                                <span class="w-2.5 h-2.5 rounded-full inline-block <?php echo $p['is_active'] ? 'bg-emerald-500' : 'bg-slate-300'; ?>" title="<?php echo $p['is_active'] ? 'Active in store' : 'Draft / Hidden'; ?>"></span>
                            </td>

                            <!-- Actions -->
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="product_form.php?id=<?php echo $p['id']; ?>" class="p-1.5 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100 transition-colors" title="Edit product details & stock">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <a href="../product/<?php echo urlencode($p['slug']); ?>" target="_blank" class="p-1.5 text-slate-400 hover:text-emerald-700 rounded hover:bg-emerald-50 transition-colors" title="Open product on storefront">
                                        <i class="fas fa-external-link-alt text-xs"></i>
                                    </a>
                                    <a href="orders.php?q=<?php echo urlencode($p['name']); ?>" class="p-1.5 text-slate-400 hover:text-sky-700 rounded hover:bg-sky-50 transition-colors" title="View customer orders containing this product">
                                        <i class="fas fa-box text-xs"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($product_stats)): ?>
                <tfoot>
                    <tr class="bg-slate-100 font-bold border-t-2 border-slate-300 text-xs text-slate-800">
                        <td colspan="5" class="text-right pr-4 uppercase tracking-wider text-[11px] text-slate-600">Total for visible items:</td>
                        <td class="text-right text-slate-900"><?php echo number_format($filtered_units_sold); ?></td>
                        <td class="text-right text-[#004f42]">₹<?php echo number_format($filtered_revenue); ?></td>
                        <td class="text-center">100%</td>
                        <td class="text-center"><?php echo array_sum(array_column($product_stats, 'order_count')); ?></td>
                        <td class="text-center"><?php echo array_sum(array_column($product_stats, 'stock')); ?></td>
                        <td class="text-right text-slate-900">₹<?php echo number_format(array_sum(array_map(fn($p) => $p['stock'] * $p['price'], $product_stats))); ?></td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>

</div>

<!-- CHART.JS SCRIPTS WITH CLEAR, SIMPLE LABELS -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Dual-Axis Timeline Line Chart (Sales Money ₹ + Items Sold)
    const timeCtx = document.getElementById('timelineSalesChart');
    if (timeCtx) {
        const timeData = <?php echo json_encode($timeline_chart_data); ?>;
        new Chart(timeCtx, {
            type: 'line',
            data: {
                labels: timeData.map(d => d.label),
                datasets: [
                    {
                        label: 'Money Earned (₹)',
                        data: timeData.map(d => d.revenue),
                        borderColor: '#004f42',
                        backgroundColor: 'rgba(0, 79, 66, 0.08)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#004f42',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Items Sold',
                        data: timeData.map(d => d.units),
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        borderWidth: 2,
                        borderDash: [4, 4],
                        fill: false,
                        tension: 0.35,
                        pointBackgroundColor: '#0ea5e9',
                        pointRadius: 2.5,
                        pointHoverRadius: 4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                if (ctx.datasetIndex === 0) {
                                    return ' Money Earned: ₹' + Number(ctx.raw).toLocaleString('en-IN');
                                } else {
                                    return ' Items Sold: ' + Number(ctx.raw).toLocaleString('en-IN') + ' packs';
                                }
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            callback: val => '₹' + (val >= 1000 ? (val/1000).toFixed(0) + 'k' : val),
                            font: { size: 10 }
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: {
                            callback: val => val + ' pcs',
                            font: { size: 10 }
                        }
                    },
                    x: {
                        ticks: { font: { size: 10 }, maxRotation: 45 },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Top 5 Products Bar Chart
    const topCtx = document.getElementById('topProductsChart');
    if (topCtx) {
        new Chart(topCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(fn($p) => mb_substr($p['name'], 0, 18) . '...', $top_products_chart)); ?>,
                datasets: [{
                    label: 'Sales (₹)',
                    data: <?php echo json_encode(array_column($top_products_chart, 'revenue_generated')); ?>,
                    backgroundColor: '#004f42',
                    borderRadius: 8,
                    hoverBackgroundColor: '#003a31'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' Sales: ₹' + Number(ctx.raw).toLocaleString('en-IN');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: val => '₹' + (val >= 1000 ? (val/1000).toFixed(0) + 'k' : val),
                            font: { size: 10 }
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        ticks: { font: { size: 10 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 3. Sales by Category Doughnut Chart
    const catCtx = document.getElementById('categoryShareChart');
    if (catCtx) {
        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($cat_revenue_breakdown, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($cat_revenue_breakdown, 'revenue')); ?>,
                    backgroundColor: [
                        '#004f42',
                        '#24B25D',
                        '#0ea5e9',
                        '#f59e0b',
                        '#8b5cf6',
                        '#ec4899',
                        '#64748b'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 10 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.label + ': ₹' + Number(ctx.raw).toLocaleString('en-IN');
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
