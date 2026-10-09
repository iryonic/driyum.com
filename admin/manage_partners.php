<?php
include 'includes/header.php';
$conn = get_db_connection();
$msg = "";
$err = "";

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_partner'])) {
        $name = sanitize_input($_POST['name']);
        $location = sanitize_input($_POST['location']);
        $sort_order = intval($_POST['sort_order']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (!empty($_POST['edit_id'])) {
            $id = intval($_POST['edit_id']);
            $stmt = $conn->prepare("UPDATE partners SET name=?, location=?, is_active=?, sort_order=? WHERE id=?");
            $stmt->bind_param("ssiii", $name, $location, $is_active, $sort_order, $id);
            if ($stmt->execute()) {
                $msg = "Partner updated successfully!";
                echo "<script>setTimeout(() => window.location.href='manage_partners.php', 600);</script>";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO partners (name, location, is_active, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $name, $location, $is_active, $sort_order);
            if ($stmt->execute()) $msg = "Partner added successfully!";
        }
    }
    
    if (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        $conn->query("DELETE FROM partners WHERE id=$id");
        $msg = "Partner removed successfully!";
    }

    if (isset($_POST['toggle_active'])) {
        $id = intval($_POST['toggle_active']);
        $conn->query("UPDATE partners SET is_active = 1 - is_active WHERE id=$id");
        $msg = "Partner status toggled!";
    }
}

// Fetch All
$partners = fetch_all("SELECT * FROM partners ORDER BY sort_order ASC, created_at DESC", []);

// Edit Mode
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_data = fetch_one("SELECT * FROM partners WHERE id=$edit_id");
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Retail Partners</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage partner stores and physical stocking points across Kashmir and India.</p>
        </div>
        <?php if ($edit_data): ?>
            <a href="manage_partners.php" class="btn-admin btn-admin-secondary text-xs">
                <i class="fas fa-times"></i> Cancel Edit
            </a>
        <?php endif; ?>
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

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Editor Column -->
        <div class="lg:col-span-5">
            <div class="admin-card p-5 sticky top-20">
                <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="fas fa-<?php echo $edit_data ? 'pen' : 'plus-circle'; ?> text-primary"></i>
                    <span><?php echo $edit_data ? 'Edit Retail Partner' : 'Add Retail Partner'; ?></span>
                </h3>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="save_partner" value="1">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                    <?php endif; ?>
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Store / Brand Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($edit_data['name'] ?? ''); ?>" required placeholder="e.g. Nature's Basket / Ecogrocery" class="admin-input text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Location / Address *</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($edit_data['location'] ?? ''); ?>" required placeholder="e.g. Rajbagh, Srinagar" class="admin-input text-xs">
                    </div>

                    <div class="grid grid-cols-2 gap-3 items-center">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sort Order</label>
                            <input type="number" name="sort_order" value="<?php echo $edit_data['sort_order'] ?? 0; ?>" class="admin-input text-xs">
                        </div>
                        <div class="pt-5">
                            <label class="relative flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" <?php echo (!isset($edit_data['is_active']) || $edit_data['is_active']) ? 'checked' : ''; ?> class="rounded border-slate-300 text-primary focus:ring-primary">
                                <span class="text-xs font-semibold text-slate-700">Visible on Store</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full btn-admin btn-admin-primary text-xs py-2.5">
                            <i class="fas fa-save"></i> <?php echo $edit_data ? 'Update Partner' : 'Save Partner'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Partners Table Column -->
        <div class="lg:col-span-7">
            <div class="admin-card p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Partner Name</th>
                                <th>Location</th>
                                <th class="text-center">Order</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($partners)): ?>
                                <tr>
                                    <td colspan="5" class="p-12 text-center text-slate-400">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                            <i class="fas fa-store-alt-slash text-lg"></i>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">No retail partners added</p>
                                        <p class="text-xs text-slate-400 mt-0.5">Use the form on the left to add a stockist.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($partners as $p): 
                                    $isBeingEdited = ($edit_data && $edit_data['id'] == $p['id']);
                                ?>
                                    <tr class="hover:bg-slate-50/70 transition-colors <?php echo $isBeingEdited ? 'bg-primary/5' : ''; ?>">
                                        <td class="font-semibold text-slate-900 text-xs">
                                            <?php echo htmlspecialchars($p['name']); ?>
                                        </td>
                                        <td class="text-xs text-slate-600">
                                            <?php echo htmlspecialchars($p['location']); ?>
                                        </td>
                                        <td class="text-center font-mono text-xs text-slate-700">
                                            <?php echo (int)$p['sort_order']; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="inline-block">
                                                <input type="hidden" name="toggle_active" value="<?php echo $p['id']; ?>">
                                                <button type="submit" class="cursor-pointer">
                                                    <?php if ($p['is_active']): ?>
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                            Disabled
                                                        </span>
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="manage_partners.php?edit=<?php echo $p['id']; ?>" class="p-1.5 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100 transition-colors" title="Edit Partner">
                                                    <i class="fas fa-pen text-xs"></i>
                                                </a>
                                                <form method="POST" onsubmit="return confirm('Delete this retail partner?');" class="inline-block">
                                                    <input type="hidden" name="delete_id" value="<?php echo $p['id']; ?>">
                                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded hover:bg-rose-50 transition-colors" title="Delete Partner">
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
</div>

<?php include 'includes/footer.php'; ?>
