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
        'seo_description',
        'maintenance_headline',
        'maintenance_description',
        'maintenance_progress',
        'maintenance_status_label',
        'maintenance_mode_text',
        'maintenance_sticker_text',
        'maintenance_overlay_text',
        'maintenance_show_timer',
        'maintenance_end_date',
        'maintenance_countdown_label',
        'payment_cod_enabled',
        'payment_online_enabled',
        'backup_frequency',
        'storage_freshness_enabled',
        'storage_freshness_title',
        'storage_freshness_content',
        'footer_powered_by_enabled',
        'footer_powered_by_text',
        'footer_powered_by_name',
        'footer_powered_by_url'
    ];

    $error_found = false;
    foreach ($settings_to_update as $key) {
        $raw_val = $_POST[$key] ?? '';
        if ($key === 'maintenance_headline') {
            $raw_val = strip_tags($raw_val);
        }
        $val = sanitize_input($raw_val);
        if (in_array($key, ['tax_percentage', 'free_shipping_threshold']) && !empty($val) && !is_numeric($val)) {
            $error = "Field " . str_replace('_', ' ', $key) . " must be a number.";
            $error_found = true;
            break;
        }
        update_setting($key, $val);
    }

    // Handle File Uploads (Logo, Favicon, Maintenance)
    $uploads = ['maintenance_image_file' => 'maintenance_image', 'site_logo_file' => 'site_logo', 'site_favicon_file' => 'site_favicon'];
    foreach($uploads as $file_key => $db_key) {
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'ico', 'svg'];
            $filename = $_FILES[$file_key]['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $upload_dir = '../assets/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $new_name = $db_key . '_' . time() . '.' . $ext;
                $dest = $upload_dir . $new_name;
                
                if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $dest)) {
                    $db_path = 'assets/uploads/' . $new_name;
                    update_setting($db_key, $db_path);
                } else {
                    $error = "Failed to move uploaded file ($file_key).";
                }
            } else {
                $error = "Invalid file type for $file_key.";
            }
        }
    }
    
    if (!$error_found && empty($error)) {
        $success = "Store configurations updated successfully!";
    }
}

$s = [
    'tax' => get_setting('tax_percentage', '12'),
    'name' => get_setting('store_name', 'DRIYUM'),
    'email' => get_setting('support_email', 'contact@driyum.com'),
    'phone' => get_setting('support_phone', '+91 9419809801'),
    'threshold' => get_setting('free_shipping_threshold', '499'),
    'instagram' => get_setting('instagram_url', '#'),
    'whatsapp' => get_setting('whatsapp_number', ''),
    'facebook' => get_setting('facebook_url', '#'),
    'twitter' => get_setting('twitter_url', '#'),
    'youtube' => get_setting('youtube_url', '#'),
    'pinterest' => get_setting('pinterest_url', '#'),
    'maintenance' => get_setting('maintenance_mode', 'off'),
    'footer_desc' => get_setting('footer_description', 'Redefining the art of snacking with premium, indulgence.'),
    'order_prefix' => get_setting('order_prefix', 'DRY-'),
    'seo_desc' => get_setting('seo_description', 'Premium snacks and organic delicacies from Kashmir.'),
    'm_headline' => get_setting('maintenance_headline', 'System Update'),
    'm_desc' => get_setting('maintenance_description', "We're performing scheduled maintenance."),
    'm_progress' => get_setting('maintenance_progress', '80'),
    'm_status' => get_setting('maintenance_status_label', 'System Optimization'),
    'm_mode_text' => get_setting('maintenance_mode_text', 'Maintenance Mode'),
    'm_image' => get_setting('maintenance_image', 'assets/images/hero.jpg'),
    'm_countdown_label' => get_setting('maintenance_countdown_label', 'WE WILL BE BACK SUBSCRIBE US TILL THEN'),
    'logo' => get_setting('site_logo', ''),
    'favicon' => get_setting('site_favicon', ''),
    'm_show_timer' => get_setting('maintenance_show_timer', 'on'),
    'backup_freq' => get_setting('backup_frequency', 'manual'),
    'last_backup' => get_setting('last_backup_at', 'Never'),
    'storage_enabled' => get_setting('storage_freshness_enabled', 'on'),
    'storage_title' => get_setting('storage_freshness_title', 'Storage & Freshness'),
    'storage_content' => get_setting('storage_freshness_content', "Store in a cool, dry place away from direct sunlight. Once opened, keep in an airtight container or seal the ziplock pouch tightly.\n\nBest consumed within 6 months from packaging date for maximum crunch and natural sweetness."),
    'powered_by_enabled' => get_setting('footer_powered_by_enabled', 'on'),
    'powered_by_text' => get_setting('footer_powered_by_text', 'Powered By'),
    'powered_by_name' => get_setting('footer_powered_by_name', 'Saastify'),
    'powered_by_url' => get_setting('footer_powered_by_url', 'https://saastify.fmmedianetwork.com/')
];

$active_tab = isset($_POST['active_tab']) ? sanitize_input($_POST['active_tab']) : (isset($_GET['tab']) ? sanitize_input($_GET['tab']) : 'general');
$valid_tabs = ['general', 'economics', 'contact', 'maintenance', 'system'];
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'general';
}
?>

<div class="space-y-6">
    <form method="POST" enctype="multipart/form-data" id="settingsForm">
        <input type="hidden" name="active_tab" id="active_tab_input" value="<?php echo htmlspecialchars($active_tab); ?>">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Store Settings</h1>
                <p class="text-sm text-slate-500 mt-0.5">Manage store parameters, branding, payment gateways, maintenance status, and system configurations.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $s['maintenance'] == 'on' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'; ?>">
                    <span class="w-2 h-2 rounded-full <?php echo $s['maintenance'] == 'on' ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500'; ?>"></span>
                    <?php echo $s['maintenance'] == 'on' ? 'Maintenance Active' : 'Store Live'; ?>
                </span>
                <button type="submit" name="update_settings" class="btn-admin btn-admin-primary text-xs shadow-sm hover:shadow">
                    <i class="fas fa-save mr-1.5"></i> Save Changes
                </button>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="mt-4 p-3.5 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 text-xs font-medium flex items-center justify-between animate-fade-in">
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times text-xs"></i></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mt-4 p-3.5 bg-rose-50 text-rose-800 rounded-xl border border-rose-200 text-xs font-medium flex items-center justify-between animate-fade-in">
                <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-circle text-rose-600 text-sm"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fas fa-times text-xs"></i></button>
            </div>
        <?php endif; ?>

        <!-- Responsive Segmented Tab Navigation -->
        <div class="mt-6 flex items-center gap-1.5 p-1.5 bg-slate-100 rounded-2xl w-full sm:w-fit overflow-x-auto no-scrollbar border border-slate-200/80 shadow-xs">
            <button type="button" onclick="switchTab('general')" id="tab-btn-general" class="settings-tab-btn flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?php echo $active_tab === 'general' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">
                <i class="fas fa-store text-emerald-600"></i>
                <span>General & Brand</span>
            </button>
            <button type="button" onclick="switchTab('economics')" id="tab-btn-economics" class="settings-tab-btn flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?php echo $active_tab === 'economics' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">
                <i class="fas fa-credit-card text-indigo-500"></i>
                <span>Economics & Payments</span>
            </button>
            <button type="button" onclick="switchTab('contact')" id="tab-btn-contact" class="settings-tab-btn flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?php echo $active_tab === 'contact' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">
                <i class="fas fa-headset text-sky-500"></i>
                <span>Support & Socials</span>
            </button>
            <button type="button" onclick="switchTab('maintenance')" id="tab-btn-maintenance" class="settings-tab-btn flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?php echo $active_tab === 'maintenance' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">
                <i class="fas fa-hammer text-amber-500"></i>
                <span>Maintenance</span>
                <span class="w-2 h-2 rounded-full <?php echo $s['maintenance'] === 'on' ? 'bg-rose-500 animate-pulse' : 'bg-slate-300'; ?>"></span>
            </button>
            <button type="button" onclick="switchTab('system')" id="tab-btn-system" class="settings-tab-btn flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?php echo $active_tab === 'system' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">
                <i class="fas fa-sliders-h text-purple-500"></i>
                <span>SEO & System</span>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- TAB 1: GENERAL & BRAND IDENTITY -->
        <!-- ========================================== -->
        <div id="tab-pane-general" class="tab-pane space-y-6 mt-6 <?php echo $active_tab === 'general' ? '' : 'hidden'; ?>">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Store Brand Identity -->
                <div class="admin-card p-5 sm:p-6 space-y-5">
                    <h3 class="text-sm font-bold text-slate-900 pb-2.5 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-store text-emerald-600"></i> Brand & Store Identity
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Store Public Name</label>
                            <input type="text" name="store_name" value="<?php echo htmlspecialchars($s['name']); ?>" class="admin-input text-xs" placeholder="e.g. DRIYUM">
                            <p class="text-[11px] text-slate-400 mt-1">Displayed in title tags, email alerts, and customer receipts.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Order Number Prefix</label>
                            <input type="text" name="order_prefix" value="<?php echo htmlspecialchars($s['order_prefix']); ?>" class="admin-input text-xs font-mono" placeholder="e.g. DRY-">
                            <p class="text-[11px] text-slate-400 mt-1">Prepended to invoice numbers (e.g. <span class="font-mono text-slate-700"><?php echo htmlspecialchars($s['order_prefix']); ?>10042</span>).</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Footer Brand Statement</label>
                            <textarea name="footer_description" rows="3" class="admin-input text-xs resize-none" placeholder="Short description about your brand..."><?php echo htmlspecialchars($s['footer_desc']); ?></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">Summary displayed in the main storefront footer column.</p>
                        </div>
                    </div>
                </div>

                <!-- Brand Visual Assets (Logo & Favicon) -->
                <div class="admin-card p-5 sm:p-6 space-y-5">
                    <h3 class="text-sm font-bold text-slate-900 pb-2.5 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-palette text-primary"></i> Visual Brand Assets
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Store Logo -->
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-700">Store Logo</label>
                            <div class="border border-dashed border-slate-300 rounded-xl p-3 text-center group hover:border-primary transition-colors bg-slate-50/50">
                                <div class="h-24 bg-white rounded-lg border border-slate-200/80 overflow-hidden relative flex items-center justify-center p-2">
                                    <img id="logo_preview" src="<?php echo !empty($s['logo']) ? '../' . ltrim($s['logo'], './') : ''; ?>" class="max-h-full max-w-full object-contain <?php echo !empty($s['logo']) ? '' : 'hidden'; ?>">
                                    <div id="logo_placeholder" class="text-slate-400 text-center <?php echo !empty($s['logo']) ? 'hidden' : ''; ?>">
                                        <i class="fas fa-mountain-sun text-2xl mb-1"></i>
                                        <span class="block text-[10px]">No Logo Uploaded</span>
                                    </div>
                                    <label class="absolute inset-0 flex items-center justify-center bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-white text-xs font-semibold">
                                        <i class="fas fa-upload mr-1.5"></i> Change Logo
                                        <input type="file" name="site_logo_file" id="logo_input" class="hidden" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                                    </label>
                                </div>
                                <span class="text-[10px] text-slate-400 block mt-2">Recommended: PNG / SVG with transparent background</span>
                            </div>
                        </div>

                        <!-- Browser Favicon -->
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-700">Browser Favicon</label>
                            <div class="border border-dashed border-slate-300 rounded-xl p-3 text-center group hover:border-primary transition-colors bg-slate-50/50">
                                <div class="h-24 bg-white rounded-lg border border-slate-200/80 overflow-hidden relative flex items-center justify-center p-2">
                                    <img id="favicon_preview" src="<?php echo !empty($s['favicon']) ? '../' . ltrim($s['favicon'], './') : ''; ?>" class="w-10 h-10 object-contain <?php echo !empty($s['favicon']) ? '' : 'hidden'; ?>">
                                    <div id="favicon_placeholder" class="text-slate-400 text-center <?php echo !empty($s['favicon']) ? 'hidden' : ''; ?>">
                                        <i class="fas fa-icons text-2xl mb-1"></i>
                                        <span class="block text-[10px]">No Favicon</span>
                                    </div>
                                    <label class="absolute inset-0 flex items-center justify-center bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-white text-xs font-semibold">
                                        <i class="fas fa-upload mr-1.5"></i> Change Icon
                                        <input type="file" name="site_favicon_file" id="favicon_input" class="hidden" accept="image/png,image/x-icon,image/svg+xml,image/webp">
                                    </label>
                                </div>
                                <span class="text-[10px] text-slate-400 block mt-2">Recommended: 32x32 or 64x64 square ICO/PNG</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Attribution / Powered By (Full width) -->
                <div class="lg:col-span-2 admin-card p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 pb-2.5 border-b border-slate-100">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <i class="fas fa-bolt text-emerald-600"></i> Storefront Footer Attribution
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Control the "Powered By" branding link displayed at the bottom of public pages.</p>
                        </div>
                        <label class="admin-toggle-switch" title="Enable Powered By in footer">
                            <input type="hidden" name="footer_powered_by_enabled" value="off">
                            <input type="checkbox" name="footer_powered_by_enabled" value="on" <?php echo ($s['powered_by_enabled'] ?? 'on') == 'on' ? 'checked' : ''; ?>>
                            <span class="toggle-track"><span class="toggle-thumb"></span></span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Prefix Label</label>
                            <input type="text" name="footer_powered_by_text" value="<?php echo htmlspecialchars($s['powered_by_text']); ?>" class="admin-input text-xs" placeholder="Powered By">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Brand / Organization Name</label>
                            <input type="text" name="footer_powered_by_name" value="<?php echo htmlspecialchars($s['powered_by_name']); ?>" class="admin-input text-xs" placeholder="Saastify">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Target Destination URL</label>
                            <input type="text" name="footer_powered_by_url" value="<?php echo htmlspecialchars($s['powered_by_url']); ?>" class="admin-input text-xs font-mono" placeholder="https://example.com">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: ECONOMICS & PAYMENTS -->
        <!-- ========================================== -->
        <div id="tab-pane-economics" class="tab-pane space-y-6 mt-6 <?php echo $active_tab === 'economics' ? '' : 'hidden'; ?>">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Economics & Shipping Rules -->
                <div class="admin-card p-5 sm:p-6 space-y-5">
                    <h3 class="text-sm font-bold text-slate-900 pb-2.5 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-coins text-amber-500"></i> Taxation & Shipping Thresholds
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">GST / Tax Percentage (%)</label>
                            <div class="relative">
                                <input type="number" step="0.01" name="tax_percentage" value="<?php echo htmlspecialchars($s['tax']); ?>" class="admin-input text-xs pr-8" placeholder="12.00">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-bold">%</span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">Calculated into order subtotal upon checkout.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Free Shipping Minimum (₹)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-bold">₹</span>
                                <input type="number" name="free_shipping_threshold" value="<?php echo htmlspecialchars($s['threshold']); ?>" class="admin-input pl-7 text-xs" placeholder="499">
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">Orders above this qualify for free shipping.</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Gateways -->
                <div class="admin-card p-5 sm:p-6 space-y-5">
                    <h3 class="text-sm font-bold text-slate-900 pb-2.5 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-credit-card text-indigo-500"></i> Checkout Payment Gateways
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-200 hover:border-slate-300 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm shrink-0">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-900">Cash on Delivery (COD)</p>
                                    <p class="text-[11px] text-slate-500">Allow customers to pay upon package receipt</p>
                                </div>
                            </div>
                            <label class="admin-toggle-switch ml-3" title="Toggle Cash on Delivery">
                                <input type="hidden" name="payment_cod_enabled" value="off">
                                <input type="checkbox" name="payment_cod_enabled" value="on" <?php echo get_setting('payment_cod_enabled', 'on') == 'on' ? 'checked' : ''; ?>>
                                <span class="toggle-track"><span class="toggle-thumb"></span></span>
                            </label>
                        </div>

                        <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-200 hover:border-slate-300 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm shrink-0">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-900">Online Payments (Razorpay)</p>
                                    <p class="text-[11px] text-slate-500">Instant UPI, Credit/Debit Cards, Netbanking</p>
                                </div>
                            </div>
                            <label class="admin-toggle-switch ml-3" title="Toggle Online Payments">
                                <input type="hidden" name="payment_online_enabled" value="off">
                                <input type="checkbox" name="payment_online_enabled" value="on" <?php echo get_setting('payment_online_enabled', 'on') == 'on' ? 'checked' : ''; ?>>
                                <span class="toggle-track"><span class="toggle-thumb"></span></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Product Storage & Freshness Accordion (Full width) -->
                <div class="lg:col-span-2 admin-card p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 pb-2.5 border-b border-slate-100">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <i class="fas fa-boxes-packing text-emerald-600"></i> Product Page "Storage & Freshness" Accordion
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Control the standard storage guidance displayed on single product detail pages.</p>
                        </div>
                        <label class="admin-toggle-switch" title="Enable on product details page">
                            <input type="hidden" name="storage_freshness_enabled" value="off">
                            <input type="checkbox" name="storage_freshness_enabled" value="on" <?php echo ($s['storage_enabled'] ?? 'on') == 'on' ? 'checked' : ''; ?>>
                            <span class="toggle-track"><span class="toggle-thumb"></span></span>
                        </label>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Accordion Title</label>
                            <input type="text" name="storage_freshness_title" value="<?php echo htmlspecialchars($s['storage_title']); ?>" class="admin-input text-xs" placeholder="Storage & Freshness">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Advice Guidelines Content</label>
                            <textarea name="storage_freshness_content" rows="4" class="admin-input text-xs resize-none" placeholder="Provide storage advice..."><?php echo htmlspecialchars($s['storage_content']); ?></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">Separate paragraphs with blank lines. Formats cleanly on storefront.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: SUPPORT & SOCIAL CHANNELS -->
        <!-- ========================================== -->
        <div id="tab-pane-contact" class="tab-pane space-y-6 mt-6 <?php echo $active_tab === 'contact' ? '' : 'hidden'; ?>">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Customer Support Channels -->
                <div class="admin-card p-5 sm:p-6 space-y-5">
                    <h3 class="text-sm font-bold text-slate-900 pb-2.5 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-headset text-sky-500"></i> Direct Customer Support Channels
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Official Support Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <i class="fas fa-envelope text-xs"></i>
                                </div>
                                <input type="email" name="support_email" value="<?php echo htmlspecialchars($s['email']); ?>" class="admin-input pl-8 text-xs" placeholder="support@driyum.com">
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">Displayed in order confirmation emails and help banners.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Support Hotline Phone</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <i class="fas fa-phone text-xs"></i>
                                </div>
                                <input type="text" name="support_phone" value="<?php echo htmlspecialchars($s['phone']); ?>" class="admin-input pl-8 text-xs font-mono" placeholder="+91 9419809801">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">WhatsApp Business Helpline</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-emerald-500">
                                    <i class="fab fa-whatsapp text-sm"></i>
                                </div>
                                <input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars($s['whatsapp']); ?>" class="admin-input pl-8 text-xs font-mono" placeholder="+919419809801">
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">Include country code for direct WhatsApp click-to-chat links.</p>
                        </div>
                    </div>
                </div>

                <!-- Social Media Presence -->
                <div class="admin-card p-5 sm:p-6 space-y-5">
                    <h3 class="text-sm font-bold text-slate-900 pb-2.5 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-share-nodes text-purple-500"></i> Social Media Profiles
                    </h3>
                    <div class="space-y-3.5">
                        <?php 
                        $social_fields = [
                            'instagram_url' => ['icon' => 'fab fa-instagram text-rose-500', 'label' => 'Instagram Profile', 'placeholder' => 'https://instagram.com/driyum'],
                            'facebook_url' => ['icon' => 'fab fa-facebook-f text-blue-600', 'label' => 'Facebook Page', 'placeholder' => 'https://facebook.com/driyum'],
                            'twitter_url' => ['icon' => 'fab fa-twitter text-sky-500', 'label' => 'Twitter / X Profile', 'placeholder' => 'https://x.com/driyum'],
                            'youtube_url' => ['icon' => 'fab fa-youtube text-red-600', 'label' => 'YouTube Channel', 'placeholder' => 'https://youtube.com/@driyum'],
                            'pinterest_url' => ['icon' => 'fab fa-pinterest text-red-500', 'label' => 'Pinterest Board', 'placeholder' => 'https://pinterest.com/driyum'],
                        ];
                        foreach ($social_fields as $field => $meta):
                            $val = $s[str_replace('_url', '', $field)];
                        ?>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1"><?php echo $meta['label']; ?></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="<?php echo $meta['icon']; ?> text-xs"></i>
                                    </div>
                                    <input type="text" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($val); ?>" placeholder="<?php echo $meta['placeholder']; ?>" class="admin-input pl-8 text-xs font-mono">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 4: MAINTENANCE MODE -->
        <!-- ========================================== -->
        <div id="tab-pane-maintenance" class="tab-pane space-y-6 mt-6 <?php echo $active_tab === 'maintenance' ? '' : 'hidden'; ?>">
            <!-- Status Card Alert -->
            <div class="p-4 sm:p-5 rounded-2xl border <?php echo $s['maintenance'] === 'on' ? 'bg-rose-50/70 border-rose-200' : 'bg-slate-50 border-slate-200'; ?> flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl <?php echo $s['maintenance'] === 'on' ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'; ?> flex items-center justify-center text-lg shrink-0">
                        <i class="fas <?php echo $s['maintenance'] === 'on' ? 'fa-triangle-exclamation' : 'fa-circle-check'; ?>"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">
                            Maintenance Mode: <?php echo $s['maintenance'] === 'on' ? '<span class="text-rose-600">ENABLED (Storefront Locked)</span>' : '<span class="text-emerald-700">DISABLED (Store Live)</span>'; ?>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            When active, regular customers see the maintenance splash screen. Logged-in administrators retain full access to this panel.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="text-xs font-bold text-slate-700"><?php echo $s['maintenance'] === 'on' ? 'Active' : 'Offline'; ?></span>
                    <label class="admin-toggle-switch toggle-danger" title="Toggle Maintenance Mode">
                        <input type="hidden" name="maintenance_mode" value="off">
                        <input type="checkbox" name="maintenance_mode" value="on" <?php echo $s['maintenance'] == 'on' ? 'checked' : ''; ?>>
                        <span class="toggle-track"><span class="toggle-thumb"></span></span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Public Notice & Messaging -->
                <div class="admin-card p-5 sm:p-6 space-y-5">
                    <h3 class="text-sm font-bold text-slate-900 pb-2.5 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-bullhorn text-amber-500"></i> Public Messaging & Copy
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Public Headline</label>
                            <input type="text" name="maintenance_headline" value="<?php echo htmlspecialchars($s['m_headline']); ?>" class="admin-input text-xs" placeholder="e.g. Upgrading Experience">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Detailed Notice Description</label>
                            <textarea name="maintenance_description" rows="3" class="admin-input text-xs resize-none" placeholder="We are performing scheduled updates..."><?php echo htmlspecialchars($s['m_desc']); ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pill Badge Text</label>
                                <input type="text" name="maintenance_mode_text" value="<?php echo htmlspecialchars($s['m_mode_text']); ?>" class="admin-input text-xs" placeholder="e.g. Maintenance Mode">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sticker Text</label>
                                <input type="text" name="maintenance_sticker_text" value="<?php echo htmlspecialchars($s['maintenance_sticker_text'] ?? 'Under Construction'); ?>" class="admin-input text-xs" placeholder="e.g. Under Construction">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Countdown & Newsletter Subtitle</label>
                            <input type="text" name="maintenance_countdown_label" value="<?php echo htmlspecialchars($s['m_countdown_label'] ?? 'WE WILL BE BACK SOON'); ?>" class="admin-input text-xs" placeholder="WE WILL BE BACK SOON">
                        </div>

                        <input type="hidden" name="maintenance_progress" value="<?php echo htmlspecialchars($s['m_progress']); ?>">
                        <input type="hidden" name="maintenance_status_label" value="<?php echo htmlspecialchars($s['m_status']); ?>">
                        <input type="hidden" name="maintenance_overlay_text" value="<?php echo htmlspecialchars(get_setting('maintenance_overlay_text', '')); ?>">
                    </div>
                </div>

                <!-- Launch Timer & Hero Image -->
                <div class="admin-card p-5 sm:p-6 space-y-5">
                    <h3 class="text-sm font-bold text-slate-900 pb-2.5 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-clock text-sky-500"></i> Countdown Timer & Visuals
                    </h3>
                    <div class="space-y-4">
                        <!-- Timer Box -->
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-slate-800">Display Launch Countdown Timer</span>
                                    <p class="text-[11px] text-slate-400">Shows ticking days/hours/minutes until target launch</p>
                                </div>
                                <label class="admin-toggle-switch" title="Toggle Launch Countdown Timer">
                                    <input type="hidden" name="maintenance_show_timer" value="off">
                                    <input type="checkbox" name="maintenance_show_timer" value="on" <?php echo $s['m_show_timer'] == 'on' ? 'checked' : ''; ?>>
                                    <span class="toggle-track"><span class="toggle-thumb"></span></span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Target Reopen Date & Time</label>
                                <input type="datetime-local" name="maintenance_end_date" value="<?php echo htmlspecialchars(get_setting('maintenance_end_date', '')); ?>" class="admin-input text-xs font-mono">
                            </div>
                        </div>

                        <!-- Hero Cover Image -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Maintenance Cover Banner</label>
                            <div class="border border-dashed border-slate-300 rounded-xl p-2.5 text-center group hover:border-primary transition-colors bg-slate-50/50">
                                <div class="aspect-video bg-slate-100 rounded-lg overflow-hidden relative flex items-center justify-center">
                                    <img id="m_image_preview" src="<?php echo $s['m_image'] ? '../'.$s['m_image'] : ''; ?>" class="w-full h-full object-cover <?php echo $s['m_image'] ? '' : 'hidden'; ?>">
                                    <div id="m_image_placeholder" class="text-slate-400 <?php echo $s['m_image'] ? 'hidden' : ''; ?>">
                                        <i class="fas fa-image text-2xl mb-1"></i>
                                        <span class="block text-[10px]">No Cover Image</span>
                                    </div>
                                    <label class="absolute inset-0 flex items-center justify-center bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-white text-xs font-semibold">
                                        <i class="fas fa-camera mr-1.5"></i> Change Cover Banner
                                        <input type="file" name="maintenance_image_file" id="m_image_input" class="hidden" accept="image/*">
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 5: SEO & SYSTEM CONFIGURATION -->
        <!-- ========================================== -->
        <div id="tab-pane-system" class="tab-pane space-y-6 mt-6 <?php echo $active_tab === 'system' ? '' : 'hidden'; ?>">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Search Engine Optimization -->
                <div class="admin-card p-5 sm:p-6 space-y-5">
                    <h3 class="text-sm font-bold text-slate-900 pb-2.5 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-search text-purple-500"></i> Global SEO & Snippets
                    </h3>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-slate-700">Default Meta Description</label>
                            <span id="seo-counter" class="text-[10px] text-slate-400 font-mono">0 / 160</span>
                        </div>
                        <textarea id="seo_description_input" name="seo_description" rows="4" class="admin-input text-xs resize-none" placeholder="Provide a search engine snippet..."><?php echo htmlspecialchars($s['seo_desc']); ?></textarea>
                        <p class="text-[11px] text-slate-400 mt-1.5">Recommended length: 150–160 characters. Appears on Google search results when no specific page description is set.</p>
                    </div>
                </div>

                <!-- Database Management & Backups -->
                <div class="admin-card p-5 sm:p-6 space-y-5">
                    <h3 class="text-sm font-bold text-slate-900 pb-2.5 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-database text-emerald-600"></i> Database Backups & Health
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Automated Frequency</label>
                            <select name="backup_frequency" class="admin-select text-xs">
                                <option value="manual" <?php echo $s['backup_freq'] == 'manual' ? 'selected' : ''; ?>>Manual Only</option>
                                <option value="daily" <?php echo $s['backup_freq'] == 'daily' ? 'selected' : ''; ?>>Daily (Recommended)</option>
                                <option value="weekly" <?php echo $s['backup_freq'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="monthly" <?php echo $s['backup_freq'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Schedule for recurring database snapshots.</p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase font-bold block tracking-wider">Last Recorded Backup</span>
                                <span class="text-xs font-mono font-semibold text-slate-800"><?php echo htmlspecialchars($s['last_backup']); ?></span>
                            </div>
                            <a href="backup_db.php?action=generate" class="btn-admin btn-admin-secondary text-xs py-1.5 px-3">
                                <i class="fas fa-download mr-1"></i> Dump SQL
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Bottom Save Bar on Mobile / Form Footer -->
        <div class="pt-6 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <p class="text-xs text-slate-400">Remember to save changes before leaving this page.</p>
            <div class="flex items-center gap-3">
                <button type="submit" name="update_settings" class="w-full sm:w-auto btn-admin btn-admin-primary text-xs py-2 px-5 shadow-sm">
                    <i class="fas fa-save mr-1.5"></i> Save All Settings
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function switchTab(tabId) {
    const validTabs = ['general', 'economics', 'contact', 'maintenance', 'system'];
    if (!validTabs.includes(tabId)) tabId = 'general';

    // Toggle panels
    validTabs.forEach(id => {
        const pane = document.getElementById('tab-pane-' + id);
        const btn = document.getElementById('tab-btn-' + id);
        if (pane) {
            pane.classList.toggle('hidden', id !== tabId);
        }
        if (btn) {
            if (id === tabId) {
                btn.className = 'settings-tab-btn flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all bg-white text-slate-900 shadow-sm border border-slate-200/60';
            } else {
                btn.className = 'settings-tab-btn flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all text-slate-600 hover:text-slate-900 hover:bg-white/50';
            }
        }
    });

    // Update active tab hidden input & url hash
    const input = document.getElementById('active_tab_input');
    if (input) input.value = tabId;
    if (history.replaceState) {
        history.replaceState(null, null, '#tab=' + tabId);
    }
}

// Auto-activate tab from URL Hash on page load
document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash;
    if (hash && hash.includes('tab=')) {
        const tab = hash.split('tab=')[1];
        if (tab) switchTab(tab);
    }

    // SEO Character Counter
    const seoInput = document.getElementById('seo_description_input');
    const seoCounter = document.getElementById('seo-counter');
    if (seoInput && seoCounter) {
        const updateSeo = () => {
            const len = seoInput.value.length;
            seoCounter.textContent = `${len} / 160`;
            seoCounter.className = len > 160 ? 'text-[10px] text-amber-600 font-mono font-bold' : 'text-[10px] text-slate-400 font-mono';
        };
        seoInput.addEventListener('input', updateSeo);
        updateSeo();
    }

    // Image Previews helper
    function setupFilePreview(inputId, previewId, placeholderId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const placeholder = document.getElementById(placeholderId);
        if (input && preview) {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        preview.src = evt.target.result;
                        preview.classList.remove('hidden');
                        if (placeholder) placeholder.classList.add('hidden');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    }

    setupFilePreview('m_image_input', 'm_image_preview', 'm_image_placeholder');
    setupFilePreview('logo_input', 'logo_preview', 'logo_placeholder');
    setupFilePreview('favicon_input', 'favicon_preview', 'favicon_placeholder');
});
</script>

<?php include 'includes/footer.php'; ?>

