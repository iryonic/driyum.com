<?php
include 'includes/header.php';
$conn = get_db_connection();

$msg = "";
$error = "";

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_testimonial'])) {
        $name = $_POST['name'];
        $location = $_POST['location'];
        $message = $_POST['message'];
        $rating = intval($_POST['rating']);
        
        if(!empty($_POST['edit_id'])) {
            // Update Existing
            $id = intval($_POST['edit_id']);
            $stmt = $conn->prepare("UPDATE testimonials SET name=?, location=?, message=?, rating=? WHERE id=?");
            $stmt->bind_param("sssii", $name, $location, $message, $rating, $id);
            if ($stmt->execute()) {
                $msg = "Review updated successfully!";
                echo "<script>window.location.href='manage_testimonials.php';</script>";
                exit;
            }
        } else {
            // Add New
            $stmt = $conn->prepare("INSERT INTO testimonials (name, location, message, rating, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("sssi", $name, $location, $message, $rating);
            if ($stmt->execute()) $msg = "Review added successfully!";
        }
    }
    
    if (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        $conn->query("DELETE FROM testimonials WHERE id=$id");
        $msg = "Review deleted.";
    }
}

// Fetch All
$query = "SELECT * FROM testimonials ORDER BY created_at DESC";
$pagination = get_pagination_data($query, [], 10);
$reviews = $pagination['records'];

// Check for Edit Mode
$edit_data = null;
if(isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_data = fetch_one("SELECT * FROM testimonials WHERE id=$edit_id");
}
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-['Fredoka'] font-bold text-gray-900">Manage Reviews</h1>
            <p class="text-gray-500">Add, Edit or remove customer testimonials.</p>
        </div>
        <?php if($edit_data): ?>
            <a href="manage_testimonials.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-bold">Cancel Edit</a>
        <?php endif; ?>
    </div>

    <!-- Feedback -->
    <?php if($msg): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-6 border-l-4 border-green-500 font-bold"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Add/Edit Form -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-6">
                <h3 class="text-xl font-bold mb-4 font-['Fredoka']"><?php echo $edit_data ? 'Edit Review' : 'Add New Review'; ?></h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="save_testimonial" value="1">
                    <?php if($edit_data): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                    <?php endif; ?>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Customer Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($edit_data['name'] ?? ''); ?>" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-[#19DC7E]">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Location / Role</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($edit_data['location'] ?? ''); ?>" placeholder="e.g. Mumbai" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-[#19DC7E]">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Rating</label>
                        <select name="rating" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-[#19DC7E]">
                            <?php $curr_rating = $edit_data['rating'] ?? 5; ?>
                            <option value="5" <?php echo $curr_rating==5?'selected':''; ?>>⭐⭐⭐⭐⭐ (5 Stars)</option>
                            <option value="4" <?php echo $curr_rating==4?'selected':''; ?>>⭐⭐⭐⭐ (4 Stars)</option>
                            <option value="3" <?php echo $curr_rating==3?'selected':''; ?>>⭐⭐⭐ (3 Stars)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Message</label>
                        <textarea name="message" rows="4" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-[#19DC7E]"><?php echo htmlspecialchars($edit_data['message'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="w-full bg-black text-white font-bold py-3 rounded-lg hover:bg-[#19DC7E] hover:text-black transition">
                        <?php echo $edit_data ? 'Update Review' : 'Add Review'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- List -->
        <div class="lg:col-span-2 space-y-4">
            <?php foreach($reviews as $r): ?>
            <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition flex justify-between items-start group <?php echo ($edit_data && $edit_data['id'] == $r['id']) ? 'border-[#19DC7E] ring-2 ring-[#19DC7E]/20' : ''; ?>">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-yellow-400 text-sm">
                            <?php for($i=0; $i<$r['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                        </span>
                        <span class="font-bold text-gray-900"><?php echo htmlspecialchars($r['name']); ?></span>
                        <span class="text-xs text-gray-400 uppercase tracking-wide">• <?php echo htmlspecialchars($r['location']); ?></span>
                    </div>
                    <p class="text-gray-600 italic">"<?php echo htmlspecialchars($r['message']); ?>"</p>
                    <div class="mt-2 text-xs text-gray-300">Added: <?php echo date('M d, Y', strtotime($r['created_at'])); ?></div>
                </div>
                
                <div class="flex gap-2">
                    <a href="manage_testimonials.php?edit=<?php echo $r['id']; ?>" class="text-gray-300 hover:text-blue-500 w-8 h-8 flex items-center justify-center rounded-full hover:bg-blue-50 transition">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form method="POST" onsubmit="return confirm('Delete this review?');">
                        <input type="hidden" name="delete_id" value="<?php echo $r['id']; ?>">
                        <button type="submit" class="text-gray-300 hover:text-red-500 w-8 h-8 flex items-center justify-center rounded-full hover:bg-red-50 transition">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>

            <?php if(empty($reviews)): ?>
                <div class="text-center py-20 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                    <p class="text-gray-400 font-bold">No reviews found. Add one to get started!</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>
