<?php
require_once 'includes/header.php';

$success = "";
$error = "";

// Handle Page Content Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pages'])) {
    $keys = [
        'about_hero_title', 'about_hero_subtitle', 'about_hero_desc',
        'about_story_title', 'about_story_text', 'about_story_quote',
        'about_tradition_title', 'about_tradition_text', 'about_twist_title', 
        'about_twist_text', 'about_tip_title', 'about_tip_text',
        'about_mission', 'about_vision',
        'legal_privacy_policy', 'legal_terms_conditions', 'legal_returns_refunds', 'legal_disclaimer'
    ];

    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            update_setting($key, $_POST[$key]);
        }
    }

    // Handle Image Uploads
    $image_fields = ['about_story_image', 'about_tradition_image'];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    foreach ($image_fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_exts)) {
                $target_dir = "../assets/images/uploads/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                
                $filename = "page_" . uniqid() . "." . $ext;
                if (move_uploaded_file($_FILES[$field]["tmp_name"], $target_dir . $filename)) {
                    $path = "assets/images/uploads/" . $filename;
                    
                    $old_path = get_setting($field, '');
                    if (!empty($old_path) && strpos($old_path, 'assets/images/uploads/') === 0) {
                        if (file_exists("../" . $old_path)) @unlink("../" . $old_path);
                    }
                    
                    update_setting($field, $path);
                }
            } else {
                $error = "Invalid file type for $field. Allowed types: " . implode(', ', $allowed_exts);
            }
        }
    }

    $success = "Page contents and visual assets updated successfully!";
}

// Fetch Current Settings
$p = [
    'about_hero_title' => get_setting('about_hero_title', 'Born from <span class="text-[#24B25D]">Frustration</span>,<br>Dried to <span class="text-amber-500">Perfection</span>.'),
    'about_hero_subtitle' => get_setting('about_hero_subtitle', 'The Driyum Journey'),
    'about_hero_desc' => get_setting('about_hero_desc', "We're on a mission to prove that healthy snacking shouldn't cost the earth or your health."),
    
    'about_story_title' => get_setting('about_story_title', 'The "Aha!" Moment'),
    'about_story_text' => get_setting('about_story_text', "Our whole journey started with a simple frustration: why were all the truly healthy snacks so expensive, while the cheap ones were loaded with processed ingredients?\n\nIt felt like we were filling our bodies with chemical experiments and guilt rather than a snack. We realized the market was missing something. So, we took matters into our own hands, deciding to create the perfect alternative."),
    'about_story_quote' => get_setting('about_story_quote', '"We simply select the best fruit and gently dehydrate it. That\'s it!"'),
    
    'about_tradition_title' => get_setting('about_tradition_title', 'HOKH SUIN'),
    'about_tradition_text' => get_setting('about_tradition_text', "With long, harsh winters and limited access to fresh produce, families would preserve vegetables during summer so the warmth and flavour of the season could be carried into winter meals. What was once an everyday practice has now become a delicacy — a way to taste tradition, history, and memory.\n\nAs modern lifestyles take over, this practice is slowly fading. \"Hokh suin by Driyum\" is our effort to revive this tradition and make it accessible again."),
    'about_twist_title' => get_setting('about_twist_title', 'Modern Twist on Ancient Traditions'),
    'about_twist_text' => get_setting('about_twist_text', 'Prepared from fresh vegetables, Hokh suin is traditionally dried, but unlike earlier times, it is handled with modern hygiene standards and packed carefully to ensure safety, quality, and convenience.'),
    'about_tip_title' => get_setting('about_tip_title', 'A Quick Tip for the Perfect Meal'),
    'about_tip_text' => get_setting('about_tip_text', 'In dried form, the vegetables may feel hard or chewy, which is natural. Once soaked or cooked, they soften and become ready for use in rice dishes, curries, and traditional meals. It is not just dehydrated vegetables — it is a piece of Kashmiri heritage, thoughtfully prepared and delivered to your doorstep.'),
    
    'about_mission' => get_setting('about_mission', 'Our mission is to make healthy snacking affordable, accessible, and convenient for everyone.'),
    'about_vision' => get_setting('about_vision', 'To build Driyum into one of India’s leading healthy snacking brands, starting from Kashmir and reaching across the country.'),
    
    'legal_privacy_policy' => get_setting('legal_privacy_policy', "At Driyum, we value your privacy and are committed to protecting your personal information. When you visit our website or place an order, we may collect basic details such as your name, phone number, email address, delivery address, and payment-related information.\n\nThis information is collected solely for the purpose of processing orders, providing customer support, and improving our services. We do not sell, rent, or share your personal data with third parties, except where required to complete your order (such as payment gateways and delivery partners) or when required by law."),
    'legal_terms_conditions' => get_setting('legal_terms_conditions', "By accessing and using the Driyum website, you agree to comply with these terms and conditions.\n\nAll products sold by Driyum are food products. Dehydrated fruits are intended for direct consumption, while dehydrated vegetables are intended for cooking purposes only. Product images shown on the website are for representation purposes only. Actual product colour, size, and texture may vary due to natural variations in fruits and vegetables."),
    'legal_returns_refunds' => get_setting('legal_returns_refunds', "Due to the nature of food products, returns are not accepted once an order has been delivered. Refunds may be considered only in special cases, including damaged packaging or unsealed pouches. To request a refund, customers are required to share a clear unboxing video of the package."),
    'legal_disclaimer' => get_setting('legal_disclaimer', "Driyum products are made using natural fruits and vegetables. As these are agricultural products, variations in colour, taste, texture, and appearance may occur. Nutritional values are approximate. Consult a professional before consumption if you have medical conditions."),
    'about_story_image' => get_setting('about_story_image', 'assets/images/about_story.jpg'),
    'about_tradition_image' => get_setting('about_tradition_image', 'assets/images/tradition.jpg')
];

$active_tab = isset($_GET['tab']) ? sanitize_input($_GET['tab']) : 'about';
if (!in_array($active_tab, ['about', 'legal'])) {
    $active_tab = 'about';
}
?>

<div class="space-y-6">
    <form method="POST" action="manage_pages.php?tab=<?php echo urlencode($active_tab); ?>" enctype="multipart/form-data">
        <input type="hidden" name="update_pages" value="1">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Content Management (CMS)</h1>
                <p class="text-sm text-slate-500 mt-0.5">Edit About page storytelling, cultural heritage narratives, and legal policy pages.</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex p-1 bg-slate-100 rounded-xl text-xs font-medium">
                    <a href="manage_pages.php?tab=about" id="btn-about" class="px-4 py-1.5 rounded-lg transition-all <?php echo $active_tab === 'about' ? 'bg-white text-slate-900 font-semibold shadow-sm' : 'text-slate-600 hover:text-slate-900'; ?>">
                        About Story
                    </a>
                    <a href="manage_pages.php?tab=legal" id="btn-legal" class="px-4 py-1.5 rounded-lg transition-all <?php echo $active_tab === 'legal' ? 'bg-white text-slate-900 font-semibold shadow-sm' : 'text-slate-600 hover:text-slate-900'; ?>">
                        Legal & Policies
                    </a>
                </div>

                <button type="submit" name="update_pages" class="btn-admin btn-admin-primary text-xs">
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

        <!-- ABOUT TAB -->
        <div id="tab-about" class="tab-content space-y-6 mt-6 <?php echo $active_tab === 'about' ? '' : 'hidden'; ?>">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Hero Section -->
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-heading text-amber-500"></i> Hero Section
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Subtitle Tagline</label>
                            <input type="text" name="about_hero_subtitle" value="<?php echo htmlspecialchars($p['about_hero_subtitle']); ?>" class="admin-input text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Hero Headline (HTML Allowed)</label>
                            <textarea name="about_hero_title" rows="2" class="admin-input text-xs resize-none font-bold"><?php echo htmlspecialchars($p['about_hero_title']); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Short Intro Description</label>
                            <textarea name="about_hero_desc" rows="3" class="admin-input text-xs resize-none"><?php echo htmlspecialchars($p['about_hero_desc']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Brand Story -->
                <div class="admin-card p-5">
                    <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i class="fas fa-book-open text-primary"></i> Brand Story
                        </h3>
                        <label class="btn-admin btn-admin-secondary text-xs cursor-pointer py-1 px-2.5">
                            <i class="fas fa-camera mr-1"></i> Change Photo
                            <input type="file" name="about_story_image" class="hidden" accept="image/*">
                        </label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2 space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Story Headline</label>
                                <input type="text" name="about_story_title" value="<?php echo htmlspecialchars($p['about_story_title']); ?>" class="admin-input text-xs font-bold">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Story Narrative</label>
                                <textarea name="about_story_text" rows="5" class="admin-input text-xs resize-none"><?php echo htmlspecialchars($p['about_story_text']); ?></textarea>
                            </div>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-700 mb-1.5">Current Visual</span>
                            <div class="aspect-square rounded-xl overflow-hidden border border-slate-200 bg-slate-100">
                                <img src="../<?php echo htmlspecialchars($p['about_story_image']); ?>" class="w-full h-full object-cover">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tradition & Heritage -->
                <div class="admin-card p-5 lg:col-span-2">
                    <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i class="fas fa-landmark text-indigo-500"></i> Tradition & Kashmiri Heritage
                        </h3>
                        <label class="btn-admin btn-admin-secondary text-xs cursor-pointer py-1 px-2.5">
                            <i class="fas fa-camera mr-1"></i> Change Photo
                            <input type="file" name="about_tradition_image" class="hidden" accept="image/*">
                        </label>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div>
                            <span class="block text-xs font-semibold text-slate-700 mb-1.5">Heritage Visual</span>
                            <div class="aspect-video rounded-xl overflow-hidden border border-slate-200 bg-slate-100 mb-3">
                                <img src="../<?php echo htmlspecialchars($p['about_tradition_image']); ?>" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Section Title</label>
                                <input type="text" name="about_tradition_title" value="<?php echo htmlspecialchars($p['about_tradition_title']); ?>" class="admin-input text-xs font-bold">
                            </div>
                        </div>
                        <div class="lg:col-span-2 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Main Tradition Narrative</label>
                                <textarea name="about_tradition_text" rows="4" class="admin-input text-xs resize-none"><?php echo htmlspecialchars($p['about_tradition_text']); ?></textarea>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Modern Twist Heading</label>
                                    <input type="text" name="about_twist_title" value="<?php echo htmlspecialchars($p['about_twist_title']); ?>" class="admin-input text-xs font-bold mb-2">
                                    <textarea name="about_twist_text" rows="3" class="admin-input text-xs resize-none"><?php echo htmlspecialchars($p['about_twist_text']); ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Culinary Tip Heading</label>
                                    <input type="text" name="about_tip_title" value="<?php echo htmlspecialchars($p['about_tip_title']); ?>" class="admin-input text-xs font-bold mb-2">
                                    <textarea name="about_tip_text" rows="3" class="admin-input text-xs resize-none"><?php echo htmlspecialchars($p['about_tip_text']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mission & Vision -->
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-bullseye text-purple-500"></i> Brand Mission
                    </h3>
                    <textarea name="about_mission" rows="4" class="admin-input text-xs resize-none"><?php echo htmlspecialchars($p['about_mission']); ?></textarea>
                </div>

                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-eye text-rose-500"></i> Brand Vision
                    </h3>
                    <textarea name="about_vision" rows="4" class="admin-input text-xs resize-none"><?php echo htmlspecialchars($p['about_vision']); ?></textarea>
                </div>
            </div>
        </div>

        <!-- LEGAL TAB -->
        <div id="tab-legal" class="tab-content space-y-6 mt-6 <?php echo $active_tab === 'legal' ? '' : 'hidden'; ?>">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Privacy Policy -->
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-user-shield text-blue-500"></i> Privacy Policy
                    </h3>
                    <textarea name="legal_privacy_policy" rows="12" class="admin-input text-xs resize-none leading-relaxed font-mono"><?php echo htmlspecialchars($p['legal_privacy_policy']); ?></textarea>
                </div>

                <!-- Terms & Conditions -->
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-file-contract text-indigo-500"></i> Terms & Conditions
                    </h3>
                    <textarea name="legal_terms_conditions" rows="12" class="admin-input text-xs resize-none leading-relaxed font-mono"><?php echo htmlspecialchars($p['legal_terms_conditions']); ?></textarea>
                </div>

                <!-- Returns & Refunds -->
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-undo text-amber-500"></i> Returns & Refund Policy
                    </h3>
                    <textarea name="legal_returns_refunds" rows="8" class="admin-input text-xs resize-none leading-relaxed font-mono"><?php echo htmlspecialchars($p['legal_returns_refunds']); ?></textarea>
                </div>

                <!-- Agricultural Disclaimer -->
                <div class="admin-card p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle text-rose-500"></i> Product Disclaimer
                    </h3>
                    <textarea name="legal_disclaimer" rows="8" class="admin-input text-xs resize-none leading-relaxed font-mono"><?php echo htmlspecialchars($p['legal_disclaimer']); ?></textarea>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function switchTab(tab) {
    window.location.href = 'manage_pages.php?tab=' + encodeURIComponent(tab);
}
</script>

<?php include 'includes/footer.php'; ?>
