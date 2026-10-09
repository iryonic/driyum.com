<?php
include 'includes/header.php';
$conn = get_db_connection();
$msg = "";

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_testimonial'])) {
        $name = sanitize_input($_POST['name']);
        $location = sanitize_input($_POST['location']);
        $message = sanitize_input($_POST['message']);
        $rating = intval($_POST['rating']);
        
        if (!empty($_POST['edit_id'])) {
            $id = intval($_POST['edit_id']);
            $stmt = $conn->prepare("UPDATE testimonials SET name=?, location=?, message=?, rating=? WHERE id=?");
            $stmt->bind_param("sssii", $name, $location, $message, $rating, $id);
            if ($stmt->execute()) {
                $msg = "Testimonial updated successfully!";
                echo "<script>setTimeout(() => window.location.href='manage_testimonials.php', 600);</script>";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO testimonials (name, location, message, rating, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("sssi", $name, $location, $message, $rating);
            if ($stmt->execute()) $msg = "Testimonial added successfully!";
        }
    }
    
    if (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        $conn->query("DELETE FROM testimonials WHERE id=$id");
        $msg = "Testimonial deleted successfully!";
    }
}

// Fetch All
$query = "SELECT * FROM testimonials ORDER BY created_at DESC";
$pagination = get_pagination_data($query, [], 10);
$reviews = $pagination['records'];

// Edit Mode
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_data = fetch_one("SELECT * FROM testimonials WHERE id=$edit_id");
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Customer Testimonials</h1>
            <p class="text-sm text-slate-500 mt-0.5">Curate standout reviews and testimonials displayed on the homepage trust carousel.</p>
        </div>
        <?php if ($edit_data): ?>
            <a href="manage_testimonials.php" class="btn-admin btn-admin-secondary text-xs">
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
                    <span><?php echo $edit_data ? 'Edit Testimonial' : 'Add Testimonial'; ?></span>
                </h3>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="save_testimonial" value="1">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                    <?php endif; ?>
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Customer Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($edit_data['name'] ?? ''); ?>" required placeholder="e.g. Priya Sharma" class="admin-input text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Location / City</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($edit_data['location'] ?? ''); ?>" placeholder="e.g. Mumbai, Maharashtra" class="admin-input text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Rating</label>
                        <select name="rating" class="admin-select text-xs">
                            <?php $curr_rating = $edit_data['rating'] ?? 5; ?>
                            <option value="5" <?php echo $curr_rating == 5 ? 'selected' : ''; ?>>★★★★★ (5 Stars - Exceptional)</option>
                            <option value="4" <?php echo $curr_rating == 4 ? 'selected' : ''; ?>>★★★★☆ (4 Stars - Great)</option>
                            <option value="3" <?php echo $curr_rating == 3 ? 'selected' : ''; ?>>★★★☆☆ (3 Stars - Good)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Testimonial Quote *</label>
                        <textarea name="message" rows="4" required class="admin-input text-xs resize-none" placeholder="Write the customer's testimonial..."><?php echo htmlspecialchars($edit_data['message'] ?? ''); ?></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full btn-admin btn-admin-primary text-xs py-2.5">
                            <i class="fas fa-save"></i> <?php echo $edit_data ? 'Update Testimonial' : 'Save Testimonial'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Feed List Column -->
        <div class="lg:col-span-7 space-y-4">
            <?php if (empty($reviews)): ?>
                <div class="admin-card p-12 text-center text-slate-400">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                        <i class="fas fa-quote-left text-lg"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">No testimonials published</p>
                    <p class="text-xs text-slate-400 mt-0.5">Add testimonials using the form on the left.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $r): 
                    $isBeingEdited = ($edit_data && $edit_data['id'] == $r['id']);
                ?>
                    <div class="admin-card p-5 relative group transition-all <?php echo $isBeingEdited ? 'border-primary ring-1 ring-primary bg-primary/5' : 'hover:border-slate-300'; ?>">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="flex text-amber-400 text-xs gap-0.5">
                                        <?php for ($i = 0; $i < $r['rating']; $i++): ?>
                                            <i class="fas fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-slate-300">·</span>
                                    <span class="text-[11px] text-slate-400 font-medium">
                                        <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
                                    </span>
                                </div>
                                <h4 class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($r['name']); ?></h4>
                                <?php if (!empty($r['location'])): ?>
                                    <p class="text-xs text-slate-500 mb-2"><?php echo htmlspecialchars($r['location']); ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-slate-700 leading-relaxed italic">
                                    "<?php echo htmlspecialchars($r['message']); ?>"
                                </p>
                            </div>

                            <div class="flex items-center gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                                <a href="manage_testimonials.php?edit=<?php echo $r['id']; ?>" class="p-1.5 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100 transition-colors" title="Edit">
                                    <i class="fas fa-pen text-xs"></i>
                                </a>
                                <form method="POST" onsubmit="return confirm('Delete this testimonial?');" class="inline-block">
                                    <input type="hidden" name="delete_id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded hover:bg-rose-50 transition-colors" title="Delete">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="pt-2">
                <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
