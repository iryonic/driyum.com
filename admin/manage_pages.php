<?php
require_once 'includes/header.php';

$success = "";
$error = "";

// Handle Page Content Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pages'])) {
    $keys = [
        // About Page
        'about_hero_title', 'about_hero_subtitle', 'about_hero_desc',
        'about_story_title', 'about_story_text', 'about_story_quote',
        'about_tradition_title', 'about_tradition_text', 'about_twist_title', 
        'about_twist_text', 'about_tip_title', 'about_tip_text',
        'about_mission', 'about_vision',
        // Legal Page
        'legal_privacy_policy', 'legal_terms_conditions', 'legal_returns_refunds', 'legal_disclaimer'
    ];

    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            update_setting($key, $_POST[$key]);
        }
    }
    // Handle Image Uploads
    $image_fields = ['about_story_image', 'about_tradition_image'];
    foreach ($image_fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
            $target_dir = "../assets/images/uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            
            $filename = "page_" . uniqid() . "_" . basename($_FILES[$field]["name"]);
            if (move_uploaded_file($_FILES[$field]["tmp_name"], $target_dir . $filename)) {
                $path = "assets/images/uploads/" . $filename;
                
                // Delete old file if it exists and is an upload
                $old_path = get_setting($field, '');
                if (!empty($old_path) && strpos($old_path, 'assets/images/uploads/') === 0) {
                    if (file_exists("../" . $old_path)) @unlink("../" . $old_path);
                }
                
                update_setting($field, $path);
            }
        }
    }

    $success = "Page contents and visuals updated successfully!";
}

// Fetch Current Settings
$p = [
    'about_hero_title' => get_setting('about_hero_title', 'Born from <span class="text-[#19DC7E]">Frustration</span>,<br>Dried to <span class="text-amber-500">Perfection</span>.'),
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
?>

<form method="POST" enctype="multipart/form-data" class="pb-20 anim-up">
    <input type="hidden" name="update_pages" value="1">

    <!-- Top Action Bar -->
    <div class="sticky top-20 z-40 bg-white/80 backdrop-blur-md border-b border-gray-100 py-4 mb-8 -mx-4 px-4 md:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 fredoka tracking-tight">Page Content</h1>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Manage About & Legal Pages</p>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <!-- TAB NAVIGATION (Inline) -->
            <div class="flex bg-gray-100 p-1 rounded-xl">
                <button type="button" onclick="switchTab('about')" id="btn-about" class="px-6 py-2 rounded-lg font-black uppercase text-[10px] tracking-widest transition-all shadow-sm bg-white text-black">About</button>
                <button type="button" onclick="switchTab('legal')" id="btn-legal" class="px-6 py-2 rounded-lg font-black uppercase text-[10px] tracking-widest transition-all text-gray-400 hover:text-gray-600">Legal</button>
            </div>

            <button type="submit" name="update_pages" class="bg-black text-white px-8 py-3 rounded-xl font-bold uppercase text-xs tracking-widest hover:bg-[#19DC7E] hover:text-black transition-all shadow-lg flex items-center gap-2">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </div>

    <?php if($success): ?>
        <div class="mb-8 p-4 bg-green-50 text-green-700 rounded-2xl border border-green-100 font-bold text-xs anim-up flex items-center gap-3">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- ABOUT PAGE CONTENT -->
    <div id="tab-about" class="tab-content space-y-8">
        
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            
            <!-- Hero Section -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-50">
                    <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3 class="font-bold text-gray-900">Hero Section</h3>
                </div>
                
                <div class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Hero Title (HTML Allowed)</label>
                        <textarea name="about_hero_title" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-bold outline-none focus:bg-white focus:border-black transition-all h-24 resize-none"><?php echo htmlspecialchars($p['about_hero_title']); ?></textarea>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Subtitle</label>
                        <input type="text" name="about_hero_subtitle" value="<?php echo htmlspecialchars($p['about_hero_subtitle']); ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-bold outline-none focus:bg-white focus:border-black">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Short Description</label>
                        <textarea name="about_hero_desc" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-xs font-medium outline-none focus:bg-white focus:border-black transition-all h-20 resize-none"><?php echo htmlspecialchars($p['about_hero_desc']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Brand Story -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-green-50 text-green-500 flex items-center justify-center">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3 class="font-bold text-gray-900">Brand Story</h3>
                    </div>
                    <label class="cursor-pointer bg-black text-white px-3 py-1.5 rounded-lg text-[9px] font-bold uppercase hover:bg-[#19DC7E] hover:text-black transition-colors">
                        Change Image
                        <input type="file" name="about_story_image" class="hidden">
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-full">
                    <div class="order-2 md:order-1 space-y-4">
                         <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Story Title</label>
                            <input type="text" name="about_story_title" value="<?php echo htmlspecialchars($p['about_story_title']); ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-bold outline-none focus:bg-white focus:border-black">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Main Text</label>
                            <textarea name="about_story_text" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-xs font-medium outline-none focus:bg-white focus:border-black transition-all h-40 resize-none"><?php echo htmlspecialchars($p['about_story_text']); ?></textarea>
                        </div>
                    </div>
                    <div class="order-1 md:order-2">
                        <div class="aspect-square rounded-2xl overflow-hidden relative group/img">
                            <img src="../<?php echo $p['about_story_image']; ?>" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold">Current Image</div>
                        </div>
                    </div>
                </div>
            </div>

             <!-- Tradition -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 group hover:shadow-md transition-shadow xl:col-span-2">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center">
                            <i class="fas fa-landmark"></i>
                        </div>
                        <h3 class="font-bold text-gray-900">Tradition & Heritage</h3>
                    </div>
                    <label class="cursor-pointer bg-black text-white px-3 py-1.5 rounded-lg text-[9px] font-bold uppercase hover:bg-[#19DC7E] hover:text-black transition-colors">
                        Change Image
                        <input type="file" name="about_tradition_image" class="hidden">
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                     <div class="space-y-4">
                         <div class="aspect-video rounded-2xl overflow-hidden relative shadow-sm">
                            <img src="../<?php echo $p['about_tradition_image']; ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Section Title</label>
                            <input type="text" name="about_tradition_title" value="<?php echo htmlspecialchars($p['about_tradition_title']); ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-bold outline-none focus:bg-white focus:border-black">
                        </div>
                     </div>
                     <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1 sm:col-span-2">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Main Text</label>
                            <textarea name="about_tradition_text" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-xs font-medium outline-none focus:bg-white focus:border-black transition-all h-32 resize-none"><?php echo htmlspecialchars($p['about_tradition_text']); ?></textarea>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Modern Twist Title</label>
                            <input type="text" name="about_twist_title" value="<?php echo htmlspecialchars($p['about_twist_title']); ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-2 text-xs font-bold outline-none focus:bg-white focus:border-black">
                             <textarea name="about_twist_text" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-xs font-medium outline-none focus:bg-white focus:border-black transition-all h-24 resize-none mt-2"><?php echo htmlspecialchars($p['about_twist_text']); ?></textarea>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-3">Tip Title</label>
                            <input type="text" name="about_tip_title" value="<?php echo htmlspecialchars($p['about_tip_title']); ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-2 text-xs font-bold outline-none focus:bg-white focus:border-black">
                            <textarea name="about_tip_text" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-xs font-medium outline-none focus:bg-white focus:border-black transition-all h-24 resize-none mt-2"><?php echo htmlspecialchars($p['about_tip_text']); ?></textarea>
                        </div>
                     </div>
                </div>
            </div>

            <!-- Mission & Vision -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-50">
                    <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-500 flex items-center justify-center">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3 class="font-bold text-gray-900">Mission</h3>
                </div>
                <textarea name="about_mission" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-xs font-medium outline-none focus:bg-white focus:border-black transition-all h-32 resize-none"><?php echo htmlspecialchars($p['about_mission']); ?></textarea>
            </div>
             <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-50">
                    <div class="w-10 h-10 rounded-full bg-pink-50 text-pink-500 flex items-center justify-center">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="font-bold text-gray-900">Vision</h3>
                </div>
                <textarea name="about_vision" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-xs font-medium outline-none focus:bg-white focus:border-black transition-all h-32 resize-none"><?php echo htmlspecialchars($p['about_vision']); ?></textarea>
            </div>

        </div>
    </div>

    <!-- LEGAL PAGE CONTENT -->
    <div id="tab-legal" class="tab-content hidden space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Privacy Policy -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-50">
                    <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="font-bold text-gray-900">Privacy Policy</h3>
                </div>
                <textarea name="legal_privacy_policy" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-xs font-medium outline-none focus:bg-white focus:border-black transition-all h-80 resize-none leading-relaxed"><?php echo htmlspecialchars($p['legal_privacy_policy']); ?></textarea>
            </div>

            <!-- Terms -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-50">
                    <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <h3 class="font-bold text-gray-900">Terms & Conditions</h3>
                </div>
                 <textarea name="legal_terms_conditions" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-xs font-medium outline-none focus:bg-white focus:border-black transition-all h-80 resize-none leading-relaxed"><?php echo htmlspecialchars($p['legal_terms_conditions']); ?></textarea>
            </div>

             <!-- Returns -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-50">
                    <div class="w-10 h-10 rounded-full bg-orange-50 text-orange-500 flex items-center justify-center">
                        <i class="fas fa-undo"></i>
                    </div>
                    <h3 class="font-bold text-gray-900">Returns & Refunds</h3>
                </div>
                 <textarea name="legal_returns_refunds" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-xs font-medium outline-none focus:bg-white focus:border-black transition-all h-64 resize-none leading-relaxed"><?php echo htmlspecialchars($p['legal_returns_refunds']); ?></textarea>
            </div>

             <!-- Disclaimer -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-50">
                    <div class="w-10 h-10 rounded-full bg-red-50 text-red-500 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3 class="font-bold text-gray-900">Disclaimer</h3>
                </div>
                 <textarea name="legal_disclaimer" class="w-full bg-gray-50/50 border border-gray-100 rounded-xl px-4 py-3 text-xs font-medium outline-none focus:bg-white focus:border-black transition-all h-64 resize-none leading-relaxed"><?php echo htmlspecialchars($p['legal_disclaimer']); ?></textarea>
            </div>
        </div>
    </div>

</form>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    
    // Reset buttons
    const btns = ['about', 'legal'];
    btns.forEach(t => {
        const btn = document.getElementById('btn-' + t);
        if(t === tab) {
            btn.classList.remove('text-gray-400');
            btn.classList.add('bg-white', 'text-black', 'shadow-sm');
        } else {
            btn.classList.add('text-gray-400');
            btn.classList.remove('bg-white', 'text-black', 'shadow-sm');
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
