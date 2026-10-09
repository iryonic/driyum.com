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
        'storage_freshness_content'
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
    'storage_content' => get_setting('storage_freshness_content', "Store in a cool, dry place away from direct sunlight. Once opened, keep in an airtight container or seal the ziplock pouch tightly.\n\nBest consumed within 6 months from packaging date for maximum crunch and natural sweetness.")
];
?>

<div class="space-y-6">
    <form method="POST" enctype="multipart/form-data">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Store Settings</h1>
                <p class="text-sm text-slate-500 mt-0.5">Manage store parameters, payment rules, SEO metadata, and maintenance status.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $s['maintenance'] == 'on' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'; ?>">
                    <span class="w-2 h-2 rounded-full <?php echo $s['maintenance'] == 'on' ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500'; ?>"></span>
                    <?php echo $s['maintenance'] == 'on' ? 'Maintenance Active' : 'Store Live'; ?>
                </span>
                <button type="submit" name="update_settings" class="btn-admin btn-admin-primary text-xs">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="mt-4 p-3.5 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 text-xs font-medium flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-emerald-600"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times text-xs"></i></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mt-4 p-3.5 bg-rose-50 text-rose-800 rounded-xl border border-rose-200 text-xs font-medium flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-circle text-rose-600"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fas fa-times text-xs"></i></button>
            </div>
        <?php endif; ?>

        <!-- 3-Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            
            <!-- COLUMN 1: BRAND, ECONOMICS, PAYMENTS -->
            <div class="space-y-6">
                <!-- Brand Identity -->
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-store text-primary"></i> Brand Identity
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Store Name</label>
                            <input type="text" name="store_name" value="<?php echo htmlspecialchars($s['name']); ?>" class="admin-input text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Order Number Prefix</label>
                            <input type="text" name="order_prefix" value="<?php echo htmlspecialchars($s['order_prefix']); ?>" class="admin-input text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Footer Description</label>
                            <textarea name="footer_description" rows="3" class="admin-input text-xs resize-none"><?php echo htmlspecialchars($s['footer_desc']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Economics & Shipping Rules -->
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-coins text-amber-500"></i> Economics & Tax
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">GST / Tax (%)</label>
                            <input type="number" step="0.01" name="tax_percentage" value="<?php echo htmlspecialchars($s['tax']); ?>" class="admin-input text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Free Ship Over (₹)</label>
                            <input type="number" name="free_shipping_threshold" value="<?php echo htmlspecialchars($s['threshold']); ?>" class="admin-input text-xs">
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-credit-card text-indigo-500"></i> Payment Gateways
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <div>
                                <p class="text-xs font-semibold text-slate-900">Cash on Delivery (COD)</p>
                                <p class="text-[11px] text-slate-400">Allow customers to pay upon delivery</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="payment_cod_enabled" value="off">
                                <input type="checkbox" name="payment_cod_enabled" value="on" class="rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer" <?php echo get_setting('payment_cod_enabled', 'on') == 'on' ? 'checked' : ''; ?>>
                            </label>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <div>
                                <p class="text-xs font-semibold text-slate-900">Online Payments (Razorpay)</p>
                                <p class="text-[11px] text-slate-400">Accept UPI, Credit Cards, Netbanking</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="payment_online_enabled" value="off">
                                <input type="checkbox" name="payment_online_enabled" value="on" class="rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer" <?php echo get_setting('payment_online_enabled', 'on') == 'on' ? 'checked' : ''; ?>>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Storage & Freshness Accordion -->
                <div class="admin-card p-5">
                    <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i class="fas fa-boxes-packing text-emerald-600"></i> Storage & Freshness
                        </h3>
                        <label class="relative inline-flex items-center cursor-pointer" title="Enable on product details page">
                            <input type="hidden" name="storage_freshness_enabled" value="off">
                            <input type="checkbox" name="storage_freshness_enabled" value="on" class="rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer" <?php echo ($s['storage_enabled'] ?? 'on') == 'on' ? 'checked' : ''; ?>>
                        </label>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tab Title</label>
                            <input type="text" name="storage_freshness_title" value="<?php echo htmlspecialchars($s['storage_title']); ?>" class="admin-input text-xs" placeholder="Storage & Freshness">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Advice Guidelines</label>
                            <textarea name="storage_freshness_content" rows="4" class="admin-input text-xs resize-none" placeholder="Provide storage advice..."><?php echo htmlspecialchars($s['storage_content']); ?></textarea>
                            <p class="text-[10px] text-slate-400 mt-1">Separate paragraphs with blank lines.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMN 2: CONTACT, SEO, BACKUP -->
            <div class="space-y-6">
                <!-- Contact & Socials -->
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-address-book text-sky-500"></i> Support & Social Media
                    </h3>
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Support Email</label>
                                <input type="email" name="support_email" value="<?php echo htmlspecialchars($s['email']); ?>" class="admin-input text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Phone / Hotline</label>
                                <input type="text" name="support_phone" value="<?php echo htmlspecialchars($s['phone']); ?>" class="admin-input text-xs font-mono">
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-100 space-y-2.5">
                            <?php 
                            $social_fields = [
                                'whatsapp_number' => ['icon' => 'fab fa-whatsapp text-emerald-500', 'placeholder' => 'WhatsApp Number with country code'],
                                'instagram_url' => ['icon' => 'fab fa-instagram text-rose-500', 'placeholder' => 'Instagram Profile URL'],
                                'facebook_url' => ['icon' => 'fab fa-facebook-f text-blue-600', 'placeholder' => 'Facebook Page URL'],
                                'twitter_url' => ['icon' => 'fab fa-twitter text-sky-500', 'placeholder' => 'Twitter/X URL'],
                                'youtube_url' => ['icon' => 'fab fa-youtube text-red-600', 'placeholder' => 'YouTube Channel URL'],
                                'pinterest_url' => ['icon' => 'fab fa-pinterest text-red-500', 'placeholder' => 'Pinterest URL'],
                            ];
                            foreach ($social_fields as $field => $meta):
                                $val = ($field === 'whatsapp_number') ? $s['whatsapp'] : $s[str_replace('_url', '', $field)];
                            ?>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="<?php echo $meta['icon']; ?> text-xs"></i>
                                    </div>
                                    <input type="text" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($val); ?>" placeholder="<?php echo $meta['placeholder']; ?>" class="admin-input pl-8 text-xs">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- SEO Metadata -->
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-search text-purple-500"></i> SEO & Search Meta
                    </h3>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Global Meta Description</label>
                        <textarea name="seo_description" rows="3" class="admin-input text-xs resize-none" placeholder="Provide a search snippet..."><?php echo htmlspecialchars($s['seo_desc']); ?></textarea>
                        <p class="text-[10px] text-slate-400 mt-1">Recommended length: 150–160 characters.</p>
                    </div>
                </div>

                <!-- Data Backups -->
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-database text-emerald-600"></i> Database & Backups
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
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Last Backup</span>
                                <span class="text-xs font-mono font-medium text-slate-800"><?php echo htmlspecialchars($s['last_backup']); ?></span>
                            </div>
                            <a href="backup_db.php?action=generate" class="btn-admin btn-admin-secondary text-xs py-1.5">
                                <i class="fas fa-download mr-1"></i> Dump SQL
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMN 3: MAINTENANCE MODE -->
            <div class="space-y-6">
                <div class="admin-card p-5 border-rose-200/60">
                    <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i class="fas fa-hammer text-rose-500"></i> Maintenance Mode
                        </h3>
                        <label class="relative inline-flex items-center cursor-pointer" title="Enable Maintenance Mode">
                            <input type="hidden" name="maintenance_mode" value="off">
                            <input type="checkbox" name="maintenance_mode" value="on" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer" <?php echo $s['maintenance'] == 'on' ? 'checked' : ''; ?>>
                        </label>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Public Headline</label>
                            <input type="text" name="maintenance_headline" value="<?php echo htmlspecialchars($s['m_headline']); ?>" class="admin-input text-xs" placeholder="e.g. Upgrading Experience">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Public Notice / Description</label>
                            <textarea name="maintenance_description" rows="3" class="admin-input text-xs resize-none" placeholder="We are performing scheduled updates..."><?php echo htmlspecialchars($s['m_desc']); ?></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Badge & Sticker Text</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" name="maintenance_mode_text" value="<?php echo htmlspecialchars($s['m_mode_text']); ?>" class="admin-input text-xs" placeholder="Badge text">
                                <input type="text" name="maintenance_sticker_text" value="<?php echo htmlspecialchars($s['maintenance_sticker_text'] ?? 'Under Construction'); ?>" class="admin-input text-xs" placeholder="Sticker text">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Overlay & Countdown Label</label>
                            <input type="text" name="maintenance_countdown_label" value="<?php echo htmlspecialchars($s['m_countdown_label'] ?? 'WE WILL BE BACK SOON'); ?>" class="admin-input text-xs">
                        </div>

                        <!-- Timer -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-700">Display Launch Timer</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="maintenance_show_timer" value="off">
                                    <input type="checkbox" name="maintenance_show_timer" value="on" class="rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer" <?php echo $s['m_show_timer'] == 'on' ? 'checked' : ''; ?>>
                                </label>
                            </div>
                            <input type="datetime-local" name="maintenance_end_date" value="<?php echo htmlspecialchars(get_setting('maintenance_end_date', '')); ?>" class="admin-input text-xs">
                        </div>

                        <!-- Hero Image Preview -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Cover Image</label>
                            <div class="border border-dashed border-slate-300 rounded-xl p-2 text-center group hover:border-primary transition-colors">
                                <div class="aspect-video bg-slate-100 rounded-lg overflow-hidden relative flex items-center justify-center">
                                    <img id="m_image_preview" src="<?php echo $s['m_image'] ? '../'.$s['m_image'] : ''; ?>" class="w-full h-full object-cover <?php echo $s['m_image'] ? '' : 'hidden'; ?>">
                                    <div id="m_image_placeholder" class="text-slate-400 <?php echo $s['m_image'] ? 'hidden' : ''; ?>">
                                        <i class="fas fa-image text-2xl"></i>
                                    </div>
                                    <label class="absolute inset-0 flex items-center justify-center bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-white text-xs font-semibold">
                                        <i class="fas fa-camera mr-1.5"></i> Change Photo
                                        <input type="file" name="maintenance_image_file" id="m_image_input" class="hidden" accept="image/*">
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('m_image_input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('m_image_preview');
            const placeholder = document.getElementById('m_image_placeholder');
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
