<?php
include 'includes/header.php';

$conn = get_db_connection();
$msg = "";
$error = "";

// Handle Actions
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    execute_query("UPDATE affiliates SET is_approved = 1, status = 'active' WHERE id = ?", [$id]);
    $msg = "Affiliate approved successfully.";
}

if (isset($_GET['suspend'])) {
    $id = (int)$_GET['suspend'];
    execute_query("UPDATE affiliates SET status = 'suspended' WHERE id = ?", [$id]);
    $msg = "Affiliate account suspended.";
}

// Handle Bulk Actions
if (isset($_POST['bulk_action']) && isset($_POST['selected_ids'])) {
    $action = $_POST['bulk_action'];
    $ids = array_map('intval', $_POST['selected_ids']);
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        if ($action === 'delete') {
            execute_query("DELETE FROM affiliates WHERE id IN ($placeholders)", $ids);
            $msg = "Selected affiliates deleted successfully.";
        } elseif ($action === 'approve') {
            execute_query("UPDATE affiliates SET is_approved = 1, status = 'active' WHERE id IN ($placeholders)", $ids);
            $msg = "Selected affiliates approved.";
        } elseif ($action === 'suspend') {
            execute_query("UPDATE affiliates SET status = 'suspended' WHERE id IN ($placeholders)", $ids);
            $msg = "Selected affiliates suspended.";
        } elseif ($action === 'update_commission') {
            $new_rate = (float)$_POST['bulk_commission'];
            $params = array_merge([$new_rate], $ids);
            execute_query("UPDATE affiliates SET commission_rate = ? WHERE id IN ($placeholders)", $params);
            $msg = "Commission updated to {$new_rate}% for selected affiliates.";
        }
    }
}

// Handle Edit Influencer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_influencer'])) {
    $affiliate_id = (int)$_POST['affiliate_id'];
    $user_id = (int)$_POST['user_id'];
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $code = sanitize_input($_POST['code']);
    $commission = (float)$_POST['commission'];
    
    $conn->begin_transaction();
    try {
        execute_query("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?", [$name, $email, $phone, $user_id]);
        
        $exists_code = fetch_one("SELECT id FROM affiliates WHERE code = ? AND id != ?", [$code, $affiliate_id]);
        if ($exists_code) {
             throw new Exception("Handle/Code '$code' is already assigned to another partner.");
        }
        
        execute_query("UPDATE affiliates SET code = ?, commission_rate = ? WHERE id = ?", [$code, $commission, $affiliate_id]);
        $conn->commit();
        $msg = "Influencer partner updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

// Handle Manual Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_influencer'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $code = sanitize_input($_POST['code']);
    $commission = (float)$_POST['commission'];
    
    $conn->begin_transaction();
    try {
        $user = fetch_one("SELECT id FROM users WHERE email = ?", [$email]);
        $user_id = null;
        
        if ($user) {
            $user_id = $user['id'];
        } else {
            $password = password_hash('welcome123', PASSWORD_DEFAULT);
            execute_query("INSERT INTO users (name, email, phone, password, is_active) VALUES (?, ?, ?, ?, 1)", [$name, $email, $phone, $password]);
            $user_id = get_last_insert_id();
            subscribe_newsletter($email);
        }
        
        $exists_aff = fetch_one("SELECT id FROM affiliates WHERE user_id = ?", [$user_id]);
        if ($exists_aff) {
            throw new Exception("This user already has an active affiliate profile.");
        }
        
        $exists_code = fetch_one("SELECT id FROM affiliates WHERE code = ?", [$code]);
        if ($exists_code) {
             throw new Exception("Handle/Code '$code' is already taken.");
        }
        
        execute_query(
            "INSERT INTO affiliates (user_id, code, commission_rate, discount_percentage, is_approved, status) VALUES (?, ?, ?, 10.00, 1, 'active')", 
            [$user_id, $code, $commission]
        );
        
        $conn->commit();
        $msg = "Influencer added successfully with partner code: @$code";
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

// Fetch Affiliates
$query = "
    SELECT a.*, u.name, u.email, u.phone
    FROM affiliates a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC
";
$pagination = get_pagination_data($query, [], 15);
$affiliates = $pagination['records'];
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Affiliates & Partners</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage brand ambassadors, referral voucher codes, and commission payouts.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="document.getElementById('add-modal').classList.remove('hidden')" class="btn-admin btn-admin-primary text-xs">
                <i class="fas fa-plus"></i> Add Influencer
            </button>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="p-3.5 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times text-xs"></i></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-3.5 bg-rose-50 text-rose-800 rounded-xl border border-rose-200 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-circle text-rose-600"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fas fa-times text-xs"></i></button>
        </div>
    <?php endif; ?>

    <!-- Affiliates Form & Table -->
    <form method="POST" id="bulk-form" class="space-y-4">
        <!-- Bulk Controls Toolbar -->
        <div class="admin-card p-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <select name="bulk_action" id="bulk_action_select" onchange="toggleBulkInput()" class="admin-select text-xs w-auto">
                    <option value="">Bulk Actions...</option>
                    <option value="approve">Approve Selected</option>
                    <option value="suspend">Suspend Selected</option>
                    <option value="update_commission">Update Commission %</option>
                    <option value="delete">Delete Forever</option>
                </select>
                
                <input type="number" name="bulk_commission" id="bulk_commission_input" placeholder="New %" min="0" max="100" step="0.1" class="hidden admin-input text-xs w-24">
                
                <button type="submit" onclick="return confirm('Execute bulk action on selected affiliates?')" class="btn-admin btn-admin-primary text-xs py-1.5 px-3">
                    Apply
                </button>
            </div>
            <span class="text-xs text-slate-500">
                Total Affiliates: <strong class="text-slate-800"><?php echo count($affiliates); ?></strong>
            </span>
        </div>

        <div class="admin-card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">
                                <input type="checkbox" onclick="toggleAll(this)" class="rounded border-slate-300 text-primary focus:ring-primary cursor-pointer">
                            </th>
                            <th>Partner Identity</th>
                            <th>Referral Handle</th>
                            <th class="text-right">Commission Rate</th>
                            <th class="text-right">Total Earnings</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($affiliates)): ?>
                            <tr>
                                <td colspan="7" class="p-12 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                        <i class="fas fa-handshake text-lg"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-700">No affiliate partners registered</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Click "Add Influencer" above to create an ambassador code.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($affiliates as $aff): ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="text-center">
                                        <input type="checkbox" name="selected_ids[]" value="<?php echo $aff['id']; ?>" class="rounded border-slate-300 text-primary focus:ring-primary cursor-pointer">
                                    </td>
                                    <td>
                                        <div class="font-semibold text-slate-900 text-xs"><?php echo htmlspecialchars($aff['name']); ?></div>
                                        <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                            <?php echo htmlspecialchars($aff['email']); ?>
                                            <?php if (!empty($aff['phone'])): ?> · <?php echo htmlspecialchars($aff['phone']); ?><?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200">
                                            @<?php echo htmlspecialchars($aff['code']); ?>
                                        </span>
                                    </td>
                                    <td class="text-right font-semibold text-slate-900 text-xs">
                                        <?php echo floatval($aff['commission_rate']); ?>%
                                    </td>
                                    <td class="text-right font-semibold text-slate-900 text-xs">
                                        ₹<?php echo number_format($aff['total_earnings'] ?? 0, 2); ?>
                                    </td>
                                    <td>
                                        <?php if (!$aff['is_approved']): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                Pending Review
                                            </span>
                                        <?php elseif ($aff['status'] == 'suspended'): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                Suspended
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($aff)); ?>)' class="p-1.5 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100 transition-colors" title="Edit Partner">
                                                <i class="fas fa-pen text-xs"></i>
                                            </button>

                                            <?php if (!$aff['is_approved']): ?>
                                                <a href="?approve=<?php echo $aff['id']; ?>" class="btn-admin btn-admin-primary text-[10px] py-1 px-2.5">Approve</a>
                                            <?php endif; ?>
                                            
                                            <?php if ($aff['status'] == 'active' && $aff['is_approved']): ?>
                                                <a href="?suspend=<?php echo $aff['id']; ?>" class="p-1.5 text-slate-400 hover:text-rose-600 rounded hover:bg-rose-50 transition-colors" title="Suspend Partner">
                                                    <i class="fas fa-ban text-xs"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($aff['status'] == 'suspended'): ?>
                                                <a href="?approve=<?php echo $aff['id']; ?>" class="p-1.5 text-slate-400 hover:text-emerald-600 rounded hover:bg-emerald-50 transition-colors" title="Reactivate Partner">
                                                    <i class="fas fa-undo text-xs"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
    </form>
</div>

<!-- ADD MODAL -->
<div id="add-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[200] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <h3 class="text-base font-bold text-slate-900">Add Influencer Partner</h3>
            <button type="button" onclick="document.getElementById('add-modal').classList.add('hidden')" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="add_influencer" value="1">
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name</label>
                <input type="text" name="name" required placeholder="e.g. Rahul Sharma" class="admin-input text-xs">
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Address</label>
                <input type="email" name="email" required placeholder="influencer@example.com" class="admin-input text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Phone Number</label>
                <input type="text" name="phone" required placeholder="9876543210" class="admin-input text-xs font-mono">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Handle / Code</label>
                    <input type="text" name="code" required placeholder="rahul10" class="admin-input text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Commission (%)</label>
                    <input type="number" name="commission" value="10" min="0" max="100" class="admin-input text-xs">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('add-modal').classList.add('hidden')" class="btn-admin btn-admin-secondary text-xs">Cancel</button>
                <button type="submit" class="btn-admin btn-admin-primary text-xs">Create Partner</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="edit-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[200] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <h3 class="text-base font-bold text-slate-900">Edit Influencer</h3>
            <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="edit_influencer" value="1">
            <input type="hidden" name="affiliate_id" id="edit_affiliate_id">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name</label>
                <input type="text" name="name" id="edit_name" required class="admin-input text-xs">
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Address</label>
                <input type="email" name="email" id="edit_email" required class="admin-input text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Phone Number</label>
                <input type="text" name="phone" id="edit_phone" required class="admin-input text-xs font-mono">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Handle / Code</label>
                    <input type="text" name="code" id="edit_code" required class="admin-input text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Commission (%)</label>
                    <input type="number" name="commission" id="edit_commission" min="0" max="100" step="0.01" class="admin-input text-xs">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')" class="btn-admin btn-admin-secondary text-xs">Cancel</button>
                <button type="submit" class="btn-admin btn-admin-primary text-xs">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleBulkInput() {
    const select = document.getElementById('bulk_action_select');
    const input = document.getElementById('bulk_commission_input');
    if (select.value === 'update_commission') {
        input.classList.remove('hidden');
        input.required = true;
        input.focus();
    } else {
        input.classList.add('hidden');
        input.required = false;
    }
}

function toggleAll(source) {
    const checkboxes = document.getElementsByName('selected_ids[]');
    for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}

function openEditModal(data) {
    document.getElementById('edit_affiliate_id').value = data.id;
    document.getElementById('edit_user_id').value = data.user_id;
    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_email').value = data.email;
    document.getElementById('edit_phone').value = data.phone;
    document.getElementById('edit_code').value = data.code;
    document.getElementById('edit_commission').value = data.commission_rate;
    document.getElementById('edit-modal').classList.remove('hidden');
    document.getElementById('edit-modal').classList.add('flex');
}
</script>

<?php include 'includes/footer.php'; ?>
