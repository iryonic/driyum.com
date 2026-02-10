<?php
// Start output buffering to prevent header errors
ob_start();

require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$ids = $_POST['ids'] ?? [];
$all_selected = ($_POST['all_selected'] ?? 'false') === 'true';

if ($all_selected) {
    // Re-calculate all IDs based on current filters
    $user_id = (int)($_POST['user_id'] ?? 0);
    $status_filter = sanitize_input($_POST['status_filter'] ?? '');
    $q = sanitize_input($_POST['q'] ?? '');
    
    $where = "WHERE 1=1";
    $params = [];
    if ($user_id) { $where .= " AND user_id = ?"; $params[] = $user_id; }
    if ($status_filter) { $where .= " AND order_status = ?"; $params[] = $status_filter; }
    if ($q) { 
        $where .= " AND (order_number LIKE ? OR shipping_address LIKE ?)"; 
        $params[] = "%$q%"; 
        $params[] = "%$q%"; 
    }
    
    $all_ids_data = fetch_all("SELECT id FROM orders $where", $params);
    if ($all_ids_data) {
        $ids = array_column($all_ids_data, 'id');
    }
}

if (empty($ids) || !$action) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No items found matching selection']);
    exit;
}

$conn = get_db_connection();
$ids_str = implode(',', array_map('intval', $ids));

try {
    if ($action === 'bulk_delete') {
        $conn->query("DELETE FROM order_items WHERE order_id IN ($ids_str)");
        $conn->query("DELETE FROM order_status_history WHERE order_id IN ($ids_str)");
        $conn->query("DELETE FROM orders WHERE id IN ($ids_str)");
        
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'bulk_status') {
        $status = sanitize_input($_POST['status'] ?? '');
        if (!$status) throw new Exception("Status required");

        // 1. Update all order statuses in ONE query (Super Fast)
        $conn->query("UPDATE orders SET order_status = '$status' WHERE id IN ($ids_str)");
        
        // 2. Add history records in ONE query
        $history_values = [];
        $notes = "Order status updated to $status via bulk action by admin";
        foreach ($ids as $id) {
            $history_values[] = "(" . (int)$id . ", '" . $conn->real_escape_string($status) . "', '" . $conn->real_escape_string($notes) . "')";
        }
        $conn->query("INSERT INTO order_status_history (order_id, status, notes) VALUES " . implode(',', $history_values));

        // Prepare response
        $response = json_encode(['success' => true, 'count' => count($ids)]);
        
        // Respond to client as fast as possible
        ob_clean();
        header('Content-Type: application/json');
        
        if (function_exists('fastcgi_finish_request')) {
            echo $response;
            fastcgi_finish_request();
        } else {
            // Fallback for non-FPM environments (like some Apache setups)
            // Note: This isn't always reliable but better than nothing
            ignore_user_abort(true);
            set_time_limit(300);
            
            echo $response;
            header('Connection: close');
            header('Content-Length: ' . ob_get_length());
            ob_end_flush();
            flush();
        }

        // Now process emails after the user sees "Success"
        foreach ($ids as $id) {
            send_order_status_email($id, $status, true); // true = add to queue
        }
        exit;
    }

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
