<?php
include 'includes/header.php'; 

$conn = get_db_connection();
$msg = "";

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_info'])) {
        $address = $_POST['address'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $whatsapp = $_POST['whatsapp'] ?? '';
        $instagram = $_POST['instagram'] ?? '';
        $facebook = $_POST['facebook'] ?? '';
        $map_iframe = $_POST['map_iframe'] ?? '';

        $stmt = $conn->prepare("UPDATE contact_info SET address=?, phone=?, email=?, whatsapp=?, instagram=?, facebook=?, map_iframe=? LIMIT 1");
        $stmt->bind_param("sssssss", $address, $phone, $email, $whatsapp, $instagram, $facebook, $map_iframe);
        
        if ($stmt->execute()) {
            $msg = "Contact details updated!";
        }
    }

    if (isset($_POST['delete_msg'])) {
        $mid = intval($_POST['msg_id']);
        $conn->query("DELETE FROM contact_messages WHERE id = $mid");
        $msg = "Message deleted!";
    }
}

// Fetch Data
$info = fetch_one("SELECT * FROM contact_info LIMIT 1");
$query = "SELECT * FROM contact_messages ORDER BY created_at DESC";
$pagination = get_pagination_data($query, [], 10);
$messages = $pagination['records'];
?>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 crimson-pro tracking-tight">Inquiries</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Found <span class="text-black"><?php echo $pagination['total_records']; ?></span> customer messages</p>
    </div>
    <a href="../contact.php" target="_blank" class="bg-black text-[#19DC7E] px-6 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg flex items-center gap-2">
        <i class="fas fa-external-link-alt"></i> View Contact Page
    </a>
</div>

<?php if($msg): ?>
    <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-2xl border border-green-100 font-bold text-xs anim-up">
        <i class="fas fa-check-circle mr-2"></i> <?php echo $msg; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pb-20">
    
    <!-- Company Info -->
    <div class="lg:col-span-5 space-y-8">
        <div class="bg-white rounded-[32px] shadow-sm border border-gray-100 overflow-hidden anim-up">
            <div class="bg-gray-50 px-8 py-5 border-b border-gray-100">
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-900">About Company</h3>
            </div>
            <form method="POST" class="p-8 space-y-6">
                <input type="hidden" name="update_info" value="1">
                
                <div>
                    <label class="block text-[9px] font-black uppercase text-gray-400 mb-2 tracking-widest">Address</label>
                    <textarea name="address" rows="3" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-2xl px-5 py-3 outline-none font-bold text-sm shadow-sm transition-all"><?php echo htmlspecialchars($info['address']); ?></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[9px] font-black uppercase text-gray-400 mb-2 tracking-widest">Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($info['phone']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-2xl px-5 py-3 outline-none font-bold text-sm shadow-sm transition-all">
                    </div>
                    <div>
                        <label class="block text-[9px] font-black uppercase text-gray-400 mb-2 tracking-widest">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($info['email']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-2xl px-5 py-3 outline-none font-bold text-sm shadow-sm transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[9px] font-black uppercase text-gray-400 mb-2 tracking-widest">WhatsApp</label>
                        <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($info['whatsapp']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-xl px-4 py-2 outline-none font-bold text-xs shadow-sm transition-all">
                    </div>
                    <div>
                        <label class="block text-[9px] font-black uppercase text-gray-400 mb-2 tracking-widest">Instagram</label>
                        <input type="text" name="instagram" value="<?php echo htmlspecialchars($info['instagram']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-xl px-4 py-2 outline-none font-bold text-xs shadow-sm transition-all">
                    </div>
                    <div>
                        <label class="block text-[9px] font-black uppercase text-gray-400 mb-2 tracking-widest">Facebook</label>
                        <input type="text" name="facebook" value="<?php echo htmlspecialchars($info['facebook']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-xl px-4 py-2 outline-none font-bold text-xs shadow-sm transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-[9px] font-black uppercase text-gray-400 mb-2 tracking-widest">Google Maps (Embed)</label>
                    <textarea name="map_iframe" rows="3" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-2xl px-5 py-3 outline-none font-mono text-[10px] shadow-sm transition-all"><?php echo htmlspecialchars($info['map_iframe']); ?></textarea>
                </div>

                <button type="submit" class="w-full bg-black text-[#19DC7E] py-4 rounded-[20px] font-black uppercase tracking-widest hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-black/5">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Messages -->
    <div class="lg:col-span-7">
        <div class="bg-white rounded-[32px] shadow-sm border border-gray-100 overflow-hidden anim-up flex flex-col h-full">
            <div class="bg-gray-50 px-8 py-5 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-900">Messages</h3>
            </div>
            <div class="flex-1 p-6 md:p-8 space-y-6">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-20 opacity-10">
                        <i class="fas fa-inbox text-6xl mb-4"></i>
                        <p class="text-xl font-black">No new messages.</p>
                    </div>
                <?php endif; ?>

                <?php foreach($messages as $m): ?>
                    <div class="bg-gray-50/50 rounded-3xl p-6 border border-gray-100 hover:border-black transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-white rounded-xl shadow-sm border border-gray-50 flex items-center justify-center text-black font-black text-xs group-hover:bg-black group-hover:text-[#19DC7E] transition-all">
                                    <?php echo strtoupper(substr($m['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h4 class="font-black text-gray-900 text-sm"><?php echo htmlspecialchars($m['name']); ?></h4>
                                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest"><?php echo date('d M, Y', strtotime($m['created_at'])); ?></p>
                                </div>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete message?')">
                                <input type="hidden" name="delete_msg" value="1">
                                <input type="hidden" name="msg_id" value="<?php echo $m['id']; ?>">
                                <button class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-300 hover:bg-red-500 hover:text-white transition-all"><i class="fas fa-trash-alt text-[10px]"></i></button>
                            </form>
                        </div>
                        <div class="pl-14">
                            <p class="text-[9px] font-black text-black uppercase tracking-widest mb-1 italic">Sub: <?php echo htmlspecialchars($m['subject']); ?></p>
                            <p class="text-gray-600 text-xs leading-relaxed mb-4"><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
                            <a href="mailto:<?php echo $m['email']; ?>" class="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-gray-700 border border-gray-100 hover:bg-black hover:text-[#19DC7E] transition-all">
                                <i class="fas fa-reply"></i> Reply to <?php echo htmlspecialchars($m['email']); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>


