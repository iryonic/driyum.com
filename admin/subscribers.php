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
    header("Location: subscribers.php");
    exit;
}

// Handle Status Toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn = get_db_connection();
    $conn->query("UPDATE newsletter_subscribers SET is_active = NOT is_active WHERE id = $id");
    header("Location: subscribers.php" . ($search ? "?q=$search" : ""));
    exit;
}

// Bulk Broadcaster Logic (Background Queued)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_email') {
    $subject = sanitize_input($_POST['subject']);
    $body = $_POST['body']; // HTML content
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
        $_SESSION['success'] = "Broadcast of " . count($emails) . " emails has been queued!";
    } else {
        $_SESSION['error'] = "No subscribers selected.";
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

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 crimson-pro tracking-tight">Subscribers</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Found <span class="text-black"><?php echo $pagination['total_records']; ?></span> subscribers</p>
    </div>
    
    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
        <form class="relative group flex-1 sm:flex-none">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-black transition-colors text-[10px]"></i>
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search email..." class="w-full bg-white border border-gray-100 rounded-2xl pl-10 pr-4 py-2 text-xs font-bold outline-none focus:border-black shadow-sm transition-all sm:min-w-[200px]">
        </form>
        <div class="flex gap-2">
            <button onclick="openEmailModal()" id="bulkEmailBtn" class="flex-1 sm:flex-none bg-[#19DC7E] text-black px-5 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-emerald-50 hidden items-center justify-center gap-2">
                <i class="fas fa-paper-plane"></i> Send Email (<span id="selectedCountDisplay">0</span>)
            </button>
            <button onclick="exportSubscribers()" class="flex-1 sm:flex-none bg-black text-[#19DC7E] px-5 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-black/5 flex items-center justify-center gap-2">
                <i class="fas fa-file-export"></i> Export
            </button>
        </div>
    </div>
</div>

<div id="selectionBanner" class="hidden bg-black text-white px-6 py-4 rounded-3xl mb-6 anim-up border border-white/5 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-[#19DC7E]">
            <i class="fas fa-check-double text-xs"></i>
        </div>
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-[#19DC7E]">Selection Mode</p>
            <p class="text-xs font-bold" id="selectionText">Items selected.</p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <button onclick="selectAllMatches()" class="text-[9px] font-black text-white hover:text-[#19DC7E] bg-white/5 px-4 py-2 rounded-xl transition-all uppercase tracking-widest" id="selectAllBtn">Select all <?php echo $pagination['total_records']; ?> matches</button>
        <button onclick="resetSelection()" class="text-[9px] font-black text-red-500 uppercase tracking-widest hover:underline">Clear</button>
    </div>
</div>

<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse" id="subscribersTable">
            <thead>
                <tr class="text-gray-400 text-[8px] uppercase bg-gray-50/50 border-b border-gray-100 font-black tracking-widest">
                    <th class="p-5 w-16 text-center">
                        <input type="checkbox" id="selectAllHeader" class="w-4 h-4 rounded border-gray-200 text-black focus:ring-black cursor-pointer">
                    </th>
                    <th class="p-5 pl-0">Email</th>
                    <th class="p-5">Status</th>
                    <th class="p-5 hidden sm:table-cell">Date</th>
                    <th class="p-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-xs text-gray-600">
                <?php if (empty($subscribers)): ?>
                <tr>
                    <td colspan="5" class="p-20 text-center">
                        <i class="fas fa-envelope-open-text text-3xl text-gray-100 mb-4 block"></i>
                        <h3 class="text-xl font-black text-gray-900">Empty List</h3>
                        <p class="text-[10px] text-gray-400 mt-1">No subscribers found here.</p>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($subscribers as $s): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-all group">
                    <td class="p-4 text-center">
                        <input type="checkbox" value="<?php echo $s['id']; ?>" class="subscriber-checkbox w-4 h-4 rounded border-gray-200 text-[#19DC7E] focus:ring-[#19DC7E] cursor-pointer">
                    </td>
                    <td class="p-4 pl-0">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-gray-300 font-black text-[10px] border border-gray-50 group-hover:bg-black group-hover:text-[#19DC7E] transition-all">
                                #<?php echo $s['id']; ?>
                            </div>
                            <div class="font-bold text-gray-900"><?php echo $s['email']; ?></div>
                        </div>
                    </td>
                    <td class="p-4">
                        <a href="?toggle=<?php echo $s['id']; ?><?php echo $search ? '&q='.$search : ''; ?>" class="inline-block">
                            <?php if($s['is_active']): ?>
                                <span class="px-2 py-0.5 rounded-lg text-[8px] font-black uppercase tracking-widest border border-green-100 bg-green-50 text-green-600 flex items-center gap-1.5">
                                    <span class="w-1 h-1 rounded-full bg-green-500"></span> Active
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-lg text-[8px] font-black uppercase tracking-widest border border-gray-100 bg-gray-50 text-gray-400">Inactive</span>
                            <?php endif; ?>
                        </a>
                    </td>
                    <td class="p-4 hidden sm:table-cell">
                        <div class="font-bold text-gray-900"><?php echo date('M d, Y', strtotime($s['subscribed_at'])); ?></div>
                        <div class="text-[9px] text-gray-400 font-medium"><?php echo date('g:i A', strtotime($s['subscribed_at'])); ?></div>
                    </td>
                    <td class="p-4 text-right">
                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                            <a href="?delete=<?php echo $s['id']; ?>" onclick="return confirm('Delete subscriber?')" class="w-8 h-8 bg-black text-white hover:bg-red-500 rounded-lg flex items-center justify-center transition-all">
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

<div id="emailModal" class="fixed inset-0 bg-black/60 hidden z-[200] items-center justify-center p-6 backdrop-blur-sm anim-up">
    <div class="bg-white w-full max-w-lg rounded-[40px] shadow-2xl p-10 relative">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h3 class="text-2xl font-black text-gray-900 crimson-pro">Email</h3>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest text-emerald-500">Sending to <span id="modalTargetCount">0</span> people</p>
            </div>
            <button onclick="closeEmailModal()" class="w-10 h-10 rounded-2xl bg-gray-50 hover:bg-red-50 hover:text-red-500 flex items-center justify-center transition-all">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        
        <form method="POST" id="bulkEmailForm" class="space-y-6">
            <input type="hidden" name="action" value="bulk_email">
            <input type="hidden" name="all_selected" id="inputAllSelected" value="false">
            <div id="selectedIdsContainer"></div>
            
            <div class="space-y-1.5">
                <label class="text-[9px] font-black uppercase tracking-widest text-gray-400 ml-4">Subject</label>
                <input type="text" name="subject" required class="w-full bg-gray-50 border-2 border-transparent rounded-[24px] px-6 py-4 text-sm font-bold outline-none focus:border-black focus:bg-white transition-all shadow-sm" placeholder="Exciting news from Driyum!">
            </div>
            
            <div class="space-y-1.5">
                <label class="text-[9px] font-black uppercase tracking-widest text-gray-400 ml-4">Message (HTML content supported)</label>
                <textarea name="body" required class="w-full bg-gray-50 border-2 border-transparent rounded-[24px] px-6 py-6 text-sm font-medium h-48 resize-none shadow-sm focus:border-black focus:bg-white transition-all" placeholder="Hi there! We have some cool snacks in store for you..."></textarea>
            </div>
            
            <button type="submit" class="w-full bg-[#19DC7E] text-black py-5 rounded-[24px] font-black text-[10px] uppercase tracking-widest shadow-xl shadow-emerald-100 hover:scale-[1.02] active:scale-95 transition-all">
                Send Email
            </button>
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
const allPagesNotice = document.getElementById('all-pages-notice');
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
        bulkEmailBtn.classList.add('flex');
        selectionBanner.classList.remove('hidden');
        selectionBanner.classList.add('flex');

        if (isAllSelectedAcrossPages) {
            selectedCountDisplay.textContent = TOTAL_RECORDS;
            selectionText.textContent = `All ${TOTAL_RECORDS} subscribers selected`;
            selectAllBtn.classList.add('hidden');
        } else {
            selectedCountDisplay.textContent = tracked.size;
            selectionText.textContent = `${tracked.size} selected on this view`;
            
            if (checkedOnPage === onPage && TOTAL_RECORDS > onPage) {
                selectAllBtn.classList.remove('hidden');
            } else {
                selectAllBtn.classList.add('hidden');
            }
        }
    } else {
        bulkEmailBtn.classList.add('hidden');
        selectionBanner.classList.add('hidden');
    }
}

function init() {
    const tracked = getStored();
    subscriberCheckboxes.forEach(cb => {
        if (tracked.has(cb.value)) cb.checked = true;
    });
    updateUI();
}

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
    document.getElementById('emailModal').classList.add('hidden');
    document.getElementById('emailModal').classList.remove('flex');
}

function exportSubscribers() {
    // Basic CSV export logic would go here
    alert('Exporting ' + (isAllSelectedAcrossPages ? TOTAL_RECORDS : getStored().size) + ' subscribers...');
}

init();
</script>

<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
<?php include 'includes/footer.php'; ?>


