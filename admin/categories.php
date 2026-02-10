<?php include 'includes/header.php'; ?>

<?php
// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    get_db_connection()->query("DELETE FROM categories WHERE id = $id");
    echo "<script>window.location='categories.php';</script>";
}

$query = "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as prod_count FROM categories c ORDER BY c.sort_order ASC, c.id DESC";
$pagination = get_pagination_data($query, [], 12);
$cats = $pagination['records'];
?>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 fredoka tracking-tight">Category Hub</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Found <span class="text-black"><?php echo $pagination['total_records']; ?></span> departments in the vault</p>
    </div>
    <a href="category_form.php" class="bg-[#19DC7E] text-black font-black px-6 py-2.5 rounded-2xl text-[10px] uppercase tracking-widest shadow-lg shadow-emerald-100 hover:scale-105 active:scale-95 transition-all text-center">
        <i class="fas fa-plus mr-1"></i> New Category
    </a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php foreach ($cats as $c): ?>
    <div class="category-card bg-white rounded-3xl p-5 shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 group flex flex-col anim-up relative overflow-hidden">
        
        <!-- Decoration -->
        <div class="absolute -right-4 -top-4 w-16 h-16 bg-gray-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700"></div>

        <div class="flex items-start justify-between mb-4 relative z-10">
            <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center p-2 border border-gray-50 group-hover:border-[#19DC7E] transition-all overflow-hidden">
                <?php if($c['image']): ?>
                    <img src="../<?php echo $c['image']; ?>" class="w-full h-full object-contain group-hover:scale-110 transition duration-500">
                <?php else: ?>
                    <i class="fas fa-shapes text-gray-300 text-xl"></i>
                <?php endif; ?>
            </div>
            <div class="flex gap-1.5 opacity-0 group-hover:opacity-100 transition-all">
                <a href="category_form.php?id=<?php echo $c['id']; ?>" class="w-8 h-8 flex items-center justify-center bg-black text-[#19DC7E] rounded-xl hover:scale-105 transition active:scale-95"><i class="fas fa-edit text-[10px]"></i></a>
                <button onclick="if(confirm('Delete category?')) window.location='?delete=<?php echo $c['id']; ?>'" class="w-8 h-8 flex items-center justify-center bg-red-50 text-red-400 rounded-xl hover:bg-red-500 hover:text-white transition active:scale-95"><i class="fas fa-trash-alt text-[10px]"></i></button>
            </div>
        </div>

        <div class="flex-1 relative z-10">
            <h3 class="font-bold text-lg fredoka text-gray-900 mb-1 group-hover:text-[#19DC7E] transition-colors leading-tight"><?php echo $c['name']; ?></h3>
            <p class="text-gray-400 font-medium text-xs mb-4 line-clamp-2"><?php echo $c['description'] ?: 'No description provided.'; ?></p>
        </div>
        
        <div class="flex justify-between items-center border-t border-gray-50 pt-4 mt-auto relative z-10">
            <div class="flex flex-col">
                <span class="text-[8px] font-black uppercase text-gray-400">Total Items</span>
                <span class="font-black text-gray-900 text-sm"><?php echo $c['prod_count']; ?> <span class="text-[9px] font-bold text-gray-300 uppercase ml-0.5">Snacks</span></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[8px] font-black text-gray-300">#<?php echo $c['sort_order']; ?></span>
                <div class="h-2 w-2 rounded-full <?php echo $c['is_active'] ? 'bg-[#19DC7E]' : 'bg-gray-200'; ?>"></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="mt-8">
    <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
</div>

<div class="mt-12 bg-[#0f172a] p-6 rounded-3xl shadow-xl relative overflow-hidden group anim-up border border-white/5">
    <div class="relative z-10 flex flex-col md:flex-row gap-5 items-center text-center md:text-left">
        <div class="w-12 h-12 rounded-xl bg-[#19DC7E]/10 flex items-center justify-center text-[#19DC7E] text-xl group-hover:scale-110 transition-transform">
            <i class="fas fa-magic"></i>
        </div>
        <div>
            <h4 class="text-white text-sm font-bold fredoka mb-0.5">Visual Tip</h4>
            <p class="text-gray-500 text-[11px] font-medium leading-relaxed">The <span class="text-[#19DC7E]">"Browse by Vibe"</span> section on your homepage pulls from these categories. Vibrant icons make it pop!</p>
        </div>
    </div>
    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-[#19DC7E]/5 rounded-full blur-2xl"></div>
</div>

<?php include 'includes/footer.php'; ?>
