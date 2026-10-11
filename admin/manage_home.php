<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

function resolve_sort_conflict($table, $new_sort, $exclude_id = 0) {
    global $conn;
    $new_sort = intval($new_sort);
    $exclude_id = intval($exclude_id);
    
    $sql_check = "SELECT id FROM $table WHERE sort_order = $new_sort AND id != $exclude_id LIMIT 1";
    $exists = $conn->query($sql_check)->fetch_assoc();
    
    if ($exists) {
        $conn->query("UPDATE $table SET sort_order = sort_order + 1 WHERE sort_order >= $new_sort AND id != $exclude_id");
    }
}

if (session_status() === PHP_SESSION_NONE) session_start();

// Strict Admin Check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$conn = get_db_connection();
$msg = "";
$error = "";

$active_tab = $_GET['tab'] ?? 'badges';
if (!in_array($active_tab, ['badges', 'sale', 'announcement', 'video'])) {
    $active_tab = 'badges';
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $_SESSION['error'] = "The file you are trying to upload exceeds server limits.";
        header("Location: manage_home.php?tab=" . urlencode($active_tab));
        exit;
    }

    $redirect = false;
    $submitted_tab = $_POST['active_tab'] ?? $active_tab;

    // --- AJAX CUSTOM BADGE ICON UPLOAD ---
    if (isset($_POST['action']) && $_POST['action'] === 'upload_badge_icon') {
        header('Content-Type: application/json');
        if (isset($_FILES['badge_icon_file']) && $_FILES['badge_icon_file']['error'] === UPLOAD_ERR_OK) {
            $allowed_exts = ['svg', 'png', 'webp', 'jpg', 'jpeg'];
            $file = $_FILES['badge_icon_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_exts)) {
                $target_dir = "../assets/images/badges/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

                $orig_name = pathinfo($file['name'], PATHINFO_FILENAME);
                $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $orig_name);
                if (empty($safe_name)) $safe_name = "icon";
                $filename = "custom_" . $safe_name . "_" . uniqid() . "." . $ext;

                if (move_uploaded_file($file["tmp_name"], $target_dir . $filename)) {
                    // Update manifest.json
                    $manifest_path = $target_dir . "manifest.json";
                    $manifest = file_exists($manifest_path) ? json_decode(file_get_contents($manifest_path), true) : [];
                    if (!is_array($manifest)) $manifest = [];
                    $manifest[$filename] = [
                        'name' => $orig_name,
                        'uploaded_at' => date('Y-m-d H:i:s'),
                        'size' => $file['size']
                    ];
                    file_put_contents($manifest_path, json_encode($manifest, JSON_PRETTY_PRINT));

                    $rel_path = "assets/images/badges/" . $filename;
                    $clean_label = '📁 ' . ucwords(str_replace(['_', '-'], ' ', $orig_name)) . ' (Custom)';

                    $svg_content = '';
                    if ($ext === 'svg') {
                        $raw_svg = file_get_contents($target_dir . $filename);
                        if (preg_match('/<svg[\s\S]*<\/svg>/i', $raw_svg, $matches)) {
                            $svg_content = $matches[0];
                            if (!str_contains($svg_content, 'viewBox') && preg_match('/width="([0-9.]+)"\s+height="([0-9.]+)"/i', $svg_content, $wh)) {
                                $svg_content = str_replace('<svg ', '<svg viewBox="0 0 ' . $wh[1] . ' ' . $wh[2] . '" ', $svg_content);
                            }
                        }
                    }
                    if (empty($svg_content)) {
                        $svg_content = '<img src="../' . htmlspecialchars($rel_path) . '" alt="' . htmlspecialchars($clean_label) . '" class="w-full h-full object-contain" />';
                    }

                    echo json_encode([
                        'success' => true,
                        'path' => $rel_path,
                        'label' => $clean_label,
                        'svg' => $svg_content
                    ]);
                    exit;
                }
            }
            echo json_encode(['success' => false, 'error' => 'Allowed formats: SVG, PNG, WebP, JPG.']);
            exit;
        }
        echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error occurred.']);
        exit;
    }

    // --- AJAX REMOVE / DELETE BADGE ICON FROM LIBRARY ---
    if (isset($_POST['action']) && in_array($_POST['action'], ['remove_badge_icon', 'delete_badge_icon'])) {
        header('Content-Type: application/json');
        $icon_key = trim($_POST['icon_key'] ?? '');
        if (empty($icon_key)) {
            echo json_encode(['success' => false, 'error' => 'No icon key provided.']);
            exit;
        }

        // Case 1: Custom uploaded icon (physical file deletion)
        if (str_starts_with($icon_key, 'assets/images/badges/custom_') || str_starts_with($icon_key, 'assets/images/badges/')) {
            $filename = basename($icon_key);
            $full_path = "../assets/images/badges/" . $filename;
            if (file_exists($full_path)) {
                @unlink($full_path);
            }
            $manifest_path = "../assets/images/badges/manifest.json";
            if (file_exists($manifest_path)) {
                $manifest = json_decode(file_get_contents($manifest_path), true);
                if (is_array($manifest) && isset($manifest[$filename])) {
                    unset($manifest[$filename]);
                    file_put_contents($manifest_path, json_encode($manifest, JSON_PRETTY_PRINT));
                }
            }
            echo json_encode(['success' => true, 'type' => 'custom', 'key' => $icon_key]);
            exit;
        }

        // Case 2: Built-in curated icon (hide from library via settings)
        $hidden_json = get_setting('hidden_badge_icons', '[]');
        $hidden_keys = json_decode($hidden_json, true) ?: [];
        if (!in_array($icon_key, $hidden_keys)) {
            $hidden_keys[] = $icon_key;
            update_setting('hidden_badge_icons', json_encode(array_values($hidden_keys)));
        }
        echo json_encode(['success' => true, 'type' => 'builtin', 'key' => $icon_key]);
        exit;
    }

    // --- AJAX RESTORE ALL HIDDEN BUILT-IN ICONS ---
    if (isset($_POST['action']) && $_POST['action'] === 'restore_hidden_icons') {
        header('Content-Type: application/json');
        update_setting('hidden_badge_icons', '[]');
        echo json_encode(['success' => true]);
        exit;
    }

    // --- SALE COUNTDOWN UPDATE ---
    if (isset($_POST['update_sale'])) {
        $title = sanitize_input($_POST['sale_title'] ?? '');
        $end_date = sanitize_input($_POST['sale_end'] ?? '');
        $is_active = isset($_POST['sale_active']) ? 1 : 0;

        if (empty($end_date)) $end_date = date('Y-m-d H:i:s', strtotime('+7 days'));

        $check = $conn->query("SELECT id FROM sale_countdowns LIMIT 1");
        if ($check->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE sale_countdowns SET title=?, end_date=?, is_active=? LIMIT 1");
        } else {
            $stmt = $conn->prepare("INSERT INTO sale_countdowns (title, end_date, is_active) VALUES (?, ?, ?)");
        }
        $stmt->bind_param("ssi", $title, $end_date, $is_active);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Flash sale countdown updated successfully!";
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
        $_SESSION['msg'] = "Top announcement marquee updated successfully!";
        $redirect = true;
    }

    // --- QUALITY PLEDGE BADGES MANAGEMENT ---
    if (isset($_POST['reset_default_badges'])) {
        seed_default_trust_badges();
        $_SESSION['msg'] = "Quality pledge badges reset to defaults!";
        $redirect = true;
    }

    if (isset($_POST['save_all_badges'])) {
        $submitted_badges = $_POST['badges'] ?? [];
        if (!empty($submitted_badges) && is_array($submitted_badges)) {
            $conn->query("DELETE FROM trust_badges");
            $stmt = $conn->prepare("INSERT INTO trust_badges (title, subtitle, icon, bg_color, icon_color, is_active, sort_order) VALUES (?, ?, ?, '', '', 1, ?)");
            
            $order = 1;
            foreach ($submitted_badges as $b) {
                $t1 = trim(sanitize_input($b['title'] ?? ''));
                $t2 = trim(sanitize_input($b['subtitle'] ?? ''));
                $icon = trim(sanitize_input($b['icon'] ?? 'made-in-india'));
                
                if (!empty($t1) || !empty($t2)) {
                    $stmt->bind_param("sssi", $t1, $t2, $icon, $order);
                    $stmt->execute();
                    $order++;
                }
            }
            $_SESSION['msg'] = "Quality pledge badges saved successfully!";
            $redirect = true;
        } else {
            $_SESSION['error'] = "No pledge badges submitted to save.";
            $redirect = true;
        }
    }

    // --- VIDEO SECTION UPDATE ---
    if (isset($_POST['update_video_section'])) {
        $heading = $_POST['heading'] ?? '';
        $subheading = $_POST['subheading'] ?? '';
        $video_url = $_POST['video_url'] ?? '';
        $media_url = $_POST['current_media'] ?? '';

        $old_data = fetch_one("SELECT media_url, video_url FROM homepage_sections WHERE section_name = 'video_brand_story'");

        $allowed_img_exts = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'];
        if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_img_exts)) {
                $target_dir = "../assets/images/uploads/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                $filename = "vid_thumb_" . uniqid() . "." . $ext;
                if (move_uploaded_file($_FILES["media"]["tmp_name"], $target_dir . $filename)) {
                    $media_url = "assets/images/uploads/" . $filename;
                    if ($old_data && !empty($old_data['media_url']) && strpos($old_data['media_url'], 'assets/images/uploads/') === 0) {
                        $old_path = "../" . $old_data['media_url'];
                        if (file_exists($old_path)) @unlink($old_path);
                    }
                }
            }
        }

        $allowed_vid_exts = ['mp4', 'webm', 'ogg', 'mov'];
        $video_uploaded = false;
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_vid_exts)) {
                $target_dir = "../assets/videos/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                $filename = "brand_v_" . uniqid() . "." . $ext;
                if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_dir . $filename)) {
                    $video_url = "assets/videos/" . $filename;
                    $video_uploaded = true;
                }
            }
        }

        if ($old_data && !empty($old_data['video_url']) && strpos($old_data['video_url'], 'assets/videos/') === 0) {
            if ($video_uploaded || (isset($_POST['video_url']) && $_POST['video_url'] !== $old_data['video_url'])) {
                $old_v_path = "../" . $old_data['video_url'];
                if (file_exists($old_v_path)) @unlink($old_v_path);
            }
        }

        $stmt = $conn->prepare("UPDATE homepage_sections SET heading = ?, subheading = ?, media_url = ?, video_url = ? WHERE section_name = 'video_brand_story'");
        $stmt->bind_param("ssss", $heading, $subheading, $media_url, $video_url);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Video brand story section updated!";
            $redirect = true;
        } else {
            $_SESSION['error'] = "Failed to update video brand story.";
        }
    }

    if ($redirect) {
        header("Location: manage_home.php?tab=" . urlencode($submitted_tab));
        exit;
    }
}

include 'includes/header.php';

$msg = $_SESSION['msg'] ?? "";
$error = $_SESSION['error'] ?? "";
unset($_SESSION['msg'], $_SESSION['error']);

$vid_sec = fetch_one("SELECT * FROM homepage_sections WHERE section_name = 'video_brand_story'");
$sale = fetch_one("SELECT * FROM sale_countdowns LIMIT 1");
$announcement_text = get_setting('announcement_text', '🚀 Free Shipping on All Orders Over ₹499 • 🌿 100% Organic & Natural');
$announcement_bg = get_setting('announcement_bg_color', '#004f42');
$trust_badges = fetch_all("SELECT * FROM trust_badges ORDER BY sort_order ASC");
$icon_lib = get_trust_badge_icon_library();
?>

<div class="space-y-6">
    <!-- Standard Clean Admin Header (Matching settings.php & products.php) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Homepage Content</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage quality trust badges, promotional sales, announcement marquee, and video media.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="../index.php" target="_blank" class="btn-admin btn-admin-secondary text-xs">
                <i class="fas fa-external-link-alt text-slate-400"></i> View Storefront
            </a>
            <a href="hero_slides.php" class="btn-admin btn-admin-secondary text-xs">
                <i class="fas fa-images text-slate-400"></i> Hero Slides
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if ($msg): ?>
        <div class="p-3.5 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 text-xs font-semibold flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times text-xs"></i></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-3.5 bg-rose-50 text-rose-800 rounded-xl border border-rose-200 text-xs font-semibold flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-circle text-rose-600 text-sm"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fas fa-times text-xs"></i></button>
        </div>
    <?php endif; ?>

    <!-- Standard Segmented Tab Navigation (Matching settings.php) -->
    <div class="flex items-center gap-1.5 p-1.5 bg-slate-100 rounded-2xl w-full sm:w-fit overflow-x-auto no-scrollbar border border-slate-200/80 shadow-xs" id="homeTabsNav">
        <button type="button" onclick="switchHomeTab('badges')" id="tabBtn-badges" class="home-tab-btn flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?php echo $active_tab === 'badges' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">
            <i class="fas fa-shield-alt text-emerald-600"></i>
            <span>Quality Badges</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black <?php echo $active_tab === 'badges' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'; ?>" id="navBadgesCount"><?php echo count($trust_badges); ?></span>
        </button>

        <button type="button" onclick="switchHomeTab('sale')" id="tabBtn-sale" class="home-tab-btn flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?php echo $active_tab === 'sale' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">
            <i class="fas fa-bolt text-amber-500"></i>
            <span>Flash Sale</span>
            <span class="w-2 h-2 rounded-full <?php echo !empty($sale['is_active']) ? 'bg-amber-500 animate-pulse' : 'bg-slate-300'; ?>"></span>
        </button>

        <button type="button" onclick="switchHomeTab('announcement')" id="tabBtn-announcement" class="home-tab-btn flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?php echo $active_tab === 'announcement' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">
            <i class="fas fa-bullhorn text-sky-500"></i>
            <span>Announcement Marquee</span>
        </button>

        <button type="button" onclick="switchHomeTab('video')" id="tabBtn-video" class="home-tab-btn flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?php echo $active_tab === 'video' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">
            <i class="fas fa-video text-indigo-500"></i>
            <span>Brand Story Video</span>
        </button>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: QUALITY PLEDGE BADGES -->
    <!-- ========================================== -->
    <div id="tabContent-badges" class="home-tab-pane space-y-6 <?php echo $active_tab === 'badges' ? '' : 'hidden'; ?>">
        
        <!-- Live Storefront Preview Box -->
        <div class="admin-card p-0 overflow-hidden">
            <div class="p-4 bg-slate-50/70 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Storefront Live Preview</h3>
                </div>
                <span class="text-[11px] font-medium text-slate-500">
                    Icon above &bull; Title below &bull; Updates in real-time
                </span>
            </div>

            <!-- Ticker Canvas (Matching Storefront Texture) -->
            <div class="p-6 md:p-8 relative overflow-hidden" style="background-color: #FAF8F5; background-image: radial-gradient(rgba(0, 135, 90, 0.12) 1.2px, transparent 1.2px); background-size: 18px 18px;">
                <div class="flex items-center justify-center gap-8 md:gap-14 overflow-x-auto py-2 no-scrollbar" id="liveTickerPreviewTrack">
                    <!-- Populated dynamically by JS -->
                </div>
            </div>
            
            <div class="px-5 py-2.5 bg-white border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                <span>Changes made below update this live preview immediately.</span>
                <span class="font-semibold text-slate-600 hidden sm:inline">No Card Borders &bull; Pure Icons & Typography</span>
            </div>
        </div>

        <!-- Badges Management Card -->
        <div class="admin-card p-0 overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-shield-alt text-emerald-600"></i>
                        Quality Badges (<span id="badgesCountBadge"><?php echo count($trust_badges); ?></span>)
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Click any icon preview to choose from the 27-icon library (including 0 to 6, official FSSAI, India map, leaves & smiles).</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" onclick="addNewBadgeCard()" class="btn-admin btn-admin-primary text-xs flex items-center gap-1.5">
                        <i class="fas fa-plus"></i> Add Badge
                    </button>
                    <form method="POST" onsubmit="return confirm('Reset all pledge badges to default?')" class="inline-block">
                        <input type="hidden" name="active_tab" value="badges">
                        <button type="submit" name="reset_default_badges" class="btn-admin btn-admin-secondary text-xs flex items-center gap-1.5">
                            <i class="fas fa-rotate-left text-slate-400"></i> Reset Defaults
                        </button>
                    </form>
                </div>
            </div>

            <!-- Badges Form -->
            <form method="POST" id="pledge-badges-form" class="p-5 sm:p-6 space-y-6">
                <input type="hidden" name="save_all_badges" value="1">
                <input type="hidden" name="active_tab" value="badges">
                
                <!-- Clean 2-Column Responsive Card Grid -->
                <div id="badge-cards-list" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <?php 
                    foreach ($trust_badges as $idx => $badge): 
                        $card_num = $idx + 1;
                        $current_icon = $badge['icon'];
                    ?>
                    <div class="badge-card-row bg-white border border-slate-200/90 hover:border-emerald-500/80 rounded-2xl p-4 shadow-2xs hover:shadow-xs transition-all duration-200 flex flex-col justify-between group">
                        
                        <!-- Top Bar: Badge Number & Reorder Actions -->
                        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200/80">
                                <i class="fas fa-shield-alt text-[10px] text-emerald-600"></i> BADGE #<span class="badge-num-display"><?php echo $card_num; ?></span>
                            </span>
                            <div class="flex items-center gap-1">
                                <button type="button" onclick="moveBadgeCardUp(this)" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="Move Up">
                                    <i class="fas fa-chevron-up text-xs"></i>
                                </button>
                                <button type="button" onclick="moveBadgeCardDown(this)" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="Move Down">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </button>
                                <button type="button" onclick="removeBadgeCard(this)" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition ml-1" title="Delete Badge">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Card Body: Clean Horizontal Split -->
                        <div class="flex items-center gap-4">
                            <!-- Left: Interactive Icon Preview Avatar -->
                            <div class="badge-preview-box w-18 h-18 rounded-2xl bg-[#FAF8F5] border border-stone-200 flex flex-col items-center justify-center p-2 text-emerald-700 shrink-0 shadow-2xs cursor-pointer hover:border-emerald-500 hover:scale-105 transition group/icon" onclick="openIconPickerForCard(this)" title="Click to browse all vector icons">
                                <div class="w-12 h-12 flex items-center justify-center">
                                    <?php echo render_trust_badge_icon($current_icon, 'w-11 h-11'); ?>
                                </div>
                                <span class="text-[9px] font-bold text-emerald-700 tracking-tight mt-0.5 group-hover/icon:text-emerald-900 flex items-center gap-0.5">
                                    <i class="fas fa-pen text-[7px]"></i> Change
                                </span>
                            </div>

                            <!-- Right: Clean Inputs -->
                            <div class="flex-1 space-y-2 min-w-0">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Vector Icon</label>
                                        <button type="button" onclick="openIconPickerForCard(this)" class="text-[10px] font-bold text-emerald-600 hover:text-emerald-800 flex items-center gap-1">
                                            <i class="fas fa-th text-[9px]"></i> Icon Gallery
                                        </button>
                                    </div>
                                    <select name="badges[<?php echo $idx; ?>][icon]" class="admin-input text-xs font-semibold bg-white cursor-pointer py-1.5" onchange="updateBadgeCardPreview(this)">
                                        <?php foreach($icon_lib as $key => $info): ?>
                                            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $current_icon === $key ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($info['label']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if (!isset($icon_lib[$current_icon]) && !empty($current_icon)): ?>
                                            <option value="<?php echo htmlspecialchars($current_icon); ?>" selected>
                                                Custom: <?php echo htmlspecialchars($current_icon); ?>
                                            </option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">Line 1 Text *</label>
                                        <input type="text" name="badges[<?php echo $idx; ?>][title]" value="<?php echo htmlspecialchars($badge['title']); ?>" class="admin-input text-xs font-bold uppercase py-1.5" required placeholder="e.g. ZERO" oninput="handleBadgeInput(this)">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">Line 2 Text</label>
                                        <input type="text" name="badges[<?php echo $idx; ?>][subtitle]" value="<?php echo htmlspecialchars($badge['subtitle']); ?>" class="admin-input text-xs font-bold uppercase py-1.5" placeholder="e.g. PRESERVATIVES" oninput="handleBadgeInput(this)">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Bottom Save Actions Bar -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-slate-400 font-medium"><i class="fas fa-info-circle mr-1"></i> Changes will immediately appear in the homepage ticker after saving.</span>
                    <button type="submit" name="save_all_badges" class="w-full sm:w-auto btn-admin btn-admin-primary text-xs py-2 px-6 font-bold shadow-sm">
                        <i class="fas fa-save mr-1.5"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Badge Card Template for JS Injection -->
    <template id="badge-card-template">
        <div class="badge-card-row bg-white border border-slate-200/90 hover:border-emerald-500/80 rounded-2xl p-4 shadow-2xs hover:shadow-xs transition-all duration-200 flex flex-col justify-between group">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200/80">
                    <i class="fas fa-shield-alt text-[10px] text-emerald-600"></i> BADGE #<span class="badge-num-display">1</span>
                </span>
                <div class="flex items-center gap-1">
                    <button type="button" onclick="moveBadgeCardUp(this)" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="Move Up">
                        <i class="fas fa-chevron-up text-xs"></i>
                    </button>
                    <button type="button" onclick="moveBadgeCardDown(this)" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="Move Down">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <button type="button" onclick="removeBadgeCard(this)" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition ml-1" title="Delete Badge">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="badge-preview-box w-18 h-18 rounded-2xl bg-[#FAF8F5] border border-stone-200 flex flex-col items-center justify-center p-2 text-emerald-700 shrink-0 shadow-2xs cursor-pointer hover:border-emerald-500 hover:scale-105 transition group/icon" onclick="openIconPickerForCard(this)" title="Click to browse all vector icons">
                    <div class="w-12 h-12 flex items-center justify-center"></div>
                    <span class="text-[9px] font-bold text-emerald-700 tracking-tight mt-0.5 group-hover/icon:text-emerald-900 flex items-center gap-0.5">
                        <i class="fas fa-pen text-[7px]"></i> Change
                    </span>
                </div>

                <div class="flex-1 space-y-2 min-w-0">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Vector Icon</label>
                            <button type="button" onclick="openIconPickerForCard(this)" class="text-[10px] font-bold text-emerald-600 hover:text-emerald-800 flex items-center gap-1">
                                <i class="fas fa-th text-[9px]"></i> Icon Gallery
                            </button>
                        </div>
                        <select name="badges[__INDEX__][icon]" class="admin-input text-xs font-semibold bg-white cursor-pointer py-1.5" onchange="updateBadgeCardPreview(this)">
                            <?php foreach($icon_lib as $key => $info): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>">
                                    <?php echo htmlspecialchars($info['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">Line 1 Text *</label>
                            <input type="text" name="badges[__INDEX__][title]" class="admin-input text-xs font-bold uppercase py-1.5" required placeholder="e.g. 100%" oninput="handleBadgeInput(this)">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">Line 2 Text</label>
                            <input type="text" name="badges[__INDEX__][subtitle]" class="admin-input text-xs font-bold uppercase py-1.5" placeholder="e.g. NATURAL" oninput="handleBadgeInput(this)">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- ========================================== -->
    <!-- TAB 2: PROMOTIONAL FLASH SALE -->
    <!-- ========================================== -->
    <div id="tabContent-sale" class="home-tab-pane space-y-6 <?php echo $active_tab === 'sale' ? '' : 'hidden'; ?>">
        <div class="admin-card p-5 sm:p-6">
            <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-bolt text-amber-500"></i> Promotional Flash Sale
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Configure countdown timer banner displayed on the storefront.</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?php echo ($sale['is_active'] ?? 0) ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-50 text-slate-600 border border-slate-200'; ?>">
                    <span class="w-2 h-2 rounded-full <?php echo ($sale['is_active'] ?? 0) ? 'bg-amber-500 animate-pulse' : 'bg-slate-400'; ?>"></span>
                    <?php echo ($sale['is_active'] ?? 0) ? 'Active on Store' : 'Inactive'; ?>
                </span>
            </div>

            <form method="POST" class="space-y-5">
                <input type="hidden" name="update_sale" value="1">
                <input type="hidden" name="active_tab" value="sale">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sale Headline Banner *</label>
                            <input type="text" name="sale_title" id="sale_title_input" value="<?php echo htmlspecialchars($sale['title'] ?? ''); ?>" placeholder="e.g. HARVEST SALE - 20% OFF ALL DRIED FRUITS" class="admin-input text-xs" required oninput="document.getElementById('salePreviewTitle').innerText = this.value || 'FESTIVE SALE'">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-slate-700">Countdown End Date & Time *</label>
                                <div class="flex items-center gap-1 text-[10px]">
                                    <button type="button" onclick="setSaleDateOffset(1)" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-600">+1 Day</button>
                                    <button type="button" onclick="setSaleDateOffset(3)" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-600">+3 Days</button>
                                    <button type="button" onclick="setSaleDateOffset(7)" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-600">+7 Days</button>
                                </div>
                            </div>
                            <input type="datetime-local" name="sale_end" id="sale_end_input" value="<?php echo isset($sale['end_date']) ? date('Y-m-d\TH:i', strtotime($sale['end_date'])) : ''; ?>" class="admin-input text-xs" required>
                        </div>

                        <div class="pt-1">
                            <label class="relative flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="sale_active" value="1" <?php echo ($sale['is_active'] ?? 0) ? 'checked' : ''; ?> class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs font-semibold text-slate-700">Display Sale Banner on Homepage</span>
                            </label>
                        </div>
                    </div>

                    <!-- Live Countdown Widget Preview -->
                    <div class="rounded-2xl border border-amber-200/80 bg-gradient-to-br from-amber-50/50 to-orange-50/30 p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800 bg-amber-100 px-2.5 py-0.5 rounded">Live Banner Preview</span>
                                <span class="text-xs text-amber-600 font-medium">Ticking</span>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900 mt-3 truncate" id="salePreviewTitle">
                                <?php echo !empty($sale['title']) ? htmlspecialchars($sale['title']) : 'FESTIVE HARVEST SALE - 20% OFF'; ?>
                            </h4>
                            <div class="grid grid-cols-4 gap-2 mt-4 text-center">
                                <div class="bg-white border border-amber-200/70 rounded-xl p-2.5 shadow-2xs">
                                    <span class="text-lg font-bold text-slate-900 block" id="cdDays">03</span>
                                    <span class="text-[10px] text-slate-400 uppercase">Days</span>
                                </div>
                                <div class="bg-white border border-amber-200/70 rounded-xl p-2.5 shadow-2xs">
                                    <span class="text-lg font-bold text-slate-900 block" id="cdHours">14</span>
                                    <span class="text-[10px] text-slate-400 uppercase">Hours</span>
                                </div>
                                <div class="bg-white border border-amber-200/70 rounded-xl p-2.5 shadow-2xs">
                                    <span class="text-lg font-bold text-slate-900 block" id="cdMins">45</span>
                                    <span class="text-[10px] text-slate-400 uppercase">Mins</span>
                                </div>
                                <div class="bg-white border border-amber-200/70 rounded-xl p-2.5 shadow-2xs">
                                    <span class="text-lg font-bold text-amber-600 block" id="cdSecs">20</span>
                                    <span class="text-[10px] text-slate-400 uppercase">Secs</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-amber-200/60 text-xs text-amber-800">
                            Target End: <span class="font-semibold"><?php echo !empty($sale['end_date']) ? date('M j, Y - g:i A', strtotime($sale['end_date'])) : 'Not set'; ?></span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit" class="btn-admin btn-admin-primary text-xs py-2 px-5">
                        <i class="fas fa-save mr-1.5"></i> Save Sale Timer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 3: TOP ANNOUNCEMENT MARQUEE -->
    <!-- ========================================== -->
    <div id="tabContent-announcement" class="home-tab-pane space-y-6 <?php echo $active_tab === 'announcement' ? '' : 'hidden'; ?>">
        <div class="admin-card p-5 sm:p-6">
            <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-bullhorn text-sky-500"></i> Header Announcement Marquee
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Customize the scrolling ticker bar pinned at the very top of the storefront header.</p>
                </div>
            </div>

            <!-- Live Banner Preview Canvas -->
            <div class="mb-5 rounded-xl overflow-hidden border border-slate-200 shadow-2xs">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50 px-3 py-1.5 border-b border-slate-200 flex items-center justify-between">
                    <span>Live Header Preview</span>
                    <span>Pinned Website Header</span>
                </div>
                <div id="liveAnnouncementBar" class="py-2.5 px-4 text-center font-bold text-xs tracking-wide transition-colors text-white" style="background-color: <?php echo htmlspecialchars($announcement_bg); ?>;">
                    <span id="liveAnnouncementText"><?php echo htmlspecialchars($announcement_text); ?></span>
                </div>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="update_announcement" value="1">
                <input type="hidden" name="active_tab" value="announcement">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Marquee Announcement Text *</label>
                    <textarea name="announcement_text" id="ann_text_input" rows="3" class="admin-input text-xs resize-none" placeholder="Enter scrolling announcement text..." oninput="document.getElementById('liveAnnouncementText').innerText = this.value || 'Announcement text preview'"><?php echo htmlspecialchars($announcement_text); ?></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">Separate multiple announcements with <span class="font-bold text-slate-700">" • "</span> bullet symbols to produce a clean loop.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bar Background Color</label>
                    <div class="flex items-center gap-4 flex-wrap">
                        <div class="flex items-center gap-2.5">
                            <input type="color" name="announcement_bg_color" id="ann_bg_input" value="<?php echo $announcement_bg; ?>" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5" oninput="updateAnnouncementBg(this.value)">
                            <code id="ann_bg_hex" class="text-xs font-mono font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded border border-slate-200"><?php echo strtoupper($announcement_bg); ?></code>
                        </div>

                        <!-- Presets -->
                        <div class="flex items-center gap-1.5 border-l border-slate-200 pl-3">
                            <span class="text-xs text-slate-400">Presets:</span>
                            <button type="button" onclick="setAnnColor('#004f42')" class="w-6 h-6 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition" style="background-color: #004f42;" title="Deep Emerald"></button>
                            <button type="button" onclick="setAnnColor('#00875A')" class="w-6 h-6 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition" style="background-color: #00875A;" title="Vibrant Green"></button>
                            <button type="button" onclick="setAnnColor('#0A382C')" class="w-6 h-6 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition" style="background-color: #0A382C;" title="Forest"></button>
                            <button type="button" onclick="setAnnColor('#D97706')" class="w-6 h-6 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition" style="background-color: #D97706;" title="Golden Amber"></button>
                            <button type="button" onclick="setAnnColor('#0F172A')" class="w-6 h-6 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition" style="background-color: #0F172A;" title="Midnight Dark"></button>
                        </div>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit" class="btn-admin btn-admin-primary text-xs py-2 px-5">
                        <i class="fas fa-save mr-1.5"></i> Update Marquee
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 4: BRAND STORY VIDEO -->
    <!-- ========================================== -->
    <div id="tabContent-video" class="home-tab-pane space-y-6 <?php echo $active_tab === 'video' ? '' : 'hidden'; ?>">
        <div class="admin-card p-5 sm:p-6">
            <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-video text-indigo-500"></i> Video Brand Story Section
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Manage your artisanal valley harvesting story, craft videos, or cinematic brand film.</p>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="update_video_section" value="1">
                <input type="hidden" name="active_tab" value="video">
                <input type="hidden" name="current_media" value="<?php echo htmlspecialchars($vid_sec['media_url'] ?? ''); ?>">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Section Title *</label>
                            <input type="text" name="heading" value="<?php echo htmlspecialchars($vid_sec['heading'] ?? ''); ?>" class="admin-input text-xs" placeholder="e.g. Pure Craft, Valley Freshness">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Subheading Description</label>
                            <textarea name="subheading" rows="3" class="admin-input text-xs resize-none" placeholder="Provide a compelling story behind DRIYUM's craft..."><?php echo htmlspecialchars($vid_sec['subheading'] ?? ''); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">External Video URL (YouTube / Vimeo)</label>
                            <input type="text" name="video_url" value="<?php echo htmlspecialchars($vid_sec['video_url'] ?? ''); ?>" class="admin-input text-xs" placeholder="https://youtube.com/watch?v=...">
                            <p class="text-[10px] text-slate-400 mt-1">Clicking play will launch the high-resolution external video player modal.</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Thumbnail Cover & Video File</label>
                        <div class="aspect-video rounded-xl overflow-hidden border border-slate-200 bg-slate-900 relative group flex items-center justify-center">
                            <img id="vid-cover-preview" src="../<?php echo !empty($vid_sec['media_url']) ? $vid_sec['media_url'] : 'assets/images/hero.jpg'; ?>" class="w-full h-full object-cover">
                            <video id="vid-file-preview" class="absolute inset-0 w-full h-full object-cover hidden" autoplay muted loop></video>
                            
                            <div class="absolute inset-0 bg-slate-900/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <label class="btn-admin btn-admin-secondary text-xs cursor-pointer py-1.5 px-3">
                                    <i class="fas fa-image mr-1"></i> Cover Photo
                                    <input type="file" name="media" id="vid-cover-input" class="hidden" accept="image/*" onchange="previewMedia(this, 'vid-cover-preview')">
                                </label>
                                <label class="btn-admin btn-admin-secondary text-xs cursor-pointer py-1.5 px-3">
                                    <i class="fas fa-film mr-1"></i> MP4 Video
                                    <input type="file" name="video_file" id="vid-file-input" class="hidden" accept="video/*" onchange="previewMedia(this, 'vid-file-preview')">
                                </label>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-400">Hover over the preview above to change cover photo or upload an MP4 video file.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-3 border-t border-slate-100">
                    <button type="submit" name="update_video_section" class="btn-admin btn-admin-primary text-xs py-2 px-5">
                        <i class="fas fa-save mr-1.5"></i> Save Brand Story
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- ICON LIBRARY & CUSTOM UPLOAD MODAL -->
<!-- ========================================== -->
<div id="iconPickerModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden animate-scale-in">
        <!-- Modal Header -->
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-icons text-emerald-600"></i> Select Badge Icon
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Choose from curated industry vector icons or upload a custom SVG/PNG file.</p>
            </div>
            <button type="button" onclick="closeIconPickerModal()" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <?php 
        $custom_icons_count = 0;
        foreach($icon_lib as $k => $info) {
            if (!empty($info['is_custom'])) $custom_icons_count++;
        }
        ?>
        <!-- Modal Sub-Tabs -->
        <div class="flex items-center gap-2 p-2.5 bg-slate-100 border-b border-slate-200">
            <button type="button" onclick="switchIconModalTab('library')" id="iconModalTabBtn-library" class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition bg-white text-slate-900 shadow-xs border border-slate-200/80">
                <i class="fas fa-th text-emerald-600 mr-1.5"></i> Icon Library (<span id="modalIconTotal"><?php echo count($icon_lib); ?></span>)
            </button>
            <button type="button" onclick="switchIconModalTab('upload')" id="iconModalTabBtn-upload" class="flex-1 py-1.5 px-3 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-white/60 transition">
                <i class="fas fa-cloud-upload-alt text-sky-500 mr-1.5"></i> Upload Custom Icon / SVG (<span id="modalCustomTabCount"><?php echo $custom_icons_count; ?></span>)
            </button>
        </div>

        <!-- TAB 1: CURATED & CUSTOM VECTOR ICONS -->
        <div id="iconModalPane-library" class="flex-1 flex flex-col min-h-0">
            <!-- Filter Search Bar -->
            <div class="p-3 border-b border-slate-100 bg-white">
                <div class="relative mb-2">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="iconSearchInput" oninput="filterIconPicker(this.value)" placeholder="Search icons (e.g. custom, 0, 1, 6, fssai, india, leaf, smile, sugar, palm...)" class="admin-input text-xs pl-8 pr-3 py-1.5 w-full">
                </div>
                <!-- Category Filter Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5 text-[11px] font-semibold text-slate-600" id="iconCategoryPills">
                    <button type="button" onclick="filterByCategory('all', this)" class="icon-cat-btn px-2.5 py-0.5 rounded-full bg-emerald-600 text-white font-bold transition">All (<span id="catTotalAll"><?php echo count($icon_lib); ?></span>)</button>
                    <button type="button" onclick="filterByCategory('custom', this)" class="icon-cat-btn px-2.5 py-0.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 transition">📁 Custom (<span id="catTotalCustom"><?php echo $custom_icons_count; ?></span>)</button>
                    <button type="button" onclick="filterByCategory('numbers', this)" class="icon-cat-btn px-2.5 py-0.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 transition">🔢 Numbers 0-6</button>
                    <button type="button" onclick="filterByCategory('india', this)" class="icon-cat-btn px-2.5 py-0.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 transition">🇮🇳 India & Origin</button>
                    <button type="button" onclick="filterByCategory('nature', this)" class="icon-cat-btn px-2.5 py-0.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 transition">🌿 Nature & Organic</button>
                    <button type="button" onclick="filterByCategory('quality', this)" class="icon-cat-btn px-2.5 py-0.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 transition">🛡️ Quality & Trust</button>
                </div>
            </div>

            <?php 
            $hidden_keys_list = json_decode(get_setting('hidden_badge_icons', '[]'), true) ?: [];
            $hidden_count = count($hidden_keys_list);
            ?>
            <!-- Hidden Icons Restore Banner -->
            <div id="hiddenIconsRestoreBar" class="<?php echo $hidden_count > 0 ? '' : 'hidden'; ?> mx-3 mt-2.5 p-2 rounded-xl bg-amber-50 border border-amber-200/90 flex items-center justify-between text-xs text-amber-900 shadow-2xs">
                <span class="flex items-center gap-1.5 font-medium">
                    <i class="fas fa-eye-slash text-amber-600"></i>
                    <span><strong id="hiddenIconsCount"><?php echo $hidden_count; ?></strong> built-in icon(s) removed from library</span>
                </span>
                <button type="button" onclick="restoreHiddenIcons()" class="text-[11px] font-bold text-amber-900 hover:text-amber-950 bg-amber-200/80 hover:bg-amber-300 py-1 px-3 rounded-lg transition flex items-center gap-1 cursor-pointer">
                    <i class="fas fa-undo text-[9px]"></i> Restore All
                </button>
            </div>

            <!-- Icon Grid -->
            <div class="p-4 overflow-y-auto grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 flex-1" id="iconPickerGrid">
                <?php foreach($icon_lib as $key => $info): 
                    $is_custom = !empty($info['is_custom']);
                    $cat = 'quality';
                    if ($is_custom) {
                        $cat = 'custom';
                    } elseif (str_starts_with($key, 'num-') || $key === 'quality-check') {
                        $cat = 'numbers';
                    } elseif (in_array($key, ['made-in-india', 'kashmir'])) {
                        $cat = 'india';
                    } elseif (in_array($key, ['organic', 'leaf', 'twin-leaves', 'sun-dried'])) {
                        $cat = 'nature';
                    }
                ?>
                <div class="icon-picker-choice relative flex flex-col items-center justify-between p-3 rounded-xl border <?php echo $is_custom ? 'border-amber-300 bg-amber-50/20 hover:border-amber-500 hover:bg-amber-50/40' : 'border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/30'; ?> text-center transition group shadow-2xs hover:shadow-xs" data-key="<?php echo htmlspecialchars($key); ?>" data-label="<?php echo htmlspecialchars(strtolower($info['label'])); ?>" data-category="<?php echo $cat; ?>" data-is-custom="<?php echo $is_custom ? '1' : '0'; ?>">
                    <!-- Top Action Bar -->
                    <div class="w-full flex items-center justify-between mb-1 z-10">
                        <?php if ($is_custom): ?>
                        <span class="px-1.5 py-0.5 rounded text-[7px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200">Custom</span>
                        <?php else: ?>
                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-wider"><?php echo $cat; ?></span>
                        <?php endif; ?>
                        
                        <!-- Remove Icon from Library Button -->
                        <button type="button" onclick="removeIconFromLibrary(event, '<?php echo htmlspecialchars($key); ?>', <?php echo $is_custom ? 'true' : 'false'; ?>)" title="<?php echo $is_custom ? 'Delete custom icon from library' : 'Remove icon from library'; ?>" class="w-6 h-6 rounded-md text-slate-300 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition cursor-pointer">
                            <i class="fas fa-trash-alt text-[10px]"></i>
                        </button>
                    </div>

                    <!-- Center Icon Preview -->
                    <div class="w-12 h-12 flex items-center justify-center mb-1 text-emerald-700 group-hover:scale-110 transition-transform cursor-pointer" onclick="selectIconForCurrentCard('<?php echo htmlspecialchars($key); ?>')" title="Click to use icon">
                        <?php echo render_trust_badge_icon($key, 'w-10 h-10'); ?>
                    </div>

                    <!-- Label -->
                    <span class="text-[10px] font-bold text-slate-700 leading-tight group-hover:text-emerald-700 transition-colors line-clamp-2 cursor-pointer mb-2 w-full" onclick="selectIconForCurrentCard('<?php echo htmlspecialchars($key); ?>')">
                        <?php echo htmlspecialchars($info['label']); ?>
                    </span>

                    <!-- Quick Select Button -->
                    <button type="button" onclick="selectIconForCurrentCard('<?php echo htmlspecialchars($key); ?>')" class="w-full py-1 text-[9px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-600 hover:text-white rounded-lg transition border border-emerald-200/80 cursor-pointer">
                        Use Icon
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TAB 2: UPLOAD CUSTOM ICON / SVG -->
        <div id="iconModalPane-upload" class="p-6 flex flex-col space-y-5 hidden flex-1 overflow-y-auto">
            <div class="w-full max-w-lg mx-auto p-6 border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl bg-slate-50/50 hover:bg-emerald-50/20 text-center transition cursor-pointer" onclick="document.getElementById('customBadgeIconFileInput').click()">
                <div class="w-14 h-14 mx-auto rounded-full bg-emerald-100/80 text-emerald-600 flex items-center justify-center text-xl mb-3">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h4 class="text-xs font-bold text-slate-800">Click to upload custom icon or SVG</h4>
                <p class="text-[11px] text-slate-500 mt-1">Supports vector .SVG, transparent .PNG, or .WebP</p>
                <p class="text-[10px] text-emerald-600 font-semibold mt-1.5"><i class="fas fa-check-circle mr-1"></i> Uploaded icons are permanently saved and instantly added to your library for all cards.</p>
                <input type="file" id="customBadgeIconFileInput" accept=".svg,.png,.webp,.jpg,.jpeg" class="hidden" onchange="handleCustomIconFileUpload(this)">
            </div>
            <div id="iconUploadStatus" class="text-xs font-semibold text-center text-slate-500"></div>

            <!-- Custom Icons List in Upload Tab -->
            <div class="border-t border-slate-100 pt-4">
                <div class="flex items-center justify-between mb-3">
                    <h5 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-folder-open text-amber-500"></i> Saved Custom Icons in Library (<span id="uploadPaneCustomCount"><?php echo $custom_icons_count; ?></span>)
                    </h5>
                    <span class="text-[10px] text-slate-400">Click any icon to use on active card</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5" id="uploadTabCustomGrid">
                    <?php 
                    $has_custom = false;
                    foreach($icon_lib as $key => $info): 
                        if (empty($info['is_custom'])) continue;
                        $has_custom = true;
                    ?>
                    <div class="p-2.5 rounded-xl border border-amber-200 bg-amber-50/30 flex flex-col items-center justify-between text-center group relative hover:border-amber-400 transition" id="custom-item-<?php echo md5($key); ?>">
                        <span onclick="deleteCustomBadgeIcon(event, '<?php echo htmlspecialchars($key); ?>')" title="Delete from library" class="absolute top-1 right-1 w-5 h-5 rounded text-slate-300 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition cursor-pointer">
                            <i class="fas fa-trash-alt text-[8px]"></i>
                        </span>
                        <div class="w-10 h-10 flex items-center justify-center my-1 cursor-pointer" onclick="selectIconForCurrentCard('<?php echo htmlspecialchars($key); ?>')">
                            <?php echo render_trust_badge_icon($key, 'w-9 h-9'); ?>
                        </div>
                        <span class="text-[9px] font-bold text-slate-700 truncate w-full cursor-pointer" onclick="selectIconForCurrentCard('<?php echo htmlspecialchars($key); ?>')">
                            <?php echo htmlspecialchars(str_replace(['📁 ', ' (Custom)'], '', $info['label'])); ?>
                        </span>
                        <button type="button" onclick="selectIconForCurrentCard('<?php echo htmlspecialchars($key); ?>')" class="mt-1.5 text-[8px] font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-100 hover:bg-emerald-200 px-2 py-0.5 rounded transition">
                            Use Icon
                        </button>
                    </div>
                    <?php endforeach; ?>
                    <?php if (!$has_custom): ?>
                    <div class="col-span-full py-6 text-center text-xs text-slate-400" id="noCustomIconsMsg">
                        No custom icons uploaded yet. Upload your first SVG or PNG icon above!
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-3 border-t border-slate-100 bg-slate-50 flex items-center justify-between text-xs text-slate-500">
            <span>Free, crisp vector rendering &bull; Zero external latency</span>
            <button type="button" onclick="closeIconPickerModal()" class="btn-admin btn-admin-secondary text-xs py-1 px-3">Close</button>
        </div>
    </div>
</div>

<script>
// Icon Library Reference for live updates
const ICON_LIBRARY = <?php echo json_encode(get_trust_badge_icon_library()); ?>;

function getIconSvgHtml(iconKey, sizeClass = 'w-10 h-10') {
    if (!iconKey) return `<i class="fas fa-shield-alt text-2xl text-[#00875A]"></i>`;
    if (iconKey === 'no-preservatives') iconKey = 'zero-preservatives';
    if (iconKey === 'secure') iconKey = 'secure-checkout';
    if (iconKey === 'num-6') iconKey = 'quality-check';

    // Handle file path (custom upload)
    if (iconKey.startsWith('assets/') || iconKey.startsWith('uploads/') || /\.(svg|png|webp|jpg|jpeg)$/i.test(iconKey)) {
        if (ICON_LIBRARY[iconKey] && ICON_LIBRARY[iconKey].svg && (ICON_LIBRARY[iconKey].svg.startsWith('<svg') || ICON_LIBRARY[iconKey].svg.includes('</svg>'))) {
            return ICON_LIBRARY[iconKey].svg.replace('<svg ', `<svg class="${sizeClass}" `);
        }
        const src = (iconKey.startsWith('http') ? iconKey : '../' + iconKey.replace(/^\/+/, ''));
        return `<img src="${src}" alt="badge" class="${sizeClass} object-contain" />`;
    }

    // Handle inline SVG
    if (iconKey.startsWith('<svg') || iconKey.includes('</svg>')) {
        return iconKey.replace('<svg ', `<svg class="${sizeClass}" `);
    }

    // Handle curated library
    if (ICON_LIBRARY[iconKey] && ICON_LIBRARY[iconKey].svg) {
        return ICON_LIBRARY[iconKey].svg.replace('<svg ', `<svg class="${sizeClass}" `);
    }

    if (iconKey.includes('fa-') || iconKey.includes('fas ') || iconKey.includes('far ')) {
        return `<i class="${iconKey} text-2xl text-[#00875A]"></i>`;
    }
    return `<i class="fas fa-shield-alt text-2xl text-[#00875A]"></i>`;
}

// Tab Switching
function switchHomeTab(tabId) {
    document.querySelectorAll('.home-tab-pane').forEach(el => el.classList.add('hidden'));
    const targetPane = document.getElementById('tabContent-' + tabId);
    if (targetPane) targetPane.classList.remove('hidden');

    document.querySelectorAll('.home-tab-btn').forEach(btn => {
        btn.classList.remove('bg-white', 'text-slate-900', 'shadow-sm', 'border', 'border-slate-200/60');
        btn.classList.add('text-slate-600', 'hover:text-slate-900', 'hover:bg-white/50');
    });

    const activeBtn = document.getElementById('tabBtn-' + tabId);
    if (activeBtn) {
        activeBtn.classList.remove('text-slate-600', 'hover:text-slate-900', 'hover:bg-white/50');
        activeBtn.classList.add('bg-white', 'text-slate-900', 'shadow-sm', 'border', 'border-slate-200/60');
    }

    // Update active tab hidden inputs
    document.querySelectorAll('input[name="active_tab"]').forEach(input => input.value = tabId);

    // Update URL hash
    if (history.pushState) {
        history.pushState(null, null, '?tab=' + encodeURIComponent(tabId));
    }
}

// Live Top Storefront Preview Ticker Sync
function syncStorefrontLivePreview() {
    const track = document.getElementById('liveTickerPreviewTrack');
    if (!track) return;
    track.innerHTML = '';

    const cards = document.querySelectorAll('#badge-cards-list .badge-card-row');
    cards.forEach(card => {
        const title = card.querySelector('input[name*="[title]"]')?.value.trim() || '';
        const subtitle = card.querySelector('input[name*="[subtitle]"]')?.value.trim() || '';
        const iconSelect = card.querySelector('select[name*="[icon]"]');
        const iconKey = iconSelect ? iconSelect.value : 'zero-preservatives';

        const item = document.createElement('div');
        item.className = 'flex flex-col items-center justify-center text-center shrink-0 w-[140px] md:w-[160px] select-none';
        item.innerHTML = `
            <div class="w-14 h-14 md:w-16 md:h-16 flex items-center justify-center mb-2.5">
                ${getIconSvgHtml(iconKey, 'w-12 h-12 md:w-14 md:h-14')}
            </div>
            <div class="flex flex-col items-center text-center justify-center">
                <span class="text-xs md:text-[13px] font-black uppercase text-[#1C1917] tracking-wider leading-tight">
                    ${escapeHtml(title)}
                </span>
                ${subtitle ? `
                <span class="text-xs md:text-[13px] font-black uppercase text-[#1C1917] tracking-wider leading-tight mt-0.5">
                    ${escapeHtml(subtitle)}
                </span>` : ''}
            </div>
        `;
        track.appendChild(item);
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}

function updateBadgeCardPreview(selectEl) {
    const card = selectEl.closest('.badge-card-row');
    if (!card) return;
    const previewBox = card.querySelector('.badge-preview-box');
    if (previewBox) {
        const iconWrapper = previewBox.querySelector('div') || previewBox;
        iconWrapper.innerHTML = getIconSvgHtml(selectEl.value, 'w-11 h-11');
    }

    // Auto-fill suggested titles if line 1 is empty
    const line1 = card.querySelector('input[name*="[title]"]');
    const line2 = card.querySelector('input[name*="[subtitle]"]');
    if (line1 && line1.value.trim() === '' && ICON_LIBRARY[selectEl.value]) {
        line1.value = ICON_LIBRARY[selectEl.value].default_line1 || '';
        if (line2) line2.value = ICON_LIBRARY[selectEl.value].default_line2 || '';
    }

    syncStorefrontLivePreview();
}

function handleBadgeInput(inputEl) {
    syncStorefrontLivePreview();
}

function reindexBadgeCards() {
    const cards = document.querySelectorAll('#badge-cards-list .badge-card-row');
    const count = cards.length;

    const badgesCountBadge = document.getElementById('badgesCountBadge');
    if (badgesCountBadge) badgesCountBadge.innerText = count;

    const navBadgesCount = document.getElementById('navBadgesCount');
    if (navBadgesCount) navBadgesCount.innerText = count;

    cards.forEach((card, idx) => {
        const numDisplay = card.querySelector('.badge-num-display');
        if (numDisplay) numDisplay.innerText = idx + 1;

        const titleInput = card.querySelector('input[name*="[title]"]');
        const subtitleInput = card.querySelector('input[name*="[subtitle]"]');
        const iconSelect = card.querySelector('select[name*="[icon]"]');

        if (titleInput) titleInput.name = `badges[${idx}][title]`;
        if (subtitleInput) subtitleInput.name = `badges[${idx}][subtitle]`;
        if (iconSelect) iconSelect.name = `badges[${idx}][icon]`;
    });

    syncStorefrontLivePreview();
}

function moveBadgeCardUp(btn) {
    const card = btn.closest('.badge-card-row');
    if (!card || !card.previousElementSibling) return;
    card.parentNode.insertBefore(card, card.previousElementSibling);
    reindexBadgeCards();
    card.classList.add('ring-2', 'ring-emerald-400');
    setTimeout(() => card.classList.remove('ring-2', 'ring-emerald-400'), 400);
}

function moveBadgeCardDown(btn) {
    const card = btn.closest('.badge-card-row');
    if (!card || !card.nextElementSibling) return;
    card.parentNode.insertBefore(card.nextElementSibling, card);
    reindexBadgeCards();
    card.classList.add('ring-2', 'ring-emerald-400');
    setTimeout(() => card.classList.remove('ring-2', 'ring-emerald-400'), 400);
}

function removeBadgeCard(btn) {
    const card = btn.closest('.badge-card-row');
    if (!card) return;
    const remaining = document.querySelectorAll('#badge-cards-list .badge-card-row').length;
    if (remaining <= 1) {
        alert('You must have at least one badge configured.');
        return;
    }
    card.classList.add('opacity-0', 'scale-95');
    setTimeout(() => {
        card.remove();
        reindexBadgeCards();
    }, 150);
}

function addNewBadgeCard() {
    const template = document.getElementById('badge-card-template');
    const list = document.getElementById('badge-cards-list');
    if (!template || !list) return;

    const clone = template.content.cloneNode(true);
    const newCard = clone.querySelector('.badge-card-row');
    const select = newCard.querySelector('select');
    
    // Pick next icon
    const keys = Object.keys(ICON_LIBRARY);
    const count = list.children.length;
    const defaultKey = keys[count % keys.length] || 'zero-preservatives';
    
    if (select) {
        select.value = defaultKey;
        const line1 = newCard.querySelector('input[name*="title"]');
        const line2 = newCard.querySelector('input[name*="subtitle"]');
        if (ICON_LIBRARY[defaultKey]) {
            if (line1) line1.value = ICON_LIBRARY[defaultKey].default_line1 || 'QUALITY';
            if (line2) line2.value = ICON_LIBRARY[defaultKey].default_line2 || 'GUARANTEED';
        }
    }
    
    const previewBox = newCard.querySelector('.badge-preview-box div');
    if (previewBox) {
        previewBox.innerHTML = getIconSvgHtml(defaultKey, 'w-11 h-11');
    }

    list.appendChild(newCard);
    reindexBadgeCards();
    newCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    newCard.classList.add('ring-2', 'ring-emerald-400');
    setTimeout(() => newCard.classList.remove('ring-2', 'ring-emerald-400'), 500);
}

function updateAnnouncementBg(hex) {
    document.getElementById('liveAnnouncementBar').style.backgroundColor = hex;
    document.getElementById('ann_bg_hex').innerText = hex.toUpperCase();
}

function setAnnColor(hex) {
    document.getElementById('ann_bg_input').value = hex;
    updateAnnouncementBg(hex);
}

function setSaleDateOffset(days) {
    const now = new Date();
    now.setDate(now.getDate() + days);
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const dd = String(now.getDate()).padStart(2, '0');
    const hh = String(now.getHours()).padStart(2, '0');
    const min = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('sale_end_input').value = `${yyyy}-${mm}-${dd}T${hh}:${min}`;
}

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

// Icon Picker Modal Functions
let currentTargetCard = null;

function openIconPickerForCard(el) {
    currentTargetCard = el.closest('.badge-card-row');
    const modal = document.getElementById('iconPickerModal');
    if (modal) {
        modal.classList.remove('hidden');
        const search = document.getElementById('iconSearchInput');
        if (search) {
            search.value = '';
            filterIconPicker('');
            setTimeout(() => search.focus(), 50);
        }
    }
}

function closeIconPickerModal() {
    const modal = document.getElementById('iconPickerModal');
    if (modal) modal.classList.add('hidden');
    currentTargetCard = null;
}

function selectIconForCurrentCard(iconKey) {
    if (!currentTargetCard) return;
    const select = currentTargetCard.querySelector('select[name*="[icon]"]');
    if (select) {
        let opt = select.querySelector(`option[value="${CSS.escape(iconKey)}"]`);
        if (!opt) {
            opt = document.createElement('option');
            opt.value = iconKey;
            opt.innerText = iconKey.startsWith('assets/') ? `Custom: ${iconKey.split('/').pop()}` : iconKey;
            select.appendChild(opt);
        }
        select.value = iconKey;
        updateBadgeCardPreview(select);
    }
    closeIconPickerModal();
}

function switchIconModalTab(tabId) {
    const paneLib = document.getElementById('iconModalPane-library');
    const paneUp = document.getElementById('iconModalPane-upload');
    const btnLib = document.getElementById('iconModalTabBtn-library');
    const btnUp = document.getElementById('iconModalTabBtn-upload');

    if (tabId === 'library') {
        paneLib?.classList.remove('hidden');
        paneUp?.classList.add('hidden');
        if (btnLib) btnLib.className = 'flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition bg-white text-slate-900 shadow-xs border border-slate-200/80';
        if (btnUp) btnUp.className = 'flex-1 py-1.5 px-3 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-white/60 transition';
    } else {
        paneLib?.classList.add('hidden');
        paneUp?.classList.remove('hidden');
        if (btnUp) btnUp.className = 'flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition bg-white text-slate-900 shadow-xs border border-slate-200/80';
        if (btnLib) btnLib.className = 'flex-1 py-1.5 px-3 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-white/60 transition';
    }
}

function updateIconCounts() {
    const allItems = document.querySelectorAll('#iconPickerGrid .icon-picker-choice');
    const customItems = document.querySelectorAll('#iconPickerGrid .icon-picker-choice[data-is-custom="1"]');
    const totalCount = allItems.length;
    const customCount = customItems.length;

    const modalTotal = document.getElementById('modalIconTotal');
    if (modalTotal) modalTotal.innerText = totalCount;

    const catAll = document.getElementById('catTotalAll');
    if (catAll) catAll.innerText = totalCount;

    const catCustom = document.getElementById('catTotalCustom');
    if (catCustom) catCustom.innerText = customCount;

    const customTabBadge = document.getElementById('modalCustomTabCount');
    if (customTabBadge) customTabBadge.innerText = customCount;

    const uploadPaneCount = document.getElementById('uploadPaneCustomCount');
    if (uploadPaneCount) uploadPaneCount.innerText = customCount;

    const noCustomMsg = document.getElementById('noCustomIconsMsg');
    if (noCustomMsg) {
        noCustomMsg.style.display = customCount === 0 ? 'block' : 'none';
    }
}

function filterByCategory(category, btn) {
    document.querySelectorAll('#iconCategoryPills .icon-cat-btn').forEach(b => {
        b.className = 'icon-cat-btn px-2.5 py-0.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 transition font-medium';
    });
    btn.className = 'icon-cat-btn px-2.5 py-0.5 rounded-full bg-emerald-600 text-white font-bold transition';

    const search = document.getElementById('iconSearchInput');
    if (search) search.value = '';

    document.querySelectorAll('#iconPickerGrid .icon-picker-choice').forEach(card => {
        if (category === 'all') {
            card.classList.remove('hidden');
        } else if (category === 'custom') {
            if (card.getAttribute('data-is-custom') === '1') {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        } else {
            if (card.getAttribute('data-category') === category) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        }
    });
}

function removeIconFromLibrary(event, iconKey, isCustom = false) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }

    // Check if this icon is currently assigned to any card on the page
    const usedCards = [];
    document.querySelectorAll('#badge-cards-list .badge-card-row').forEach(card => {
        const sel = card.querySelector('select[name*="[icon]"]');
        if (sel && sel.value === iconKey) {
            const num = card.querySelector('.badge-num-display')?.innerText || '';
            usedCards.push(`Badge #${num}`);
        }
    });

    let confirmMsg = isCustom 
        ? 'Are you sure you want to permanently delete this custom icon from your library?' 
        : 'Are you sure you want to remove this icon from the library?';

    if (usedCards.length > 0) {
        confirmMsg = `Warning: This icon is currently assigned to ${usedCards.join(', ')}.\nRemoving it will change those badges to the default icon.\n\nDo you want to proceed?`;
    }

    if (!confirm(confirmMsg)) return;

    const formData = new FormData();
    formData.append('action', 'remove_badge_icon');
    formData.append('icon_key', iconKey);

    fetch('manage_home.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Remove from JS ICON_LIBRARY
            delete ICON_LIBRARY[iconKey];

            // Animate and remove from grid
            document.querySelectorAll(`#iconPickerGrid .icon-picker-choice[data-key="${CSS.escape(iconKey)}"]`).forEach(el => {
                el.classList.add('opacity-0', 'scale-90');
                setTimeout(() => el.remove(), 180);
            });

            // Remove from upload tab custom grid if present
            const uploadGrid = document.getElementById('uploadTabCustomGrid');
            if (uploadGrid) {
                const item = Array.from(uploadGrid.children).find(c => c.innerHTML && c.innerHTML.includes(iconKey));
                if (item) item.remove();
            }

            // Remove option from all card selects and template
            document.querySelectorAll(`select[name*="[icon]"] option[value="${CSS.escape(iconKey)}"]`).forEach(opt => opt.remove());

            // If cards were using this icon, reset them to default
            document.querySelectorAll('#badge-cards-list .badge-card-row').forEach(card => {
                const sel = card.querySelector('select[name*="[icon]"]');
                if (sel && sel.value === iconKey) {
                    sel.value = 'zero-preservatives';
                    updateBadgeCardPreview(sel);
                }
            });

            if (data.type === 'builtin') {
                const bar = document.getElementById('hiddenIconsRestoreBar');
                const countEl = document.getElementById('hiddenIconsCount');
                if (bar && countEl) {
                    bar.classList.remove('hidden');
                    countEl.innerText = parseInt(countEl.innerText || '0') + 1;
                }
            }

            setTimeout(updateIconCounts, 200);
        } else {
            alert(data.error || 'Failed to remove icon from library.');
        }
    })
    .catch(() => alert('Network error while removing icon.'));
}

const deleteCustomBadgeIcon = (e, key) => removeIconFromLibrary(e, key, true);

function restoreHiddenIcons() {
    if (!confirm('Restore all hidden built-in icons back to the library?')) return;

    const formData = new FormData();
    formData.append('action', 'restore_hidden_icons');

    fetch('manage_home.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Failed to restore icons.');
        }
    })
    .catch(() => alert('Network error while restoring icons.'));
}

function handleCustomIconFileUpload(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const statusEl = document.getElementById('iconUploadStatus');
    if (statusEl) statusEl.innerHTML = `<span class="text-sky-600 font-semibold"><i class="fas fa-spinner fa-spin mr-1"></i> Saving ${file.name} to library...</span>`;

    const formData = new FormData();
    formData.append('action', 'upload_badge_icon');
    formData.append('badge_icon_file', file);

    fetch('manage_home.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.path) {
            // Register permanently in Javascript ICON_LIBRARY
            ICON_LIBRARY[data.path] = {
                label: data.label,
                svg: data.svg,
                default_line1: data.label.replace('📁 ', '').replace(' (Custom)', '').toUpperCase().slice(0, 15),
                default_line2: '',
                is_custom: true
            };

            // 1. Add item to #iconPickerGrid
            const grid = document.getElementById('iconPickerGrid');
            if (grid) {
                const card = document.createElement('div');
                card.className = 'icon-picker-choice relative flex flex-col items-center justify-between p-3 rounded-xl border border-amber-300 bg-amber-50/20 hover:border-amber-500 hover:bg-amber-50/40 text-center transition group shadow-2xs hover:shadow-xs ring-2 ring-emerald-400';
                card.setAttribute('data-key', data.path);
                card.setAttribute('data-label', data.label.toLowerCase());
                card.setAttribute('data-category', 'custom');
                card.setAttribute('data-is-custom', '1');
                card.innerHTML = `
                    <div class="w-full flex items-center justify-between mb-1 z-10">
                        <span class="px-1.5 py-0.5 rounded text-[7px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200">Custom</span>
                        <button type="button" onclick="removeIconFromLibrary(event, '${data.path}', true)" title="Delete custom icon from library" class="w-6 h-6 rounded-md text-slate-300 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition cursor-pointer">
                            <i class="fas fa-trash-alt text-[10px]"></i>
                        </button>
                    </div>
                    <div class="w-12 h-12 flex items-center justify-center mb-1 text-emerald-700 group-hover:scale-110 transition-transform cursor-pointer" onclick="selectIconForCurrentCard('${data.path}')" title="Click to use icon">
                        ${data.svg}
                    </div>
                    <span class="text-[10px] font-bold text-slate-700 leading-tight group-hover:text-emerald-700 transition-colors line-clamp-2 cursor-pointer mb-2 w-full" onclick="selectIconForCurrentCard('${data.path}')">
                        ${escapeHtml(data.label)}
                    </span>
                    <button type="button" onclick="selectIconForCurrentCard('${data.path}')" class="w-full py-1 text-[9px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-600 hover:text-white rounded-lg transition border border-emerald-200/80 cursor-pointer">
                        Use Icon
                    </button>
                `;
                grid.insertBefore(card, grid.firstChild);
            }

            // 2. Add item to #uploadTabCustomGrid
            const uploadGrid = document.getElementById('uploadTabCustomGrid');
            if (uploadGrid) {
                const noMsg = document.getElementById('noCustomIconsMsg');
                if (noMsg) noMsg.style.display = 'none';

                const cleanTitle = data.label.replace('📁 ', '').replace(' (Custom)', '');
                const previewItem = document.createElement('div');
                previewItem.className = 'p-2.5 rounded-xl border border-amber-200 bg-amber-50/30 flex flex-col items-center justify-between text-center group relative hover:border-amber-400 transition ring-2 ring-emerald-400';
                previewItem.innerHTML = `
                    <button type="button" onclick="removeIconFromLibrary(event, '${data.path}', true)" title="Delete from library" class="absolute top-1 right-1 w-5 h-5 rounded text-slate-300 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition cursor-pointer">
                        <i class="fas fa-trash-alt text-[8px]"></i>
                    </button>
                    <div class="w-10 h-10 flex items-center justify-center my-1 cursor-pointer" onclick="selectIconForCurrentCard('${data.path}')">
                        ${data.svg}
                    </div>
                    <span class="text-[9px] font-bold text-slate-700 truncate w-full cursor-pointer" onclick="selectIconForCurrentCard('${data.path}')">
                        ${escapeHtml(cleanTitle)}
                    </span>
                    <button type="button" onclick="selectIconForCurrentCard('${data.path}')" class="mt-1.5 text-[8px] font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-100 hover:bg-emerald-200 px-2 py-0.5 rounded transition">
                        Use Icon
                    </button>
                `;
                uploadGrid.insertBefore(previewItem, uploadGrid.firstChild);
            }

            // 3. Add <option> to all card selects
            document.querySelectorAll('select[name*="[icon]"]').forEach(select => {
                if (!select.querySelector(`option[value="${CSS.escape(data.path)}"]`)) {
                    const opt = document.createElement('option');
                    opt.value = data.path;
                    opt.innerText = data.label;
                    select.insertBefore(opt, select.firstChild);
                }
            });

            updateIconCounts();

            if (statusEl) statusEl.innerHTML = `<span class="text-emerald-600 font-bold"><i class="fas fa-check-circle mr-1"></i> Saved to Icon Library!</span>`;
            setTimeout(() => {
                if (currentTargetCard) {
                    selectIconForCurrentCard(data.path);
                } else {
                    switchIconModalTab('library');
                }
                if (statusEl) statusEl.innerHTML = '';
                input.value = '';
            }, 400);
        } else {
            if (statusEl) statusEl.innerHTML = `<span class="text-rose-600"><i class="fas fa-exclamation-circle mr-1"></i> ${data.error || 'Upload failed.'}</span>`;
        }
    })
    .catch(err => {
        if (statusEl) statusEl.innerHTML = `<span class="text-rose-600"><i class="fas fa-exclamation-circle mr-1"></i> Upload error occurred.</span>`;
    });
}

function filterIconPicker(query) {
    query = query.toLowerCase().trim();
    document.querySelectorAll('#iconPickerGrid .icon-picker-choice').forEach(btn => {
        const key = btn.getAttribute('data-key') || '';
        const label = btn.getAttribute('data-label') || '';
        if (!query || key.includes(query) || label.includes(query)) {
            btn.classList.remove('hidden');
        } else {
            btn.classList.add('hidden');
        }
    });
}

// Close modal on escape key or outside click
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeIconPickerModal();
});

document.addEventListener('click', (e) => {
    const modal = document.getElementById('iconPickerModal');
    if (modal && !modal.classList.contains('hidden') && e.target === modal) {
        closeIconPickerModal();
    }
});

// Initial sync on load
document.addEventListener('DOMContentLoaded', () => {
    syncStorefrontLivePreview();
});
</script>

<?php include 'includes/footer.php'; ?>
