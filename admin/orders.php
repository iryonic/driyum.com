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
                $notes = "Your order status has been updated to " . ucfirst(str_replace('_', ' ', $status));
                execute_query("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)", [$id, $status, $notes]);
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
        
        $notes = "Your order status has been updated to " . ucfirst(str_replace('_', ' ', $status));
        execute_query("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)", [$id, $status, $notes]);
        
        send_order_status_email($id, $status, true); 
        
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

// Counts by status for tabs
$status_counts = [];
try {
    $sc_query = "SELECT order_status, COUNT(*) as cnt FROM orders GROUP BY order_status";
    $sc_res = fetch_all($sc_query);
    foreach ($sc_res as $sc) {
        $status_counts[$sc['order_status']] = (int)$sc['cnt'];
    }
} catch (Exception $e) {}

$query = "SELECT o.*, u.name as user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id $where_clause ORDER BY o.created_at DESC";
$pagination = get_pagination_data($query, $params, 15);
$orders = $pagination['records'];

// Status styling helper
function get_order_badge_style($status) {
    switch ($status) {
        case 'pending':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'pending_payment':
            return 'bg-orange-50 text-orange-700 border-orange-200';
        case 'confirmed':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'shipped':
            return 'bg-sky-50 text-sky-700 border-sky-200';
        case 'delivered':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'cancelled':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200';
    }
}
?>

<div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Orders</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage customer orders, track fulfillment status, and generate shipping documentation.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="exportOrders()" class="btn-admin btn-admin-secondary text-xs">
                <i class="fas fa-file-export text-slate-500"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar border-b border-slate-200 text-xs">
        <?php
        $tabs = [
            '' => ['label' => 'All Orders', 'count' => array_sum($status_counts)],
            'pending' => ['label' => 'Pending', 'count' => $status_counts['pending'] ?? 0],
            'pending_payment' => ['label' => 'Awaiting Payment', 'count' => $status_counts['pending_payment'] ?? 0],
            'confirmed' => ['label' => 'Confirmed', 'count' => $status_counts['confirmed'] ?? 0],
            'shipped' => ['label' => 'Shipped', 'count' => $status_counts['shipped'] ?? 0],
            'delivered' => ['label' => 'Delivered', 'count' => $status_counts['delivered'] ?? 0],
            'cancelled' => ['label' => 'Cancelled', 'count' => $status_counts['cancelled'] ?? 0],
        ];
        foreach ($tabs as $key => $tab):
            $isActive = ($status_filter === $key);
            $queryUrl = 'orders.php?' . http_build_query(array_merge($_GET, ['status_filter' => $key, 'page' => 1]));
        ?>
            <a href="<?php echo htmlspecialchars($queryUrl); ?>" 
               class="px-3.5 py-2 font-semibold whitespace-nowrap rounded-t-lg transition-colors flex items-center gap-2 border-b-2 <?php echo $isActive ? 'text-emerald-950 border-[#004f42] bg-emerald-50/70 font-bold' : 'text-slate-600 border-transparent hover:text-slate-900 hover:bg-slate-50'; ?>">
                <span><?php echo $tab['label']; ?></span>
                <span class="text-[11px] px-1.5 py-0.5 rounded-full font-bold <?php echo $isActive ? 'bg-[#004f42] text-white' : 'bg-slate-100 text-slate-600'; ?>">
                    <?php echo $tab['count']; ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Filter & Search Bar -->
    <div class="admin-card p-4">
        <form method="GET" action="orders.php" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <?php if ($status_filter): ?>
                <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
            <?php endif; ?>
            <?php if ($user_filter): ?>
                <input type="hidden" name="user_id" value="<?php echo (int)$user_filter; ?>">
            <?php endif; ?>

            <div class="relative flex-1 max-w-md">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by Order # or Address..." class="admin-input pl-9 text-xs">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn-admin btn-admin-primary text-xs">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
                <?php if ($search || $status_filter || $user_filter): ?>
                    <a href="orders.php" class="btn-admin btn-admin-secondary text-xs" title="Reset Filters">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Bulk Floating Dock (Unified Light Theme & Responsive) -->
    <div id="bulk-action-bar" class="hidden admin-bulk-dock">
        <div class="flex items-center gap-2 pr-3 border-r border-slate-200 shrink-0">
            <span class="bulk-counter-badge">
                <span id="selected-count-circle">0</span> Selected
            </span>
            <div id="all-pages-notice" class="hidden">
                <button type="button" onclick="selectAllPages()" class="text-[11px] text-emerald-700 font-bold hover:underline ml-1">Select all <?php echo $pagination['total_records']; ?></button>
            </div>
            <div id="all-pages-active" class="hidden">
                <span class="text-[11px] text-emerald-700 font-bold">All <?php echo $pagination['total_records']; ?></span>
                <button type="button" onclick="resetSelection()" class="text-[11px] text-rose-600 font-bold hover:underline ml-1">Clear</button>
            </div>
            <button type="button" onclick="resetSelection()" class="text-slate-400 hover:text-slate-700 ml-1" title="Clear selection">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <select id="bulk-status-select" class="admin-select py-1 px-2 text-xs font-semibold bg-white border-slate-200 text-slate-800">
                <option value="">Update Status...</option>
                <option value="confirmed">Mark Confirmed</option>
                <option value="shipped">Mark Shipped</option>
                <option value="delivered">Mark Delivered</option>
                <option value="cancelled">Mark Cancelled</option>
            </select>
            <button type="button" id="bulk-apply-btn" onclick="applyBulkStatus()" class="bulk-btn bulk-btn-primary">
                Apply
            </button>
        </div>

        <div class="flex items-center gap-1.5 pl-3 border-l border-slate-200 shrink-0">
            <button type="button" onclick="applyBulkShipments()" class="bulk-btn text-slate-700 hover:text-sky-700 hover:border-sky-300" title="Print Shipping Labels">
                <i class="fas fa-barcode text-sky-600 text-xs mr-0.5"></i> Labels
            </button>
            <button type="button" onclick="applyBulkDelete()" class="bulk-btn bulk-btn-danger" title="Delete Selected">
                <i class="fas fa-trash-alt text-xs"></i>
            </button>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="admin-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">
                            <input type="checkbox" id="select-all" class="rounded border-slate-300 text-primary focus:ring-primary cursor-pointer">
                        </th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th class="text-right">Total</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                    <i class="fas fa-inbox text-lg"></i>
                                </div>
                                <p class="text-sm font-semibold text-slate-700">No orders found</p>
                                <p class="text-xs text-slate-400 mt-0.5">Try adjusting your search criteria or status filter.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors group">
                                <td class="text-center">
                                    <input type="checkbox" class="order-checkbox rounded border-slate-300 text-primary focus:ring-primary cursor-pointer" value="<?php echo $o['id']; ?>">
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-xs font-semibold text-slate-700 group-hover:border-primary/30 group-hover:bg-primary/5 transition-colors">
                                            #<?php echo $o['id']; ?>
                                        </div>
                                        <div>
                                            <a href="../invoice.php?id=<?php echo urlencode($o['order_number']); ?>" target="_blank" class="font-semibold text-slate-900 hover:text-primary transition-colors text-xs block">
                                                <?php echo htmlspecialchars($o['order_number']); ?>
                                            </a>
                                            <span class="text-[11px] text-slate-400">
                                                <?php echo date('M d, Y · h:i A', strtotime($o['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-xs font-medium text-slate-900 mb-0.5">
                                        <?php echo htmlspecialchars($o['user_name'] ?: 'Guest Customer'); ?>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono text-[10px] uppercase border border-slate-200">
                                            <?php echo htmlspecialchars($o['payment_method'] ?? 'COD'); ?>
                                        </span>
                                        <?php if (!empty($o['razorpay_payment_id'])): ?>
                                            <span class="text-[10px] text-emerald-600 font-mono" title="Payment ID">
                                                ID: <?php echo substr($o['razorpay_payment_id'], -8); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-right font-semibold text-slate-900 text-xs">
                                    ₹<?php echo number_format($o['total'], 2); ?>
                                </td>
                                <td>
                                    <div class="relative inline-block status-dropdown-container">
                                        <button onclick="toggleStatusDropdown(this, event)" class="status-btn px-2.5 py-1 rounded-full text-[11px] font-semibold border flex items-center gap-1.5 transition-all <?php echo get_order_badge_style($o['order_status']); ?>">
                                            <span class="capitalize"><?php echo str_replace('_', ' ', $o['order_status']); ?></span>
                                            <i class="fas fa-chevron-down text-[9px] opacity-60 transition-transform"></i>
                                        </button>
                                        
                                        <div class="status-menu absolute left-0 top-full mt-1.5 w-44 bg-white rounded-xl shadow-xl border border-slate-200 py-1 hidden z-30">
                                            <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'pending', this)" class="block px-3.5 py-1.5 text-xs text-amber-700 hover:bg-amber-50">Pending</a>
                                            <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'confirmed', this)" class="block px-3.5 py-1.5 text-xs text-indigo-700 hover:bg-indigo-50">Confirm</a>
                                            <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'shipped', this)" class="block px-3.5 py-1.5 text-xs text-sky-700 hover:bg-sky-50">Ship</a>
                                            <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'delivered', this)" class="block px-3.5 py-1.5 text-xs text-emerald-700 hover:bg-emerald-50">Deliver</a>
                                            <div class="border-t border-slate-100 my-1"></div>
                                            <a href="javascript:void(0)" onclick="openDispatchModal(<?php echo $o['id']; ?>, '<?php echo $o['order_number']; ?>')" class="block px-3.5 py-1.5 text-xs font-semibold text-sky-600 hover:bg-sky-50">
                                                <i class="fas fa-shipping-fast mr-1"></i> Send / Dispatch
                                            </a>
                                            <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'cancelled', this)" class="block px-3.5 py-1.5 text-xs text-rose-700 hover:bg-rose-50">Cancel</a>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="../invoice.php?id=<?php echo urlencode($o['order_number']); ?>" target="_blank" title="Invoice" class="p-1.5 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100 transition-colors">
                                            <i class="fas fa-file-invoice text-xs"></i>
                                        </a>
                                        <a href="generate_label.php?id=<?php echo urlencode($o['order_number']); ?>" target="_blank" title="Shipping Label" class="p-1.5 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100 transition-colors">
                                            <i class="fas fa-barcode text-xs"></i>
                                        </a>
                                        <a href="../track.php?id=<?php echo urlencode($o['order_number']); ?>" target="_blank" title="Track Order" class="p-1.5 text-slate-400 hover:text-sky-600 rounded hover:bg-sky-50 transition-colors">
                                            <i class="fas fa-location-arrow text-xs"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
</div>

<!-- Dispatch Modal -->
<div id="dispatchModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-[200] p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
            <div>
                <h3 class="text-base font-bold text-slate-900">Dispatch Order</h3>
                <p class="text-xs text-slate-500 mt-0.5" id="dispatch-order-number">ORD-000000</p>
            </div>
            <button type="button" onclick="closeDispatchModal()" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        
        <form action="orders.php" method="POST" id="dispatch-form" class="space-y-4">
            <input type="hidden" name="order_id" id="dispatch-order-id">
            <input type="hidden" name="dispatch_order" value="1">
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tracking Number (India Post)</label>
                <input type="text" name="tracking_number" required placeholder="e.g. EB123456789IN" class="admin-input text-xs font-mono">
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Dispatch Date</label>
                <input type="date" name="dispatch_date" id="dispatch-date" required value="<?php echo date('Y-m-d'); ?>" class="admin-input text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tracking Note (Visible to Customer)</label>
                <textarea name="tracking_note" id="dispatch-note" rows="3" placeholder="e.g. Your parcel has reached the sorting hub." class="admin-input text-xs resize-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeDispatchModal()" class="btn-admin btn-admin-secondary text-xs">Cancel</button>
                <button type="submit" id="dispatch-btn" class="btn-admin btn-admin-primary text-xs">
                    <i class="fas fa-shipping-fast"></i> Mark Shipped
                </button>
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
const allPagesNotice = document.getElementById('all-pages-notice');
const allPagesActive = document.getElementById('all-pages-active');

let isAllSelectedAcrossPages = (sessionStorage.getItem('driyum_all_pages_selected') === 'true');
const PAGE_SIZE = 15;
const TOTAL_RECORDS = <?php echo (int)$pagination['total_records']; ?>;
const FILTERS = {
    q: '<?php echo addslashes($search); ?>',
    status_filter: '<?php echo addslashes($status_filter); ?>',
    user_id: '<?php echo (int)$user_filter; ?>'
};

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
    const count = isAllSelectedAcrossPages ? TOTAL_RECORDS : tracked.size;
    const countCircle = document.getElementById('selected-count-circle');
    if(countCircle) countCircle.textContent = count;
    
    const onPage = orderCheckboxes.length;
    const onPageChecked = Array.from(orderCheckboxes).filter(cb => cb.checked).length;
    if(selectAll) selectAll.checked = (onPage > 0 && onPageChecked === onPage);

    if (count > 0) {
        bulkBar.classList.remove('hidden');
        bulkBar.classList.add('flex');
    } else {
        bulkBar.classList.add('hidden');
        bulkBar.classList.remove('flex');
    }

    if (tracked.size === PAGE_SIZE && TOTAL_RECORDS > PAGE_SIZE && !isAllSelectedAcrossPages) {
        if(allPagesNotice) allPagesNotice.classList.remove('hidden');
    } else {
        if(allPagesNotice) allPagesNotice.classList.add('hidden');
    }

    if (isAllSelectedAcrossPages) {
        if(allPagesActive) allPagesActive.classList.remove('hidden');
    } else {
        if(allPagesActive) allPagesActive.classList.add('hidden');
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
            isAllSelectedAcrossPages = false;
            sessionStorage.setItem('driyum_all_pages_selected', 'false');
        }
        syncTracked(tracked);
    });
});

// Init
(function initSelection() {
    const tracked = getTracked();
    orderCheckboxes.forEach(cb => {
        if (tracked.has(cb.value)) cb.checked = true;
    });
    updateBulkBar();
})();

async function applyBulkStatus() {
    const status = document.getElementById('bulk-status-select').value;
    if (!status) {
        if (typeof window.showAlert === 'function') await window.showAlert('Please select a status first.', { type: 'warning' });
        else alert('Please select a status first.');
        return;
    }
    
    const count = isAllSelectedAcrossPages ? TOTAL_RECORDS : getTracked().size;
    const ok = typeof window.showConfirm === 'function'
        ? await window.showConfirm(`Update status of ${count} order(s) to "${status}"?`, {
            title: 'Update Orders Status',
            type: 'warning',
            confirmText: 'Update Status'
        })
        : confirm(`Update status of ${count} order(s) to "${status}"?`);
    if(!ok) return;
    
    await performBulkAction('bulk_status', { status });
}

async function applyBulkDelete() {
    const count = isAllSelectedAcrossPages ? TOTAL_RECORDS : getTracked().size;
    const ok = typeof window.showConfirm === 'function'
        ? await window.showConfirm(`Permanently delete ${count} order(s)? This action cannot be reversed.`, {
            title: 'Delete Orders',
            type: 'danger',
            confirmText: 'Delete Forever'
        })
        : confirm(`Permanently delete ${count} order(s)? This action cannot be reversed.`);
    if(!ok) return;
    
    await performBulkAction('bulk_delete');
}

function applyBulkShipments() {
    const tracked = getTracked();
    if (tracked.size === 0 && !isAllSelectedAcrossPages) return alert('Select at least one order');
    const ids = Array.from(tracked).join(',');
    window.open(`generate_batch_shipments.php?ids=${ids}`, '_blank');
}

async function performBulkAction(action, extraParams = {}) {
    const tracked = getTracked();
    const ids = Array.from(tracked);
    const originalContent = bulkBar.innerHTML;
    
    bulkBar.innerHTML = `<div class="flex items-center gap-2 px-4 py-1 text-xs font-semibold text-slate-700"><i class="fas fa-spinner fa-spin text-[#004f42]"></i> Processing bulk update...</div>`;

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

        const response = await fetch('api/bulk_action.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            resetSelection();
            setTimeout(() => window.location.reload(), 600);
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
    
    el.setAttribute('data-processing', 'true');
    btn.disabled = true;
    const originalContent = btn.innerHTML;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin text-xs"></i>`;
    
    try {
        const formData = new FormData();
        formData.append('ajax_action', 'update_status');
        formData.append('id', id);
        formData.append('status', status);

        const response = await fetch('orders.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            btn.innerHTML = `<span class="capitalize">${data.label}</span> <i class="fas fa-chevron-down text-[9px] opacity-60"></i>`;
            
            // Clean styling classes
            btn.className = 'status-btn px-2.5 py-1 rounded-full text-[11px] font-semibold border flex items-center gap-1.5 transition-all';
            let style = 'bg-slate-50 text-slate-700 border-slate-200';
            if (status === 'pending') style = 'bg-amber-50 text-amber-700 border-amber-200';
            else if (status === 'confirmed') style = 'bg-indigo-50 text-indigo-700 border-indigo-200';
            else if (status === 'shipped') style = 'bg-sky-50 text-sky-700 border-sky-200';
            else if (status === 'delivered') style = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            else if (status === 'cancelled') style = 'bg-rose-50 text-rose-700 border-rose-200';
            btn.className += ' ' + style;
        } else {
            alert('Failed to update status: ' + (data.message || 'Server error'));
            btn.innerHTML = originalContent;
        }
    } catch (error) {
        console.error('AJAX Error:', error);
        alert('An error occurred while updating status.');
        btn.innerHTML = originalContent;
    } finally {
        el.setAttribute('data-processing', 'false');
        btn.disabled = false;
        container.querySelector('.status-menu').classList.add('hidden');
    }
}

function openDispatchModal(id, number, tracking = '', note = '') {
    document.getElementById('dispatch-order-id').value = id;
    document.getElementById('dispatch-order-number').textContent = 'Order #' + number;
    
    const trackingInput = document.querySelector('input[name="tracking_number"]');
    const noteInput = document.getElementById('dispatch-note');
    const btn = document.getElementById('dispatch-btn');
    
    if(tracking) {
        trackingInput.value = tracking;
        noteInput.value = note;
        btn.innerHTML = '<i class="fas fa-shipping-fast"></i> Update Tracking';
    } else {
        trackingInput.value = number;
        noteInput.value = '';
        btn.innerHTML = '<i class="fas fa-shipping-fast"></i> Mark Shipped';
    }

    document.getElementById('dispatchModal').classList.remove('hidden');
    document.getElementById('dispatchModal').classList.add('flex');
}

function closeDispatchModal() {
    document.getElementById('dispatchModal').classList.add('hidden');
    document.getElementById('dispatchModal').classList.remove('flex');
}

// AJAX Dispatch Form
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

        const response = await fetch('orders.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            closeDispatchModal();
            setTimeout(() => window.location.reload(), 400);
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

function toggleStatusDropdown(btn, e) {
    e.stopPropagation();
    const container = btn.closest('.status-dropdown-container');
    const menu = container.querySelector('.status-menu');
    
    document.querySelectorAll('.status-menu').forEach(m => {
        if(m !== menu) m.classList.add('hidden');
    });

    menu.classList.toggle('hidden');
}

document.addEventListener('click', () => {
    document.querySelectorAll('.status-menu').forEach(m => m.classList.add('hidden'));
});

function exportOrders() {
    let url = 'export_orders.php';
    const params = new URLSearchParams();
    const tracked = Array.from(getTracked());
    
    if (isAllSelectedAcrossPages) {
        params.append('all', '1');
        params.append('q', FILTERS.q);
        params.append('status_filter', FILTERS.status_filter);
        params.append('user_id', FILTERS.user_id);
    } else if (tracked.length > 0) {
        params.append('ids', tracked.join(','));
    } else {
        params.append('all', '1');
        params.append('q', FILTERS.q);
        params.append('status_filter', FILTERS.status_filter);
        params.append('user_id', FILTERS.user_id);
    }
    
    window.location.href = url + '?' + params.toString();
}
</script>

<?php include 'includes/footer.php'; ?>
