<?php
date_default_timezone_set('Asia/Kolkata');
require_once __DIR__ . '/../config/database.php';

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
 * Format Price for Display
 * Removes unnecessary .00 for whole numbers
 */
function format_price($price, $currency = '₹') {
    if (floor($price) == $price) {
        return $currency . number_format($price, 0);
    }
    return $currency . number_format($price, 2);
}

/**
 * Send Email Helper
 * Currently uses standard mail(), should be upgraded to PHPMailer for Production
 */
function send_email($to, $subject, $message) {
    // Log for debugging
    $log_entry = "[" . date('Y-m-d H:i:s') . "] To: $to | Subject: $subject\n" . str_repeat("-", 40) . "\n";
    file_put_contents(__DIR__ . '/../mail_log.txt', $log_entry, FILE_APPEND);

    // Construct Signature
    $s_name = function_exists('get_setting') ? get_setting('store_name', 'Driyum') : 'Driyum';
    $s_url  = defined('FULL_BASE_URL') ? FULL_BASE_URL : 'https://driyum.com';
    $s_email = function_exists('get_setting') ? get_setting('support_email', 'help@driyum.com') : 'help@driyum.com';
    $current_year = date('Y');

    $signature = "
    <br><br>
    <div style='font-family: Arial, sans-serif; font-size: 13px; color: #555; border-top: 1px solid #eee; padding-top: 20px; margin-top: 30px;'>
        <p style='margin-bottom: 5px; font-weight: bold; color: #000;'>Best Regards,</p>
        <p style='margin-top: 0; margin-bottom: 20px;'>The {$s_name} Team</p>
        
        <table style='width: 100%; max-width: 600px; font-size: 11px; color: #999;'>
            <tr>
                <td style='padding-right: 20px;'>
                    <strong>{$s_name} Inc.</strong><br>
                    Premium Healthy Delicacies<br>
                    <a href='{$s_url}' style='color: #24B25D; text-decoration: none;'>{$s_url}</a>
                </td>
                <td style='text-align: right;'>
                    Need help? <a href='mailto:{$s_email}' style='color: #555; text-decoration: none;'>{$s_email}</a><br>
                    &copy; {$current_year} All rights reserved.
                </td>
            </tr>
        </table>
        
        <div style='margin-top: 15px; font-size: 10px; color: #ccc; text-align: center;'>
            You received this email because you are a valued part of the {$s_name} community.<br>
            If you believe this was a mistake, please ignore this email.
        </div>
    </div>";

    $message .= $signature;

    // Determine domain
    $domain = parse_url(FULL_BASE_URL, PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? 'driyum.com');
    
    // If on localhost and no SMTP config, fallback to mail() with improved headers
    if (($domain === 'localhost' || $domain === '127.0.0.1') && !defined('MAIL_HOST')) {
        $from = 'contact@driyum.com';
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: Driyum <$from>\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // The '-f' parameter is CRITICAL for passing SPF checks on some systems
        return @mail($to, $subject, $message, $headers, "-f$from");
    }

    // Use PHPMailer
    require_once __DIR__ . '/../vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = defined('MAIL_HOST') ? MAIL_HOST : 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = defined('MAIL_USER') ? MAIL_USER : 'contact@driyum.com';
        $mail->Password   = defined('MAIL_PASS') ? MAIL_PASS : ''; 
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = defined('MAIL_PORT') ? MAIL_PORT : 465;
        
        // Anti-Spam & Reliability Settings
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Sender = $mail->Username; // Sets the 'Return-Path' header for SPF alignment

        // Recipients
        $mail->setFrom($mail->Username, 'Driyum');
        $mail->addAddress($to);
        $mail->addReplyTo($mail->Username, 'Driyum Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error and fallback to mail() as last resort with robust headers
        error_log("PHPMailer Error for $to: " . $e->getMessage());
        file_put_contents(__DIR__ . '/../mail_log.txt', "[" . date('Y-m-d H:i:s') . "] PHPMailer Exception for $to: " . $e->getMessage() . "\n", FILE_APPEND);
        
        $from = 'contact@driyum.com';
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: Driyum <$from>\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "X-Mailer: DriyumSystem/1.0";
        
        return @mail($to, $subject, $message, $headers, "-f$from");
    }
}

/**
 * Queue Email for background processing
 * Adds email to a queue table to avoid timeouts during bulk broadcasts.
 */
function queue_email($to, $subject, $message) {
    // Check if table exists, if not create it (One-time check per session)
    static $table_checked = false;
    if (!$table_checked) {
        $sql = "CREATE TABLE IF NOT EXISTS email_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            last_attempt TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        execute_query($sql);
        $table_checked = true;
    }

    return execute_query("INSERT INTO email_queue (recipient, subject, message) VALUES (?, ?, ?)", [$to, $subject, $message]);
}

/**
 * Process the Email Queue
 * This should be triggered via Cron or individual calls.
 */
function process_email_queue($limit = 10) {
    $queued = fetch_all("SELECT * FROM email_queue WHERE status = 'pending' OR (status = 'failed' AND attempts < 3) LIMIT ?", [$limit]);
    
    foreach ($queued as $item) {
        $status = send_email($item['recipient'], $item['subject'], $item['message']) ? 'sent' : 'failed';
        $attempts = $item['attempts'] + 1;
        
        execute_query("UPDATE email_queue SET status = ?, attempts = ?, last_attempt = CURRENT_TIMESTAMP WHERE id = ?", [$status, $attempts, $item['id']]);
    }
    
    return count($queued);
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

function get_setting($key, $default = null) {
    $res = fetch_one("SELECT value FROM settings WHERE `key` = ?", [$key]);
    return $res ? $res['value'] : $default;
}

function update_setting($key, $value) {
    execute_query("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?", [$key, $value, $value]);
}

/**
 * Live User Tracking
 */
function get_live_user_count($minutes = 5) {
    $res = fetch_one("SELECT COUNT(DISTINCT session_id) as c FROM live_users WHERE last_activity > (NOW() - INTERVAL ? MINUTE)", [$minutes]);
    return $res['c'] ?? 0;
}

function get_live_users_list($minutes = 5) {
    return fetch_all("SELECT * FROM live_users WHERE last_activity > (NOW() - INTERVAL ? MINUTE) ORDER BY last_activity DESC", [$minutes]);
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
function get_hero_slides($include_inactive = false) {
    $where = $include_inactive ? "" : "WHERE s.is_active = 1";
    $sql = "SELECT s.*, p.slug as product_slug, p.name as product_name 
            FROM hero_slides s 
            LEFT JOIN products p ON s.product_id = p.id 
            $where 
            ORDER BY s.sort_order ASC, s.id ASC";
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
    $sql = "SELECT * FROM products WHERE is_active = 1 AND is_featured = 1 ORDER BY CASE WHEN stock > 0 THEN 0 ELSE 1 END ASC, featured_sort_order ASC, id DESC LIMIT ?";
    $products = fetch_all($sql, [$limit]);
    if (count($products) < $limit) {
        $needed = $limit - count($products);
        $ids = !empty($products) ? implode(',', array_column($products, 'id')) : '0';
        $fillers = fetch_all("SELECT * FROM products WHERE is_active = 1 AND id NOT IN ($ids) ORDER BY CASE WHEN stock > 0 THEN 0 ELSE 1 END ASC, featured_sort_order ASC, id DESC LIMIT ?", [$needed]);
        $products = array_merge($products, $fillers);
    }
    // Always ensure in-stock items appear before out-of-stock items, while respecting custom order
    usort($products, function($a, $b) {
        $a_stock = ($a['stock'] > 0) ? 0 : 1;
        $b_stock = ($b['stock'] > 0) ? 0 : 1;
        if ($a_stock !== $b_stock) {
            return $a_stock - $b_stock;
        }
        $a_sort = isset($a['featured_sort_order']) ? (int)$a['featured_sort_order'] : (int)$a['id'];
        $b_sort = isset($b['featured_sort_order']) ? (int)$b['featured_sort_order'] : (int)$b['id'];
        if ($a_sort !== $b_sort) {
            return $a_sort - $b_sort;
        }
        return $b['id'] - $a['id'];
    });
    return $products;
}

function get_best_sellers($limit = 8) {
    $sql = "SELECT p.*, COUNT(oi.id) as sales_count FROM products p LEFT JOIN order_items oi ON oi.product_id = p.id WHERE p.is_active = 1 GROUP BY p.id ORDER BY CASE WHEN p.stock > 0 THEN 0 ELSE 1 END ASC, sales_count DESC LIMIT ?";
    return fetch_all($sql, [$limit]);
}

function get_new_arrivals($limit = 8) {
    $sql = "SELECT * FROM products WHERE is_active = 1 ORDER BY CASE WHEN stock > 0 THEN 0 ELSE 1 END ASC, created_at DESC LIMIT ?";
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
    $sql = "SELECT * FROM products WHERE category_id = ? AND id != ? AND is_active = 1 ORDER BY CASE WHEN stock > 0 THEN 0 ELSE 1 END ASC, RAND() LIMIT ?";
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
    $order_by = "ORDER BY CASE WHEN stock > 0 THEN 0 ELSE 1 END ASC, created_at DESC";
    if (!empty($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price_low':
                $order_by = "ORDER BY CASE WHEN stock > 0 THEN 0 ELSE 1 END ASC, price ASC";
                break;
            case 'price_high':
                $order_by = "ORDER BY CASE WHEN stock > 0 THEN 0 ELSE 1 END ASC, price DESC";
                break;
            case 'name':
                $order_by = "ORDER BY CASE WHEN stock > 0 THEN 0 ELSE 1 END ASC, name ASC";
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
                <h1 style='color: #24B25D; margin: 0; font-size: 32px;'>YOU'RE AWESOME!</h1>
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
                            <td style='padding: 10px; color: #24B25D;'>Discount</td>
                            <td style='padding: 10px; text-align: right; color: #24B25D;'>- ₹" . number_format($order['discount'], 2) . "</td>
                        </tr>" : "") . "
                        <tr>
                            <td style='padding: 10px; font-weight: bold;'>Shipping</td>
                            <td style='padding: 10px; text-align: right;'>" . ($order['shipping_cost'] == 0 ? 'FREE' : '₹' . number_format($order['shipping_cost'], 2)) . "</td>
                        </tr>
                        <tr style='font-size: 18px; font-weight: bold;'>
                            <td style='padding: 10px; border-top: 2px solid #000;'>Total</td>
                            <td style='padding: 10px; text-align: right; border-top: 2px solid #000; color: #24B25D;'>₹" . number_format($order['total'], 2) . "</td>
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
                    <a href='" . FULL_BASE_URL . "track.php?id={$order['order_number']}&contact={$to}' style='display: inline-block; padding: 15px 30px; background-color: #24B25D; color: #000; text-decoration: none; border-radius: 50px; font-weight: bold; font-family: sans-serif;'>Track Your Order</a>
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
function send_order_status_email($order_id, $status, $queue = false) {
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
        'delivered' => "Ding Dong! Your order has been delivered. We hope you enjoy your delicious Driyum snacks! Your order invoice details are provided below.",
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

    // Invoice details for delivered status
    $invoice_html = "";
    if (strtolower($status) === 'delivered') {
        $items = fetch_all("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?", [$order_id]);
        $items_list = "";
        foreach ($items as $item) {
            $items_list .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; font-size: 14px;'>{$item['name']} x {$item['quantity']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; font-size: 14px; text-align: right;'>₹" . number_format($item['price'] * $item['quantity'], 2) . "</td>
                </tr>";
        }

        $invoice_html = "
            <div style='margin-top: 30px; padding: 25px; background: #F9FAFB; border-radius: 20px; border: 1px solid #E5E7EB;'>
                <p style='margin: 0 0 15px 0; font-size: 10px; font-weight: 900; color: #374151; text-transform: uppercase; letter-spacing: 0.1em;'>Order Invoice Summary</p>
                <table style='width: 100%; border-collapse: collapse;'>
                    {$items_list}
                    <tr>
                        <td style='padding: 15px 10px 5px 10px; font-weight: bold; font-size: 14px;'>Subtotal</td>
                        <td style='padding: 15px 10px 5px 10px; font-weight: bold; font-size: 14px; text-align: right;'>₹" . number_format($order['subtotal'], 2) . "</td>
                    </tr>
                    " . ($order['discount'] > 0 ? "
                    <tr>
                        <td style='padding: 5px 10px; font-size: 14px; color: #10B981;'>Discount</td>
                        <td style='padding: 5px 10px; font-size: 14px; color: #10B981; text-align: right;'>- ₹" . number_format($order['discount'], 2) . "</td>
                    </tr>" : "") . "
                    <tr>
                        <td style='padding: 5px 10px; font-size: 14px;'>Shipping</td>
                        <td style='padding: 5px 10px; font-size: 14px; text-align: right;'>₹" . number_format($order['shipping_cost'], 2) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border-top: 2px solid #374151; font-weight: 900; font-size: 16px;'>Grand Total</td>
                        <td style='padding: 10px; border-top: 2px solid #374151; font-weight: 900; font-size: 16px; text-align: right; color: #24B25D;'>₹" . number_format($order['total'], 2) . "</td>
                    </tr>
                </table>
                <div style='margin-top: 20px; text-align: center;'>
                    <a href='" . FULL_BASE_URL . "invoice.php?id={$order['order_number']}&contact={$to}' style='font-size: 11px; font-weight: 900; color: #24B25D; text-decoration: none; text-transform: uppercase;'>View Full Professional Invoice &rarr;</a>
                </div>
            </div>
        ";
    }

    $email_content = "
        <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 30px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.05);'>
            <div style='background-color: #000; padding: 50px 40px; text-align: center;'>
                <div style='display: inline-block; padding: 10px 20px; background: #24B25D; border-radius: 12px; color: #000; font-weight: 900; font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 20px;'>Order Update</div>
                <h1 style='color: #fff; margin: 0; font-size: 32px; font-weight: 900;'>{$status_title}</h1>
            </div>
            <div style='padding: 40px; color: #333; line-height: 1.6;'>
                <p style='font-size: 18px; font-weight: bold; margin-bottom: 10px;'>Hi " . ($addr['name'] ?? 'Snacker') . ",</p>
                <p style='color: #666;'>{$message_body}</p>
                
                {$tracking_html}
                {$invoice_html}
" . (strtolower($status) !== 'delivered' ? "
                <div style='margin-top: 40px; text-align: center;'>
                    <a href='" . FULL_BASE_URL . "track.php?id={$order['order_number']}&contact={$to}' style='display: inline-block; padding: 18px 35px; background-color: #24B25D; color: #000; text-decoration: none; border-radius: 20px; font-weight: 900; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em; box-shadow: 0 10px 20px rgba(25, 220, 126, 0.2);'>Live Tracking</a>
                </div>" : "") . "

                <div style='margin-top: 50px; border-top: 1px solid #f0f0f0; padding-top: 30px;'>
                    <div style='display: flex; justify-content: space-between; align-items: center;'>
                        <div>
                            <p style='margin: 0; font-size: 10px; font-weight: 900; color: #999; text-transform: uppercase;'>Order Number</p>
                            <p style='margin: 0; font-weight: bold;'>#{$order['order_number']}</p>
                        </div>
                        <div style='text-align: right;'>
                            <p style='margin: 0; font-size: 10px; font-weight: 900; color: #999; text-transform: uppercase;'>Total Value</p>
                            <p style='margin: 0; font-weight: bold; color: #24B25D;'>₹" . number_format($order['total'], 2) . "</p>
                        </div>
                    </div>
                </div>
            </div>
            <div style='padding: 30px; background-color: #f9f9f9; text-align: center; font-size: 11px; color: #aaa; text-transform: uppercase; font-weight: bold; letter-spacing: 0.1em;'>
                Driyum &bull; Premium Snacks &bull; Handcrafted with Love
            </div>
        </div>
    ";

    if ($queue) {
        return queue_email($to, $subject, $email_content);
    }
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
    $base_title = "DRIYUM - Premium Healthy Snacks";
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
    $where = "WHERE r.product_id = ?";
    $sql = "SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id $where ORDER BY r.created_at DESC";
    return fetch_all($sql, [$product_id]);
}

function add_review($user_id, $product_id, $rating, $comment) {
    if (!$rating) $rating = 5;
    $sql = "INSERT INTO reviews (user_id, product_id, rating, comment, is_approved, created_at) VALUES (?, ?, ?, ?, 1, NOW())";
    
    // Notify admin about new review
    create_admin_notification("New review received for product #$product_id", 'review', 'reviews.php');
    
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
 * Send Abandoned Cart Reminder Email
 */
/**
 * Build Content for Abandoned Cart Email
 */
function get_abandoned_cart_email_content($cart_id) {
    $cart = fetch_one("SELECT ac.*, u.email, u.name 
                      FROM abandoned_carts ac
                      LEFT JOIN users u ON ac.user_id = u.id
                      WHERE ac.id = ?", [$cart_id]);
    
    if (!$cart || empty($cart['email'])) return null;

    $cart_items_data = json_decode($cart['cart_data'], true);
    if (empty($cart_items_data)) return null;

    $subject = "Your Driyum snacks are waiting!";
    $items_html = "";
    $subtotal = 0;
    
    // Batch fetch products for performance
    $product_ids = array_keys($cart_items_data);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $products = fetch_all("SELECT id, name, price FROM products WHERE id IN ($placeholders)", $product_ids);
    $products_indexed = [];
    foreach($products as $p) $products_indexed[$p['id']] = $p;

    foreach($cart_items_data as $pid => $qty) {
        $p = $products_indexed[$pid] ?? null;
        if($p) {
            $total = $p['price'] * $qty;
            $subtotal += $total;
            $items_html .= "<tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$p['name']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>x $qty</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>₹" . number_format($total, 2) . "</td>
            </tr>";
        }
    }

    $checkout_url = FULL_BASE_URL . "checkout";
    $user_name = strtoupper($cart['name'] ?? 'FRIEND');

    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 25px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05);'>
        <div style='background: #000; padding: 40px; text-align: center;'>
            <h1 style='color: #24B25D; margin: 0; font-size: 28px;'>HEY $user_name!</h1>
            <p style='color: #fff; opacity: 0.7; margin: 10px 0 0 0;'>Did you forget something delicious?</p>
        </div>
        
        <div style='padding: 40px; color: #333;'>
            <p>We noticed you left some premium snacks in your cart. They are still here, perfectly preserved and waiting for you to hit that checkout button!</p>
            
            <table style='width: 100%; border-collapse: collapse; margin: 30px 0;'>
                <thead>
                    <tr style='background: #f9f9f9;'>
                        <th style='padding: 12px 10px; text-align: left; font-size: 11px; text-transform: uppercase; color: #999;'>Item</th>
                        <th style='padding: 12px 10px; text-align: left; font-size: 11px; text-transform: uppercase; color: #999;'>Qty</th>
                        <th style='padding: 12px 10px; text-align: right; font-size: 11px; text-transform: uppercase; color: #999;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    $items_html
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan='2' style='padding: 20px 10px; font-weight: bold; text-align: right;'>Subtotal:</td>
                        <td style='padding: 20px 10px; font-weight: bold; text-align: right; color: #24B25D; font-size: 18px;'>₹" . number_format($subtotal, 2) . "</td>
                    </tr>
                </tfoot>
            </table>

            <div style='text-align: center; margin-top: 20px;'>
                <a href='$checkout_url' style='background: #24B25D; color: #000; padding: 18px 35px; text-decoration: none; border-radius: 50px; font-weight: 900; display: inline-block; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;'>Secure My Snacks</a>
            </div>
            
            <p style='margin-top: 40px; font-size: 12px; color: #999; text-align: center; line-height: 1.6;'>
                Need help? Just reply to this email .<br>
                &copy; " . date('Y') . " DRIYUM. All Rights Reserved.
            </p>
        </div>
    </div>";

    return ['to' => $cart['email'], 'subject' => $subject, 'message' => $message];
}

/**
 * Send Abandoned Cart Reminder Email
 */
function send_abandoned_cart_reminder($cart_id) {
    $content = get_abandoned_cart_email_content($cart_id);
    if (!$content) return false;
    return send_email($content['to'], $content['subject'], $content['message']);
}

/**
 * Queue Abandoned Cart Reminder Email
 */
function queue_abandoned_cart_reminder($cart_id) {
    $content = get_abandoned_cart_email_content($cart_id);
    if (!$content) return false;
    return queue_email($content['to'], $content['subject'], $content['message']);
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
    
    // Run every 5 minutes
    if ($now - $last_run > 300) {
        update_setting('last_cron_run', $now);
        
        // 1. Process Pending Emails from Queue
        process_email_queue(10);
        
        // 2. Automate finding abandoned carts to remind
        $cron_script = __DIR__ . '/../cron/abandoned_cart_reminder.php';
        if (file_exists($cron_script)) {
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
    // Allow user to choose how many elements to see per page via $_GET['per_page']
    if (isset($_GET['per_page'])) {
        $req_per_page = (int)$_GET['per_page'];
        if ($req_per_page >= 5 && $req_per_page <= 500) {
            $per_page = $req_per_page;
        }
    }

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

/**
 * Dynamic Hero Wavy Divider Path Generator
 * Calculates SVG paths based on an intensity factor (0% - 100%)
 */
function get_hero_wave_data($intensity = null) {
    if ($intensity === null) {
        $intensity = function_exists('get_setting') ? (int)get_setting('hero_wave_intensity', 75) : 75;
    }
    $intensity = max(0, min(100, (int)$intensity));
    $f = $intensity / 100.0;
    $B = 145; // Baseline level

    // Key points: [cmd, x, dev] where dev is deviation from baseline B=145
    $points = [
        ['M', 0, 5],
        ['C', 80, 65, 130, 90, 220, 85],
        ['C', 320, 80, 370, -80, 480, -85],
        ['C', 590, -90, 640, 80, 750, 75],
        ['C', 860, 70, 910, -90, 1020, -95],
        ['C', 1130, -100, 1180, 70, 1280, 65],
        ['C', 1360, 60, 1400, 0, 1440, -25],
    ];

    $build_path = function($cream = false) use ($points, $B, $f) {
        $offset = $cream ? -20 : 0;
        $d = "";
        foreach ($points as $p) {
            $cmd = $p[0];
            if ($cmd === 'M') {
                $y = round($B + ($p[2] + $offset) * $f);
                $d .= "M{$p[1]},{$y} ";
            } elseif ($cmd === 'C') {
                $y1 = round($B + ($p[2] + $offset) * $f);
                $y2 = round($B + ($p[4] + $offset) * $f);
                $y3 = round($B + ($p[6] + $offset) * $f);
                $d .= "C{$p[1]},{$y1} {$p[3]},{$y2} {$p[5]},{$y3} ";
            }
        }
        $d .= "L1440,320 L0,320 Z";
        return trim($d);
    };

    return [
        'intensity' => $intensity,
        'white_path' => $build_path(false),
        'cream_path' => $build_path(true)
    ];
}

function render_pagination($total_pages, $current_page = 1, $per_page = null, $total_records = null) {
    // Support passing entire $pagination array as first argument
    if (is_array($total_pages)) {
        $p_data = $total_pages;
        $total_pages = $p_data['total_pages'] ?? 1;
        $current_page = $p_data['current_page'] ?? 1;
        $per_page = $p_data['per_page'] ?? null;
        $total_records = $p_data['total_records'] ?? null;
    }

    $total_pages = (int)$total_pages;
    $current_page = (int)$current_page;
    if ($total_pages < 1) $total_pages = 1;
    if ($current_page < 1) $current_page = 1;
    if ($current_page > $total_pages) $current_page = $total_pages;

    // Detect per_page if not explicitly passed
    if ($per_page === null) {
        if (isset($_GET['per_page']) && (int)$_GET['per_page'] > 0) {
            $per_page = (int)$_GET['per_page'];
        } elseif (isset($GLOBALS['pagination']['per_page'])) {
            $per_page = (int)$GLOBALS['pagination']['per_page'];
        } elseif (isset($GLOBALS['pagination_rates']['per_page'])) {
            $per_page = (int)$GLOBALS['pagination_rates']['per_page'];
        } else {
            $per_page = 10;
        }
    }
    $per_page = (int)$per_page;

    // Detect total_records if not explicitly passed
    if ($total_records === null) {
        if (isset($GLOBALS['pagination']['total_records'])) {
            $total_records = (int)$GLOBALS['pagination']['total_records'];
        } elseif (isset($GLOBALS['pagination_rates']['total_records'])) {
            $total_records = (int)$GLOBALS['pagination_rates']['total_records'];
        }
    }

    $is_admin = (strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false);
    // On non-admin pages, don't show pagination if only 1 page
    if (!$is_admin && $total_pages <= 1 && !isset($_GET['per_page'])) {
        return '';
    }
    // If no records at all, don't show pagination
    if ($total_records !== null && $total_records <= 0) {
        return '';
    }

    // Standard preset choices for elements per page
    $options = [10, 25, 50, 100];
    if (!in_array($per_page, $options) && $per_page > 0) {
        $options[] = $per_page;
        sort($options);
    }

    $url = htmlspecialchars($_SERVER['PHP_SELF'] ?? '');
    $params = $_GET;

    $get_page_url = function($p) use ($url, $params, $per_page) {
        $p_params = $params;
        $p_params['page'] = $p;
        $p_params['per_page'] = $per_page;
        return $url . '?' . http_build_query($p_params);
    };

    // Calculate sliding window for desktop (always max 7 items to prevent awkward layout shifts)
    $pages = [];
    if ($total_pages <= 7) {
        $pages = range(1, $total_pages);
    } elseif ($current_page <= 4) {
        $pages = [1, 2, 3, 4, 5, '...', $total_pages];
    } elseif ($current_page >= $total_pages - 3) {
        $pages = [1, '...', $total_pages - 4, $total_pages - 3, $total_pages - 2, $total_pages - 1, $total_pages];
    } else {
        $pages = [1, '...', $current_page - 1, $current_page, $current_page + 1, '...', $total_pages];
    }

    $prev_url = $get_page_url(max(1, $current_page - 1));
    $next_url = $get_page_url(min($total_pages, $current_page + 1));
    $first_url = $get_page_url(1);
    $last_url = $get_page_url($total_pages);

    $is_first = ($current_page <= 1);
    $is_last = ($current_page >= $total_pages);

    // Reusable per-page dropdown markup
    $render_per_page_select = function() use ($options, $per_page) {
        $out = '<div class="flex items-center gap-2 text-xs font-semibold text-gray-500 bg-white border border-gray-200/80 px-3 py-2 rounded-xl shadow-xs">';
        $out .= '<span class="text-gray-400 font-medium">Show</span>';
        $out .= '<div class="relative inline-flex items-center">';
        $out .= '<select onchange="window.handlePaginationPerPage(this.value)" aria-label="Items per page" class="appearance-none bg-gray-50 hover:bg-gray-100 text-black font-black text-xs pl-2.5 pr-6 py-1 rounded-lg border border-gray-200 focus:outline-none focus:border-[#24B25D] focus:ring-1 focus:ring-[#24B25D] cursor-pointer transition-all">';
        foreach ($options as $opt) {
            $sel = ($opt == $per_page) ? ' selected' : '';
            $out .= "<option value=\"$opt\"$sel>$opt</option>";
        }
        $out .= '</select>';
        $out .= '<i class="fas fa-chevron-down text-[9px] text-gray-400 pointer-events-none absolute right-2"></i>';
        $out .= '</div>';
        $out .= '<span class="text-gray-400 font-medium">per page</span>';
        $out .= '</div>';
        return $out;
    };

    $html = '<nav aria-label="Pagination Navigation" class="w-full flex flex-col items-center justify-center mt-10 mb-8 sm:mt-12 sm:pb-12 anim-up select-none">';

    // Global helper script to update per_page and reset to page 1
    $html .= '<script>
    if (!window.handlePaginationPerPage) {
        window.handlePaginationPerPage = function(perPage) {
            try {
                var url = new URL(window.location.href);
                url.searchParams.set("per_page", perPage);
                url.searchParams.set("page", "1");
                window.location.href = url.toString();
            } catch(e) {
                var sep = window.location.href.indexOf("?") !== -1 ? "&" : "?";
                window.location.href = window.location.pathname + "?page=1&per_page=" + perPage;
            }
        };
    }
    </script>';

    // ================= MOBILE VIEW (< 640px) =================
    // Compact, touch-friendly, fits comfortably on 320px+ viewports with zero horizontal overflow
    $html .= '<div class="flex sm:hidden flex-col items-center w-full max-w-sm px-3 gap-3">';
    
    // Top row: mobile pagination controls
    $html .= '<div class="flex items-center justify-between w-full gap-1.5">';
    
    // Mobile: First Page jump
    if ($is_first) {
        $html .= '<span class="w-9 h-9 rounded-xl border border-gray-100 bg-gray-50 text-gray-300 flex items-center justify-center text-xs opacity-50 cursor-not-allowed" aria-disabled="true"><i class="fas fa-angles-left text-[11px]"></i></span>';
    } else {
        $html .= '<a href="' . $first_url . '" class="w-9 h-9 rounded-xl border border-gray-200/80 bg-white text-gray-600 hover:text-black hover:border-gray-300 flex items-center justify-center text-xs shadow-xs active:scale-95 transition-all" title="First Page" aria-label="First Page"><i class="fas fa-angles-left text-[11px]"></i></a>';
    }

    // Mobile: Previous button
    if ($is_first) {
        $html .= '<span class="px-3 h-9 rounded-xl border border-gray-100 bg-gray-50 text-gray-300 flex items-center gap-1.5 text-xs font-bold opacity-50 cursor-not-allowed" aria-disabled="true"><i class="fas fa-chevron-left text-[10px]"></i> Prev</span>';
    } else {
        $html .= '<a href="' . $prev_url . '" class="px-3 h-9 rounded-xl border border-gray-200/80 bg-white text-gray-600 hover:text-black hover:border-gray-300 flex items-center gap-1.5 text-xs font-bold shadow-xs active:scale-95 transition-all" aria-label="Previous Page"><i class="fas fa-chevron-left text-[10px]"></i> Prev</a>';
    }

    // Mobile: Current Page Badge
    $html .= '<div class="h-9 px-3 bg-white border border-gray-200/80 rounded-xl shadow-xs text-xs font-semibold text-gray-500 flex items-center justify-center gap-1">';
    $html .= '<span class="text-black font-black text-sm">' . $current_page . '</span>';
    $html .= '<span class="text-gray-300 font-normal">/</span>';
    $html .= '<span class="text-gray-600 font-bold">' . $total_pages . '</span>';
    $html .= '</div>';

    // Mobile: Next button
    if ($is_last) {
        $html .= '<span class="px-3 h-9 rounded-xl border border-gray-100 bg-gray-50 text-gray-300 flex items-center gap-1.5 text-xs font-bold opacity-50 cursor-not-allowed" aria-disabled="true">Next <i class="fas fa-chevron-right text-[10px]"></i></span>';
    } else {
        $html .= '<a href="' . $next_url . '" class="px-3 h-9 rounded-xl border border-gray-200/80 bg-white text-gray-600 hover:text-black hover:border-gray-300 flex items-center gap-1.5 text-xs font-bold shadow-xs active:scale-95 transition-all" aria-label="Next Page">Next <i class="fas fa-chevron-right text-[10px]"></i></a>';
    }

    // Mobile: Last Page jump
    if ($is_last) {
        $html .= '<span class="w-9 h-9 rounded-xl border border-gray-100 bg-gray-50 text-gray-300 flex items-center justify-center text-xs opacity-50 cursor-not-allowed" aria-disabled="true"><i class="fas fa-angles-right text-[11px]"></i></span>';
    } else {
        $html .= '<a href="' . $last_url . '" class="w-9 h-9 rounded-xl border border-gray-200/80 bg-white text-gray-600 hover:text-black hover:border-gray-300 flex items-center justify-center text-xs shadow-xs active:scale-95 transition-all" title="Last Page" aria-label="Last Page"><i class="fas fa-angles-right text-[11px]"></i></a>';
    }

    $html .= '</div>';

    // Bottom row: Mobile Per-page selector & item count
    $html .= '<div class="flex items-center justify-center w-full gap-2">';
    $html .= $render_per_page_select();
    if ($total_records !== null) {
        $html .= '<span class="text-gray-400 font-bold text-xs bg-white border border-gray-200/80 px-2.5 py-2 rounded-xl shadow-xs">' . number_format($total_records) . ' items</span>';
    }
    $html .= '</div>';

    $html .= '</div>';

    // ================= DESKTOP / TABLET VIEW (>= 640px) =================
    // Smart 3-zone layout: [Show X per page] ... [Sliding Window] ... [Page info / count]
    $html .= '<div class="hidden sm:flex flex-wrap items-center justify-between w-full max-w-5xl px-4 gap-3">';

    // Left: Per-page selector
    $html .= '<div class="flex items-center">';
    $html .= $render_per_page_select();
    $html .= '</div>';

    // Center: Sliding window pagination bar (7 fixed slots maximum)
    $html .= '<div class="flex items-center justify-center gap-1.5 md:gap-2">';

    // Desktop: Previous Button
    if ($is_first) {
        $html .= '<span class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl border border-gray-100 bg-gray-50/70 text-gray-300 flex items-center justify-center text-xs cursor-not-allowed opacity-50" aria-disabled="true"><i class="fas fa-chevron-left text-xs"></i></span>';
    } else {
        $html .= '<a href="' . $prev_url . '" class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl border border-gray-200/80 bg-white text-gray-600 hover:bg-black hover:text-[#24B25D] hover:border-black flex items-center justify-center text-xs shadow-xs active:scale-95 transition-all" aria-label="Previous Page"><i class="fas fa-chevron-left text-xs"></i></a>';
    }

    // Desktop: Number Buttons & Ellipsis
    foreach ($pages as $item) {
        if ($item === '...') {
            $html .= '<span class="w-8 h-10 sm:w-10 sm:h-11 flex items-center justify-center text-gray-400 font-bold tracking-widest text-xs select-none">...</span>';
        } else {
            $page_num = (int)$item;
            $page_url = $get_page_url($page_num);
            if ($page_num === $current_page) {
                $html .= '<span class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl border border-[#24B25D] bg-[#24B25D] text-black font-black flex items-center justify-center text-xs sm:text-sm shadow-md shadow-green-500/25 scale-105" aria-current="page">' . $page_num . '</span>';
            } else {
                $html .= '<a href="' . $page_url . '" class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl border border-gray-200/80 bg-white text-gray-600 hover:text-black hover:bg-gray-50 hover:border-gray-300 flex items-center justify-center text-xs sm:text-sm font-bold shadow-xs active:scale-95 transition-all">' . $page_num . '</a>';
            }
        }
    }

    // Desktop: Next Button
    if ($is_last) {
        $html .= '<span class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl border border-gray-100 bg-gray-50/70 text-gray-300 flex items-center justify-center text-xs cursor-not-allowed opacity-50" aria-disabled="true"><i class="fas fa-chevron-right text-xs"></i></span>';
    } else {
        $html .= '<a href="' . $next_url . '" class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl border border-gray-200/80 bg-white text-gray-600 hover:bg-black hover:text-[#24B25D] hover:border-black flex items-center justify-center text-xs shadow-xs active:scale-95 transition-all" aria-label="Next Page"><i class="fas fa-chevron-right text-xs"></i></a>';
    }

    $html .= '</div>';

    // Right: Status Badge & Total Count
    $html .= '<div class="flex items-center text-[11px] font-bold text-gray-400 uppercase tracking-wider bg-white border border-gray-200/80 px-3.5 py-2 rounded-xl shadow-xs">';
    $html .= 'Page <span class="text-black font-black mx-1">' . $current_page . '</span> of <span class="text-gray-700 font-bold ml-1">' . $total_pages . '</span>';
    if ($total_records !== null) {
        $html .= '<span class="text-gray-300 mx-1.5">•</span>';
        $html .= '<span class="text-gray-600 font-semibold">' . number_format($total_records) . ' items</span>';
    }
    $html .= '</div>';

    $html .= '</div>';
    $html .= '</nav>';

    return $html;
}

// -------------------------------------------------------------------------
// GLOBAL MAINTENANCE MODE CHECK
// -------------------------------------------------------------------------
if (function_exists('get_setting') && !defined('MAINTENANCE_CHECK_RUN')) {
    define('MAINTENANCE_CHECK_RUN', true);
    
    // Safety check: ensure DB is connected (get_setting relies on it)
    // If get_setting fails (e.g. returns null because table missing), we default to 'off' inside it? 
    // get_setting returns default if fetch_one returns false?
    // fetch_one relies on execute_query -> get_db_connection.
    // So if DB is down, this might throw. But if DB is down, site is down anyway.

    $g_maintenance = get_setting('maintenance_mode', 'off');

    if ($g_maintenance === 'on') {
        // Check if user is admin (bypass everything)
        $g_is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

        if (!$g_is_admin) {
            $g_script = basename($_SERVER['PHP_SELF']);
            $g_uri = $_SERVER['REQUEST_URI'];
            
            // Allow admin access based on URL if not logged in (to see login page)
            // But login.php is explicitly allowed.
            // Admin folder usually protected by require_login/require_admin in their files.
            
            $g_allowed = (
                $g_script === 'maintenance.php' || 
                $g_script === 'login.php' ||
                str_contains($g_uri, '/admin') || // Simple check for admin path
                str_contains($g_uri, '/api')      // API access
            );

            if (!$g_allowed) {
                header("Location: " . get_url('maintenance.php'));
                exit;
            }
        }
    }
}



