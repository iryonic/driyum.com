<?php include 'includes/header.php'; ?>
<?php
$conn = get_db_connection();
$error = "";
$success = "";

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    execute_query("DELETE FROM coupons WHERE id = ?", [$id]);
    $success = "Coupon deleted successfully!";
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_coupon'])) {
    $code = sanitize_input($_POST['code']);
    $type = $_POST['type'];
    $value = (float)$_POST['value'];
    $min_order = (float)$_POST['min_order_value'];
    $limit = (int)$_POST['usage_limit'];
    $expiry = $_POST['expiry_date'] ? $_POST['expiry_date'] : null;
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $max_discount = (float)($_POST['max_discount'] ?? 0);

    if ($id) {
        $sql = "UPDATE coupons SET code=?, type=?, value=?, min_order_value=?, max_discount=?, usage_limit=?, expiry_date=?, is_active=? WHERE id=?";
        execute_query($sql, [$code, $type, $value, $min_order, $max_discount, $limit, $expiry, $is_active, $id]);
        $success = "Coupon updated successfully!";
    } else {
        $sql = "INSERT INTO coupons (code, type, value, min_order_value, max_discount, usage_limit, expiry_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        execute_query($sql, [$code, $type, $value, $min_order, $max_discount, $limit, $expiry, $is_active]);
        $success = "Coupon created successfully!";
    }
}

$query = "SELECT * FROM coupons ORDER BY created_at DESC";
$pagination = get_pagination_data($query, [], 9);
$coupons = $pagination['records'];
?>

<div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 anim-up">
    <div>
        <h1 class="text-4xl font-black text-gray-900 fredoka mb-2">Snack Codes.</h1>
        <p class="text-gray-500 font-medium font-['Outfit'] italic">Managing <span class="text-black font-bold"><?php echo $pagination['total_records']; ?></span> high-impact discount codes.</p>
    </div>
    <button onclick="openModal()" class="btn-chunky bg-[#19DC7E] text-black font-black px-10 py-4 rounded-2xl shadow-2xl hover:scale-105 transition border-none flex items-center gap-2">
        <i class="fas fa-plus-circle text-lg"></i> Create Coupon
    </button>
</div>

<?php if($success): ?>
    <div class="mb-8 p-6 bg-green-500 text-black rounded-[30px] shadow-lg shadow-green-500/20 font-black text-sm anim-up flex items-center gap-4">
        <div class="w-10 h-10 bg-black/10 rounded-full flex items-center justify-center"><i class="fas fa-check"></i></div>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
    <?php foreach ($coupons as $c): ?>
    <div class="coupon-card bg-white rounded-[40px] p-10 shadow-sm border-2 border-dashed border-gray-100 relative group hover:border-[#19DC7E] hover:shadow-2xl transition-all duration-500 anim-up overflow-hidden">
        
        <!-- Perforated Edge Decoration -->
        <div class="absolute -left-1.5 top-1/2 -translate-y-1/2 flex flex-col gap-1">
            <div class="w-3 h-3 bg-gray-50 rounded-full"></div>
            <div class="w-3 h-3 bg-gray-50 rounded-full"></div>
            <div class="w-3 h-3 bg-gray-50 rounded-full"></div>
        </div>
        <div class="absolute -right-1.5 top-1/2 -translate-y-1/2 flex flex-col gap-1">
            <div class="w-3 h-3 bg-gray-50 rounded-full"></div>
            <div class="w-3 h-3 bg-gray-50 rounded-full"></div>
            <div class="w-3 h-3 bg-gray-50 rounded-full"></div>
        </div>

        <div class="relative">
            <div class="flex justify-between items-start mb-8">
                <div class="bg-black text-[#19DC7E] px-6 py-3 rounded-2xl font-black text-lg tracking-[0.2em] shadow-2xl fredoka"><?php echo $c['code']; ?></div>
                <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-all translate-x-4 group-hover:translate-x-0">
                    <button onclick='editCoupon(<?php echo json_encode($c); ?>)' class="w-12 h-12 rounded-2xl bg-gray-50 text-gray-400 hover:bg-black hover:text-white flex items-center justify-center transition-all shadow-sm"><i class="fas fa-pen"></i></button>
                    <a href="?delete=<?php echo $c['id']; ?>" onclick="return confirm('Burn this coupon?')" class="w-12 h-12 rounded-2xl bg-red-50 text-red-500 hover:bg-red-600 hover:text-white flex items-center justify-center transition-all shadow-sm"><i class="fas fa-fire"></i></a>
                </div>
            </div>

            <div class="space-y-6 mb-10">
                <div>
                    <div class="text-[10px] font-black uppercase text-gray-300 tracking-[0.3em] mb-1">Discount Magnitude</div>
                    <h3 class="text-5xl font-black font-['Fredoka'] text-gray-900 group-hover:text-[#19DC7E] transition-colors">
                        <?php echo $c['type'] === 'percentage' ? $c['value'].'%' : '₹'.number_format($c['value']); ?> <span class="text-xl opacity-40">OFF</span>
                    </h3>
                </div>
                
                <div class="p-6 bg-gray-50 rounded-[30px] border border-gray-100 group-hover:bg-white group-hover:border-transparent transition-all">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1 font-['Outfit']">Usage Intensity</p>
                            <p class="text-lg font-black text-gray-900 font-['Outfit']"><?php echo $c['usage_count']; ?> <span class="text-xs opacity-40">/ <?php echo $c['usage_limit'] ?: '∞'; ?></span></p>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1 font-['Outfit']">Fresh Until</p>
                            <p class="text-lg font-black text-gray-900 font-['Outfit']"><?php echo $c['expiry_date'] ? date('M d', strtotime($c['expiry_date'])) : 'Infinity'; ?></p>
                        </div>
                    </div>
                    <!-- Utilization bar -->
                    <?php if($c['usage_limit']): ?>
                    <div class="mt-4 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-[#19DC7E] transition-all duration-1000" style="width: <?php echo ($c['usage_count'] / $c['usage_limit']) * 100; ?>%"></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-2.5 rounded-full <?php echo $c['is_active'] ? 'bg-[#19DC7E] shadow-[0_0_10px_#19DC7E]' : 'bg-gray-300'; ?>"></div>
                    <span class="text-[10px] font-black uppercase tracking-widest font-['Outfit'] <?php echo $c['is_active'] ? 'text-gray-900' : 'text-gray-400'; ?>">
                        <?php echo $c['is_active'] ? 'Accepting Orders' : 'Paused'; ?>
                    </span>
                </div>
                <div class="text-[10px] font-black uppercase text-gray-400 font-['Outfit']">Min. Order: ₹<?php echo $c['min_order_value']; ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>

<!-- Modal -->
<div id="couponModal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-[200] hidden items-center justify-center p-6">
    <div class="bg-white rounded-[50px] w-full max-w-xl shadow-[0_40px_100px_rgba(0,0,0,0.4)] relative overflow-hidden anim-up">
        <!-- Decoration -->
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-[#19DC7E]/10 rounded-full blur-3xl"></div>
        
        <form method="POST" class="p-12 relative z-10">
            <input type="hidden" name="id" id="modal-id">
            <div class="text-center mb-10">
                <div class="w-20 h-20 bg-[#19DC7E] text-black rounded-[30px] flex items-center justify-center mx-auto mb-6 shadow-2xl transform -rotate-12">
                    <i class="fas fa-ticket-alt text-3xl"></i>
                </div>
                <h2 class="text-4xl font-black text-gray-900 fredoka" id="modal-title">New Coupon</h2>
                <p class="text-gray-400 font-medium font-['Outfit'] mt-2">Craft a new discount experience for your fans.</p>
            </div>
            
            <div class="space-y-6">
                <!-- Code & Type -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-4">Identifier / Code</label>
                        <input type="text" name="code" id="modal-code" required placeholder="OFF20" class="w-full bg-gray-50 border-3 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[24px] px-8 py-5 outline-none transition-all font-black text-xl fredoka shadow-inner">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-4">Magnitude Type</label>
                        <select name="type" id="modal-type" class="w-full bg-gray-50 border-3 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[24px] px-8 py-5 outline-none transition-all font-black text-xl appearance-none shadow-inner">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed (₹)</option>
                        </select>
                    </div>
                </div>

                <!-- Values -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-4">Discount Value</label>
                        <input type="number" name="value" id="modal-value" required placeholder="0" class="w-full bg-gray-50 border-3 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[24px] px-8 py-5 outline-none transition-all font-black text-xl shadow-inner">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-4">Min. Order Threshold</label>
                        <input type="number" name="min_order_value" id="modal-min-order" value="0" class="w-full bg-gray-50 border-3 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[24px] px-8 py-5 outline-none transition-all font-black text-xl shadow-inner">
                    </div>
                </div>

                <!-- Limits -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-4">Usage Capacity</label>
                        <input type="number" name="usage_limit" id="modal-limit" placeholder="No limit" class="w-full bg-gray-50 border-3 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[24px] px-8 py-5 outline-none transition-all font-black text-xl shadow-inner">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-4">Retirement Date</label>
                        <input type="date" name="expiry_date" id="modal-expiry" class="w-full bg-gray-50 border-3 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[24px] px-8 py-5 outline-none transition-all font-black text-xl shadow-inner">
                    </div>
                </div>

                <!-- Advanced -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-4">Max Discount (₹)</label>
                        <input type="number" name="max_discount" id="modal-max-discount" placeholder="No limit" class="w-full bg-gray-50 border-3 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[24px] px-8 py-5 outline-none transition-all font-black text-xl shadow-inner">
                    </div>
                    <div class="space-y-2 pt-8 ml-4">
                        <label class="flex items-center gap-4 cursor-pointer">
                            <input type="checkbox" name="is_active" id="modal-active" checked class="w-6 h-6 rounded-lg text-[#19DC7E] border-gray-200 focus:ring-[#19DC7E]">
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-900">Accepting Orders?</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 mt-12">
                <button type="button" onclick="closeModal()" class="w-full py-5 rounded-[24px] font-black text-gray-400 hover:text-black transition-colors uppercase tracking-widest text-xs">Dismiss</button>
                <button type="submit" name="save_coupon" class="btn-chunky bg-black text-[#19DC7E] w-full py-5 rounded-[24px] font-black shadow-2xl uppercase tracking-[0.2em] text-xs border-none">Propagate Code</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modal-title').textContent = "New Coupon";
        document.getElementById('modal-id').value = "";
        document.getElementById('modal-code').value = "";
        document.getElementById('modal-value').value = "";
        document.getElementById('modal-min-order').value = "0";
        document.getElementById('modal-limit').value = "";
        document.getElementById('modal-expiry').value = "";
        document.getElementById('couponModal').classList.replace('hidden', 'flex');
    }

    function closeModal() {
        document.getElementById('couponModal').classList.replace('flex', 'hidden');
    }

    function editCoupon(c) {
        document.getElementById('modal-title').textContent = "Edit Coupon";
        document.getElementById('modal-id').value = c.id;
        document.getElementById('modal-code').value = c.code;
        document.getElementById('modal-type').value = c.type;
        document.getElementById('modal-value').value = c.value;
        document.getElementById('modal-min-order').value = c.min_order_value;
        document.getElementById('modal-max-discount').value = c.max_discount || "";
        document.getElementById('modal-limit').value = c.usage_limit;
        document.getElementById('modal-active').checked = parseInt(c.is_active) === 1;
        document.getElementById('modal-expiry').value = c.expiry_date ? c.expiry_date.split(' ')[0] : "";
        document.getElementById('couponModal').classList.replace('hidden', 'flex');
    }
</script>
</body>
</html>
