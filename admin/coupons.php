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
    $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
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
$pagination = get_pagination_data($query, [], 12);
$coupons = $pagination['records'];
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Coupons & Discounts</h1>
            <p class="text-sm text-slate-500 mt-0.5">Configure promotional codes, percentage discounts, minimum spends, and redemptions.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openModal()" class="btn-admin btn-admin-primary text-xs">
                <i class="fas fa-plus"></i> Create Coupon
            </button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="p-3.5 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times text-xs"></i></button>
        </div>
    <?php endif; ?>

    <!-- Coupons Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php if (empty($coupons)): ?>
            <div class="col-span-full admin-card p-12 text-center text-slate-400">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                    <i class="fas fa-ticket-alt text-lg"></i>
                </div>
                <p class="text-sm font-semibold text-slate-700">No active coupons</p>
                <p class="text-xs text-slate-400 mt-0.5">Click "Create Coupon" above to launch a new discount campaign.</p>
            </div>
        <?php else: ?>
            <?php foreach ($coupons as $c): 
                $isPercentage = ($c['type'] === 'percentage');
                $isExpired = ($c['expiry_date'] && strtotime($c['expiry_date']) < time());
                $usagePct = ($c['usage_limit'] > 0) ? min(100, round(($c['usage_count'] / $c['usage_limit']) * 100)) : 0;
            ?>
                <div class="admin-card p-5 relative group flex flex-col justify-between hover:border-slate-300 transition-all <?php echo !$c['is_active'] ? 'opacity-75 bg-slate-50/50' : ''; ?>">
                    <!-- Card Header -->
                    <div>
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-md bg-slate-900 text-emerald-400 font-mono text-xs font-bold tracking-wider uppercase border border-slate-800">
                                    <?php echo htmlspecialchars($c['code']); ?>
                                </span>
                                <?php if ($c['is_active'] && !$isExpired): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                <?php elseif ($isExpired): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        Expired
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Paused
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex items-center gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                                <button onclick='editCoupon(<?php echo json_encode($c); ?>)' class="p-1 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100 transition-colors" title="Edit Coupon">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                <a href="?delete=<?php echo $c['id']; ?>" onclick="return confirm('Delete coupon <?php echo htmlspecialchars($c['code']); ?>?')" class="p-1 text-slate-400 hover:text-rose-600 rounded hover:bg-rose-50 transition-colors" title="Delete Coupon">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Value Presentation -->
                        <div class="mb-4">
                            <div class="text-2xl font-bold text-slate-900 tracking-tight">
                                <?php if ($isPercentage): ?>
                                    <?php echo (float)$c['value']; ?>% <span class="text-sm font-medium text-slate-500">OFF</span>
                                <?php else: ?>
                                    ₹<?php echo number_format($c['value'], 0); ?> <span class="text-sm font-medium text-slate-500">OFF</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                <?php if ($c['min_order_value'] > 0): ?>
                                    Min spend: <span class="font-semibold text-slate-700">₹<?php echo number_format($c['min_order_value']); ?></span>
                                <?php else: ?>
                                    No minimum spend required
                                <?php endif; ?>
                                <?php if ($isPercentage && $c['max_discount'] > 0): ?>
                                    · Up to <span class="font-semibold text-slate-700">₹<?php echo number_format($c['max_discount']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Stats & Progress -->
                    <div class="pt-3 border-t border-slate-100 space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-600">
                            <span>Redemptions</span>
                            <span class="font-semibold text-slate-800">
                                <?php echo (int)$c['usage_count']; ?>
                                <span class="text-slate-400 font-normal">/ <?php echo $c['usage_limit'] ? $c['usage_limit'] : '∞'; ?></span>
                            </span>
                        </div>
                        
                        <?php if ($c['usage_limit'] > 0): ?>
                            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-primary h-full rounded-full transition-all" style="width: <?php echo $usagePct; ?>%"></div>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1">
                            <span>Expiry:</span>
                            <span class="font-medium <?php echo $isExpired ? 'text-rose-600 font-semibold' : 'text-slate-600'; ?>">
                                <?php echo $c['expiry_date'] ? date('M d, Y', strtotime($c['expiry_date'])) : 'Never expires'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
</div>

<!-- Modal Form -->
<div id="couponModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[200] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
            <div>
                <h2 class="text-base font-bold text-slate-900" id="modal-title">New Coupon</h2>
                <p class="text-xs text-slate-500 mt-0.5">Configure voucher parameters and discount rules</p>
            </div>
            <button type="button" onclick="closeModal()" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        
        <form method="POST" class="p-5 space-y-4">
            <input type="hidden" name="id" id="modal-id">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Coupon Code *</label>
                    <input type="text" name="code" id="modal-code" required placeholder="e.g. DRIYUM20" class="admin-input uppercase font-mono text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Discount Type *</label>
                    <select name="type" id="modal-type" class="admin-select text-xs">
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount (₹)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Discount Value *</label>
                    <input type="number" step="any" name="value" id="modal-value" required placeholder="e.g. 15 or 100" class="admin-input text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Min Order Value (₹)</label>
                    <input type="number" step="any" name="min_order_value" id="modal-min-order" value="0" class="admin-input text-xs">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Max Discount Cap (₹)</label>
                    <input type="number" step="any" name="max_discount" id="modal-max-discount" placeholder="Leave empty for unlimited" class="admin-input text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Usage Limit</label>
                    <input type="number" name="usage_limit" id="modal-limit" placeholder="Leave empty for unlimited" class="admin-input text-xs">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Expiry Date</label>
                    <input type="date" name="expiry_date" id="modal-expiry" class="admin-input text-xs">
                </div>
                <div class="pt-5">
                    <label class="relative flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" id="modal-active" checked class="rounded border-slate-300 text-primary focus:ring-primary">
                        <span class="text-xs font-semibold text-slate-700">Coupon Active</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal()" class="btn-admin btn-admin-secondary text-xs">Cancel</button>
                <button type="submit" name="save_coupon" class="btn-admin btn-admin-primary text-xs">
                    <i class="fas fa-save"></i> Save Coupon
                </button>
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
    document.getElementById('modal-max-discount').value = "";
    document.getElementById('modal-limit').value = "";
    document.getElementById('modal-expiry').value = "";
    document.getElementById('modal-active').checked = true;
    document.getElementById('couponModal').classList.remove('hidden');
    document.getElementById('couponModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('couponModal').classList.remove('flex');
    document.getElementById('couponModal').classList.add('hidden');
}

function editCoupon(c) {
    document.getElementById('modal-title').textContent = "Edit Coupon";
    document.getElementById('modal-id').value = c.id;
    document.getElementById('modal-code').value = c.code;
    document.getElementById('modal-type').value = c.type;
    document.getElementById('modal-value').value = c.value;
    document.getElementById('modal-min-order').value = c.min_order_value;
    document.getElementById('modal-max-discount').value = c.max_discount || "";
    document.getElementById('modal-limit').value = c.usage_limit || "";
    document.getElementById('modal-active').checked = (parseInt(c.is_active) === 1);
    document.getElementById('modal-expiry').value = c.expiry_date ? c.expiry_date.split(' ')[0] : "";
    document.getElementById('couponModal').classList.remove('hidden');
    document.getElementById('couponModal').classList.add('flex');
}
</script>

<?php include 'includes/footer.php'; ?>
