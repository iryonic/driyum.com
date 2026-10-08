<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// AJAX Actions
if (isset($_POST['ajax_action'])) {
    $ids = $_POST['ids'] ?? [];
    if (!empty($ids)) {
        $conn = get_db_connection();
        $ids_str = implode(',', array_map('intval', $ids));
        
        if ($_POST['ajax_action'] === 'bulk_delete_reviews') {
            $conn->query("DELETE FROM reviews WHERE id IN ($ids_str)");
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}
?>
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
    }
    $conn->close();
    echo "<script>window.location.href='reviews.php';</script>";
    exit;
}

$search = sanitize_input($_GET['q'] ?? '');
$params = [];
$where = "WHERE 1=1";

if ($search) {
    $where .= " AND (u.name LIKE ? OR p.name LIKE ? OR r.comment LIKE ?)";
    $term = "%$search%";
    $params[] = $term; $params[] = $term; $params[] = $term;
}

$query = "SELECT r.*, u.name as user_name, u.email as user_email, p.name as product_name 
          FROM reviews r 
          JOIN users u ON r.user_id = u.id 
          JOIN products p ON r.product_id = p.id
          $where
          ORDER BY r.created_at DESC";

$pagination = get_pagination_data($query, $params, 15);
$reviews = $pagination['records'];
?>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 crimson-pro tracking-tight">Product Reviews</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Managing <span class="text-black"><?php echo $pagination['total_records']; ?></span> verified verdicts</p>
    </div>
    
    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
        <div id="bulk-action-bar" class="hidden items-center gap-3 bg-white px-4 py-2 rounded-2xl border border-gray-100 shadow-sm anim-up">
            <span class="text-[9px] font-black text-[#24B25D] uppercase tracking-widest px-2"><span id="selected-count">0</span> Selected</span>
            <button onclick="bulkReviewAction('bulk_delete_reviews')" class="bg-red-50 text-red-500 w-8 h-8 rounded-xl hover:bg-red-500 hover:text-white transition flex items-center justify-center">
                <i class="fas fa-trash-alt text-[10px]"></i>
            </button>
        </div>

        <form class="relative group flex-1 sm:flex-none">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-black transition-colors text-[10px]"></i>
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search reviews..." class="w-full bg-white border border-gray-100 rounded-2xl pl-10 pr-4 py-2 text-xs font-bold outline-none focus:border-black shadow-sm transition-all sm:min-w-[200px]">
        </form>
    </div>
</div>

<?php if(isset($_SESSION['success'])): ?>
    <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-2xl border border-green-100 font-bold text-xs anim-up flex items-center gap-3">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-gray-400 text-[8px] uppercase bg-gray-50/50 border-b border-gray-100 font-black tracking-widest">
                    <th class="p-5 w-16 text-center">
                        <input type="checkbox" id="select-all" class="w-4 h-4 rounded border-gray-200 text-black focus:ring-black cursor-pointer" onchange="toggleSelectAll(this)">
                    </th>
                    <th class="p-5 pl-0">Reviewer</th>
                    <th class="p-5">Product</th>
                    <th class="p-5">Verdict</th>
                    <th class="p-5">Rating</th>
                    <th class="p-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-xs text-gray-600">
                <?php if (empty($reviews)): ?>
                <tr>
                    <td colspan="6" class="p-16 text-center">
                        <i class="fas fa-comment-slash text-2xl text-gray-100 mb-4 block"></i>
                        <h3 class="text-xl font-black text-gray-900">Quiet Zone</h3>
                        <p class="text-[10px] text-gray-400 mt-1">No reviews found matching your search.</p>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($reviews as $r): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-all group review-row">
                    <td class="p-4 text-center">
                        <input type="checkbox" class="review-checkbox w-4 h-4 rounded border-gray-200 text-[#24B25D] focus:ring-[#24B25D] cursor-pointer" value="<?php echo $r['id']; ?>" onchange="updateBulkBar()">
                    </td>
                    <td class="p-4 pl-0">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-gray-900 font-black text-xs border border-gray-50 group-hover:bg-black group-hover:text-[#24B25D] transition-all">
                                <?php echo strtoupper(substr($r['user_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900"><?php echo $r['user_name']; ?></div>
                                <div class="text-[9px] text-gray-400 font-medium"><?php echo $r['user_email']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        <div class="font-bold text-gray-900"><?php echo $r['product_name']; ?></div>
                        <div class="text-[8px] text-gray-400 font-black uppercase tracking-widest">ID: #<?php echo $r['product_id']; ?></div>
                    </td>
                    <td class="p-4 max-w-xs">
                        <p class="text-xs text-gray-500 italic line-clamp-2">"<?php echo htmlspecialchars($r['comment']); ?>"</p>
                        <span class="text-[8px] font-black text-gray-300 uppercase tracking-widest mt-1 block"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
                    </td>
                    <td class="p-4">
                        <div class="flex text-[#24B25D] text-[8px] gap-0.5">
                            <?php for($i=0; $i<$r['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                        </div>
                    </td>
                    <td class="p-4 text-right">
                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                            <a href="?delete=<?php echo $r['id']; ?><?php echo $search ? '&q='.$search : ''; ?>" onclick="return confirm('Delete review?')" class="w-8 h-8 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-lg flex items-center justify-center transition-all">
                                <i class="fas fa-trash-alt text-[10px]"></i>
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

<script>
function toggleSelectAll(master) {
    document.querySelectorAll('.review-checkbox').forEach(cb => cb.checked = master.checked);
    updateBulkBar();
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.review-checkbox:checked');
    const bar = document.getElementById('bulk-action-bar');
    const count = document.getElementById('selected-count');
    if (checked.length > 0) {
        bar.classList.remove('hidden'); bar.classList.add('flex');
        count.innerText = checked.length;
    } else { bar.classList.add('hidden'); bar.classList.remove('flex'); }
}

async function bulkReviewAction(action) {
    const checked = document.querySelectorAll('.review-checkbox:checked');
    if (checked.length === 0) return;
    const ok = await showConfirm('Delete selected reviews? This cannot be undone.', {
        title: 'Delete Reviews',
        type: 'danger',
        confirmText: 'Delete Reviews'
    });
    if (!ok) return;
    const ids = Array.from(checked).map(cb => cb.value);
    const formData = new FormData();
    formData.append('ajax_action', action);
    ids.forEach(id => formData.append('ids[]', id));
    const response = await fetch('reviews.php', { method: 'POST', body: formData });
    const data = await response.json();
    if (data.success) window.location.reload();
}
</script>
<?php include 'includes/footer.php'; ?>


