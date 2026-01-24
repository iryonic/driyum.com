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

<div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 anim-up">
    <div>
        <h1 class="text-4xl font-black text-gray-900 fredoka mb-2">Order Desk.</h1>
        <div class="flex items-center gap-4">
            <p class="text-gray-500 font-medium font-['Outfit'] italic">The logistics nerve-center of Driyum.</p>
            <div class="h-4 w-px bg-gray-200"></div>
            <span class="text-[10px] font-black uppercase tracking-widest text-[#19DC7E] bg-[#19DC7E]/10 px-3 py-1 rounded-full"><?php echo $pagination['total_records']; ?> Total Shipments</span>
        </div>
    </div>
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 w-full md:w-auto">
        <form class="flex flex-col sm:flex-row gap-4 w-full">
            <div class="relative group flex-1">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search orders, customers..." 
                    class="bg-white border-2 border-gray-100 rounded-2xl px-6 py-4 pl-12 outline-none focus:border-[#19DC7E] transition-all font-bold text-xs w-full shadow-sm">
                <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-gray-300"></i>
            </div>
            <select name="status_filter" onchange="this.form.submit()" class="bg-white border-2 border-gray-100 rounded-2xl px-6 py-4 outline-none focus:border-[#19DC7E] transition-all font-bold text-xs shadow-sm">
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

<div class="bg-white rounded-[45px] shadow-2xl border border-gray-100 overflow-hidden anim-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-gray-400 text-[10px] uppercase bg-gray-50/30 border-b border-gray-100 font-['Outfit']">
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Shipment Details</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Recipient Info</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40 text-center">Revenue</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Order Status</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40 text-right">Utility</th>
                </tr>
            </thead>
            <tbody class="text-sm font-['Outfit'] text-gray-600">
                <?php foreach ($orders as $o): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-all duration-300 group">
                    <td class="p-10">
                        <div class="flex items-center gap-5">
                            <div class="w-16 h-16 bg-white border-2 border-dashed border-gray-100 rounded-[24px] flex flex-col items-center justify-center text-gray-300 group-hover:border-[#19DC7E] group-hover:text-[#19DC7E] transition-colors shadow-sm">
                                <span class="text-[10px] font-black uppercase">ID</span>
                                <span class="font-black text-lg leading-none">#<?php echo $o['id']; ?></span>
                            </div>
                            <div>
                                <span class="font-black text-xl text-gray-900 block mb-1"><?php echo $o['order_number']; ?></span>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest bg-gray-100 px-2 py-1 rounded-md"><?php echo date('M d, Y • h:i A', strtotime($o['created_at'])); ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="p-10">
                        <div class="font-black text-lg text-gray-800 mb-1"><?php echo $o['user_name'] ?? 'Guest Customer'; ?></div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest border border-gray-200 px-2 py-0.5 rounded-md"><?php echo $o['payment_method']; ?></span>
                            <?php if(!empty($o['razorpay_payment_id'])): ?>
                                <span class="text-[8px] font-bold text-[#19DC7E] bg-[#19DC7E]/10 px-2 py-0.5 rounded-md ml-1">ID: <?php echo $o['razorpay_payment_id']; ?></span>
                            <?php endif; ?>
                            <?php if($o['payment_method'] == 'COD'): ?>
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="p-10 text-center">
                        <div class="inline-block px-5 py-3 bg-gray-50 rounded-2xl border border-gray-100 font-black text-gray-900 text-xl group-hover:bg-[#19DC7E] group-hover:text-black group-hover:border-transparent transition-all">
                            ₹<?php echo number_format($o['total'], 0); ?>
                        </div>
                    </td>
                    <td class="p-10">
                        <div class="relative inline-block status-dropdown-container">
                            <button onclick="toggleStatusDropdown(this, event)" class="status-btn px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-[0.15em] flex items-center gap-3 shadow-sm transition-all hover:scale-105 active:scale-95
                                <?php echo get_status_color($o['order_status']); ?>
                            ">
                                <?php echo str_replace('_', ' ', $o['order_status']); ?> 
                                <i class="fas fa-chevron-down opacity-50 text-[10px] transition-transform duration-300"></i>
                            </button>
                            
                            <!-- Dropdown -->
                            <div class="status-menu absolute left-0 top-full mt-4 w-56 bg-white rounded-[32px] shadow-[0_20px_60px_rgba(0,0,0,0.15)] border border-gray-100 py-4 hidden z-50 overflow-hidden transform origin-top-left transition-all">
                                <div class="px-6 py-2 mb-2 text-[10px] font-black text-gray-300 uppercase tracking-widest border-b border-gray-50">Workflow Status</div>
                                <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'pending', this)" class="block px-6 py-3 hover:bg-yellow-400 hover:text-black text-yellow-600 font-black text-[10px] uppercase tracking-widest transition-colors">Mark Pending</a>
                                <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'confirmed', this)" class="block px-6 py-3 hover:bg-indigo-600 hover:text-white text-indigo-600 font-black text-[10px] uppercase tracking-widest transition-colors">Confirm Order</a>
                                <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'shipped', this)" class="block px-6 py-3 hover:bg-blue-600 hover:text-white text-blue-600 font-black text-[10px] uppercase tracking-widest transition-colors">Ship Parcel</a>
                                <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'delivered', this)" class="block px-6 py-3 hover:bg-[#19DC7E] hover:text-black text-green-600 font-black text-[10px] uppercase tracking-widest transition-colors">Deliver Item</a>
                                <div class="border-t border-gray-50 my-2"></div>
                                <a href="javascript:void(0)" onclick="openDispatchModal(<?php echo $o['id']; ?>, '<?php echo $o['order_number']; ?>')" class="block px-6 py-3 bg-blue-50 hover:bg-blue-600 hover:text-white text-blue-600 font-black text-[10px] uppercase tracking-widest transition-colors">🚀 Dispatch Parcel</a>
                                <div class="border-t border-gray-50 my-2"></div>
                                <a href="javascript:void(0)" onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'cancelled', this)" class="block px-6 py-3 hover:bg-red-600 hover:text-white text-red-600 font-black text-[10px] uppercase tracking-widest transition-colors">Void / Cancel</a>
                                <div class="border-t border-gray-50 my-2"></div>
                                <a href="javascript:void(0)" onclick="openDispatchModal(<?php echo $o['id']; ?>, '<?php echo $o['order_number']; ?>', '<?php echo addslashes($o['tracking_number']); ?>', '<?php echo addslashes($o['tracking_note']); ?>')" class="block px-6 py-3 bg-[#19DC7E]/10 hover:bg-[#19DC7E] hover:text-black text-[#19DC7E] font-black text-[10px] uppercase tracking-widest transition-colors">📝 Update Tracking Info</a>
                            </div>
                        </div>
                    </td>
                    <td class="p-10 text-right">
                        <div class="flex justify-end gap-3 translate-x-4 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-500">
                            <a href="../invoice.php?id=<?php echo $o['order_number']; ?>" target="_blank" title="Invoice" class="w-14 h-14 bg-gray-50 text-gray-400 hover:bg-black hover:text-white flex items-center justify-center rounded-[20px] transition-all shadow-sm active:scale-95">
                                <i class="fas fa-file-invoice-dollar text-lg"></i>
                            </a>
                            <a href="generate_label.php?id=<?php echo $o['order_number']; ?>" target="_blank" title="Thermal Label" class="w-14 h-14 bg-gray-50 text-gray-400 hover:bg-[#19DC7E] hover:text-black flex items-center justify-center rounded-[20px] transition-all shadow-sm active:scale-95">
                                <i class="fas fa-barcode text-lg"></i>
                            </a>
                            <a href="../track.php?id=<?php echo $o['order_number']; ?>" target="_blank" title="Track Live" class="w-14 h-14 bg-gray-50 text-gray-400 hover:bg-indigo-500 hover:text-white flex items-center justify-center rounded-[20px] transition-all shadow-sm active:scale-95">
                                <i class="fas fa-location-arrow text-lg"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($orders)): ?>
                <tr>
                    <td colspan="5" class="p-20 text-center">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-inbox text-gray-200 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-black text-gray-300 fredoka uppercase tracking-widest">No Shipments Found</h3>
                        <p class="text-gray-400 font-medium font-['Outfit'] mt-2">Orders will appear here once customers start buying snacks!</p>
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
                <h3 class="text-3xl font-black fredoka text-gray-900">Dispatch Order</h3>
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
async function updateOrderStatus(id, status, el) {
    const container = el.closest('.status-dropdown-container');
    const btn = container.querySelector('.status-btn');
    const icon = btn.querySelector('.fa-chevron-down');
    
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
            btn.classList.remove('bg-yellow-400', 'bg-indigo-600', 'bg-blue-600', 'bg-[#19DC7E]', 'bg-red-600', 'text-black', 'text-white');
            
            // Add new class
            let newClasses = [];
            switch(status) {
                case 'pending': newClasses = ['bg-yellow-400', 'text-black']; break;
                case 'confirmed': newClasses = ['bg-indigo-600', 'text-white']; break;
                case 'shipped': newClasses = ['bg-blue-600', 'text-white']; break;
                case 'delivered': newClasses = ['bg-[#19DC7E]', 'text-black']; break;
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
    }
    
    // Close menu
    container.querySelector('.status-menu').classList.add('hidden');
    icon.classList.remove('rotate-180');
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
                statusBtn.classList.remove('bg-yellow-400', 'bg-indigo-600', 'bg-blue-600', 'bg-[#19DC7E]', 'bg-red-600', 'text-black', 'text-white');
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
</body>
</html>
