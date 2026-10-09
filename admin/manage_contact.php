<?php
include 'includes/header.php'; 

$conn = get_db_connection();
$msg = "";
$msg_type = "success";

// Active Tab
$active_tab = $_GET['tab'] ?? 'inbox';
$search = trim($_GET['q'] ?? '');

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_info'])) {
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $instagram = trim($_POST['instagram'] ?? '');
        $facebook = trim($_POST['facebook'] ?? '');
        $map_iframe = trim($_POST['map_iframe'] ?? '');

        $stmt = $conn->prepare("UPDATE contact_info SET address=?, phone=?, email=?, whatsapp=?, instagram=?, facebook=?, map_iframe=? LIMIT 1");
        $stmt->bind_param("sssssss", $address, $phone, $email, $whatsapp, $instagram, $facebook, $map_iframe);
        
        if ($stmt->execute()) {
            $msg = "Contact details and communication channels updated successfully.";
            $msg_type = "success";
            $active_tab = 'channels';
        } else {
            $msg = "Failed to update contact information. Please check database logs.";
            $msg_type = "error";
            $active_tab = 'channels';
        }
    }

    if (isset($_POST['delete_msg'])) {
        $mid = intval($_POST['msg_id'] ?? 0);
        if ($mid > 0) {
            $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->bind_param("i", $mid);
            if ($stmt->execute()) {
                $msg = "Message #{$mid} deleted permanently.";
                $msg_type = "success";
                $active_tab = 'inbox';
            }
        }
    }
}

// Fetch Data
$info = fetch_one("SELECT * FROM contact_info LIMIT 1") ?: [
    'address' => '', 'phone' => '', 'email' => '', 
    'whatsapp' => '', 'instagram' => '', 'facebook' => '', 'map_iframe' => ''
];

$where = "WHERE 1=1";
$params = [];
if ($search !== '') {
    $where .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $s_term = "%$search%";
    $params = [$s_term, $s_term, $s_term, $s_term];
}

$query = "SELECT * FROM contact_messages $where ORDER BY created_at DESC";
$pagination = get_pagination_data($query, $params, 10);
$messages = $pagination['records'];

// Total Messages Count for Header
$total_all_messages = fetch_one("SELECT COUNT(*) as count FROM contact_messages")['count'] ?? 0;
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-envelope-open-text text-emerald-700 text-xl"></i>
             Contact Messages & Settings
            </h1>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="../contact.php" target="_blank" class="btn-admin-secondary text-xs inline-flex items-center gap-2">
                <i class="fas fa-external-link-alt text-slate-400"></i> View Live Contact Page
            </a>
        </div>
    </div>

    <!-- Flash Alert -->
    <?php if ($msg): ?>
        <div class="p-4 rounded-xl border flex items-center justify-between <?php echo $msg_type === 'error' ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-emerald-50 border-emerald-200 text-emerald-900'; ?>">
            <div class="flex items-center gap-3">
                <i class="fas <?php echo $msg_type === 'error' ? 'fa-exclamation-circle text-rose-600' : 'fa-check-circle text-emerald-600'; ?>"></i>
                <span class="text-sm font-medium"><?php echo htmlspecialchars($msg); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <div class="flex items-center border-b border-slate-200 gap-2 whitespace-nowrap overflow-x-auto">
        <a href="?tab=inbox<?php echo $search ? '&q=' . urlencode($search) : ''; ?>" class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition-all <?php echo $active_tab === 'inbox' ? 'border-[#004f42] text-[#004f42]' : 'border-transparent text-slate-500 hover:text-slate-800'; ?>">
            <i class="fas fa-inbox text-xs"></i>
            <span>Customer Inquiries</span>
            <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] <?php echo $active_tab === 'inbox' ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-100 text-slate-600'; ?>">
                <?php echo number_format($total_all_messages); ?>
            </span>
        </a>
        <a href="?tab=channels" class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition-all <?php echo $active_tab === 'channels' ? 'border-[#004f42] text-[#004f42]' : 'border-transparent text-slate-500 hover:text-slate-800'; ?>">
            <i class="fas fa-address-book text-xs"></i>
            <span>Public Store Coordinates & Channels</span>
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
        </a>
    </div>

    <!-- TAB 1: CUSTOMER INQUIRIES INBOX -->
    <?php if ($active_tab === 'inbox'): ?>
        <div class="space-y-4">
            <!-- Search & Filter Bar -->
            <div class="admin-card p-4">
                <form method="GET" action="manage_contact.php" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                    <input type="hidden" name="tab" value="inbox">
                    <div class="relative flex-1 max-w-md">
                        <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by customer name, email, or keywords..." class="admin-input pl-9 text-xs">
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn-admin-primary text-xs py-1.5 px-3">
                            <i class="fas fa-filter text-[10px]"></i> Search
                        </button>
                        <?php if ($search): ?>
                            <a href="manage_contact.php?tab=inbox" class="btn-admin-secondary text-xs py-1.5 px-3" title="Clear Search">
                                <i class="fas fa-times text-[10px]"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Messages Stream -->
            <div class="admin-card overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-slate-800">Inquiry Messages</h3>
                        <?php if ($search): ?>
                            <span class="text-xs text-slate-500 font-medium">
                                (Matching "<strong><?php echo htmlspecialchars($search); ?></strong>")
                            </span>
                        <?php endif; ?>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">
                        Showing <?php echo count($messages); ?> of <?php echo number_format($pagination['total_records']); ?>
                    </span>
                </div>

                <div class="p-5 space-y-4">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-16 px-4">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-800">No Inquiries Found</h4>
                            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                <?php echo $search ? 'No messages matched your search query. Try clearing your search keyword.' : 'When customers reach out through your website contact form, their inquiries will appear here.'; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach($messages as $m): ?>
                            <div class="p-4 rounded-xl border border-slate-200 hover:border-slate-300 transition-all bg-white shadow-xs">
                                <div class="flex items-start justify-between gap-3 mb-2.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-sm uppercase shrink-0 border border-emerald-200">
                                            <?php echo htmlspecialchars(substr($m['name'] ?: 'U', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h4 class="font-bold text-slate-900 text-sm">
                                                    <?php echo htmlspecialchars($m['name']); ?>
                                                </h4>
                                                <?php if (!empty($m['email'])): ?>
                                                    <span class="text-xs text-slate-500 font-normal">
                                                        &lt;<?php echo htmlspecialchars($m['email']); ?>&gt;
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-0.5">
                                                <span><i class="far fa-clock mr-1"></i><?php echo date('d M Y, h:i A', strtotime($m['created_at'])); ?></span>
                                                <span>•</span>
                                                <span>Ref #<?php echo $m['id']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <form method="POST" onsubmit="return confirm('Permanently delete this inquiry from <?php echo htmlspecialchars(addslashes($m['name'])); ?>?');">
                                        <input type="hidden" name="delete_msg" value="1">
                                        <input type="hidden" name="msg_id" value="<?php echo $m['id']; ?>">
                                        <button type="submit" title="Delete Message" class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors flex items-center justify-center text-xs">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>

                                <?php if (!empty($m['subject'])): ?>
                                    <div class="mb-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                            Subject: <?php echo htmlspecialchars($m['subject']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="bg-slate-50/80 p-3.5 rounded-lg border border-slate-200/80 text-slate-700 text-xs leading-relaxed mb-3 whitespace-pre-wrap font-sans">
                                    <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                                </div>

                                <div class="flex items-center justify-between pt-1">
                                    <?php if (!empty($m['email'])): ?>
                                        <div class="flex items-center gap-2">
                                            <a href="mailto:<?php echo htmlspecialchars($m['email']); ?>?subject=Re: <?php echo urlencode($m['subject'] ?: 'Your inquiry at DRIYUM'); ?>" class="btn-admin-secondary text-xs py-1.5 px-3 inline-flex items-center gap-1.5">
                                                <i class="fas fa-reply text-slate-500"></i> Reply via Email
                                            </a>
                                            <button type="button" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars(addslashes($m['email'])); ?>'); alert('Copied <?php echo htmlspecialchars($m['email']); ?> to clipboard!');" class="text-xs text-slate-400 hover:text-slate-600 px-2 py-1">
                                                <i class="far fa-copy mr-1"></i> Copy Email
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">No email provided</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                        <?php echo render_pagination($pagination['total_pages'], $pagination['current_page'], 10, $pagination['total_records']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <!-- TAB 2: STORE COORDINATES & CHANNELS -->
    <?php else: ?>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="update_info" value="1">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Left 7 cols: Coordinates & Social -->
                <div class="lg:col-span-7 space-y-6">
                    
                    <!-- Card 1: Official Business Coordinates -->
                    <div class="admin-card p-5 space-y-4">
                        <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-800 flex items-center justify-center text-sm font-semibold">
                                <i class="fas fa-store"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Physical Location & Contact</h3>
                                <p class="text-xs text-slate-500">Official business coordinates for storefront footer & contact page</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Storefront Address / Facility Location <span class="text-rose-500">*</span>
                            </label>
                            <textarea name="address" rows="3" required class="admin-input text-xs" placeholder="Baghi Mehtab, Srinagar, Jammu & Kashmir 190019"><?php echo htmlspecialchars($info['address']); ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    <i class="fas fa-phone-alt text-slate-400 mr-1"></i> Support Phone
                                </label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($info['phone']); ?>" class="admin-input text-xs" placeholder="+91 9419809801">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    <i class="fas fa-envelope text-slate-400 mr-1"></i> Official Email
                                </label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($info['email']); ?>" class="admin-input text-xs" placeholder="care@driyum.com">
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Messaging & Social Networks -->
                    <div class="admin-card p-5 space-y-4">
                        <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-800 flex items-center justify-center text-sm font-semibold">
                                <i class="fas fa-share-alt"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Direct Messaging & Social Pages</h3>
                                <p class="text-xs text-slate-500">Live channels displayed in footer navigation and contact cards</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    <i class="fab fa-whatsapp text-emerald-600 mr-1"></i> WhatsApp Support Number
                                </label>
                                <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($info['whatsapp']); ?>" class="admin-input text-xs" placeholder="+91 9419809801">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    <i class="fab fa-instagram text-rose-500 mr-1"></i> Instagram Handle / Profile URL
                                </label>
                                <input type="text" name="instagram" value="<?php echo htmlspecialchars($info['instagram']); ?>" class="admin-input text-xs" placeholder="https://instagram.com/driyum">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    <i class="fab fa-facebook text-blue-600 mr-1"></i> Facebook Page URL
                                </label>
                                <input type="text" name="facebook" value="<?php echo htmlspecialchars($info['facebook']); ?>" class="admin-input text-xs" placeholder="https://facebook.com/driyum">
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn-admin-primary text-xs py-2.5 px-6">
                            <i class="fas fa-save mr-1.5"></i> Save All Changes
                        </button>
                    </div>

                </div>

                <!-- Right 5 cols: Google Map Embed with Live Preview -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="admin-card p-5 space-y-4">
                        <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                            <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-800 flex items-center justify-center text-sm font-semibold">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Location Map (Google Maps)</h3>
                                <p class="text-xs text-slate-500">Embed your official store pin for customers</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Google Maps Embed Iframe Code
                            </label>
                            <textarea name="map_iframe" rows="4" class="admin-input font-mono text-[11px] leading-relaxed" placeholder="<iframe src='https://www.google.com/maps/embed?...'></iframe>"><?php echo htmlspecialchars($info['map_iframe']); ?></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Go to Google Maps, search your address, click "Share" &rarr; "Embed a map", and paste the iframe tag here.
                            </p>
                        </div>

                        <!-- Live Map Preview Box -->
                        <div class="pt-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Live Storefront Map Preview</label>
                            <div class="rounded-xl border border-slate-200 overflow-hidden bg-slate-100 h-64 flex items-center justify-center text-center p-2 relative shadow-inner">
                                <?php if (!empty($info['map_iframe'])): ?>
                                    <div class="w-full h-full [&>iframe]:w-full [&>iframe]:h-full [&>iframe]:border-0 rounded-lg overflow-hidden">
                                        <?php echo $info['map_iframe']; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-slate-400 p-4">
                                        <i class="fas fa-map-marked text-3xl mb-2 block text-slate-300"></i>
                                        <span class="text-xs font-medium">No Google Map iframe provided yet. Paste an iframe embed tag to verify pin placement.</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
