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
    if (send_abandoned_cart_reminder($cart['id'])) {
        echo "[" . date('Y-m-d H:i:s') . "] Reminder sent to " . $cart['email'] . "\n";
        execute_query("UPDATE abandoned_carts SET is_reminded = 1 WHERE id = ?", [$cart['id']]);
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Failed to send email to " . $cart['email'] . "\n";
    }
}


