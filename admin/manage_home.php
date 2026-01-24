<?php
include 'includes/header.php'; 

// Database Connection
$conn = get_db_connection();
$msg = "";
$error = "";

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Detect if post_max_size was exceeded
    if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = "The file you're trying to upload is too large.";
    }

    // --- HERO SECTION UPDATE ---
    if (isset($_POST['update_hero'])) {
        $heading = $_POST['hero_heading'] ?? '';
        $subheading = $_POST['hero_subheading'] ?? '';
        $cta_text = $_POST['hero_cta_text'] ?? '';
        $cta_link = $_POST['hero_cta_link'] ?? '';
        $media_url = $_POST['current_hero_media'] ?? '';

        // Fetch old data for cleanup
        $old_data = fetch_one("SELECT media_url FROM homepage_sections WHERE section_name = 'hero'");

        // Handle Image Upload
        if (isset($_FILES['hero_media']) && $_FILES['hero_media']['error'] == 0) {
            $target_dir = "../assets/images/uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            
            $filename = "hero_" . uniqid() . "_" . basename($_FILES["hero_media"]["name"]);
            if (move_uploaded_file($_FILES["hero_media"]["tmp_name"], $target_dir . $filename)) {
                $media_url = "assets/images/uploads/" . $filename;
                
                // Delete old file if it was an upload
                if ($old_data && !empty($old_data['media_url']) && strpos($old_data['media_url'], 'assets/images/uploads/') === 0) {
                    $old_path = "../" . $old_data['media_url'];
                    if (file_exists($old_path)) @unlink($old_path);
                }
            } else {
                $error = "Failed to save hero image.";
            }
        }

        if (!$error) {
            $stmt = $conn->prepare("UPDATE homepage_sections SET heading = ?, subheading = ?, cta_text = ?, cta_link = ?, media_url = ? WHERE section_name = 'hero'");
            $stmt->bind_param("sssss", $heading, $subheading, $cta_text, $cta_link, $media_url);
            if ($stmt->execute()) {
                $msg = "Hero section updated!";
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }

    // --- SALE COUNTDOWN UPDATE ---
    if (isset($_POST['update_sale'])) {
        $title = $_POST['sale_title'];
        $end_date = $_POST['sale_end'];
        $is_active = isset($_POST['sale_active']) ? 1 : 0;

        if(empty($end_date)) $end_date = date('Y-m-d H:i:s', strtotime('+7 days'));

        $check = $conn->query("SELECT id FROM sale_countdowns LIMIT 1");
        if($check->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE sale_countdowns SET title=?, end_date=?, is_active=? LIMIT 1");
        } else {
            $stmt = $conn->prepare("INSERT INTO sale_countdowns (title, end_date, is_active) VALUES (?, ?, ?)");
        }
        $stmt->bind_param("ssi", $title, $end_date, $is_active);
        if($stmt->execute()) $msg = "Sale countdown updated!";
        else $error = "Sale update failed.";
    }

    // --- ANNOUNCEMENT BAR UPDATE ---
    if (isset($_POST['update_announcement'])) {
        $announcement_text = sanitize_input($_POST['announcement_text'] ?? '');
        update_setting('announcement_text', $announcement_text);
        $msg = "Announcement bar updated!";
    }

    // --- VIDEO SECTION UPDATE ---
    if (isset($_POST['update_video_section'])) {
        $heading = $_POST['heading'] ?? '';
        $subheading = $_POST['subheading'] ?? '';
        $video_url = $_POST['video_url'] ?? '';
        $media_url = $_POST['current_media'] ?? '';

        // Fetch old data for cleanup
        $old_data = fetch_one("SELECT media_url, video_url FROM homepage_sections WHERE section_name = 'video_brand_story'");

        // Image (Thumbnail)
        if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
            $target_dir = "../assets/images/uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $filename = "vid_thumb_" . uniqid() . "_" . basename($_FILES["media"]["name"]);
            if (move_uploaded_file($_FILES["media"]["tmp_name"], $target_dir . $filename)) {
                $media_url = "assets/images/uploads/" . $filename;
                
                // Cleanup old thumbnail
                if ($old_data && !empty($old_data['media_url']) && strpos($old_data['media_url'], 'assets/images/uploads/') === 0) {
                    $old_path = "../" . $old_data['media_url'];
                    if (file_exists($old_path)) @unlink($old_path);
                }
            }
        }

        // Video File
        $video_uploaded = false;
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
            $target_dir = "../assets/videos/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            $filename = "brand_v_" . uniqid() . "." . $ext;
            if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_dir . $filename)) {
                $video_url = "assets/videos/" . $filename;
                $video_uploaded = true;
            }
        }

        // Deletion logic for video: 
        // 1. If a new file was uploaded, delete the old file.
        // 2. If the user manualy changed the video text input (e.g. to a YouTube link), and it's different from the old local path, delete the old local path.
        if ($old_data && !empty($old_data['video_url']) && strpos($old_data['video_url'], 'assets/videos/') === 0) {
            if ($video_uploaded || (isset($_POST['video_url']) && $_POST['video_url'] !== $old_data['video_url'])) {
                $old_v_path = "../" . $old_data['video_url'];
                if (file_exists($old_v_path)) @unlink($old_v_path);
            }
        }

        if (!$error) {
            $stmt = $conn->prepare("UPDATE homepage_sections SET heading = ?, subheading = ?, media_url = ?, video_url = ? WHERE section_name = 'video_brand_story'");
            $stmt->bind_param("ssss", $heading, $subheading, $media_url, $video_url);
            if ($stmt->execute()) $msg = "Video section updated!";
        }
    }
}

// Handle Delete Announcement
if(isset($_GET['del_anno'])) {
    $id = intval($_GET['del_anno']);
    $conn->query("DELETE FROM announcements WHERE id = $id");
    $msg = "Announcement deleted!";
}

// Fetch Current Data
$hero = fetch_one("SELECT * FROM homepage_sections WHERE section_name = 'hero'");
$vid_sec = fetch_one("SELECT * FROM homepage_sections WHERE section_name = 'video_brand_story'");
$sale = fetch_one("SELECT * FROM sale_countdowns LIMIT 1");
$announcement_text = get_setting('announcement_text', '🚀 Free Shipping on All Orders Over ₹499 • 🌿 100% Organic & Natural');
?>

<div class="p-1 md:p-6 bg-[#f8fafc] min-h-screen">
    <!-- Page Header -->
    <div class="mb-10 max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="text-center md:text-left">
            <h1 class="text-3xl md:text-4xl font-['Fredoka'] font-black text-gray-900 tracking-tight">Homepage Architect</h1>
            <p class="text-gray-500 font-medium text-sm md:text-base">Design and control your storefront experience.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="../index.php" target="_blank" class="bg-white text-gray-700 px-6 py-3 rounded-2xl font-bold border-2 border-gray-100 hover:border-black transition flex items-center justify-center gap-2">
                <i class="fas fa-external-link-alt text-sm"></i> View Live Site
            </a>
        </div>
    </div>

    <!-- Stats Summary (Optional Visually) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-7xl mx-auto mb-10">
        <div class="bg-gradient-to-br from-[#19DC7E] to-[#10b981] p-6 rounded-[32px] text-white shadow-xl shadow-green-100 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 text-7xl opacity-20 transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
                <i class="fas fa-rocket"></i>
            </div>
            <p class="text-white/80 font-bold uppercase text-[10px] tracking-widest mb-1">Current Active Sale</p>
            <h3 class="text-2xl font-black font-['Fredoka']"><?php echo ($sale['is_active'] ?? 0) ? htmlspecialchars($sale['title']) : 'No Active Sale'; ?></h3>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-100 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center text-xl">
                <i class="fas fa-bullhorn"></i>
            </div>
            <div>
                <p class="text-gray-400 font-bold text-[10px] tracking-widest uppercase">Bar Status</p>
                <h3 class="text-2xl font-black font-['Fredoka'] text-gray-900"><?php echo !empty($announcement_text) ? 'Active' : 'Empty'; ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-100 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center text-xl">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <p class="text-gray-400 font-bold text-[10px] tracking-widest uppercase">Last Update</p>
                <h3 class="text-xl font-bold text-gray-900"><?php echo date('h:i A, d M'); ?></h3>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if($msg): ?>
        <div class="max-w-7xl mx-auto mb-8 animate-[slideIn_0.3s_ease-out]">
            <div class="bg-green-500 text-white px-8 py-5 rounded-3xl shadow-xl shadow-green-100 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-check"></i>
                    </div>
                    <p class="font-bold"><?php echo $msg; ?></p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-white/60 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="max-w-7xl mx-auto mb-8">
            <div class="bg-red-500 text-white px-8 py-5 rounded-3xl shadow-xl shadow-red-100 flex items-center gap-4">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <p class="font-bold"><?php echo $error; ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto space-y-10 pb-20">

        <!-- HERO SECTION MANAGER -->
        <div class="bg-white rounded-[30px] md:rounded-[40px] shadow-xl shadow-gray-100 overflow-hidden border border-gray-50">
            <div class="bg-gray-900 px-6 md:px-10 py-6 md:py-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4 md:gap-5">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-[#19DC7E] rounded-xl md:rounded-2xl flex items-center justify-center text-black">
                        <i class="fas fa-star text-base md:text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg md:text-xl font-black text-white font-['Fredoka']">Hero Spotlight</h3>
                        <p class="text-gray-400 text-xs md:text-sm">Main headline and primary banner.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 bg-white/5 rounded-full px-4 py-2 border border-white/10 self-start md:self-auto">
                    <span class="w-2 h-2 rounded-full bg-[#19DC7E] animate-pulse"></span>
                    <span class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">Live Dynamic</span>
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="p-6 md:p-10">
                <input type="hidden" name="update_hero" value="1">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <!-- Preview & Content -->
                    <div class="space-y-8">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-4 tracking-widest">Headline Content</label>
                            <textarea name="hero_heading" id="hero-head-input" rows="3" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-3xl px-6 py-5 outline-none transition-all font-bold text-3xl text-gray-900 shadow-inner" placeholder="YOUR NEW HEALTHY HABIT."><?php echo htmlspecialchars($hero['heading']); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-4 tracking-widest">Tagline Message</label>
                            <input type="text" name="hero_subheading" value="<?php echo htmlspecialchars($hero['subheading']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-2xl px-6 py-4 outline-none transition-all font-bold text-gray-600 shadow-inner">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase text-gray-400 mb-4 tracking-widest">Button Text</label>
                                <input type="text" name="hero_cta_text" value="<?php echo htmlspecialchars($hero['cta_text']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] rounded-2xl px-6 py-4 outline-none font-bold">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase text-gray-400 mb-4 tracking-widest">Button Link</label>
                                <input type="text" name="hero_cta_link" value="<?php echo htmlspecialchars($hero['cta_link']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] rounded-2xl px-6 py-4 outline-none font-bold">
                            </div>
                        </div>
                    </div>

                    <!-- Media Upload -->
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-4 tracking-widest text-center md:text-left">Live Visual Mockup (Desktop)</label>
                        <div class="relative group aspect-[16/9] rounded-3xl overflow-hidden border-4 border-white shadow-2xl bg-black">
                            <img id="hero-media-preview" src="../<?php echo !empty($hero['media_url']) ? $hero['media_url'] : 'assets/images/hero.jpg'; ?>" class="w-full h-full object-cover opacity-60 group-hover:scale-105 transition-transform duration-700">
                            
                            <!-- LIVE TEXT OVERLAY -->
                            <div class="absolute inset-0 flex flex-col justify-end p-4 md:p-6 pointer-events-none">
                                <h1 id="hero-head-preview" class="text-white font-black font-['Fredoka'] text-xl md:text-3xl leading-[0.9] mb-2 drop-shadow-2xl"><?php echo nl2br(htmlspecialchars($hero['heading'])); ?></h1>
                                <p id="hero-sub-preview" class="text-[#19DC7E] font-bold text-[8px] md:text-xs uppercase tracking-widest drop-shadow-lg"><?php echo htmlspecialchars($hero['subheading']); ?></p>
                            </div>

                            <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity">
                                <label class="cursor-pointer bg-white text-black px-6 py-3 rounded-2xl font-bold flex items-center gap-2 transform translate-y-4 group-hover:translate-y-0 transition-transform">
                                    <i class="fas fa-camera"></i> Change Image
                                    <input type="file" name="hero_media" id="hero-media-input" class="hidden" onchange="previewMedia(this, 'hero-media-preview')">
                                </label>
                            </div>
                            <input type="hidden" name="current_hero_media" value="<?php echo htmlspecialchars($hero['media_url']); ?>">
                        </div>
                        <p class="text-center text-[10px] text-gray-400 font-bold uppercase mt-4 tracking-widest italic">Live Preview of your Headline & Background</p>
                    </div>
                </div>
                
                <div class="mt-10 pt-10 border-t border-gray-100 flex justify-center md:justify-end">
                    <button type="submit" name="update_hero" class="w-full md:w-auto bg-black text-[#19DC7E] px-12 py-5 rounded-2xl font-black uppercase tracking-widest hover:bg-[#19DC7E] hover:text-black transition-all shadow-2xl flex items-center justify-center gap-3">
                        <i class="fas fa-rocket"></i> Publish Updates
                    </button>
                </div>
            </form>
        </div>

        <!-- TWO COLUMN GRID FOR SMALLER SECTIONS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            
            <!-- SALE COUNTDOWN -->
            <div class="bg-white rounded-[40px] shadow-xl shadow-gray-100 overflow-hidden border border-gray-50 flex flex-col">
                <div class="bg-amber-400 px-8 py-6">
                    <h3 class="text-xl font-black text-black font-['Fredoka'] flex items-center gap-3">
                        <i class="fas fa-bolt"></i> Flash Sale Timer
                    </h3>
                </div>
                <form method="POST" class="p-6 flex-1 flex flex-col justify-between">
                    <input type="hidden" name="update_sale" value="1">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">Sale Tagline</label>
                            <input type="text" name="sale_title" value="<?php echo htmlspecialchars($sale['title'] ?? ''); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-amber-400 rounded-2xl px-6 py-4 outline-none font-bold text-lg" placeholder="e.g. MEGA HOLIDAY SALE!">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">End Date & Time</label>
                            <input type="datetime-local" name="sale_end" value="<?php echo isset($sale['end_date']) ? date('Y-m-d\TH:i', strtotime($sale['end_date'])) : ''; ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-amber-400 rounded-2xl px-6 py-4 outline-none font-bold">
                        </div>
                        <div class="bg-amber-50 p-4 rounded-2xl border border-amber-100">
                            <label class="flex items-center gap-4 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" name="sale_active" value="1" class="sr-only peer" <?php echo ($sale['is_active'] ?? 0) ? 'checked' : ''; ?>>
                                    <div class="w-12 h-6 bg-gray-200 rounded-full peer peer-checked:bg-amber-500 transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-6"></div>
                                </div>
                                <span class="text-sm font-black text-amber-900 group-hover:text-black">ENABLE TIMER ON FRONTEND</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="mt-8 bg-black text-white w-full py-5 rounded-2xl font-bold hover:bg-amber-400 hover:text-black transition-all shadow-lg">Update Sale Settings</button>
                </form>
            </div>

            <!-- ANNOUNCEMENT BAR SETTING -->
            <div class="bg-white rounded-[40px] shadow-xl shadow-gray-100 overflow-hidden border border-gray-50 flex flex-col">
                <div class="bg-blue-600 px-8 py-6">
                    <h3 class="text-xl font-black text-white font-['Fredoka'] flex items-center gap-3">
                        <i class="fas fa-bullhorn"></i> Announcement Bar
                    </h3>
                </div>
                <form method="POST" class="p-8 flex-1 flex flex-col">
                    <input type="hidden" name="update_announcement" value="1">
                    <div class="space-y-6 flex-1">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-4 tracking-widest">Marquee Content</label>
                            <textarea name="announcement_text" rows="4" class="w-full bg-gray-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-3xl px-6 py-5 outline-none transition-all font-bold text-lg shadow-inner resize-none" placeholder="Enter marquee text..."><?php echo htmlspecialchars($announcement_text); ?></textarea>
                        </div>
                        <div class="flex items-center gap-3 bg-blue-50 p-4 rounded-2xl border border-blue-100">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <p class="text-[10px] text-blue-700 font-bold uppercase tracking-widest">Use <span class="text-black font-black">" • "</span> to chain multiple messages together.</p>
                        </div>
                    </div>
                    <button type="submit" class="mt-8 bg-black text-white w-full py-5 rounded-2xl font-bold hover:bg-blue-600 transition-all shadow-lg flex items-center justify-center gap-3">
                        <i class="fas fa-save"></i> Save Bar Content
                    </button>
                </form>
            </div>

        </div>

        <!-- VIDEO BRAND STORY SECTION -->
        <div class="bg-white rounded-[30px] md:rounded-[40px] shadow-xl shadow-gray-100 overflow-hidden border border-gray-50">
            <div class="bg-gray-900 px-6 md:px-10 py-6 md:py-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4 md:gap-5">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-[#19DC7E] rounded-xl md:rounded-2xl flex items-center justify-center text-black">
                        <i class="fas fa-play text-base md:text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg md:text-xl font-black text-white font-['Fredoka']">Cinematic Brand Story</h3>
                        <p class="text-gray-400 text-xs md:text-sm">Large video background section.</p>
                    </div>
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="p-6 md:p-10">
                <input type="hidden" name="update_video_section" value="1">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">Section Heading</label>
                            <textarea name="heading" rows="2" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] rounded-2xl px-6 py-4 outline-none font-bold text-xl"><?php echo htmlspecialchars($vid_sec['heading']); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">Subheading / Description</label>
                            <textarea name="subheading" rows="3" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] rounded-2xl px-6 py-4 outline-none font-medium text-gray-500"><?php echo htmlspecialchars($vid_sec['subheading']); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">YouTube Video URL (Alternative)</label>
                            <input type="text" name="video_url" value="<?php echo htmlspecialchars($vid_sec['video_url']); ?>" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] rounded-2xl px-6 py-4 outline-none font-bold text-sm" placeholder="https://youtube.com/watch?v=...">
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">Live Video Preview (Upload Only)</label>
                            <div class="relative group h-16 w-full">
                                <input type="file" name="video_file" id="vid-file-input" accept="video/*" class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="previewMedia(this, 'vid-file-preview')">
                                <div class="absolute inset-0 border-2 border-dashed border-gray-200 rounded-2xl flex items-center justify-center gap-3 group-hover:bg-[#19DC7E]/5 transition-colors group-hover:border-[#19DC7E]">
                                    <i class="fas fa-film text-gray-300"></i>
                                    <span class="text-sm font-bold text-gray-400">Click to upload brand video</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest">Thumbnail / Cover Image</label>
                            <div class="relative group aspect-video rounded-3xl overflow-hidden border-2 border-gray-100 bg-gray-50">
                                <img id="vid-cover-preview" src="../<?php echo !empty($vid_sec['media_url']) ? $vid_sec['media_url'] : 'assets/images/hero.jpg'; ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                <video id="vid-file-preview" class="absolute inset-0 w-full h-full object-cover hidden z-10" autoplay muted loop></video>
                                
                                <div class="absolute inset-0 flex flex-col justify-end p-6 z-20 pointer-events-none bg-gradient-to-t from-black/60 to-transparent">
                                    <h2 id="vid-head-preview" class="text-white font-black font-['Fredoka'] text-lg drop-shadow-lg leading-tight"><?php echo nl2br(htmlspecialchars($vid_sec['heading'])); ?></h2>
                                </div>

                                <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity z-30">
                                    <label class="cursor-pointer bg-white text-black px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2">
                                        <i class="fas fa-image"></i> Change Cover
                                        <input type="file" name="media" id="vid-cover-input" class="hidden" onchange="previewMedia(this, 'vid-cover-preview')">
                                    </label>
                                </div>
                                <input type="hidden" name="current_media" value="<?php echo htmlspecialchars($vid_sec['media_url']); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-10 pt-10 border-t border-gray-100 flex justify-end">
                    <button type="submit" name="update_video_section" class="bg-gray-900 text-white px-10 py-4 rounded-xl font-bold hover:bg-black transition shadow-xl">Update Brand Story Section</button>
                </div>
            </form>
        </div>

    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    
    @keyframes slideIn {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>

<script>
// --- LIVE TEXT PREVIEW ---
document.addEventListener('DOMContentLoaded', () => {
    // Hero Text
    const heroHeadIn = document.getElementById('hero-head-input');
    const heroSubIn = document.querySelector('input[name="hero_subheading"]');
    const heroHeadPre = document.getElementById('hero-head-preview');
    const heroSubPre = document.getElementById('hero-sub-preview');

    if(heroHeadIn) heroHeadIn.oninput = (e) => heroHeadPre.innerHTML = e.target.value.replace(/\n/g, '<br>');
    if(heroSubIn) heroSubIn.oninput = (e) => heroSubPre.innerText = e.target.value;

    // Video Section Text
    const vidHeadIn = document.querySelector('textarea[name="heading"]');
    const vidHeadPre = document.getElementById('vid-head-preview');

    if(vidHeadIn) vidHeadIn.oninput = (e) => vidHeadPre.innerHTML = e.target.value.replace(/\n/g, '<br>');
});

// --- ENHANCED MEDIA PREVIEW (Image & Video) ---
function previewMedia(input, previewId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const preview = document.getElementById(previewId);
        const reader = new FileReader();

        // Handle Video Selection
        if (file.type.startsWith('video/')) {
            const videoPreview = document.getElementById('vid-file-preview');
            if (videoPreview) {
                videoPreview.src = URL.createObjectURL(file);
                videoPreview.classList.remove('hidden');
                // Optional: Hide the thumbnail image when video is selected
                if(preview && preview.tagName === 'IMG') preview.style.opacity = '0.3';
            }
            return;
        }

        // Handle Image Selection
        reader.onload = function(e) {
            preview.setAttribute('src', e.target.result);
            preview.style.opacity = '0.6'; // Keep overlay readable
            
            // If it was a video preview being replaced, hide video
            const videoPreview = document.getElementById('vid-file-preview');
            if (videoPreview) videoPreview.classList.add('hidden');
        }
        reader.readAsDataURL(file);
    }
}
</script>

</body>
</html>
