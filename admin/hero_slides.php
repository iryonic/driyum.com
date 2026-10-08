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

        foreach ($upload_map as $file_key => &$cfg) {
            if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION);
                $clean_filename = $cfg['prefix'] . uniqid() . '.' . strtolower($ext);
                $dest = $upload_dir . $clean_filename;
                if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $dest)) {
                    $cfg['var'] = "assets/images/uploads/" . $clean_filename;
                }
            }
        }

        if (empty($image_desktop)) {
            $err = "Desktop banner image is required.";
        } else {
            $title = $alt_text ?: "Hero Banner Slide";

            if ($slide_id <= 0) {
                // INSERT
                $stmt = $conn->prepare("INSERT INTO hero_slides (title, alt_text, image, image_tablet, image_mobile, cta_link, product_id, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssiii", $title, $alt_text, $image_desktop, $image_tablet, $image_mobile, $cta_link, $product_id, $sort_order, $is_active);
                if ($stmt->execute()) {
                    $_SESSION['msg'] = "New hero banner slide created successfully!";
                    header("Location: hero_slides.php");
                    exit;
                } else {
                    $err = "Database error: " . $stmt->error;
                }
            } else {
                // UPDATE
                $stmt = $conn->prepare("UPDATE hero_slides SET title = ?, alt_text = ?, image = ?, image_tablet = ?, image_mobile = ?, cta_link = ?, product_id = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("ssssssiiii", $title, $alt_text, $image_desktop, $image_tablet, $image_mobile, $cta_link, $product_id, $sort_order, $is_active, $slide_id);
                if ($stmt->execute()) {
                    $_SESSION['msg'] = "Hero banner slide updated successfully!";
                    header("Location: hero_slides.php");
                    exit;
                } else {
                    $err = "Database error: " . $stmt->error;
                }
            }
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

<div class="max-w-100 mx-auto space-y-6">

    <!-- Toast Notification Container -->
    <div id="toast" class="fixed top-6 right-6 z-[9999] hidden flex items-center gap-3 px-5 py-3.5 rounded-2xl text-white font-bold text-xs shadow-2xl transition-all duration-300"></div>

    <?php if(!empty($_SESSION['msg'])): ?>
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600 text-base"></i>
                <span><?php echo htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-800"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <?php if(!empty($err)): ?>
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs font-bold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-circle text-red-600 text-base"></i>
                <span><?php echo htmlspecialchars($err); ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-800"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <!-- HEADER TITLE & ADD BUTTON -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 font-heading tracking-tight">Hero Slides</h1>
            <p class="text-xs font-medium text-gray-500 mt-1">Manage responsive carousel banners, target links, and display order</p>
        </div>
        <button onclick="openAddSlideModal()" class="inline-flex items-center gap-2 bg-[#24B25D] hover:bg-[#004F42] text-white px-6 py-3.5 rounded-2xl font-black text-xs uppercase tracking-wider transition-all duration-300 shadow-lg shadow-[#24B25D]/20 active:scale-95">
            <i class="fas fa-plus text-sm"></i>
            <span>Add New Slide</span>
        </button>
    </div>

    <!-- METRICS CARDS (SCREENSHOT 1) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <!-- Total Hero Slides -->
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-2xl border border-sky-100/80">
                <i class="fas fa-images"></i>
            </div>
            <div>
                <div class="text-3xl font-black text-gray-900 font-heading leading-none"><?php echo $total_slides; ?></div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1.5">Total Hero Slides</div>
            </div>
        </div>

        <!-- Active / Live -->
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-[#24B25D] flex items-center justify-center text-2xl border border-emerald-100/80">
                <i class="fas fa-check"></i>
            </div>
            <div>
                <div class="text-3xl font-black text-gray-900 font-heading leading-none"><?php echo $active_slides; ?></div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1.5">Active / Live</div>
            </div>
        </div>
    </div>

    <!-- WAVY BORDER DIVIDER CUSTOMIZER (INDUSTRY LEVEL RANGE BAR) -->
    <style>
        #wave-slider {
            --fill-pct: <?php echo (int)$current_wave_intensity; ?>%;
            -webkit-appearance: none;
            appearance: none;
            background: linear-gradient(to right, #24B25D 0%, #24B25D var(--fill-pct), #E2E8F0 var(--fill-pct), #E2E8F0 100%);
        }
        #wave-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #ffffff;
            border: 4px solid #24B25D;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(36, 178, 93, 0.45), 0 0 0 4px rgba(36, 178, 93, 0.12);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        #wave-slider::-webkit-slider-thumb:hover {
            transform: scale(1.15);
            box-shadow: 0 6px 18px rgba(36, 178, 93, 0.55), 0 0 0 6px rgba(36, 178, 93, 0.18);
        }
        #wave-slider::-webkit-slider-thumb:active {
            transform: scale(0.95);
            box-shadow: 0 2px 8px rgba(36, 178, 93, 0.6);
        }
        #wave-slider::-moz-range-thumb {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #ffffff;
            border: 4px solid #24B25D;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(36, 178, 93, 0.45);
        }
        .preview-viewport-trans {
            transition: max-width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
    <div class="bg-white rounded-3xl p-5 sm:p-7 md:p-8 border border-slate-200/90 shadow-sm relative overflow-hidden transition-all duration-300">
        <!-- Ambient decorative corner glows -->
        <div class="absolute -top-24 -right-24 w-72 h-72 bg-gradient-to-br from-[#24B25D]/15 via-emerald-100/30 to-transparent rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-gradient-to-tr from-amber-100/20 to-transparent rounded-full blur-3xl pointer-events-none"></div>

        <form id="wave-settings-form" method="POST" onsubmit="event.preventDefault(); saveWaveIntensity();">
            <input type="hidden" name="save_wave_settings" value="1">
            <input type="hidden" id="wave_intensity_hidden" name="hero_wave_intensity" value="<?php echo $current_wave_intensity; ?>">
            
            <!-- Card Header: Fully Responsive Stacking -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-6 border-b border-gray-100 relative z-10">
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100/90 text-[#24B25D] flex items-center justify-center text-xl shrink-0 border border-emerald-200/70 shadow-xs">
                        <i class="fas fa-water"></i>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base sm:text-lg font-black text-gray-900 font-heading tracking-tight">Wavy Border   </h2>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-[#004F42] border border-emerald-200 shadow-2xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#24B25D] animate-ping"></span>
                                Live Storefront Sync
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 font-medium mt-0.5 max-w-2xl">Tailor the curvature and depth of the organic wave dividing the hero banner from the storefront content below.</p>
                    </div>
                </div>

                <!-- Action Controls: Responsive Stacking with tactile buttons -->
                <div class="flex items-center gap-2.5 sm:self-auto shrink-0 w-full sm:w-auto justify-end">
                    <button type="button" onclick="setWavePreset(75)" class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:text-black hover:bg-gray-50 transition active:scale-95 flex items-center justify-center gap-1.5 shadow-2xs">
                        <i class="fas fa-undo-alt text-[10px] text-gray-400"></i>
                        <span>Reset (75%)</span>
                    </button>
                    <button type="button" id="save-wave-btn" onclick="saveWaveIntensity()" class="flex-1 sm:flex-initial px-5 py-2.5 rounded-xl bg-[#24B25D] hover:bg-[#004F42] text-white text-xs font-black uppercase tracking-wider transition shadow-lg shadow-emerald-500/20 flex items-center justify-center gap-2 active:scale-95">
                        <i class="fas fa-check"></i>
                        <span>Save Waviness</span>
                    </button>
                </div>
            </div>

            <!-- Card Body: Responsive 2-Column Grid -->
            <div class="pt-6 grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start relative z-10">
                <!-- Left Column (7 cols): Controls & Slider -->
                <div class="lg:col-span-7 space-y-6">
                    <div>
                        <!-- Slider Metadata Header -->
                        <div class="flex flex-col xs:flex-row xs:items-center justify-between gap-2 mb-3">
                            <div>
                                <label for="wave-slider" class="text-xs font-black text-gray-900 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>Wave Amplitude</span>
                                    <span class="text-gray-400 font-medium text-[11px] hidden sm:inline">(0% Flat &rarr; 100% Deep)</span>
                                </label>
                            </div>
                            <div class="flex items-center gap-2 self-start xs:self-auto">
                                <span id="wave-desc-badge" class="text-xs font-black text-emerald-800 bg-emerald-50/90 border border-emerald-200/80 px-3 py-1 rounded-xl shadow-2xs">
                                    Dynamic & Curvy
                                </span>
                                <span id="wave-val-pill" class="text-base sm:text-lg font-black text-black font-mono bg-gray-50 border border-gray-200 px-3.5 py-0.5 rounded-xl shadow-2xs min-w-[66px] text-center">
                                    <?php echo $current_wave_intensity; ?>%
                                </span>
                            </div>
                        </div>

                        <!-- Industry Level Range Bar with Steppers for Precision & Mobile Ergonomics -->
                        <div class="bg-gray-50/80 rounded-2xl p-3 sm:p-4 border border-gray-200/70 shadow-2xs">
                            <div class="flex items-center gap-2.5 sm:gap-3.5">
                                <!-- Decrement Stepper Button -->
                                <button type="button" 
                                        onclick="stepWave(-5)" 
                                        title="Decrease 5%"
                                        aria-label="Decrease waviness by 5%"
                                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-white border border-gray-200 text-gray-600 hover:text-black hover:border-gray-400 flex items-center justify-center text-xs font-bold shrink-0 transition active:scale-90 shadow-2xs">
                                    <i class="fas fa-minus text-[10px]"></i>
                                </button>

                                <!-- Interactive Slider -->
                                <div class="flex-1 relative py-1">
                                    <input type="range" 
                                           id="wave-slider" 
                                           min="0" 
                                           max="100" 
                                           step="1" 
                                           value="<?php echo $current_wave_intensity; ?>" 
                                           oninput="onWaveSliderChange(this.value)"
                                           onchange="saveWaveIntensity(true)"
                                           class="w-full h-3.5 rounded-lg appearance-none cursor-pointer focus:outline-none transition-all">
                                </div>

                                <!-- Increment Stepper Button -->
                                <button type="button" 
                                        onclick="stepWave(5)" 
                                        title="Increase 5%"
                                        aria-label="Increase waviness by 5%"
                                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-white border border-gray-200 text-gray-600 hover:text-black hover:border-gray-400 flex items-center justify-center text-xs font-bold shrink-0 transition active:scale-90 shadow-2xs">
                                    <i class="fas fa-plus text-[10px]"></i>
                                </button>
                            </div>

                            <!-- Milestone Markers / Ticks with Clickable Anchors -->
                            <div class="flex justify-between items-center text-[10px] font-bold text-gray-400 mt-3 px-1 select-none">
                                <button type="button" onclick="setWavePreset(0)" class="hover:text-black transition flex flex-col items-center gap-0.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                    <span>0% Flat</span>
                                </button>
                                <button type="button" onclick="setWavePreset(25)" class="hover:text-black transition hidden xs:flex flex-col items-center gap-0.5">
                                    <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                    <span>25%</span>
                                </button>
                                <button type="button" onclick="setWavePreset(50)" class="hover:text-black transition flex flex-col items-center gap-0.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                    <span>50%</span>
                                </button>
                                <button type="button" onclick="setWavePreset(75)" class="text-[#24B25D] hover:text-[#004F42] transition flex flex-col items-center gap-0.5 font-black">
                                    <span class="w-2 h-2 rounded-full bg-[#24B25D]"></span>
                                    <span>75% ★ Ideal</span>
                                </button>
                                <button type="button" onclick="setWavePreset(100)" class="hover:text-black transition flex flex-col items-center gap-0.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                    <span>100% Max</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Instant Preset Cards (Responsive Grid) -->
                    <div>
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Curated Style Presets</span>
                            <span class="text-[10px] text-gray-400 font-medium hidden sm:inline">Click any preset to apply instantly</span>
                        </div>
                        <div class="grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-5 gap-2 sm:gap-2.5">
                            <button type="button" data-val="0" onclick="setWavePreset(0)" class="preset-btn p-2.5 rounded-2xl border border-gray-200 text-left transition hover:border-gray-400 hover:bg-gray-50 active:scale-95 shadow-2xs group">
                                <div class="preset-val text-xs font-black text-gray-900 flex items-center justify-between">
                                    <span>0%</span>
                                    <i class="preset-ico fas fa-minus text-[9px] text-gray-400 group-hover:text-gray-700"></i>
                                </div>
                                <div class="preset-lbl text-[10px] font-bold text-gray-500 mt-0.5 leading-tight truncate">Flat Line</div>
                            </button>
                            
                            <button type="button" data-val="30" onclick="setWavePreset(30)" class="preset-btn p-2.5 rounded-2xl border border-gray-200 text-left transition hover:border-gray-400 hover:bg-gray-50 active:scale-95 shadow-2xs group">
                                <div class="preset-val text-xs font-black text-gray-900 flex items-center justify-between">
                                    <span>30%</span>
                                    <i class="preset-ico fas fa-water text-[9px] text-gray-400 group-hover:text-gray-700"></i>
                                </div>
                                <div class="preset-lbl text-[10px] font-bold text-gray-500 mt-0.5 leading-tight truncate">Subtle Flow</div>
                            </button>

                            <button type="button" data-val="60" onclick="setWavePreset(60)" class="preset-btn p-2.5 rounded-2xl border border-gray-200 text-left transition hover:border-gray-400 hover:bg-gray-50 active:scale-95 shadow-2xs group">
                                <div class="preset-val text-xs font-black text-gray-900 flex items-center justify-between">
                                    <span>60%</span>
                                    <i class="preset-ico fas fa-wind text-[9px] text-gray-400 group-hover:text-gray-700"></i>
                                </div>
                                <div class="preset-lbl text-[10px] font-bold text-gray-500 mt-0.5 leading-tight truncate">Balanced</div>
                            </button>

                            <button type="button" data-val="75" onclick="setWavePreset(75)" class="preset-btn p-2.5 rounded-2xl border border-emerald-400 bg-emerald-50 text-[#004F42] text-left transition shadow-xs active:scale-95 group ring-2 ring-emerald-500/20">
                                <div class="preset-val text-xs font-black text-[#004F42] flex items-center justify-between">
                                    <span>75%</span>
                                    <i class="preset-ico fas fa-star text-[9px] text-emerald-500"></i>
                                </div>
                                <div class="preset-lbl text-[10px] font-black text-emerald-700 mt-0.5 leading-tight truncate">Golden ★</div>
                            </button>

                            <button type="button" data-val="100" onclick="setWavePreset(100)" class="preset-btn p-2.5 rounded-2xl border border-gray-200 text-left transition hover:border-gray-400 hover:bg-gray-50 active:scale-95 shadow-2xs group">
                                <div class="preset-val text-xs font-black text-gray-900 flex items-center justify-between">
                                    <span>100%</span>
                                    <i class="preset-ico fas fa-mountain text-[9px] text-gray-400 group-hover:text-gray-700"></i>
                                </div>
                                <div class="preset-lbl text-[10px] font-bold text-gray-500 mt-0.5 leading-tight truncate">Deep Crests</div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Column (5 cols): Live Simulated Visual Preview Window -->
                <div class="lg:col-span-5 w-full">
                    <div class="rounded-2xl border border-gray-200/90 bg-gray-950 overflow-hidden shadow-md relative">
                        <!-- Preview Studio Header with Window Controls & Viewport Switcher -->
                        <div class="px-3.5 py-2.5 border-b border-white/10 flex items-center justify-between bg-black/60 backdrop-blur-md">
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-red-500/80"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500/80"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></span>
                                </div>
                                <span class="text-[10px] font-mono text-gray-400 ml-1.5 hidden sm:inline">driyum.com/hero</span>
                            </div>

                            <!-- Responsive Viewport Switcher (Desktop vs Mobile Preview) -->
                            <div class="flex items-center gap-1 bg-white/10 p-0.5 rounded-lg">
                                <button type="button" 
                                        id="vp-desktop-btn" 
                                        onclick="setPreviewViewport('desktop')" 
                                        title="Preview widescreen desktop view"
                                        class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-white text-gray-900 shadow-2xs transition">
                                    <i class="fas fa-desktop text-[9px] mr-1"></i> Desktop
                                </button>
                                <button type="button" 
                                        id="vp-mobile-btn" 
                                        onclick="setPreviewViewport('mobile')" 
                                        title="Preview mobile screen view"
                                        class="px-2 py-0.5 rounded-md text-[10px] font-bold text-gray-400 hover:text-white transition">
                                    <i class="fas fa-mobile-alt text-[9px] mr-1"></i> Mobile
                                </button>
                            </div>
                        </div>

                        <!-- Preview Canvas Area -->
                        <div class="bg-gray-900/60 p-2 sm:p-3 overflow-hidden">
                            <div id="preview-wrapper" class="preview-viewport-trans w-full mx-auto rounded-xl overflow-hidden border border-white/10 shadow-inner">
                                <!-- Simulated Hero Slide Stage -->
                                <div class="relative h-28 sm:h-32 md:h-36 bg-gradient-to-br from-[#004F42] via-[#043329] to-[#011a14] flex flex-col justify-center items-center overflow-hidden">
                                    <!-- Ambient mock elements -->
                                    <div class="absolute top-2 left-3 flex items-center gap-1.5 pointer-events-none select-none opacity-40">
                                        <span class="text-[8px] font-black text-amber-200 uppercase tracking-widest bg-amber-400/20 px-1.5 py-0.5 rounded border border-amber-300/30">100% Organic</span>
                                    </div>
                                    <div class="text-center pointer-events-none select-none z-0 px-4">
                                        <span class="text-white/40 text-[11px] sm:text-xs font-black uppercase tracking-[0.2em] block font-heading">Hero Slider Banner</span>
                                        <span class="text-white/20 text-[9px] font-medium tracking-wider">Dynamic Organic Wave Dividing Edge</span>
                                    </div>

                                    <!-- Live Morphing SVG Waves inside Preview -->
                                    <div class="absolute bottom-0 left-0 w-full leading-none pointer-events-none z-10 translate-y-[2px]">
                                        <svg class="w-full h-14 sm:h-16 md:h-20 block" viewBox="0 0 1440 320" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                                            <defs>
                                                <!-- Depth Drop Shadow inside Preview -->
                                                <filter id="previewWaveShadow" x="-5%" y="-35%" width="110%" height="170%" filterUnits="userSpaceOnUse">
                                                    <feDropShadow dx="0" dy="-3" stdDeviation="4" flood-color="#000000" flood-opacity="0.18" />
                                                </filter>
                                            </defs>
                                            <path id="preview-cream-wave" d="<?php echo htmlspecialchars($hero_wave_preview['cream_path']); ?>" fill="#FFFEDC" fill-opacity="0.95" filter="url(#previewWaveShadow)"></path>
                                            <path id="preview-white-wave" d="<?php echo htmlspecialchars($hero_wave_preview['white_path']); ?>" fill="#FFFFFF"></path>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Simulated Storefront Content Row Directly Underneath Wave -->
                                <div class="bg-white py-2 px-3 flex items-center justify-between border-t border-gray-100 select-none">
                                    <span class="text-[9px] font-black text-gray-500 uppercase tracking-wider">Now Available At</span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[8px] font-bold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">Nature's Basket</span>
                                        <span class="text-[8px] font-bold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded hidden xs:inline">Blinkit</span>
                                        <span class="text-[8px] font-bold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">Zepto</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preview Status Bar -->
                        <div class="bg-black/80 px-3.5 py-2 flex items-center justify-between text-[10px] text-gray-400 border-t border-white/5">
                            <span class="flex items-center gap-1.5">
                                <i class="fas fa-circle text-[6px] text-emerald-400 animate-pulse"></i>
                                <span class="font-medium text-gray-300">Live Preview</span>
                            </span>
                            <span id="preview-indicator" class="text-emerald-400 font-bold">Synchronized</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="bg-amber-50/60 rounded-3xl p-6 border border-amber-200/60 shadow-sm relative overflow-hidden">
        <div class="flex items-start gap-4">
            <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                <i class="fas fa-info text-xs"></i>
            </div>
            <div class="space-y-2 text-xs text-amber-950/80 leading-relaxed">
                <p class="font-bold text-amber-900">
                    <span class="font-black text-amber-950">Required Hero Banner Image Dimensions:</span>
                    To ensure crisp representation without distortion or layout breakage on Retina / high-resolution screens, please prepare banners to the following specifications before selecting:
                </p>
                <ul class="space-y-1.5 pl-4 list-disc text-amber-900/90 font-medium text-[11.5px]">
                    <li><strong class="font-black text-gray-900">Desktop size (>= 992px):</strong> Aspect ratio ~2.18:1. Recommended size: <code class="bg-white/80 px-1.5 py-0.5 rounded font-mono font-bold text-amber-900 border border-amber-200/80">1350×620 px</code> (or 1920×880 px)</li>
                    <li><strong class="font-black text-gray-900">Tablet size (577px – 992px):</strong> Aspect ratio 16:10. Recommended size: <code class="bg-white/80 px-1.5 py-0.5 rounded font-mono font-bold text-amber-900 border border-amber-200/80">1024×640 px</code></li>
                    <li><strong class="font-black text-gray-900">Mobile size (<= 576px):</strong> Aspect ratio 1:1 (Square). Recommended size: <code class="bg-white/80 px-1.5 py-0.5 rounded font-mono font-bold text-amber-900 border border-amber-200/80">768×768 px</code> (or 1024×1024 px)</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- MAIN CAROUSEL BANNERS TABLE (SCREENSHOT 1) -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        
        <!-- Table Header Bar -->
        <div class="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/40">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-[#24B25D]/10 text-[#24B25D] flex items-center justify-center text-sm">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-gray-900 font-heading">Homepage Carousel Banners</h3>
                    <p class="text-[10px] text-gray-400 font-medium">Grab the handle on any row to drag & reorder sequence in real-time</p>
                </div>
            </div>
            
            <div id="bulk-action-bar" class="hidden flex items-center gap-2">
                <span id="selected-count" class="text-xs font-bold text-gray-600">0 selected</span>
                <button type="button" onclick="submitBulkDelete()" class="px-3.5 py-1.5 rounded-xl bg-red-50 text-red-600 border border-red-200 text-xs font-bold hover:bg-red-600 hover:text-white transition">
                    <i class="fas fa-trash-alt mr-1"></i> Delete Selected
                </button>
            </div>
        </div>

        <form id="bulk-form" method="POST">
            <input type="hidden" name="bulk_delete" value="1">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-[9.5px] font-black text-gray-400 uppercase tracking-wider bg-gray-50/20">
                            <th class="py-4 pl-6 pr-2 w-10 text-center">
                                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-[#24B25D] focus:ring-[#24B25D]">
                            </th>
                            <th class="py-4 px-3 w-16 text-center">Sort</th>
                            <th class="py-4 px-4 min-w-[280px]">Responsive Banner Images</th>
                            <th class="py-4 px-4 min-w-[180px]">Target Action Link</th>
                            <th class="py-4 px-4 min-w-[220px]">Alt Description</th>
                            <th class="py-4 px-3 text-center w-24">Status</th>
                            <th class="py-4 pr-6 pl-3 text-right w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-slides" class="divide-y divide-gray-50 text-xs">
                        <?php if(empty($slides)): ?>
                            <tr>
                                <td colspan="7" class="py-14 text-center">
                                    <div class="w-16 h-16 rounded-full bg-gray-50 text-gray-300 mx-auto flex items-center justify-center text-2xl mb-3">
                                        <i class="fas fa-images"></i>
                                    </div>
                                    <p class="font-bold text-gray-700 text-sm">No Hero Banner Slides Found</p>
                                    <p class="text-gray-400 text-xs mt-1">Get started by creating your first responsive banner slide.</p>
                                    <button type="button" onclick="openAddSlideModal()" class="mt-4 px-5 py-2.5 rounded-xl bg-[#24B25D] text-white font-bold text-xs hover:bg-[#004F42] transition">
                                        + Add Your First Slide
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
                            <tr class="hover:bg-gray-50/70 transition-colors group cursor-default" data-id="<?php echo $s['id']; ?>">
                                <!-- Checkbox -->
                                <td class="py-4 pl-6 pr-2 text-center">
                                    <input type="checkbox" name="selected_ids[]" value="<?php echo $s['id']; ?>" class="row-checkbox rounded border-gray-300 text-[#24B25D] focus:ring-[#24B25D]">
                                </td>

                                <!-- Sort Handle + Number -->
                                <td class="py-4 px-3 text-center">
                                    <div class="drag-handle inline-flex items-center gap-1.5 cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-800 transition py-1 px-1.5 rounded-lg hover:bg-gray-100">
                                        <i class="fas fa-grip-vertical text-xs"></i>
                                        <span class="sort-number font-black text-gray-700 text-xs"><?php echo (int)$s['sort_order']; ?></span>
                                    </div>
                                </td>

                                <!-- Responsive Banner Images (Desktop, Tablet, Mobile Thumbnails) -->
                                <td class="py-4 px-4">
                                    <div class="flex items-end gap-3">
                                        <!-- Desktop Preview -->
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Desktop</span>
                                            <div class="w-24 h-11 rounded-lg bg-gray-100 border border-gray-200/80 overflow-hidden relative shadow-sm group/img cursor-pointer" onclick="viewLargeImage('<?php echo $desk_img; ?>', 'Desktop (1350x620)')">
                                                <?php if($desk_img): ?>
                                                    <img src="<?php echo $desk_img; ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <div class="w-full h-full flex items-center justify-center text-[8px] text-gray-400 font-bold">No Image</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Tablet Preview -->
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tablet</span>
                                            <div class="w-16 h-10 rounded-lg bg-gray-100 border border-gray-200/80 overflow-hidden relative shadow-sm group/img cursor-pointer" onclick="viewLargeImage('<?php echo $tab_img; ?>', 'Tablet (1024x640)')">
                                                <?php if($tab_img): ?>
                                                    <img src="<?php echo $tab_img; ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <div class="w-full h-full flex items-center justify-center text-[7px] text-gray-300 font-bold">—</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Mobile Preview -->
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Mobile</span>
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 border border-gray-200/80 overflow-hidden relative shadow-sm group/img cursor-pointer" onclick="viewLargeImage('<?php echo $mob_img; ?>', 'Mobile (768x768 / Square)')">
                                                <?php if($mob_img): ?>
                                                    <img src="<?php echo $mob_img; ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <div class="w-full h-full flex items-center justify-center text-[7px] text-gray-300 font-bold">—</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Target Action Link -->
                                <td class="py-4 px-4 font-mono text-[11px]">
                                    <?php if(!empty($disp_link)): ?>
                                        <a href="<?php echo get_url(ltrim($disp_link, '/')); ?>" target="_blank" class="text-emerald-700 hover:text-emerald-900 hover:underline flex items-center gap-1.5 font-bold">
                                            <span class="truncate max-w-[200px]"><?php echo htmlspecialchars($disp_link); ?></span>
                                            <i class="fas fa-external-link-alt text-[9px] text-gray-400"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 font-sans italic text-xs">(None - No Link)</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Alt Description -->
                                <td class="py-4 px-4 text-gray-700 text-xs font-medium">
                                    <span class="line-clamp-2 max-w-[260px]"><?php echo htmlspecialchars($alt_disp); ?></span>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-3 text-center">
                                    <button type="button" onclick="toggleSlideStatus(<?php echo $s['id']; ?>, this)" class="status-btn inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider transition-all <?php echo $s['is_active'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-500 border border-gray-200 hover:bg-gray-200'; ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?php echo $s['is_active'] ? 'bg-emerald-500' : 'bg-gray-400'; ?>"></span>
                                        <span class="status-text"><?php echo $s['is_active'] ? 'Active' : 'Inactive'; ?></span>
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 pr-6 pl-3 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" onclick='openEditSlideModal(<?php echo json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' class="w-8 h-8 rounded-xl bg-gray-50 text-gray-600 hover:bg-[#24B25D] hover:text-white transition flex items-center justify-center text-xs shadow-sm" title="Edit Slide">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button type="button" onclick="confirmDeleteSlide(<?php echo $s['id']; ?>)" class="w-8 h-8 rounded-xl bg-gray-50 text-gray-600 hover:bg-red-500 hover:text-white transition flex items-center justify-center text-xs shadow-sm" title="Delete Slide">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
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

<!-- HIDDEN SINGLE DELETE FORM -->
<form id="single-delete-form" method="POST" class="hidden">
    <input type="hidden" name="delete_slide" value="1">
    <input type="hidden" name="slide_id" id="delete-slide-id" value="0">
</form>

<!-- ADD / EDIT HERO SLIDE MODAL (SCREENSHOT 2) -->
<div id="slide-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeSlideModal()"></div>

    <!-- Modal Box -->
    <div class="bg-white w-full max-w-xl rounded-[2rem] shadow-2xl overflow-hidden border border-gray-100 relative z-10 max-h-[92vh] flex flex-col anim-up">
        
        <!-- Header in Brand Forest Green (#004F42) -->
        <div class="bg-[#004F42] px-7 py-5 flex items-center justify-between text-white shrink-0">
            <h3 id="modal-title" class="text-lg font-black font-heading tracking-tight">Add New Hero Slide</h3>
            <button type="button" onclick="closeSlideModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-xs transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Form Body -->
        <div class="overflow-y-auto p-7 flex-1 custom-scrollbar">
            <form id="slide-form" method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="save_slide" value="1">
                <input type="hidden" name="slide_id" id="modal-slide-id" value="0">

                <!-- 1. Desktop Image Path (1350x620 px) * -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-gray-800">
                        Desktop Image Path (1350x620 px) <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="desktop_image_path" id="desktop-image-path" placeholder="e.g. assets/images/hero/banner-desktop.png" required class="flex-1 rounded-xl border border-gray-200 px-4 py-3 text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#24B25D] focus:ring-1 focus:ring-[#24B25D] transition">
                        <input type="file" name="file_desktop" id="file-desktop" accept="image/*" class="hidden" onchange="handleFileSelected(this, 'desktop')">
                        <button type="button" onclick="document.getElementById('file-desktop').click()" class="px-5 py-3 rounded-xl bg-[#24B25D] hover:bg-[#004F42] text-white font-black text-xs transition flex items-center gap-1.5 shrink-0 shadow-sm">
                            <i class="fas fa-image text-xs"></i>
                            <span>Choose</span>
                        </button>
                    </div>
                    <!-- Live Desktop Thumbnail Preview -->
                    <div id="preview-box-desktop" class="hidden mt-2 p-2 rounded-xl bg-gray-50 border border-gray-100 flex items-center gap-3">
                        <img id="preview-img-desktop" src="" class="w-24 h-11 object-cover rounded-lg border border-gray-200">
                        <div class="flex-1 text-[11px] text-gray-500">
                            <div class="font-bold text-gray-800" id="preview-name-desktop">Selected Image</div>
                            <span class="text-[9px] text-[#24B25D] font-black uppercase">Aspect ~2.18:1 (Desktop)</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Tablet Image Path (1024x640 px) * -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-gray-800">
                        Tablet Image Path (1024x640 px)
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="tablet_image_path" id="tablet-image-path" placeholder="e.g. assets/images/hero/banner-tablet.png" class="flex-1 rounded-xl border border-gray-200 px-4 py-3 text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#24B25D] focus:ring-1 focus:ring-[#24B25D] transition">
                        <input type="file" name="file_tablet" id="file-tablet" accept="image/*" class="hidden" onchange="handleFileSelected(this, 'tablet')">
                        <button type="button" onclick="document.getElementById('file-tablet').click()" class="px-5 py-3 rounded-xl bg-[#24B25D] hover:bg-[#004F42] text-white font-black text-xs transition flex items-center gap-1.5 shrink-0 shadow-sm">
                            <i class="fas fa-image text-xs"></i>
                            <span>Choose</span>
                        </button>
                    </div>
                    <!-- Live Tablet Thumbnail Preview -->
                    <div id="preview-box-tablet" class="hidden mt-2 p-2 rounded-xl bg-gray-50 border border-gray-100 flex items-center gap-3">
                        <img id="preview-img-tablet" src="" class="w-16 h-10 object-cover rounded-lg border border-gray-200">
                        <div class="flex-1 text-[11px] text-gray-500">
                            <div class="font-bold text-gray-800" id="preview-name-tablet">Selected Image</div>
                            <span class="text-[9px] text-blue-600 font-black uppercase">Aspect 16:10 (Tablet)</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Mobile Image Path (768x768 px / Square) * -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-gray-800">
                        Mobile Image Path (768x768 px / Square)
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="mobile_image_path" id="mobile-image-path" placeholder="e.g. assets/images/hero/banner-mobile.png" class="flex-1 rounded-xl border border-gray-200 px-4 py-3 text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#24B25D] focus:ring-1 focus:ring-[#24B25D] transition">
                        <input type="file" name="file_mobile" id="file-mobile" accept="image/*" class="hidden" onchange="handleFileSelected(this, 'mobile')">
                        <button type="button" onclick="document.getElementById('file-mobile').click()" class="px-5 py-3 rounded-xl bg-[#24B25D] hover:bg-[#004F42] text-white font-black text-xs transition flex items-center gap-1.5 shrink-0 shadow-sm">
                            <i class="fas fa-image text-xs"></i>
                            <span>Choose</span>
                        </button>
                    </div>
                    <!-- Live Mobile Thumbnail Preview -->
                    <div id="preview-box-mobile" class="hidden mt-2 p-2 rounded-xl bg-gray-50 border border-gray-100 flex items-center gap-3">
                        <img id="preview-img-mobile" src="" class="w-10 h-10 object-cover rounded-lg border border-gray-200">
                        <div class="flex-1 text-[11px] text-gray-500">
                            <div class="font-bold text-gray-800" id="preview-name-mobile">Selected Image</div>
                            <span class="text-[9px] text-amber-600 font-black uppercase">Aspect 1:1 Square (Mobile)</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Action Link URL / Target Product * -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-gray-800">
                        Action Link URL / Target Product <span class="text-red-500">*</span>
                    </label>
                    <select name="action_target" id="action-target-select" onchange="handleTargetSelection(this)" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#24B25D] focus:ring-1 focus:ring-[#24B25D] transition bg-white">
                        <option value="">(None - No Link)</option>
                        <optgroup label="Categories">
                            <?php foreach($categories_list as $cat): ?>
                                <option value="cat:/category/<?php echo $cat['slug']; ?>">Category: <?php echo htmlspecialchars($cat['name']); ?> (/category/<?php echo $cat['slug']; ?>)</option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Products">
                            <?php foreach($products_list as $p): ?>
                                <option value="prod:<?php echo $p['id']; ?>:/product/<?php echo $p['slug']; ?>">Product: <?php echo htmlspecialchars($p['name']); ?> (/product/<?php echo $p['slug']; ?>)</option>
                            <?php endforeach; ?>
                        </optgroup>
                        <option value="custom">Custom URL...</option>
                    </select>

                    <div id="custom-link-box" class="hidden pt-1.5">
                        <input type="text" name="custom_cta_link" id="custom-cta-link" placeholder="e.g. /shop or https://..." class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#24B25D] focus:ring-1 focus:ring-[#24B25D] transition">
                    </div>
                </div>

                <!-- 5. Accessibility Alt Description -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-gray-800">
                        Accessibility Alt Description
                    </label>
                    <input type="text" name="alt_text" id="alt-text-input" placeholder="Describe the image content for screen readers..." class="w-full rounded-xl border border-gray-200 px-4 py-3 text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#24B25D] focus:ring-1 focus:ring-[#24B25D] transition">
                </div>

                <!-- 6. Display Sequence Order * & Active Status * -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-black text-gray-800">
                            Display Sequence Order <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="sort_order" id="sort-order-input" value="0" min="0" required class="w-full rounded-xl border border-gray-200 px-4 py-3 text-xs font-black text-gray-800 focus:outline-none focus:border-[#24B25D] focus:ring-1 focus:ring-[#24B25D] transition text-center bg-gray-50/50">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-black text-gray-800">
                            Active Status <span class="text-red-500">*</span>
                        </label>
                        <select name="is_active" id="is-active-select" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-xs font-bold text-gray-800 focus:outline-none focus:border-[#24B25D] focus:ring-1 focus:ring-[#24B25D] transition bg-white">
                            <option value="1">Active (Live on frontpage)</option>
                            <option value="0">Inactive (Draft)</option>
                        </select>
                    </div>
                </div>

                <!-- 7. Save Banner Slide Button (Brand Green #24B25D) -->
                <div class="pt-4">
                    <button type="submit" class="w-full py-4 rounded-2xl bg-[#24B25D] hover:bg-[#004F42] text-white font-black text-xs uppercase tracking-widest shadow-xl shadow-[#24B25D]/20 active:scale-95 transition-all">
                        Save Banner Slide
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- IMAGE LIGHTBOX MODAL -->
<div id="image-lightbox" class="fixed inset-0 z-[10000] hidden flex items-center justify-center p-4 bg-black/80 backdrop-blur-md cursor-pointer" onclick="this.classList.add('hidden')">
    <div class="max-w-4xl max-h-[85vh] p-2 bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100 text-xs font-bold text-gray-700">
            <span id="lightbox-title">Image Preview</span>
            <button onclick="document.getElementById('image-lightbox').classList.add('hidden')" class="text-gray-400 hover:text-black"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-2 flex items-center justify-center overflow-auto max-h-[75vh]">
            <img id="lightbox-img" src="" class="max-w-full max-h-[70vh] object-contain rounded-lg">
        </div>
    </div>
</div>

<script>
// --- TOAST HELPER ---
function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.className = `fixed top-6 right-6 z-[9999] flex items-center gap-3 px-5 py-3.5 rounded-2xl text-white font-bold text-xs shadow-2xl transition-all duration-300 ${type === 'success' ? 'bg-[#24B25D]' : 'bg-red-500'}`;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

// --- MODAL CONTROLS ---
function openAddSlideModal() {
    document.getElementById('slide-form').reset();
    document.getElementById('modal-slide-id').value = '0';
    document.getElementById('modal-title').textContent = 'Add New Hero Slide';
    document.getElementById('sort-order-input').value = '<?php echo $next_sort; ?>';
    document.getElementById('custom-link-box').classList.add('hidden');
    
    // Clear preview boxes
    ['desktop', 'tablet', 'mobile'].forEach(type => {
        document.getElementById(`preview-box-${type}`).classList.add('hidden');
        document.getElementById(`preview-img-${type}`).src = '';
    });
    
    document.getElementById('slide-modal').classList.remove('hidden');
}

function openEditSlideModal(data) {
    document.getElementById('slide-form').reset();
    document.getElementById('modal-slide-id').value = data.id || 0;
    document.getElementById('modal-title').textContent = 'Edit Hero Slide #' + data.id;
    
    // Fill text inputs
    document.getElementById('desktop-image-path').value = data.image || '';
    document.getElementById('tablet-image-path').value = data.image_tablet || '';
    document.getElementById('mobile-image-path').value = data.image_mobile || '';
    document.getElementById('alt-text-input').value = data.alt_text || data.title || '';
    document.getElementById('sort-order-input').value = data.sort_order || 0;
    document.getElementById('is-active-select').value = data.is_active !== undefined ? data.is_active : 1;

    // Fill previews
    if (data.image) {
        showPreviewBox('desktop', data.image, 'Current Desktop Banner');
    } else {
        document.getElementById('preview-box-desktop').classList.add('hidden');
    }
    if (data.image_tablet) {
        showPreviewBox('tablet', data.image_tablet, 'Current Tablet Banner');
    } else {
        document.getElementById('preview-box-tablet').classList.add('hidden');
    }
    if (data.image_mobile) {
        showPreviewBox('mobile', data.image_mobile, 'Current Mobile Banner');
    } else {
        document.getElementById('preview-box-mobile').classList.add('hidden');
    }

    // Resolve Action Target Select
    const targetSelect = document.getElementById('action-target-select');
    const customBox = document.getElementById('custom-link-box');
    const customInput = document.getElementById('custom-cta-link');
    
    let matched = false;
    const link = data.cta_link || '';
    const pid = data.product_id;

    if (pid && link) {
        for (let opt of targetSelect.options) {
            if (opt.value.startsWith('prod:' + pid + ':')) {
                opt.selected = true;
                matched = true;
                break;
            }
        }
    }

    if (!matched && link) {
        for (let opt of targetSelect.options) {
            if (opt.value === 'cat:' + link || opt.value === link) {
                opt.selected = true;
                matched = true;
                break;
            }
        }
    }

    if (!matched && link) {
        targetSelect.value = 'custom';
        customInput.value = link;
        customBox.classList.remove('hidden');
    } else if (!matched) {
        targetSelect.value = '';
        customBox.classList.add('hidden');
    } else {
        customBox.classList.add('hidden');
    }

    document.getElementById('slide-modal').classList.remove('hidden');
}

function closeSlideModal() {
    document.getElementById('slide-modal').classList.add('hidden');
}

// --- FILE SELECTION & LIVE PREVIEW ---
function handleFileSelected(input, type) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const pathInput = document.getElementById(`${type}-image-path`);
        pathInput.value = file.name;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            showPreviewBox(type, e.target.result, file.name);
        };
        reader.readAsDataURL(file);
    }
}

function showPreviewBox(type, src, name) {
    const box = document.getElementById(`preview-box-${type}`);
    const img = document.getElementById(`preview-img-${type}`);
    const nameEl = document.getElementById(`preview-name-${type}`);
    
    // Resolve relative path if needed
    let finalSrc = src;
    if (!src.startsWith('data:') && !src.startsWith('http') && !src.startsWith('/')) {
        finalSrc = '../' + src;
    }

    img.src = finalSrc;
    if (nameEl) nameEl.textContent = name;
    box.classList.remove('hidden');
}

function handleTargetSelection(sel) {
    const customBox = document.getElementById('custom-link-box');
    if (sel.value === 'custom') {
        customBox.classList.remove('hidden');
    } else {
        customBox.classList.add('hidden');
    }
}

// --- LIGHTBOX PREVIEW ---
function viewLargeImage(url, title) {
    if (!url) return;
    document.getElementById('lightbox-img').src = url;
    document.getElementById('lightbox-title').textContent = title || 'Image Preview';
    document.getElementById('image-lightbox').classList.remove('hidden');
}

// --- DELETE CONFIRMATION ---
function confirmDeleteSlide(id) {
    if (confirm('Are you sure you want to delete this hero slide? This action cannot be undone.')) {
        document.getElementById('delete-slide-id').value = id;
        document.getElementById('single-delete-form').submit();
    }
}

// --- TOGGLE STATUS AJAX ---
function toggleSlideStatus(id, btn) {
    fetch(`hero_slides.php?action=toggle&id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const isActive = data.is_active === 1;
                const dot = btn.querySelector('span:first-child');
                const text = btn.querySelector('.status-text');
                
                if (isActive) {
                    btn.className = 'status-btn inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider transition-all bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100';
                    dot.className = 'w-1.5 h-1.5 rounded-full bg-emerald-500';
                    text.textContent = 'Active';
                } else {
                    btn.className = 'status-btn inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider transition-all bg-gray-100 text-gray-500 border border-gray-200 hover:bg-gray-200';
                    dot.className = 'w-1.5 h-1.5 rounded-full bg-gray-400';
                    text.textContent = 'Inactive';
                }
                showToast(`Slide #${id} status updated!`, 'success');
            } else {
                showToast('Failed to update status.', 'error');
            }
        })
        .catch(() => showToast('Network error while toggling status.', 'error'));
}

// --- DRAG & DROP SORTABLEJS ---
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('sortable-slides');
    if (tbody && tbody.querySelectorAll('tr[data-id]').length > 1) {
        new Sortable(tbody, {
            handle: '.drag-handle',
            animation: 200,
            ghostClass: 'bg-emerald-50/70',
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
                        showToast('Slide order updated successfully!', 'success');
                    } else {
                        showToast('Failed to save slide order.', 'error');
                    }
                })
                .catch(() => showToast('Error saving slide order.', 'error'));
            }
        });
    }

    // --- BULK SELECTION LOGIC ---
    const selectAll = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkBar = document.getElementById('bulk-action-bar');
    const selectedCount = document.getElementById('selected-count');

    function updateBulkBar() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        if (checked.length > 0) {
            bulkBar.classList.remove('hidden');
            selectedCount.textContent = `${checked.length} selected`;
        } else {
            bulkBar.classList.add('hidden');
        }
    }

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

function submitBulkDelete() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    if (checked.length === 0) return;
    if (confirm(`Are you sure you want to delete ${checked.length} selected slides?`)) {
        document.getElementById('bulk-form').submit();
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
    if (val === 0) return '📏 Flat Divider (Straight)';
    if (val <= 30) return '🍃 Subtle Soft Ripples';
    if (val <= 60) return '🌊 Balanced Organic Flow';
    if (val <= 85) return '✨ Dynamic & Curvy (Recommended)';
    return '🏔️ Bold Crests (Maximum)';
}

function highlightMatchingPreset(val) {
    val = parseInt(val) || 0;
    document.querySelectorAll('.preset-btn').forEach(btn => {
        const pVal = parseInt(btn.getAttribute('data-val'));
        const isMatch = (pVal === val);
        const valElem = btn.querySelector('.preset-val') || btn.firstElementChild;
        const descElem = btn.querySelector('.preset-lbl') || btn.lastElementChild;
        const iconElem = btn.querySelector('.preset-ico') || btn.querySelector('i');
        
        if (isMatch) {
            btn.className = 'preset-btn p-2.5 rounded-2xl border border-emerald-400 bg-emerald-50 text-[#004F42] text-left transition shadow-xs active:scale-95 group ring-2 ring-emerald-500/20';
            if (valElem) valElem.className = 'preset-val text-xs font-black text-[#004F42] flex items-center justify-between';
            if (descElem) descElem.className = 'preset-lbl text-[10px] font-black text-emerald-700 mt-0.5 leading-tight truncate';
            if (iconElem) {
                iconElem.classList.remove('text-gray-400');
                iconElem.classList.add('text-emerald-500');
            }
        } else {
            btn.className = 'preset-btn p-2.5 rounded-2xl border border-gray-200 text-left transition hover:border-gray-400 hover:bg-gray-50 active:scale-95 shadow-2xs group';
            if (valElem) valElem.className = 'preset-val text-xs font-black text-gray-900 flex items-center justify-between';
            if (descElem) descElem.className = 'preset-lbl text-[10px] font-bold text-gray-500 mt-0.5 leading-tight truncate';
            if (iconElem) {
                iconElem.classList.remove('text-emerald-500');
                iconElem.classList.add('text-gray-400');
            }
        }
    });
}

function onWaveSliderChange(val) {
    try {
        val = Math.max(0, Math.min(100, parseInt(val) || 0));
        
        // Dynamic CSS track fill
        const slider = document.getElementById('wave-slider');
        if (slider) {
            slider.style.setProperty('--fill-pct', `${val}%`);
        }

        // Update labels and hidden inputs
        const hiddenInput = document.getElementById('wave_intensity_hidden');
        const valPill = document.getElementById('wave-val-pill');
        const descBadge = document.getElementById('wave-desc-badge');
        const indicator = document.getElementById('preview-indicator');
        
        if (hiddenInput) hiddenInput.value = val;
        if (valPill) valPill.textContent = val + '%';
        if (descBadge) descBadge.textContent = getWaveDescription(val);
        
        highlightMatchingPreset(val);

        if (indicator) {
            indicator.textContent = 'Unsaved changes...';
            indicator.className = 'text-amber-400 font-bold';
        }

        // Live update SVG paths inside preview
        const whitePath = generateWavePathJs(val, false);
        const creamPath = generateWavePathJs(val, true);
        
        const previewWhite = document.getElementById('preview-white-wave');
        const previewCream = document.getElementById('preview-cream-wave');
        if (previewWhite) previewWhite.setAttribute('d', whitePath);
        if (previewCream) {
            if (val === 0) {
                previewCream.setAttribute('d', '');
                previewCream.setAttribute('visibility', 'hidden');
            } else {
                previewCream.setAttribute('visibility', 'visible');
                previewCream.setAttribute('d', creamPath);
            }
        }
    } catch (err) {
        console.error('Wave preview update error:', err);
    }
}

function stepWave(delta) {
    const slider = document.getElementById('wave-slider');
    if (!slider) return;
    let val = (parseInt(slider.value) || 0) + delta;
    val = Math.max(0, Math.min(100, val));
    slider.value = val;
    onWaveSliderChange(val);
    saveWaveIntensity(true);
}

function setWavePreset(val) {
    const slider = document.getElementById('wave-slider');
    if (slider) {
        slider.value = val;
        onWaveSliderChange(val);
        saveWaveIntensity(true);
    }
}

function setPreviewViewport(mode) {
    const wrapper = document.getElementById('preview-wrapper');
    const desktopBtn = document.getElementById('vp-desktop-btn');
    const mobileBtn = document.getElementById('vp-mobile-btn');
    if (!wrapper || !desktopBtn || !mobileBtn) return;

    if (mode === 'mobile') {
        wrapper.style.maxWidth = '310px';
        mobileBtn.className = 'px-2 py-0.5 rounded-md text-[10px] font-bold bg-white text-gray-900 shadow-2xs transition';
        desktopBtn.className = 'px-2 py-0.5 rounded-md text-[10px] font-bold text-gray-400 hover:text-white transition';
    } else {
        wrapper.style.maxWidth = '100%';
        desktopBtn.className = 'px-2 py-0.5 rounded-md text-[10px] font-bold bg-white text-gray-900 shadow-2xs transition';
        mobileBtn.className = 'px-2 py-0.5 rounded-md text-[10px] font-bold text-gray-400 hover:text-white transition';
    }
}

let saveDebounceTimer = null;
function saveWaveIntensity(isAuto = false) {
    const slider = document.getElementById('wave-slider');
    if (!slider) return;
    const val = Math.max(0, Math.min(100, parseInt(slider.value) || 0));
    const btn = document.getElementById('save-wave-btn');
    const indicator = document.getElementById('preview-indicator');
    
    if (btn && !isAuto) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Saving...</span>';
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
            showToast('Hero wave amplitude updated to ' + val + '%!', 'success');
        } else {
            showToast('Failed to save wave setting.', 'error');
        }
    })
    .catch(() => {
        showToast('Network error saving wave setting.', 'error');
    })
    .finally(() => {
        if (btn && !isAuto) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> <span>Save Waviness</span>';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    onWaveSliderChange(<?php echo (int)$current_wave_intensity; ?>);
});
</script>

<?php include 'includes/footer.php'; ?>
