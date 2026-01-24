<?php include 'includes/header.php'; ?>

<?php
// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        // Save/Update logic would go here (Simplified for demo)
        $msg = "Product saved successfully!";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    get_db_connection()->query("DELETE FROM products WHERE id = $id");
    echo "<script>window.location='products.php';</script>";
}

// AJAX Status Toggle
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'toggle_status') {
    $id = (int)$_POST['id'];
    $current = fetch_one("SELECT is_active FROM products WHERE id = ?", [$id]);
    if ($current) {
        $new_status = $current['is_active'] ? 0 : 1;
        execute_query("UPDATE products SET is_active = ? WHERE id = ?", [$new_status, $id]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'new_status' => $new_status, 'label' => $new_status ? 'Active' : 'Draft']);
        exit;
    }
}

// Search Logic
$search = sanitize_input($_GET['search'] ?? '');
$params = [];
$where = "WHERE 1=1";
if ($search) {
    $where .= " AND (p.name LIKE ? OR c.name LIKE ?)";
    $params = ["%$search%", "%$search%"];
}

// Fetch Products
$query = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where ORDER BY p.id DESC";
$pagination = get_pagination_data($query, $params, 12);
$products = $pagination['records'];
?>

<div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-4xl font-black text-gray-900 fredoka mb-2">Snack Vault.</h1>
        <p class="text-gray-500 font-medium font-['Outfit']">Managing <span class="text-black font-bold"><?php echo $pagination['total_records']; ?></span> active products in your catalog.</p>
    </div>
    <div class="flex items-center gap-4 w-full md:w-auto">
        <form class="relative flex-1 md:w-64">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Search snacks..." class="w-full pl-12 pr-4 py-3 bg-white border-2 border-transparent focus:border-[#19DC7E] outline-none rounded-2xl shadow-sm transition-all font-bold font-['Outfit']">
        </form>
        <a href="product_form.php" class="btn-chunky bg-[#19DC7E] text-black font-black px-8 py-3 rounded-2xl shadow-xl hover:scale-105 transition border-none whitespace-nowrap">
            <i class="fas fa-plus-circle mr-2"></i> New Snack
        </a>
    </div>
</div>

<!-- Products Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-8" id="productGrid">
    <?php foreach ($products as $p): ?>
    <div class="product-card bg-white rounded-[35px] p-5 shadow-sm border-2 border-transparent hover:border-[#19DC7E] hover:shadow-2xl transition-all duration-500 group relative flex flex-col anim-up">
        
        <!-- Status & Badge -->
        <div class="absolute top-6 left-6 z-20 flex flex-col gap-2">
            <button onclick="toggleProductStatus(<?php echo $p['id']; ?>, this)" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm font-['Outfit'] transition-all hover:scale-105 active:scale-95 status-badge <?php echo $p['is_active'] ? 'bg-[#19DC7E] text-black' : 'bg-gray-200 text-gray-500'; ?>">
                <?php echo $p['is_active'] ? 'Active' : 'Draft'; ?>
            </button>
            <?php if($p['stock'] <= 5): ?>
                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-red-500 text-white shadow-lg animate-pulse font-['Outfit']">Low Stock</span>
            <?php endif; ?>
        </div>

        <!-- Image -->
        <div class="rounded-[28px] aspect-square mb-6 relative overflow-hidden flex items-center justify-center p-4 transition-colors duration-500" style="background-color: <?php echo $p['bg_color'] ?: '#F9FAFB'; ?>">
            <img src="../<?php echo $p['image']; ?>" class="w-full h-full object-contain group-hover:scale-110 transition duration-700 drop-shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-t from-black/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>

        <!-- Info -->
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-[#19DC7E] bg-[#19DC7E]/10 px-2 py-0.5 rounded-md font-['Outfit']"><?php echo $p['cat_name']; ?></span>
            </div>
            <h3 class="font-black text-xl fredoka text-gray-900 leading-tight mb-2 group-hover:text-[#19DC7E] transition-colors"><?php echo $p['name']; ?></h3>
            
            <div class="flex justify-between items-end mt-4">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1 font-['Outfit']">Pricing</span>
                    <span class="font-black text-2xl text-gray-900 font-['Outfit']">₹<?php echo number_format($p['price']); ?></span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1 font-['Outfit']">Stock</span>
                    <span class="font-black text-lg font-['Outfit'] <?php echo $p['stock'] < 10 ? 'text-red-500' : 'text-gray-900'; ?>"><?php echo $p['stock']; ?></span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 mt-8">
            <a href="product_form.php?id=<?php echo $p['id']; ?>" class="flex-1 btn-chunky bg-black text-white py-3 rounded-xl text-center text-[10px] font-black uppercase tracking-widest hover:bg-[#19DC7E] hover:text-black transition">Manage</a>
            <button onclick="if(confirm('Delete this snack? This cannot be undone.')) window.location='?delete=<?php echo $p['id']; ?>'" class="w-12 h-12 flex items-center justify-center bg-gray-50 text-gray-400 rounded-xl hover:bg-red-500 hover:text-white transition duration-300 shadow-sm"><i class="fas fa-trash-alt"></i></button>
        </div>

    </div>
    <?php endforeach; ?>
</div>

<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>

<script>
async function toggleProductStatus(id, btn) {
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('ajax_action', 'toggle_status');
        formData.append('id', id);

        const response = await fetch('products.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            btn.innerHTML = data.label;
            if (data.new_status) {
                btn.classList.remove('bg-gray-200', 'text-gray-500');
                btn.classList.add('bg-[#19DC7E]', 'text-black');
            } else {
                btn.classList.remove('bg-[#19DC7E]', 'text-black');
                btn.classList.add('bg-gray-200', 'text-gray-500');
            }
            // Success animation
            btn.classList.add('scale-110');
            setTimeout(() => btn.classList.remove('scale-110'), 200);
        } else {
            alert('Failed to update status');
            btn.innerHTML = originalContent;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred');
        btn.innerHTML = originalContent;
    } finally {
        btn.disabled = false;
    }
}
</script>
</body>
</html>
