<?php include 'includes/header.php'; ?>
<?php
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
    echo "<script>window.location.href='subscribers.php';</script>";
    exit;
}

$search = sanitize_input($_GET['q'] ?? '');
$params = [];
$query = "SELECT * FROM newsletter_subscribers";
if ($search) {
    $query .= " WHERE email LIKE ?";
    $params[] = "%$search%";
}
$query .= " ORDER BY subscribed_at DESC";

$pagination = get_pagination_data($query, $params, 15);
$subscribers = $pagination['records'];

// Handle Status Toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn = get_db_connection();
    $conn->query("UPDATE newsletter_subscribers SET is_active = NOT is_active WHERE id = $id");
    header("Location: subscribers.php" . ($search ? "?q=$search" : ""));
    exit;
}
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

<div class="bg-white rounded-[50px] shadow-2xl border border-gray-100 overflow-hidden anim-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse" id="subscribersTable">
            <thead>
                <tr class="text-gray-400 text-[10px] uppercase bg-gray-50/30 border-b border-gray-100 font-['Outfit']">
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

<script>
function exportSubscribers() {
    let csv = 'ID,Email,Status,Subscribed At\n';
    const rows = document.querySelectorAll('#subscribersTable tbody tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td');
        if (cols.length > 1) {
            const id = cols[0].innerText.replace('#', '');
            const email = cols[1].innerText;
            const status = cols[2].innerText;
            const date = cols[3].innerText;
            csv += `"${id}","${email}","${status}","${date}"\n`;
        }
    });

    const hiddenElement = document.createElement('a');
    hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csv);
    hiddenElement.target = '_blank';
    hiddenElement.download = 'driyum_subscribers_' + new Date().toISOString().split('T')[0] + '.csv';
    hiddenElement.click();
}
</script>

</div>
<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
</main>
</body>
</html>
