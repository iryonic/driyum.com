<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$query = sanitize_input($_GET['id'] ?? '');
$contact = sanitize_input($_GET['contact'] ?? '');
$order = null;
$items = [];
$history = [];

if ($query) {
    // If contact is provided, use it to verify
    if ($contact) {
        $order = fetch_one("SELECT * FROM orders WHERE (id = ? OR order_number = ?)", [$query, $query]);
        if ($order) {
            $shipping_addr = json_decode($order['shipping_address'], true);
            $order_email = $shipping_addr['email'] ?? '';
            $order_phone = $shipping_addr['phone'] ?? '';
            
            // Verify match (case insensitive for email)
            if (strtolower(trim($contact)) !== strtolower(trim($order_email)) && trim($contact) !== trim($order_phone)) {
                $order = null;
                $error = "Verification failed. Please check your contact details.";
            }
        }
    } else {
        // Search by ID/Number but check if user is logged in and owns it
        $order = fetch_one("SELECT * FROM orders WHERE (id = ? OR order_number = ?)", [$query, $query]);
        
        if ($order) {
           // Verification skip: If admin or if user is logged in and it's their order
           $is_owner = is_logged_in() && $order['user_id'] == $_SESSION['user_id'];
           $is_just_placed = isset($_SESSION['last_order_id']) && $_SESSION['last_order_id'] == $order['id'];
           
           if (!is_admin() && !$is_owner && !$is_just_placed) {
               $order = null;
               $needs_verify = true;
           }
        }
    }

    if ($order) {
        $items = fetch_all("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?", [$order['id']]);
        $history = fetch_all("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at DESC", [$order['id']]);
        $shipping_addr = json_decode($order['shipping_address'], true);
        
        $shipping_method = null;
        if (!empty($order['shipping_method_id'])) {
            $shipping_method = fetch_one("SELECT * FROM shipping_methods WHERE id = ?", [$order['shipping_method_id']]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'Track Your Pack';
    $page_description = "Track your DRIYUM shipment and watch your chunky snacks journey from the valley to your doorstep in real-time.";
    include 'includes/head.php'; 
    ?>
    <style>
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 30px;
            bottom: -10px;
            width: 2px;
            background: #f3f4f6;
        }
        .timeline-item:last-child::before {
            display: none;
        }
    </style>
</head>
<body class="bg-[#FFFBEB] font-['Inter']">

    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-6 py-16 max-w-4xl">
        
        <!-- SEARCH BOX -->
        <div class="bg-white rounded-[40px] p-6 md:p-12 shadow-sm border border-gray-100 mb-12 text-center anim-up">
            <h1 class="text-[clamp(2rem,6vw,4rem)] font-['Crimson_Pro'] font-black text-gray-900 mb-4 leading-tight">Track Order.</h1>
            <p class="text-gray-400 font-bold mb-8 md:mb-10 uppercase tracking-[0.2em] text-[8px] md:text-[10px]">Verify your order details</p>
            
            <form class="flex flex-col gap-5 md:gap-6 max-w-2xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2 text-left">
                        <label class="text-[9px] md:text-[10px] font-black uppercase tracking-widest text-gray-400 ml-5 md:ml-6">Order ID / Number</label>
                        <input type="text" name="id" value="<?php echo htmlspecialchars($query); ?>" required placeholder="e.g. ORD-123" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[20px] md:rounded-[24px] px-6 md:px-8 py-4 md:py-5 outline-none transition-all font-black text-base md:text-lg">
                    </div>
                    <div class="space-y-2 text-left">
                        <label class="text-[9px] md:text-[10px] font-black uppercase tracking-widest text-[#19DC7E] ml-5 md:ml-6">Email / Phone (Verification)</label>
                        <input type="text" name="contact" value="<?php echo htmlspecialchars($contact); ?>" required placeholder="Verify identity..." class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[20px] md:rounded-[24px] px-6 md:px-8 py-4 md:py-5 outline-none transition-all font-black text-base md:text-lg">
                    </div>
                </div>
                <?php if(isset($error)): ?>
                    <p class="text-red-500 font-bold text-xs"><?php echo $error; ?></p>
                <?php endif; ?>
                <button type="submit" class="btn-chunky bg-[#111827] text-white w-full py-5 text-xl shadow-xl hover:bg-[#19DC7E] hover:text-black">Locate My Package</button>
            </form>
        </div>

        <?php if ($order): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                
                <!-- LEFT: STATUS & ITEMS -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- CURRENT STATUS -->
                    <div class="bg-white rounded-[40px] p-8 shadow-sm border border-gray-100 anim-up">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 mb-10">
                            <div>
                                <span class="bg-[#19DC7E]/10 text-[#19DC7E] text-[10px] font-black px-4 py-2 rounded-full uppercase tracking-widest mb-3 inline-block">Order #<?php echo $order['order_number']; ?></span>
                                <h2 class="text-3xl font-['Crimson_Pro'] font-black text-gray-900">Current Status: <span class="capitalize"><?php echo str_replace('_', ' ', $order['order_status']); ?></span></h2>
                            </div>
                            <?php 
                            $invoice_allowed = is_admin() || in_array($order['order_status'], ['confirmed', 'shipped', 'delivered']);
                            $invoice_url = get_url('invoice.php?id=' . $order['order_number']);
                            if ($contact) $invoice_url .= '&contact=' . urlencode($contact);
                            
                            if($invoice_allowed): ?>
                            <a href="<?php echo $invoice_url; ?>" target="_blank" class="btn-chunky bg-[#19DC7E] text-black border-none text-xs px-6 py-3 hover:scale-105 active:scale-95 transition-all">
                                <i class="fas fa-file-invoice mr-2"></i> Get Invoice
                            </a>
                            <?php else: ?>
                            <div class="flex flex-col items-end">
                                <button onclick="showToast('Invoice is available once the order is confirmed.', 'info')" 
                                        title="Order not confirmed yet."
                                        class="btn-chunky bg-gray-50 text-gray-300 border-none text-[10px] px-6 py-3 cursor-pointer opacity-60">
                                    <i class="fas fa-lock mr-2"></i> Invoice Locked
                                </button>
                                <span class="text-[8px] font-black text-gray-400 uppercase mt-2">Available after confirmation</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if($shipping_method && $order['order_status'] != 'delivered' && $order['order_status'] != 'cancelled'): ?>
                        <div class="mb-10 p-6 bg-blue-50/50 rounded-3xl border border-blue-100 flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-1">Expected Arrival</p>
                                <p class="text-xl font-black text-gray-900 crimson-pro">
                                    <?php 
                                        $created_at = strtotime($order['created_at']);
                                        $min_arrival = date('M d', strtotime("+{$shipping_method['min_days']} days", $created_at));
                                        $max_arrival = date('M d', strtotime("+{$shipping_method['max_days']} days", $created_at));
                                        echo "$min_arrival - $max_arrival";
                                    ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-1">Method</p>
                                <p class="font-bold text-gray-800"><?php echo $shipping_method['display_name']; ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- PROGRESS VISUALISERS -->
                        <div class="relative mb-12 px-2">
                            <div class="absolute top-1/2 left-0 w-full h-1 bg-gray-100 -translate-y-1/2 rounded-full"></div>
                            <?php 
                                $steps = ['pending' => 10, 'confirmed' => 35, 'shipped' => 65, 'out_for_delivery' => 85, 'delivered' => 100];
                                $w = $steps[$order['order_status']] ?? 10;
                            ?>
                            <div class="absolute top-1/2 left-0 h-1 bg-[#19DC7E] -translate-y-1/2 rounded-full transition-all duration-1000 shadow-[0_0_15px_rgba(25,220,126,0.5)]" style="width: <?php echo $w; ?>%"></div>
                            
                            <div class="relative flex justify-between z-10">
                                <div class="group relative">
                                    <div class="w-8 h-8 rounded-full bg-[#19DC7E] border-4 border-white shadow-md flex items-center justify-center text-white text-[10px]"><i class="fas fa-check"></i></div>
                                    <span class="absolute top-10 left-1/2 -translate-x-1/2 text-[8px] font-black uppercase text-gray-400 whitespace-nowrap">Placed</span>
                                </div>
                                <div class="group relative">
                                    <div class="w-8 h-8 rounded-full <?php echo $w >= 35 ? 'bg-[#19DC7E] text-white' : 'bg-white text-gray-200'; ?> border-4 border-white shadow-md flex items-center justify-center text-[10px]"><i class="fas fa-thumbs-up"></i></div>
                                    <span class="absolute top-10 left-1/2 -translate-x-1/2 text-[8px] font-black uppercase <?php echo $w >= 35 ? 'text-gray-900' : 'text-gray-400'; ?> whitespace-nowrap">Confirmed</span>
                                </div>
                                <div class="group relative">
                                    <div class="w-8 h-8 rounded-full <?php echo $w >= 65 ? 'bg-[#19DC7E] text-white' : 'bg-white text-gray-200'; ?> border-4 border-white shadow-md flex items-center justify-center text-[10px]"><i class="fas fa-truck-fast"></i></div>
                                    <span class="absolute top-10 left-1/2 -translate-x-1/2 text-[8px] font-black uppercase <?php echo $w >= 65 ? 'text-gray-900' : 'text-gray-400'; ?> whitespace-nowrap">Shipped</span>
                                </div>
                                <div class="group relative">
                                    <div class="w-8 h-8 rounded-full <?php echo $w >= 100 ? 'bg-[#19DC7E] text-white' : 'bg-white text-gray-200'; ?> border-4 border-white shadow-md flex items-center justify-center text-[10px]"><i class="fas fa-house-chimney-user"></i></div>
                                    <span class="absolute top-10 left-1/2 -translate-x-1/2 text-[8px] font-black uppercase <?php echo $w >= 100 ? 'text-gray-900' : 'text-gray-400'; ?> whitespace-nowrap">Delivered</span>
                                </div>
                            </div>
                        </div>

                        <!-- ITEMS -->
                        <h3 class="font-black text-gray-900 border-t border-gray-100 pt-8 mt-16 mb-6">Shipment Contents</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php foreach ($items as $item): ?>
                            <div class="flex items-center gap-4 bg-gray-50/50 p-4 rounded-3xl border border-gray-100">
                                <div class="w-16 h-16 bg-white rounded-2xl p-1 shadow-sm flex-shrink-0">
                                    <img src="<?php echo get_url(ltrim($item['image'], './')); ?>" class="w-full h-full object-contain">
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-sm text-gray-900 line-clamp-1"><?php echo $item['name']; ?></h4>
                                    <p class="text-[10px] font-black uppercase text-gray-400">Qty: <?php echo $item['quantity']; ?></p>
                                </div>
                                <span class="font-black text-gray-900 text-sm">₹<?php echo $item['price'] * $item['quantity']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- TIMELINE -->
                    <div class="bg-white rounded-[40px] p-8 shadow-sm border border-gray-100">
                        <h3 class="font-black text-gray-900 mb-8">Journey History</h3>
                        <div class="space-y-8">
                            <?php if (empty($history)): ?>
                                <p class="text-gray-400 text-sm font-bold">No updates yet.</p>
                            <?php else: ?>
                                <?php foreach ($history as $event): ?>
                                <div class="relative pl-10 timeline-item">
                                    <div class="absolute left-0 top-1 w-8 h-8 rounded-full bg-gray-100 border-4 border-white flex items-center justify-center text-gray-400 text-[10px] z-10">
                                        <i class="fas fa-circle"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-3 mb-1">
                                            <span class="font-black text-gray-900 text-sm capitalize"><?php echo str_replace('_', ' ', $event['status']); ?></span>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase"><?php echo date('M d, h:i A', strtotime($event['created_at'])); ?></span>
                                        </div>
                                        <p class="text-xs text-gray-500 font-medium"><?php echo htmlspecialchars($event['notes']); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- RIGHT: DETAILS SIDEBAR -->
                <div class="space-y-8">
                    
                    <!-- SUMMARY -->
                    <div class="bg-[#111827] text-white rounded-[40px] p-8 shadow-xl shadow-gray-200">
                        <h3 class="font-bold text-gray-400 uppercase text-[10px] tracking-widest mb-6">Payment Summary</h3>
                        <div class="space-y-4 text-sm font-bold">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Subtotal</span>
                                <span>₹<?php echo $order['subtotal']; ?></span>
                            </div>
                            <?php if($order['discount'] > 0): ?>
                            <div class="flex justify-between text-[#19DC7E]">
                                <span>Discount</span>
                                <span>- ₹<?php echo $order['discount']; ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Shipping</span>
                                <span><?php echo $order['shipping_cost'] == 0 ? 'FREE' : '₹' . $order['shipping_cost']; ?></span>
                            </div>
                            <div class="flex justify-between pt-4 border-t border-white/10 text-xl font-['Crimson_Pro'] font-black">
                                <span class="text-gray-400">Total</span>
                                <span class="text-[#19DC7E]">₹<?php echo $order['total']; ?></span>
                            </div>
                        </div>
                        <div class="mt-8 pt-6 border-t border-white/10 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-xs text-gray-400"><i class="fas fa-credit-card"></i></div>
                            <div>
                                <p class="text-[10px] text-gray-500 font-black uppercase">Paid via</p>
                                <p class="text-xs font-bold uppercase"><?php echo $order['payment_method']; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- SHIPPING INFO -->
                    <div class="bg-white rounded-[40px] p-8 shadow-sm border border-gray-100">
                        <h3 class="font-black text-gray-900 mb-6 flex items-center gap-2">
                            <i class="fas fa-location-dot text-[#19DC7E] text-sm"></i> Shipping To
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <h4 class="font-black text-gray-900 text-sm"><?php echo htmlspecialchars($shipping_addr['name'] ?? 'N/A'); ?></h4>
                                <p class="text-xs text-gray-500 font-medium leading-relaxed mt-2">
                                    <?php echo htmlspecialchars($shipping_addr['address'] ?? ''); ?><br>
                                    <?php echo htmlspecialchars($shipping_addr['city'] ?? ''); ?>, <?php echo htmlspecialchars($shipping_addr['state'] ?? ''); ?> <?php echo htmlspecialchars($shipping_addr['zip'] ?? ''); ?>
                                </p>
                            </div>
                            <div class="pt-4 border-t border-gray-50">
                                <p class="text-[10px] text-gray-400 font-black uppercase mb-1">Contact</p>
                                <p class="text-xs font-bold text-gray-900"><?php echo htmlspecialchars($shipping_addr['phone'] ?? ''); ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if($order['tracking_number']): ?>
                    <div class="bg-green-50 border-2 border-green-100 rounded-[40px] p-8 shadow-sm">
                        <h3 class="font-black text-green-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-barcode"></i> AWB Number
                        </h3>
                        <p class="text-2xl font-black font-['Crimson_Pro'] text-green-900"><?php echo $order['tracking_number']; ?></p>
                        <p class="text-xs text-green-600 font-medium mt-2 uppercase tracking-widest">Shipped via <?php echo $shipping_method['carrier_name'] ?? 'India Post'; ?></p>
                        
                        <?php if(!empty($order['tracking_note'])): ?>
                        <div class="mt-6 p-4 bg-white/50 rounded-2xl border border-green-200">
                            <p class="text-[10px] font-black text-green-800 uppercase tracking-widest mb-1 flex items-center gap-2">
                                <i class="fas fa-info-circle"></i> Special Update
                            </p>
                            <p class="text-sm font-bold text-green-900 leading-relaxed italic">"<?php echo nl2br(htmlspecialchars($order['tracking_note'])); ?>..."</p>
                        </div>
                        <?php endif; ?>

                        <?php if($order['dispatch_date']): ?>
                            <p class="text-[9px] font-black text-green-500 uppercase tracking-widest mt-4">Dispatched on <?php echo date('M d, Y', strtotime($order['dispatch_date'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php elseif($query): ?>
             <div class="text-center py-20 bg-white rounded-[40px] border border-gray-100 anim-up">
                <div class="text-8xl mb-6">🍿</div>
                <h3 class="text-2xl font-black font-['Crimson_Pro'] text-gray-900">Order Missing in Action!</h3>
                <?php if(isset($needs_verify)): ?>
                    <p class="text-gray-500 font-medium mb-8">For security, please provide the <span class="bg-[#19DC7E]/10 text-[#19DC7E] px-2 py-1 rounded">Email or Phone</span> used during checkout.</p>
                <?php else: ?>
                    <p class="text-gray-500 font-medium mb-8">We couldn't find an order with <span class="bg-yellow-100 text-gray-900 px-2 py-1 rounded">"<?php echo htmlspecialchars($query); ?>"</span>.</p>
                <?php endif; ?>
                <a href="<?php echo get_url('track'); ?>" class="btn-chunky bg-[#111827] text-white px-10 py-4">New Search</a>
             </div>
        <?php endif; ?>

    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>


