<?php 
include 'includes/header.php'; 

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    get_db_connection()->query("DELETE FROM categories WHERE id = $id");
    $_SESSION['success'] = "Category deleted successfully.";
    header("Location: categories.php");
    exit;
}

$query = "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as prod_count FROM categories c ORDER BY c.sort_order ASC, c.id DESC";
$pagination = get_pagination_data($query, [], 12);
$cats = $pagination['records'];
?>

<!-- PAGE HEADER -->
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Product Categories</h1>
        <p class="text-xs text-slate-500 mt-0.5">Found <span class="font-semibold text-slate-800"><?php echo $pagination['total_records']; ?></span> categories organizing the store catalog</p>
    </div>
    <a href="category_form.php" class="btn-admin btn-admin-primary text-xs">
        <i class="fas fa-plus text-xs"></i> New Category
    </a>
</div>

<!-- CATEGORIES GRID -->
<?php if(empty($cats)): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
            <i class="fas fa-th-list"></i>
        </div>
        <h3 class="text-base font-bold text-slate-800">No categories found</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Create your first category to group your products on the shop page.</p>
        <a href="category_form.php" class="btn-admin btn-admin-primary text-xs mt-4">Create Category</a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        <?php foreach ($cats as $c): ?>
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs hover:border-slate-300 hover:shadow-sm transition-all duration-200 p-5 flex flex-col justify-between group">
            
            <div>
                <!-- Top Row: Icon + Actions -->
                <div class="flex items-start justify-between gap-3 mb-3.5">
                    <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center p-1.5 shrink-0 group-hover:border-[#004f42] transition-colors overflow-hidden">
                        <?php if($c['image']): ?>
                            <img src="../<?php echo $c['image']; ?>" class="w-full h-full object-contain" alt="<?php echo htmlspecialchars($c['name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-shapes text-slate-300 text-lg"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center gap-1">
                        <a href="category_form.php?id=<?php echo $c['id']; ?>" class="btn-admin btn-admin-icon" title="Edit category">
                            <i class="fas fa-pen text-xs"></i>
                        </a>
                        <button onclick="if(confirm('Delete category <?php echo addslashes($c['name']); ?>?')) window.location='?delete=<?php echo $c['id']; ?>'" class="btn-admin btn-admin-icon hover:text-rose-600 hover:border-rose-200 hover:bg-rose-50" title="Delete category">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Info -->
                <h3 class="font-bold text-base text-slate-900 group-hover:text-[#004f42] transition-colors leading-tight mb-1">
                    <?php echo htmlspecialchars($c['name']); ?>
                </h3>
                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed mb-4">
                    <?php echo htmlspecialchars($c['description'] ?: 'No description provided.'); ?>
                </p>
            </div>

            <!-- Footer Stats -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="font-semibold text-slate-700">
                    <?php echo $c['prod_count']; ?> <span class="font-normal text-slate-400">products</span>
                </span>
                
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-slate-400 font-mono">#<?php echo $c['sort_order']; ?></span>
                    <span class="w-2 h-2 rounded-full <?php echo $c['is_active'] ? 'bg-emerald-500' : 'bg-slate-300'; ?>" title="<?php echo $c['is_active'] ? 'Active' : 'Hidden'; ?>"></span>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- PAGINATION -->
<div class="mt-8">
    <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
</div>

<?php include 'includes/footer.php'; ?>
