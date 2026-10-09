<?php
require_once 'includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_method'])) {
        $carrier = sanitize_input($_POST['carrier_name']);
        $display = sanitize_input($_POST['display_name']);
        $charge_type = sanitize_input($_POST['charge_type']);
        
        execute_query("INSERT INTO shipping_methods (carrier_name, display_name, min_days, max_days, charge_type) VALUES (?, ?, 0, 0, ?)", 
            [$carrier, $display, $charge_type]);
        $success = "Shipping method added successfully!";
    }

    if (isset($_POST['update_method'])) {
        $id = (int)$_POST['method_id'];
        $carrier = sanitize_input($_POST['carrier_name']);
        $display = sanitize_input($_POST['display_name']);
        $charge_type = sanitize_input($_POST['charge_type']);
        $status = isset($_POST['status']) ? 1 : 0;
        
        execute_query("UPDATE shipping_methods SET carrier_name=?, display_name=?, charge_type=?, status=? WHERE id=?", 
            [$carrier, $display, $charge_type, $status, $id]);
        $success = "Shipping method updated successfully!";
    }

    if (isset($_POST['add_zone'])) {
        $name = sanitize_input($_POST['zone_name']);
        $ranges = sanitize_input($_POST['pincode_ranges']);
        $min_days = (int)($_POST['min_days'] ?? 0);
        $max_days = (int)($_POST['max_days'] ?? 0);
        execute_query("INSERT INTO shipping_zones (zone_name, pincode_ranges, min_days, max_days) VALUES (?, ?, ?, ?)", [$name, $ranges, $min_days, $max_days]);
        $success = "Shipping zone added successfully!";
    }

    if (isset($_POST['update_zone'])) {
        $id = (int)$_POST['zone_id'];
        $name = sanitize_input($_POST['zone_name']);
        $ranges = sanitize_input($_POST['pincode_ranges']);
        $min_days = (int)($_POST['min_days'] ?? 0);
        $max_days = (int)($_POST['max_days'] ?? 0);
        execute_query("UPDATE shipping_zones SET zone_name=?, pincode_ranges=?, min_days=?, max_days=? WHERE id=?", [$name, $ranges, $min_days, $max_days, $id]);
        $success = "Shipping zone updated successfully!";
    }

    if (isset($_POST['add_rate'])) {
        $method_id = (int)$_POST['method_id'];
        $zone_id = (int)$_POST['zone_id'];
        $min_w = (float)$_POST['min_weight'];
        $max_w = (float)$_POST['max_weight'];
        $charge = (float)$_POST['charge'];
        execute_query("INSERT INTO shipping_rates (method_id, zone_id, min_weight, max_weight, charge) VALUES (?, ?, ?, ?, ?)", 
            [$method_id, $zone_id, $min_w, $max_w, $charge]);
        $success = "Shipping rate added successfully!";
    }
    
    if (isset($_POST['delete_rate'])) {
        $id = (int)$_POST['rate_id'];
        execute_query("DELETE FROM shipping_rates WHERE id=?", [$id]);
        $success = "Shipping rate deleted!";
    }

    if (isset($_POST['delete_method'])) {
        $id = (int)$_POST['method_id'];
        execute_query("DELETE FROM shipping_rates WHERE method_id=?", [$id]);
        execute_query("DELETE FROM shipping_methods WHERE id=?", [$id]);
        $success = "Shipping method deleted!";
    }
}

$methods = fetch_all("SELECT * FROM shipping_methods ORDER BY id DESC");
$zones = fetch_all("SELECT * FROM shipping_zones ORDER BY id DESC");

$query_rates = "SELECT r.*, m.display_name as method_name, z.zone_name 
                FROM shipping_rates r 
                JOIN shipping_methods m ON r.method_id = m.id 
                JOIN shipping_zones z ON r.zone_id = z.id 
                ORDER BY m.id, z.id, r.min_weight";

$pagination_rates = get_pagination_data($query_rates, [], 15);
$rates = $pagination_rates['records'];

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'methods';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Shipping & Logistics</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage dispatch carriers, delivery zones, pincode routing, and weight rate slabs.</p>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="p-3.5 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times text-xs"></i></button>
        </div>
    <?php endif; ?>

    <!-- Segmented Tabs -->
    <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl w-fit text-xs font-medium">
        <a href="?tab=methods" class="px-4 py-1.5 rounded-lg transition-all <?php echo $active_tab == 'methods' ? 'bg-white text-slate-900 font-semibold shadow-sm' : 'text-slate-600 hover:text-slate-900'; ?>">
            <i class="fas fa-truck mr-1.5 text-slate-500"></i> Shipping Methods
        </a>
        <a href="?tab=zones" class="px-4 py-1.5 rounded-lg transition-all <?php echo $active_tab == 'zones' ? 'bg-white text-slate-900 font-semibold shadow-sm' : 'text-slate-600 hover:text-slate-900'; ?>">
            <i class="fas fa-map-marked-alt mr-1.5 text-slate-500"></i> Delivery Zones
        </a>
        <a href="?tab=rates" class="px-4 py-1.5 rounded-lg transition-all <?php echo $active_tab == 'rates' ? 'bg-white text-slate-900 font-semibold shadow-sm' : 'text-slate-600 hover:text-slate-900'; ?>">
            <i class="fas fa-weight-hanging mr-1.5 text-slate-500"></i> Weight Rates
        </a>
    </div>

    <?php if ($active_tab == 'methods'): ?>
        <!-- Methods Tab -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Add Method Card -->
            <div class="lg:col-span-1">
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-primary"></i> Add Shipping Method
                    </h3>
                    <form action="?tab=methods" method="POST" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Carrier Name</label>
                            <input type="text" name="carrier_name" required placeholder="e.g. India Post / Delhivery" class="admin-input text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Customer Display Name</label>
                            <input type="text" name="display_name" required placeholder="e.g. Standard Express Delivery" class="admin-input text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Charge Type</label>
                            <select name="charge_type" class="admin-select text-xs">
                                <option value="weight_based">Weight Based</option>
                                <option value="flat">Flat Rate</option>
                            </select>
                        </div>
                        <button type="submit" name="add_method" class="w-full btn-admin btn-admin-primary text-xs py-2.5">
                            <i class="fas fa-plus"></i> Create Method
                        </button>
                    </form>
                </div>
            </div>

            <!-- Methods Table -->
            <div class="lg:col-span-2">
                <div class="admin-card p-0 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Carrier & Display Name</th>
                                    <th>Charge Type</th>
                                    <th>Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($methods)): ?>
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-slate-400 text-xs">No shipping methods configured yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($methods as $m): ?>
                                        <tr class="hover:bg-slate-50/70 transition-colors">
                                            <td>
                                                <div class="font-semibold text-slate-900 text-xs"><?php echo htmlspecialchars($m['display_name']); ?></div>
                                                <div class="text-[11px] text-slate-400 uppercase font-mono mt-0.5"><?php echo htmlspecialchars($m['carrier_name']); ?></div>
                                            </td>
                                            <td>
                                                <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 capitalize border border-slate-200">
                                                    <?php echo str_replace('_', ' ', $m['charge_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($m['status']): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                        Disabled
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <button onclick="editMethod(<?php echo htmlspecialchars(json_encode($m)); ?>)" class="p-1.5 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100 transition-colors" title="Edit Method">
                                                        <i class="fas fa-pen text-xs"></i>
                                                    </button>
                                                    <form action="?tab=methods" method="POST" onsubmit="return confirm('Delete this method and all its weight rates?')" class="inline-block">
                                                        <input type="hidden" name="method_id" value="<?php echo $m['id']; ?>">
                                                        <button type="submit" name="delete_method" class="p-1.5 text-slate-400 hover:text-rose-600 rounded hover:bg-rose-50 transition-colors" title="Delete Method">
                                                            <i class="fas fa-trash-alt text-xs"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($active_tab == 'zones'): ?>
        <!-- Zones Tab -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Add Zone Card -->
            <div class="lg:col-span-1">
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-map-pin text-primary"></i> Add Delivery Zone
                    </h3>
                    <form action="?tab=zones" method="POST" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Zone Name</label>
                            <input type="text" name="zone_name" required placeholder="e.g. Metro Cities / Rest of India" class="admin-input text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pincode Ranges</label>
                            <textarea name="pincode_ranges" rows="3" placeholder="e.g. 110001-110099, 400001-400099, 190001" class="admin-input text-xs resize-none"></textarea>
                            <p class="text-[10px] text-slate-400 mt-1">Comma-separated ranges or single pincodes.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Min Days</label>
                                <input type="number" name="min_days" required value="2" class="admin-input text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Max Days</label>
                                <input type="number" name="max_days" required value="5" class="admin-input text-xs">
                            </div>
                        </div>
                        <button type="submit" name="add_zone" class="w-full btn-admin btn-admin-primary text-xs py-2.5">
                            <i class="fas fa-plus"></i> Create Zone
                        </button>
                    </form>
                </div>
            </div>

            <!-- Zones Table -->
            <div class="lg:col-span-2">
                <div class="admin-card p-0 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Zone Name</th>
                                    <th>Estimated Transit</th>
                                    <th>Pincode Coverage</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($zones)): ?>
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-slate-400 text-xs">No delivery zones registered yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($zones as $z): ?>
                                        <tr class="hover:bg-slate-50/70 transition-colors">
                                            <td class="font-semibold text-slate-900 text-xs"><?php echo htmlspecialchars($z['zone_name']); ?></td>
                                            <td>
                                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                                    <?php echo $z['min_days']; ?>–<?php echo $z['max_days']; ?> Days
                                                </span>
                                            </td>
                                            <td class="max-w-[260px]">
                                                <div class="text-xs text-slate-600 truncate font-mono" title="<?php echo htmlspecialchars($z['pincode_ranges']); ?>">
                                                    <?php echo htmlspecialchars($z['pincode_ranges'] ?: 'All pincodes (Default)'); ?>
                                                </div>
                                            </td>
                                            <td class="text-right">
                                                <button onclick="editZone(<?php echo htmlspecialchars(json_encode($z)); ?>)" class="p-1.5 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100 transition-colors" title="Edit Zone">
                                                    <i class="fas fa-pen text-xs"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($active_tab == 'rates'): ?>
        <!-- Rates Tab -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Add Rate Card -->
            <div class="lg:col-span-1">
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-balance-scale text-primary"></i> Add Weight Rate
                    </h3>
                    <form action="?tab=rates" method="POST" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Shipping Method</label>
                            <select name="method_id" required class="admin-select text-xs">
                                <?php foreach ($methods as $m): ?>
                                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['display_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Destination Zone</label>
                            <select name="zone_id" required class="admin-select text-xs">
                                <?php foreach ($zones as $z): ?>
                                    <option value="<?php echo $z['id']; ?>"><?php echo htmlspecialchars($z['zone_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Min Weight (kg)</label>
                                <input type="number" step="0.001" name="min_weight" required value="0.000" class="admin-input text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Max Weight (kg)</label>
                                <input type="number" step="0.001" name="max_weight" required value="0.500" class="admin-input text-xs">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Rate Charge (₹)</label>
                            <input type="number" step="0.01" name="charge" required placeholder="e.g. 50.00" class="admin-input text-xs">
                        </div>
                        <button type="submit" name="add_rate" class="w-full btn-admin btn-admin-primary text-xs py-2.5">
                            <i class="fas fa-plus"></i> Save Rate Slabs
                        </button>
                    </form>
                </div>
            </div>

            <!-- Rates Table -->
            <div class="lg:col-span-2 space-y-4">
                <div class="admin-card p-0 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>Zone</th>
                                    <th>Weight Bracket</th>
                                    <th>Charge</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rates)): ?>
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-slate-400 text-xs">No rates established yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rates as $r): ?>
                                        <tr class="hover:bg-slate-50/70 transition-colors">
                                            <td class="font-semibold text-slate-900 text-xs"><?php echo htmlspecialchars($r['method_name']); ?></td>
                                            <td class="text-xs text-slate-600"><?php echo htmlspecialchars($r['zone_name']); ?></td>
                                            <td class="text-xs text-slate-600 font-mono">
                                                <?php echo ($r['min_weight'] * 1000); ?>g – <?php echo ($r['max_weight'] * 1000); ?>g
                                            </td>
                                            <td class="font-semibold text-slate-900 text-xs">₹<?php echo number_format($r['charge'], 2); ?></td>
                                            <td class="text-right">
                                                <form action="?tab=rates" method="POST" onsubmit="return confirm('Delete this rate slab?')" class="inline-block">
                                                    <input type="hidden" name="rate_id" value="<?php echo $r['id']; ?>">
                                                    <button type="submit" name="delete_rate" class="p-1.5 text-slate-400 hover:text-rose-600 rounded hover:bg-rose-50 transition-colors" title="Delete Rate">
                                                        <i class="fas fa-trash-alt text-xs"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php echo render_pagination($pagination_rates['total_pages'], $pagination_rates['current_page']); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Edit Method Modal -->
<div id="methodModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-[200] p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Edit Shipping Method</h3>
            <button type="button" onclick="closeModal('methodModal')" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <form action="?tab=methods" method="POST" class="p-5 space-y-4">
            <input type="hidden" name="method_id" id="edit_method_id">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Carrier Name</label>
                <input type="text" name="carrier_name" id="edit_carrier_name" required class="admin-input text-xs">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Display Name</label>
                <input type="text" name="display_name" id="edit_display_name" required class="admin-input text-xs">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Charge Type</label>
                <select name="charge_type" id="edit_charge_type" class="admin-select text-xs">
                    <option value="weight_based">Weight Based</option>
                    <option value="flat">Flat Rate</option>
                </select>
            </div>
            <div class="pt-2">
                <label class="relative flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="status" id="edit_status" class="rounded border-slate-300 text-primary focus:ring-primary">
                    <span class="text-xs font-semibold text-slate-700">Method Enabled</span>
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('methodModal')" class="btn-admin btn-admin-secondary text-xs">Cancel</button>
                <button type="submit" name="update_method" class="btn-admin btn-admin-primary text-xs">Update Method</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Zone Modal -->
<div id="zoneModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-[200] p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Edit Delivery Zone</h3>
            <button type="button" onclick="closeModal('zoneModal')" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <form action="?tab=zones" method="POST" class="p-5 space-y-4">
            <input type="hidden" name="zone_id" id="edit_zone_id">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Zone Name</label>
                <input type="text" name="zone_name" id="edit_zone_name" required class="admin-input text-xs">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pincode Ranges</label>
                <textarea name="pincode_ranges" id="edit_pincode_ranges" rows="3" class="admin-input text-xs resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Min Days</label>
                    <input type="number" name="min_days" id="edit_zone_min_days" required class="admin-input text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Max Days</label>
                    <input type="number" name="max_days" id="edit_zone_max_days" required class="admin-input text-xs">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('zoneModal')" class="btn-admin btn-admin-secondary text-xs">Cancel</button>
                <button type="submit" name="update_zone" class="btn-admin btn-admin-primary text-xs">Update Zone</button>
            </div>
        </form>
    </div>
</div>

<script>
function editMethod(data) {
    document.getElementById('edit_method_id').value = data.id;
    document.getElementById('edit_carrier_name').value = data.carrier_name;
    document.getElementById('edit_display_name').value = data.display_name;
    document.getElementById('edit_charge_type').value = data.charge_type;
    document.getElementById('edit_status').checked = (parseInt(data.status) === 1);
    document.getElementById('methodModal').classList.remove('hidden');
    document.getElementById('methodModal').classList.add('flex');
}

function editZone(data) {
    document.getElementById('edit_zone_id').value = data.id;
    document.getElementById('edit_zone_name').value = data.zone_name;
    document.getElementById('edit_pincode_ranges').value = data.pincode_ranges;
    document.getElementById('edit_zone_min_days').value = data.min_days;
    document.getElementById('edit_zone_max_days').value = data.max_days;
    document.getElementById('zoneModal').classList.remove('hidden');
    document.getElementById('zoneModal').classList.add('flex');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.remove('flex');
        el.classList.add('hidden');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
