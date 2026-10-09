<?php 
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = '';
$err = '';

// Handle Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = get_db_connection();
    
    $name = sanitize_input($_POST['name'] ?? '');
    $desc = $_POST['description'] ?? '';
    $active = (int)($_POST['is_active'] ?? 1);
    $sort = (int)($_POST['sort_order'] ?? 0);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    // Image Upload
    $image_path = $_POST['current_image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../assets/images/categories/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        if (in_array($ext, $allowed)) {
            $filename = uniqid('cat_') . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_path = 'assets/images/categories/' . $filename;
            }
        } else {
            $err = "Invalid image file format. Allowed formats: " . implode(', ', $allowed);
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
        $_SESSION['success'] = "Category saved successfully!";
        header("Location: categories.php");
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

<div class="max-w-2xl mx-auto pb-12">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="categories.php" class="w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-500 hover:text-slate-900 transition-colors" title="Back to categories">
                <i class="fas fa-arrow-left text-xs"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    <?php echo $id ? 'Edit Category' : 'New Category'; ?>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">Configure store grouping and display icon</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="categories.php" class="btn-admin btn-admin-secondary text-xs">Cancel</a>
            <button type="submit" form="cat-form" class="btn-admin btn-admin-primary text-xs">
                <i class="fas fa-save text-xs"></i> Save Category
            </button>
        </div>
    </div>

    <?php if($err): ?>
        <div class="mb-6 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-medium flex items-center gap-2">
            <i class="fas fa-exclamation-circle text-rose-600"></i>
            <span><?php echo $err; ?></span>
        </div>
    <?php endif; ?>

    <form id="cat-form" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-5">
        <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($cat['image']); ?>">

        <div>
            <label class="admin-label">Category Name <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($cat['name']); ?>" required class="admin-input font-bold text-sm" placeholder="e.g. Exotic Fruits">
        </div>

        <div>
            <label class="admin-label">Description</label>
            <textarea name="description" rows="3" class="admin-textarea text-xs" placeholder="Brief explanation of snacks in this category..."><?php echo htmlspecialchars($cat['description']); ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="admin-label">Sort Order</label>
                <input type="number" name="sort_order" value="<?php echo $cat['sort_order']; ?>" class="admin-input">
                <span class="text-[11px] text-slate-400 mt-0.5 block">Lower numbers appear first</span>
            </div>
            <div>
                <label class="admin-label">Status</label>
                <select name="is_active" class="admin-select font-semibold">
                    <option value="1" <?php echo $cat['is_active'] ? 'selected' : ''; ?>>Active (Visible)</option>
                    <option value="0" <?php echo !$cat['is_active'] ? 'selected' : ''; ?>>Hidden (Draft)</option>
                </select>
            </div>
        </div>

        <div>
            <label class="admin-label">Category Icon / Visual</label>
            <div class="border-2 border-dashed border-slate-200 hover:border-[#004f42] rounded-xl p-5 text-center bg-slate-50/50 hover:bg-slate-50 transition-colors relative group">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-20 h-20 rounded-xl bg-white border border-slate-200 flex items-center justify-center overflow-hidden p-2 shadow-2xs">
                        <img id="cat-preview" src="<?php echo $cat['image'] ? '../' . $cat['image'] : ''; ?>" class="w-full h-full object-contain <?php echo $cat['image'] ? '' : 'hidden'; ?>">
                        <div id="cat-placeholder" class="<?php echo $cat['image'] ? 'hidden' : ''; ?> text-slate-300 text-2xl">
                            <i class="fas fa-shapes"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-700">Upload category icon</p>
                        <p class="text-[11px] text-slate-400">PNG or SVG with transparent background recommended</p>
                    </div>
                </div>
                <input type="file" name="image" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(this, 'cat-preview')">
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex justify-end">
            <button type="submit" class="btn-admin btn-admin-primary text-xs">
                <i class="fas fa-save text-xs"></i> Save Category
            </button>
        </div>
    </form>
</div>

<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            const placeholder = document.getElementById('cat-placeholder');
            
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            if(placeholder) placeholder.classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
