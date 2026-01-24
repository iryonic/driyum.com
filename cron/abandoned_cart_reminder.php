// Set base path if not already set
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Abandoned time threshold (e.g. 1 hour)
$threshold = "1 HOUR";

// 1. Find abandoned carts that haven't been reminded
$sql = "SELECT ac.*, u.email, u.name 
        FROM abandoned_carts ac
        JOIN users u ON ac.user_id = u.id
        WHERE ac.is_reminded = 0 
        AND ac.last_updated < (NOW() - INTERVAL $threshold)
        LIMIT 50";

$abandoned_carts = fetch_all($sql);

if (empty($abandoned_carts)) {
    // Silent return if called via include
    return;
}

foreach ($abandoned_carts as $cart) {
    $cart_items_data = json_decode($cart['cart_data'], true);
    if (empty($cart_items_data)) continue;

    $user_email = $cart['email'];
    $user_name = $cart['name'];
    
    // Build email content
    $subject = "Your Driyum snacks are waiting! 🍎";
    
    $items_html = "";
    $subtotal = 0;
    
    // Get product details for individual items
    foreach($cart_items_data as $pid => $qty) {
        $p = fetch_one("SELECT name, price FROM products WHERE id = ?", [$pid]);
        if($p) {
            $total = $p['price'] * $qty;
            $subtotal += $total;
            $items_html .= "<tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$p['name']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>x $qty</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>₹$total</td>
            </tr>";
        }
    }

    // Use helper if available, otherwise construct
    if (function_exists('get_url')) {
        // get_url returns path relative to domain root usually.
        // For email we need absolute URL including domain.
        // We can use the logic from before but replace /newdry/ with BASE_URL path
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $checkout_url = $scheme . "://" . $host . get_url('checkout');
    } else {
        $checkout_url = (defined('BASE_URL') ? BASE_URL : '/') . "checkout";
    }

    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px; border-radius: 10px;'>
        <h2 style='color: #19DC7E;'>Hey $user_name, did you forget something?</h2>
        <p>We noticed you left some delicious snacks in your cart. They are still here waiting for you!</p>
        
        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
            <thead>
                <tr style='background: #f9f9f9;'>
                    <th style='padding: 10px; text-align: left;'>Item</th>
                    <th style='padding: 10px; text-align: left;'>Qty</th>
                    <th style='padding: 10px; text-align: right;'>Total</th>
                </tr>
            </thead>
            <tbody>
                $items_html
            </tbody>
            <tfoot>
                <tr>
                    <td colspan='2' style='padding: 10px; font-weight: bold; text-align: right;'>Subtotal:</td>
                    <td style='padding: 10px; font-weight: bold; text-align: right;'>₹$subtotal</td>
                </tr>
            </tfoot>
        </table>

        <div style='text-align: center; margin-top: 30px;'>
            <a href='$checkout_url' style='background: #19DC7E; color: black; padding: 15px 30px; text-decoration: none; border-radius: 30px; font-weight: bold; display: inline-block;'>Complete Your Purchase</a>
        </div>
        
        <p style='margin-top: 30px; font-size: 12px; color: #999; text-align: center;'>
            If you need help, just reply to this email. We're here to help!
        </p>
    </div>";

    // Send email
    if (send_email($user_email, $subject, $message)) {
        echo "[" . date('Y-m-d H:i:s') . "] Reminder sent to $user_email\n";
        // Mark as reminded
        execute_query("UPDATE abandoned_carts SET is_reminded = 1 WHERE id = ?", [$cart['id']]);
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Failed to send email to $user_email\n";
    }
}
