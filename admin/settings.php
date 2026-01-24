<?php
require_once 'includes/header.php';

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $settings_to_update = [
        'tax_percentage',
        'store_name',
        'support_email',
        'support_phone',
        'free_shipping_threshold',
        'instagram_url',
        'whatsapp_number',
        'facebook_url',
        'twitter_url',
        'youtube_url',
        'pinterest_url',
        'maintenance_mode',
        'footer_description',
        'order_prefix',
        'seo_description'
    ];

    $error_found = false;
    foreach ($settings_to_update as $key) {
        $val = sanitize_input($_POST[$key] ?? '');
        
        // Validation for numeric fields
        if (in_array($key, ['tax_percentage', 'free_shipping_threshold']) && !empty($val) && !is_numeric($val)) {
            $error = "Field " . str_replace('_', ' ', $key) . " must be a number.";
            $error_found = true;
            break;
        }
        
        update_setting($key, $val);
    }
    
    if (!$error_found) {
        $success = "Global configurations updated successfully!";
    }
}

$s = [
    'tax' => get_setting('tax_percentage', '12'),
    'name' => get_setting('store_name', 'DRIYUM'),
    'email' => get_setting('support_email', 'hello@driyum.com'),
    'phone' => get_setting('support_phone', '+91 91030 00000'),
    'threshold' => get_setting('free_shipping_threshold', '499'),
    'instagram' => get_setting('instagram_url', '#'),
    'whatsapp' => get_setting('whatsapp_number', ''),
    'facebook' => get_setting('facebook_url', '#'),
    'twitter' => get_setting('twitter_url', '#'),
    'youtube' => get_setting('youtube_url', '#'),
    'pinterest' => get_setting('pinterest_url', '#'),
    'maintenance' => get_setting('maintenance_mode', 'off'),
    'footer_desc' => get_setting('footer_description', 'Redefining the art of snacking with premium, sun-dried indulgence. Naturally sweet, unapologetically bold.'),
    'order_prefix' => get_setting('order_prefix', 'DRY-'),
    'seo_desc' => get_setting('seo_description', 'Premium sun-dried snacks and organic delicacies from Kashmir.')
];
?>

<div class="max-w-6xl mx-auto pb-40">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
        <div>
            <h1 class="text-5xl font-black text-gray-900 fredoka mb-2 tracking-tight">Command Center</h1>
            <p class="text-gray-500 font-bold uppercase tracking-widest text-[10px] flex items-center gap-2">
                <span class="text-[#19DC7E]">System Settings</span>
                <span class="opacity-20">/</span>
                <span>Global Configuration</span>
            </p>
        </div>
        <div class="flex items-center gap-4">
            <div class="bg-white px-6 py-3 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-3">
                <div class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?php echo $s['maintenance'] == 'on' ? 'bg-red-400' : 'bg-green-400'; ?> opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 <?php echo $s['maintenance'] == 'on' ? 'bg-red-500' : 'bg-green-500'; ?>"></span>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-900"><?php echo $s['maintenance'] == 'on' ? 'Maintenance Active' : 'Store Live'; ?></span>
            </div>
            <div class="w-16 h-16 bg-black text-white rounded-[24px] flex items-center justify-center text-2xl shadow-2xl rotate-3">
                <i class="fas fa-sliders-h"></i>
            </div>
        </div>
    </div>

    <?php if($success): ?>
        <div class="mb-10 p-2 bg-green-500 rounded-[32px] anim-up">
            <div class="bg-white p-6 rounded-[28px] border-2 border-green-100/50 flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500 text-white rounded-2xl flex items-center justify-center shadow-lg"><i class="fas fa-check"></i></div>
                <p class="font-black uppercase tracking-widest text-sm text-green-600"><?php echo $success; ?></p>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-12">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <!-- EMERGENCY SECTION -->
            <div class="bg-white rounded-[48px] p-8 shadow-sm border border-gray-100 flex flex-col">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center shadow-inner"><i class="fas fa-power-off"></i></div>
                    <div>
                        <h3 class="font-black text-gray-900 text-lg fredoka">System Control</h3>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Master Switch</p>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="p-6 bg-red-50/50 rounded-3xl border border-red-100 text-[11px] text-red-700 font-bold leading-relaxed mb-6">
                        <i class="fas fa-info-circle mr-2"></i> Enabling maintenance mode will lock the storefront and display a "Restocking" page to all customers.
                    </div>
                    <label class="flex items-center justify-between p-6 bg-gray-50 rounded-[28px] border-2 border-transparent cursor-pointer hover:border-red-200 hover:bg-red-50/20 transition-all group">
                        <span class="text-xs font-black uppercase tracking-widest text-gray-900">Maintenance Mode</span>
                        <div class="relative inline-block w-14 h-7">
                            <input type="hidden" name="maintenance_mode" value="off">
                            <input type="checkbox" name="maintenance_mode" value="on" <?php echo $s['maintenance'] == 'on' ? 'checked' : ''; ?> class="peer opacity-0 w-0 h-0">
                            <span class="absolute cursor-pointer inset-0 bg-gray-200 rounded-full transition-all peer-checked:bg-red-500 after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:rounded-full after:h-5 after:w-5 after:shadow-sm after:transition-all peer-checked:after:translate-x-7"></span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- LOGISTICS & DNA -->
            <div class="bg-white rounded-[48px] p-10 shadow-sm border border-gray-100 lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="space-y-8">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-indigo-50 text-indigo-500 rounded-lg flex items-center justify-center text-xs shadow-sm"><i class="fas fa-fingerprint"></i></div>
                        <h4 class="font-black text-xs uppercase tracking-[0.2em] text-gray-900">Brand DNA</h4>
                    </div>
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Store Name</label>
                            <input type="text" name="store_name" value="<?php echo $s['name']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black focus:bg-white rounded-[20px] px-6 py-4 outline-none transition-all font-bold shadow-sm">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Order ID Prefix</label>
                            <input type="text" name="order_prefix" value="<?php echo $s['order_prefix']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black focus:bg-white rounded-[20px] px-6 py-4 outline-none transition-all font-bold shadow-sm">
                        </div>
                    </div>
                </div>
                <div class="space-y-8">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-amber-50 text-amber-500 rounded-lg flex items-center justify-center text-xs shadow-sm"><i class="fas fa-coins"></i></div>
                        <h4 class="font-black text-xs uppercase tracking-[0.2em] text-gray-900">Economics</h4>
                    </div>
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Free Shipping At (₹)</label>
                            <input type="text" name="free_shipping_threshold" value="<?php echo $s['threshold']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black focus:bg-white rounded-[20px] px-6 py-4 outline-none transition-all font-bold shadow-sm">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Default Tax (%)</label>
                            <input type="text" name="tax_percentage" value="<?php echo $s['tax']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black focus:bg-white rounded-[20px] px-6 py-4 outline-none transition-all font-bold shadow-sm">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUPPORT & SOCIAL -->
            <div class="bg-white rounded-[48px] p-10 shadow-sm border border-gray-100 lg:col-span-3">
                <div class="flex items-center gap-4 mb-10 pb-6 border-b border-gray-50">
                    <div class="w-12 h-12 bg-pink-50 text-pink-600 rounded-2xl flex items-center justify-center shadow-inner"><i class="fas fa-share-alt"></i></div>
                    <div>
                        <h3 class="font-black text-gray-900 text-lg fredoka">Connectivity & Presence</h3>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Support Channels & Social Links</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Support Email</label>
                        <input type="email" name="support_email" value="<?php echo $s['email']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black focus:bg-white rounded-[20px] px-6 py-4 outline-none transition-all font-bold shadow-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Direct Contact/WA</label>
                        <input type="text" name="support_phone" value="<?php echo $s['phone']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black focus:bg-white rounded-[20px] px-6 py-4 outline-none transition-all font-bold shadow-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">WhatsApp (Auto-Connect)</label>
                        <input type="text" name="whatsapp_number" value="<?php echo $s['whatsapp']; ?>" placeholder="+91..." class="w-full bg-gray-50 border-2 border-transparent focus:border-green-500 focus:bg-white rounded-[20px] px-6 py-4 outline-none transition-all font-bold shadow-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Instagram URL</label>
                        <input type="text" name="instagram_url" value="<?php echo $s['instagram']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-pink-500 focus:bg-white rounded-[20px] px-6 py-4 outline-none transition-all font-bold shadow-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Facebook Page URL</label>
                        <input type="text" name="facebook_url" value="<?php echo $s['facebook']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-blue-600 focus:bg-white rounded-[20px] px-6 py-4 outline-none transition-all font-bold shadow-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Twitter (X) URL</label>
                        <input type="text" name="twitter_url" value="<?php echo $s['twitter']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-sky-500 focus:bg-white rounded-[20px] px-6 py-4 outline-none transition-all font-bold shadow-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">YouTube Channel URL</label>
                        <input type="text" name="youtube_url" value="<?php echo $s['youtube']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-red-600 focus:bg-white rounded-[20px] px-6 py-4 outline-none transition-all font-bold shadow-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Pinterest URL</label>
                        <input type="text" name="pinterest_url" value="<?php echo $s['pinterest']; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-red-500 focus:bg-white rounded-[20px] px-6 py-4 outline-none transition-all font-bold shadow-sm">
                    </div>
                </div>
            </div>

            <!-- SEO & CONTENT -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:col-span-3 gap-8">
                <div class="bg-black rounded-[48px] p-10 shadow-sm border border-black lg:col-span-1 text-white">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 bg-white/10 text-white rounded-2xl flex items-center justify-center shadow-inner"><i class="fas fa-search"></i></div>
                        <div>
                            <h3 class="font-black text-lg fredoka text-white">SEO Master</h3>
                            <p class="text-[10px] text-white/40 font-bold uppercase tracking-widest">Metadata Control</p>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-white/40 ml-4">Global Meta Description</label>
                            <textarea name="seo_description" class="w-full bg-white/5 border-2 border-transparent focus:border-white/20 focus:bg-white/10 rounded-[32px] px-6 py-4 outline-none transition-all font-bold h-40 resize-none text-white text-sm shadow-inner"><?php echo $s['seo_desc']; ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[48px] p-10 shadow-sm border border-gray-100 md:col-span-2">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 bg-gray-50 text-gray-900 rounded-2xl flex items-center justify-center shadow-inner"><i class="fas fa-quote-left"></i></div>
                        <div>
                            <h3 class="font-black text-gray-900 text-lg fredoka">Footer Story</h3>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Brand Narrative</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">"About Us" Fragment</label>
                        <textarea name="footer_description" class="w-full bg-gray-50 border-2 border-transparent focus:border-black focus:bg-white rounded-[32px] px-8 py-8 outline-none transition-all font-medium h-40 resize-none leading-relaxed shadow-inner"><?php echo $s['footer_desc']; ?></textarea>
                    </div>
                </div>
            </div>

        </div>

        <!-- Submit -->
        <div class="flex justify-center pt-10">
            <button type="submit" name="update_settings" class="group relative overflow-hidden bg-black text-white px-24 py-7 rounded-[40px] font-black uppercase tracking-[0.3em] shadow-[0_25px_60px_rgba(0,0,0,0.3)] hover:scale-105 active:scale-95 transition-all duration-500">
                <span class="relative z-10 flex items-center gap-4">
                    <i class="fas fa-rocket group-hover:rotate-12 transition-transform"></i> Propagate Update
                </span>
                <div class="absolute inset-0 bg-[#19DC7E] opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-[40px] mix-blend-difference"></div>
            </button>
        </div>
    </form>
</div>

</main>
</body>
</html>
