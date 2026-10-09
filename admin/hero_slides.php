<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Strict Admin Check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$conn = get_db_connection();

// --- AJAX REORDER HANDLER ---
if (isset($_GET['action']) && $_GET['action'] === 'reorder') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    if (!empty($input['order']) && is_array($input['order'])) {
        $stmt = $conn->prepare("UPDATE hero_slides SET sort_order = ? WHERE id = ?");
        foreach ($input['order'] as $item) {
            $sid = intval($item['id']);
            $sorder = intval($item['sort_order']);
            $stmt->bind_param("ii", $sorder, $sid);
            $stmt->execute();
        }
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

// --- AJAX TOGGLE STATUS ---
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    header('Content-Type: application/json');
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $conn->query("UPDATE hero_slides SET is_active = 1 - is_active WHERE id = $id");
        $new_status = fetch_one("SELECT is_active FROM hero_slides WHERE id = $id")['is_active'] ?? 0;
        echo json_encode(['success' => true, 'is_active' => (int)$new_status]);
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}

// --- AJAX SAVE WAVE SETTING ---
if (isset($_GET['action']) && $_GET['action'] === 'save_wave') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $intensity = isset($input['intensity']) ? max(0, min(100, intval($input['intensity']))) : 75;
    update_setting('hero_wave_intensity', $intensity);
    echo json_encode(['success' => true, 'intensity' => $intensity]);
    exit;
}

// --- FORM POST ACTIONS ---
$msg = "";
$err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 0. Save Wave Settings Form
    if (isset($_POST['save_wave_settings'])) {
        $intensity = isset($_POST['hero_wave_intensity']) ? max(0, min(100, intval($_POST['hero_wave_intensity']))) : 75;
        update_setting('hero_wave_intensity', $intensity);
        $_SESSION['msg'] = "Hero wave border intensity updated to {$intensity}%!";
        header("Location: hero_slides.php");
        exit;
    }

    // 1. Delete Slide
    if (isset($_POST['delete_slide'])) {
        $id = intval($_POST['slide_id']);
        $old = fetch_one("SELECT image, image_tablet, image_mobile FROM hero_slides WHERE id = $id");
        if ($old) {
            foreach (['image', 'image_tablet', 'image_mobile'] as $img_col) {
                if (!empty($old[$img_col]) && strpos($old[$img_col], 'assets/images/uploads/') === 0) {
                    $file_path = '../' . $old[$img_col];
                    if (file_exists($file_path)) @unlink($file_path);
                }
            }
        }
        $conn->query("DELETE FROM hero_slides WHERE id = $id");
        $_SESSION['msg'] = "Slide deleted successfully!";
        header("Location: hero_slides.php");
        exit;
    }

    // 2. Bulk Delete
    if (isset($_POST['bulk_delete']) && !empty($_POST['selected_ids'])) {
        $ids = array_map('intval', $_POST['selected_ids']);
        if (!empty($ids)) {
            $ids_str = implode(',', $ids);
            $old_rows = fetch_all("SELECT image, image_tablet, image_mobile FROM hero_slides WHERE id IN ($ids_str)");
            foreach ($old_rows as $row) {
                foreach (['image', 'image_tablet', 'image_mobile'] as $img_col) {
                    if (!empty($row[$img_col]) && strpos($row[$img_col], 'assets/images/uploads/') === 0) {
                        $f = '../' . $row[$img_col];
                        if (file_exists($f)) @unlink($f);
                    }
                }
            }
            $conn->query("DELETE FROM hero_slides WHERE id IN ($ids_str)");
            $_SESSION['msg'] = count($ids) . " slides deleted!";
            header("Location: hero_slides.php");
            exit;
        }
    }

    // 3. Save / Update Slide
    if (isset($_POST['save_slide'])) {
        $slide_id = intval($_POST['slide_id'] ?? 0);
        $alt_text = sanitize_input($_POST['alt_text'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 1);
        
        // Link handling
        $action_target = trim($_POST['action_target'] ?? '');
        $custom_link = trim($_POST['custom_cta_link'] ?? '');
        $product_id = null;
        $cta_link = '';

        if ($action_target === 'custom') {
            $cta_link = $custom_link;
        } elseif (strpos($action_target, 'prod:') === 0) {
            $parts = explode(':', $action_target, 3);
            $product_id = intval($parts[1]);
            $cta_link = $parts[2] ?? '';
        } elseif (strpos($action_target, 'cat:') === 0) {
            $parts = explode(':', $action_target, 2);
            $cta_link = $parts[1] ?? '';
        } elseif (!empty($action_target)) {
            $cta_link = $action_target;
        }

        // Existing values if editing
        $existing = null;
        if ($slide_id > 0) {
            $existing = fetch_one("SELECT * FROM hero_slides WHERE id = ?", [$slide_id]);
        }

        $image_desktop = trim($_POST['desktop_image_path'] ?? ($existing['image'] ?? ''));
        $image_tablet  = trim($_POST['tablet_image_path'] ?? ($existing['image_tablet'] ?? ''));
        $image_mobile  = trim($_POST['mobile_image_path'] ?? ($existing['image_mobile'] ?? ''));

        // Handle uploaded files
        $upload_dir = "../assets/images/uploads/";
        if (!file_exists($upload_dir)) @mkdir($upload_dir, 0777, true);

        $upload_map = [
            'file_desktop' => ['var' => &$image_desktop, 'prefix' => 'slide_desk_'],
            'file_tablet'  => ['var' => &$image_tablet,  'prefix' => 'slide_tab_'],
            'file_mobile'  => ['var' => &$image_mobile,  'prefix' => 'slide_mob_']
        ];

        foreach ($upload_map as $input_name => $cfg) {
            if (!empty($_FILES[$input_name]['name']) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'])) {
                    $new_name = $cfg['prefix'] . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    $target = $upload_dir . $new_name;
                    if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $target)) {
                        $cfg['var'] = 'assets/images/uploads/' . $new_name;
                    }
                }
            }
        }

        if (empty($image_desktop)) {
            $err = "Desktop banner image is mandatory.";
        } else {
            if ($slide_id > 0) {
                $stmt = $conn->prepare("UPDATE hero_slides SET image=?, image_tablet=?, image_mobile=?, cta_link=?, product_id=?, alt_text=?, sort_order=?, is_active=? WHERE id=?");
                $stmt->bind_param("ssssisiii", $image_desktop, $image_tablet, $image_mobile, $cta_link, $product_id, $alt_text, $sort_order, $is_active, $slide_id);
                $stmt->execute();
                $_SESSION['msg'] = "Slide updated successfully!";
            } else {
                $stmt = $conn->prepare("INSERT INTO hero_slides (image, image_tablet, image_mobile, cta_link, product_id, alt_text, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssisii", $image_desktop, $image_tablet, $image_mobile, $cta_link, $product_id, $alt_text, $sort_order, $is_active);
                $stmt->execute();
                $_SESSION['msg'] = "New slide banner created successfully!";
            }
            header("Location: hero_slides.php");
            exit;
        }
    }
}

// Fetch all slides
$slides = fetch_all("SELECT s.*, p.name as prod_name, p.slug as prod_slug 
                     FROM hero_slides s 
                     LEFT JOIN products p ON s.product_id = p.id 
                     ORDER BY s.sort_order ASC, s.id DESC");

$total_slides = count($slides);
$active_slides = count(array_filter($slides, fn($s) => !empty($s['is_active'])));

// Next suggested sort order
$next_sort = 0;
if (!empty($slides)) {
    $max_sort = max(array_map(fn($s) => (int)$s['sort_order'], $slides));
    $next_sort = $max_sort + 1;
}

// Fetch categories & products for target dropdown
$categories_list = fetch_all("SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY name ASC");
$products_list = fetch_all("SELECT id, name, slug FROM products WHERE is_active = 1 ORDER BY name ASC");

// Fetch dynamic wave intensity setting
$current_wave_intensity = function_exists('get_setting') ? (int)get_setting('hero_wave_intensity', 75) : 75;
$hero_wave_preview = get_hero_wave_data($current_wave_intensity);

require_once 'includes/header.php';
?>

<!-- Sortable.js for smooth Drag & Drop reordering -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="space-y-6">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-images text-emerald-700 text-xl"></i>
                Hero Banner Carousel
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage responsive homepage banners, destination links, sequence ordering, and organic wave borders.</p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <button onclick="openAddSlideModal()" class="btn-admin btn-admin-primary text-xs inline-flex items-center gap-2">
                <i class="fas fa-plus"></i> Add New Slide
            </button>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    <?php if(!empty($_SESSION['msg'])): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
                <span><?php echo htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xs"></i></button>
        </div>
    <?php endif; ?>

    <?php if(!empty($err)): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <i class="fas fa-exclamation-circle text-rose-600 text-sm"></i>
                <span><?php echo htmlspecialchars($err); ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xs"></i></button>
        </div>
    <?php endif; ?>

    <!-- METRICS OVERVIEW CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="admin-card p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center text-lg border border-sky-100 shrink-0">
                <i class="fas fa-images"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900 leading-none"><?php echo $total_slides; ?></div>
                <div class="text-xs text-slate-500 font-medium mt-1">Total Hero Slides</div>
            </div>
        </div>

        <div class="admin-card p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg border border-emerald-100 shrink-0">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900 leading-none"><?php echo $active_slides; ?></div>
                <div class="text-xs text-slate-500 font-medium mt-1">Active on Frontpage</div>
            </div>
        </div>

        <div class="admin-card p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center text-lg border border-teal-100 shrink-0">
                <i class="fas fa-water"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900 leading-none"><?php echo $current_wave_intensity; ?>%</div>
                <div class="text-xs text-slate-500 font-medium mt-1">Wave Border Curvature</div>
            </div>
        </div>
    </div>

    <!-- WAVY BORDER DIVIDER CUSTOMIZER -->
    <style>
        #wave-slider {
            --fill-pct: <?php echo (int)$current_wave_intensity; ?>%;
            -webkit-appearance: none;
            appearance: none;
            background: linear-gradient(to right, #004f42 0%, #24B25D var(--fill-pct), #E2E8F0 var(--fill-pct), #E2E8F0 100%);
            border-radius: 9999px;
            height: 8px;
        }
        #wave-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #ffffff;
            border: 3px solid #004f42;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 79, 66, 0.4);
            transition: transform 0.15s ease;
        }
        #wave-slider::-webkit-slider-thumb:hover {
            transform: scale(1.15);
        }
        .preset-btn {
            transition: all 0.18s ease;
            cursor: pointer;
        }
        .preset-btn:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
            transform: translateY(-1px);
        }
        .preset-btn.active {
            border-color: #004f42;
            background: #ecfdf5;
            box-shadow: 0 1px 4px rgba(0, 79, 66, 0.12);
        }
        .preset-btn.active span:first-child {
            color: #004f42;
        }
    </style>
    

    <!-- BANNER DIMENSIONS HELPER ALERT -->
    <div class="p-3.5 rounded-xl bg-amber-50/80 border border-amber-200 text-amber-900 text-xs flex items-start gap-3">
        <i class="fas fa-info-circle text-amber-600 mt-0.5 shrink-0 text-sm"></i>
        <div class="flex-1 space-y-1">
            <span class="font-bold">Recommended Banner Resolution Standards:</span>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-amber-950/80">
                <span><strong>Desktop (>=992px):</strong> Aspect ratio ~2.18:1. Recommended size: 1350×620 px (or 1920×880 px)</span>
                <span><strong>Tablet (577px–992px):</strong>  Aspect ratio 16:10. Recommended size: 1024×640 px</span>
                <span><strong>Mobile (&lt;=576px):</strong> Aspect ratio 1:1 (Square). Recommended size: 768×768 px (or 1024×1024 px)</span>
            </div>
        </div>
    </div>

    <!-- MAIN CAROUSEL BANNERS TABLE -->
    <div class="admin-card overflow-hidden">
        <div class="px-2 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-slate-800">Carousel Slides Sequence</h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-200 text-slate-700">
                    <?php echo $total_slides; ?> Banners
                </span>
            </div>
            <p class="text-xs text-slate-400 font-medium hidden sm:block">
                <i class="fas fa-grip-vertical mr-1"></i> Drag rows to reorder carousel sequence
            </p>
        </div>

        <form id="bulk-form" method="POST">
            <input type="hidden" name="bulk_delete" value="1">
            
            <!-- FLOATING BULK DOCK (LIGHT THEMED & RESPONSIVE) -->
            <div id="bulk-action-bar" class="hidden admin-bulk-dock">
                <span class="bulk-counter-badge"><span id="selected-count">0</span> Selected</span>
                <button type="button" onclick="submitBulkDelete()" class="bulk-btn bulk-btn-danger">
                    <i class="fas fa-trash-alt mr-1"></i> Delete Selected
                </button>
                <button type="button" onclick="document.querySelectorAll('.row-checkbox').forEach(cb => { cb.checked = false; }); updateBulkBar();" class="text-slate-400 hover:text-slate-700 p-1 text-xs" title="Clear selection">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">
                                <input type="checkbox" id="select-all" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            </th>
                            <th class="w-14 text-center">Order</th>
                            <th class="min-w-[280px]">Responsive Media (Desk / Tab / Mob)</th>
                            <th class="min-w-[180px]">Target Destination</th>
                            <th class="min-w-[200px]">Alt / Description</th>
                            <th class="w-24 text-center">Status</th>
                            <th class="w-24 text-right pr-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-slides" class="divide-y divide-slate-100">
                        <?php if(empty($slides)): ?>
                            <tr>
                                <td colspan="7" class="py-14 text-center">
                                    <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                                        <i class="fas fa-images"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-800">No Hero Banner Slides Found</h4>
                                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Get started by creating your first responsive banner slide for the storefront.</p>
                                    <button type="button" onclick="openAddSlideModal()" class="btn-admin btn-admin-primary text-xs mt-3.5 inline-flex items-center gap-1.5">
                                        <i class="fas fa-plus"></i> Add First Slide
                                    </button>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($slides as $s): 
                                $desk_img = !empty($s['image']) ? get_url(ltrim($s['image'], './')) : '';
                                $tab_img  = !empty($s['image_tablet']) ? get_url(ltrim($s['image_tablet'], './')) : '';
                                $mob_img  = !empty($s['image_mobile']) ? get_url(ltrim($s['image_mobile'], './')) : '';
                                
                                $disp_link = $s['cta_link'] ?: (!empty($s['prod_slug']) ? '/product/' . $s['prod_slug'] : '');
                                $alt_disp = !empty($s['alt_text']) ? $s['alt_text'] : ($s['title'] ?: '—');
                            ?>
                            <tr class="hover:bg-slate-50/70 transition-colors cursor-default" data-id="<?php echo $s['id']; ?>">
                                <!-- Checkbox -->
                                <td class="text-center">
                                    <input type="checkbox" name="selected_ids[]" value="<?php echo $s['id']; ?>" class="row-checkbox rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                </td>

                                <!-- Sort Grip + Number -->
                                <td class="text-center">
                                    <div class="drag-handle inline-flex items-center gap-1 cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-700 py-1 px-1.5 rounded-lg hover:bg-slate-100 transition">
                                        <i class="fas fa-grip-vertical text-xs"></i>
                                        <span class="sort-number font-bold text-slate-700 text-xs"><?php echo (int)$s['sort_order']; ?></span>
                                    </div>
                                </td>

                                <!-- Responsive Banner Previews -->
                                <td>
                                    <div class="flex items-center gap-3">
                                        <!-- Desktop Preview -->
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Desktop</span>
                                            <div class="w-20 h-9 rounded-md bg-slate-100 border border-slate-200 overflow-hidden cursor-pointer hover:ring-2 hover:ring-emerald-500 transition" onclick="viewLargeImage('<?php echo $desk_img; ?>', 'Desktop Banner')">
                                                <?php if($desk_img): ?>
                                                    <img src="<?php echo $desk_img; ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <span class="w-full h-full flex items-center justify-center text-[9px] text-slate-300">None</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Tablet Preview -->
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Tablet</span>
                                            <div class="w-14 h-9 rounded-md bg-slate-100 border border-slate-200 overflow-hidden cursor-pointer hover:ring-2 hover:ring-emerald-500 transition" onclick="viewLargeImage('<?php echo $tab_img ?: $desk_img; ?>', 'Tablet Banner')">
                                                <?php if($tab_img): ?>
                                                    <img src="<?php echo $tab_img; ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <span class="w-full h-full flex items-center justify-center text-[8px] text-slate-300 italic">Auto</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Mobile Preview -->
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Mobile</span>
                                            <div class="w-9 h-9 rounded-md bg-slate-100 border border-slate-200 overflow-hidden cursor-pointer hover:ring-2 hover:ring-emerald-500 transition" onclick="viewLargeImage('<?php echo $mob_img ?: $desk_img; ?>', 'Mobile Banner')">
                                                <?php if($mob_img): ?>
                                                    <img src="<?php echo $mob_img; ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <span class="w-full h-full flex items-center justify-center text-[8px] text-slate-300 italic">Auto</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Target Link -->
                                <td>
                                    <?php if(!empty($s['prod_name'])): ?>
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 text-xs font-semibold border border-emerald-200">
                                            <i class="fas fa-box text-[10px] text-emerald-600"></i>
                                            <span class="truncate max-w-[150px]"><?php echo htmlspecialchars($s['prod_name']); ?></span>
                                        </div>
                                    <?php elseif(!empty($disp_link)): ?>
                                        <a href="<?php echo htmlspecialchars($disp_link); ?>" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-slate-600 hover:text-emerald-700 font-mono hover:underline">
                                            <i class="fas fa-link text-[10px] text-slate-400"></i>
                                            <span class="truncate max-w-[150px]"><?php echo htmlspecialchars($disp_link); ?></span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-300 italic">(No Link)</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Alt Description -->
                                <td>
                                    <div class="text-xs text-slate-700 font-medium truncate max-w-[200px]" title="<?php echo htmlspecialchars($alt_disp); ?>">
                                        <?php echo htmlspecialchars($alt_disp); ?>
                                    </div>
                                </td>

                                <!-- Status Toggle -->
                                <td class="text-center">
                                    <button type="button" onclick="toggleSlideStatus(<?php echo $s['id']; ?>, this)" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border transition <?php echo !empty($s['is_active']) ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'; ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?php echo !empty($s['is_active']) ? 'bg-emerald-500' : 'bg-slate-400'; ?>"></span>
                                        <span><?php echo !empty($s['is_active']) ? 'Active' : 'Offline'; ?></span>
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="text-right pr-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" onclick="openEditSlideModal(<?php echo htmlspecialchars(json_encode($s)); ?>)" class="btn-admin btn-admin-secondary text-xs py-1 px-2.5" title="Edit Slide">
                                            <i class="fas fa-pen text-[10px]"></i>
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this banner slide permanently?');">
                                            <input type="hidden" name="delete_slide" value="1">
                                            <input type="hidden" name="slide_id" value="<?php echo $s['id']; ?>">
                                            <button type="submit" class="btn-admin btn-admin-danger text-xs py-1 px-2" title="Delete">
                                                <i class="fas fa-trash-alt text-[10px]"></i>
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
        </form>
    </div>

</div>

<!-- SLIDE ADD / EDIT MODAL DIALOG -->
<div id="slide-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeSlideModal()"></div>

    <!-- Modal Box -->
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden border border-slate-200 relative z-10 max-h-[92vh] flex flex-col anim-fade-in">
        
        <!-- Header in Brand Forest Green -->
        <div class="bg-[#004f42] px-6 py-4 flex items-center justify-between text-white shrink-0">
            <h3 id="modal-title" class="text-base font-bold tracking-tight">Add New Hero Banner Slide</h3>
            <button type="button" onclick="closeSlideModal()" class="w-7 h-7 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-xs transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Form Body -->
        <div class="overflow-y-auto p-2 flex-1 custom-scrollbar">
            <form id="slide-form" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="save_slide" value="1">
                <input type="hidden" name="slide_id" id="modal-slide-id" value="0">

                <!-- 1. Desktop Image Path (1350x620) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Desktop Banner (1350×620 px recommended) <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="desktop_image_path" id="desktop-image-path" placeholder="assets/images/hero/banner-desktop.png" required class="admin-input text-xs flex-1">
                        <input type="file" name="file_desktop" id="file-desktop" accept="image/*" class="hidden" onchange="handleFileSelected(this, 'desktop')">
                        <button type="button" onclick="document.getElementById('file-desktop').click()" class="btn-admin btn-admin-secondary text-xs px-3.5 shrink-0">
                            <i class="fas fa-upload mr-1 text-slate-400"></i> Browse
                        </button>
                    </div>
                    <div id="preview-box-desktop" class="hidden mt-2 p-2 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-3">
                        <img id="preview-img-desktop" src="" class="w-20 h-9 object-cover rounded border border-slate-200">
                        <div class="flex-1 text-[11px] text-slate-500">
                            <div class="font-bold text-slate-800" id="preview-name-desktop">Selected Banner</div>
                            <span class="text-[10px] text-emerald-700 font-semibold">Desktop Resolution (~2.18:1)</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Tablet Image Path (1024x640) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Tablet Banner (1024×640 px / Optional)
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="tablet_image_path" id="tablet-image-path" placeholder="assets/images/hero/banner-tablet.png" class="admin-input text-xs flex-1">
                        <input type="file" name="file_tablet" id="file-tablet" accept="image/*" class="hidden" onchange="handleFileSelected(this, 'tablet')">
                        <button type="button" onclick="document.getElementById('file-tablet').click()" class="btn-admin btn-admin-secondary text-xs px-3.5 shrink-0">
                            <i class="fas fa-upload mr-1 text-slate-400"></i> Browse
                        </button>
                    </div>
                    <div id="preview-box-tablet" class="hidden mt-2 p-2 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-3">
                        <img id="preview-img-tablet" src="" class="w-16 h-10 object-cover rounded border border-slate-200">
                        <div class="flex-1 text-[11px] text-slate-500">
                            <div class="font-bold text-slate-800" id="preview-name-tablet">Selected Banner</div>
                            <span class="text-[10px] text-sky-700 font-semibold">Tablet Resolution (16:10)</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Mobile Image Path (768x768 Square) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Mobile Banner (768×768 px Square / Optional)
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="mobile_image_path" id="mobile-image-path" placeholder="assets/images/hero/banner-mobile.png" class="admin-input text-xs flex-1">
                        <input type="file" name="file_mobile" id="file-mobile" accept="image/*" class="hidden" onchange="handleFileSelected(this, 'mobile')">
                        <button type="button" onclick="document.getElementById('file-mobile').click()" class="btn-admin btn-admin-secondary text-xs px-3.5 shrink-0">
                            <i class="fas fa-upload mr-1 text-slate-400"></i> Browse
                        </button>
                    </div>
                    <div id="preview-box-mobile" class="hidden mt-2 p-2 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-3">
                        <img id="preview-img-mobile" src="" class="w-10 h-10 object-cover rounded border border-slate-200">
                        <div class="flex-1 text-[11px] text-slate-500">
                            <div class="font-bold text-slate-800" id="preview-name-mobile">Selected Banner</div>
                            <span class="text-[10px] text-amber-700 font-semibold">Mobile Square (1:1)</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Action Link URL / Destination -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Click Action / Link Destination
                    </label>
                    <select name="action_target" id="action-target-select" onchange="handleTargetSelection(this)" class="admin-select text-xs">
                        <option value="">(None - Display Only)</option>
                        <optgroup label="Categories">
                            <?php foreach($categories_list as $cat): ?>
                                <option value="cat:/category/<?php echo $cat['slug']; ?>">Category: <?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Products">
                            <?php foreach($products_list as $p): ?>
                                <option value="prod:<?php echo $p['id']; ?>:/product/<?php echo $p['slug']; ?>">Product: <?php echo htmlspecialchars($p['name']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <option value="custom">Custom URL Link...</option>
                    </select>

                    <div id="custom-link-box" class="hidden pt-2">
                        <input type="text" name="custom_cta_link" id="custom-cta-link" placeholder="e.g. /shop or https://..." class="admin-input text-xs">
                    </div>
                </div>

                <!-- 5. Accessibility Alt Text -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Alt Text & Image Description
                    </label>
                    <input type="text" name="alt_text" id="alt-text-input" placeholder="e.g. Kashmiri Almonds Harvest Banner" class="admin-input text-xs">
                </div>

                <!-- 6. Display Sequence Order & Active Status -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Sort Sequence <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="sort_order" id="sort-order-input" value="0" min="0" required class="admin-input text-xs font-bold text-center">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Status <span class="text-rose-500">*</span>
                        </label>
                        <select name="is_active" id="is-active-select" class="admin-select text-xs font-semibold">
                            <option value="1">Active (Live)</option>
                            <option value="0">Draft (Offline)</option>
                        </select>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" class="btn-admin btn-admin-primary w-full justify-center text-xs py-2.5 font-bold shadow-xs">
                        <i class="fas fa-save mr-1.5"></i> Save Slide Banner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- IMAGE LIGHTBOX MODAL -->
<div id="image-lightbox" class="fixed inset-0 z-[10000] hidden flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-xs cursor-pointer" onclick="this.classList.add('hidden')">
    <div class="max-w-4xl max-h-[85vh] p-2 bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-4 py-2 border-b border-slate-100 text-xs font-bold text-slate-700">
            <span id="lightbox-title">Image Preview</span>
            <button onclick="document.getElementById('image-lightbox').classList.add('hidden')" class="text-slate-400 hover:text-slate-700"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-2 flex items-center justify-center overflow-auto max-h-[75vh]">
            <img id="lightbox-img" src="" class="max-w-full max-h-[70vh] object-contain rounded-lg">
        </div>
    </div>
</div>

<!-- Wave Settings -->


<div class=" mt-10 admin-card overflow-hidden">
        <div class="px-2 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-sm font-semibold">
                    <i class="fas fa-water"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Organic Wavy Border Divider</h3>
                    <p class="text-xs text-slate-500">Fine-tune the wave curvature dividing the hero banner from storefront content</p>
                </div>
            </div>
            <div class="flex items-center gap-2 self-end sm:self-auto">
                <button type="button" onclick="setWavePreset(75)" class="btn-admin btn-admin-secondary text-xs py-1.5 px-3">
                    Reset (75%)
                </button>
                <button type="button" id="save-wave-btn" onclick="saveWaveIntensity()" class="btn-admin btn-admin-primary text-xs py-1.5 px-3.5">
                    <i class="fas fa-save mr-1"></i> Save Curvature
                </button>
            </div>
        </div>

        <form id="wave-settings-form" method="POST" onsubmit="event.preventDefault(); saveWaveIntensity();" class="p-2">
            <input type="hidden" name="save_wave_settings" value="1">
            <input type="hidden" id="wave_intensity_hidden" name="hero_wave_intensity" value="<?php echo $current_wave_intensity; ?>">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                <!-- Left: Slider & Presets (7 cols) -->
                <div class="lg:col-span-7 space-y-4">
                    <div class="flex items-center justify-between">
                        <label for="wave-slider" class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Wave Amplitude: <span id="wave-desc-badge" class="text-emerald-700 font-semibold normal-case ml-1">Optimal Flow</span>
                        </label>
                        <span id="wave-val-pill" class="text-xs font-bold text-slate-900 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg font-mono">
                            <?php echo $current_wave_intensity; ?>%
                        </span>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center gap-3">
                        <button type="button" onclick="stepWave(-5)" class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 hover:border-slate-300 active:scale-95 flex items-center justify-center text-xs font-bold shrink-0 transition-all shadow-2xs">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="range" min="0" max="100" step="1" id="wave-slider" value="<?php echo $current_wave_intensity; ?>" oninput="onWaveSliderChange(this.value)" class="flex-1 cursor-pointer">
                        <button type="button" onclick="stepWave(5)" class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 hover:border-slate-300 active:scale-95 flex items-center justify-center text-xs font-bold shrink-0 transition-all shadow-2xs">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <!-- Presets Chips -->
                    <div class="grid grid-cols-4 gap-2">
                        <button type="button" onclick="setWavePreset(0)" class="preset-btn p-2 rounded-lg border border-slate-200 text-left hover:bg-slate-50 text-xs transition <?php echo $current_wave_intensity == 0 ? 'active' : ''; ?>" data-val="0">
                            <span class="font-bold text-slate-800 block text-[11px]">0% Flat</span>
                            <span class="text-[10px] text-slate-400 block truncate">Straight Edge</span>
                        </button>
                        <button type="button" onclick="setWavePreset(45)" class="preset-btn p-2 rounded-lg border border-slate-200 text-left hover:bg-slate-50 text-xs transition <?php echo $current_wave_intensity == 45 ? 'active' : ''; ?>" data-val="45">
                            <span class="font-bold text-slate-800 block text-[11px]">45% Subtle</span>
                            <span class="text-[10px] text-slate-400 block truncate">Soft Ripple</span>
                        </button>
                        <button type="button" onclick="setWavePreset(75)" class="preset-btn p-2 rounded-lg border border-slate-200 text-left hover:bg-slate-50 text-xs transition <?php echo $current_wave_intensity == 75 ? 'active' : ''; ?>" data-val="75">
                            <span class="font-bold text-slate-800 block text-[11px]">75% Classic</span>
                            <span class="text-[10px] text-slate-400 block truncate">Recommended</span>
                        </button>
                        <button type="button" onclick="setWavePreset(95)" class="preset-btn p-2 rounded-lg border border-slate-200 text-left hover:bg-slate-50 text-xs transition <?php echo $current_wave_intensity == 95 ? 'active' : ''; ?>" data-val="95">
                            <span class="font-bold text-slate-800 block text-[11px]">95% Deep</span>
                            <span class="text-[10px] text-slate-400 block truncate">High Crest</span>
                        </button>
                    </div>
                </div>

                <!-- Right: Mini Preview (5 cols) -->
                <div class="lg:col-span-5">
                    <div class="rounded-xl border border-slate-200 overflow-hidden bg-slate-900 shadow-xs">
                        <div class="px-3 py-1.5 bg-slate-800/80 border-b border-slate-700/80 flex items-center justify-between text-[11px] text-slate-300 font-medium">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Live Wave Preview
                            </span>
                            <span id="preview-indicator" class="text-emerald-400 font-bold">Synchronized</span>
                        </div>
                        <div class="h-28 relative flex items-end overflow-hidden" style="background: linear-gradient(135deg, #072a24 0%, #004f42 100%);">
                            <svg class="w-full absolute bottom-0 left-0" viewBox="0 0 1440 320" preserveAspectRatio="none" style="height: 60px;">
                                <path id="preview-cream-wave" d="<?php echo htmlspecialchars($hero_wave_preview['cream_path']); ?>" fill="#FFFEDC" fill-opacity="0.95"></path>
                                <path id="preview-white-wave" d="<?php echo htmlspecialchars($hero_wave_preview['white_path']); ?>" fill="#FFFFFF"></path>
                            </svg>
                        </div>
                        <div class="bg-white py-2 px-3 flex items-center justify-between text-[10px] text-slate-500 font-semibold border-t border-slate-100">
                            <span>Storefront Content Zone</span>
                            <span class="text-emerald-700">Dividing Boundary</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

<script>
// --- TOAST HELPER ---
function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.textContent = msg;
        toast.className = `fixed top-6 right-6 z-[9999] flex items-center gap-3 px-2 py-3 rounded-xl text-white font-bold text-xs shadow-xl transition-all ${type === 'success' ? 'bg-[#004f42]' : 'bg-rose-600'}`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    } else {
        alert(msg);
    }
}

// --- MODAL CONTROLS ---
function openAddSlideModal() {
    document.getElementById('slide-form').reset();
    document.getElementById('modal-slide-id').value = '0';
    document.getElementById('modal-title').textContent = 'Add New Hero Banner Slide';
    document.getElementById('sort-order-input').value = '<?php echo $next_sort; ?>';
    document.getElementById('custom-link-box').classList.add('hidden');
    
    ['desktop', 'tablet', 'mobile'].forEach(type => {
        document.getElementById(`preview-box-${type}`).classList.add('hidden');
        document.getElementById(`preview-img-${type}`).src = '';
    });
    
    const modal = document.getElementById('slide-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function openEditSlideModal(data) {
    document.getElementById('slide-form').reset();
    document.getElementById('modal-slide-id').value = data.id || 0;
    document.getElementById('modal-title').textContent = 'Edit Hero Slide #' + data.id;
    
    document.getElementById('desktop-image-path').value = data.image || '';
    document.getElementById('tablet-image-path').value = data.image_tablet || '';
    document.getElementById('mobile-image-path').value = data.image_mobile || '';
    document.getElementById('alt-text-input').value = data.alt_text || data.title || '';
    document.getElementById('sort-order-input').value = data.sort_order || 0;
    document.getElementById('is-active-select').value = data.is_active !== undefined ? data.is_active : 1;

    if (data.image) showPreviewBox('desktop', data.image, 'Current Desktop Banner');
    if (data.image_tablet) showPreviewBox('tablet', data.image_tablet, 'Current Tablet Banner');
    if (data.image_mobile) showPreviewBox('mobile', data.image_mobile, 'Current Mobile Banner');

    const select = document.getElementById('action-target-select');
    const customBox = document.getElementById('custom-link-box');
    const customInput = document.getElementById('custom-cta-link');

    let matched = false;
    if (data.product_id) {
        const val = `prod:${data.product_id}:${data.cta_link}`;
        for (let opt of select.options) {
            if (opt.value === val) {
                select.value = val;
                matched = true;
                break;
            }
        }
    }
    if (!matched && data.cta_link) {
        for (let opt of select.options) {
            if (opt.value.endsWith(data.cta_link)) {
                select.value = opt.value;
                matched = true;
                break;
            }
        }
    }
    if (!matched && data.cta_link) {
        select.value = 'custom';
        customInput.value = data.cta_link;
        customBox.classList.remove('hidden');
    } else {
        customBox.classList.add('hidden');
    }

    const modal = document.getElementById('slide-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeSlideModal() {
    const modal = document.getElementById('slide-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function handleTargetSelection(sel) {
    const customBox = document.getElementById('custom-link-box');
    if (sel.value === 'custom') {
        customBox.classList.remove('hidden');
    } else {
        customBox.classList.add('hidden');
    }
}

function handleFileSelected(input, type) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            showPreviewBox(type, e.target.result, file.name);
            document.getElementById(`${type}-image-path`).value = `assets/images/uploads/${file.name}`;
        };
        reader.readAsDataURL(file);
    }
}

function showPreviewBox(type, url, name) {
    const box = document.getElementById(`preview-box-${type}`);
    const img = document.getElementById(`preview-img-${type}`);
    const lbl = document.getElementById(`preview-name-${type}`);
    if (box && img) {
        img.src = url.startsWith('data:') ? url : (url.startsWith('http') ? url : '../' + url.replace(/^\.\//, ''));
        if (lbl) lbl.textContent = name || 'Selected';
        box.classList.remove('hidden');
    }
}

function viewLargeImage(url, title) {
    if (!url) return;
    document.getElementById('lightbox-img').src = url;
    document.getElementById('lightbox-title').textContent = title || 'Banner Preview';
    document.getElementById('image-lightbox').classList.remove('hidden');
}

// --- DRAG & DROP SORTABLEJS ---
document.addEventListener('DOMContentLoaded', () => {
    // Auto-open modal if navigated via New Slide action button
    if (window.location.search.includes('action=new') || window.location.hash === '#new' || window.location.hash === '#add-slide') {
        openAddSlideModal();
    }

    const tbody = document.getElementById('sortable-slides');
    if (tbody && tbody.querySelectorAll('tr[data-id]').length > 1) {
        new Sortable(tbody, {
            handle: '.drag-handle',
            animation: 180,
            ghostClass: 'bg-emerald-50',
            onEnd: function() {
                const order = [];
                tbody.querySelectorAll('tr[data-id]').forEach((row, index) => {
                    const id = row.getAttribute('data-id');
                    order.push({ id: id, sort_order: index });
                    const sortBadge = row.querySelector('.sort-number');
                    if (sortBadge) sortBadge.textContent = index;
                });
                
                fetch('hero_slides.php?action=reorder', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order: order })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('Slide sequence updated successfully!');
                    } else {
                        showToast('Failed to save slide order.', 'error');
                    }
                })
                .catch(() => showToast('Error saving slide order.', 'error'));
            }
        });
    }

    // Bulk selection logic
    const selectAll = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkBar = document.getElementById('bulk-action-bar');
    const selectedCount = document.getElementById('selected-count');

    window.updateBulkBar = function() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        if (checked.length > 0) {
            bulkBar.classList.remove('hidden');
            bulkBar.classList.add('flex');
            selectedCount.textContent = checked.length;
        } else {
            bulkBar.classList.add('hidden');
            bulkBar.classList.remove('flex');
        }
    };

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            rowCheckboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkBar();
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            updateBulkBar();
            if (!cb.checked && selectAll) selectAll.checked = false;
        });
    });
});

async function submitBulkDelete() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    if (checked.length === 0) return;
    if (confirm(`Are you sure you want to delete ${checked.length} selected slides?`)) {
        document.getElementById('bulk-form').submit();
    }
}

async function toggleSlideStatus(id, btn) {
    try {
        const res = await fetch(`hero_slides.php?action=toggle&id=${id}`);
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        }
    } catch(e) {
        console.error(e);
    }
}

// =========================================================================
// REAL-TIME WAVY BORDER SLIDER & LIVE PREVIEW CONTROLLER
// =========================================================================
function generateWavePathJs(intensity, isCream) {
    const f = intensity / 100.0;
    const B = 145;
    const offset = isCream ? -20 : 0;
    const points = [
        ['M', 0, 5],
        ['C', 80, 65, 130, 90, 220, 85],
        ['C', 320, 80, 370, -80, 480, -85],
        ['C', 590, -90, 640, 80, 750, 75],
        ['C', 860, 70, 910, -90, 1020, -95],
        ['C', 1130, -100, 1180, 70, 1280, 65],
        ['C', 1360, 60, 1400, 0, 1440, -25],
    ];
    let d = '';
    points.forEach(p => {
        if (p[0] === 'M') {
            const y = Math.round(B + (p[2] + offset) * f);
            d += `M${p[1]},${y} `;
        } else if (p[0] === 'C') {
            const y1 = Math.round(B + (p[2] + offset) * f);
            const y2 = Math.round(B + (p[4] + offset) * f);
            const y3 = Math.round(B + (p[6] + offset) * f);
            d += `C${p[1]},${y1} ${p[3]},${y2} ${p[5]},${y3} `;
        }
    });
    d += 'L1440,320 L0,320 Z';
    return d.trim();
}

function getWaveDescription(val) {
    val = parseInt(val) || 0;
    if (val === 0) return 'Flat Divider';
    if (val <= 30) return 'Subtle Ripples';
    if (val <= 60) return 'Balanced Flow';
    if (val <= 85) return 'Optimal Curvature';
    return 'Deep Crests';
}

function onWaveSliderChange(val) {
    val = Math.max(0, Math.min(100, parseInt(val) || 0));
    const slider = document.getElementById('wave-slider');
    if (slider) slider.style.setProperty('--fill-pct', `${val}%`);

    const hiddenInput = document.getElementById('wave_intensity_hidden');
    const valPill = document.getElementById('wave-val-pill');
    const descBadge = document.getElementById('wave-desc-badge');
    const indicator = document.getElementById('preview-indicator');
    
    if (hiddenInput) hiddenInput.value = val;
    if (valPill) valPill.textContent = val + '%';
    if (descBadge) descBadge.textContent = getWaveDescription(val);

    // Update preset chips active state
    document.querySelectorAll('.preset-btn').forEach(btn => {
        if (parseInt(btn.getAttribute('data-val')) === parseInt(val)) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    if (indicator) {
        indicator.textContent = 'Unsaved...';
        indicator.className = 'text-amber-400 font-bold';
    }

    const whitePath = generateWavePathJs(val, false);
    const creamPath = generateWavePathJs(val, true);
    
    const previewWhite = document.getElementById('preview-white-wave');
    const previewCream = document.getElementById('preview-cream-wave');
    if (previewWhite) previewWhite.setAttribute('d', whitePath);
    if (previewCream) {
        if (val === 0) {
            previewCream.setAttribute('visibility', 'hidden');
        } else {
            previewCream.setAttribute('visibility', 'visible');
            previewCream.setAttribute('d', creamPath);
        }
    }
}

function stepWave(delta) {
    const slider = document.getElementById('wave-slider');
    if (!slider) return;
    let val = (parseInt(slider.value) || 0) + delta;
    val = Math.max(0, Math.min(100, val));
    slider.value = val;
    onWaveSliderChange(val);
}

function setWavePreset(val) {
    const slider = document.getElementById('wave-slider');
    if (slider) {
        slider.value = val;
        onWaveSliderChange(val);
        saveWaveIntensity(true);
    }
}

function saveWaveIntensity(isAuto = false) {
    const slider = document.getElementById('wave-slider');
    if (!slider) return;
    const val = Math.max(0, Math.min(100, parseInt(slider.value) || 0));
    const btn = document.getElementById('save-wave-btn');
    const indicator = document.getElementById('preview-indicator');
    
    if (btn && !isAuto) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
    }

    fetch('hero_slides.php?action=save_wave', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ intensity: val })
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.success) {
            if (indicator) {
                indicator.textContent = 'Synchronized (' + val + '%)';
                indicator.className = 'text-emerald-400 font-bold';
            }
            showToast('Hero wave amplitude updated to ' + val + '%!');
        } else {
            showToast('Failed to save wave setting.', 'error');
        }
    })
    .catch(() => showToast('Network error saving wave setting.', 'error'))
    .finally(() => {
        if (btn && !isAuto) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save mr-1"></i> Save Curvature';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    onWaveSliderChange(<?php echo (int)$current_wave_intensity; ?>);
});
</script>

<?php include 'includes/footer.php'; ?>
