<?php
// Polyfill for PHP < 8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
    }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }
}
// Session & Cookie Configuration
if (session_status() === PHP_SESSION_NONE) {
    // Increase session security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

/**
 * Send Email Helper
 * Currently uses standard mail(), should be upgraded to PHPMailer for Production
 */
function send_email($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Driyum <noreply@' . $_SERVER['HTTP_HOST'] . '>' . "\r\n";
    
    // Log for debugging (especially useful in local/XAMPP)
    $log_entry = "[" . date('Y-m-d H:i:s') . "] To: $to | Subject: $subject\n$message\n" . str_repeat("-", 40) . "\n";
    file_put_contents(__DIR__ . '/../mail_log.txt', $log_entry, FILE_APPEND);
    
    // Attempt to send (suppressing error if on localhost)
    if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
        return @mail($to, $subject, $message, $headers);
    }
    return mail($to, $subject, $message, $headers);
}

// Flash Message Helpers
function set_flash_message($message, $type = 'success') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Global Settings Helpers
function get_setting($key, $default = null) {
    $res = fetch_one("SELECT value FROM settings WHERE `key` = ?", [$key]);
    return $res ? $res['value'] : $default;
}

function update_setting($key, $value) {
    execute_query("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?", [$key, $value, $value]);
}

// Remember Me Logic (Cookie Check)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    // In production, you would lookup this token in a 'user_tokens' table
    // For this implementation, we'll look for a base64 encoded string: user_id:random_hash
    $decoded = base64_decode($token);
    if ($decoded && strpos($decoded, ':') !== false) {
        list($uid, $hash) = explode(':', $decoded);
        $user = fetch_one("SELECT * FROM users WHERE id = ?", [(int)$uid]);
        // Validate hash (simple check for this demo)
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_admin'] = (int)($user['is_admin'] ?? 0);
        }
    }
}

// Security Functions
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . get_url('login.php?msg=login_required'));
        exit;
    }
}

// User Address Functions (Magic Checkout)
function get_user_default_address($user_id) {
    if (!$user_id) return null;
    return fetch_one("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC LIMIT 1", [$user_id]);
}

function save_user_address($user_id, $data) {
    if (!$user_id) return false;
    
    // Check if address already exists matching exactly based on core fields
    $existing = fetch_one("SELECT id FROM user_addresses WHERE user_id = ? AND address_line1 = ? AND city = ? AND pincode = ?", 
        [$user_id, $data['address_line1'], $data['city'], $data['pincode']]);
    
    if ($existing) {
        // Update existing address details and ensure it's marked as default/latest
        $sql = "UPDATE user_addresses SET name = ?, phone = ?, address_line2 = ?, state = ?, is_default = 1, updated_at = NOW() WHERE id = ?";
        // Clear other defaults first
        execute_query("UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND id != ?", [$user_id, $existing['id']]);
        return execute_query($sql, [$data['name'], $data['phone'], $data['address_line2'], $data['state'], $existing['id']]);
    } else {
        // Clear previous defaults
        execute_query("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?", [$user_id]);
        
        // Insert new default address
        $sql = "INSERT INTO user_addresses (user_id, name, phone, address_line1, address_line2, city, state, pincode, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
        return execute_query($sql, [
            $user_id, $data['name'], $data['phone'], $data['address_line1'], 
            $data['address_line2'], $data['city'], $data['state'], $data['pincode']
        ]);
    }
}

function require_admin() {
    if (!is_admin()) {
        header('Location: ' . get_url(''));
        exit;
    }
}

// Homepage Functions
function get_hero_slides() {
    $sql = "SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY sort_order ASC";
    return fetch_all($sql);
}

function get_active_announcement() {
    $sql = "SELECT * FROM announcements WHERE is_active = 1 AND (end_date IS NULL OR end_date > NOW()) ORDER BY created_at DESC LIMIT 1";
    return fetch_one($sql);
}

function get_sale_countdown() {
    $sql = "SELECT * FROM sale_countdowns WHERE is_active = 1 AND end_date > NOW() ORDER BY created_at DESC LIMIT 1";
    return fetch_one($sql);
}

function get_all_categories() {
    $sql = "SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1 WHERE c.is_active = 1 GROUP BY c.id ORDER BY c.sort_order ASC";
    return fetch_all($sql);
}

function get_featured_products($limit = 8) {
    $sql = "SELECT * FROM products WHERE is_active = 1 AND is_featured = 1 ORDER BY created_at DESC LIMIT ?";
    return fetch_all($sql, [$limit]);
}

function get_best_sellers($limit = 8) {
    $sql = "SELECT p.*, COUNT(oi.id) as sales_count FROM products p LEFT JOIN order_items oi ON oi.product_id = p.id WHERE p.is_active = 1 GROUP BY p.id ORDER BY sales_count DESC LIMIT ?";
    return fetch_all($sql, [$limit]);
}

function get_new_arrivals($limit = 8) {
    $sql = "SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT ?";
    return fetch_all($sql, [$limit]);
}

function get_testimonials() {
    $sql = "SELECT * FROM testimonials WHERE is_active = 1 ORDER BY created_at DESC LIMIT 10";
    return fetch_all($sql);
}

// Product Functions
function get_product_by_id($id) {
    if (empty($id)) return null;
    $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ? AND p.is_active = 1";
    return fetch_one($sql, [$id]);
}

function get_product_by_slug($slug) {
    if (empty($slug)) return null;
    $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.is_active = 1";
    return fetch_one($sql, [$slug]);
}

function get_category_by_slug($slug) {
    if (empty($slug)) return null;
    $sql = "SELECT * FROM categories WHERE slug = ? AND is_active = 1";
    return fetch_one($sql, [$slug]);
}

function get_product_images($product_id) {
    $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC";
    return fetch_all($sql, [$product_id]);
}

function get_related_products($product_id, $category_id, $limit = 4) {
    $sql = "SELECT * FROM products WHERE category_id = ? AND id != ? AND is_active = 1 ORDER BY RAND() LIMIT ?";
    return fetch_all($sql, [$category_id, $product_id, $limit]);
}

function get_products_by_filter($filters = [], $page = 1, $per_page = 12) {
    $where = ["is_active = 1"];
    $params = [];
    if (!empty($filters['category_id'])) {
        $where[] = "category_id = ?";
        $params[] = $filters['category_id'];
    }
    if (!empty($filters['search'])) {
        $where[] = "(name LIKE ? OR description LIKE ?)";
        $search_term = '%' . $filters['search'] . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    if (!empty($filters['min_price'])) {
        $where[] = "price >= ?";
        $params[] = $filters['min_price'];
    }
    if (!empty($filters['max_price'])) {
        $where[] = "price <= ?";
        $params[] = $filters['max_price'];
    }
    $where_clause = implode(' AND ', $where);
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM products WHERE $where_clause";
    $count_result = fetch_one($count_sql, $params);
    $total = $count_result['total'];
    // Get products
    $offset = ($page - 1) * $per_page;
    $order_by = "ORDER BY created_at DESC";
    if (!empty($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price_low':
                $order_by = "ORDER BY price ASC";
                break;
            case 'price_high':
                $order_by = "ORDER BY price DESC";
                break;
            case 'name':
                $order_by = "ORDER BY name ASC";
                break;
        }
    }
    $sql = "SELECT * FROM products WHERE $where_clause $order_by LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $products = fetch_all($sql, $params);
    return [
        'products' => $products,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ];
}

// Cart Functions
function get_cart_items() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $cart = [];
    $total = 0;
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $product = get_product_by_id($product_id);
        if ($product) {
            $product['quantity'] = $quantity;
            $product['subtotal'] = $product['price'] * $quantity;
            $cart[] = $product;
            $total += $product['subtotal'];
        }
    }
    return [
        'items' => $cart,
        'total' => $total,
        'count' => array_sum($_SESSION['cart'])
    ];
}

function add_to_cart($product_id, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    return true;
}

function update_cart_item($product_id, $quantity) {
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    return true;
}

function remove_from_cart($product_id) {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        return true;
    }
    return false;
}

function clear_cart() {
    $_SESSION['cart'] = [];
    return true;
}

function get_cart_count() {
    if (!isset($_SESSION['cart'])) {
        return 0;
    }
    return array_sum($_SESSION['cart']);
}

// Coupon Functions
function validate_coupon($code, $cart_total) {
    $coupon = fetch_one("SELECT * FROM coupons WHERE code = ?", [$code]);
    
    if (!$coupon) {
        return ['valid' => false, 'message' => 'Invalid coupon code'];
    }
    
    if (!$coupon['is_active']) {
        return ['valid' => false, 'message' => 'This coupon is currently inactive'];
    }
    
    if ($coupon['expiry_date'] && strtotime($coupon['expiry_date']) < time()) {
        return ['valid' => false, 'message' => 'This coupon has expired'];
    }
    
    if ($coupon['usage_limit'] > 0 && $coupon['usage_count'] >= $coupon['usage_limit']) {
        return ['valid' => false, 'message' => 'This coupon has reached its usage limit'];
    }
    
    if ($coupon['min_order_value'] > 0 && $cart_total < $coupon['min_order_value']) {
        return ['valid' => false, 'message' => 'Minimum order value of ₹' . $coupon['min_order_value'] . ' not met'];
    }
    
    $discount = 0;
    if ($coupon['type'] === 'percentage') {
        $discount = ($cart_total * $coupon['value']) / 100;
        if (isset($coupon['max_discount']) && $coupon['max_discount'] > 0 && $discount > $coupon['max_discount']) {
            $discount = $coupon['max_discount'];
        }
    } else {
        $discount = $coupon['value'];
    }
    
    // Ensure discount doesn't exceed total
    $discount = min($discount, $cart_total);
    
    return [
        'valid' => true,
        'coupon' => $coupon,
        'discount' => round($discount, 2)
    ];
}

function validate_coupon_by_id($id, $cart_total) {
    $coupon = fetch_one("SELECT * FROM coupons WHERE id = ?", [$id]);
    if (!$coupon) return ['valid' => false, 'message' => 'Coupon not found'];
    return validate_coupon($coupon['code'], $cart_total);
}

// Order Functions
function create_order($user_id, $items, $address, $payment_method, $coupon_id = null) {
    // Calculate totals
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['subtotal'];
    }
    $discount = 0;
    if ($coupon_id) {
        $coupon_result = validate_coupon_by_id($coupon_id, $subtotal);
        if ($coupon_result['valid']) {
            $discount = $coupon_result['discount'];
        }
    }
    $total = $subtotal - $discount;
    // Generate order number
    $order_number = 'DRY' . time() . rand(1000, 9999);
    // Insert order
    $sql = "INSERT INTO orders (order_number, user_id, subtotal, discount, total, payment_method, payment_status, order_status, shipping_address, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, NOW())";
    $address_json = json_encode($address);
    execute_query($sql, [$order_number, $user_id, $subtotal, $discount, $total, $payment_method, $address_json]);
    $order_id = get_last_insert_id();
    // Insert order items
    foreach ($items as $item) {
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)";
        execute_query($sql, [$order_id, $item['id'], $item['quantity'], $item['price'], $item['subtotal']]);
        // Update product stock
        $sql = "UPDATE products SET stock = stock - ? WHERE id = ?";
        execute_query($sql, [$item['quantity'], $item['id']]);
    }
    // Update coupon usage if ($coupon_id) {
    if ($coupon_id) {
        $sql = "UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?";
        execute_query($sql, [$coupon_id]);
    }
    return [
        'success' => true,
        'order_id' => $order_id,
        'order_number' => $order_number
    ];
}

/**
 * Send Order Confirmation Email
 */
function send_order_confirmation($order_id) {
    $order = fetch_one("SELECT * FROM orders WHERE id = ?", [$order_id]);
    if (!$order) return false;

    $addr = json_decode($order['shipping_address'], true);
    $to = $addr['email'] ?? '';
    if (!$to) return false;

    $items = fetch_all("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?", [$order_id]);
    
    $items_html = "";
    foreach ($items as $item) {
        $items_html .= "<tr>
            <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['name']} x {$item['quantity']}</td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>₹" . number_format($item['price'] * $item['quantity'], 2) . "</td>
        </tr>";
    }

    $subject = "Driyum Order Confirmed! #" . $order['order_number'];
    
    $email_content = "
        <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05);'>
            <div style='background-color: #000; padding: 40px; text-align: center;'>
                <h1 style='color: #19DC7E; margin: 0; font-size: 32px;'>YOU'RE AWESOME!</h1>
                <p style='color: #fff; opacity: 0.7; margin-top: 10px;'>Your snack drop is secured.</p>
            </div>
            <div style='padding: 40px; color: #333;'>
                <h2 style='margin-top: 0;'>Order #{$order['order_number']}</h2>
                <p>Hello " . ($addr['name'] ?? 'Snacker') . ",</p>
                <p>Thanks for choosing Driyum! We've received your order and our team is getting your snacks ready for dispatch.</p>
                
                <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                    <thead>
                        <tr style='background-color: #f9f9f9;'>
                            <th style='padding: 10px; text-align: left; font-size: 12px; text-transform: uppercase;'>Item</th>
                            <th style='padding: 10px; text-align: right; font-size: 12px; text-transform: uppercase;'>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$items_html}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style='padding: 10px; font-weight: bold;'>Subtotal</td>
                            <td style='padding: 10px; text-align: right;'>₹" . number_format($order['subtotal'], 2) . "</td>
                        </tr>
                        " . ($order['discount'] > 0 ? "<tr>
                            <td style='padding: 10px; color: #19DC7E;'>Discount</td>
                            <td style='padding: 10px; text-align: right; color: #19DC7E;'>- ₹" . number_format($order['discount'], 2) . "</td>
                        </tr>" : "") . "
                        <tr>
                            <td style='padding: 10px; font-weight: bold;'>Shipping</td>
                            <td style='padding: 10px; text-align: right;'>" . ($order['shipping_cost'] == 0 ? 'FREE' : '₹' . number_format($order['shipping_cost'], 2)) . "</td>
                        </tr>
                        <tr style='font-size: 18px; font-weight: bold;'>
                            <td style='padding: 10px; border-top: 2px solid #000;'>Total</td>
                            <td style='padding: 10px; text-align: right; border-top: 2px solid #000; color: #19DC7E;'>₹" . number_format($order['total'], 2) . "</td>
                        </tr>
                    </tfoot>
                </table>

                <div style='margin-top: 40px; padding: 20px; background-color: #f5f5f5; border-radius: 12px;'>
                    <h4 style='margin-top: 0;'>Shipping Address</h4>
                    <p style='margin-bottom: 0; font-size: 14px; line-height: 1.6;'>
                        " . ($addr['address'] ?? '') . "<br>
                        " . ($addr['city'] ?? '') . ", " . ($addr['zip'] ?? '') . "
                    </p>
                </div>

                <div style='text-align: center; margin-top: 40px;'>
                    <a href='http://{$_SERVER['HTTP_HOST']}/track.php?id={$order['order_number']}&contact={$to}' style='display: inline-block; padding: 15px 30px; background-color: #19DC7E; color: #000; text-decoration: none; border-radius: 50px; font-weight: bold; font-family: sans-serif;'>Track Your Order</a>
                </div>
            </div>
            <div style='padding: 20px; background-color: #eee; text-align: center; font-size: 12px; color: #999;'>
                &copy; " . date('Y') . " Driyum. Redefining Snacking.
            </div>
        </div>
    ";

    return send_email($to, $subject, $email_content);
}

/**
 * Send Order Status Update Email
 */
function send_order_status_email($order_id, $status) {
    $order = fetch_one("SELECT * FROM orders WHERE id = ?", [$order_id]);
    if (!$order) return false;

    $addr = json_decode($order['shipping_address'], true);
    $to = $addr['email'] ?? '';
    if (!$to) return false;

    $status_title = strtoupper(str_replace('_', ' ', $status));
    $subject = "Order #{$order['order_number']} Status Update: {$status_title}";
    
    $status_messages = [
        'confirmed' => "Good news! Your order has been confirmed and our team is currently packing your snacks with care.",
        'shipped' => "Exciting news! Your order is on its way. It has been dispatched and is currently in transit.",
        'delivered' => "Ding Dong! Your order has been delivered. We hope you enjoy your delicious Driyum snacks!",
        'cancelled' => "Your order has been cancelled. If you have any questions, please contact our support team."
    ];

    $message_body = $status_messages[strtolower($status)] ?? "Your order status has been updated to " . str_replace('_', ' ', $status) . ".";
    
    // Tracking info for shipped status
    $tracking_html = "";
    if (strtolower($status) === 'shipped' && !empty($order['tracking_number'])) {
        $tracking_html = "
            <div style='margin-top: 30px; padding: 25px; background: #EEF2FF; border-radius: 20px; border: 1px solid #C7D2FE;'>
                <p style='margin: 0 0 10px 0; font-size: 10px; font-weight: 900; color: #4338CA; text-transform: uppercase; letter-spacing: 0.1em;'>Tracking Details</p>
                <p style='margin: 0; font-size: 18px; font-weight: 900; color: #1E1B4B;'>{$order['tracking_number']}</p>
                " . (!empty($order['tracking_note']) ? "<p style='margin: 10px 0 0 0; font-size: 13px; color: #4338CA; font-style: italic;'>\"{$order['tracking_note']}\"</p>" : "") . "
                <p style='margin: 15px 0 0 0; font-size: 12px; color: #6366F1;'>Carrier: India Post</p>
            </div>
        ";
    }

    $email_content = "
        <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 30px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.05);'>
            <div style='background-color: #000; padding: 50px 40px; text-align: center;'>
                <div style='display: inline-block; padding: 10px 20px; background: #19DC7E; border-radius: 12px; color: #000; font-weight: 900; font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 20px;'>Order Update</div>
                <h1 style='color: #fff; margin: 0; font-size: 32px; font-weight: 900;'>{$status_title}</h1>
            </div>
            <div style='padding: 40px; color: #333; line-height: 1.6;'>
                <p stylealso='font-size: 18px; font-weight: bold; margin-bottom: 10px;'>Hi " . ($addr['name'] ?? 'Snacker') . ",</p>
                <p style='color: #666;'>{$message_body}</p>
                
                {$tracking_html}

                <div style='margin-top: 40px; text-align: center;'>
                    <a href='http://{$_SERVER['HTTP_HOST']}/track.php?id={$order['order_number']}&contact={$to}' style='display: inline-block; padding: 18px 35px; background-color: #19DC7E; color: #000; text-decoration: none; border-radius: 20px; font-weight: 900; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em; box-shadow: 0 10px 20px rgba(25, 220, 126, 0.2);'>Live Tracking</a>
                </div>

                <div style='margin-top: 50px; border-top: 1px solid #f0f0f0; padding-top: 30px;'>
                    <div style='display: flex; justify-content: space-between; align-items: center;'>
                        <div>
                            <p style='margin: 0; font-size: 10px; font-weight: 900; color: #999; text-transform: uppercase;'>Order Number</p>
                            <p style='margin: 0; font-weight: bold;'>#{$order['order_number']}</p>
                        </div>
                        <div style='text-align: right;'>
                            <p style='margin: 0; font-size: 10px; font-weight: 900; color: #999; text-transform: uppercase;'>Total Value</p>
                            <p style='margin: 0; font-weight: bold; color: #19DC7E;'>₹" . number_format($order['total'], 2) . "</p>
                        </div>
                    </div>
                </div>
            </div>
            <div style='padding: 30px; background-color: #f9f9f9; text-align: center; font-size: 11px; color: #aaa; text-transform: uppercase; font-weight: bold; letter-spacing: 0.1em;'>
                Driyum &bull; Premium Snacks &bull; Handcrafted with Love
            </div>
        </div>
    ";

    return send_email($to, $subject, $email_content);
}

function get_user_orders($user_id) {
    $sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
    return fetch_all($sql, [$user_id]);
}

function get_order_by_id($order_id, $user_id = null) {
    $sql = "SELECT * FROM orders WHERE id = ?";
    $params = [$order_id];
    if ($user_id) {
        $sql .= " AND user_id = ?";
        $params[] = $user_id;
    }
    return fetch_one($sql, $params);
}

function get_order_items($order_id) {
    $sql = "SELECT oi.*, p.name, p.image FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?";
    return fetch_all($sql, [$order_id]);
}

function track_order($order_number, $email_or_phone) {
    $sql = "SELECT o.*, u.email, u.phone FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.order_number = ? AND (u.email = ? OR u.phone = ?)";
    return fetch_one($sql, [$order_number, $email_or_phone, $email_or_phone]);
}

// User Functions
function register_user($name, $email, $phone, $password) {
    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = ?";
    $existing = fetch_one($sql, [$email]);
    if ($existing) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    // Insert user
    $sql = "INSERT INTO users (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())";
    execute_query($sql, [$name, $email, $phone, $hashed_password]);
    $user_id = get_last_insert_id();
    return [
        'success' => true,
        'user_id' => $user_id,
        'message' => 'Registration successful'
    ];
}

function login_user($email, $password) {
    $sql = "SELECT * FROM users WHERE email = ? AND is_active = 1";
    $user = fetch_one($sql, [$email]);
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['is_admin'] = $user['is_admin'];
    return [
        'success' => true,
        'message' => 'Login successful',
        'user' => $user
    ];
}

function logout_user() {
    session_destroy();
    return true;
}

function get_user_by_id($user_id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    return fetch_one($sql, [$user_id]);
}

function update_user_profile($user_id, $data) {
    $fields = [];
    $params = [];
    if (isset($data['name'])) {
        $fields[] = "name = ?";
        $params[] = $data['name'];
    }
    if (isset($data['phone'])) {
        $fields[] = "phone = ?";
        $params[] = $data['phone'];
    }
    if (!empty($fields)) {
        $params[] = $user_id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        execute_query($sql, $params);
        return true;
    }
    return false;
}

// Newsletter Functions
function subscribe_newsletter($email) {
    // Check if already subscribed
    $sql = "SELECT id FROM newsletter_subscribers WHERE email = ?";
    $existing = fetch_one($sql, [$email]);
    if ($existing) {
        return ['success' => false, 'message' => 'Already subscribed'];
    }
    // Insert subscriber
    $sql = "INSERT INTO newsletter_subscribers (email, subscribed_at) VALUES (?, NOW())";
    execute_query($sql, [$email]);
    return [
        'success' => true,
        'message' => 'Successfully subscribed!'
    ];
}

// Utility Functions
function format_currency($amount) {
    return '₹' . number_format($amount, 2);
}

function get_url($path = '') {
    // Clean leading ./ or /
    $path = preg_replace('/^(\.\/|\/)/', '', $path);
    $base = defined('BASE_URL') ? BASE_URL : '/';
    $url = $base . $path;
    
    // For SEO: remove .php from internal links if they are core pages
    if (strpos($path, '.php') !== false && strpos($path, 'admin/') === false && strpos($path, 'api/') === false) {
        $parts = explode('?', $path);
        if (str_ends_with($parts[0], '.php')) {
            $parts[0] = substr($parts[0], 0, -4);
            $path = implode('?', $parts);
            return BASE_URL . $path;
        }
    }
    
    return $url;
}

function get_canonical_url() {
    $path = ltrim($_SERVER['REQUEST_URI'], '/');
    // If in subdirectory local, remove the prefix
    if (strpos($path, 'newdry/') === 0) {
        $path = substr($path, 7);
    }
    return FULL_BASE_URL . $path;
}

function render_seo_tags($title = '', $description = '', $image = '', $type = 'website') {
    $store_name = get_setting('store_name', 'DRIYUM');
    $base_title = "DRIYUM - Premium Sun-Dried Healthy Snacks";
    $full_title = $title ? "$title | $store_name" : $base_title;
    
    $default_desc = get_setting('seo_description', 'Redefining the art of snacking with premium, indulgence. Naturally sweet, unapologetically bold.');
    $description = $description ?: $default_desc;
    
    // Clean description: strip tags and truncate
    $description = mb_strimwidth(strip_tags($description), 0, 160, "...");
    
    $image = $image ? get_url(ltrim($image, './')) : get_url('assets/images/og-image.jpg');
    // Ensure absolute image URL if possible
    if (!preg_match("~^(?:f|ht)tps?://~i", $image)) {
        $image = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . $image;
    }

    $url = get_canonical_url();

    echo "\n    <!-- SEO Basics -->\n";
    echo "    <title>" . htmlspecialchars($full_title) . "</title>\n";
    echo "    <meta name='description' content='" . htmlspecialchars($description) . "'>\n";
    echo "    <meta name='robots' content='index, follow'>\n";
    echo "    <link rel='canonical' href='$url'>\n";
    
    echo "\n    <!-- Open Graph / Facebook -->\n";
    echo "    <meta property='og:type' content='$type'>\n";
    echo "    <meta property='og:url' content='$url'>\n";
    echo "    <meta property='og:site_name' content='$store_name'>\n";
    echo "    <meta property='og:title' content='" . htmlspecialchars($full_title) . "'>\n";
    echo "    <meta property='og:description' content='" . htmlspecialchars($description) . "'>\n";
    echo "    <meta property='og:image' content='$image'>\n";

    echo "\n    <!-- Twitter -->\n";
    echo "    <meta name='twitter:card' content='summary_large_image'>\n";
    echo "    <meta name='twitter:url' content='$url'>\n";
    echo "    <meta name='twitter:title' content='" . htmlspecialchars($full_title) . "'>\n";
    echo "    <meta name='twitter:description' content='" . htmlspecialchars($description) . "'>\n";
    echo "    <meta name='twitter:image' content='$image'>\n";
    
    // Optional: Add Itemprop for Google Plus (legacy but harmless)
    echo "\n    <meta itemprop='name' content='" . htmlspecialchars($full_title) . "'>\n";
    echo "    <meta itemprop='description' content='" . htmlspecialchars($description) . "'>\n";
    echo "    <meta itemprop='image' content='$image'>\n";
}

function product_url($slug) {
    return get_url('product/' . $slug);
}

function category_url($slug) {
    return get_url('category/' . $slug);
}

function get_time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    if ($seconds <= 60) {
        return "Just now";
    } else if ($minutes <= 60) {
        return "$minutes min ago";
    } else if ($hours <= 24) {
        return "$hours hours ago";
    } else if ($days <= 7) {
        return "$days days ago";
    } else if ($weeks <= 4.3) {
        return "$weeks weeks ago";
    } else if ($months <= 12) {
        return "$months months ago";
    } else {
        return "$years years ago";
    }
}

function upload_image($file, $directory = 'uploads/products/') {
    $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/' . $directory;
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'success' => true,
            'filename' => $new_filename,
            'path' => '/' . $directory . $new_filename
        ];
    }
    return ['success' => false, 'message' => 'Upload failed'];
}

/**
 * Adjust the brightness of a hex color
 * $steps: -255 to 255 (Negative to darken, Positive to lighten)
 */
function adjust_brightness($hex, $steps) {
    // Remove # if present
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));

    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . 
                 str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . 
                 str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

// Reviews Functions
function get_product_reviews($product_id) {
    $sql = "SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC";
    return fetch_all($sql, [$product_id]);
}

function add_review($user_id, $product_id, $rating, $comment) {
    if (!$rating) $rating = 5;
    $sql = "INSERT INTO reviews (user_id, product_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())";
    return execute_query($sql, [$user_id, $product_id, $rating, $comment]);
}

function delete_reviews($ids) {
    if (empty($ids)) return false;
    if (!is_array($ids)) $ids = [$ids];
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    return execute_query("DELETE FROM reviews WHERE id IN ($placeholders)", $ids);
}

// Admin Notification Functions
function create_admin_notification($message, $type = 'info', $link = '') {
    $sql = "INSERT INTO admin_notifications (message, type, link) VALUES (?, ?, ?)";
    execute_query($sql, [$message, $type, $link]);
}

function get_unread_notifications() {
    return fetch_all("SELECT * FROM admin_notifications WHERE is_read = 0 ORDER BY created_at DESC");
}

function mark_notification_read($id) {
    return execute_query("UPDATE admin_notifications SET is_read = 1 WHERE id = ?", [$id]);
}

// Shipping Functions
function get_shipping_zone($pincode) {
    if (empty($pincode)) return null;
    $pincode = (int)trim($pincode);
    
    $zones = fetch_all("SELECT * FROM shipping_zones WHERE is_active = 1 ORDER BY id DESC");
    
    // Priority: We check all zones. If a pincode matches multiple, we could return the most specific.
    // But usually Local/State/National are mutually exclusive or hierarchical.
    // We'll return the first match. In Admin, they should be ordered correctly.
    foreach ($zones as $zone) {
        if (empty($zone['pincode_ranges'])) continue;
        
        $ranges = explode(',', $zone['pincode_ranges']);
        foreach ($ranges as $range) {
            $range = trim($range);
            if (strpos($range, '-') !== false) {
                list($min, $max) = explode('-', $range);
                if ($pincode >= (int)$min && $pincode <= (int)$max) {
                    return $zone;
                }
            } else {
                if ($pincode == (int)$range) {
                    return $zone;
                }
            }
        }
    }
    
    return null;
}

function get_shipping_methods_with_rates($pincode, $total_weight_kg) {
    $zone = get_shipping_zone($pincode);
    if (!$zone) return [];
    
    $methods = fetch_all("SELECT * FROM shipping_methods WHERE status = 1");
    $available_methods = [];
    
    foreach ($methods as $method) {
        $cost = 0;
        if ($method['charge_type'] == 'weight_based') {
            $rate = fetch_one("SELECT charge FROM shipping_rates 
                               WHERE method_id = ? AND zone_id = ? 
                               AND ? >= min_weight AND ? <= max_weight 
                               LIMIT 1", 
                               [$method['id'], $zone['id'], $total_weight_kg, $total_weight_kg]);
            if ($rate) {
                $cost = (float)$rate['charge'];
            } else {
                // Try to find the closest max weight if it exceeds all ranges
                $highest_rate = fetch_one("SELECT charge FROM shipping_rates 
                                           WHERE method_id = ? AND zone_id = ? 
                                           ORDER BY max_weight DESC LIMIT 1", 
                                           [$method['id'], $zone['id']]);
                if ($highest_rate && $total_weight_kg > 0) {
                    $cost = (float)$highest_rate['charge'];
                } else {
                    continue; 
                }
            }
        } else {
            $rate = fetch_one("SELECT charge FROM shipping_rates WHERE method_id = ? AND zone_id = ? LIMIT 1", [$method['id'], $zone['id']]);
            if ($rate) {
                $cost = (float)$rate['charge'];
            } else {
                continue;
            }
        }
        
        // Apply Free Shipping Threshold
        $free_threshold = (float)get_setting('free_shipping_threshold', 499);
        $cart_total = (float)get_cart_items()['total'];
        if ($cart_total >= $free_threshold) {
            $cost = 0;
        }
        
        $available_methods[] = [
            'id' => $method['id'],
            'carrier_name' => $method['carrier_name'],
            'display_name' => $method['display_name'],
            'min_days' => ($zone['min_days'] > 0 || $zone['max_days'] > 0) ? $zone['min_days'] : $method['min_days'],
            'max_days' => ($zone['min_days'] > 0 || $zone['max_days'] > 0) ? $zone['max_days'] : $method['max_days'],
            'cost' => $cost,
            'zone_id' => $zone['id'],
            'zone_name' => $zone['zone_name']
        ];
    }
    
    return $available_methods;
}
function sync_shipping_cost($subtotal = null) {
    if (!isset($_SESSION['shipping_method_id']) || !isset($_SESSION['shipping_zip'])) {
        return;
    }

    $method_id = (int)$_SESSION['shipping_method_id'];
    $pincode = $_SESSION['shipping_zip'];
    $weight = get_cart_weight();
    
    // Use get_shipping_methods_with_rates which already has free threshold logic
    $methods = get_shipping_methods_with_rates($pincode, $weight);
    foreach ($methods as $m) {
        if ($m['id'] == $method_id) {
            $_SESSION['shipping_cost'] = $m['cost'];
            return;
        }
    }
}

function get_cart_weight() {
    $cart = get_cart_items();
    $total_weight_kg = 0;
    foreach ($cart['items'] as $item) {
        $weight_str = strtolower($item['weight'] ?? '0');
        $val = (float)$weight_str;
        if (strpos($weight_str, 'kg') !== false) {
            $total_weight_kg += $val * $item['quantity'];
        } elseif (strpos($weight_str, 'g') !== false) {
            $total_weight_kg += ($val / 1000) * $item['quantity'];
        } else {
            $total_weight_kg += $val * $item['quantity'];
        }
    }
    return $total_weight_kg;
}

function get_shipping_estimate_text($min_days, $max_days) {
    if ($min_days == $max_days) {
        return "$min_days Business Days";
    }
    return "$min_days - $max_days Business Days";
}

// function for send email to customer after order confirmation
function send_order_confirmation_email($order_id) {
    $order = get_order_by_id($order_id);
    if (!$order) return false;
    
    $customer = get_user_by_id($order['user_id']);
    if (!$customer) return false;
    
    $subject = "Order Confirmation - " . $order['order_number'];
    $message = "
    <h2>Order Confirmation</h2>
    <p>Thank you for your order! Here are the details:</p>
    <p><strong>Order ID:</strong> " . $order['order_number'] . "</p>
    <p><strong>Order Date:</strong> " . $order['created_at'] . "</p>
    <p><strong>Total Amount:</strong> " . $order['total_amount'] . "</p>
    <p><strong>Shipping Address:</strong> " . $order['shipping_address'] . "</p>
    <p><strong>Billing Address:</strong> " . $order['billing_address'] . "</p>
    <p><strong>Payment Method:</strong> " . $order['payment_method'] . "</p>
    <p><strong>Order Status:</strong> " . $order['order_status'] . "</p>
    <p><strong>Shipping Method:</strong> " . $order['shipping_method'] . "</p>
    <p><strong>Shipping Cost:</strong> " . $order['shipping_cost'] . "</p>
    <p><strong>Tax:</strong> " . $order['tax'] . "</p>
    <p><strong>Discount:</strong> " . $order['discount'] . "</p>
    <p><strong>Order Items:</strong></p>
    <ul>
        " . implode('', array_map(function($item) {
            return "<li>" . $item['product_name'] . " - " . $item['quantity'] . " x " . $item['price'] . "</li>";
        }, $order['items'])) . "
    </ul>
    <p><strong>Total:</strong> " . $order['total_amount'] . "</p>
    <p>Thank you for your business!</p>
    <p>Sincerely,</p>
    <p>Driyum Team</p>
    ";
    
    return send_email($customer['email'], $subject, $message);
}

/**
 * Abandoned Cart Helpers
 */
function sync_cart_to_db() {
    if (!is_logged_in()) return;
    
    $user_id = $_SESSION['user_id'];
    $cart = $_SESSION['cart'] ?? [];
    
    if (empty($cart)) {
        clear_abandoned_cart($user_id);
        return;
    }
    
    $cart_data = json_encode($cart);
    
    // Check if entry exists that hasn't been reminded yet
    $existing = fetch_one("SELECT id FROM abandoned_carts WHERE user_id = ? AND is_reminded = 0", [$user_id]);
    
    if ($existing) {
        execute_query("UPDATE abandoned_carts SET cart_data = ?, last_updated = NOW() WHERE id = ?", [$cart_data, $existing['id']]);
    } else {
        execute_query("INSERT INTO abandoned_carts (user_id, cart_data) VALUES (?, ?)", [$user_id, $cart_data]);
    }
}

function clear_abandoned_cart($user_id) {
    execute_query("DELETE FROM abandoned_carts WHERE user_id = ?", [$user_id]);
}

/**
 * Pseudo-Cron Runner
 */
function run_crons() {
    $last_run = get_setting('last_cron_run', 0);
    $now = time();
    
    // Run every 30 minutes
    if ($now - $last_run > 1800) {
        update_setting('last_cron_run', $now);
        
        // Use a more reliable path for the cron script
        $cron_script = __DIR__ . '/../cron/abandoned_cart_reminder.php';
        if (file_exists($cron_script)) {
            // We'll run it and ignore output for now
            ob_start();
            include $cron_script;
            ob_end_clean();
        }
    }
}

/**
 * Pagination Helpers
 */
function get_pagination_data($query, $params = [], $per_page = 10) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    
    // Count total rows
    $count_query = "SELECT COUNT(*) as total FROM ($query) as t";
    $total_res = fetch_one($count_query, $params);
    $total_records = $total_res['total'] ?? 0;
    
    $total_pages = ceil($total_records / $per_page);
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
    
    $offset = ($page - 1) * $per_page;
    
    // Final query with limit
    $paginated_query = $query . " LIMIT $per_page OFFSET $offset";
    $records = fetch_all($paginated_query, $params);
    
    return [
        'records' => $records,
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_records' => $total_records,
        'per_page' => $per_page
    ];
}

function get_status_color($status) {
    $colors = [
        'pending'   => 'bg-yellow-100 text-yellow-700',
        'confirmed' => 'bg-indigo-100 text-indigo-700',
        'shipped'   => 'bg-blue-100 text-blue-700',
        'delivered' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-700',
        'completed' => 'bg-green-100 text-green-700'
    ];
    return $colors[strtolower($status)] ?? 'bg-gray-100 text-gray-600';
}

function render_pagination($total_pages, $current_page) {
    if ($total_pages <= 1) return '';
    
    $html = '<div class="flex items-center justify-center gap-2 mt-12 pb-12 anim-up">';
    
    // Current URL without page param
    $url = $_SERVER['PHP_SELF'];
    $params = $_GET;
    
    // Previous
    if ($current_page > 1) {
        $params['page'] = $current_page - 1;
        $prev_url = $url . '?' . http_build_query($params);
        $html .= "<a href='$prev_url' class='w-12 h-12 bg-white border border-gray-100 rounded-2xl flex items-center justify-center text-gray-400 hover:bg-black hover:text-[#19DC7E] transition-all shadow-sm'><i class='fas fa-chevron-left'></i></a>";
    }

    // Pages
    for ($i = 1; $i <= $total_pages; $i++) {
        $params['page'] = $i;
        $page_url = $url . '?' . http_build_query($params);
        $active_class = ($i == $current_page) ? 'bg-[#19DC7E] text-black border-transparent shadow-lg shadow-green-500/20 font-black' : 'bg-white text-gray-400 hover:bg-gray-50 border-gray-100';
        
        $html .= "<a href='$page_url' class='w-12 h-12 rounded-2xl border flex items-center justify-center text-sm transition-all $active_class'>$i</a>";
    }

    // Next
    if ($current_page < $total_pages) {
        $params['page'] = $current_page + 1;
        $next_url = $url . '?' . http_build_query($params);
        $html .= "<a href='$next_url' class='w-12 h-12 bg-white border border-gray-100 rounded-2xl flex items-center justify-center text-gray-400 hover:bg-black hover:text-[#19DC7E] transition-all shadow-sm'><i class='fas fa-chevron-right'></i></a>";
    }
    
    $html .= '</div>';
    return $html;
}
