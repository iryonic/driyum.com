<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check Admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$search = sanitize_input($_GET['q'] ?? '');

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn = get_db_connection();
    $stmt = $conn->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Subscriber removed successfully.";
    }
    $conn->close();
    header("Location: subscribers.php" . ($search ? "?q=" . urlencode($search) : ""));
    exit;
}

// Handle Status Toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn = get_db_connection();
    $conn->query("UPDATE newsletter_subscribers SET is_active = NOT is_active WHERE id = $id");
    header("Location: subscribers.php" . ($search ? "?q=" . urlencode($search) : ""));
    exit;
}

// Bulk Broadcaster Logic (Background Queued)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_email') {
    $subject = sanitize_input($_POST['subject']);
    $body = $_POST['body'];
    $select_all_matches = ($_POST['all_selected'] ?? 'false') === 'true';
    $selected_ids = $_POST['selected_ids'] ?? [];

    $conn = get_db_connection();
    $emails = [];

    if ($select_all_matches) {
        $where = "WHERE 1=1";
        $params = [];
        if ($search) {
            $where .= " AND email LIKE ?";
            $params[] = "%$search%";
        }
        $res = fetch_all("SELECT email FROM newsletter_subscribers $where", $params);
        foreach ($res as $r) $emails[] = $r['email'];
    } else if (!empty($selected_ids)) {
        $ids_str = implode(',', array_map('intval', $selected_ids));
        $res = fetch_all("SELECT email FROM newsletter_subscribers WHERE id IN ($ids_str)");
        foreach ($res as $r) $emails[] = $r['email'];
    }

    if (!empty($emails)) {
        foreach ($emails as $email) {
            queue_email($email, $subject, $body);
        }
        $_SESSION['success'] = "Broadcast campaign of " . count($emails) . " email(s) queued for background delivery!";
        process_email_queue(5);
    } else {
        $_SESSION['error'] = "No subscribers selected for broadcast.";
    }
    header("Location: subscribers.php");
    exit;
}

include 'includes/header.php';

$params = [];
$query = "SELECT * FROM newsletter_subscribers";
if ($search) {
    $query .= " WHERE email LIKE ?";
    $params[] = "%$search%";
}
$query .= " ORDER BY subscribed_at DESC";

$pagination = get_pagination_data($query, $params, 15);
$subscribers = $pagination['records'];
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Newsletter Subscribers</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage audience reach, active subscriptions, and dispatch email marketing campaigns.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openEmailModal()" id="bulkEmailBtn" class="btn-admin btn-admin-primary text-xs hidden items-center gap-1.5">
                <i class="fas fa-paper-plane"></i> Send Email (<span id="selectedCountDisplay">0</span>)
            </button>
            <button onclick="exportSubscribers()" class="btn-admin btn-admin-secondary text-xs">
                <i class="fas fa-file-export text-slate-500"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- Selection Notice Banner (Light Themed & Responsive) -->
    <div id="selectionBanner" class="hidden bg-emerald-50/90 text-emerald-950 px-4 py-3 rounded-xl border border-emerald-200 items-center justify-between gap-3 flex-wrap shadow-xs">
        <div class="flex items-center gap-2.5">
            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">
                <i class="fas fa-check text-[10px]"></i>
            </span>
            <span class="text-xs font-semibold text-emerald-900" id="selectionText">Items selected.</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="selectAllMatches()" class="btn-admin btn-admin-secondary text-xs py-1 px-2.5 bg-white text-emerald-900 border-emerald-300 hover:bg-emerald-100" id="selectAllBtn">
                Select all <?php echo $pagination['total_records']; ?> matches
            </button>
            <button onclick="resetSelection()" class="text-xs font-semibold text-rose-600 hover:text-rose-800 px-2">Clear</button>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="admin-card p-4">
        <form method="GET" action="subscribers.php" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="relative flex-1 max-w-md">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by email address..." class="admin-input pl-9 text-xs">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn-admin btn-admin-primary text-xs">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($search): ?>
                    <a href="subscribers.php" class="btn-admin btn-admin-secondary text-xs" title="Reset Search">
                        <i class="fas fa-undo"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Subscribers Table -->
    <div class="admin-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">
                            <input type="checkbox" id="selectAllHeader" class="rounded border-slate-300 text-primary focus:ring-primary cursor-pointer">
                        </th>
                        <th>Subscriber Email</th>
                        <th>Subscription Status</th>
                        <th>Joined Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscribers)): ?>
                        <tr>
                            <td colspan="5" class="p-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                    <i class="fas fa-envelope-open-text text-lg"></i>
                                </div>
                                <p class="text-sm font-semibold text-slate-700">No subscribers found</p>
                                <p class="text-xs text-slate-400 mt-0.5">Try searching with different terms or check back after promotions.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subscribers as $s): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors group">
                                <td class="text-center">
                                    <input type="checkbox" value="<?php echo $s['id']; ?>" class="subscriber-checkbox rounded border-slate-300 text-primary focus:ring-primary cursor-pointer">
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 border border-slate-200 text-slate-500 flex items-center justify-center text-xs font-mono">
                                            #<?php echo $s['id']; ?>
                                        </div>
                                        <div class="text-xs font-semibold text-slate-900 font-mono">
                                            <?php echo htmlspecialchars($s['email']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="?toggle=<?php echo $s['id']; ?><?php echo $search ? '&q=' . urlencode($search) : ''; ?>" title="Click to toggle subscription">
                                        <?php if ($s['is_active']): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200 transition-colors">
                                                Unsubscribed
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="text-xs text-slate-800"><?php echo date('M d, Y', strtotime($s['subscribed_at'])); ?></div>
                                    <div class="text-[10px] text-slate-400"><?php echo date('h:i A', strtotime($s['subscribed_at'])); ?></div>
                                </td>
                                <td class="text-right">
                                    <a href="?delete=<?php echo $s['id']; ?>" onclick="return confirm('Remove subscriber <?php echo htmlspecialchars($s['email']); ?>?')" class="p-1.5 text-slate-400 hover:text-rose-600 rounded hover:bg-rose-50 transition-colors inline-block" title="Delete Subscriber">
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

<!-- Broadcast Email Modal -->
<div id="emailModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden z-[200] items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 p-6 relative">
        <div class="flex justify-between items-center pb-4 border-b border-slate-100 mb-5">
            <div>
                <h3 class="text-base font-bold text-slate-900">Broadcast Campaign</h3>
                <p class="text-xs text-slate-500 mt-0.5">Sending message to <span id="modalTargetCount" class="font-semibold text-emerald-600">0</span> recipient(s)</p>
            </div>
            <button onclick="closeEmailModal()" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        
        <form method="POST" id="bulkEmailForm" class="space-y-4">
            <input type="hidden" name="action" value="bulk_email">
            <input type="hidden" name="all_selected" id="inputAllSelected" value="false">
            <div id="selectedIdsContainer"></div>
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Subject *</label>
                <input type="text" name="subject" required class="admin-input text-xs" placeholder="e.g. Exclusive Harvest Special: 15% Off Valley Walnuts!">
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Message Body (HTML supported) *</label>
                <textarea name="body" required rows="6" class="admin-input text-xs resize-none" placeholder="Hello! We are delighted to share our newest premium harvest with you..."></textarea>
            </div>
            
            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeEmailModal()" class="btn-admin btn-admin-secondary text-xs">Cancel</button>
                <button type="submit" class="btn-admin btn-admin-primary text-xs">
                    <i class="fas fa-paper-plane"></i> Queue & Dispatch
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const STORAGE_KEY = 'driyum_selected_subscribers';
const selectAllHeader = document.getElementById('selectAllHeader');
const subscriberCheckboxes = document.querySelectorAll('.subscriber-checkbox');
const selectionBanner = document.getElementById('selectionBanner');
const selectedCountDisplay = document.getElementById('selectedCountDisplay');
const bulkEmailBtn = document.getElementById('bulkEmailBtn');
const selectionText = document.getElementById('selectionText');
const selectAllBtn = document.getElementById('selectAllBtn');

let isAllSelectedAcrossPages = (sessionStorage.getItem('sub_all_pages') === 'true');
const TOTAL_RECORDS = <?php echo (int)$pagination['total_records']; ?>;

function getStored() {
    return new Set(JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]'));
}

function syncStored(set) {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify([...set]));
    updateUI();
}

function selectAllMatches() {
    isAllSelectedAcrossPages = true;
    sessionStorage.setItem('sub_all_pages', 'true');
    updateUI();
}

function resetSelection() {
    isAllSelectedAcrossPages = false;
    sessionStorage.removeItem('sub_all_pages');
    sessionStorage.removeItem(STORAGE_KEY);
    if(selectAllHeader) selectAllHeader.checked = false;
    subscriberCheckboxes.forEach(cb => cb.checked = false);
    updateUI();
}

function updateUI() {
    const tracked = getStored();
    const onPage = subscriberCheckboxes.length;
    const checkedOnPage = Array.from(subscriberCheckboxes).filter(cb => cb.checked).length;
    
    if(selectAllHeader) selectAllHeader.checked = (onPage > 0 && checkedOnPage === onPage);

    if (tracked.size > 0 || isAllSelectedAcrossPages) {
        bulkEmailBtn.classList.remove('hidden');
        bulkEmailBtn.classList.add('inline-flex');
        selectionBanner.classList.remove('hidden');
        selectionBanner.classList.add('flex');

        if (isAllSelectedAcrossPages) {
            selectedCountDisplay.textContent = TOTAL_RECORDS;
            selectionText.textContent = `All ${TOTAL_RECORDS} subscribers selected`;
            selectAllBtn.classList.add('hidden');
        } else {
            selectedCountDisplay.textContent = tracked.size;
            selectionText.textContent = `${tracked.size} subscriber(s) selected on this page`;
            if (checkedOnPage === onPage && TOTAL_RECORDS > onPage) {
                selectAllBtn.classList.remove('hidden');
            } else {
                selectAllBtn.classList.add('hidden');
            }
        }
    } else {
        bulkEmailBtn.classList.add('hidden');
        bulkEmailBtn.classList.remove('inline-flex');
        selectionBanner.classList.add('hidden');
        selectionBanner.classList.remove('flex');
    }
}

(function init() {
    const tracked = getStored();
    subscriberCheckboxes.forEach(cb => {
        if (tracked.has(cb.value)) cb.checked = true;
    });
    updateUI();
})();

if(selectAllHeader) {
    selectAllHeader.addEventListener('change', () => {
        const tracked = getStored();
        subscriberCheckboxes.forEach(cb => {
            cb.checked = selectAllHeader.checked;
            if (selectAllHeader.checked) tracked.add(cb.value);
            else tracked.delete(cb.value);
        });
        syncStored(tracked);
    });
}

subscriberCheckboxes.forEach(cb => {
    cb.addEventListener('change', () => {
        const tracked = getStored();
        if (cb.checked) tracked.add(cb.value);
        else {
            tracked.delete(cb.value);
            isAllSelectedAcrossPages = false;
            sessionStorage.setItem('sub_all_pages', 'false');
        }
        syncStored(tracked);
    });
});

function openEmailModal() {
    const tracked = getStored();
    const count = isAllSelectedAcrossPages ? TOTAL_RECORDS : tracked.size;
    
    document.getElementById('modalTargetCount').textContent = count;
    document.getElementById('inputAllSelected').value = isAllSelectedAcrossPages;
    
    const container = document.getElementById('selectedIdsContainer');
    container.innerHTML = '';
    
    if (!isAllSelectedAcrossPages) {
        tracked.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_ids[]';
            input.value = id;
            container.appendChild(input);
        });
    }

    document.getElementById('emailModal').classList.remove('hidden');
    document.getElementById('emailModal').classList.add('flex');
}

function closeEmailModal() {
    document.getElementById('emailModal').classList.remove('flex');
    document.getElementById('emailModal').classList.add('hidden');
}

function exportSubscribers() {
    let url = 'export_subscribers.php';
    const params = new URLSearchParams();
    const tracked = Array.from(getStored());
    
    if (isAllSelectedAcrossPages) {
        params.append('all', '1');
        const q = new URLSearchParams(window.location.search).get('q');
        if (q) params.append('q', q);
    } else if (tracked.length > 0) {
        params.append('ids', tracked.join(','));
    } else {
        params.append('all', '1');
        const q = new URLSearchParams(window.location.search).get('q');
        if (q) params.append('q', q);
    }
    
    window.location.href = url + '?' + params.toString();
}
</script>

<?php include 'includes/footer.php'; ?>
