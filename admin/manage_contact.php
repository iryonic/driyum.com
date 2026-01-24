<?php
include 'includes/header.php'; 

$conn = get_db_connection();
$msg = "";
$error = "";

// --- HANDLE UPDATES ---
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
            $msg = "Contact information updated successfully!";
        } else {
            $error = "Error updating information.";
        }
    }

    if (isset($_POST['delete_msg'])) {
        $mid = intval($_POST['msg_id']);
        $conn->query("DELETE FROM contact_messages WHERE id = $mid");
        $msg = "Message deleted!";
    }
}

// Fetch Current Data
$info = fetch_one("SELECT * FROM contact_info LIMIT 1");

$query = "SELECT * FROM contact_messages ORDER BY created_at DESC";
$pagination = get_pagination_data($query, [], 10);
$messages = $pagination['records'];
?>

<div class="p-1 md:p-8 bg-[#f8fafc] min-h-screen">
    <!-- Header -->
    <div class="mb-10 max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-4xl font-['Fredoka'] font-black text-gray-900 tracking-tight">Support Desk</h1>
            <p class="text-gray-500 font-medium">Manage how customers reach you and read their messages.</p>
        </div>
        <a href="../contact.php" target="_blank" class="bg-white text-gray-700 px-6 py-3 rounded-2xl font-bold border-2 border-gray-100 hover:border-black transition flex items-center gap-2 self-start">
            <i class="fas fa-external-link-alt text-sm"></i> View Contact Page
        </a>
    </div>

    <?php if($msg): ?>
        <div class="max-w-7xl mx-auto mb-8 animate-bounce">
            <div class="bg-green-500 text-white px-8 py-5 rounded-3xl shadow-xl shadow-green-100 flex items-center justify-between">
                <p class="font-bold"><?php echo $msg; ?></p>
                <button onclick="this.parentElement.parentElement.remove()" class="text-white/60 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-10 pb-20">
        
        <!-- INFO EDITOR -->
        <div class="lg:col-span-5 space-y-10">
            <div class="bg-white rounded-[40px] shadow-xl shadow-gray-100 overflow-hidden border border-gray-50">
                <div class="bg-gray-900 px-8 py-6 text-white">
                    <h3 class="text-xl font-black font-['Fredoka']">Global Info</h3>
                </div>
                <form method="POST" class="p-8 space-y-6">
                    <input type="hidden" name="update_info" value="1">
                    
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">Office Address</label>
                        <textarea name="address" rows="3" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] rounded-2xl px-5 py-3 outline-none font-bold text-sm"><?php echo htmlspecialchars($info['address']); ?></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($info['phone']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] rounded-2xl px-5 py-3 outline-none font-bold text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($info['email']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] rounded-2xl px-5 py-3 outline-none font-bold text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">WhatsApp</label>
                            <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($info['whatsapp']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] rounded-xl px-4 py-2 outline-none font-bold text-xs" placeholder="91...">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">Instagram</label>
                            <input type="text" name="instagram" value="<?php echo htmlspecialchars($info['instagram']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] rounded-xl px-4 py-2 outline-none font-bold text-xs">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">Facebook</label>
                            <input type="text" name="facebook" value="<?php echo htmlspecialchars($info['facebook']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] rounded-xl px-4 py-2 outline-none font-bold text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">Maps Embed Code</label>
                        <textarea name="map_iframe" rows="4" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] rounded-2xl px-5 py-3 outline-none font-mono text-[10px]"><?php echo htmlspecialchars($info['map_iframe']); ?></textarea>
                    </div>

                    <button type="submit" class="w-full bg-black text-[#19DC7E] py-4 rounded-2xl font-black uppercase tracking-widest hover:bg-[#19DC7E] hover:text-black transition-all shadow-lg">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- MESSAGES LIST -->
        <div class="lg:col-span-7">
            <div class="bg-white rounded-[40px] shadow-xl shadow-gray-100 overflow-hidden border border-gray-50 flex flex-col h-full">
                <div class="bg-blue-600 px-8 py-6 text-white flex justify-between items-center">
                    <h3 class="text-xl font-black font-['Fredoka']">Customer Inbox</h3>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-bold uppercase"><?php echo $pagination['total_records']; ?> New</span>
                </div>
                <div class="flex-1 overflow-y-auto p-4 md:p-8 space-y-6 custom-scrollbar">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-20 opacity-30">
                            <i class="fas fa-inbox text-6xl mb-4"></i>
                            <p class="text-2xl font-black">Your inbox is clean!</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach($messages as $m): ?>
                        <div class="bg-gray-50 rounded-[30px] p-6 border border-gray-100 group hover:border-blue-200 transition-colors">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-blue-500 shadow-sm font-black text-xl">
                                        <?php echo strtoupper(substr($m['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-gray-900"><?php echo htmlspecialchars($m['name']); ?></h4>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase"><?php echo date('d M, Y | h:i A', strtotime($m['created_at'])); ?></p>
                                    </div>
                                </div>
                                <form method="POST" onsubmit="return confirm('Delete this message?')">
                                    <input type="hidden" name="delete_msg" value="1">
                                    <input type="hidden" name="msg_id" value="<?php echo $m['id']; ?>">
                                    <button class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-300 hover:bg-red-50 hover:text-red-500 transition-colors"><i class="fas fa-trash-alt text-xs"></i></button>
                                </form>
                            </div>
                            <div class="pl-16">
                                <p class="text-[11px] font-black text-blue-500 uppercase tracking-widest mb-1 italic">Subject: <?php echo htmlspecialchars($m['subject']); ?></p>
                                <p class="text-gray-600 text-sm leading-relaxed mb-4"><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
                                <a href="mailto:<?php echo $m['email']; ?>" class="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-xl text-xs font-bold text-gray-700 border border-gray-100 hover:border-blue-300 transition-colors">
                                    <i class="fas fa-reply"></i> Reply to <?php echo htmlspecialchars($m['email']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="p-4 border-t border-gray-50">
                    <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>
</body>
</html>
