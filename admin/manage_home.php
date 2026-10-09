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

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $_SESSION['error'] = "The file you are trying to upload exceeds server limits.";
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
        $title = sanitize_input($_POST['sale_title']);
        $end_date = sanitize_input($_POST['sale_end']);
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
            resolve_sort_conflict('trust_badges', $sort_order);
            $stmt = $conn->prepare("INSERT INTO trust_badges (title, subtitle, icon, sort_order, is_active, bg_color, icon_color) VALUES (?, ?, ?, ?, 1, ?, ?)");
            $stmt->bind_param("sssiss", $title, $subtitle, $icon, $sort_order, $bg_color, $icon_color);
            if ($stmt->execute()) $_SESSION['msg'] = "Trust badge added!";
            else $_SESSION['error'] = "Failed to add badge: " . $conn->error;
        } else {
            $id = intval($_POST['badge_id']);
            resolve_sort_conflict('trust_badges', $sort_order, $id);
            $stmt = $conn->prepare("UPDATE trust_badges SET title=?, subtitle=?, icon=?, sort_order=?, bg_color=?, icon_color=? WHERE id=?");
            $stmt->bind_param("sssisss", $title, $subtitle, $icon, $sort_order, $bg_color, $icon_color, $id);
            if ($stmt->execute()) $_SESSION['msg'] = "Trust badge updated!";
            else $_SESSION['error'] = "Failed to update badge: " . $conn->error;
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

        if (!$error) {
            $stmt = $conn->prepare("UPDATE homepage_sections SET heading = ?, subheading = ?, media_url = ?, video_url = ? WHERE section_name = 'video_brand_story'");
            $stmt->bind_param("ssss", $heading, $subheading, $media_url, $video_url);
            if ($stmt->execute()) {
                $_SESSION['msg'] = "Video brand story section updated!";
                $redirect = true;
            }
        }
    }

    if ($redirect) {
        header("Location: manage_home.php");
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
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Homepage Management</h1>
            <p class="text-sm text-slate-500 mt-0.5">Customize announcements, promotional sale banners, trust badges, and brand media.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="../index.php" target="_blank" class="btn-admin btn-admin-secondary text-xs">
                <i class="fas fa-external-link-alt text-slate-500"></i> View Storefront
            </a>
            <a href="hero_slides.php" class="btn-admin btn-admin-primary text-xs">
                <i class="fas fa-images"></i> Hero Slides
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if ($msg): ?>
        <div class="p-3.5 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times text-xs"></i></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-3.5 bg-rose-50 text-rose-800 rounded-xl border border-rose-200 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-circle text-rose-600"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fas fa-times text-xs"></i></button>
        </div>
    <?php endif; ?>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="admin-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-slate-500">Active Flash Sale</span>
                    <h3 class="text-lg font-bold text-slate-900 mt-1 truncate max-w-[200px]">
                        <?php echo ($sale['is_active'] ?? 0) ? htmlspecialchars($sale['title']) : 'No Active Sale'; ?>
                    </h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                    <i class="fas fa-bolt"></i>
                </div>
            </div>
        </div>

        <div class="admin-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-slate-500">Top Header Marquee</span>
                    <h3 class="text-lg font-bold text-slate-900 mt-1">
                        <?php echo !empty($announcement_text) ? 'Enabled' : 'Disabled'; ?>
                    </h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg">
                    <i class="fas fa-bullhorn"></i>
                </div>
            </div>
        </div>

        <div class="admin-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-slate-500">Trust Badges Count</span>
                    <h3 class="text-lg font-bold text-slate-900 mt-1">
                        <?php echo count($trust_badges); ?> Badges
                    </h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Trust Badges Section -->
    <div class="admin-card p-0 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-shield-heart text-primary"></i> Trust Badges Marquee
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Manage credibility guarantee badges displayed below the hero carousel.</p>
            </div>
            <button onclick="showModal('badge-modal')" class="btn-admin btn-admin-primary text-xs">
                <i class="fas fa-plus"></i> Add Badge
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Badge & Text</th>
                        <th class="text-center">Colors</th>
                        <th class="text-center">Order</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($trust_badges)): ?>
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400 text-xs">No trust badges configured yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($trust_badges as $badge): 
                            $bg = str_contains($badge['bg_color'], '[') ? substr($badge['bg_color'], 4, 7) : $badge['bg_color'];
                            $ic = str_contains($badge['icon_color'], '[') ? substr($badge['icon_color'], 6, 7) : $badge['icon_color'];
                        ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg border border-slate-200" style="background-color: <?php echo htmlspecialchars($bg); ?>; color: <?php echo htmlspecialchars($ic); ?>;">
                                            <i class="<?php echo htmlspecialchars($badge['icon']); ?>"></i>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-900 text-xs uppercase tracking-tight"><?php echo htmlspecialchars($badge['title']); ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5"><?php echo htmlspecialchars($badge['subtitle']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-slate-100 text-[10px] font-mono text-slate-600 border border-slate-200">
                                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: <?php echo htmlspecialchars($bg); ?>;"></span>
                                        <span><?php echo htmlspecialchars($bg); ?></span>
                                    </div>
                                </td>
                                <td class="text-center font-mono text-xs font-semibold text-slate-700">
                                    <?php echo (int)$badge['sort_order']; ?>
                                </td>
                                <td>
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="badge_id" value="<?php echo $badge['id']; ?>">
                                        <button type="submit" name="toggle_badge" class="cursor-pointer">
                                            <?php if ($badge['is_active']): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                    Disabled
                                                </span>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button onclick='openEditBadge(<?php echo htmlspecialchars(json_encode($badge)); ?>)' class="p-1.5 text-slate-400 hover:text-slate-800 rounded hover:bg-slate-100 transition-colors" title="Edit Badge">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Delete this trust badge?')" class="inline-block">
                                            <input type="hidden" name="badge_id" value="<?php echo $badge['id']; ?>">
                                            <button type="submit" name="delete_badge" class="p-1.5 text-slate-400 hover:text-rose-600 rounded hover:bg-rose-50 transition-colors" title="Delete Badge">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2-Column: Flash Sale & Announcement Marquee -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Flash Sale Card -->
        <div class="admin-card p-5 flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="fas fa-bolt text-amber-500"></i> Promotional Flash Sale
                </h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="update_sale" value="1">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sale Headline Banner</label>
                        <input type="text" name="sale_title" value="<?php echo htmlspecialchars($sale['title'] ?? ''); ?>" placeholder="e.g. HARVEST SALE - 20% OFF ALL DRIED FRUITS" class="admin-input text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Countdown End Date & Time</label>
                        <input type="datetime-local" name="sale_end" value="<?php echo isset($sale['end_date']) ? date('Y-m-d\TH:i', strtotime($sale['end_date'])) : ''; ?>" class="admin-input text-xs">
                    </div>
                    <div class="pt-1">
                        <label class="relative flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="sale_active" value="1" <?php echo ($sale['is_active'] ?? 0) ? 'checked' : ''; ?> class="rounded border-slate-300 text-primary focus:ring-primary">
                            <span class="text-xs font-semibold text-slate-700">Display Sale Banner on Homepage</span>
                        </label>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="w-full btn-admin btn-admin-primary text-xs py-2.5">
                            <i class="fas fa-save"></i> Save Sale Timer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Announcement Marquee Card -->
        <div class="admin-card p-5 flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="fas fa-bullhorn text-sky-500"></i> Top Header Announcement Marquee
                </h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="update_announcement" value="1">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Marquee Announcement Text</label>
                        <textarea name="announcement_text" rows="3" class="admin-input text-xs resize-none" placeholder="Enter scrolling announcement text..."><?php echo htmlspecialchars($announcement_text); ?></textarea>
                        <p class="text-[10px] text-slate-400 mt-1">Separate multiple announcements with <span class="font-bold text-slate-700">" • "</span> bullet symbols.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bar Background Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="announcement_bg_color" id="ann_bg_input" value="<?php echo $announcement_bg; ?>" class="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5" oninput="document.getElementById('ann_bg_hex').innerText = this.value.toUpperCase()">
                            <code id="ann_bg_hex" class="text-xs font-mono font-semibold text-slate-700"><?php echo strtoupper($announcement_bg); ?></code>
                        </div>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="w-full btn-admin btn-admin-primary text-xs py-2.5">
                            <i class="fas fa-save"></i> Update Marquee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Video Brand Story Section -->
    <div class="admin-card p-6">
        <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
            <i class="fas fa-video text-indigo-500"></i> Video Brand Story Section
        </h3>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="update_video_section" value="1">
            <input type="hidden" name="current_media" value="<?php echo htmlspecialchars($vid_sec['media_url'] ?? ''); ?>">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Section Title</label>
                        <input type="text" name="heading" value="<?php echo htmlspecialchars($vid_sec['heading'] ?? ''); ?>" class="admin-input text-xs" placeholder="e.g. Pure Craft, Valley Freshness">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Subheading Description</label>
                        <textarea name="subheading" rows="3" class="admin-input text-xs resize-none" placeholder="Provide a compelling story behind DRIYUM's craft..."><?php echo htmlspecialchars($vid_sec['subheading'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">External Video URL (YouTube / Vimeo)</label>
                        <input type="text" name="video_url" value="<?php echo htmlspecialchars($vid_sec['video_url'] ?? ''); ?>" class="admin-input text-xs" placeholder="https://youtube.com/watch?v=...">
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
                    <p class="text-[10px] text-slate-400">Hover over the preview above to change cover photo or upload a direct video file.</p>
                </div>
            </div>

            <div class="flex items-center justify-end pt-4 border-t border-slate-100">
                <button type="submit" name="update_video_section" class="btn-admin btn-admin-primary text-xs">
                    <i class="fas fa-save"></i> Save Brand Story
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TRUST BADGE MODAL -->
<div id="badge-modal" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900" id="badge-modal-title">Add Trust Badge</h3>
            <button onclick="closeBadgeModal()" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <form method="POST" class="p-5 space-y-4">
            <input type="hidden" name="badge_id" id="modal-badge-id">
            <input type="hidden" name="add_badge" id="modal-badge-action-add" value="1">
            <input type="hidden" name="edit_badge" id="modal-badge-action-edit" value="1" disabled>
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Badge Title *</label>
                <input type="text" name="badge_title" id="modal-badge-title" placeholder="e.g. 100% Organic" class="admin-input text-xs" required>
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Subtitle / Detail</label>
                <input type="text" name="badge_subtitle" id="modal-badge-subtitle" placeholder="e.g. Direct Valley Harvest" class="admin-input text-xs">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">FontAwesome Icon *</label>
                    <input type="text" name="badge_icon" id="modal-badge-icon" placeholder="fas fa-leaf" class="admin-input text-xs font-mono" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sort Order</label>
                    <input type="number" name="sort_order" id="modal-badge-sort" value="0" class="admin-input text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Card Background</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="badge_bg_color" id="modal-badge-bg" value="#FFFEDC" class="w-8 h-8 rounded border border-slate-300 cursor-pointer">
                        <span class="text-xs font-mono text-slate-600" id="badge-bg-hex">#FFFEDC</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Icon Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="badge_icon_color" id="modal-badge-icon-color" value="#19DC7E" class="w-8 h-8 rounded border border-slate-300 cursor-pointer">
                        <span class="text-xs font-mono text-slate-600" id="badge-icon-hex">#19DC7E</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeBadgeModal()" class="btn-admin btn-admin-secondary text-xs">Cancel</button>
                <button type="submit" class="btn-admin btn-admin-primary text-xs">Save Badge</button>
            </div>
        </form>
    </div>
</div>

<script>
function showModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function hideModal(id) {
    document.getElementById(id).classList.add('hidden');
}

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
    }, 200);
}

if(document.getElementById('modal-badge-bg')) {
    document.getElementById('modal-badge-bg').oninput = function() { document.getElementById('badge-bg-hex').innerText = this.value.toUpperCase(); };
}
if(document.getElementById('modal-badge-icon-color')) {
    document.getElementById('modal-badge-icon-color').oninput = function() { document.getElementById('badge-icon-hex').innerText = this.value.toUpperCase(); };
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
</script>

<?php include 'includes/footer.php'; ?>
