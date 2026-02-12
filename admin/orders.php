<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Strict Admin Check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    if (isset($_POST['ajax_action']) || isset($_POST['is_ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    header("Location: ../login.php");
    exit;
}

// AJAX Bulk Actions
if (isset($_POST['ajax_action']) && in_array($_POST['ajax_action'], ['bulk_status', 'bulk_delete'])) {
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No orders selected']);
        exit;
    }

    $conn = get_db_connection();
    $ids_str = implode(',', array_map('intval', $ids));
    
    try {
        if ($_POST['ajax_action'] === 'bulk_delete') {
            // Delete order items first (foreign key constraints)
            $conn->query("DELETE FROM order_items WHERE order_id IN ($ids_str)");
            $conn->query("DELETE FROM order_status_history WHERE order_id IN ($ids_str)");
            $conn->query("DELETE FROM orders WHERE id IN ($ids_str)");
        } else {
            $status = sanitize_input($_POST['status']);
            $conn->query("UPDATE orders SET order_status = '$status' WHERE id IN ($ids_str)");
            
            // Add history and send emails for each
            foreach ($ids as $id) {
                $notes = "Order status updated to $status via bulk action by admin";
                execute_query("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)", [$id, $status, $notes]);
                // Pass true for $queue to avoid waiting for SMTP
                send_order_status_email($id, $status, true);
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Update Status
if ((isset($_GET['status']) || isset($_POST['ajax_action'])) && isset($_REQUEST['id'])) {
    $status = sanitize_input($_REQUEST['status'] ?? $_POST['status']);
    $id = (int)$_REQUEST['id'];
    
    try {
        $conn = get_db_connection();
        $order = fetch_one("SELECT id FROM orders WHERE id = ?", [$id]);
        
        if (!$order) {
            throw new Exception("Order not found (ID: $id)");
        }

        execute_query("UPDATE orders SET order_status = ? WHERE id = ?", [$status, $id]);
        
        $notes = "Order status updated to $status by admin via " . (isset($_POST['ajax_action']) ? 'AJAX' : 'Direct Link');
        execute_query("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)", [$id, $status, $notes]);
        
        // Send email notification to customer (Immediate, not queued)
        send_order_status_email($id, $status, false); 
        
        if (isset($_POST['ajax_action'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'status' => $status, 'label' => str_replace('_', ' ', $status)]);
            exit;
        }
        
        echo "<script>window.location='orders.php?success=status_updated';</script>"; 
        exit;
        
    } catch (Exception $e) {
        if (isset($_POST['ajax_action'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        $error = $e->getMessage();
    }
}

// Handle Dispatch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispatch_order'])) {
    $order_id = (int)$_POST['order_id'];
    $tracking_number = sanitize_input($_POST['tracking_number']);
    $dispatch_date = sanitize_input($_POST['dispatch_date']);
    $tracking_note = sanitize_input($_POST['tracking_note'] ?? '');
    
    try {
        execute_query("UPDATE orders SET tracking_number = ?, dispatch_date = ?, tracking_note = ?, order_status = 'shipped' WHERE id = ?", 
            [$tracking_number, $dispatch_date, $tracking_note, $order_id]);
        
        $notes = "Order dispatched via India Post. Tracking Number: $tracking_number. Note: $tracking_note";
        execute_query("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, 'shipped', ?)", [$order_id, $notes]);
        
        // Send email notification to customer (queued for speed)
        send_order_status_email($order_id, 'shipped', true); 
        
        if (isset($_POST['is_ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'status' => 'shipped', 'id' => $order_id]);
            exit;
        }

        echo "<script>window.location='orders.php?success=dispatched';</script>";
        exit;
    } catch (Exception $e) {
        if (isset($_POST['is_ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        $error = $e->getMessage();
    }
}
?>
<?php include 'includes/header.php'; ?>
<?php
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$status_filter = sanitize_input($_GET['status_filter'] ?? '');
$search = sanitize_input($_GET['q'] ?? '');

$params = [];
$where_clause = "WHERE 1=1";

if ($user_filter) {
    $where_clause .= " AND o.user_id = ?";
    $params[] = $user_filter;
}

if ($status_filter) {
    $where_clause .= " AND o.order_status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_clause .= " AND (o.order_number LIKE ? OR o.shipping_address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query = "SELECT o.*, u.name as user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id $where_clause ORDER BY o.created_at DESC";
$pagination = get_pagination_data($query, $params, 10);
$orders = $pagination['records'];
?>
<style>
    @media (max-width: 768px) {
        #bulk-action-bar {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            transform: none !important;
            width: 100% !important;
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
            padding: 1.5rem 1.25rem 2rem 1.25rem; /* Increased bottom padding for safe area */
            border-radius: 24px 24px 0 0;
            border-top: 1px solid rgba(255,255,255,0.1);
            border-left: none;
            border-right: none;
            border-bottom: none;
           
        }

        /* 1. Selection Info Section */
        #bulk-action-bar > div:first-child {
            border-right: none !important;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-right: 0 !important;
            padding-bottom: 1rem;
            margin-bottom: 0px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        /* 2. Actions Container (Flex Col -> Stack items) */
        #bulk-action-bar > div:last-child {
            flex-direction: column;
            width: 100%;
            gap: 12px;
            align-items: stretch;
        }

        /* 3. Dropdown Group */
        #bulk-action-bar .group {
            width: 100%;
        }
        #bulk-status-select {
            width: 100%;
            min-width: 0; /* Allow shrinking if needed */
        }
        
        /* 4. Separators */
        .desktop-separator,
        .h-8.w-px { display: none !important; }

        /* 5. Buttons */
        #bulk-apply-btn { 
            width: 100%; 
            justify-content: center;
        }

        /* 6. Grid for Secondary Actions */
        .bulk-actions-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            width: 100%;
        }
    }
</style>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <!-- Header Content Remained Same -->
    <div>
        <h1 class="text-3xl font-black text-gray-900 crimson-pro tracking-tight">Orders</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Found <span class="text-black"><?php echo $pagination['total_records']; ?></span> orders</p>
    </div>
    
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
        <form class="flex flex-col sm:flex-row gap-3 w-full">
            <div class="relative group flex-1 md:w-64">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search orders..." class="w-full bg-white border border-gray-100 focus:border-[#24B25D] rounded-2xl pl-10 pr-4 py-2.5 text-xs font-bold transition-all outline-none shadow-sm">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-[#24B25D] transition-colors text-[10px]"></i>
            </div>
            <select name="status_filter" onchange="this.form.submit()" class="bg-white border border-gray-100 rounded-2xl px-4 py-2.5 text-[10px] font-black uppercase tracking-widest outline-none focus:border-[#24B25D] shadow-sm">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </form>
    </div>
</div>

<!-- Bulk Bar -->
<div id="bulk-action-bar" class="hidden fixed bottom-10 z-50 bg-black backdrop-blur-xl border border-white/10 px-8 py-5 rounded-[2rem] shadow-2xl shadow-blue-900/20 items-center gap-8 anim-up ring-1 ring-white/10">
    <div class="flex items-center gap-3 border-r border-white/10 pr-8">
        <div class="w-8 h-8 rounded-full bg-[#24B25D] text-black flex items-center justify-center font-black text-xs shadow-lg shadow-green-500/20" id="selected-count-circle">0</div>
        <div class="flex flex-col">
            <span class="text-[9px] font-black text-white/50 uppercase tracking-widest">Selection</span>
            <span class="text-xs font-bold text-white">Orders Active</span>
        </div>

        <div id="all-pages-notice" class="hidden">
            <button onclick="selectAllPages()" class="text-[9px] font-black text-white hover:text-[#24B25D] uppercase tracking-[0.15em] border border-white/10 px-2 py-1 rounded-lg transition-all ml-2">Select all <?php echo $pagination['total_records']; ?> orders</button>
        </div>
        <div id="all-pages-active" class="hidden">
            <span class="text-[9px] font-black text-white uppercase tracking-[0.15em] ml-2">All <?php echo $pagination['total_records']; ?> orders selected</span>
            <button onclick="resetSelection()" class="text-[9px] font-bold text-red-400 hover:underline ml-2">Clear</button>
        </div>
    </div>
    
    <div class="flex flex-col md:flex-row items-center gap-4">
        <div class="flex items-center gap-2">
            <div class="relative group">
                <i class="fas fa-bolt absolute left-3 top-1/2 -translate-y-1/2 text-white/30 text-xs"></i>
                <select id="bulk-status-select" class="pl-8 bg-black border border-white/10 rounded-xl pr-8 py-2.5 text-[10px] font-bold text-white outline-none focus:border-[#24B25D] focus:bg-white/10 transition-all hover:border-white/20 appearance-none cursor-pointer min-w-[140px]">
                    <option value="" class="bg-black">Choose Action...</option>
                    <option value="confirmed" class="bg-black">Mark Confirmed</option>
                    <option value="shipped" class="bg-black">Mark Shipped</option>
                    <option value="delivered" class="bg-black">Mark Delivered</option>
                    <option value="cancelled" class="bg-black">Mark Cancelled</option>
                </select>
                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-white/30 text-[10px] pointer-events-none"></i>
            </div>
            <button id="bulk-apply-btn" onclick="applyBulkStatus()" class="bg-[#24B25D] hover:bg-[#15bd6b] text-black text-[10px] font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-green-500/20 hover:shadow-green-500/40 transition-all transform active:scale-95 flex items-center gap-2">
                Apply <i class="fas fa-check"></i>
            </button>
        </div>

        <div class="h-8 w-px bg-white/10 desktop-separator"></div>

        <div class="bulk-actions-grid flex items-center gap-2">
             <button onclick="applyBulkShipments()" class="flex-1 bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/20 text-white px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2 group">
                <i class="fas fa-shipping-fast text-blue-400 group-hover:scale-110 transition-transform"></i> <span class="hidden md:inline">Print</span> Label
            </button>

            <button onclick="applyBulkDelete()" class="bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 hover:border-red-500/40 text-red-500 p-2.5 rounded-xl transition-all flex items-center justify-center group" title="Delete Selected">
                <i class="fas fa-trash-alt text-xs group-hover:rotate-12 transition-transform"></i>
            </button>
        </div>
    </div>
        <i class="fas fa-trash-alt text-xs"></i>
    </button>
</div>

<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-gray-400 text-[9px] uppercase font-black bg-gray-50/50 border-b border-gray-100 tracking-widest">
                    <th class="p-4 w-12 text-center">
                        <input type="checkbox" id="select-all" class="w-4 h-4 rounded border-gray-200 text-[#24B25D] focus:ring-[#24B25D] cursor-pointer shadow-sm">
                    </th>
                    <th class="p-4">Order</th>
                    <th class="p-4">Customer</th>
                    <th class="p-4 text-center">Total</th>
                    <th class="p-4">Status</th>
                    <th class="p-4 text-right">Manage</th>
                </tr>
            </thead>
            <tbody class="text-xs text-gray-600">
                <?php foreach ($orders as $o): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-all group">
                    <td class="p-4 text-center">
                        <input type="checkbox" class="order-checkbox w-4 h-4 rounded border-gray-200 text-[#24B25D] focus:ring-[#24B25D] cursor-pointer" value="<?php echo $o['id']; ?>">
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-900 font-black text-xs border border-gray-100 group-hover:bg-black group-hover:text-[#24B25D] transition-colors">
                                <?php echo $o['id']; ?>
                            </div>
                            <div>
                                <span class="font-bold text-gray-900 block leading-tight mb-0.5"><?php echo $o['order_number']; ?></span>
                                <span class="text-[8px] font-black text-gray-400 uppercase"><?php echo date('M d, H:i', strtotime($o['created_at'])); ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        <div class="font-bold text-gray-800 leading-tight mb-0.5"><?php echo $o['user_name'] ?: 'Guest'; ?></div>
                        <div class="flex items-center gap-2">
                            <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest px-1.5 py-0.5 bg-gray-50 rounded border border-gray-100"><?php echo $o['payment_method']; ?></span>
                            <?php if(!empty($o['razorpay_payment_id'])): ?>
                                <span class="text-[7px] font-bold text-[#24B25D]">PayID: <?php echo substr($o['razorpay_payment_id'], -8); ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="p-4 text-center font-black text-gray-900">
                        ₹<?php echo number_format($o['total'], 0); ?>
                    </td>
                    <td class="p-4">
                        <div class="relative inline-block status-dropdown-container">
                            <button onclick="toggleStatusDropdown(this, event)" class="status-btn px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest flex items-center gap-2 shadow-sm transition-all hover:scale-105 active:scale-95 border-b-2
                                <?php echo get_status_color($o['order_status']); ?>
                            ">
                                <?php echo str_replace('_', ' ', $o['order_status']); ?> 
                                <i class="fas fa-chevron-down opacity-50 text-[8px] transition-transform duration-300"></i>
                            </button>
                            
                            <div class="status-menu absolute left-0 top-full mt-2 w-48 bg-white rounded-2xl shadow-2xl border border-gray-100 py-2 hidden z-50 overflow-hidden transform origin-top-left transition-all">
                                <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'pending', this)" class="block px-4 py-2 hover:bg-yellow-50 text-yellow-600 font-bold text-[9px] uppercase tracking-widest transition-colors">Pending</a>
                                <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'confirmed', this)" class="block px-4 py-2 hover:bg-indigo-50 text-indigo-600 font-bold text-[9px] uppercase tracking-widest transition-colors">Confirm</a>
                                <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'shipped', this)" class="block px-4 py-2 hover:bg-blue-50 text-blue-600 font-bold text-[9px] uppercase tracking-widest transition-colors">Ship</a>
                                <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'delivered', this)" class="block px-4 py-2 hover:bg-green-50 text-green-600 font-bold text-[9px] uppercase tracking-widest transition-colors">Deliver</a>
                                <div class="border-t border-gray-50 my-1"></div>
                                <a href="javascript:void(0)" onclick="openDispatchModal(<?php echo $o['id']; ?>, '<?php echo $o['order_number']; ?>')" class="block px-4 py-2 hover:bg-blue-50 text-blue-700 font-black text-[9px] uppercase tracking-widest transition-colors">🚀 Send Order</a>
                                <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'cancelled', this)" class="block px-4 py-2 hover:bg-red-50 text-red-600 font-bold text-[9px] uppercase tracking-widest transition-colors">Cancel</a>
                            </div>
                        </div>
                    </td>
                    <td class="p-4 text-right">
                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300">
                            <a href="../invoice.php?id=<?php echo $o['order_number']; ?>" target="_blank" title="Invoice" class="w-8 h-8 bg-gray-50 text-gray-400 hover:bg-black hover:text-[#24B25D] flex items-center justify-center rounded-lg transition-all"><i class="fas fa-file-invoice text-xs"></i></a>
                            <a href="generate_label.php?id=<?php echo $o['order_number']; ?>" target="_blank" title="Shipping Label" class="w-8 h-8 bg-gray-50 text-gray-400 hover:bg-black hover:text-[#24B25D] flex items-center justify-center rounded-lg transition-all"><i class="fas fa-barcode text-xs"></i></a>
                            <a href="../track.php?id=<?php echo $o['order_number']; ?>" target="_blank" title="Track Live" class="w-8 h-8 bg-gray-50 text-gray-400 hover:bg-indigo-500 hover:text-white flex items-center justify-center rounded-lg transition-all"><i class="fas fa-location-arrow text-[10px]"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($orders)): ?>
                <tr>
                    <td colspan="6" class="p-20 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-inbox text-gray-200 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-black text-gray-400 crimson-pro">No Orders Found</h3>
                        <p class="text-[10px] text-gray-300 font-bold uppercase tracking-widest mt-1">Check back later or adjust filters</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Dispatch Modal -->
<div id="dispatchModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[200]">
    <div class="bg-white rounded-[40px] p-10 w-full max-w-lg shadow-2xl anim-up">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl">
                <i class="fas fa-shipping-fast"></i>
            </div>
            <div>
                <h3 class="text-3xl font-black crimson-pro text-gray-900">Send Order</h3>
                <p class="text-gray-400 font-medium" id="dispatch-order-number">ORD-000000</p>
            </div>
        </div>
        
        <form action="orders.php" method="POST" id="dispatch-form" class="space-y-6">
            <input type="hidden" name="order_id" id="dispatch-order-id">
            <input type="hidden" name="dispatch_order" value="1">
            
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Tracking Number (India Post)</label>
                <input type="text" name="tracking_number" required placeholder="e.g. EB123456789IN" class="w-full bg-gray-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
            </div>
            
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Dispatch Date</label>
                <input type="date" name="dispatch_date" id="dispatch-date" required value="<?php echo date('Y-m-d'); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold">
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Tracking Note (For Customer)</label>
                <textarea name="tracking_note" id="dispatch-note" placeholder="e.g. Your parcel has reached the Srinagar sorting hub." class="w-full bg-gray-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-[24px] px-6 py-4 outline-none transition-all font-bold h-24 resize-none"></textarea>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="button" onclick="closeDispatchModal()" class="flex-1 bg-gray-100 text-gray-500 py-5 rounded-[24px] font-black uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-600 text-white py-5 rounded-[24px] font-black uppercase tracking-widest shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all" id="dispatch-btn">Mark Shipped</button>
            </div>
        </form>
    </div>
</div>

<script>
// Bulk Actions Logic
const STORAGE_KEY = 'driyum_selected_orders';
const selectAll = document.getElementById('select-all');
const orderCheckboxes = document.querySelectorAll('.order-checkbox');
const bulkBar = document.getElementById('bulk-action-bar');
const selectedCountText = document.getElementById('selected-count');
const allPagesNotice = document.getElementById('all-pages-notice');
const allPagesActive = document.getElementById('all-pages-active');

let isAllSelectedAcrossPages = (sessionStorage.getItem('driyum_all_pages_selected') === 'true');
const PAGE_SIZE = 10;
const TOTAL_RECORDS = <?php echo (int)$pagination['total_records']; ?>;
const FILTERS = {
    q: '<?php echo addslashes($search); ?>',
    status_filter: '<?php echo addslashes($status_filter); ?>',
    user_id: '<?php echo (int)$user_filter; ?>'
};

// Selection State Management
function getTracked() {
    const raw = sessionStorage.getItem(STORAGE_KEY);
    return raw ? new Set(JSON.parse(raw)) : new Set();
}

function syncTracked(set) {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify([...set]));
    updateBulkBar();
}

function selectAllPages() {
    isAllSelectedAcrossPages = true;
    sessionStorage.setItem('driyum_all_pages_selected', 'true');
    allPagesNotice.classList.add('hidden');
    allPagesActive.classList.remove('hidden');
    updateBulkBar();
}

function resetSelection() {
    isAllSelectedAcrossPages = false;
    sessionStorage.removeItem('driyum_all_pages_selected');
    sessionStorage.removeItem(STORAGE_KEY);
    if(selectAll) selectAll.checked = false;
    orderCheckboxes.forEach(cb => cb.checked = false);
    updateBulkBar();
}

function updateBulkBar() {
    const tracked = getTracked();
    const onPage = document.querySelectorAll('.order-checkbox').length;
    
    // Check/Uncheck the "Select All" header checkbox based on current page state
    const onPageCheckedCount = Array.from(orderCheckboxes).filter(cb => cb.checked).length;
    if(selectAll) selectAll.checked = (onPage > 0 && onPageCheckedCount === onPage);

    if (tracked.size > 0 || isAllSelectedAcrossPages) {
        bulkBar.classList.remove('hidden');
        bulkBar.classList.add('flex');
        
        if (isAllSelectedAcrossPages) {
            selectedCountText.textContent = TOTAL_RECORDS;
            allPagesNotice.classList.add('hidden');
            allPagesActive.classList.remove('hidden');
        } else {
            selectedCountText.textContent = tracked.size;
            
            // If all on current page are selected, show "Select All Across Pages" alert
            if (onPageCheckedCount === onPage && TOTAL_RECORDS > onPage && !isAllSelectedAcrossPages) {
                allPagesNotice.classList.remove('hidden');
            } else {
                allPagesNotice.classList.add('hidden');
            }
            allPagesActive.classList.add('hidden');
        }
    } else {
        bulkBar.classList.add('hidden');
        bulkBar.classList.remove('flex');
        allPagesNotice.classList.add('hidden');
        allPagesActive.classList.add('hidden');
    }
}

// Initial Sync
function initSelection() {
    const tracked = getTracked();
    orderCheckboxes.forEach(cb => {
        if (tracked.has(cb.value)) cb.checked = true;
    });
    updateBulkBar();
}

function updateBulkBar() {
    const tracked = getTracked();
    const count = isAllSelectedAcrossPages ? TOTAL_RECORDS : tracked.size;
    const bulkBar = document.getElementById('bulk-action-bar');
    
    // Update count
    const countCircle = document.getElementById('selected-count-circle');
    if(countCircle) countCircle.textContent = count;
    
    // Show/Hide Bar
    if (count > 0) {
        bulkBar.classList.remove('hidden');
        bulkBar.classList.add('flex');
    } else {
        bulkBar.classList.add('hidden');
        bulkBar.classList.remove('flex');
    }

    // "Select All" Logic across pages
    const notice = document.getElementById('all-pages-notice');
    const active = document.getElementById('all-pages-active');
    
    if (tracked.size === PAGE_SIZE && TOTAL_RECORDS > PAGE_SIZE && !isAllSelectedAcrossPages) {
        if(notice) notice.classList.remove('hidden');
    } else {
        if(notice) notice.classList.add('hidden');
    }

    if (isAllSelectedAcrossPages) {
        if(active) active.classList.remove('hidden');
    } else {
        if(active) active.classList.add('hidden');
    }
}

if(selectAll) {
    selectAll.addEventListener('change', () => {
        const tracked = getTracked();
        orderCheckboxes.forEach(cb => {
            cb.checked = selectAll.checked;
            if (selectAll.checked) tracked.add(cb.value);
            else tracked.delete(cb.value);
        });
        syncTracked(tracked);
    });
}

orderCheckboxes.forEach(cb => {
    cb.addEventListener('change', () => {
        const tracked = getTracked();
        if (cb.checked) tracked.add(cb.value);
        else {
            tracked.delete(cb.value);
            isAllSelectedAcrossPages = false; // Breaking "All" mode if someone deselects 1
            sessionStorage.setItem('driyum_all_pages_selected', 'false');
        }
        syncTracked(tracked);
    });
});

initSelection();

async function applyBulkStatus() {
    const status = document.getElementById('bulk-status-select').value;
    if (!status) return alert('Select a status first');
    
    const count = isAllSelectedAcrossPages ? TOTAL_RECORDS : getTracked().size;
    if(!confirm(`Update ${count} orders to ${status}?`)) return;
    
    await performBulkAction('bulk_status', { status });
}

async function applyBulkDelete() {
    const count = isAllSelectedAcrossPages ? TOTAL_RECORDS : getTracked().size;
    if(!confirm(`DANGER! Delete ${count} orders forever? This cannot be undone.`)) return;
    
    await performBulkAction('bulk_delete');
}

function applyBulkShipments() {
    const tracked = getTracked();
    if (tracked.size === 0 && !isAllSelectedAcrossPages) return alert('Select at least one order');
    
    // For shipping labels, we usually need the actual IDs unless we have a filter-based generator
    const ids = Array.from(tracked).join(',');
    window.open(`generate_batch_shipments.php?ids=${ids}`, '_blank');
}

async function performBulkAction(action, extraParams = {}) {
    const tracked = getTracked();
    const ids = Array.from(tracked);
    const originalContent = bulkBar.innerHTML;
    
    bulkBar.innerHTML = `<div class="flex items-center gap-3 px-10"><i class="fas fa-check-circle text-[#24B25D] text-xl"></i> <span class="text-[10px] font-black text-white uppercase tracking-widest">${isAllSelectedAcrossPages ? 'Updating all records...' : 'Update Started...'}</span></div>`;

    // Visual Update for current page (Optional)
    if(action === 'bulk_status' && extraParams.status) {
        ids.forEach(id => {
            const btn = document.querySelector(`button[onclick*="updateOrderStatus(${id}"]`) || 
                        document.querySelector(`a[onclick*="updateOrderStatus(${id}"]`)?.closest('.status-dropdown-container')?.querySelector('.status-btn');
            if(btn) btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Updating...`;
        });
    }

    try {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('all_selected', isAllSelectedAcrossPages);
        
        if (isAllSelectedAcrossPages) {
            formData.append('q', FILTERS.q);
            formData.append('status_filter', FILTERS.status_filter);
            formData.append('user_id', FILTERS.user_id);
        } else {
            ids.forEach(id => formData.append('ids[]', id));
        }

        for (const [key, value] of Object.entries(extraParams)) {
            formData.append(key, value);
        }

        // Use the new dedicated API for background processing
        const response = await fetch('api/bulk_action.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            resetSelection(); // Important to clear after success
            // Wait 500ms for user to see the "Update Started" then reload
            setTimeout(() => window.location.reload(), 800);
        } else {
            alert('Error: ' + data.message);
            bulkBar.innerHTML = originalContent;
        }
    } catch (e) {
        console.error('Bulk Action Failed:', e);
        alert('Operation failed: ' + e.message);
        bulkBar.innerHTML = originalContent;
    }
}

async function updateOrderStatus(id, status, el) {
    if (el.getAttribute('data-processing') === 'true') return;
    
    const container = el.closest('.status-dropdown-container');
    const btn = container.querySelector('.status-btn');
    
    // Set processing state
    el.setAttribute('data-processing', 'true');
    btn.disabled = true;
    
    // Show loading state
    const originalContent = btn.innerHTML;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Updating...`;
    
    try {
        const formData = new FormData();
        formData.append('ajax_action', 'update_status');
        formData.append('id', id);
        formData.append('status', status);

        const response = await fetch('orders.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Update button text and class
            btn.innerHTML = `${data.label} <i class="fas fa-chevron-down opacity-50 text-[10px] transition-transform duration-300"></i>`;
            
            // Remove all possible status classes
            btn.classList.remove('bg-yellow-400', 'bg-indigo-600', 'bg-blue-600', 'bg-[#24B25D]', 'bg-red-600', 'text-black', 'text-white');
            
            // Add new class
            let newClasses = [];
            switch(status) {
                case 'pending': newClasses = ['bg-yellow-400', 'text-black']; break;
                case 'confirmed': newClasses = ['bg-indigo-600', 'text-white']; break;
                case 'shipped': newClasses = ['bg-blue-600', 'text-white']; break;
                case 'delivered': newClasses = ['bg-[#24B25D]', 'text-black']; break;
                case 'cancelled': newClasses = ['bg-red-600', 'text-white']; break;
            }
            btn.classList.add(...newClasses);
            
            // Success animation
            btn.classList.add('scale-110');
            setTimeout(() => btn.classList.remove('scale-110'), 200);
        } else {
            alert('Failed to update status: ' + (data.message || 'Server error'));
            btn.innerHTML = originalContent;
        }
    } catch (error) {
        console.error('AJAX Error:', error);
        alert('An error occurred while updating status. Check console for details.');
        btn.innerHTML = originalContent;
    } finally {
        // Reset processing state
        el.setAttribute('data-processing', 'false');
        btn.disabled = false;
        
        // Close menu
        container.querySelector('.status-menu').classList.add('hidden');
        const icon = btn.querySelector('.fa-chevron-down');
        if (icon) icon.classList.remove('rotate-180');
    }
}

function openDispatchModal(id, number, tracking = '', note = '') {
    document.getElementById('dispatch-order-id').value = id;
    document.getElementById('dispatch-order-number').textContent = 'ID: ' + number;
    
    const trackingInput = document.querySelector('input[name="tracking_number"]');
    const noteInput = document.getElementById('dispatch-note');
    const btn = document.getElementById('dispatch-btn');
    
    if(tracking) {
        trackingInput.value = tracking;
        noteInput.value = note;
        btn.textContent = 'Update Tracking';
    } else {
        // Default tracking number to Order Number for convenience
        trackingInput.value = number;
        noteInput.value = '';
        btn.textContent = 'Mark Shipped';
    }

    document.getElementById('dispatchModal').classList.remove('hidden');
    document.getElementById('dispatchModal').classList.add('flex');
}

function closeDispatchModal() {
    document.getElementById('dispatchModal').classList.add('hidden');
    document.getElementById('dispatchModal').classList.remove('flex');
}

// AJAX Dispatch Submission
document.getElementById('dispatch-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = this;
    const btn = document.getElementById('dispatch-btn');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dispatching...';
    btn.disabled = true;

    try {
        const formData = new FormData(form);
        formData.append('is_ajax', '1');

        const response = await fetch('orders.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Find the status button for this order and update it
            const orderRow = document.querySelector(`tr:has(span:contains("#${data.id}"))`) || 
                             document.querySelector(`tr:has(div:contains("#${data.id}"))`);
            
            // Simpler way: reload current page's data or just update that specific button
            // Since we already have updateOrderStatus, let's use it or simulate its success
            const statusBtn = document.querySelector(`button[onclick*="updateOrderStatus(${data.id}"]`) || 
                              document.querySelector(`a[onclick*="updateOrderStatus(${data.id}"]`)?.closest('.status-dropdown-container')?.querySelector('.status-btn');
            
            if (statusBtn) {
                statusBtn.innerHTML = `Shipped <i class="fas fa-chevron-down opacity-50 text-[10px] transition-transform duration-300"></i>`;
                statusBtn.classList.remove('bg-yellow-400', 'bg-indigo-600', 'bg-blue-600', 'bg-[#24B25D]', 'bg-red-600', 'text-black', 'text-white');
                statusBtn.classList.add('bg-blue-600', 'text-white');
            }

            closeDispatchModal();
            // Optional: show a small toast
            if(typeof showToast === 'function') showToast('Order Dispatched!', 'success');
            else alert('Order Dispatched Successfully!');
        } else {
            alert('Failed to dispatch order');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred during dispatch');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

// Click-based Dropdown Logic
function toggleStatusDropdown(btn, e) {
    e.stopPropagation();
    const container = btn.closest('.status-dropdown-container');
    const menu = container.querySelector('.status-menu');
    const icon = btn.querySelector('.fa-chevron-down');
    
    // Close others
    document.querySelectorAll('.status-menu').forEach(m => {
        if(m !== menu) {
            m.classList.add('hidden');
            m.closest('.status-dropdown-container').querySelector('.fa-chevron-down').classList.remove('rotate-180');
        }
    });

    menu.classList.toggle('hidden');
    icon.classList.toggle('rotate-180');
}

// Close dropdowns on outside click
document.addEventListener('click', () => {
    document.querySelectorAll('.status-menu').forEach(m => m.classList.add('hidden'));
    document.querySelectorAll('.fa-chevron-down').forEach(i => i.classList.remove('rotate-180'));
});
</script>

<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
<?php include 'includes/footer.php'; ?>


