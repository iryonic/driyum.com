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
        
        if(!empty($_POST['edit_id'])) {
            $id = intval($_POST['edit_id']);
            $stmt = $conn->prepare("UPDATE partners SET name=?, location=?, is_active=?, sort_order=? WHERE id=?");
            $stmt->bind_param("ssiii", $name, $location, $is_active, $sort_order, $id);
            if ($stmt->execute()) {
                $msg = "Partner updated!";
                echo "<script>setTimeout(() => window.location.href='manage_partners.php', 800);</script>";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO partners (name, location, is_active, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $name, $location, $is_active, $sort_order);
            if ($stmt->execute()) $msg = "Partner added!";
        }
    }
    
    if (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        $conn->query("DELETE FROM partners WHERE id=$id");
        $msg = "Partner deleted!";
    }

    if (isset($_POST['toggle_active'])) {
        $id = intval($_POST['toggle_active']);
        $conn->query("UPDATE partners SET is_active = 1 - is_active WHERE id=$id");
        $msg = "Status toggled!";
    }
}

// Fetch All
$partners = fetch_all("SELECT * FROM partners ORDER BY sort_order ASC, created_at DESC", []);

// Edit Mode
$edit_data = null;
if(isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_data = fetch_one("SELECT * FROM partners WHERE id=$edit_id");
}
?>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 crimson-pro tracking-tight">Retail Partners</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Manage where Driyum is available offline</p>
    </div>
    <?php if($edit_data): ?>
        <a href="manage_partners.php" class="bg-gray-100 text-gray-500 px-6 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-all">Cancel Edit</a>
    <?php endif; ?>
</div>

<?php if($msg): ?>
    <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-2xl border border-green-100 font-bold text-xs anim-up">
        <i class="fas fa-check-circle mr-2"></i> <?php echo $msg; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
    
    <!-- Editor -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-[32px] shadow-sm border border-gray-100 p-8 sticky top-10 anim-up">
            <h3 class="text-lg font-black crimson-pro mb-6 text-gray-900"><?php echo $edit_data ? 'Update Partner' : 'New Partner'; ?></h3>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="save_partner" value="1">
                <?php if($edit_data): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>
                
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Store Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($edit_data['name'] ?? ''); ?>" required placeholder="e.g. Ecogrocery" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-2xl px-5 py-3 text-sm font-bold shadow-sm transition-all outline-none">
                </div>

                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Location/City</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($edit_data['location'] ?? ''); ?>" placeholder="e.g. Rajbagh, Srinagar" required class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-2xl px-5 py-3 text-sm font-bold shadow-sm transition-all outline-none">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Sort Order</label>
                        <input type="number" name="sort_order" value="<?php echo $edit_data['sort_order'] ?? 0; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-2xl px-5 py-3 text-sm font-bold shadow-sm transition-all outline-none">
                    </div>
                    <div class="flex items-end pb-3">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" name="is_active" <?php echo (!isset($edit_data['is_active']) || $edit_data['is_active']) ? 'checked' : ''; ?> class="w-5 h-5 rounded border-gray-300 text-black focus:ring-black">
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-500 group-hover:text-black transition-colors">Visible</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="w-full bg-black text-[#24B25D] py-4 rounded-[20px] font-black uppercase tracking-widest shadow-xl shadow-black/5 hover:scale-[1.02] active:scale-95 transition-all">
                    <?php echo $edit_data ? 'Save Changes' : 'Add Partner'; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- List -->
    <div class="lg:col-span-8 space-y-4">
        <?php foreach($partners as $p): ?>
        <div class="bg-white p-6 rounded-[28px] border border-gray-100 shadow-sm hover:border-black transition-all flex items-center gap-6 group <?php echo (!$p['is_active']) ? 'opacity-60 grayscale' : ''; ?> anim-up">
            <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center shrink-0 border border-gray-100 text-gray-300">
                <i class="fas fa-store text-xl"></i>
            </div>
            
            <div class="flex-1">
                <h4 class="text-base font-black text-gray-900 leading-tight"><?php echo htmlspecialchars($p['name']); ?></h4>
                <p class="text-[10px] font-bold text-[#24B25D] uppercase tracking-widest mt-1">
                    <i class="fas fa-location-dot mr-1"></i> <?php echo htmlspecialchars($p['location']); ?>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <form method="POST" class="inline">
                    <input type="hidden" name="toggle_active" value="<?php echo $p['id']; ?>">
                    <button type="submit" class="w-10 h-10 rounded-xl <?php echo $p['is_active'] ? 'bg-green-50 text-green-500 hover:bg-green-100' : 'bg-gray-50 text-gray-400 hover:bg-gray-100'; ?> flex items-center justify-center transition-all" title="Toggle Visibility">
                        <i class="fas <?php echo $p['is_active'] ? 'fa-eye' : 'fa-eye-slash'; ?> text-xs"></i>
                    </button>
                </form>
                
                <a href="manage_partners.php?edit=<?php echo $p['id']; ?>" class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-black hover:text-[#24B25D] transition-all" title="Edit">
                    <i class="fas fa-edit text-xs"></i>
                </a>

                <form method="POST" onsubmit="return confirm('Remove this partner?');" class="inline">
                    <input type="hidden" name="delete_id" value="<?php echo $p['id']; ?>">
                    <button type="submit" class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-300 hover:bg-red-500 hover:text-white transition-all">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(empty($partners)): ?>
            <div class="text-center py-20 bg-gray-50/50 rounded-[40px] border-4 border-dashed border-gray-100">
                <i class="fas fa-handshake text-4xl text-gray-100 mb-4 block"></i>
                <p class="text-gray-400 font-black uppercase tracking-widest text-xs">No partners listed yet.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
