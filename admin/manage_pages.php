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
    'about_twist_text' => get_setting('about_twist_text', 'Prepared from fresh vegetables, Hokh suin is traditionally sun-dried, but unlike earlier times, it is handled with modern hygiene standards and packed carefully to ensure safety, quality, and convenience.'),
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

<div class="max-w-7xl mx-auto pb-40">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
        <div>
            <h1 class="text-5xl font-black text-gray-900 fredoka mb-2 tracking-tight">Page Architect</h1>
            <p class="text-gray-500 font-bold uppercase tracking-widest text-[10px]">Manage dynamic content for About & Legal pages</p>
        </div>
        <div class="w-16 h-16 bg-[#19DC7E] text-black rounded-[24px] flex items-center justify-center text-2xl shadow-xl rotate-3">
            <i class="fas fa-file-alt"></i>
        </div>
    </div>

    <?php if($success): ?>
        <div class="mb-10 p-6 bg-green-50 text-green-600 rounded-[35px] border border-green-100 flex items-center gap-4 anim-up">
            <i class="fas fa-check-circle text-2xl"></i>
            <p class="font-bold"><?php echo $success; ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-12">
        <input type="hidden" name="update_pages" value="1">

        <!-- TAB NAVIGATION -->
        <div class="flex gap-4 mb-8 overflow-x-auto pb-2">
            <button type="button" onclick="switchTab('about')" id="btn-about" class="tab-btn px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-xs transition-all bg-black text-white">About Us Page</button>
            <button type="button" onclick="switchTab('legal')" id="btn-legal" class="tab-btn px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-xs transition-all bg-white text-gray-400 border border-gray-100 italic">Legal & Policies</button>
        </div>

        <!-- ABOUT PAGE CONTENT -->
        <div id="tab-about" class="space-y-12 tab-content">
            <!-- Hero Section -->
            <div class="bg-white rounded-[48px] p-10 shadow-sm border border-gray-100">
                <h3 class="font-black text-gray-900 text-xl fredoka mb-8 flex items-center gap-3">
                    <span class="w-10 h-10 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-rocket"></i></span>
                    Hero Section
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Hero Title (HTML Allowed)</label>
                        <textarea name="about_hero_title" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-[28px] px-6 py-5 outline-none font-bold text-2xl h-32"><?php echo htmlspecialchars($p['about_hero_title']); ?></textarea>
                    </div>
                    <div class="space-y-8">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Hero Subtitle</label>
                            <input type="text" name="about_hero_subtitle" value="<?php echo htmlspecialchars($p['about_hero_subtitle']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-[20px] px-6 py-4 outline-none font-bold">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Mission Statement (Short)</label>
                            <textarea name="about_hero_desc" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-[28px] px-6 py-4 outline-none font-medium h-24"><?php echo htmlspecialchars($p['about_hero_desc']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Brand Story -->
            <div class="bg-white rounded-[48px] p-10 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between gap-4 mb-8">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 bg-[#19DC7E]/10 text-[#19DC7E] rounded-xl flex items-center justify-center text-sm"><i class="fas fa-book-open"></i></span>
                            <h3 class="font-black text-gray-900 text-xl fredoka">Brand Story</h3>
                        </div>
                        <label class="cursor-pointer bg-gray-50 hover:bg-black hover:text-[#19DC7E] text-gray-500 px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border border-gray-100 italic">
                            Change Story Image
                            <input type="file" name="about_story_image" class="hidden" onchange="this.form.submit()">
                        </label>
                    </div>
                    <div class="mb-8 relative group max-w-sm">
                        <img src="../<?php echo $p['about_story_image']; ?>" class="rounded-3xl h-40 w-full object-cover shadow-inner opacity-80">
                        <div class="absolute inset-0 bg-black/20 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold">Current Visual</div>
                    </div>
                <div class="space-y-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Story Heading</label>
                        <input type="text" name="about_story_title" value="<?php echo htmlspecialchars($p['about_story_title']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-[20px] px-6 py-4 outline-none font-bold text-xl">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Main Story Text</label>
                            <textarea name="about_story_text" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-[28px] px-6 py-6 outline-none font-medium h-64 border-dashed"><?php echo htmlspecialchars($p['about_story_text']); ?></textarea>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-4">Highlight Quote</label>
                            <textarea name="about_story_quote" class="w-full bg-[#FFFBEB] border-2 border-amber-100 focus:border-amber-400 rounded-[28px] px-8 py-8 outline-none font-black italic text-gray-900 h-64 leading-relaxed"><?php echo htmlspecialchars($p['about_story_quote']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Heritage Section -->
            <div class="bg-gray-900 rounded-[48px] p-10 shadow-sm text-white">
                    <div class="flex items-center justify-between gap-4 mb-8">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 bg-white/10 text-white rounded-xl flex items-center justify-center text-sm"><i class="fas fa-history"></i></span>
                            <h3 class="font-black text-white text-xl fredoka">Tradition & Heritage</h3>
                        </div>
                        <label class="cursor-pointer bg-white/10 hover:bg-white hover:text-black text-white px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border border-white/5 italic">
                            Change Heritage Image
                            <input type="file" name="about_tradition_image" class="hidden" onchange="this.form.submit()">
                        </label>
                    </div>
                    <div class="mb-8 relative group max-w-sm">
                        <img src="../<?php echo $p['about_tradition_image']; ?>" class="rounded-3xl h-40 w-full object-cover shadow-inner opacity-50">
                    </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-white/40 ml-4">Section Heading</label>
                            <input type="text" name="about_tradition_title" value="<?php echo htmlspecialchars($p['about_tradition_title']); ?>" class="w-full bg-white/5 border-2 border-transparent focus:border-white/20 rounded-[20px] px-6 py-4 outline-none font-black text-2xl text-white">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-white/40 ml-4">Heritage Description</label>
                            <textarea name="about_tradition_text" class="w-full bg-white/5 border-2 border-transparent focus:border-white/20 rounded-[28px] px-6 py-6 outline-none font-medium h-60 text-white/70"><?php echo htmlspecialchars($p['about_tradition_text']); ?></textarea>
                        </div>
                    </div>
                    <div class="space-y-8">
                        <div class="bg-white/5 p-8 rounded-[40px] border border-white/10 space-y-4">
                            <input type="text" name="about_twist_title" value="<?php echo htmlspecialchars($p['about_twist_title']); ?>" class="w-full bg-transparent border-b border-white/10 focus:border-[#19DC7E] outline-none font-black text-lg text-white mb-2" placeholder="Twist Title">
                            <textarea name="about_twist_text" class="w-full bg-transparent outline-none font-medium h-24 text-white/60 text-sm" placeholder="Twist Description"><?php echo htmlspecialchars($p['about_twist_text']); ?></textarea>
                        </div>
                        <div class="bg-white/5 p-8 rounded-[40px] border border-white/10 space-y-4">
                            <input type="text" name="about_tip_title" value="<?php echo htmlspecialchars($p['about_tip_title']); ?>" class="w-full bg-transparent border-b border-white/10 focus:border-amber-400 outline-none font-black text-lg text-white mb-2" placeholder="Tip Title">
                            <textarea name="about_tip_text" class="w-full bg-transparent outline-none font-medium h-24 text-white/60 text-sm" placeholder="Tip Description"><?php echo htmlspecialchars($p['about_tip_text']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mission & Vision -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white rounded-[40px] p-10 border border-gray-100 shadow-sm space-y-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-[#19DC7E]/10 text-[#19DC7E] rounded-xl flex items-center justify-center"><i class="fas fa-rocket"></i></div>
                        <h4 class="font-black text-gray-900 fredoka text-lg">Our Mission</h4>
                    </div>
                    <textarea name="about_mission" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-[28px] px-6 py-5 outline-none font-bold text-gray-600 h-32 leading-relaxed"><?php echo htmlspecialchars($p['about_mission']); ?></textarea>
                </div>
                <div class="bg-white rounded-[40px] p-10 border border-gray-100 shadow-sm space-y-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-indigo-50 text-indigo-500 rounded-xl flex items-center justify-center"><i class="fas fa-eye"></i></div>
                        <h4 class="font-black text-gray-900 fredoka text-lg">Our Vision</h4>
                    </div>
                    <textarea name="about_vision" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-[28px] px-6 py-5 outline-none font-bold text-gray-600 h-32 leading-relaxed"><?php echo htmlspecialchars($p['about_vision']); ?></textarea>
                </div>
            </div>
        </div>

        <!-- LEGAL PAGE CONTENT -->
        <div id="tab-legal" class="hidden space-y-12 tab-content">
            <div class="grid grid-cols-1 gap-12">
                <!-- Privacy Policy -->
                <div class="bg-white rounded-[48px] p-10 shadow-sm border border-gray-100">
                    <h3 class="font-black text-gray-900 text-xl fredoka mb-6 flex items-center gap-3">
                        <span class="w-10 h-10 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-shield-alt"></i></span>
                        Privacy Policy
                    </h3>
                    <textarea name="legal_privacy_policy" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-[32px] px-8 py-8 outline-none font-medium h-64 leading-relaxed"><?php echo htmlspecialchars($p['legal_privacy_policy']); ?></textarea>
                </div>

                <!-- Terms & Conditions -->
                <div class="bg-white rounded-[48px] p-10 shadow-sm border border-gray-100">
                    <h3 class="font-black text-gray-900 text-xl fredoka mb-6 flex items-center gap-3">
                        <span class="w-10 h-10 bg-indigo-50 text-indigo-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-file-contract"></i></span>
                        Terms & Conditions
                    </h3>
                    <textarea name="legal_terms_conditions" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-[32px] px-8 py-8 outline-none font-medium h-64 leading-relaxed"><?php echo htmlspecialchars($p['legal_terms_conditions']); ?></textarea>
                </div>

                <!-- Returns & Refunds -->
                <div class="bg-white rounded-[48px] p-10 shadow-sm border border-gray-100">
                    <h3 class="font-black text-gray-900 text-xl fredoka mb-6 flex items-center gap-3">
                        <span class="w-10 h-10 bg-orange-50 text-orange-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-undo"></i></span>
                        Returns & Refunds
                    </h3>
                    <textarea name="legal_returns_refunds" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-[32px] px-8 py-8 outline-none font-medium h-64 leading-relaxed"><?php echo htmlspecialchars($p['legal_returns_refunds']); ?></textarea>
                </div>

                <!-- Disclaimer -->
                <div class="bg-white rounded-[48px] p-10 shadow-sm border border-gray-100">
                    <h3 class="font-black text-gray-900 text-xl fredoka mb-6 flex items-center gap-3">
                        <span class="w-10 h-10 bg-red-50 text-red-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-exclamation-triangle"></i></span>
                        Disclaimer
                    </h3>
                    <textarea name="legal_disclaimer" class="w-full bg-gray-50 border-2 border-transparent focus:border-black rounded-[32px] px-8 py-8 outline-none font-medium h-48 leading-relaxed"><?php echo htmlspecialchars($p['legal_disclaimer']); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-center pt-10">
            <button type="submit" class="group bg-black text-white px-20 py-6 rounded-[35px] font-black uppercase tracking-[0.2em] shadow-2xl hover:scale-105 active:scale-95 transition-all flex items-center gap-4">
                <i class="fas fa-save group-hover:rotate-12 transition-transform"></i> Propagate Page Updates
            </button>
        </div>
    </form>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('bg-black', 'text-white');
        b.classList.add('bg-white', 'text-gray-400', 'border-gray-100');
    });
    
    document.getElementById('btn-' + tab).classList.remove('bg-white', 'text-gray-400', 'border-gray-100');
    document.getElementById('btn-' + tab).classList.add('bg-black', 'text-white');
}
</script>

<?php include 'includes/footer.php'; ?>
