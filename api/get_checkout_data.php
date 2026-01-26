<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

$conn = get_db_connection();

// Calculate Subtotal and Validate Stock
$subtotal = 0;
$has_stock_error = false;
$ids_arr = array_keys($_SESSION['cart']);

if (empty($ids_arr)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

$ids = implode(',', array_map('intval', $ids_arr));
$products_result = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
$products_data = [];
$items_html = '';

while($row = $products_result->fetch_assoc()) {
    $requested_qty = $_SESSION['cart'][$row['id']];
    $stock = (int)$row['stock'];
    $qty = $requested_qty;
    $stock_warning = '';

    if ($requested_qty > $stock) {
        $qty = $stock;
        $_SESSION['cart'][$row['id']] = $qty; // Auto-adjust to available stock
        if ($qty <= 0) {
            unset($_SESSION['cart'][$row['id']]);
            continue; // Skip item if out of stock
        }
        $stock_warning = '<p class="text-[9px] font-black text-amber-500 uppercase tracking-tighter mt-0.5">⚠️ Only ' . $stock . ' left in stock</p>';
        $has_stock_error = true;
    }

    $row['quantity'] = $qty;
    $row['line_subtotal'] = $row['price'] * $qty;
    $products_data[$row['id']] = $row;
    $subtotal += $row['line_subtotal'];
    
    // Generate HTML for each item
    $items_html .= '
    <div class="flex gap-4 items-center group mb-4">
        <div class="w-16 h-16 bg-gray-50 rounded-2xl border border-gray-100 p-1 flex-shrink-0 relative overflow-hidden">
            <img src="' . get_url(ltrim($row['image'], './')) . '" class="w-full h-full object-contain rounded-xl group-hover:scale-110 transition">
        </div>
        <div class="flex-1">
            <h4 class="font-bold text-sm text-gray-800 line-clamp-1 font-[\'Fredoka\']">' . $row['name'] . '</h4>
            <div class="flex items-center gap-2 mt-1">
                <div class="flex items-center bg-gray-50 rounded-lg p-0.5 border border-gray-100">
                    <button onclick="updateCheckoutQty(' . $row['id'] . ', ' . ($qty - 1) . ')" class="w-5 h-5 flex items-center justify-center text-[8px] text-gray-400 hover:text-black transition">-</button>
                    <span class="text-[10px] font-black w-5 text-center">' . $qty . '</span>
                    <button onclick="updateCheckoutQty(' . $row['id'] . ', ' . ($qty + 1) . ')" class="w-5 h-5 flex items-center justify-center text-[8px] text-gray-400 hover:text-black transition">+</button>
                </div>
                <div class="flex flex-col">
                    <span class="text-[8px] font-black text-gray-400 uppercase tracking-tighter leading-none">@ ₹' . $row['price'] . '</span>
                    ' . $stock_warning . '
                </div>
            </div>
        </div>
        <div class="text-right">
            <span class="font-black text-gray-900 text-sm block">₹' . $row['line_subtotal'] . '</span>
            <button onclick="updateCheckoutQty(' . $row['id'] . ', 0)" class="text-[8px] font-bold text-red-300 hover:text-red-500 uppercase tracking-widest">Remove</button>
        </div>
    </div>';
}

// Coupon Logic
$coupon_discount = 0;
$coupon_code = '';
if (isset($_SESSION['coupon'])) {
    $coupon_val = validate_coupon($_SESSION['coupon']['code'], $subtotal);
    if ($coupon_val['valid']) {
        $coupon_discount = $coupon_val['discount'];
        $coupon_code = $_SESSION['coupon']['code'];
        $_SESSION['coupon']['discount'] = $coupon_discount;
    } else {
        unset($_SESSION['coupon']);
    }
}

// Affiliate Logic
$affiliate_discount = 0;
$affiliate_code = '';
if (isset($_SESSION['affiliate'])) {
    $aff_data = $_SESSION['affiliate'];
    $affiliate_code = $aff_data['code'];
    $affiliate_discount = floor($subtotal * ($aff_data['discount'] / 100));
}

// Shipping
sync_shipping_cost($subtotal);
$shipping = $_SESSION['shipping_cost'] ?? 0;

// Totals Calculation (matching checkout.php central logic)
$tax_perc = (float)get_setting('tax_percentage', 5);
$tax_rate = $tax_perc / 100;

$total_discount = $coupon_discount + $affiliate_discount;
$taxable_amount = max(0, $subtotal - $total_discount + $shipping);
$tax = ceil($taxable_amount * $tax_rate);
$total = ($subtotal - $total_discount) + $shipping + $tax;

echo json_encode([
    'success' => true,
    'data' => [
        'subtotal' => $subtotal,
        'coupon_discount' => $coupon_discount,
        'coupon_code' => $coupon_code,
        'affiliate_discount' => $affiliate_discount,
        'affiliate_code' => $affiliate_code,
        'shipping' => $shipping,
        'shipping_method_id' => $_SESSION['shipping_method_id'] ?? 0,
        'tax' => $tax,
        'tax_perc' => $tax_perc,
        'total' => $total,
        'items_html' => $items_html,
        'has_stock_error' => $has_stock_error,
        'stock_message' => $has_stock_error ? 'Some items have limited stock. We have adjusted your cart.' : ''
    ]
]);
