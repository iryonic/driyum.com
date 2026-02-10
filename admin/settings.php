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
        'maintenance_countdown_label'
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
        $success = "Global configurations updated successfully!";
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
    'm_show_timer' => get_setting('maintenance_show_timer', 'on')
];
?>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 anim-up">
    <div>
        <h1 class="text-3xl font-black text-gray-900 crimson-pro tracking-tight">Settings</h1>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Manage global website configuration</p>
    </div>
    <div class="flex items-center gap-3">
        <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-2xl border border-gray-100 shadow-sm">
            <span class="w-1.5 h-1.5 rounded-full <?php echo $s['maintenance'] == 'on' ? 'bg-red-500 animate-pulse' : 'bg-[#24B25D]'; ?>"></span>
            <span class="text-[9px] font-black uppercase tracking-widest text-gray-600"><?php echo $s['maintenance'] == 'on' ? 'Maintenance' : 'Store Live'; ?></span>
        </div>
    </div>
</div>

<?php if($success): ?>
    <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-2xl border border-green-100 font-bold text-xs anim-up flex items-center gap-3">
        <i class="fas fa-check-circle"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100 font-bold text-xs anim-up flex items-center gap-3">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="pb-20 anim-up">
    
    <!-- Top Action Bar -->
     <div class="sticky top-20 z-40 bg-white/80 backdrop-blur-md border-b border-gray-100 py-4 mb-8 -mx-4 px-4 md:px-8 flex flex-wrap gap-2 justify-between items-center">
        <h2 class="text-xl font-black font-crimson-pro">Configuration</h2>
        <button type="submit" name="update_settings" class="bg-black text-white px-8 py-3 rounded-xl font-bold uppercase text-xs tracking-widest hover:bg-[#24B25D] hover:text-black transition-all shadow-lg flex items-center gap-2">
            <i class="fas fa-save"></i> Save Changes
        </button>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        
        <!-- COLUMN 1: BRAND & BUSINESS -->
        <div class="space-y-8">
            <!-- Store Identity -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-50">
                    <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3 class="font-bold text-gray-900">Brand Identity</h3>
                </div>
                
                <div class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Store Name</label>
                        <input type="text" name="store_name" value="<?php echo htmlspecialchars($s['name']); ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-bold outline-none focus:bg-white focus:border-black transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Order Prefix</label>
                        <input type="text" name="order_prefix" value="<?php echo htmlspecialchars($s['order_prefix']); ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-bold outline-none focus:bg-white focus:border-black transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Footer Text</label>
                        <textarea name="footer_description" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-medium outline-none focus:bg-white focus:border-black transition-all h-24 resize-none"><?php echo htmlspecialchars($s['footer_desc']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Tax & Shipping -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-50">
                    <div class="w-10 h-10 rounded-full bg-green-50 text-green-500 flex items-center justify-center">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h3 class="font-bold text-gray-900">Economics</h3>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Tax (%)</label>
                        <input type="number" step="0.01" name="tax_percentage" value="<?php echo $s['tax']; ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-bold outline-none focus:bg-white focus:border-black transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Free Ship (₹)</label>
                        <input type="number" name="free_shipping_threshold" value="<?php echo $s['threshold']; ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-bold outline-none focus:bg-white focus:border-black transition-all">
                    </div>
                </div>
            </div>

            <!-- Visual Assets -->

        </div>

        <!-- COLUMN 2: CONTACT & SEO -->
        <div class="space-y-8">
            
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-50">
                    <div class="w-10 h-10 rounded-full bg-orange-50 text-orange-500 flex items-center justify-center">
                        <i class="fas fa-address-book"></i>
                    </div>
                    <h3 class="font-bold text-gray-900">Contact & Social</h3>
                </div>

                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                             <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Email</label>
                             <input type="email" name="support_email" value="<?php echo htmlspecialchars($s['email']); ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-2 text-xs font-bold outline-none focus:bg-white focus:border-black">
                        </div>
                        <div class="space-y-1">
                             <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Phone</label>
                             <input type="text" name="support_phone" value="<?php echo htmlspecialchars($s['phone']); ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-2 text-xs font-bold outline-none focus:bg-white focus:border-black">
                        </div>
                    </div>
                    
                    <div class="h-px bg-gray-50 my-2"></div>

                    <?php 
                    $social_fields = [
                        'instagram_url' => ['icon'=>'fab fa-instagram', 'label'=>'Instagram'],
                        'whatsapp_number' => ['icon'=>'fab fa-whatsapp', 'label'=>'WhatsApp'],
                        'facebook_url' => ['icon'=>'fab fa-facebook-f', 'label'=>'Facebook'],
                        'twitter_url' => ['icon'=>'fab fa-twitter', 'label'=>'Twitter'],
                        'youtube_url' => ['icon'=>'fab fa-youtube', 'label'=>'YouTube'],
                        'pinterest_url' => ['icon'=>'fab fa-pinterest', 'label'=>'Pinterest'],
                    ];
                    foreach($social_fields as $field => $meta):
                        $val = $field === 'whatsapp_number' ? $s['whatsapp'] : $s[str_replace('_url', '', $field)];
                    ?>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-300">
                            <i class="<?php echo $meta['icon']; ?>"></i>
                        </div>
                        <input type="text" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($val); ?>" placeholder="<?php echo $meta['label']; ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl pl-10 pr-4 py-2.5 text-xs font-medium outline-none focus:bg-white focus:border-black transition-all">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- SEO -->
            <div class="bg-black text-white rounded-3xl p-6 shadow-xl">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-white/10">
                    <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="font-bold">SEO Metadata</h3>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest ml-3">Meta Description</label>
                    <textarea name="seo_description" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-xs text-gray-300 outline-none focus:border-[#24B25D] transition-all h-28 resize-none"><?php echo htmlspecialchars($s['seo_desc']); ?></textarea>
                </div>
            </div>

        </div>

        <!-- COLUMN 3: MAINTENANCE -->
        <div class="space-y-8">
             <div class="bg-gray-50 border border-gray-200 rounded-3xl p-6 relative overflow-hidden">
                <!-- Status Indicator -->
                <div class="absolute top-0 right-0 p-6">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="maintenance_mode" value="off">
                        <input type="checkbox" name="maintenance_mode" value="on" class="sr-only peer" <?php echo $s['maintenance'] == 'on' ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-500"></div>
                    </label>
                </div>

                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200/50 pt-2">
                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-500 flex items-center justify-center">
                        <i class="fas fa-hammer"></i>
                    </div>
                    <a href="<?php echo get_url('maintenance'); ?>" class="font-bold text-gray-900">Maintenance Page</a>
                </div>

                <div class="space-y-5">
                    
                    <!-- Content -->
                    <div class="space-y-3">
                        <h4 class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Public Content</h4>
                        <input type="text" name="maintenance_headline" value="<?php echo htmlspecialchars($s['m_headline']); ?>" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:border-black" placeholder="Headline">
                        <textarea name="maintenance_description" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-xs font-medium outline-none focus:border-black h-20 resize-none" placeholder="Description"><?php echo htmlspecialchars($s['m_desc']); ?></textarea>
                    </div>

                    <!-- Visuals -->
                    <div class="space-y-3">
                        <h4 class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Visual Style</h4>
                        <input type="text" name="maintenance_mode_text" value="<?php echo htmlspecialchars($s['m_mode_text']); ?>" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2 text-xs font-medium focus:border-black" placeholder="Badge Text (e.g. Maintenance Mode)">
                        <input type="text" name="maintenance_sticker_text" value="<?php echo htmlspecialchars($s['maintenance_sticker_text'] ?? 'Under Construction'); ?>" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2 text-xs font-medium focus:border-black" placeholder="Sticker Text (e.g. Under Construction)">
                        <input type="text" name="maintenance_overlay_text" value="<?php echo htmlspecialchars($s['maintenance_overlay_text'] ?? 'We will be back.'); ?>" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2 text-xs font-medium focus:border-black" placeholder="Image Overlay Text">
                        <input type="text" name="maintenance_countdown_label" value="<?php echo htmlspecialchars($s['m_countdown_label'] ?? 'WE WILL BE BACK SUBSCRIBE US TILL THEN'); ?>" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2 text-xs font-medium focus:border-black" placeholder="Countdown Label">

                    </div>

                    <!-- Timer -->
                     <div class="bg-white p-4 rounded-xl border border-gray-200">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-[10px] font-black uppercase text-gray-400">Launch Timer</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="maintenance_show_timer" value="off">
                                <input type="checkbox" name="maintenance_show_timer" value="on" class="sr-only peer" <?php echo $s['m_show_timer'] == 'on' ? 'checked' : ''; ?>>
                                <div class="w-8 h-4 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-black"></div>
                            </label>
                        </div>
                        <input type="datetime-local" name="maintenance_end_date" value="<?php echo get_setting('maintenance_end_date', ''); ?>" class="w-full bg-gray-50 border-none rounded-lg px-3 py-2 text-xs font-bold">
                    </div>

                    <!-- Hero Image -->
                    <div class="space-y-2">
                        <h4 class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Hero Image</h4>
                        <div class="relative group bg-white border border-dashed border-gray-300 rounded-xl p-1 hover:border-black transition-colors">
                            <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden relative flex items-center justify-center">
                                <img id="m_image_preview" src="<?php echo $s['m_image'] ? '../'.$s['m_image'] : ''; ?>" class="w-full h-full object-cover <?php echo $s['m_image'] ? '' : 'hidden'; ?>">
                                <div id="m_image_placeholder" class="text-gray-300 <?php echo $s['m_image'] ? 'hidden' : ''; ?>">
                                    <i class="fas fa-image text-3xl"></i>
                                </div>
                                
                                <label class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-white text-xs font-bold uppercase tracking-widest z-10">
                                    Upload New
                                    <input type="file" name="maintenance_image_file" id="m_image_input" class="hidden" accept="image/*">
                                </label>
                            </div>
                        </div>
                        <p class="text-[9px] text-gray-400 text-center">Recommended: 800x600px</p>
                    </div>

                </div>
             </div>
        </div>

    </div>
</form>

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


