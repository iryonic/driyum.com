<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check Admin (since we aren't including header yet)
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
    } else {
        $_SESSION['error'] = "Something went wrong.";
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

// Handle Bulk Email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_email') {
    // Prevent timeout for large lists
    set_time_limit(0); 
    ignore_user_abort(true);

    $conn = get_db_connection();
    $ids = $_POST['selected_ids'] ?? [];
    $subject = sanitize_input($_POST['subject']);
    $body = $_POST['body']; 
    
    // If "Select All Matches" was chosen
    if (isset($_POST['select_all_matches']) && $_POST['select_all_matches'] === '1') {
        $post_search = sanitize_input($_POST['current_search'] ?? '');
        
        $sql = "SELECT email FROM newsletter_subscribers";
        if ($post_search) {
             $sql .= " WHERE email LIKE ?";
        }
        $stmt = $conn->prepare($sql);
        
        if ($post_search) {
             $param = "%$post_search%";
             $stmt->bind_param("s", $param);
        }
    } else {
        // Standard ID-based selection
        if (empty($ids) || !is_array($ids)) {
             $_SESSION['error'] = "No subscribers selected.";
             header("Location: subscribers.php");
             exit;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $stmt = $conn->prepare("SELECT email FROM newsletter_subscribers WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Batch Insert into Queue to be instant
    $insert_sql = "INSERT INTO email_queue (to_email, subject, body, status) VALUES (?, ?, ?, 'pending')";
    $insert_stmt = $conn->prepare($insert_sql);
    
    if (!$insert_stmt) {
        // Table likely missing, handle gracefully
        $_SESSION['error'] = "Error: 'email_queue' table missing. Please run setup_queue.php or check database.";
        header("Location: subscribers.php");
        exit;
    }
    
    $queued_count = 0;
    
    // Disable autocommit for speed
    $conn->autocommit(FALSE);
    
    while ($row = $result->fetch_assoc()) {
        $insert_stmt->bind_param("sss", $row['email'], $subject, $body);
        $insert_stmt->execute();
        $queued_count++;
    }
    
    $conn->commit();
    $conn->autocommit(TRUE);
    
    $conn->close();
    
    // Trigger Background Process
    // Absolute path is safer
    $script_path = __DIR__ . '/../process_queue.php';

    if (file_exists($script_path)) {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B php \"$script_path\"", "r"));
        } else {
             // Linux Production
             // Use nohup or simple &
            exec("php \"$script_path\" > /dev/null 2>&1 &");
        }
    } else {
         error_log("Queue Error: process_queue.php not found at $script_path");
    }

    $_SESSION['success'] = "Emails have been queued for sending to $queued_count subscribers. Delivery will happen in the background.";
    header("Location: subscribers.php" . ($search ? "?q=$search" : ""));
    exit;
}

// NOW include the header which outputs HTML
include 'includes/header.php';

// Prepare Data for View
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

<div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 anim-up">
    <div>
        <h1 class="text-4xl font-black text-gray-900 fredoka mb-2">Subscribers List.</h1>
        <div class="flex items-center gap-4">
            <p class="text-gray-500 font-medium font-['Outfit'] italic">Managing your growing newsletter family.</p>
            <div class="h-4 w-px bg-gray-200"></div>
            <span class="text-[10px] font-black uppercase tracking-widest text-[#19DC7E] bg-[#19DC7E]/10 px-3 py-1 rounded-full"><?php echo $pagination['total_records']; ?> Active Subscribers</span>
        </div>
    </div>
    <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
        <form class="relative group">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by email..." 
                   class="bg-white border-2 border-gray-100 rounded-2xl px-6 py-4 pl-12 outline-none focus:border-[#19DC7E] transition-all font-bold text-xs w-full sm:w-64 shadow-sm group-hover:shadow-md">
            <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-[#19DC7E] transition-colors"></i>
            <?php if($search): ?>
                <a href="subscribers.php" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-red-500 transition-colors"><i class="fas fa-times-circle"></i></a>
            <?php endif; ?>
        </form>
        <button onclick="openEmailModal()" id="bulkEmailBtn" class="btn-chunky bg-[#19DC7E] text-[#111827] px-8 py-4 rounded-2xl font-black font-['Outfit'] shadow-2xl hover:scale-105 transition border-none items-center gap-2 text-xs uppercase tracking-widest leading-none hidden">
            <i class="fas fa-paper-plane"></i> Send Email (<span id="selectedCount">0</span>)
        </button>
        <button onclick="exportSubscribers()" class="btn-chunky bg-[#111827] text-white px-8 py-4 rounded-2xl font-black font-['Outfit'] shadow-2xl hover:scale-105 transition border-none flex items-center gap-2 text-xs uppercase tracking-widest leading-none">
            <i class="fas fa-file-export"></i> Export 
        </button>
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

<div id="selectionBanner" class="hidden bg-[#111827] text-white p-4 text-center text-xs font-bold uppercase tracking-widest border-b border-gray-100">
    <span id="selectionText">All <?php echo count($subscribers); ?> subscribers on this page are selected.</span>
    <button onclick="selectAllMatches()" class="ml-4 text-[#19DC7E] hover:underline" id="selectAllBtn">Select all <?php echo $pagination['total_records']; ?> subscribers matching search?</button>
    <button onclick="clearSelection()" class="ml-4 text-gray-400 hover:text-white"><i class="fas fa-times"></i> Clear</button>
</div>

<div class="bg-white rounded-[50px] shadow-2xl border border-gray-100 overflow-hidden anim-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse" id="subscribersTable">
            <thead>
                <tr class="text-gray-400 text-[10px] uppercase bg-gray-50/30 border-b border-gray-100 font-['Outfit']">
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">
                        <input type="checkbox" id="selectAll" onclick="toggleAll(this)" class="w-4 h-4 rounded border-gray-300 text-[#19DC7E] focus:ring-[#19DC7E]">
                    </th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Identity</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Email Address</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Status Check</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40">Enlisted On</th>
                    <th class="p-10 font-black tracking-[0.2em] opacity-40 text-right">Moderation</th>
                </tr>
            </thead>
            <tbody class="text-sm font-['Outfit'] text-gray-600">
                <?php if (empty($subscribers)): ?>
                <tr>
                    <td colspan="5" class="p-24 text-center">
                        <div class="w-24 h-24 bg-gray-50 rounded-[35px] flex items-center justify-center mx-auto mb-8 shadow-inner border border-gray-100">
                            <i class="fas fa-envelope-open-text text-gray-200 text-4xl"></i>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 fredoka mb-2">The List is Empty.</h3>
                        <p class="text-gray-400 font-medium font-['Outfit']">Subscribers will appear here once they join the newsletter.</p>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($subscribers as $s): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-all duration-300 group">
                    <td class="p-10">
                        <input type="checkbox" name="subscriber_ids[]" value="<?php echo $s['id']; ?>" onclick="toggleManual(this)" class="sub-checkbox w-4 h-4 rounded border-gray-300 text-[#19DC7E] focus:ring-[#19DC7E]">
                    </td>
                    <td class="p-10">
                        <div class="w-14 h-14 bg-white border-2 border-dashed border-gray-100 rounded-[20px] flex flex-col items-center justify-center text-gray-300 group-hover:border-[#19DC7E] group-hover:text-[#19DC7E] transition-colors shadow-sm">
                            <span class="text-[9px] font-black uppercase">UID</span>
                            <span class="font-black text-lg leading-none">#<?php echo $s['id']; ?></span>
                        </div>
                    </td>
                    <td class="p-10">
                        <div class="font-black text-xl text-gray-900 mb-1 group-hover:text-[#19DC7E] transition-colors"><?php echo $s['email']; ?></div>
                        <div class="text-[10px] font-black text-gray-300 uppercase tracking-widest font-['Outfit']">Verified Subscriber</div>
                    </td>
                    <td class="p-10">
                        <a href="?toggle=<?php echo $s['id']; ?><?php echo $search ? '&q='.$search : ''; ?>" class="inline-block">
                            <?php if($s['is_active']): ?>
                                <div class="flex items-center gap-3 group/status">
                                    <div class="w-2.5 h-2.5 rounded-full bg-[#19DC7E] shadow-[0_0_10px_#19DC7E]"></div>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-900 group-hover/status:text-[#19DC7E] transition-colors">Active </span>
                                </div>
                            <?php else: ?>
                                <div class="flex items-center gap-3 group/status">
                                    <div class="w-2.5 h-2.5 rounded-full bg-gray-200"></div>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-300 group-hover/status:text-gray-900 transition-colors">Inactive</span>
                                </div>
                            <?php endif; ?>
                        </a>
                    </td>
                    <td class="p-10">
                        <div class="font-black text-gray-600 text-lg mb-1 leading-none"><?php echo date('M d, Y', strtotime($s['subscribed_at'])); ?></div>
                        <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest font-['Outfit']"><?php echo date('g:i A', strtotime($s['subscribed_at'])); ?></span>
                    </td>
                    <td class="p-10 text-right">
                        <div class="flex justify-end translate-x-4 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-500">
                            <a href="?delete=<?php echo $s['id']; ?>" onclick="return confirm('Delete this subscriber?')" class="w-14 h-14 bg-red-50 text-red-500 hover:bg-red-600 hover:text-white flex items-center justify-center rounded-[20px] transition-all shadow-sm active:scale-90">
                                <i class="fas fa-user-minus text-lg"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="emailModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center backdrop-blur-sm opacity-0 transition-opacity duration-300">
    <div class="bg-white w-full max-w-2xl rounded-[40px] shadow-2xl p-10 transform scale-95 transition-transform duration-300" id="emailModalContent">
        <div class="flex justify-between items-center mb-8">
            <h3 class="text-2xl font-black text-gray-900 fredoka">Compose Email</h3>
            <button onclick="closeEmailModal()" class="w-10 h-10 rounded-full bg-gray-50 hover:bg-red-50 hover:text-red-500 flex items-center justify-center transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="subscribers.php" id="bulkEmailForm">
            <input type="hidden" name="action" value="bulk_email">
            <input type="hidden" name="current_search" value="<?php echo htmlspecialchars($search); ?>">
            <input type="hidden" name="select_all_matches" id="inputSelectAllMatches" value="0">
            <div id="hiddenIdsInput"></div>
            
            <div class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Subject Line</label>
                    <input type="text" name="subject" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[20px] px-6 py-4 outline-none transition-all font-bold shadow-sm" placeholder="Exciting News inside...">
                </div>
                
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Message Body (HTML Allowed)</label>
                    <textarea name="body" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[32px] px-6 py-6 outline-none transition-all font-medium h-64 resize-none shadow-sm placeholder-gray-400" placeholder="<h3>Hello There!</h3><p>We have some great updates for you...</p>"></textarea>
                </div>
                
                <div class="pt-4 flex justify-end gap-4">
                    <button type="button" onclick="closeEmailModal()" class="px-8 py-4 rounded-2xl font-bold bg-gray-100 text-gray-500 hover:bg-gray-200 transition">Cancel</button>
                    <button type="submit" class="px-10 py-4 rounded-2xl font-black bg-[#19DC7E] text-[#111827] shadow-lg hover:scale-105 transition-transform flex items-center gap-3">
                        <i class="fas fa-paper-plane"></i> Send Email
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function exportSubscribers() {
    let csv = 'ID,Email,Status,Subscribed At\n';
    const rows = document.querySelectorAll('#subscribersTable tbody tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td');
        // Adjusted column index because of the new checkbox column
        if (cols.length > 2) {
            const id = cols[1].innerText.replace('UID\n#', '').trim();
            const email = cols[2].innerText.split('\n')[0];
            const status = cols[3].innerText.trim();
            const date = cols[4].innerText.split('\n')[0];
            csv += `"${id}","${email}","${status}","${date}"\n`;
        }
    });

    const hiddenElement = document.createElement('a');
    hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csv);
    hiddenElement.target = '_blank';
    hiddenElement.download = 'driyum_subscribers_' + new Date().toISOString().split('T')[0] + '.csv';
    hiddenElement.click();
}

// Bulk Action Logic with Cross-Page capabilities
let isGlobalSelection = false;
const STORAGE_KEY = 'driyum_subscriber_selection';

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    restoreSelection();
});

function getStoredSelection() {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored ? JSON.parse(stored) : [];
}

function saveSelection(ids) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
    updateBulkUI();
}

function restoreSelection() {
    // If we were in global mode, maybe we should clear it or restore it? 
    // For safety/simplicity, let's assume global mode resets on reload but manual selection persists.
    // If you wanted global mode to persist, you'd store that flag too.
    
    const selectedIds = getStoredSelection();
    const checkboxes = document.querySelectorAll('.sub-checkbox');
    
    checkboxes.forEach(cb => {
        if (selectedIds.includes(cb.value)) {
            cb.checked = true;
        }
    });
    
    updateBulkUI();
}

function toggleManual(checkbox) {
    let selectedIds = getStoredSelection();
    const id = checkbox.value;
    
    if (checkbox.checked) {
        if (!selectedIds.includes(id)) selectedIds.push(id);
    } else {
        selectedIds = selectedIds.filter(item => item !== id);
        // If user manually unchecks something, we definitely aren't in global mode anymore
        if (isGlobalSelection) disableGlobalMode();
    }
    
    saveSelection(selectedIds);
}

function toggleAll(source) {
    let selectedIds = getStoredSelection();
    const checkboxes = document.querySelectorAll('.sub-checkbox');
    
    checkboxes.forEach(cb => {
        cb.checked = source.checked;
        const id = cb.value;
        
        if (source.checked) {
            if (!selectedIds.includes(id)) selectedIds.push(id);
        } else {
            selectedIds = selectedIds.filter(item => item !== id);
        }
    });
    
    if (!source.checked && isGlobalSelection) {
        disableGlobalMode();
    }
    
    saveSelection(selectedIds);
    
    // Check for "Select Global" opportunity
    checkGlobalOpportunity(source.checked);
}

function checkGlobalOpportunity(isChecked) {
    const banner = document.getElementById('selectionBanner');
    const totalRecords = <?php echo $pagination['total_records']; ?>;
    const currentOnPage = document.querySelectorAll('.sub-checkbox').length;
    
    // Only show banner if we selected all on THIS page, and there are more total
    if (isChecked && totalRecords > currentOnPage) {
        banner.classList.remove('hidden');
    } else {
        // Don't hide immediately if it was already global, let clearSelection handle that
        if (!isGlobalSelection) banner.classList.add('hidden');
    }
}

function selectAllMatches() {
    isGlobalSelection = true;
    document.getElementById('inputSelectAllMatches').value = '1';
    
    // Update banner text
    const totalRecords = <?php echo $pagination['total_records']; ?>;
    document.getElementById('selectionText').innerHTML = `All <span class="text-[#19DC7E]">${totalRecords}</span> subscribers are selected.`;
    document.getElementById('selectAllBtn').classList.add('hidden');
    
    // For UI feedback, check all visible boxes (visual only, logic handled by global flag)
    document.querySelectorAll('.sub-checkbox').forEach(cb => cb.checked = true);
    document.getElementById('selectAll').checked = true;
    
    updateBulkUI();
}

function disableGlobalMode() {
    isGlobalSelection = false;
    document.getElementById('inputSelectAllMatches').value = '0';
    document.getElementById('selectionBanner').classList.add('hidden');
    document.getElementById('selectAllBtn').classList.remove('hidden'); // Reset for next time
    document.getElementById('selectionText').textContent = "All <?php echo count($subscribers); ?> subscribers on this page are selected.";
}

function clearSelection() {
    localStorage.removeItem(STORAGE_KEY);
    document.querySelectorAll('.sub-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    disableGlobalMode();
    updateBulkUI();
}

function updateBulkUI() {
    const btn = document.getElementById('bulkEmailBtn');
    const countSpan = document.getElementById('selectedCount');
    
    if (isGlobalSelection) {
        const totalRecords = <?php echo $pagination['total_records']; ?>;
        btn.classList.remove('hidden');
        btn.classList.add('flex');
        countSpan.textContent = totalRecords;
        return;
    }
    
    const selectedIds = getStoredSelection();
    
    if (selectedIds.length > 0) {
        btn.classList.remove('hidden');
        btn.classList.add('flex');
        countSpan.textContent = selectedIds.length;
    } else {
        btn.classList.add('hidden');
        btn.classList.remove('flex');
    }
}

// Modal Logic
const modal = document.getElementById('emailModal');
const modalContent = document.getElementById('emailModalContent');

function openEmailModal() {
    modal.classList.remove('hidden');
    void modal.offsetWidth; // Force reflow
    modal.classList.remove('opacity-0');
    modalContent.classList.remove('scale-95');
    modalContent.classList.add('scale-100');
    
    // Populate hidden inputs
    const container = document.getElementById('hiddenIdsInput');
    container.innerHTML = '';

    if (!isGlobalSelection) {
        const selectedIds = getStoredSelection();
        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_ids[]';
            input.value = id;
            container.appendChild(input);
        });
    }
}

function closeEmailModal() {
    modal.classList.add('opacity-0');
    modalContent.classList.remove('scale-100');
    modalContent.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
</script>

</div>
<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
</main>
</body>
</html>
