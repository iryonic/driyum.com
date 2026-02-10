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

    // --- HERO GLOBAL SETTINGS UPDATE ---
    if (isset($_POST['update_hero'])) {
        $show_stats = isset($_POST['show_hero_stats']) ? 'on' : 'off';
        update_setting('show_hero_stats', $show_stats);
        $msg = "Global hero settings updated!";
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
    
    // --- HERO SLIDES MANAGEMENT ---
    if (isset($_POST['add_slide']) || isset($_POST['edit_slide'])) {
        $title = $_POST['slide_title'] ?? '';
        $subtitle = $_POST['slide_subtitle'] ?? '';
        $badge_text = $_POST['badge_text'] ?? '';
        $cta_text = $_POST['slide_cta_text'] ?? '';
        $cta_link = $_POST['slide_cta_link'] ?? '';
        $sort_order = intval($_POST['slide_sort_order'] ?? 0);
        
        $show_title = isset($_POST['show_title']) ? 1 : 0;
        $show_subtitle = isset($_POST['show_subtitle']) ? 1 : 0;
        $show_badge = isset($_POST['show_badge']) ? 1 : 0;
        $show_cta = isset($_POST['show_cta']) ? 1 : 0;

        if (isset($_POST['add_slide'])) {
            $image_url = 'assets/images/hero.jpg';
            if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] == 0) {
                $target_dir = "../assets/images/uploads/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                $filename = "slide_" . uniqid() . "_" . basename($_FILES["slide_image"]["name"]);
                if (move_uploaded_file($_FILES["slide_image"]["tmp_name"], $target_dir . $filename)) {
                    $image_url = "assets/images/uploads/" . $filename;
                }
            }
            $stmt = $conn->prepare("INSERT INTO hero_slides (title, show_title, subtitle, show_subtitle, image, cta_text, cta_link, show_cta, sort_order, is_active, badge_text, show_badge) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)");
            $stmt->bind_param("sisssssiisi", $title, $show_title, $subtitle, $show_subtitle, $image_url, $cta_text, $cta_link, $show_cta, $sort_order, $badge_text, $show_badge);
            if ($stmt->execute()) $msg = "New slide added!";
            else $error = "Failed to add slide: " . $conn->error;
        } else {
            $id = intval($_POST['slide_id']);
            $image_url = $_POST['current_image'] ?? '';
            if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] == 0) {
                $target_dir = "../assets/images/uploads/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                $filename = "slide_" . uniqid() . "_" . basename($_FILES["slide_image"]["name"]);
                if (move_uploaded_file($_FILES["slide_image"]["tmp_name"], $target_dir . $filename)) {
                    $image_url = "assets/images/uploads/" . $filename;
                    // Delete old image if it's an upload
                    $old_slide = fetch_one("SELECT image FROM hero_slides WHERE id = $id");
                    if ($old_slide && strpos($old_slide['image'], 'assets/images/uploads/') === 0) {
                        @unlink("../" . $old_slide['image']);
                    }
                }
            }
            $stmt = $conn->prepare("UPDATE hero_slides SET title=?, show_title=?, subtitle=?, show_subtitle=?, image=?, cta_text=?, cta_link=?, show_cta=?, sort_order=?, badge_text=?, show_badge=? WHERE id=?");
            $stmt->bind_param("sisssssiisii", $title, $show_title, $subtitle, $show_subtitle, $image_url, $cta_text, $cta_link, $show_cta, $sort_order, $badge_text, $show_badge, $id);
            if ($stmt->execute()) $msg = "Slide updated!";
            else $error = "Failed to update slide: " . $conn->error;
        }
    }

    if (isset($_POST['delete_slide'])) {
        $id = intval($_POST['slide_id']);
        $old_slide = fetch_one("SELECT image FROM hero_slides WHERE id = $id");
        if ($old_slide && strpos($old_slide['image'], 'assets/images/uploads/') === 0) {
            $old_path = "../" . $old_slide['image'];
            if (file_exists($old_path)) @unlink($old_path);
        }
        $conn->query("DELETE FROM hero_slides WHERE id = $id");
        $msg = "Slide removed!";
    }

    if (isset($_POST['toggle_slide'])) {
        $id = intval($_POST['slide_id']);
        $conn->query("UPDATE hero_slides SET is_active = 1 - is_active WHERE id = $id");
        $msg = "Slide status toggled!";
    }
}

// Handle Delete Announcement
if(isset($_GET['del_anno'])) {
    $id = intval($_GET['del_anno']);
    $conn->query("DELETE FROM announcements WHERE id = $id");
    $msg = "Announcement deleted!";
}

$vid_sec = fetch_one("SELECT * FROM homepage_sections WHERE section_name = 'video_brand_story'");
$sale = fetch_one("SELECT * FROM sale_countdowns LIMIT 1");
$announcement_text = get_setting('announcement_text', '🚀 Free Shipping on All Orders Over ₹499 • 🌿 100% Organic & Natural');
$hero_slides = get_hero_slides(true);
$show_stats = get_setting('show_hero_stats', 'on');
?>

<!-- VIEW START -->
<div class="pb-20 anim-up">
    
    <!-- Top Action Bar -->
    <div class="sticky top-20 z-40 bg-white/80 backdrop-blur-md border-b border-gray-100 py-4 mb-8 -mx-4 px-4 md:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 fredoka tracking-tight">Homepage Architect</h1>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Design your storefront experience</p>
        </div>

        <div class="flex items-center gap-4">
            <a href="../index.php" target="_blank" class="bg-white text-gray-900 border border-gray-200 px-6 py-2.5 rounded-xl font-bold uppercase text-[10px] tracking-widest hover:bg-gray-50 hover:text-black transition-all shadow-sm flex items-center gap-2">
                View Live <i class="fas fa-external-link-alt text-[9px]"></i>
            </a>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-gradient-to-br from-[#19DC7E] to-[#10b981] p-6 rounded-3xl text-white shadow-xl shadow-green-100 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 text-7xl opacity-20 transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
                <i class="fas fa-rocket"></i>
            </div>
            <p class="text-white/80 font-bold uppercase text-[10px] tracking-widest mb-1">Current Active Sale</p>
            <h3 class="text-2xl font-black font-['Fredoka']"><?php echo ($sale['is_active'] ?? 0) ? htmlspecialchars($sale['title']) : 'No Active Sale'; ?></h3>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-5 group hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="fas fa-bullhorn"></i>
            </div>
            <div>
                <p class="text-gray-400 font-bold text-[10px] tracking-widest uppercase">Bar Status</p>
                <h3 class="text-xl font-black font-['Fredoka'] text-gray-900"><?php echo !empty($announcement_text) ? 'Active' : 'Empty'; ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-5 group hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <p class="text-gray-400 font-bold text-[10px] tracking-widest uppercase">Last Update</p>
                <h3 class="text-xl font-bold text-gray-900"><?php echo date('h:i A'); ?></h3>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if($msg): ?>
        <div class="mb-8 p-4 bg-green-50 text-green-700 rounded-2xl border border-green-100 font-bold text-xs anim-up flex items-center gap-3">
            <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="mb-8 p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100 font-bold text-xs anim-up flex items-center gap-3">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="space-y-10">

        <!-- DYNAMIC HERO SLIDES MANAGER -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up mb-10">
            <div class="px-8 py-6 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/50">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-[#19DC7E]/10 text-[#19DC7E] rounded-full flex items-center justify-center text-sm">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 font-['Fredoka'] leading-tight">Slider Master</h3>
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Manage multiple hero banners</p>
                    </div>
                </div>
                <button onclick="document.getElementById('add-slide-modal').classList.remove('hidden')" class="bg-black text-white px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-[#19DC7E] hover:text-black transition-all shadow-lg flex items-center gap-2">
                    <i class="fas fa-plus"></i> Add New Slide
                </button>
            </div>

            <div class="p-8">
                <?php if(empty($hero_slides)): ?>
                    <div class="bg-amber-50 rounded-2xl p-10 text-center border border-amber-100">
                        <i class="fas fa-images text-4xl text-amber-200 mb-4 block"></i>
                        <p class="text-xs font-bold text-amber-800 uppercase tracking-widest">No Slides Found</p>
                        <p class="text-[10px] text-amber-600 mt-1">The banner is currently using a static fallback.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach($hero_slides as $slide): ?>
                            <div class="group relative rounded-3xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-500 bg-white">
                                <!-- Preview Image -->
                                <div class="aspect-[4/3] relative">
                                    <img src="../<?php echo $slide['image']; ?>" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                                    
                                    <!-- Status Badge -->
                                    <div class="absolute top-4 left-4">
                                        <?php if($slide['is_active']): ?>
                                            <span class="bg-[#19DC7E] text-black text-[8px] font-black px-3 py-1 rounded-full uppercase tracking-tighter shadow-xl flex items-center gap-1.5">
                                                <span class="w-1 h-1 bg-black rounded-full animate-pulse"></span> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-gray-500 text-white text-[8px] font-black px-3 py-1 rounded-full uppercase tracking-tighter shadow-xl">Hidden</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Actions Overlay -->
                                    <div class="absolute inset-0 flex items-center justify-center gap-3 opacity-0 group-hover:opacity-100 transition-opacity bg-black/40 backdrop-blur-sm">
                                        <button onclick='openEditSlide(<?php echo json_encode($slide); ?>)' class="w-10 h-10 bg-white text-gray-900 rounded-xl flex items-center justify-center hover:bg-[#19DC7E] hover:text-white transition-all shadow-xl" title="Edit Content">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="contents">
                                            <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                                            <button type="submit" name="toggle_slide" class="w-10 h-10 bg-white text-gray-900 rounded-xl flex items-center justify-center hover:bg-amber-400 transition-all shadow-xl" title="Toggle Visibility">
                                                <i class="fas fa-eye<?php echo $slide['is_active'] ? '-slash' : ''; ?>"></i>
                                            </button>
                                            <button type="submit" name="delete_slide" onclick="return confirm('Kill this slide?')" class="w-10 h-10 bg-red-500 text-white rounded-xl flex items-center justify-center hover:bg-red-600 transition-all shadow-xl" title="Delete Permanent">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Content Preview -->
                                    <div class="absolute bottom-4 left-4 right-4 pointer-events-none">
                                        <h4 class="text-white font-black fredoka text-sm leading-tight line-clamp-2 <?php echo !$slide['show_title'] ? 'opacity-30 line-through' : ''; ?>"><?php echo htmlspecialchars($slide['title']); ?></h4>
                                        <p class="text-white/60 text-[9px] font-bold uppercase tracking-wider mt-1 truncate <?php echo !$slide['show_subtitle'] ? 'opacity-30 line-through' : ''; ?>"><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                                        <?php if(!$slide['show_cta']): ?>
                                            <span class="inline-block mt-2 px-2 py-0.5 bg-red-500/50 text-[7px] text-white font-bold rounded uppercase">CTA HIDDEN</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="p-4 flex items-center justify-between text-[9px] font-black text-gray-400 uppercase tracking-widest bg-gray-50/50">
                                    <span>Sort: <?php echo $slide['sort_order']; ?></span>
                                    <span class="text-gray-300">ID: #<?php echo $slide['id']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Global Options Below Slider -->
            <div class="px-8 py-6 border-t border-gray-50 flex items-center gap-4 bg-gray-50/20">
                <form method="POST" class="flex items-center gap-6">
                    <input type="hidden" name="update_hero" value="1">
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="show_hero_stats" value="on" class="sr-only peer" <?php echo ($show_stats === 'on') ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#19DC7E]"></div>
                        </label>
                        <span class="text-[10px] font-black text-gray-600 uppercase tracking-widest">Global Review Stats Badge</span>
                    </div>
                </form>
            </div>
        </div>

        <!-- ADD SLIDE MODAL -->
        <div id="add-slide-modal" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md hidden">
            <div class="bg-white w-full max-w-2xl rounded-[40px] shadow-2xl overflow-hidden anim-up">
                <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                    <h3 class="text-xl font-black text-gray-900 fredoka">New Slide Canvas</h3>
                    <button onclick="document.getElementById('add-slide-modal').classList.add('hidden')" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data" class="p-8">
                    <input type="hidden" name="add_slide" value="1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Floating Badge Text</label>
                                <input type="text" name="badge_text" class="w-full bg-gray-50 border border-transparent focus:border-black focus:bg-white rounded-xl px-5 py-3 outline-none font-bold" placeholder="The Purest Taste of Kashmir">
                                <label class="flex items-center gap-2 mt-1 ml-2 cursor-pointer">
                                    <input type="checkbox" name="show_badge" value="1" checked class="rounded border-gray-300 text-black focus:ring-black">
                                    <span class="text-[8px] font-black text-gray-400 uppercase">Visible</span>
                                </label>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Main Headline</label>
                                <textarea name="slide_title" rows="2" class="w-full bg-gray-50 border border-transparent focus:border-black focus:bg-white rounded-2xl px-5 py-3 outline-none font-bold text-gray-900 transition-all resize-none" placeholder="PURE KASHMIRI CRUNCH" required></textarea>
                                <label class="flex items-center gap-2 mt-1 ml-2 cursor-pointer">
                                    <input type="checkbox" name="show_title" value="1" checked class="rounded border-gray-300 text-black focus:ring-black">
                                    <span class="text-[8px] font-black text-gray-400 uppercase">Visible</span>
                                </label>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Tagline Subtitle</label>
                                <input type="text" name="slide_subtitle" class="w-full bg-gray-50 border border-transparent focus:border-black focus:bg-white rounded-xl px-5 py-3 outline-none font-bold" placeholder="Taste the mountains.">
                                <label class="flex items-center gap-2 mt-1 ml-2 cursor-pointer">
                                    <input type="checkbox" name="show_subtitle" value="1" checked class="rounded border-gray-300 text-black focus:ring-black">
                                    <span class="text-[8px] font-black text-gray-400 uppercase">Visible</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">CTA Label</label>
                                    <input type="text" name="slide_cta_text" class="w-full bg-gray-50 border border-transparent focus:border-black rounded-xl px-4 py-3 outline-none font-black text-[10px]" placeholder="SHOP NOW">
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Target Link</label>
                                    <input type="text" name="slide_cta_link" class="w-full bg-gray-50 border border-transparent focus:border-black rounded-xl px-4 py-3 outline-none font-black text-[10px]" placeholder="shop.php">
                                </div>
                                <div class="col-span-2">
                                    <label class="flex items-center gap-2 ml-2 cursor-pointer">
                                        <input type="checkbox" name="show_cta" value="1" checked class="rounded border-gray-300 text-black focus:ring-black">
                                        <span class="text-[8px] font-black text-gray-400 uppercase">Show Button Group</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-6">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Banner Image</label>
                                <div class="relative group aspect-video rounded-3xl overflow-hidden border-2 border-dashed border-gray-200 bg-gray-50 hover:border-black transition-all">
                                    <img id="new-slide-preview" class="w-full h-full object-cover hidden">
                                    <div id="upload-placeholder" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                                        <i class="fas fa-cloud-upload-alt text-3xl mb-2"></i>
                                        <span class="text-[9px] font-black uppercase tracking-widest">Select Image</span>
                                    </div>
                                    <input type="file" name="slide_image" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewNewSlide(this)" required>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Display Rank (Order)</label>
                                <input type="number" name="slide_sort_order" value="0" class="w-full bg-gray-50 border border-transparent focus:border-black rounded-xl px-5 py-3 outline-none font-bold">
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 flex gap-4">
                        <button type="submit" class="flex-1 bg-black text-white py-4 rounded-2xl font-black uppercase text-xs tracking-[0.2em] hover:bg-[#19DC7E] hover:text-black transition-all shadow-xl">Deploy Slide</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- EDIT SLIDE MODAL -->
        <div id="edit-slide-modal" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md hidden">
            <div class="bg-white w-full max-w-2xl rounded-[40px] shadow-2xl overflow-hidden anim-up">
                <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                    <h3 class="text-xl font-black text-gray-900 fredoka">Refine Slide Canvas</h3>
                    <button onclick="document.getElementById('edit-slide-modal').classList.add('hidden')" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data" class="p-8">
                    <input type="hidden" name="edit_slide" value="1">
                    <input type="hidden" name="slide_id" id="edit-slide-id">
                    <input type="hidden" name="current_image" id="edit-current-image">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Floating Badge Text</label>
                                <input type="text" name="badge_text" id="edit-badge-text" class="w-full bg-gray-50 border border-transparent focus:border-black focus:bg-white rounded-xl px-5 py-3 outline-none font-bold">
                                <label class="flex items-center gap-2 mt-1 ml-2 cursor-pointer">
                                    <input type="checkbox" name="show_badge" id="edit-show-badge" value="1" class="rounded border-gray-300 text-black focus:ring-black">
                                    <span class="text-[8px] font-black text-gray-400 uppercase">Visible</span>
                                </label>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Main Headline</label>
                                <textarea name="slide_title" id="edit-slide-title" rows="2" class="w-full bg-gray-50 border border-transparent focus:border-black focus:bg-white rounded-2xl px-5 py-3 outline-none font-bold text-gray-900 transition-all resize-none" required></textarea>
                                <label class="flex items-center gap-2 mt-1 ml-2 cursor-pointer">
                                    <input type="checkbox" name="show_title" id="edit-show-title" value="1" class="rounded border-gray-300 text-black focus:ring-black">
                                    <span class="text-[8px] font-black text-gray-400 uppercase">Visible</span>
                                </label>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Tagline Subtitle</label>
                                <input type="text" name="slide_subtitle" id="edit-slide-subtitle" class="w-full bg-gray-50 border border-transparent focus:border-black focus:bg-white rounded-xl px-5 py-3 outline-none font-bold">
                                <label class="flex items-center gap-2 mt-1 ml-2 cursor-pointer">
                                    <input type="checkbox" name="show_subtitle" id="edit-show-subtitle" value="1" class="rounded border-gray-300 text-black focus:ring-black">
                                    <span class="text-[8px] font-black text-gray-400 uppercase">Visible</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">CTA Label</label>
                                    <input type="text" name="slide_cta_text" id="edit-slide-cta-text" class="w-full bg-gray-50 border border-transparent focus:border-black rounded-xl px-4 py-3 outline-none font-black text-[10px]">
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Target Link</label>
                                    <input type="text" name="slide_cta_link" id="edit-slide-cta-link" class="w-full bg-gray-50 border border-transparent focus:border-black rounded-xl px-4 py-3 outline-none font-black text-[10px]">
                                </div>
                                <div class="col-span-2">
                                    <label class="flex items-center gap-2 ml-2 cursor-pointer">
                                        <input type="checkbox" name="show_cta" id="edit-show-cta" value="1" class="rounded border-gray-300 text-black focus:ring-black">
                                        <span class="text-[8px] font-black text-gray-400 uppercase">Show Button Group</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-6">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Banner Image</label>
                                <div class="relative group aspect-video rounded-3xl overflow-hidden border-2 border-dashed border-gray-200 bg-gray-50 hover:border-black transition-all">
                                    <img id="edit-slide-preview" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 flex flex-col items-center justify-center text-white bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i class="fas fa-camera text-2xl mb-2"></i>
                                        <span class="text-[9px] font-black uppercase tracking-widest">Update Image</span>
                                    </div>
                                    <input type="file" name="slide_image" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewEditSlide(this)">
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Display Rank (Order)</label>
                                <input type="number" name="slide_sort_order" id="edit-slide-sort" class="w-full bg-gray-50 border border-transparent focus:border-black rounded-xl px-5 py-3 outline-none font-bold">
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 flex gap-4">
                        <button type="submit" class="flex-1 bg-[#19DC7E] text-black py-4 rounded-2xl font-black uppercase text-xs tracking-[0.2em] hover:bg-black hover:text-white transition-all shadow-xl">Update Slide</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function previewNewSlide(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('new-slide-preview');
                    img.src = e.target.result;
                    img.classList.remove('hidden');
                    document.getElementById('upload-placeholder').classList.add('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewEditSlide(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('edit-slide-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function openEditSlide(slide) {
            document.getElementById('edit-slide-id').value = slide.id;
            document.getElementById('edit-current-image').value = slide.image;
            document.getElementById('edit-badge-text').value = slide.badge_text;
            document.getElementById('edit-slide-title').value = slide.title;
            document.getElementById('edit-slide-subtitle').value = slide.subtitle;
            document.getElementById('edit-slide-cta-text').value = slide.cta_text;
            document.getElementById('edit-slide-cta-link').value = slide.cta_link;
            document.getElementById('edit-slide-sort').value = slide.sort_order;
            document.getElementById('edit-slide-preview').src = '../' + slide.image;
            
            document.getElementById('edit-show-badge').checked = slide.show_badge == 1;
            document.getElementById('edit-show-title').checked = slide.show_title == 1;
            document.getElementById('edit-show-subtitle').checked = slide.show_subtitle == 1;
            document.getElementById('edit-show-cta').checked = slide.show_cta == 1;
            
            document.getElementById('edit-slide-modal').classList.remove('hidden');
        }
        </script>

        <!-- TWO COLUMN GRID FOR SMALLER SECTIONS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- SALE COUNTDOWN -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up flex flex-col group hover:shadow-md transition-shadow">
                <div class="px-8 py-5 border-b border-gray-50 flex items-center gap-4">
                    <div class="w-10 h-10 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center text-sm">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 font-['Fredoka'] leading-tight">Flash Sale</h3>
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Countdown timer</p>
                    </div>
                </div>
                <form method="POST" class="p-6 flex-1 flex flex-col justify-between">
                    <input type="hidden" name="update_sale" value="1">
                    <div class="space-y-5">
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black uppercase text-gray-400 ml-3 tracking-widest">Sale Tagline</label>
                            <input type="text" name="sale_title" value="<?php echo htmlspecialchars($sale['title'] ?? ''); ?>" class="w-full bg-gray-50/50 border border-gray-100 focus:border-amber-400 rounded-xl px-4 py-3 outline-none font-bold placeholder-gray-300" placeholder="e.g. MEGA HOLIDAY SALE!">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black uppercase text-gray-400 ml-3 tracking-widest">End Date & Time</label>
                            <input type="datetime-local" name="sale_end" value="<?php echo isset($sale['end_date']) ? date('Y-m-d\TH:i', strtotime($sale['end_date'])) : ''; ?>" class="w-full bg-gray-50/50 border border-gray-100 focus:border-amber-400 rounded-xl px-4 py-3 outline-none font-bold text-gray-600">
                        </div>
                        <div class="bg-amber-50 p-4 rounded-xl border border-amber-100">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" name="sale_active" value="1" class="sr-only peer" <?php echo ($sale['is_active'] ?? 0) ? 'checked' : ''; ?>>
                                    <div class="w-10 h-5 bg-gray-200 rounded-full peer peer-checked:bg-amber-500 transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                                </div>
                                <span class="text-xs font-black text-amber-900 group-hover:text-black uppercase">Active on Frontend</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="mt-6 bg-black text-white w-full py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-400 hover:text-black transition-all">Update Timer</button>
                </form>
            </div>

            <!-- ANNOUNCEMENT BAR SETTING -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up flex flex-col group hover:shadow-md transition-shadow">
                <div class="px-8 py-5 border-b border-gray-50 flex items-center gap-4">
                    <div class="w-10 h-10 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center text-sm">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 font-['Fredoka'] leading-tight">Marquee</h3>
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Header notification bar</p>
                    </div>
                </div>
                <form method="POST" class="p-6 flex-1 flex flex-col">
                    <input type="hidden" name="update_announcement" value="1">
                    <div class="space-y-4 flex-1">
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black uppercase text-gray-400 ml-3 tracking-widest">Marquee Content</label>
                            <textarea name="announcement_text" rows="4" class="w-full bg-gray-50/50 border border-gray-100 focus:border-blue-500 focus:bg-white rounded-2xl px-5 py-4 outline-none transition-all font-bold shadow-inner resize-none" placeholder="Enter marquee text..."><?php echo htmlspecialchars($announcement_text); ?></textarea>
                        </div>
                        <div class="flex items-center gap-2 bg-blue-50 p-3 rounded-xl border border-blue-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            <p class="text-[9px] text-blue-700 font-bold uppercase tracking-widest">Use <span class="text-black font-black">" • "</span> to chain multiple messages.</p>
                        </div>
                    </div>
                    <button type="submit" class="mt-6 bg-black text-white w-full py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all">Update Marquee</button>
                </form>
            </div>

        </div>

        <!-- VIDEO BRAND STORY SECTION -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up group hover:shadow-md transition-shadow">
            <div class="px-8 py-6 border-b border-gray-50 flex items-center gap-4">
                <div class="w-10 h-10 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center text-sm">
                    <i class="fas fa-play"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 font-['Fredoka'] leading-tight">Brand Story</h3>
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Cinematic video section</p>
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="p-6 md:p-10">
                <input type="hidden" name="update_video_section" value="1">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <div class="space-y-6">
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black uppercase text-gray-400 ml-3 tracking-widest">Section Heading</label>
                            <textarea name="heading" rows="2" class="w-full bg-gray-50/50 border border-gray-100 focus:border-indigo-500 rounded-xl px-4 py-3 outline-none font-bold text-xl"><?php echo htmlspecialchars($vid_sec['heading']); ?></textarea>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black uppercase text-gray-400 ml-3 tracking-widest">Subheading / Description</label>
                            <textarea name="subheading" rows="3" class="w-full bg-gray-50/50 border border-gray-100 focus:border-indigo-500 rounded-xl px-4 py-3 outline-none font-medium text-gray-500"><?php echo htmlspecialchars($vid_sec['subheading']); ?></textarea>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black uppercase text-gray-400 ml-3 tracking-widest">YouTube Video URL (Alternative)</label>
                            <input type="text" name="video_url" value="<?php echo htmlspecialchars($vid_sec['video_url']); ?>" class="w-full bg-gray-50/50 border border-gray-100 focus:border-indigo-500 rounded-xl px-4 py-3 outline-none font-bold text-sm" placeholder="https://youtube.com/watch?v=...">
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="space-y-1">
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-[10px] font-black uppercase text-gray-400 ml-3 tracking-widest">Live Video Preview</label>
                                 <label class="cursor-pointer bg-gray-100 hover:bg-black hover:text-white px-4 py-1.5 rounded-lg text-[9px] font-bold uppercase tracking-widest transition-all">
                                    Upload Video File
                                    <input type="file" name="video_file" id="vid-file-input" accept="video/*" class="hidden" onchange="previewMedia(this, 'vid-file-preview')">
                                </label>
                            </div>
                            
                            <div class="relative group aspect-video rounded-2xl overflow-hidden border-2 border-gray-100 bg-gray-50 shadow-sm">
                                <img id="vid-cover-preview" src="../<?php echo !empty($vid_sec['media_url']) ? $vid_sec['media_url'] : 'assets/images/hero.jpg'; ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                <video id="vid-file-preview" class="absolute inset-0 w-full h-full object-cover hidden z-10" autoplay muted loop></video>
                                
                                <div class="absolute inset-0 flex flex-col justify-end p-6 z-20 pointer-events-none bg-gradient-to-t from-black/60 to-transparent">
                                    <h2 id="vid-head-preview" class="text-white font-black font-['Fredoka'] text-lg drop-shadow-lg leading-tight"><?php echo nl2br(htmlspecialchars($vid_sec['heading'])); ?></h2>
                                </div>

                                <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity z-30">
                                    <label class="cursor-pointer bg-white text-black px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 hover:bg-[#19DC7E]">
                                        <i class="fas fa-image"></i> Change Cover
                                        <input type="file" name="media" id="vid-cover-input" class="hidden" onchange="previewMedia(this, 'vid-cover-preview')">
                                    </label>
                                </div>
                                <input type="hidden" name="current_media" value="<?php echo htmlspecialchars($vid_sec['media_url']); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 pt-8 border-t border-gray-50 flex justify-end">
                    <button type="submit" name="update_video_section" class="bg-black text-white px-8 py-3 rounded-xl font-bold uppercase text-xs tracking-widest hover:bg-indigo-500 hover:text-white transition-all shadow-md">Update Brand Story</button>
                </div>
            </form>
        </div>

    </div>
</div>

<style>
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
