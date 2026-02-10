<?php
include 'includes/header.php';
$conn = get_db_connection();
$msg = "";

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_testimonial'])) {
        $name = sanitize_input($_POST['name']);
        $location = sanitize_input($_POST['location']);
        $message = $_POST['message'];
        $rating = intval($_POST['rating']);
        
        if(!empty($_POST['edit_id'])) {
            $id = intval($_POST['edit_id']);
            $stmt = $conn->prepare("UPDATE testimonials SET name=?, location=?, message=?, rating=? WHERE id=?");
            $stmt->bind_param("sssii", $name, $location, $message, $rating, $id);
            if ($stmt->execute()) {
                $msg = "Review updated!";
                echo "<script>setTimeout(() => window.location.href='manage_testimonials.php', 800);</script>";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO testimonials (name, location, message, rating, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("sssi", $name, $location, $message, $rating);
            if ($stmt->execute()) $msg = "Review added!";
        }
    }
    
    if (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        $conn->query("DELETE FROM testimonials WHERE id=$id");
        $msg = "Review deleted!";
    }
}

// Fetch All
$query = "SELECT * FROM testimonials ORDER BY created_at DESC";
$pagination = get_pagination_data($query, [], 10);
$reviews = $pagination['records'];

// Edit Mode
$edit_data = null;
if(isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_data = fetch_one("SELECT * FROM testimonials WHERE id=$edit_id");
}
?>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 fredoka tracking-tight">Reviews</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Found <span class="text-black"><?php echo $pagination['total_records']; ?></span> customer testimonials</p>
    </div>
    <?php if($edit_data): ?>
        <a href="manage_testimonials.php" class="bg-gray-100 text-gray-500 px-6 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-all">Cancel Edit</a>
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
            <h3 class="text-lg font-black fredoka mb-6 text-gray-900"><?php echo $edit_data ? 'Update Review' : 'New Review'; ?></h3>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="save_testimonial" value="1">
                <?php if($edit_data): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>
                
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($edit_data['name'] ?? ''); ?>" required class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-2xl px-5 py-3 text-sm font-bold shadow-sm transition-all outline-none">
                </div>

                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Location</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($edit_data['location'] ?? ''); ?>" placeholder="e.g. Mumbai" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-2xl px-5 py-3 text-sm font-bold shadow-sm transition-all outline-none">
                </div>

                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Rating</label>
                    <select name="rating" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-2xl px-5 py-3 text-xs font-black uppercase tracking-widest shadow-sm transition-all outline-none">
                        <?php $curr_rating = $edit_data['rating'] ?? 5; ?>
                        <option value="5" <?php echo $curr_rating==5?'selected':''; ?>>5 Stars ⭐⭐⭐⭐⭐</option>
                        <option value="4" <?php echo $curr_rating==4?'selected':''; ?>>4 Stars ⭐⭐⭐⭐</option>
                        <option value="3" <?php echo $curr_rating==3?'selected':''; ?>>3 Stars ⭐⭐⭐</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Review Content</label>
                    <textarea name="message" rows="4" required class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-2xl px-5 py-4 text-sm font-medium shadow-sm transition-all outline-none leading-relaxed"><?php echo htmlspecialchars($edit_data['message'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="w-full bg-black text-[#19DC7E] py-4 rounded-[20px] font-black uppercase tracking-widest shadow-xl shadow-black/5 hover:scale-[1.02] active:scale-95 transition-all">
                    <?php echo $edit_data ? 'Save Changes' : 'Save Review'; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Feed -->
    <div class="lg:col-span-8 space-y-6">
        <?php foreach($reviews as $r): ?>
        <div class="bg-white p-8 rounded-[32px] border border-gray-100 shadow-sm hover:border-black transition-all flex justify-between items-start group <?php echo ($edit_data && $edit_data['id'] == $r['id']) ? 'border-2 border-black bg-gray-50' : ''; ?> anim-up">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex text-amber-400 text-[10px]">
                        <?php for($i=0; $i<$r['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                    </div>
                    <span class="w-px h-3 bg-gray-100"></span>
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
                </div>
                <h4 class="text-lg font-black text-gray-900 mb-1"><?php echo htmlspecialchars($r['name']); ?></h4>
                <p class="text-[9px] font-black text-[#19DC7E] uppercase tracking-widest mb-4"><?php echo htmlspecialchars($r['location']); ?></p>
                <p class="text-gray-600 text-sm leading-relaxed italic">"<?php echo htmlspecialchars($r['message']); ?>"</p>
            </div>
            
            <div class="flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all">
                <a href="manage_testimonials.php?edit=<?php echo $r['id']; ?>" class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-black hover:text-[#19DC7E] transition-all" title="Edit">
                    <i class="fas fa-edit text-[10px]"></i>
                </a>
                <form method="POST" onsubmit="return confirm('Delete this review?');">
                    <input type="hidden" name="delete_id" value="<?php echo $r['id']; ?>">
                    <button type="submit" class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-300 hover:bg-red-500 hover:text-white transition-all">
                        <i class="fas fa-trash-alt text-[10px]"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="pt-6">
            <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
        </div>

        <?php if(empty($reviews)): ?>
            <div class="text-center py-24 bg-gray-50/50 rounded-[40px] border-4 border-dashed border-gray-100">
                <i class="fas fa-star text-4xl text-gray-100 mb-4 block"></i>
                <p class="text-gray-400 font-black uppercase tracking-widest text-xs">No reviews yet.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
