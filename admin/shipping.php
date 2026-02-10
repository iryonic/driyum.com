<?php
require_once 'includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_method'])) {
        $carrier = $_POST['carrier_name'];
        $display = $_POST['display_name'];
        $charge_type = $_POST['charge_type'];
        
        execute_query("INSERT INTO shipping_methods (carrier_name, display_name, min_days, max_days, charge_type) VALUES (?, ?, 0, 0, ?)", 
            [$carrier, $display, $charge_type]);
        $success = "Shipping method added successfully!";
    }

    if (isset($_POST['update_method'])) {
        $id = $_POST['method_id'];
        $carrier = $_POST['carrier_name'];
        $display = $_POST['display_name'];
        $charge_type = $_POST['charge_type'];
        $status = isset($_POST['status']) ? 1 : 0;
        
        execute_query("UPDATE shipping_methods SET carrier_name=?, display_name=?, charge_type=?, status=? WHERE id=?", 
            [$carrier, $display, $charge_type, $status, $id]);
        $success = "Shipping method updated successfully!";
    }

    if (isset($_POST['add_zone'])) {
        $name = $_POST['zone_name'];
        $ranges = $_POST['pincode_ranges'];
        $min_days = $_POST['min_days'] ?? 0;
        $max_days = $_POST['max_days'] ?? 0;
        execute_query("INSERT INTO shipping_zones (zone_name, pincode_ranges, min_days, max_days) VALUES (?, ?, ?, ?)", [$name, $ranges, $min_days, $max_days]);
        $success = "Shipping zone added successfully!";
    }

    if (isset($_POST['update_zone'])) {
        $id = $_POST['zone_id'];
        $name = $_POST['zone_name'];
        $ranges = $_POST['pincode_ranges'];
        $min_days = $_POST['min_days'] ?? 0;
        $max_days = $_POST['max_days'] ?? 0;
        execute_query("UPDATE shipping_zones SET zone_name=?, pincode_ranges=?, min_days=?, max_days=? WHERE id=?", [$name, $ranges, $min_days, $max_days, $id]);
        $success = "Shipping zone updated successfully!";
    }

    if (isset($_POST['add_rate'])) {
        $method_id = $_POST['method_id'];
        $zone_id = $_POST['zone_id'];
        $min_w = $_POST['min_weight'];
        $max_w = $_POST['max_weight'];
        $charge = $_POST['charge'];
        execute_query("INSERT INTO shipping_rates (method_id, zone_id, min_weight, max_weight, charge) VALUES (?, ?, ?, ?, ?)", 
            [$method_id, $zone_id, $min_w, $max_w, $charge]);
        $success = "Shipping rate added successfully!";
    }
    
    if (isset($_POST['delete_rate'])) {
        $id = $_POST['rate_id'];
        execute_query("DELETE FROM shipping_rates WHERE id=?", [$id]);
        $success = "Shipping rate deleted!";
    }

    if (isset($_POST['delete_method'])) {
        $id = $_POST['method_id'];
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

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 fredoka tracking-tight">Shipping Desk</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Configure logistics, zones, and rates</p>
    </div>
</div>

<?php if (isset($success)): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-xl animate-bounce-subtle">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-3"></i>
        <span><?php echo $success; ?></span>
    </div>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="flex gap-2 mb-8 bg-gray-50/50 p-1.5 rounded-2xl w-fit anim-up border border-gray-100">
    <a href="?tab=methods" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all <?php echo $active_tab == 'methods' ? 'bg-black text-[#19DC7E] shadow-sm' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100'; ?>">
        Methods
    </a>
    <a href="?tab=zones" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all <?php echo $active_tab == 'zones' ? 'bg-black text-[#19DC7E] shadow-sm' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100'; ?>">
        Zones
    </a>
    <a href="?tab=rates" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all <?php echo $active_tab == 'rates' ? 'bg-black text-[#19DC7E] shadow-sm' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100'; ?>">
        Rates
    </a>
</div>

<?php if ($active_tab == 'methods'): ?>
    <!-- Shipping Methods Tab -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 anim-up">
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-black uppercase tracking-widest mb-6 py-1 border-l-4 border-[#19DC7E] pl-4">New Method</h3>
                <form action="?tab=methods" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Carrier Name</label>
                        <input type="text" name="carrier_name" required placeholder="e.g. India Post" class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-xs font-bold outline-none focus:ring-1 focus:ring-[#19DC7E] transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Display Name</label>
                        <input type="text" name="display_name" required placeholder="e.g. Speed Post" class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-xs font-bold outline-none focus:ring-1 focus:ring-[#19DC7E] transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Charge Type</label>
                        <select name="charge_type" class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-xs font-bold outline-none focus:ring-1 focus:ring-[#19DC7E] transition-all">
                            <option value="weight_based">Weight Based</option>
                            <option value="flat">Flat Rate</option>
                        </select>
                    </div>
                    <button type="submit" name="add_method" class="w-full bg-[#19DC7E] text-black font-black py-4 rounded-xl text-[10px] uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-emerald-50">
                        Create Method
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase">Carrier & Name</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase">Type</th>
                            
                            <th class="px-6 py-4 text-right text-xs font-black text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($methods as $m): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-bold"><?php echo $m['display_name']; ?></div>
                                <div class="text-xs text-gray-500 uppercase"><?php echo $m['carrier_name']; ?></div>
                            </td>
                            <td class="px-6 py-4 text-left">
                                <span class="text-sm font-medium capitalize"><?php echo str_replace('_', ' ', $m['charge_type']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if($m['status']): ?>
                                    <span class="w-2 h-2 rounded-full bg-green-500 inline-block mr-1"></span> Enabled
                                <?php else: ?>
                                    <span class="w-2 h-2 rounded-full bg-red-500 inline-block mr-1"></span> Disabled
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button onclick="editMethod(<?php echo htmlspecialchars(json_encode($m)); ?>)" class="text-blue-500 hover:text-blue-700 p-2">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="?tab=methods" method="POST" onsubmit="return confirm('Delete this method and all its weight rates?')">
                                        <input type="hidden" name="method_id" value="<?php echo $m['id']; ?>">
                                        <button type="submit" name="delete_method" class="text-red-500 hover:text-red-700 p-2">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($active_tab == 'zones'): ?>
    <!-- Zones Tab -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-sm">
                        <i class="fas fa-globe-asia"></i>
                    </div>
                    Add New Zone
                </h3>
                <form action="?tab=zones" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Zone Name (e.g. Local)</label>
                        <input type="text" name="zone_name" required placeholder="e.g. Kashmiri Region" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-[#19DC7E] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Pincode Ranges</label>
                        <textarea name="pincode_ranges" rows="3" placeholder="190001-190020, 201001-201010" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-[#19DC7E] focus:outline-none"></textarea>
                        <p class="text-xs text-gray-400 mt-2 italic font-medium">Format: 110001-110010, 110015 (Comma separated)</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Min Days</label>
                            <input type="number" name="min_days" required value="0" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-[#19DC7E] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Max Days</label>
                            <input type="number" name="max_days" required value="0" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-[#19DC7E] focus:outline-none">
                        </div>
                    </div>
                    <button type="submit" name="add_zone" class="w-full bg-black text-white font-bold py-4 rounded-xl hover:bg-gray-800 transition shadow-lg">
                        Create Zone
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase">Zone Name</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase">Duration</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase">Pincode Ranges</th>
                            <th class="px-6 py-4 text-right text-xs font-black text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($zones as $z): ?>
                        <tr>
                            <td class="px-6 py-4 font-bold text-gray-900"><?php echo $z['zone_name']; ?></td>
                            <td class="px-6 py-4">
                                <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider">
                                    <?php echo $z['min_days']; ?>-<?php echo $z['max_days']; ?> Days
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-[200px] truncate text-xs text-gray-400 font-medium" title="<?php echo $z['pincode_ranges']; ?>">
                                    <?php echo $z['pincode_ranges']; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button onclick="editZone(<?php echo htmlspecialchars(json_encode($z)); ?>)" class="text-blue-500 hover:text-blue-700 p-2">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($active_tab == 'rates'): ?>
    <!-- Rates Tab -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-sm">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    Add Weight Rate
                </h3>
                <form action="?tab=rates" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Shipping Method</label>
                        <select name="method_id" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-[#19DC7E] focus:outline-none">
                            <?php foreach($methods as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo $m['display_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Zone</label>
                        <select name="zone_id" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-[#19DC7E] focus:outline-none">
                            <?php foreach($zones as $z): ?>
                                <option value="<?php echo $z['id']; ?>"><?php echo $z['zone_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Min Weight (kg)</label>
                            <input type="number" step="0.001" name="min_weight" required value="0.000" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-[#19DC7E] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Max Weight (kg)</label>
                            <input type="number" step="0.001" name="max_weight" required value="0.500" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-[#19DC7E] focus:outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Charge (₹)</label>
                        <input type="number" step="0.01" name="charge" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-[#19DC7E] focus:outline-none">
                    </div>
                    <button type="submit" name="add_rate" class="w-full bg-black text-white font-bold py-4 rounded-xl hover:bg-gray-800 transition shadow-lg">
                        Add Rate
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase">Method</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase">Zone</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase">Weight Range</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase">Charge</th>
                            <th class="px-6 py-4 text-right text-xs font-black text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($rates as $r): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium"><?php echo $r['method_name']; ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo $r['zone_name']; ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo $r['min_weight']*1000; ?>g - <?php echo $r['max_weight']*1000; ?>g</td>
                            <td class="px-6 py-4 font-bold text-[#19DC7E]">₹<?php echo number_format($r['charge'], 2); ?></td>
                            <td class="px-6 py-4 text-right">
                                <form action="?tab=rates" method="POST" onsubmit="return confirm('Delete this rate?')">
                                    <input type="hidden" name="rate_id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" name="delete_rate" class="text-red-500 hover:text-red-700 p-2">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php echo render_pagination($pagination_rates['total_pages'], $pagination_rates['current_page']); ?>
        </div>
    </div>
<?php endif; ?>

<!-- Modals for editing -->
<div id="methodModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[200]">
    <div class="bg-white p-8 rounded-3xl w-full max-w-md shadow-2xl">
        <h3 class="text-2xl font-bold mb-6">Edit Method</h3>
        <form action="?tab=methods" method="POST" class="space-y-4">
            <input type="hidden" name="method_id" id="edit_method_id">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Carrier Name</label>
                <input type="text" name="carrier_name" id="edit_carrier_name" required class="w-full px-4 py-3 rounded-xl border border-gray-200">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Display Name</label>
                <input type="text" name="display_name" id="edit_display_name" required class="w-full px-4 py-3 rounded-xl border border-gray-200">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Charge Type</label>
                <select name="charge_type" id="edit_charge_type" class="w-full px-4 py-3 rounded-xl border border-gray-200">
                    <option value="weight_based">Weight Based</option>
                    <option value="flat">Flat Rate</option>
                </select>
            </div>
            <div class="flex items-center gap-2 py-2">
                <input type="checkbox" name="status" id="edit_status" class="w-5 h-5 accent-[#19DC7E]">
                <label class="font-bold text-sm">Status Enabled</label>
            </div>
            <div class="flex gap-4 mt-6">
                <button type="button" onclick="closeModal('methodModal')" class="flex-1 bg-gray-100 py-3 rounded-xl font-bold">Cancel</button>
                <button type="submit" name="update_method" class="flex-1 bg-black text-white py-3 rounded-xl font-bold">Update</button>
            </div>
        </form>
    </div>
</div>

<div id="zoneModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[200]">
    <div class="bg-white p-8 rounded-3xl w-full max-w-md shadow-2xl">
        <h3 class="text-2xl font-bold mb-6">Edit Zone</h3>
        <form action="?tab=zones" method="POST" class="space-y-4">
            <input type="hidden" name="zone_id" id="edit_zone_id">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Zone Name</label>
                <input type="text" name="zone_name" id="edit_zone_name" required class="w-full px-4 py-3 rounded-xl border border-gray-200">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Pincode Ranges</label>
                <textarea name="pincode_ranges" id="edit_pincode_ranges" rows="4" class="w-full px-4 py-3 rounded-xl border border-gray-200"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Min Days</label>
                    <input type="number" name="min_days" id="edit_zone_min_days" required class="w-full px-4 py-3 rounded-xl border border-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Max Days</label>
                    <input type="number" name="max_days" id="edit_zone_max_days" required class="w-full px-4 py-3 rounded-xl border border-gray-200">
                </div>
            </div>
            <div class="flex gap-4 mt-6">
                <button type="button" onclick="closeModal('zoneModal')" class="flex-1 bg-gray-100 py-3 rounded-xl font-bold">Cancel</button>
                <button type="submit" name="update_zone" class="flex-1 bg-black text-white py-3 rounded-xl font-bold">Update</button>
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
    document.getElementById('edit_status').checked = data.status == 1;
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
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}
</script>


</body>
</html>
