<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: " . get_url('login.php?redirect=affiliate-dashboard.php'));
    exit;
}

// Affiliate Check
$aff = fetch_one("SELECT * FROM affiliates WHERE user_id = ?", [$_SESSION['user_id']]);

if (!$aff) {
    header("Location: " . get_url('index.php'));
    exit;
}

if ($aff['is_approved'] == 0) {
    // Waiting approval
    echo "<script>alert('Your creator account is pending approval.'); window.location.href='" . get_url('index.php') . "';</script>";
    exit;
}

if ($aff['status'] != 'active') {
    die("Your affiliate account is suspended.");
}

// Fetch Stats
$query_orders = "SELECT * FROM orders WHERE affiliate_id = ? ORDER BY created_at DESC";
$pagination = get_pagination_data($query_orders, [$aff['id']], 10);
$orders = $pagination['records'];

$total_earned = $aff['total_earnings'];
$total_orders = $pagination['total_records'];

// Fetch all for commission calculation
$all_aff_orders = fetch_all("SELECT affiliate_commission, order_status FROM orders WHERE affiliate_id = ?", [$aff['id']]);

// Calculate pending vs paid (mock logic for now, using order status)
$pending_comm = 0;
foreach($all_aff_orders as $o) {
    if($o['order_status'] == 'pending' || $o['order_status'] == 'shipped') {
        $pending_comm += $o['affiliate_commission'];
    }
}

$referral_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/" . $aff['code'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'Creator Dashboard';
    include 'includes/head.php'; 
    ?>
</head>
<body class="bg-[#FFFEDC] font-['Inter']">

    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-6 py-12">
        
        <!-- HEADER -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-12 gap-6 anim-up">
            <div class="flex items-center gap-6">
                <div class="w-20 h-20 bg-black text-[#24B25D] rounded-[30px] flex items-center justify-center text-4xl shadow-xl">
                    <i class="fas fa-bolt"></i>
                </div>
                <div>
                    <span class="text-xs font-black uppercase tracking-widest text-[#24B25D]">Driyum Creator</span>
                    <h1 class="text-4xl md:text-5xl font-['Crimson_Pro'] font-black text-gray-900">Dashboard</h1>
                </div>
            </div>
            
            <!-- Link Copy Card -->
            <div class="bg-white p-2 pl-6 rounded-full shadow-lg border border-gray-100 flex items-center gap-4 max-w-full">
                <div class="hidden md:block text-xs font-bold text-gray-400 uppercase tracking-widest">Your Link:</div>
                <div class="font-mono font-bold text-gray-800 text-sm truncate max-w-[200px] md:max-w-none"><?php echo $referral_link; ?></div>
                <button onclick="navigator.clipboard.writeText('<?php echo $referral_link; ?>'); showToast('Link Copied!', 'success')" class="w-10 h-10 bg-[#24B25D] rounded-full flex items-center justify-center text-black hover:scale-110 transition shadow-md">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>

        <!-- STATS GRID -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16 anim-up delay-100">
            <!-- Earnings -->
            <div class="bg-black text-white p-10 rounded-[40px] shadow-2xl relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Total Earnings</div>
                    <div class="text-5xl font-['Crimson_Pro'] font-black text-[#24B25D]">₹<?php echo number_format($total_earned); ?></div>
                    <div class="mt-4 text-xs font-bold text-gray-400">+₹<?php echo number_format($pending_comm); ?> pending</div>
                </div>
                <!-- Decor -->
                <div class="absolute right-0 top-0 w-32 h-32 bg-[#24B25D] rounded-full blur-[60px] opacity-20 -mr-10 -mt-10 group-hover:opacity-30 transition"></div>
            </div>
            
            <!-- Referrals -->
            <div class="bg-white p-10 rounded-[40px] shadow-sm border border-gray-100 group hover:border-[#24B25D] transition-colors">
                <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center text-xl mb-6">
                    <i class="fas fa-users"></i>
                </div>
                <div class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">Total Orders</div>
                <div class="text-4xl font-['Crimson_Pro'] font-black text-gray-900"><?php echo $total_orders; ?></div>
            </div>

            <!-- Commission Rate -->
            <div class="bg-white p-10 rounded-[40px] shadow-sm border border-gray-100 group hover:border-[#24B25D] transition-colors">
                 <div class="w-12 h-12 bg-orange-50 text-orange-500 rounded-2xl flex items-center justify-center text-xl mb-6">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">Your Rate</div>
                <div class="text-4xl font-['Crimson_Pro'] font-black text-gray-900"><?php echo floatval($aff['commission_rate']); ?>%</div>
                <div class="mt-2 text-[10px] font-bold bg-green-100 text-green-700 px-3 py-1 rounded-full inline-block">Code: <?php echo $aff['code']; ?></div>
            </div>
        </div>

        <!-- RECENT ACTIVITY -->
        <div class="bg-white rounded-[50px] p-8 md:p-12 shadow-sm border border-gray-100 anim-up delay-200">
            <h3 class="text-2xl font-['Crimson_Pro'] font-black mb-8 text-gray-900">Recent Referrals</h3>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-xs font-black uppercase tracking-widest text-gray-400 border-b border-gray-100">
                            <th class="pb-6 pl-4">Date</th>
                            <th class="pb-6">Order ID</th>
                            <th class="pb-6">Total Sale</th>
                            <th class="pb-6">Your Cut</th>
                            <th class="pb-6 text-right pr-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="font-['Inter'] font-bold text-gray-600">
                        <?php foreach($orders as $o): ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50 transition group">
                            <td class="py-6 pl-4 text-sm"><?php echo date('M d, Y', strtotime($o['created_at'])); ?></td>
                            <td class="py-6 text-sm">#<?php echo $o['order_number']; ?></td>
                            <td class="py-6 text-gray-900">₹<?php echo number_format($o['total']); ?></td>
                            <td class="py-6 text-[#24B25D]">₹<?php echo number_format($o['affiliate_commission']); ?></td>
                            <td class="py-6 text-right pr-4">
                                <span class="px-3 py-1 rounded-full text-[10px] uppercase tracking-widest
                                    <?php echo $o['order_status'] == 'delivered' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                                    <?php echo $o['order_status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(count($orders) == 0): ?>
                            <tr>
                                <td colspan="5" class="py-20 text-center text-gray-400">
                                    <div class="text-4xl mb-4">🕸️</div>
                                    No referrals yet. Share your link to start earning!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>

</body>
</html>


