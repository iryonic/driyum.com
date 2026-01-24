<?php include 'includes/header.php'; ?>

<?php
// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Optional: Check if products exist before delete?
    get_db_connection()->query("DELETE FROM categories WHERE id = $id");
    echo "<script>window.location='categories.php';</script>";
}

$query = "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as prod_count FROM categories c ORDER BY c.sort_order ASC, c.id DESC";
$pagination = get_pagination_data($query, [], 12);
$cats = $pagination['records'];
?>

<div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-4xl font-black text-gray-900 fredoka mb-2">Category Hub.</h1>
        <p class="text-gray-500 font-medium font-['Outfit']">Organize your snacks into <span class="text-black font-bold"><?php echo $pagination['total_records']; ?></span> unique sections.</p>
    </div>
    <a href="category_form.php" class="btn-chunky bg-[#19DC7E] text-black font-black px-8 py-3 rounded-2xl shadow-xl hover:scale-105 transition border-none whitespace-nowrap">
        <i class="fas fa-plus-circle mr-2"></i> New Category
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
    <?php foreach ($cats as $c): ?>
    <div class="category-card bg-white rounded-[40px] p-8 shadow-sm border-2 border-transparent hover:border-[#19DC7E] hover:shadow-2xl transition-all duration-500 group relative flex flex-col anim-up">
        
        <div class="flex justify-between items-start mb-8">
            <div class="w-24 h-24 rounded-[30px] bg-gray-50 flex items-center justify-center text-4xl overflow-hidden shadow-inner border border-gray-100 p-2">
                <?php if($c['image']): ?>
                    <img src="../<?php echo $c['image']; ?>" class="w-full h-full object-contain mix-blend-multiply group-hover:scale-110 transition duration-500">
                <?php else: ?>
                    <i class="fas fa-shapes text-gray-200"></i>
                <?php endif; ?>
            </div>
            <div class="flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-4 group-hover:translate-x-0">
                <a href="category_form.php?id=<?php echo $c['id']; ?>" class="w-12 h-12 flex items-center justify-center bg-black text-[#19DC7E] rounded-2xl hover:scale-110 transition shadow-lg"><i class="fas fa-edit"></i></a>
                <button onclick="if(confirm('Delete this category?')) window.location='?delete=<?php echo $c['id']; ?>'" class="w-12 h-12 flex items-center justify-center bg-red-50 text-red-500 rounded-2xl hover:bg-red-500 hover:text-white transition shadow-sm"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>

        <div class="flex-1">
            <h3 class="font-black text-2xl fredoka text-gray-900 mb-2 group-hover:text-[#19DC7E] transition-colors"><?php echo $c['name']; ?></h3>
            <p class="text-gray-400 font-medium text-sm mb-6 line-clamp-3 font-['Outfit']"><?php echo $c['description'] ?: 'No description provided yet.'; ?></p>
        </div>
        
        <div class="flex justify-between items-center border-t border-gray-50 pt-5 mt-auto">
            <div class="flex flex-col">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-300">Catalog Size</span>
                <span class="font-black text-xl font-['Outfit']"><?php echo $c['prod_count']; ?> <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter ml-1">Snacks</span></span>
            </div>
            <div class="<?php echo $c['is_active'] ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400'; ?> px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">
                <?php echo $c['is_active'] ? 'Live' : 'Hidden'; ?>
            </div>
        </div>

        <div class="absolute top-4 right-8 text-[10px] font-black text-gray-200 fredoka tracking-widest opacity-20">#<?php echo $c['sort_order']; ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>

<div class="mt-12 bg-gray-900 p-8 rounded-[40px] shadow-2xl relative overflow-hidden group anim-up">
    <div class="relative z-10 flex flex-col md:flex-row gap-6 items-center">
        <div class="w-20 h-20 rounded-3xl bg-[#19DC7E]/20 flex items-center justify-center text-[#19DC7E] text-3xl group-hover:rotate-12 transition-transform">
            <i class="fas fa-lightbulb"></i>
        </div>
        <div>
            <h4 class="text-white text-xl font-black fredoka mb-1">Administrative Pro-Tip</h4>
            <p class="text-gray-400 font-medium font-['Outfit']">The <span class="text-[#19DC7E] font-bold">"Browse by Vibe"</span> section on your homepage pulls directly from these categories. Use vibrant images for a better visual experience!</p>
        </div>
    </div>
    <!-- Background Decoration -->
    <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-[#19DC7E]/5 rounded-full blur-3xl"></div>
</div>

</body>
</html>
