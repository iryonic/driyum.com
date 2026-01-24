<?php
include 'includes/header.php';

$conn = get_db_connection(); // Initialize connection for transactions

// Handle Actions
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    execute_query("UPDATE affiliates SET is_approved = 1, status = 'active' WHERE id = ?", [$id]);
    echo "<script>window.location.href='affiliates.php';</script>";
}

if (isset($_GET['suspend'])) {
    $id = (int)$_GET['suspend'];
    execute_query("UPDATE affiliates SET status = 'suspended' WHERE id = ?", [$id]);
    echo "<script>window.location.href='affiliates.php';</script>";
}

// Handle Manual Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_influencer'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $code = sanitize_input($_POST['code']);
    $commission = (float)$_POST['commission'];
    
    $conn->begin_transaction();
    try {
        // 1. Check/Create User
        $user = fetch_one("SELECT id FROM users WHERE email = ?", [$email]);
        $user_id = null;
        
        if ($user) {
            $user_id = $user['id'];
        } else {
            // Create new user with default password 'welcome123'
            $password = password_hash('welcome123', PASSWORD_DEFAULT);
            execute_query("INSERT INTO users (name, email, phone, password, is_active) VALUES (?, ?, ?, ?, 1)", [$name, $email, $phone, $password]);
            $user_id = get_last_insert_id();
        }
        
        // 2. Check if already affiliate
        $exists_aff = fetch_one("SELECT id FROM affiliates WHERE user_id = ?", [$user_id]);
        if ($exists_aff) {
            throw new Exception("User is already an affiliate.");
        }
        
        // 3. Check Code availability
        $exists_code = fetch_one("SELECT id FROM affiliates WHERE code = ?", [$code]);
        if ($exists_code) {
             throw new Exception("Handle/Code '$code' is already taken.");
        }
        
        // 4. Create Affiliate Profile
        execute_query(
            "INSERT INTO affiliates (user_id, code, commission_rate, discount_percentage, is_approved, status) VALUES (?, ?, ?, 10.00, 1, 'active')", 
            [$user_id, $code, $commission]
        );
        
        $conn->commit();
        echo "<script>alert('Influencer added successfully! Account Code: $code'); window.location.href='affiliates.php';</script>";
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Fetch Affiliates
$query = "
    SELECT a.*, u.name, u.email, u.phone
    FROM affiliates a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC
";
$pagination = get_pagination_data($query, [], 10);
$affiliates = $pagination['records'];
?>

<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-['Fredoka'] font-bold text-gray-900">Creator Partnerships</h1>
        <p class="text-gray-500 text-sm">Manage influencers and affiliate partners.</p>
    </div>
    <button onclick="document.getElementById('add-modal').classList.remove('hidden')" class="btn-chunky bg-black text-white px-6 py-3 shadow-lg hover:bg-[#19DC7E] hover:text-black transition">
        <i class="fas fa-plus-circle mr-2"></i> Add Influencer
    </button>
</div>

<!-- ADD MODAL -->
<div id="add-modal" class="fixed inset-0 bg-black/80 z-[100] hidden flex items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-white rounded-[40px] p-8 max-w-md w-full shadow-2xl relative anim-up">
        <button type="button" onclick="document.getElementById('add-modal').classList.add('hidden')" class="absolute top-6 right-6 text-gray-400 hover:text-black">
            <i class="fas fa-times text-xl"></i>
        </button>
        
        <h2 class="text-2xl font-black font-['Fredoka'] mb-6">Add New Influencer</h2>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="add_influencer" value="1">
            
            <div>
                <label class="text-xs font-bold uppercase text-gray-400 ml-3">Full Name</label>
                <input type="text" name="name" required placeholder="Ex: Rahul Sharma" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[20px] px-5 py-3 font-bold outline-none">
            </div>
            
            <div>
                 <label class="text-xs font-bold uppercase text-gray-400 ml-3">Email Address</label>
                 <input type="email" name="email" required placeholder="influencer@gmail.com" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[20px] px-5 py-3 font-bold outline-none">
            </div>

            <div>
                 <label class="text-xs font-bold uppercase text-gray-400 ml-3">Phone</label>
                 <input type="text" name="phone" required placeholder="9876543210" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[20px] px-5 py-3 font-bold outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                     <label class="text-xs font-bold uppercase text-gray-400 ml-3">Handle / Code</label>
                     <input type="text" name="code" required placeholder="rahul10" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[20px] px-5 py-3 font-bold outline-none">
                </div>
                <div>
                     <label class="text-xs font-bold uppercase text-gray-400 ml-3">Commission %</label>
                     <input type="number" name="commission" value="10" min="0" max="100" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[20px] px-5 py-3 font-bold outline-none">
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full btn-chunky bg-[#19DC7E] text-black py-4 shadow-lg hover:scale-[1.02]">Create Partner Account</button>
            </div>
            <p class="text-[10px] text-gray-400 text-center">If user exists, they will be linked. If not, a new account is created with password: <b>welcome123</b></p>
        </form>
    </div>
</div>

<div class="bg-white rounded-[35px] shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-gray-400 text-[10px] font-black uppercase tracking-widest bg-gray-50/50 border-b border-gray-50">
                    <th class="p-6">Partner</th>
                    <th class="p-6">Handle / Code</th>
                    <th class="p-6">Commission</th>
                    <th class="p-6">Earnings</th>
                    <th class="p-6">Status</th>
                    <th class="p-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-600 font-['Outfit']">
                <?php foreach ($affiliates as $aff): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                    <td class="p-6">
                        <div class="font-bold text-gray-900"><?php echo $aff['name']; ?></div>
                        <div class="text-xs text-gray-400"><?php echo $aff['email']; ?></div>
                        <div class="text-[10px] bg-gray-100 rounded px-2 py-0.5 mt-1 inline-block" title="Bank Details"><?php echo substr($aff['bank_details'], 0, 20); ?>...</div>
                    </td>
                    <td class="p-6">
                        <span class="font-mono text-xs bg-black text-[#19DC7E] px-2 py-1 rounded">@<?php echo $aff['code']; ?></span>
                    </td>
                    <td class="p-6 font-bold">
                        <?php echo floatval($aff['commission_rate']); ?>%
                    </td>
                    <td class="p-6 font-bold text-gray-900">
                        ₹<?php echo number_format($aff['total_earnings']); ?>
                    </td>
                    <td class="p-6">
                        <?php if(!$aff['is_approved']): ?>
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-yellow-100 text-yellow-600">Pending</span>
                        <?php elseif($aff['status'] == 'suspended'): ?>
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-red-100 text-red-600">Suspended</span>
                        <?php else: ?>
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-green-100 text-green-600">Active</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-6 text-right">
                         <?php if(!$aff['is_approved']): ?>
                            <a href="?approve=<?php echo $aff['id']; ?>" class="btn-chunky bg-[#19DC7E] text-black px-4 py-2 text-xs mr-2">Approve</a>
                         <?php endif; ?>
                         
                         <?php if($aff['status'] == 'active' && $aff['is_approved']): ?>
                            <a href="?suspend=<?php echo $aff['id']; ?>" class="text-red-400 hover:text-red-600 text-xs font-bold uppercase tracking-widest">Suspend</a>
                         <?php endif; ?>
                         
                         <?php if($aff['status'] == 'suspended'): ?>
                            <a href="?approve=<?php echo $aff['id']; ?>" class="text-green-400 hover:text-green-600 text-xs font-bold uppercase tracking-widest">Re-Activate</a>
                         <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($affiliates)): ?>
                    <tr><td colspan="6" class="p-20 text-center text-gray-400 italic">No partners yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
</body>
</html>
