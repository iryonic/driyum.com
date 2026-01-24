<?php include 'includes/header.php'; ?>
<?php
// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn = get_db_connection();
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Review removed successfully.";
    } else {
        $_SESSION['error'] = "Something went wrong.";
    }
    $conn->close();
    echo "<script>window.location.href='reviews.php';</script>";
    exit;
}

$search = sanitize_input($_GET['q'] ?? '');
$params = [];
$query = "SELECT r.*, u.name as user_name, u.email as user_email, p.name as product_name 
          FROM reviews r 
          JOIN users u ON r.user_id = u.id 
          JOIN products p ON r.product_id = p.id";

if ($search) {
    $query .= " WHERE (u.name LIKE ? OR p.name LIKE ? OR r.comment LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY r.created_at DESC";

$pagination = get_pagination_data($query, $params, 15);
$reviews = $pagination['records'];
?>

<div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 anim-up">
    <div>
        <h1 class="text-4xl font-black text-gray-900 fredoka mb-2">Product Reviews.</h1>
        <div class="flex items-center gap-4">
            <p class="text-gray-500 font-medium font-['Outfit'] italic">Community feedback and snack verdicts.</p>
            <div class="h-4 w-px bg-gray-200"></div>
            <span class="text-[10px] font-black uppercase tracking-widest text-[#19DC7E] bg-[#19DC7E]/10 px-3 py-1 rounded-full"><?php echo $pagination['total_records']; ?> Verified Reviews</span>
        </div>
    </div>
    
    <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
        <form class="relative group">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search reviews..." 
                   class="bg-white border-2 border-gray-100 rounded-2xl px-6 py-4 pl-12 outline-none focus:border-[#19DC7E] transition-all font-bold text-xs w-full sm:w-64 shadow-sm">
            <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-gray-300"></i>
        </form>
    </div>
</div>

<?php if(isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if(isset($_SESSION['success'])): ?>
            showPremiumToast("<?php echo $_SESSION['success']; ?>", 'success');
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            showPremiumToast("<?php echo $_SESSION['error']; ?>", 'error');
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    });
</script>
<?php endif; ?>

<div class="bg-white rounded-[50px] shadow-2xl border border-gray-100 overflow-hidden anim-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-gray-400 text-[10px] uppercase bg-gray-50/30 border-b border-gray-100 font-['Outfit']">
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Reviewer</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Product</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Verdict</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Rating</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40 text-right">Moderation</th>
                </tr>
            </thead>
            <tbody class="text-sm font-['Outfit'] text-gray-600">
                <?php if (empty($reviews)): ?>
                <tr>
                    <td colspan="5" class="p-24 text-center">
                        <div class="w-24 h-24 bg-gray-50 rounded-[35px] flex items-center justify-center mx-auto mb-8 shadow-inner border border-gray-100">
                            <i class="fas fa-comment-slash text-gray-200 text-4xl"></i>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 fredoka mb-2">Silence in the Valley.</h3>
                        <p class="text-gray-400 font-medium font-['Outfit'] text-sm">No reviews found matching your search.</p>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($reviews as $r): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-all duration-300 group">
                    <td class="p-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-[#19DC7E]/10 rounded-2xl flex items-center justify-center text-[#19DC7E] font-black text-xl">
                                <?php echo strtoupper(substr($r['user_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="font-black text-lg text-gray-900 leading-tight"><?php echo $r['user_name']; ?></div>
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest"><?php echo $r['user_email']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="p-10">
                        <div class="font-bold text-gray-800"><?php echo $r['product_name']; ?></div>
                        <div class="text-[9px] font-black text-gray-300 uppercase tracking-widest mt-1">Product ID: #<?php echo $r['product_id']; ?></div>
                    </td>
                    <td class="p-10 max-w-xs">
                        <p class="text-sm text-gray-500 italic leading-relaxed line-clamp-2">"<?php echo htmlspecialchars($r['comment']); ?>"</p>
                        <span class="text-[9px] font-black text-gray-300 uppercase tracking-widest mt-2 block"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
                    </td>
                    <td class="p-10">
                        <div class="flex text-yellow-400 text-xs gap-0.5">
                            <?php for($i=0; $i<$r['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                        </div>
                        <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest mt-1 inline-block"><?php echo $r['rating']; ?> / 5 Stars</span>
                    </td>
                    <td class="p-10 text-right">
                        <div class="flex justify-end translate-x-4 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-500">
                            <a href="?delete=<?php echo $r['id']; ?><?php echo $search ? '&q='.$search : ''; ?>" onclick="return confirm('Nuke this review? This cannot be undone.')" class="w-14 h-14 bg-red-50 text-red-500 hover:bg-red-600 hover:text-white flex items-center justify-center rounded-[20px] transition-all shadow-sm active:scale-95">
                                <i class="fas fa-trash-alt text-lg"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
</main>
</body>
</html>
