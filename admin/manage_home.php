<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Strict Admin Check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Database Connection
$conn = get_db_connection();
$msg = "";
$error = "";

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Detect if post_max_size was exceeded
    if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $_SESSION['error'] = "The file you're trying to upload is too large.";
        header("Location: manage_home.php");
        exit;
    }

    $redirect = false;

    // --- HERO GLOBAL SETTINGS UPDATE ---
    if (isset($_POST['update_hero'])) {
        $show_stats = isset($_POST['show_hero_stats']) ? 'on' : 'off';
        update_setting('show_hero_stats', $show_stats);
        $_SESSION['msg'] = "Global hero settings updated!";
        $redirect = true;
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
        if($stmt->execute()) {
            $_SESSION['msg'] = "Sale countdown updated!";
            $redirect = true;
        } else {
            $_SESSION['error'] = "Sale update failed.";
        }
    }

    // --- ANNOUNCEMENT BAR UPDATE ---
    if (isset($_POST['update_announcement'])) {
        $announcement_text = sanitize_input($_POST['announcement_text'] ?? '');
        $announcement_bg = sanitize_input($_POST['announcement_bg_color'] ?? '#004f42');
        update_setting('announcement_text', $announcement_text);
        update_setting('announcement_bg_color', $announcement_bg);
        $_SESSION['msg'] = "Announcement bar updated!";
        $redirect = true;
    }

    // --- TRUST BADGE MANAGEMENT ---
    if (isset($_POST['add_badge']) || isset($_POST['edit_badge'])) {
        $title = sanitize_input($_POST['badge_title']);
        $subtitle = sanitize_input($_POST['badge_subtitle']);
        $icon = sanitize_input($_POST['badge_icon']);
        $bg_color = sanitize_input($_POST['badge_bg_color']);
        $icon_color = sanitize_input($_POST['badge_icon_color']);
        $sort_order = intval($_POST['sort_order']);
        
        if (isset($_POST['add_badge'])) {
            $stmt = $conn->prepare("INSERT INTO trust_badges (title, subtitle, icon, bg_color, icon_color, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $title, $subtitle, $icon, $bg_color, $icon_color, $sort_order);
            if ($stmt->execute()) $_SESSION['msg'] = "Badge added!";
            else $_SESSION['error'] = "Failed to add badge.";
        } else {
            $id = intval($_POST['badge_id']);
            $stmt = $conn->prepare("UPDATE trust_badges SET title=?, subtitle=?, icon=?, bg_color=?, icon_color=?, sort_order=? WHERE id=?");
            $stmt->bind_param("sssssii", $title, $subtitle, $icon, $bg_color, $icon_color, $sort_order, $id);
            if ($stmt->execute()) $_SESSION['msg'] = "Badge updated!";
            else $_SESSION['error'] = "Failed to update badge.";
        }
        $redirect = true;
    }

    if (isset($_POST['delete_badge'])) {
        $id = intval($_POST['badge_id']);
        $conn->query("DELETE FROM trust_badges WHERE id = $id");
        $_SESSION['msg'] = "Badge removed!";
        $redirect = true;
    }

    if (isset($_POST['toggle_badge'])) {
        $id = intval($_POST['badge_id']);
        $conn->query("UPDATE trust_badges SET is_active = 1 - is_active WHERE id = $id");
        $_SESSION['msg'] = "Badge status toggled!";
        $redirect = true;
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
            if ($stmt->execute()) {
                $_SESSION['msg'] = "Video section updated!";
                $redirect = true;
            }
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
        $product_id = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;

        $price = floatval($_POST['price'] ?? 0);
        $accent_color = $_POST['accent_color'] ?? '#19DC7E';
        $v_text_1 = $_POST['v_text_1'] ?? 'SNACKING';
        $v_text_2 = $_POST['v_text_2'] ?? 'REIMAGINED';

        if (isset($_POST['add_slide'])) {
            // Mandatory Validation for Add
            if(empty($title)) $error = "Missing Input: Slide Heading is mandatory.";
            if($_FILES['slide_image']['error'] != 0) $error = "Missing Input: Slide Photo/Image is mandatory.";
            
            if(!$error) {
                $image_url = 'assets/images/hero.jpg';
                if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] == 0) {
                    $target_dir = "../assets/images/uploads/";
                    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                    $filename = "slide_" . uniqid() . "_" . basename($_FILES["slide_image"]["name"]);
                    if (move_uploaded_file($_FILES["slide_image"]["tmp_name"], $target_dir . $filename)) {
                        $image_url = "assets/images/uploads/" . $filename;
                    }
                }
                $stmt = $conn->prepare("INSERT INTO hero_slides (title, show_title, subtitle, show_subtitle, image, cta_text, cta_link, show_cta, sort_order, is_active, badge_text, show_badge, price, accent_color, v_text_1, v_text_2, product_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sisisssiisidsssi", $title, $show_title, $subtitle, $show_subtitle, $image_url, $cta_text, $cta_link, $show_cta, $sort_order, $badge_text, $show_badge, $price, $accent_color, $v_text_1, $v_text_2, $product_id);
                if ($stmt->execute()) {
                    $_SESSION['msg'] = "New dynamic slide added!";
                    $redirect = true;
                } else $error = "Failed to add slide: " . $conn->error;
            }
        } else {
            $id = intval($_POST['slide_id']);
            // Mandatory Validation for Edit
            if(empty($title)) $error = "Missing Input: Slide Heading is mandatory.";

            if(!$error) {
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
                $stmt = $conn->prepare("UPDATE hero_slides SET title=?, show_title=?, subtitle=?, show_subtitle=?, image=?, cta_text=?, cta_link=?, show_cta=?, sort_order=?, badge_text=?, show_badge=?, price=?, accent_color=?, v_text_1=?, v_text_2=?, product_id=? WHERE id=?");
                $stmt->bind_param("sisisssiisidsssii", $title, $show_title, $subtitle, $show_subtitle, $image_url, $cta_text, $cta_link, $show_cta, $sort_order, $badge_text, $show_badge, $price, $accent_color, $v_text_1, $v_text_2, $product_id, $id);
                if ($stmt->execute()) {
                    $_SESSION['msg'] = "Dynamic slide updated!";
                    $redirect = true;
                } else $error = "Failed to update slide: " . $conn->error;
            }
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
        $_SESSION['msg'] = "Slide removed!";
        $redirect = true;
    }

    if (isset($_POST['toggle_slide'])) {
        $id = intval($_POST['slide_id']);
        $conn->query("UPDATE hero_slides SET is_active = 1 - is_active WHERE id = $id");
        $_SESSION['msg'] = "Slide status toggled!";
        $redirect = true;
    }

    if ($redirect) {
        Cache::forget('hero_slides_all');
        Cache::forget('hero_slides_active');
        header("Location: manage_home.php");
        exit;
    }
}

// Handle Delete Announcement
if(isset($_GET['del_anno'])) {
    $id = intval($_GET['del_anno']);
    $conn->query("DELETE FROM announcements WHERE id = $id");
    $_SESSION['msg'] = "Announcement deleted!";
    header("Location: manage_home.php");
    exit;
}

include 'includes/header.php';

$msg = $_SESSION['msg'] ?? "";
$error = $_SESSION['error'] ?? "";
unset($_SESSION['msg'], $_SESSION['error']);

$all_products = fetch_all("SELECT id, name, price FROM products WHERE is_active = 1 ORDER BY name ASC");
$vid_sec = fetch_one("SELECT * FROM homepage_sections WHERE section_name = 'video_brand_story'");
$sale = fetch_one("SELECT * FROM sale_countdowns LIMIT 1");
$announcement_text = get_setting('announcement_text', '🚀 Free Shipping on All Orders Over ₹499 • 🌿 100% Organic & Natural');
$announcement_bg = get_setting('announcement_bg_color', '#004f42');
$hero_slides = get_hero_slides(true);
$trust_badges = fetch_all("SELECT * FROM trust_badges ORDER BY sort_order ASC");
$show_stats = get_setting('show_hero_stats', 'on');
?>

<!-- VIEW START -->
<div class="pb-20 anim-up">
    
    <!-- Top Action Bar -->
    <div class="sticky top-20 z-40 bg-white/80 backdrop-blur-md border-b border-gray-100 py-4 mb-8 -mx-4 px-4 md:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 crimson-pro tracking-tight">Homepage Architect</h1>
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
        <div class="bg-gradient-to-br from-[#24B25D] to-[#10b981] p-6 rounded-3xl text-white shadow-xl shadow-green-100 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 text-7xl opacity-20 transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
                <i class="fas fa-rocket"></i>
            </div>
            <p class="text-white/80 font-bold uppercase text-[10px] tracking-widest mb-1">Current Active Sale</p>
            <h3 class="text-2xl font-black font-heading"><?php echo ($sale['is_active'] ?? 0) ? htmlspecialchars($sale['title']) : 'No Active Sale'; ?></h3>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-5 group hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="fas fa-bullhorn"></i>
            </div>
            <div>
                <p class="text-gray-400 font-bold text-[10px] tracking-widest uppercase">Bar Status</p>
                <h3 class="text-xl font-black font-heading text-gray-900"><?php echo !empty($announcement_text) ? 'Active' : 'Empty'; ?></h3>
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

        <!-- TRUST BADGES MANAGER -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up mb-10">
            <div class="px-8 py-6 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/50">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center text-sm">
                        <i class="fas fa-shield-heart"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 font-heading leading-tight">Trust Marquee</h3>
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Manage infinite scrolling trust badges</p>
                    </div>
                </div>
                <button onclick="showModal('badge-modal')" class="bg-indigo-600 text-white px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg flex items-center gap-2">
                    <i class="fas fa-plus"></i> Add New Badge
                </button>
            </div>

            <div class="p-8">
                <?php if(empty($trust_badges)): ?>
                    <div class="bg-indigo-50 rounded-2xl p-10 text-center border border-indigo-100/50">
                        <i class="fas fa-certificate text-4xl text-indigo-200 mb-4 block"></i>
                        <p class="text-xs font-bold text-indigo-800 uppercase tracking-widest">No Badges Defined</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left border-b border-gray-100">
                                    <th class="pb-4 text-[9px] font-black text-gray-400 uppercase tracking-widest px-4">Icon & Title</th>
                                    <th class="pb-4 text-[9px] font-black text-gray-400 uppercase tracking-widest px-4 text-center">Style</th>
                                    <th class="pb-4 text-[9px] font-black text-gray-400 uppercase tracking-widest px-4 text-center">Status</th>
                                    <th class="pb-4 text-[9px] font-black text-gray-400 uppercase tracking-widest px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php foreach($trust_badges as $badge): 
                                    $bg = str_contains($badge['bg_color'], '[') ? substr($badge['bg_color'], 4, 7) : $badge['bg_color'];
                                    $ic = str_contains($badge['icon_color'], '[') ? substr($badge['icon_color'], 6, 7) : $badge['icon_color'];
                                ?>
                                <tr class="group hover:bg-gray-50/50 transition-colors">
                                    <td class="py-5 px-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm" style="background-color: <?php echo $bg; ?>; color: <?php echo $ic; ?>;">
                                                <i class="<?php echo $badge['icon']; ?> text-xl"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-black text-gray-900 tracking-tight uppercase"><?php echo htmlspecialchars($badge['title']); ?></p>
                                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest"><?php echo htmlspecialchars($badge['subtitle']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-5 px-4 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="text-[8px] font-bold text-gray-400 uppercase tracking-tighter">BG: <?php echo $bg; ?></span>
                                            <span class="text-[8px] font-bold text-gray-400 uppercase tracking-tighter">Icon: <?php echo $ic; ?></span>
                                        </div>
                                    </td>
                                    <td class="py-5 px-4 text-center">
                                        <form method="POST">
                                            <input type="hidden" name="badge_id" value="<?php echo $badge['id']; ?>">
                                            <button type="submit" name="toggle_badge" class="px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-tighter transition-all <?php echo $badge['is_active'] ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'; ?>">
                                                <?php echo $badge['is_active'] ? 'Active' : 'Disabled'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="py-5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick='openEditBadge(<?php echo json_encode($badge); ?>)' class="w-8 h-8 rounded-lg bg-gray-100 text-gray-600 hover:bg-indigo-600 hover:text-white transition-all flex items-center justify-center text-xs shadow-sm">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="contents" onsubmit="return confirm('Delete this trust badge?')">
                                                <input type="hidden" name="badge_id" value="<?php echo $badge['id']; ?>">
                                                <button type="submit" name="delete_badge" class="w-8 h-8 rounded-lg bg-gray-100 text-red-500 hover:bg-red-500 hover:text-white transition-all flex items-center justify-center text-xs shadow-sm">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DYNAMIC HERO SLIDES MANAGER -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up mb-10">
            <div class="px-8 py-6 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/50">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-[#24B25D]/10 text-[#24B25D] rounded-full flex items-center justify-center text-sm">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 font-heading leading-tight">Slider Master</h3>
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Manage multiple hero banners</p>
                    </div>
                </div>
                <button onclick="showModal('add-slide-modal')" class="bg-black text-white px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-[#24B25D] hover:text-black transition-all shadow-lg flex items-center gap-2">
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
                                            <span class="bg-[#24B25D] text-black text-[8px] font-black px-3 py-1 rounded-full uppercase tracking-tighter shadow-xl flex items-center gap-1.5">
                                                <span class="w-1 h-1 bg-black rounded-full animate-pulse"></span> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-gray-500 text-white text-[8px] font-black px-3 py-1 rounded-full uppercase tracking-tighter shadow-xl">Hidden</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Actions Overlay -->
                                    <div class="absolute inset-0 flex items-center justify-center gap-3 opacity-0 group-hover:opacity-100 transition-opacity bg-black/40 backdrop-blur-sm">
                                        <button onclick='openEditSlide(<?php echo json_encode($slide); ?>)' class="w-10 h-10 bg-white text-gray-900 rounded-xl flex items-center justify-center hover:bg-[#24B25D] hover:text-white transition-all shadow-xl" title="Edit Content">
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
                                        <h4 class="text-white font-black crimson-pro text-sm leading-tight line-clamp-2 <?php echo !$slide['show_title'] ? 'opacity-30 line-through' : ''; ?>"><?php echo htmlspecialchars($slide['title']); ?></h4>
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
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#24B25D]"></div>
                        </label>
                        <span class="text-[10px] font-black text-gray-600 uppercase tracking-widest">Global Review Stats Badge</span>
                    </div>
                </form>
            </div>
        </div>



        <!-- TWO COLUMN GRID FOR SMALLER SECTIONS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- SALE COUNTDOWN -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden anim-up flex flex-col group hover:shadow-md transition-shadow">
                <div class="px-8 py-5 border-b border-gray-50 flex items-center gap-4">
                    <div class="w-10 h-10 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center text-sm">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 font-heading leading-tight">Flash Sale</h3>
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
                        <h3 class="text-lg font-bold text-gray-900 font-heading leading-tight">Marquee</h3>
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Header notification bar</p>
                    </div>
                </div>
                <form method="POST" class="p-6 flex-1 flex flex-col">
                    <input type="hidden" name="update_announcement" value="1">
                    <div class="space-y-4 flex-1">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase text-gray-400 ml-3 tracking-widest">Marquee Text</label>
                                <textarea name="announcement_text" rows="3" class="w-full bg-gray-50/50 border border-gray-100 focus:border-blue-500 focus:bg-white rounded-2xl px-5 py-3 outline-none transition-all font-bold shadow-inner resize-none text-sm" placeholder="Enter marquee text..."><?php echo htmlspecialchars($announcement_text); ?></textarea>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase text-gray-400 ml-3 tracking-widest">Bar Color</label>
                                <div class="bg-gray-50/50 border border-gray-100 rounded-2xl p-3 flex flex-col justify-center h-[calc(100%-1.5rem)]">
                                    <div class="flex items-center gap-4">
                                        <div class="relative w-12 h-12 rounded-xl overflow-hidden border-2 border-white shadow-sm">
                                            <input type="color" name="announcement_bg_color" id="ann_bg_input" value="<?php echo $announcement_bg; ?>" class="absolute inset-[-10px] w-[200%] h-[200%] cursor-pointer" oninput="document.getElementById('ann_bg_hex').innerText = this.value.toUpperCase()">
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Background Hex</p>
                                            <code id="ann_bg_hex" class="text-sm font-bold text-gray-700"><?php echo strtoupper($announcement_bg); ?></code>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                    <h3 class="text-lg font-bold text-gray-900 font-heading leading-tight">Brand Story</h3>
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
                                    <h2 id="vid-head-preview" class="text-white font-black font-heading text-lg drop-shadow-lg leading-tight"><?php echo nl2br(htmlspecialchars($vid_sec['heading'])); ?></h2>
                                </div>

                                <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity z-30">
                                    <label class="cursor-pointer bg-white text-black px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 hover:bg-[#24B25D]">
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


<!-- MODALS SECTION -->
<div id="add-slide-modal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="hideModal('add-slide-modal')"></div>
    <div class="bg-white w-full max-w-xl rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.3)] overflow-hidden anim-up border border-gray-100 relative z-10 max-h-[90vh] flex flex-col">
        <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
            <div>
                <h3 class="text-xl font-black text-gray-900 font-heading">Add New Slide</h3>
                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Configure your hero masterpiece</p>
            </div>
            <button onclick="hideModal('add-slide-modal')" class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="overflow-y-auto custom-scrollbar flex-1">
            <form method="POST" enctype="multipart/form-data" class="p-8 space-y-8">
                <input type="hidden" name="add_slide" value="1">
                <div class="relative h-[250px] rounded-2xl overflow-hidden bg-[#002A23] border border-gray-100 shadow-inner flex flex-row group/preview">
                    <div class="w-[60%] h-full p-6 flex flex-col justify-center relative z-10">
                        <span class="text-[#19DC7E] font-black uppercase tracking-[0.2em] text-[6px] mb-1 transition-opacity opacity-0" id="add-preview-tagline-val" style="opacity: 1;">DRIYUM IS...</span>
                        <h1 id="add-preview-title" class="text-white font-black leading-tight text-xl uppercase tracking-tighter mb-2">YOUR<br>HEADLINE</h1>
                        <div class="flex items-center gap-2 mb-3">
                            <div class="bg-white/10 px-3 py-1.5 rounded-lg border border-white/5 flex flex-col items-center">
                                <span class="text-white font-black text-[10px]" id="add-preview-price">₹249</span>
                                <span class="text-[5px] text-white/40 font-black uppercase tracking-widest">PRICE</span>
                            </div>
                            <div class="bg-white px-3 py-1.5 rounded-lg flex items-center gap-2">
                                <div class="w-4 h-4 rounded-full bg-[#002A23] flex items-center justify-center text-white">
                                    <i class="fas fa-truck-fast text-[7px]"></i>
                                </div>
                                <span class="text-[7px] font-black text-[#002A23]">FREE</span>
                            </div>
                        </div>
                    </div>
                    <div class="w-[40%] h-full relative flex items-center justify-center">
                        <div id="add-preview-accent" class="absolute inset-y-0 right-0 w-[85%] bg-[#19DC7E] rounded-l-[30px] transition-all duration-500"></div>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none opacity-10">
                            <span id="add-preview-v1" class="text-[20px] font-black text-black leading-none uppercase tracking-tighter">SNACKING</span>
                            <span id="add-preview-v2" class="text-[20px] font-black text-black leading-none uppercase tracking-tighter">DRIYUM</span>
                        </div>
                        <img id="add-preview-bg" src="../assets/images/hero.jpg" class="relative z-10 w-full max-w-[120px] h-auto drop-shadow-2xl transition-transform duration-500">
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <div class="flex items-center justify-between mb-1 ml-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Main Heading</label>
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="show_title" value="1" checked onchange="updatePreview('add')" class="w-3.5 h-3.5 rounded border-gray-300 text-green-500">
                                    <span class="text-[9px] font-bold text-gray-400">Visible</span>
                                </label>
                            </div>
                            <textarea name="slide_title" id="add-slide-title" placeholder="e.g. SIGNATURE ALMONDS" rows="2" oninput="updatePreview('add')" class="w-full border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-green-500 font-bold text-gray-900 resize-none" required></textarea>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Linked Product</label>
                            <select name="product_id" id="add-product-id" onchange="onProductChange('add')" class="w-full border border-gray-200 rounded-lg px-4 py-2 outline-none focus:border-green-500 font-bold text-sm bg-white">
                                <option value="">-- No Product Linked --</option>
                                <?php foreach($all_products as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-1 ml-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Brand Pitch</label>
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="show_badge" value="1" checked onchange="updatePreview('add')" class="w-3.5 h-3.5 rounded border-gray-300 text-green-500">
                                    <span class="text-[9px] font-bold text-gray-400">Visible</span>
                                </label>
                            </div>
                            <input type="text" name="badge_text" id="add-badge-text" placeholder="e.g. DRIYUM IS..." oninput="updatePreview('add')" class="w-full border border-gray-200 rounded-lg px-3 py-2 outline-none focus:border-green-500 font-bold text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Hero Price (₹)</label>
                            <input type="number" step="0.01" name="price" id="add-price" value="249" oninput="updatePreview('add')" class="w-full border border-gray-200 rounded-lg px-4 py-2 outline-none focus:border-green-500 font-bold text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Accent Color</label>
                            <div class="flex items-center gap-2 border border-gray-200 rounded-lg px-3 py-1 bg-white">
                                <input type="color" name="accent_color" id="add-accent-color" value="#19DC7E" oninput="updatePreview('add')" class="w-8 h-8 cursor-pointer rounded-md border-0 bg-transparent">
                                <span class="text-[10px] font-black text-gray-400 uppercase" id="add-accent-hex">#19DC7E</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Slide Photo</label>
                            <input type="file" name="slide_image" id="add-file-input" onchange="previewSlideFile(this, 'add')" class="hidden" required>
                            <label for="add-file-input" class="w-full border border-gray-200 rounded-lg px-4 py-2 text-center bg-gray-50 hover:bg-gray-100 cursor-pointer font-bold text-xs text-gray-500 transition-colors">
                                <i class="fas fa-camera mr-2"></i> Choose File
                            </label>
                        </div>
                    </div>
                </div>
                <div class="pt-4">
                    <button type="submit" class="w-full bg-[#24B25D] text-black py-4 rounded-2xl font-black uppercase text-xs tracking-[0.2em] hover:bg-black hover:text-white transition-all shadow-xl active:scale-95">
                       <i class="fas fa-save mr-2"></i> Deploy New Slide
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="edit-slide-modal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="hideModal('edit-slide-modal')"></div>
    <div class="bg-white w-full max-w-xl rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.3)] overflow-hidden anim-up border border-gray-100 relative z-10 max-h-[90vh] flex flex-col">
        <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
            <div>
                <h3 class="text-xl font-black text-gray-900 font-heading">Edit Slide</h3>
                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Refining the experience</p>
            </div>
            <button onclick="hideModal('edit-slide-modal')" class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="overflow-y-auto custom-scrollbar flex-1">
            <form method="POST" enctype="multipart/form-data" class="p-8 space-y-8">
                <input type="hidden" name="edit_slide" value="1">
                <input type="hidden" name="slide_id" id="edit-slide-id">
                <input type="hidden" name="current_image" id="edit-current-image">
                <div class="relative h-[250px] rounded-2xl overflow-hidden bg-[#002A23] border border-gray-100 shadow-inner flex flex-row group/preview">
                    <div class="w-[60%] h-full p-6 flex flex-col justify-center relative z-10">
                        <span class="text-[#19DC7E] font-black uppercase tracking-[0.2em] text-[6px] mb-1 transition-opacity opacity-0" id="edit-preview-tagline-val" style="opacity: 1;">DRIYUM IS...</span>
                        <h1 id="edit-preview-title" class="text-white font-black leading-tight text-xl uppercase tracking-tighter mb-2">HEADLINE</h1>
                        <div class="flex items-center gap-2 mb-3">
                            <div class="bg-white/10 px-3 py-1.5 rounded-lg border border-white/5 flex flex-col items-center">
                                <span class="text-white font-black text-[10px]" id="edit-preview-price">₹249</span>
                                <span class="text-[5px] text-white/40 font-black uppercase tracking-widest">PRICE</span>
                            </div>
                            <div class="bg-white px-3 py-1.5 rounded-lg flex items-center gap-2">
                                <div class="w-4 h-4 rounded-full bg-[#002A23] flex items-center justify-center text-white">
                                    <i class="fas fa-truck-fast text-[7px]"></i>
                                </div>
                                <span class="text-[7px] font-black text-[#002A23]">FREE</span>
                            </div>
                        </div>
                    </div>
                    <div class="w-[40%] h-full relative flex items-center justify-center">
                        <div id="edit-preview-accent" class="absolute inset-y-0 right-0 w-[85%] bg-[#19DC7E] rounded-l-[30px] transition-all duration-500"></div>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none opacity-10">
                            <span id="edit-preview-v1" class="text-[20px] font-black text-black leading-none uppercase tracking-tighter">TEXT</span>
                            <span id="edit-preview-v2" class="text-[20px] font-black text-black leading-none uppercase tracking-tighter">HERE</span>
                        </div>
                        <img id="edit-preview-bg" src="" class="relative z-10 w-full max-w-[120px] h-auto drop-shadow-2xl">
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <div class="flex items-center justify-between mb-1 ml-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Product Heading</label>
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="show_title" id="edit-show-title" value="1" onchange="updatePreview('edit')" class="w-3.5 h-3.5 rounded border-gray-300 text-green-500">
                                    <span class="text-[9px] font-bold text-gray-400">Visible</span>
                                </label>
                            </div>
                            <textarea name="slide_title" id="edit-slide-title" placeholder="Banner text..." rows="2" oninput="updatePreview('edit')" class="w-full border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-green-500 font-bold text-gray-900 resize-none" required></textarea>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Linked Product</label>
                            <select name="product_id" id="edit-product-id" onchange="onProductChange('edit')" class="w-full border border-gray-200 rounded-lg px-4 py-2 outline-none focus:border-green-500 font-bold text-sm bg-white">
                                <option value="">-- No Product Linked --</option>
                                <?php foreach($all_products as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-1 ml-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Brand Pitch</label>
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="show_badge" id="edit-show-badge" value="1" onchange="updatePreview('edit')" class="w-3.5 h-3.5 rounded border-gray-300 text-green-500">
                                    <span class="text-[9px] font-bold text-gray-400">Visible</span>
                                </label>
                            </div>
                            <input type="text" name="badge_text" id="edit-badge-text" placeholder="e.g. DRIYUM IS..." oninput="updatePreview('edit')" class="w-full border border-gray-200 rounded-lg px-3 py-2 outline-none focus:border-green-500 font-bold text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Hero Price (₹)</label>
                            <input type="number" step="0.01" name="price" id="edit-price" oninput="updatePreview('edit')" class="w-full border border-gray-200 rounded-lg px-4 py-2 outline-none focus:border-green-500 font-bold text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Accent Plate Color</label>
                            <div class="flex items-center gap-2 border border-gray-200 rounded-lg px-3 py-1 bg-white">
                                <input type="color" name="accent_color" id="edit-accent-color" oninput="updatePreview('edit')" class="w-8 h-8 cursor-pointer rounded-md border-0 bg-transparent">
                                <span class="text-[10px] font-black text-gray-400 uppercase" id="edit-accent-hex">#19DC7E</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Swap Photo</label>
                            <input type="file" name="slide_image" id="edit-file-input" onchange="previewSlideFile(this, 'edit')" class="hidden">
                            <label for="edit-file-input" class="w-full border border-gray-200 rounded-lg px-4 py-2 text-center bg-gray-50 hover:bg-gray-100 cursor-pointer font-bold text-xs text-gray-500 transition-colors">
                                <i class="fas fa-camera mr-2"></i> Update Image
                            </label>
                        </div>
                    </div>
                </div>
                <div class="pt-4">
                    <button type="submit" class="w-full bg-black text-white py-4 rounded-2xl font-black uppercase text-xs tracking-[0.2em] hover:bg-[#24B25D] hover:text-black transition-all shadow-xl active:scale-95">
                        <i class="fas fa-check-circle mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="badge-modal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeBadgeModal()"></div>
    <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-[0_30px_60px_rgba(0,0,0,0.25)] overflow-hidden anim-up border border-gray-100 relative z-10">
        <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <div>
                <h3 class="text-xl font-black text-gray-900 font-heading" id="badge-modal-title">Add Trust Badge</h3>
                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Enhance site credibility</p>
            </div>
            <button onclick="closeBadgeModal()" class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all shadow-sm">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="p-10 space-y-6">
            <input type="hidden" name="badge_id" id="modal-badge-id">
            <input type="hidden" name="add_badge" id="modal-badge-action-add" value="1">
            <input type="hidden" name="edit_badge" id="modal-badge-action-edit" value="1" disabled>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Badge Title</label>
                    <input type="text" name="badge_title" id="modal-badge-title" placeholder="Main Highlight" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 outline-none focus:border-indigo-500 font-bold text-sm" required>
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Subtitle</label>
                    <input type="text" name="badge_subtitle" id="modal-badge-subtitle" placeholder="Secondary Info" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 outline-none focus:border-indigo-500 font-bold text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Icon Class (FA)</label>
                    <input type="text" name="badge_icon" id="modal-badge-icon" placeholder="fas fa-leaf" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 outline-none focus:border-indigo-500 font-bold text-sm" required>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Sort Order</label>
                    <input type="number" name="sort_order" id="modal-badge-sort" value="0" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 outline-none focus:border-indigo-500 font-bold text-sm text-center">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Card Color</label>
                    <div class="flex items-center gap-2 border border-gray-200 rounded-lg px-3 py-1 bg-white">
                        <input type="color" name="badge_bg_color" id="modal-badge-bg" value="#FFFEDC" class="w-8 h-8 cursor-pointer rounded-md border-0 bg-transparent">
                        <span class="text-[10px] font-black text-gray-400 uppercase" id="badge-bg-hex">#FFFEDC</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 ml-1">Icon Color</label>
                    <div class="flex items-center gap-2 border border-gray-200 rounded-lg px-3 py-1 bg-white">
                        <input type="color" name="badge_icon_color" id="modal-badge-icon-color" value="#19DC7E" class="w-8 h-8 cursor-pointer rounded-md border-0 bg-transparent">
                        <span class="text-[10px] font-black text-gray-400 uppercase" id="badge-icon-hex">#19DC7E</span>
                    </div>
                </div>
            </div>
            <div class="pt-6 flex gap-4">
                <button type="button" onclick="closeBadgeModal()" class="flex-1 px-6 py-4 rounded-2xl border border-gray-100 font-black text-[10px] uppercase tracking-widest text-gray-400 hover:bg-gray-50 transition-all shadow-sm">Cancel</button>
                <button type="submit" id="badge-modal-submit" class="flex-1 px-6 py-4 rounded-2xl bg-black text-white font-black text-[10px] uppercase tracking-widest hover:bg-indigo-700 shadow-xl transition-all active:scale-95">Save Badge</button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes slideIn {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .anim-up { animation: slideIn 0.4s ease-out forwards; }
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #E5E7EB; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #D1D5DB; }
</style>

<script>
// --- MODAL CORE ---
function showModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function hideModal(id) {
    document.getElementById(id).classList.add('hidden');
    if(!document.querySelector('.fixed:not(.hidden)')) {
        document.body.classList.remove('overflow-hidden');
    }
}

// --- TRUST BADGE LOGIC ---
function openEditBadge(badge) {
    document.getElementById('badge-modal-title').innerText = 'Edit Trust Badge';
    document.getElementById('modal-badge-id').value = badge.id;
    document.getElementById('modal-badge-title').value = badge.title;
    document.getElementById('modal-badge-subtitle').value = badge.subtitle;
    document.getElementById('modal-badge-icon').value = badge.icon;
    const bgHex = badge.bg_color.includes('[') ? badge.bg_color.match(/#([a-fA-F0-9]{6})/)[0] : badge.bg_color;
    const icHex = badge.icon_color.includes('[') ? badge.icon_color.match(/#([a-fA-F0-9]{6})/)[0] : badge.icon_color;
    document.getElementById('modal-badge-bg').value = bgHex;
    document.getElementById('badge-bg-hex').innerText = bgHex.toUpperCase();
    document.getElementById('modal-badge-icon-color').value = icHex;
    document.getElementById('badge-icon-hex').innerText = icHex.toUpperCase();
    document.getElementById('modal-badge-sort').value = badge.sort_order;
    document.getElementById('modal-badge-action-add').disabled = true;
    document.getElementById('modal-badge-action-edit').disabled = false;
    showModal('badge-modal');
}

function closeBadgeModal() {
    hideModal('badge-modal');
    setTimeout(() => {
        document.getElementById('badge-modal-title').innerText = 'Add Trust Badge';
        document.getElementById('modal-badge-action-add').disabled = false;
        document.getElementById('modal-badge-action-edit').disabled = true;
        document.getElementById('modal-badge-id').value = '';
    }, 300);
}

// Sync Hex labels
if(document.getElementById('modal-badge-bg')) {
    document.getElementById('modal-badge-bg').oninput = function() { document.getElementById('badge-bg-hex').innerText = this.value.toUpperCase(); };
}
if(document.getElementById('modal-badge-icon-color')) {
    document.getElementById('modal-badge-icon-color').oninput = function() { document.getElementById('badge-icon-hex').innerText = this.value.toUpperCase(); };
}

// --- HERO SLIDE LOGIC ---
const allProducts = <?php echo json_encode($all_products); ?>;

function onProductChange(type) {
    const prefix = type === 'add' ? 'add' : 'edit';
    const select = document.getElementById(prefix + '-product-id');
    const titleInput = type === 'add' ? document.getElementById('add-slide-title') : document.getElementById('edit-slide-title');
    const priceInput = document.getElementById(prefix + '-price');
    const productId = select.value;
    if(!productId) return;
    const product = allProducts.find(p => p.id == productId);
    if(product) {
        if(!titleInput.value || titleInput.value.length < 3) titleInput.value = product.name.toUpperCase();
        priceInput.value = product.price;
        updatePreview(type);
    }
}

function updatePreview(type) {
    const container = type === 'add' ? document.getElementById('add-slide-modal') : document.getElementById('edit-slide-modal');
    const prefix = type === 'add' ? 'add' : 'edit';
    const badge = container.querySelector('[name="badge_text"]').value;
    const title = container.querySelector('[name="slide_title"]').value;
    const price = container.querySelector('[name="price"]').value;
    const accent = container.querySelector('[name="accent_color"]').value;
    const showBadge = container.querySelector('[name="show_badge"]').checked;
    const showTitle = container.querySelector('[name="show_title"]').checked;
    
    const taglineEl = document.getElementById(prefix + '-preview-tagline-val');
    if(taglineEl) {
        taglineEl.innerText = badge || 'DRIYUM IS...';
        taglineEl.style.opacity = showBadge ? '1' : '0.1';
    }
    if(document.getElementById(prefix + '-preview-title'))
        document.getElementById(prefix + '-preview-title').innerHTML = (title || 'YOUR HEADLINE').replace(/\n/g, '<br>');
    if(document.getElementById(prefix + '-preview-price'))
        document.getElementById(prefix + '-preview-price').innerText = '₹' + (price || '249');
    if(document.getElementById(prefix + '-preview-accent'))
        document.getElementById(prefix + '-preview-accent').style.backgroundColor = accent;
    const hex = document.getElementById(prefix + '-accent-hex');
    if(hex) hex.innerText = accent.toUpperCase();
    if(document.getElementById(prefix + '-preview-title'))
         document.getElementById(prefix + '-preview-title').style.opacity = showTitle ? '1' : '0.1';
}

function previewSlideFile(input, type) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) { document.getElementById(type + '-preview-bg').src = e.target.result; }
        reader.readAsDataURL(input.files[0]);
    }
}

function openEditSlide(slide) {
    document.getElementById('edit-slide-id').value = slide.id;
    document.getElementById('edit-current-image').value = slide.image;
    document.getElementById('edit-badge-text').value = slide.badge_text || '';
    document.getElementById('edit-slide-title').value = slide.title || '';
    document.getElementById('edit-price').value = slide.price || 0;
    document.getElementById('edit-accent-color').value = slide.accent_color || '#19DC7E';
    document.getElementById('edit-product-id').value = slide.product_id || '';
    document.getElementById('edit-preview-bg').src = '../' + slide.image;
    document.getElementById('edit-show-badge').checked = slide.show_badge == 1;
    document.getElementById('edit-show-title').checked = slide.show_title == 1;
    updatePreview('edit');
    showModal('edit-slide-modal');
}

// --- MEDIA PREVIEW (Video Section) ---
function previewMedia(input, previewId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const preview = document.getElementById(previewId);
        if (file.type.startsWith('video/')) {
            const videoPreview = document.getElementById('vid-file-preview');
            if (videoPreview) {
                videoPreview.src = URL.createObjectURL(file);
                videoPreview.classList.remove('hidden');
                document.getElementById('vid-cover-preview').style.opacity = '0.3';
            }
        } else {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.setAttribute('src', e.target.result);
                preview.style.opacity = '1';
                const videoPreview = document.getElementById('vid-file-preview');
                if (videoPreview) videoPreview.classList.add('hidden');
            }
            reader.readAsDataURL(file);
        }
    }
}

// --- LIVE TEXT PREVIEW ---
document.addEventListener('DOMContentLoaded', () => {
    // Standardize modal triggers
    const triggers = [
        { sel: '[onclick*="add-badge-modal"]', id: 'badge-modal' },
        { sel: '[onclick*="add-slide-modal"]', id: 'add-slide-modal' }
    ];
    triggers.forEach(t => {
        const el = document.querySelector(t.sel);
        if(el) el.setAttribute('onclick', `showModal('${t.id}')`);
    });

    const heroHeadIn = document.getElementById('hero-head-input');
    const heroHeadPre = document.getElementById('hero-head-preview');
    if(heroHeadIn) heroHeadIn.oninput = (e) => heroHeadPre.innerHTML = e.target.value.replace(/\n/g, '<br>');
    const vidHeadIn = document.querySelector('textarea[name="heading"]');
    const vidHeadPre = document.getElementById('vid-head-preview');
    if(vidHeadIn) vidHeadIn.oninput = (e) => vidHeadPre.innerHTML = e.target.value.replace(/\n/g, '<br>');
});
</script>

</body>
</html>


