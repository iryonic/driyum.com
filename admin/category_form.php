<?php include 'includes/header.php'; ?>
<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = '';
$err = '';

// Handle Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = get_db_connection();
    
    $name = $conn->real_escape_string($_POST['name']);
    $desc = $conn->real_escape_string($_POST['description']);
    $active = (int)$_POST['is_active'];
    $sort = (int)$_POST['sort_order'];
    // Auto-generate slug if empty or just based on name
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    // Image Upload
    $image_path = $_POST['current_image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../assets/images/categories/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('cat_') . '.' . $ext;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $image_path = 'assets/images/categories/' . $filename;
        }
    }

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE categories SET name=?, slug=?, description=?, is_active=?, sort_order=?, image=? WHERE id=?");
        $stmt->bind_param("sssiisi", $name, $slug, $desc, $active, $sort, $image_path, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, is_active, sort_order, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiis", $name, $slug, $desc, $active, $sort, $image_path);
    }

    if ($stmt->execute()) {
        $msg = "Category saved! Slug: $slug";
        if ($id == 0) $id = $stmt->insert_id;
        echo "<script>window.location='categories.php';</script>";
        exit;
    } else {
        $err = "Error: " . $stmt->error;
    }
}

// Fetch Data
$cat = ['name'=>'', 'description'=>'', 'is_active'=>1, 'sort_order'=>0, 'image'=>''];
if ($id) {
    $res = get_db_connection()->query("SELECT * FROM categories WHERE id = $id");
    if($res) $cat = $res->fetch_assoc();
}
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-8 flex justify-between items-center">
        <div>
             <h1 class="text-3xl font-['Crimson_Pro'] font-bold text-gray-900"><?php echo $id ? 'Edit Category' : 'New Category'; ?></h1>
             <a href="categories.php" class="text-gray-500 hover:text-black mt-1 inline-block"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="bg-green-100 text-green-700 px-4 py-3 rounded-xl font-bold mb-6 animate-pulse"><?php echo $msg; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-[20px] shadow-sm border border-gray-100 space-y-6">
        <input type="hidden" name="current_image" value="<?php echo $cat['image']; ?>">

        <div>
            <label class="block text-xs font-bold uppercase text-gray-400 mb-2">Category Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($cat['name']); ?>" required class="w-full text-2xl font-bold border-b-2 border-gray-100 focus:border-[#19DC7E] outline-none py-2" placeholder="e.g. Exotic Fruits">
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-400 mb-2">Description</label>
            <textarea name="description" rows="3" class="w-full bg-gray-50 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#19DC7E]"><?php echo htmlspecialchars($cat['description']); ?></textarea>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-400 mb-2">Sort Order</label>
                <input type="number" name="sort_order" value="<?php echo $cat['sort_order']; ?>" class="w-full bg-gray-50 rounded-xl px-4 py-3 font-bold">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-gray-400 mb-2">Status</label>
                <select name="is_active" class="w-full bg-gray-50 rounded-xl px-4 py-3 font-bold">
                    <option value="1" <?php echo $cat['is_active']?'selected':''; ?>>Active</option>
                    <option value="0" <?php echo !$cat['is_active']?'selected':''; ?>>Hidden</option>
                </select>
            </div>
        </div>

        <div class="bg-gray-50 rounded-[30px] p-6 border-2 border-dashed border-gray-200 group hover:border-[#19DC7E] transition-all relative overflow-hidden">
            <label class="block text-center text-xs font-black uppercase text-gray-400 mb-4 tracking-widest group-hover:text-[#19DC7E]">Category Visual</label>
            <div class="flex flex-col items-center gap-4">
                <div class="w-32 h-32 rounded-[24px] bg-white shadow-inner flex items-center justify-center overflow-hidden border border-gray-100">
                    <img id="cat-preview" src="<?php echo $cat['image'] ? '../'.$cat['image'] : ''; ?>" class="w-full h-full object-cover <?php echo $cat['image'] ? '' : 'hidden'; ?>">
                    <div id="cat-placeholder" class="<?php echo $cat['image'] ? 'hidden' : ''; ?> text-gray-200 text-4xl">
                        <i class="fas fa-image"></i>
                    </div>
                </div>
                <div class="relative w-full">
                    <input type="file" name="image" class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="previewImage(this, 'cat-preview'); document.getElementById('cat-placeholder').classList.add('hidden'); document.getElementById('cat-preview').classList.remove('hidden');">
                    <div class="w-full py-3 bg-white rounded-xl border-2 border-gray-100 text-center text-xs font-bold text-gray-500 group-hover:bg-[#19DC7E] group-hover:text-black group-hover:border-[#19DC7E] transition-all">
                        <i class="fas fa-upload mr-2"></i> Choose Category Icon
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-4">
            <button type="submit" class="w-full btn-chunky bg-black text-white py-4 text-lg hover:bg-[#19DC7E] border-none shadow-xl transform transition">
                <i class="fas fa-save mr-2"></i> Save Category
            </button>
        </div>
    </form>
</div>
</body>
</html>


