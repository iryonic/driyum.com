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

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 fredoka tracking-tight">Snack Codes</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Managing <span class="text-black"><?php echo $pagination['total_records']; ?></span> high-impact discount vouchers</p>
    </div>
    <button onclick="openModal()" class="bg-[#19DC7E] text-black font-black px-6 py-2.5 rounded-2xl text-[10px] uppercase tracking-widest shadow-lg shadow-emerald-100 hover:scale-105 active:scale-95 transition-all text-center">
        <i class="fas fa-plus mr-1"></i> Create Coupon
    </button>
</div>

<?php if($success): ?>
    <div class="mb-8 p-6 bg-green-500 text-black rounded-[30px] shadow-lg shadow-green-500/20 font-black text-sm anim-up flex items-center gap-4">
        <div class="w-10 h-10 bg-black/10 rounded-full flex items-center justify-center"><i class="fas fa-check"></i></div>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <?php foreach ($coupons as $c): ?>
    <div class="coupon-card bg-white rounded-3xl p-6 shadow-sm border border-gray-100 relative group hover:shadow-xl transition-all duration-300 anim-up overflow-hidden">
        
        <div class="flex justify-between items-start mb-6">
            <div class="bg-gray-50 text-gray-900 px-4 py-1.5 rounded-xl font-black text-xs tracking-widest border border-gray-100 group-hover:bg-black group-hover:text-[#19DC7E] transition-colors"><?php echo $c['code']; ?></div>
            <div class="flex gap-1.5 opacity-0 group-hover:opacity-100 transition-all">
                <button onclick='editCoupon(<?php echo json_encode($c); ?>)' class="w-8 h-8 rounded-lg bg-gray-50 text-gray-400 hover:bg-black hover:text-white flex items-center justify-center transition-all"><i class="fas fa-pen text-[10px]"></i></button>
                <a href="?delete=<?php echo $c['id']; ?>" onclick="return confirm('Delete coupon?')" class="w-8 h-8 rounded-lg bg-red-50 text-red-400 hover:bg-red-500 hover:text-white flex items-center justify-center transition-all"><i class="fas fa-trash text-[10px]"></i></a>
            </div>
        </div>

        <div class="mb-6">
            <div class="text-[8px] font-black uppercase text-gray-400 tracking-widest mb-1">Discount Magnitude</div>
            <h3 class="text-3xl font-black fredoka text-gray-900 group-hover:text-[#19DC7E] transition-colors">
                <?php echo $c['type'] === 'percentage' ? $c['value'].'%' : '₹'.number_format($c['value']); ?> <span class="text-xs opacity-30">OFF</span>
            </h3>
        </div>
        
        <div class="p-4 bg-gray-50/50 rounded-2xl border border-gray-50 group-hover:bg-white group-hover:border-gray-100 transition-all mb-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Redemptions</p>
                    <p class="text-sm font-black text-gray-900"><?php echo $c['usage_count']; ?> <span class="text-[9px] text-gray-300">/ <?php echo $c['usage_limit'] ?: '∞'; ?></span></p>
                </div>
                <div class="text-right">
                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Expires</p>
                    <p class="text-sm font-black text-gray-900"><?php echo $c['expiry_date'] ? date('M d, Y', strtotime($c['expiry_date'])) : 'Never'; ?></p>
                </div>
            </div>
            <?php if($c['usage_limit']): ?>
            <div class="mt-3 h-1 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-[#19DC7E] transition-all duration-1000" style="width: <?php echo ($c['usage_count'] / $c['usage_limit']) * 100; ?>%"></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-between border-t border-gray-50 pt-4">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full <?php echo $c['is_active'] ? 'bg-[#19DC7E]' : 'bg-gray-200'; ?>"></div>
                <span class="text-[8px] font-black uppercase tracking-widest <?php echo $c['is_active'] ? 'text-gray-900' : 'text-gray-400'; ?>">
                    <?php echo $c['is_active'] ? 'Active' : 'Paused'; ?>
                </span>
            </div>
            <div class="text-[8px] font-black uppercase text-gray-300 font-['Outfit']">Min. Order: ₹<?php echo $c['min_order_value']; ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>

<!-- Modal -->
<div id="couponModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[200] hidden items-center justify-center p-6">
    <div class="bg-white rounded-[40px] w-full max-w-lg shadow-2xl relative overflow-hidden anim-up">
        
        <form method="POST" class="p-10 relative z-10">
            <input type="hidden" name="id" id="modal-id">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-12 bg-gray-50 text-gray-900 rounded-2xl flex items-center justify-center text-xl">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-gray-900 fredoka" id="modal-title">New Coupon</h2>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Craft a new discount code</p>
                </div>
            </div>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[9px] font-black uppercase text-gray-400 tracking-widest ml-4">Coupon Code</label>
                        <input type="text" name="code" id="modal-code" required placeholder="OFF10" class="w-full bg-gray-50 border-none rounded-xl px-5 py-3 text-sm font-bold outline-none focus:ring-1 focus:ring-black transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[9px] font-black uppercase text-gray-400 tracking-widest ml-4">Type</label>
                        <select name="type" id="modal-type" class="w-full bg-gray-50 border-none rounded-xl px-5 py-3 text-sm font-bold outline-none focus:ring-1 focus:ring-black transition-all appearance-none">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed (₹)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[9px] font-black uppercase text-gray-400 tracking-widest ml-4">Value</label>
                        <input type="number" name="value" id="modal-value" required placeholder="0" class="w-full bg-gray-50 border-none rounded-xl px-5 py-3 text-sm font-bold outline-none focus:ring-1 focus:ring-black transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[9px] font-black uppercase text-gray-400 tracking-widest ml-4">Min. Order</label>
                        <input type="number" name="min_order_value" id="modal-min-order" value="0" class="w-full bg-gray-50 border-none rounded-xl px-5 py-3 text-sm font-bold outline-none focus:ring-1 focus:ring-black transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[9px] font-black uppercase text-gray-400 tracking-widest ml-4">Usage Limit</label>
                        <input type="number" name="usage_limit" id="modal-limit" placeholder="No limit" class="w-full bg-gray-50 border-none rounded-xl px-5 py-3 text-sm font-bold outline-none focus:ring-1 focus:ring-black transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[9px] font-black uppercase text-gray-400 tracking-widest ml-4">Expiry Date</label>
                        <input type="date" name="expiry_date" id="modal-expiry" class="w-full bg-gray-50 border-none rounded-xl px-5 py-3 text-sm font-bold outline-none focus:ring-1 focus:ring-black transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[9px] font-black uppercase text-gray-400 tracking-widest ml-4">Max. Discount</label>
                        <input type="number" name="max_discount" id="modal-max-discount" placeholder="No cap" class="w-full bg-gray-50 border-none rounded-xl px-5 py-3 text-sm font-bold outline-none focus:ring-1 focus:ring-black transition-all">
                    </div>
                    <div class="flex items-center gap-3 pt-5 ml-4">
                        <input type="checkbox" name="is_active" id="modal-active" checked class="w-5 h-5 rounded-lg text-black border-gray-200 focus:ring-black">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-900 leading-none">Active</span>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-10">
                <button type="button" onclick="closeModal()" class="flex-1 py-4 rounded-2xl font-black text-gray-400 hover:text-black transition-colors uppercase tracking-widest text-[10px]">Back</button>
                <button type="submit" name="save_coupon" class="flex-1 bg-black text-[#19DC7E] py-4 rounded-2xl font-black shadow-lg uppercase tracking-widest text-[10px] hover:scale-[1.02] active:scale-95 transition-all">Save Code</button>
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
