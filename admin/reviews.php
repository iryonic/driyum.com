<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Strict Admin Check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    if (isset($_POST['ajax_action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    header("Location: ../login.php");
    exit;
}

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

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Product Reviews</h1>
            <p class="text-sm text-slate-500 mt-0.5">Moderate customer feedback, star ratings, and product testimonials.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="manage_testimonials.php" class="btn-admin btn-admin-secondary text-xs">
                <i class="fas fa-quote-left text-slate-500"></i> Homepage Testimonials
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="p-3.5 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600"></i>
                <span><?php echo htmlspecialchars($_SESSION['success']); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times text-xs"></i></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Search & Bulk Bar -->
    <div class="admin-card p-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <form method="GET" action="reviews.php" class="flex-1 max-w-md relative">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by customer, product, or review text..." class="admin-input pl-9 text-xs">
            </form>

            <div class="flex items-center gap-2">
                <div id="bulk-action-bar" class="hidden admin-bulk-dock">
                    <span class="bulk-counter-badge"><span id="selected-count">0</span> Selected</span>
                    <button onclick="bulkReviewAction('bulk_delete_reviews')" class="bulk-btn bulk-btn-danger" title="Delete Selected">
                        <i class="fas fa-trash-alt mr-1"></i> Delete
                    </button>
                    <button onclick="document.querySelectorAll('.review-checkbox').forEach(cb => { cb.checked = false; }); updateBulkBar();" class="text-slate-400 hover:text-slate-700 p-1 text-xs" title="Cancel">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <?php if ($search): ?>
                    <a href="reviews.php" class="btn-admin btn-admin-secondary text-xs" title="Reset Search">
                        <i class="fas fa-undo"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Reviews Table -->
    <div class="admin-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">
                            <input type="checkbox" id="select-all" class="rounded border-slate-300 text-primary focus:ring-primary cursor-pointer" onchange="toggleSelectAll(this)">
                        </th>
                        <th>Reviewer</th>
                        <th>Product</th>
                        <th>Rating</th>
                        <th>Feedback Note</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                    <i class="fas fa-comment-slash text-lg"></i>
                                </div>
                                <p class="text-sm font-semibold text-slate-700">No product reviews found</p>
                                <p class="text-xs text-slate-400 mt-0.5">Try searching with other terms or wait for new feedback.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $r): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors group">
                                <td class="text-center">
                                    <input type="checkbox" class="review-checkbox rounded border-slate-300 text-primary focus:ring-primary cursor-pointer" value="<?php echo $r['id']; ?>" onchange="updateBulkBar()">
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center uppercase">
                                            <?php echo strtoupper(substr($r['user_name'] ?: 'U', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="text-xs font-semibold text-slate-900"><?php echo htmlspecialchars($r['user_name']); ?></div>
                                            <div class="text-[11px] text-slate-400 font-mono"><?php echo htmlspecialchars($r['user_email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-xs font-semibold text-slate-900"><?php echo htmlspecialchars($r['product_name']); ?></div>
                                    <span class="text-[10px] text-slate-400 font-mono">Product #<?php echo $r['product_id']; ?></span>
                                </td>
                                <td>
                                    <div class="flex items-center text-amber-400 text-xs gap-0.5">
                                        <?php for ($i = 0; $i < $r['rating']; $i++): ?>
                                            <i class="fas fa-star"></i>
                                        <?php endfor; ?>
                                        <?php for ($i = $r['rating']; $i < 5; $i++): ?>
                                            <i class="far fa-star text-slate-200"></i>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td class="max-w-md">
                                    <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                                        "<?php echo htmlspecialchars($r['comment']); ?>"
                                    </p>
                                    <span class="text-[10px] text-slate-400 mt-0.5 block">
                                        <?php echo date('M d, Y · h:i A', strtotime($r['created_at'])); ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a href="?delete=<?php echo $r['id']; ?><?php echo $search ? '&q=' . urlencode($search) : ''; ?>" onclick="return confirm('Permanently remove this review?')" class="p-1.5 text-slate-400 hover:text-rose-600 rounded hover:bg-rose-50 transition-colors inline-block" title="Delete Review">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
</div>

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
        bar.classList.remove('hidden'); 
        bar.classList.add('flex');
        count.innerText = checked.length;
    } else { 
        bar.classList.add('hidden'); 
        bar.classList.remove('flex'); 
    }
}

async function bulkReviewAction(action) {
    const checked = document.querySelectorAll('.review-checkbox:checked');
    if (checked.length === 0) return;
    const ok = typeof window.showConfirm === 'function'
        ? await window.showConfirm('Delete selected review(s)? This action cannot be reversed.', {
            title: 'Delete Reviews',
            type: 'danger',
            confirmText: 'Delete Forever'
        })
        : confirm('Delete selected review(s)? This action cannot be reversed.');
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
