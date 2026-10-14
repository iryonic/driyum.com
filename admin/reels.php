<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Strict Admin Authorization Check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    if (isset($_GET['action']) && $_GET['action'] === 'fetch_meta') {
        // Safe read-only metadata fetch for public YouTube URLs in admin modal
    } else {
        if (isset($_GET['action'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        header("Location: ../login.php");
        exit;
    }
}

$conn = get_db_connection();
if (function_exists('ensure_reels_table')) {
    ensure_reels_table();
}

// Helper: Parse YouTube Video ID from any URL format
function extract_yt_id($url) {
    if (empty($url)) return null;
    $url = trim($url);
    $patterns = [
        '/(?:youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/i',
        '/(?:youtube\.com\/watch\?(?:.*&)?v=)([a-zA-Z0-9_-]{11})/i',
        '/(?:youtu\.be\/)([a-zA-Z0-9_-]{11})/i',
        '/(?:youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/i',
        '/(?:youtube\.com\/v\/)([a-zA-Z0-9_-]{11})/i',
        '/^([a-zA-Z0-9_-]{11})$/'
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) return $m[1];
    }
    return null;
}

// Helper: Parse ISO 8601 duration (e.g. PT45S, PT1M30S, PT1H2M3S) into M:SS or H:MM:SS
function parse_iso_duration($iso) {
    if (empty($iso)) return null;
    if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/i', $iso, $m)) {
        $hours = !empty($m[1]) ? (int)$m[1] : 0;
        $mins = !empty($m[2]) ? (int)$m[2] : 0;
        $secs = !empty($m[3]) ? (int)$m[3] : 0;
        if ($hours > 0) {
            return sprintf("%d:%02d:%02d", $hours, $mins, $secs);
        } else {
            return sprintf("%d:%02d", $mins, $secs);
        }
    }
    return null;
}

// Helper: Format raw numeric view count into clean badge (e.g. 1.8M, 450K, 2.1B)
function format_view_count($num) {
    if ($num >= 1000000000) {
        $val = round($num / 1000000000, 1);
        return ($val == floor($val) ? (int)$val : $val) . 'B';
    }
    if ($num >= 1000000) {
        $val = round($num / 1000000, 1);
        return ($val == floor($val) ? (int)$val : $val) . 'M';
    }
    if ($num >= 1000) {
        $val = round($num / 1000, 1);
        return ($val == floor($val) ? (int)$val : $val) . 'K';
    }
    return (string)$num;
}

// Helper: Fetch YouTube Metadata (oEmbed + Watch Page HTML Scraper + Data API fallback)
function get_yt_meta($raw_url) {
    $video_id = extract_yt_id($raw_url);
    if (!$video_id) {
        return ['success' => false, 'message' => 'Could not find a valid YouTube video ID in this URL.'];
    }

    $canonical_url = "https://www.youtube.com/shorts/{$video_id}";
    $thumbnail_hq = "https://i.ytimg.com/vi/{$video_id}/maxresdefault.jpg";
    $thumb_to_use = $thumbnail_hq;
    $title = "";
    $author = "";
    $duration = "";
    $duration_detected = false;
    $title_found = false;
    $views_badge = "";
    $views_raw = 0;

    // 1. YouTubei Internal Player API (Instant, Pure JSON, No API Key, Exact Duration & Views)
    $api_url = "https://www.youtube.com/youtubei/v1/player?prettyPrint=false";
    $payload = json_encode([
        'context' => [
            'client' => [
                'clientName' => 'WEB',
                'clientVersion' => '2.20230801.00.00',
                'hl' => 'en',
                'gl' => 'US'
            ]
        ],
        'videoId' => $video_id
    ]);

    $res = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code !== 200) $res = false;
    }

    // Native PHP Stream fallback (runs when curl extension is disabled in Apache php.ini)
    if (!$res) {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n" .
                            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n" .
                            "Content-Length: " . strlen($payload) . "\r\n",
                'content' => $payload,
                'timeout' => 6,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        $res = @file_get_contents($api_url, false, $ctx);
    }

    if ($res) {
        $json = json_decode($res, true);
        if (!empty($json['videoDetails'])) {
            $vd = $json['videoDetails'];
            if (!empty($vd['title'])) {
                $title = $vd['title'];
                $title_found = true;
            }
            if (!empty($vd['author'])) {
                $author = $vd['author'];
            }
            if (isset($vd['lengthSeconds'])) {
                $sec = (int)$vd['lengthSeconds'];
                if ($sec > 0) {
                    $hours = floor($sec / 3600);
                    $mins = floor(($sec % 3600) / 60);
                    $secs = $sec % 60;
                    $duration = $hours > 0 ? sprintf("%d:%02d:%02d", $hours, $mins, $secs) : sprintf("%d:%02d", $mins, $secs);
                    $duration_detected = true;
                }
            }
            if (isset($vd['viewCount'])) {
                $views_raw = (int)$vd['viewCount'];
                if ($views_raw > 0) {
                    $views_badge = format_view_count($views_raw);
                }
            }
            if (!empty($vd['thumbnail']['thumbnails'])) {
                $thumbs = $vd['thumbnail']['thumbnails'];
                $last = end($thumbs);
                if (!empty($last['url'])) {
                    $thumb_to_use = $last['url'];
                }
            }
        }
    }

    // 2. YouTube oEmbed fallback (for Title & Thumbnail if not found yet)
    if (!$title_found) {
        $oembed_url = "https://www.youtube.com/oembed?url=" . urlencode("https://www.youtube.com/watch?v={$video_id}") . "&format=json";
        $response = false;
        $http_code = 0;

        if (function_exists('curl_init')) {
            $ch = curl_init($oembed_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }

        if ($http_code !== 200 || !$response) {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 4,
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            $response = @file_get_contents($oembed_url, false, $ctx);
            if ($response) $http_code = 200;
        }

        if ($http_code === 200 && $response) {
            $json = json_decode($response, true);
            if ($json && !empty($json['title'])) {
                $title = $json['title'];
                $title_found = true;
                $author = $json['author_name'] ?? '';
                if (!empty($json['thumbnail_url'])) {
                    $thumb_to_use = $json['thumbnail_url'];
                }
            }
        }
    }

    // 2. Public YouTube Watch Page Scraper for EXACT Duration (No API Key Required)
    if (!$duration_detected && function_exists('curl_init')) {
        $watch_url = "https://www.youtube.com/watch?v={$video_id}";
        $ch = curl_init($watch_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
        $html = curl_exec($ch);
        curl_close($ch);

        if ($html) {
            // A. lengthSeconds (exact integer seconds)
            if (preg_match('/"lengthSeconds"\s*:\s*"(\d+)"/', $html, $matches)) {
                $sec = (int)$matches[1];
                if ($sec > 0) {
                    $hours = floor($sec / 3600);
                    $mins = floor(($sec % 3600) / 60);
                    $secs = $sec % 60;
                    $duration = $hours > 0 ? sprintf("%d:%02d:%02d", $hours, $mins, $secs) : sprintf("%d:%02d", $mins, $secs);
                    $duration_detected = true;
                }
            }

            // B. approxDurationMs
            if (!$duration_detected && preg_match('/"approxDurationMs"\s*:\s*"(\d+)"/', $html, $matches)) {
                $sec = round((int)$matches[1] / 1000);
                if ($sec > 0) {
                    $hours = floor($sec / 3600);
                    $mins = floor(($sec % 3600) / 60);
                    $secs = $sec % 60;
                    $duration = $hours > 0 ? sprintf("%d:%02d:%02d", $hours, $mins, $secs) : sprintf("%d:%02d", $mins, $secs);
                    $duration_detected = true;
                }
            }

            // C. itemprop="duration"
            if (!$duration_detected && preg_match('/itemprop=["\']duration["\']\s+content=["\'](PT[^"\']+)["\']/i', $html, $matches)) {
                $parsed = parse_iso_duration($matches[1]);
                if ($parsed) {
                    $duration = $parsed;
                    $duration_detected = true;
                }
            }

            // D. Title fallback if oEmbed failed
            if (!$title_found && preg_match('/<title>([^<]+)<\/title>/i', $html, $matches)) {
                $t = preg_replace('/\s*-\s*YouTube\s*$/i', '', trim($matches[1]));
                if (!empty($t)) {
                    $title = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $title_found = true;
                }
            }

            // E. View Count Extraction (Real & Dynamic)
            if (preg_match('/"shortViewCount"\s*:\s*\{"simpleText"\s*:\s*"([^"]+)"\}/i', $html, $matches)) {
                $rawText = trim($matches[1]);
                $clean = preg_replace('/\s*views\s*$/i', '', $rawText);
                $clean = str_ireplace(['bn', 'b'], 'B', $clean);
                $clean = str_ireplace(['m'], 'M', $clean);
                $clean = str_ireplace(['k'], 'K', $clean);
                $views_badge = strtoupper($clean);
            }
            if (preg_match('/"viewCount"\s*:\s*"(\d+)"/', $html, $matches)) {
                $views_raw = (int)$matches[1];
                if (empty($views_badge) || is_numeric($views_badge)) {
                    $views_badge = format_view_count($views_raw);
                }
            } elseif (empty($views_badge) && preg_match('/itemprop=["\']interactionCount["\']\s+content=["\'](\d+)["\']/i', $html, $matches)) {
                $views_raw = (int)$matches[1];
                $views_badge = format_view_count($views_raw);
            }
        }
    }

    // 3. YouTube Data API v3 fallback if API key is provided
    if (!$duration_detected || empty($views_badge)) {
        $api_key = getenv('YOUTUBE_API_KEY') ?: get_setting('youtube_api_key');
        if ($api_key && function_exists('curl_init')) {
            $api_url = "https://www.googleapis.com/youtube/v3/videos?id={$video_id}&part=contentDetails,snippet,statistics&key=" . urlencode($api_key);
            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            $api_res = curl_exec($ch);
            curl_close($ch);

            if ($api_res) {
                $api_json = json_decode($api_res, true);
                if (!empty($api_json['items'][0])) {
                    $item = $api_json['items'][0];
                    if (!empty($item['snippet']['title']) && empty($title)) {
                        $title = $item['snippet']['title'];
                        $title_found = true;
                    }
                    if (!empty($item['contentDetails']['duration']) && !$duration_detected) {
                        $parsed = parse_iso_duration($item['contentDetails']['duration']);
                        if ($parsed) {
                            $duration = $parsed;
                            $duration_detected = true;
                        }
                    }
                    if (!empty($item['statistics']['viewCount']) && empty($views_badge)) {
                        $views_raw = (int)$item['statistics']['viewCount'];
                        $views_badge = format_view_count($views_raw);
                    }
                }
            }
        }
    }

    // If detection failed (e.g. video private or unavailable), leave duration empty for manual entry
    if (!$duration_detected && empty($duration)) {
        $duration = "";
    }

    return [
        'success' => true,
        'youtube_id' => $video_id,
        'canonical_url' => $canonical_url,
        'title' => $title,
        'title_found' => $title_found,
        'thumbnail_url' => $thumb_to_use,
        'duration' => $duration,
        'duration_detected' => $duration_detected,
        'views_badge' => $views_badge,
        'views_raw' => $views_raw,
        'author' => $author
    ];
}

// --- AJAX ENDPOINTS ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    // 1. Fetch YouTube Metadata
    if ($action === 'fetch_meta') {
        $url = $_GET['url'] ?? '';
        $meta = get_yt_meta($url);
        echo json_encode($meta);
        exit;
    }

    // 2. Reorder Reels (Sortable.js)
    if ($action === 'reorder') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!empty($input['order']) && is_array($input['order'])) {
            $stmt = $conn->prepare("UPDATE reels SET sort_order = ? WHERE id = ?");
            foreach ($input['order'] as $item) {
                $rid = intval($item['id']);
                $sorder = intval($item['sort_order']);
                $stmt->bind_param("ii", $sorder, $rid);
                $stmt->execute();
            }
            echo json_encode(['success' => true, 'message' => 'Sort order saved successfully.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Invalid order payload.']);
        exit;
    }

    // 3. Toggle Status (Active / Draft)
    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        $curr = fetch_one("SELECT status FROM reels WHERE id = ?", [$id]);
        if ($curr) {
            $new_status = ($curr['status'] === 'active') ? 'draft' : 'active';
            execute_query("UPDATE reels SET status = ? WHERE id = ?", [$new_status, $id]);
            echo json_encode(['success' => true, 'new_status' => $new_status]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Reel not found.']);
        exit;
    }

    // 4. Toggle Playback Mode (Live Cards vs Static Posters)
    if ($action === 'toggle_autoplay') {
        $curr = get_setting('reels_autoplay_cards', '0');
        $new_val = ($curr === '1' || $curr === 1) ? '0' : '1';
        update_setting('reels_autoplay_cards', $new_val);
        echo json_encode(['success' => true, 'autoplay' => $new_val]);
        exit;
    }

    // 4b. Toggle Section Visibility on Homepage (Show / Hide Entire Section)
    if ($action === 'toggle_visibility') {
        $curr = get_setting('reels_section_enabled', '1');
        $new_val = ($curr === '1' || $curr === 1) ? '0' : '1';
        update_setting('reels_section_enabled', $new_val);
        echo json_encode(['success' => true, 'enabled' => $new_val]);
        exit;
    }

    // 5. Delete Reel
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            execute_query("DELETE FROM reel_products WHERE reel_id = ?", [$id]);
            execute_query("DELETE FROM reels WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'message' => 'Reel removed successfully.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Invalid Reel ID.']);
        exit;
    }

    // 6. Bulk Actions (Activate, Deactivate, Delete)
    if ($action === 'bulk_action') {
        $input = json_decode(file_get_contents('php://input'), true);
        $type = $input['type'] ?? '';
        $ids = $input['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'No items selected.']);
            exit;
        }

        $clean_ids = array_map('intval', $ids);
        $id_list = implode(',', $clean_ids);

        if ($type === 'activate') {
            $conn->query("UPDATE reels SET status = 'active' WHERE id IN ($id_list)");
            echo json_encode(['success' => true, 'message' => 'Selected reels activated.']);
            exit;
        } elseif ($type === 'deactivate') {
            $conn->query("UPDATE reels SET status = 'draft' WHERE id IN ($id_list)");
            echo json_encode(['success' => true, 'message' => 'Selected reels set to draft.']);
            exit;
        } elseif ($type === 'delete') {
            $conn->query("DELETE FROM reel_products WHERE reel_id IN ($id_list)");
            $conn->query("DELETE FROM reels WHERE id IN ($id_list)");
            echo json_encode(['success' => true, 'message' => 'Selected reels deleted.']);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        exit;
    }
}

// --- FORM SUBMISSION: ADD / EDIT REEL ---
$msg = $_SESSION['msg'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['msg'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_reel'])) {
    $id = intval($_POST['reel_id'] ?? 0);
    $youtube_url = trim($_POST['youtube_url'] ?? '');
    $youtube_id = extract_yt_id($youtube_url);
    $title = trim($_POST['title'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $badge_tag = trim($_POST['badge_tag'] ?? 'VERTICAL SHORT');
    $views_count_input = trim($_POST['views_count'] ?? '');
    $views_raw = intval(preg_replace('/[^0-9]/', '', $views_count_input));
    $views_badge = $views_raw > 0 ? format_view_count($views_raw) : trim($_POST['views_badge'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['active', 'draft', 'inactive']) ? $_POST['status'] : 'active';
    $product_id = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
    $thumbnail_url = trim($_POST['thumbnail_url'] ?? '');

    if (empty($youtube_id)) {
        $_SESSION['error'] = 'Invalid YouTube URL. Please provide a valid Shorts or standard watch URL.';
        header("Location: reels.php");
        exit;
    }

    if (empty($title) || empty($views_badge) || empty($duration) || empty($thumbnail_url) || $views_raw === 0) {
        $meta = get_yt_meta($youtube_url);
        if (empty($title) && !empty($meta['title'])) $title = $meta['title'];
        if (empty($thumbnail_url) && !empty($meta['thumbnail_url'])) $thumbnail_url = $meta['thumbnail_url'];
        if (empty($duration) && !empty($meta['duration'])) $duration = $meta['duration'];
        if (empty($views_badge) && !empty($meta['views_badge'])) $views_badge = $meta['views_badge'];
        if ($views_raw === 0 && !empty($meta['views_raw'])) {
            $views_raw = (int)$meta['views_raw'];
            if (empty($views_badge)) $views_badge = format_view_count($views_raw);
        }
    }

    if (empty($title)) $title = 'Brand Story Reel';
    if (empty($thumbnail_url)) $thumbnail_url = "https://i.ytimg.com/vi/{$youtube_id}/maxresdefault.jpg";
    if (empty($duration)) $duration = '0:30';
    if (empty($views_badge)) $views_badge = '10K';

    $canonical_url = "https://www.youtube.com/shorts/{$youtube_id}";

    if ($id > 0) {
        // UPDATE
        $stmt = $conn->prepare("UPDATE reels SET youtube_id = ?, youtube_url = ?, title = ?, thumbnail_url = ?, duration = ?, badge_tag = ?, views_badge = ?, views_count = ?, product_id = ?, status = ?, sort_order = ? WHERE id = ?");
        $stmt->bind_param("sssssssiisii", $youtube_id, $canonical_url, $title, $thumbnail_url, $duration, $badge_tag, $views_badge, $views_raw, $product_id, $status, $sort_order, $id);
        $stmt->execute();

        execute_query("DELETE FROM reel_products WHERE reel_id = ?", [$id]);
        if ($product_id) {
            $stmt_p = $conn->prepare("INSERT INTO reel_products (reel_id, product_id, is_primary, sort_order) VALUES (?, ?, 1, 1)");
            $stmt_p->bind_param("ii", $id, $product_id);
            $stmt_p->execute();
        }

        $_SESSION['msg'] = "Reel updated successfully!";
    } else {
        // INSERT
        if ($sort_order === 0) {
            $max_order = fetch_one("SELECT MAX(sort_order) as m FROM reels")['m'] ?? 0;
            $sort_order = $max_order + 1;
        }

        $stmt = $conn->prepare("INSERT INTO reels (youtube_id, youtube_url, title, thumbnail_url, duration, badge_tag, views_badge, views_count, product_id, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssissi", $youtube_id, $canonical_url, $title, $thumbnail_url, $duration, $badge_tag, $views_badge, $views_raw, $product_id, $status, $sort_order);
        $stmt->execute();
        $new_id = $stmt->insert_id;

        if ($product_id && $new_id) {
            $stmt_p = $conn->prepare("INSERT INTO reel_products (reel_id, product_id, is_primary, sort_order) VALUES (?, ?, 1, 1)");
            $stmt_p->bind_param("ii", $new_id, $product_id);
            $stmt_p->execute();
        }

        $_SESSION['msg'] = "New Reel added and published!";
    }

    header("Location: reels.php");
    exit;
}

// --- FETCH DATA FOR LISTING ---
$reels = fetch_all("
    SELECT r.*, p.name as product_name, p.slug as product_slug, p.price as product_price, p.image as product_image, p.stock as product_stock
    FROM reels r
    LEFT JOIN products p ON r.product_id = p.id
    ORDER BY r.sort_order ASC, r.id DESC
");

$total_reels = count($reels);
$active_reels = count(array_filter($reels, fn($r) => $r['status'] === 'active'));
$section_enabled = get_setting('reels_section_enabled', '1') == '1';
$autoplay_mode = get_setting('reels_autoplay_cards', '0') == '1';

// All products for selector
$all_products = fetch_all("SELECT id, name, slug, price, original_price, stock, image FROM products WHERE is_active = 1 ORDER BY name ASC");

include 'includes/header.php';
?>

<!-- Sortable.js for Drag & Drop Reordering -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="space-y-5">

    <!-- Alert Notifications -->
    <?php if ($msg): ?>
        <div class="p-3.5 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 text-xs font-semibold flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-3.5 bg-rose-50 text-rose-800 rounded-xl border border-rose-200 text-xs font-semibold flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-triangle text-rose-600"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <!-- ========================================================
         TOP STATS CARDS
         ======================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stat 1: Total Videos -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-2xs flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-500 border border-rose-100 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-video"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-slate-900 leading-none"><?php echo $total_reels; ?></div>
                <div class="text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mt-1.5">Total Videos & Shorts</div>
            </div>
        </div>

        <!-- Stat 2: Active on Homepage -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-2xs flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-check"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-slate-900 leading-none"><?php echo $active_reels; ?></div>
                <div class="text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mt-1.5">Active on Homepage</div>
            </div>
        </div>

        <!-- Stat 3: Section Visibility (Storefront Master State) -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-2xs flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg <?php echo $section_enabled ? 'bg-teal-50 text-teal-600 border border-teal-100' : 'bg-rose-50 text-rose-600 border border-rose-100'; ?> flex items-center justify-center text-lg shrink-0" id="stat-vis-icon-box">
                <i class="fas <?php echo $section_enabled ? 'fa-eye' : 'fa-eye-slash'; ?>" id="stat-vis-icon"></i>
            </div>
            <div>
                <div>
                    <span id="stat-visibility-badge" class="<?php echo $section_enabled ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-600 border-rose-200'; ?> text-[10px] font-extrabold px-2 py-0.5 rounded border uppercase">
                        <?php echo $section_enabled ? 'VISIBLE' : 'HIDDEN'; ?>
                    </span>
                </div>
                <div class="text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mt-1.5">Storefront Visibility</div>
            </div>
        </div>

        <!-- Stat 4: Playback Mode -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-2xs flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-play"></i>
            </div>
            <div>
                <div>
                    <span id="stat-mode-badge" class="<?php echo $autoplay_mode ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-rose-50 text-rose-600 border-rose-200'; ?> text-[10px] font-extrabold px-2 py-0.5 rounded border uppercase">
                        <?php echo $autoplay_mode ? 'LIVE CARDS' : 'STATIC POSTERS'; ?>
                    </span>
                </div>
                <div class="text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mt-1.5">Playback Mode</div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         HOMEPAGE SECTION VISIBILITY MASTER SWITCH
         ======================================================== -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start gap-3.5">
            <div class="w-9 h-9 rounded-lg <?php echo $section_enabled ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-600 border border-rose-100'; ?> flex items-center justify-center shrink-0 mt-0.5" id="section-icon-box">
                <i class="fas <?php echo $section_enabled ? 'fa-eye' : 'fa-eye-slash'; ?>" id="section-icon"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-xs font-bold text-slate-900">Homepage Section: Show Brand Reels & Shorts</h3>
                    <span id="section-visibility-pill" class="<?php echo $section_enabled ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-700'; ?> text-[9px] font-black uppercase px-2 py-0.5 rounded">
                        <?php echo $section_enabled ? 'VISIBLE ON HOMEPAGE' : 'HIDDEN FROM HOMEPAGE'; ?>
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Master switch to show or hide the entire Reels & Shorts section on the storefront homepage. When disabled, the section is completely removed from the homepage.</p>
            </div>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="section-visibility-toggle" class="sr-only peer" <?php echo $section_enabled ? 'checked' : ''; ?> onchange="toggleSectionVisibility(this.checked)">
                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
            </label>
        </div>
    </div>

    <!-- ========================================================
         PLAYBACK MODE TOGGLE BANNER (Exact Match to Reference 5)
         ======================================================== -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start gap-3.5">
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0 mt-0.5">
                <i class="fas fa-clapperboard text-sm"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-xs font-bold text-slate-900">Reels Section: Play Videos on Cards (Muted & Looped)</h3>
                    <span id="autoplay-status-pill" class="<?php echo $autoplay_mode ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-700'; ?> text-[9px] font-black uppercase px-2 py-0.5 rounded">
                        <?php echo $autoplay_mode ? 'ENABLED' : 'DISABLED'; ?>
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">When enabled, the homepage Brand Reels & Shorts section cards will play their reel videos live on loop and muted. When disabled, static thumbnails are shown.</p>
            </div>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="autoplay-toggle" class="sr-only peer" <?php echo $autoplay_mode ? 'checked' : ''; ?> onchange="toggleAutoplayCards(this.checked)">
                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
            </label>
        </div>
    </div>

    <!-- ========================================================
         SECTION HEADER & ACTIONS (Exact Match to Reference 5)
         ======================================================== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Reels & Short Videos</h2>
            <p class="text-xs text-slate-500 mt-0.5">Manage YouTube embedded short-form videos displayed on the homepage section below Testimonials.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <!-- Search Bar -->
            <div class="relative w-48 sm:w-60">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="filter-input" placeholder="Filter videos..." onkeyup="filterReelsTable()" class="w-full text-xs pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 placeholder-slate-400">
            </div>

            <!-- Add New Video Button (Vibrant Orange Button) -->
            <button onclick="openAddReelModal()" class="bg-[#F97316] hover:bg-[#EA580C] text-white text-xs font-bold py-1.5 px-3.5 rounded-lg flex items-center gap-1.5 shadow-xs transition-colors shrink-0">
                <i class="fas fa-plus text-xs"></i>
                <span>Add New Video / Short</span>
            </button>
        </div>
    </div>

    <!-- ========================================================
         TABLE CARD & BULK ACTIONS (Exact Match to Reference 5)
         ======================================================== -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        
        <!-- Bulk Action Row -->
        <div class="p-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-white">
            <div class="flex items-center gap-2">
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer select-none">
                    <input type="checkbox" id="select-all" onclick="toggleSelectAll(this)" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Select All</span>
                </label>
            </div>

            <div class="flex items-center gap-2">
                <select id="bulk-action-select" class="text-xs py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Bulk Actions...</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate (Draft)</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="button" onclick="applyBulkAction()" class="bg-[#00875A] hover:bg-[#00704A] text-white text-xs font-bold py-1.5 px-3 rounded-lg shadow-xs transition-colors">
                    Apply Bulk Action
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 bg-white">
                        <th class="py-3 px-3 w-8 text-center"></th>
                        <th class="py-3 px-3 w-8 text-center"></th>
                        <th class="py-3 px-3 w-20">THUMBNAIL</th>
                        <th class="py-3 px-4 min-w-[220px]">VIDEO INFORMATION</th>
                        <th class="py-3 px-4 min-w-[150px]">LINKED PRODUCT</th>
                        <th class="py-3 px-4">YOUTUBE URL</th>
                        <th class="py-3 px-3 text-center">DURATION</th>
                        <th class="py-3 px-3 text-center">VIEW COUNT</th>
                        <th class="py-3 px-3 text-center">SORT</th>
                        <th class="py-3 px-3 text-center">STATUS</th>
                        <th class="py-3 px-4 text-right">ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="sortable-reels" class="divide-y divide-slate-100">
                    <?php if (empty($reels)): ?>
                        <tr>
                            <td colspan="11" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl">
                                    <i class="fas fa-video-slash"></i>
                                </div>
                                <p class="text-xs font-bold text-slate-700">No reels added yet</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Click "+ Add New Video / Short" to add your first YouTube Short.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reels as $r): ?>
                            <tr data-id="<?php echo $r['id']; ?>" class="hover:bg-slate-50/70 transition-colors reel-row group">
                                <!-- Drag Handle -->
                                <td class="py-3 px-3 text-center cursor-grab active:cursor-grabbing drag-handle text-slate-300 hover:text-slate-600">
                                    <i class="fas fa-grip-vertical"></i>
                                </td>

                                <!-- Checkbox -->
                                <td class="py-3 px-3 text-center">
                                    <input type="checkbox" class="row-checkbox rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" value="<?php echo $r['id']; ?>">
                                </td>

                                <!-- Thumbnail with Duration Badge -->
                                <td class="py-3 px-3">
                                    <div class="relative w-12 aspect-[9/16] rounded-lg overflow-hidden bg-slate-900 border border-slate-200 shadow-2xs group-hover:scale-105 transition-transform">
                                        <img src="<?php echo htmlspecialchars($r['thumbnail_url']); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>" class="w-full h-full object-cover" onerror="this.src='https://i.ytimg.com/vi/<?php echo $r['youtube_id']; ?>/hqdefault.jpg'">
                                        <span class="absolute bottom-0.5 right-0.5 bg-black/80 text-white text-[8px] font-bold px-1 py-0.5 rounded leading-none">
                                            <?php echo htmlspecialchars($r['duration'] ?: '0:45'); ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- Video Information -->
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 line-clamp-2 leading-snug">
                                        <?php echo htmlspecialchars($r['title']); ?>
                                    </div>
                                    <div class="mt-1">
                                        <span class="inline-block text-[#F97316] text-[9px] font-black uppercase tracking-wider">
                                            <?php echo htmlspecialchars($r['badge_tag'] ?: 'VERTICAL SHORT'); ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- Linked Product -->
                                <td class="py-3 px-4">
                                    <?php if (!empty($r['product_id']) && !empty($r['product_name'])): ?>
                                        <div class="flex items-center gap-2">
                                            <img src="../<?php echo htmlspecialchars($r['product_image'] ?: 'assets/images/logo.svg'); ?>" alt="" class="w-8 h-8 rounded-md object-cover border border-slate-200 bg-white shrink-0">
                                            <div class="min-w-0">
                                                <a href="../product/<?php echo htmlspecialchars($r['product_slug']); ?>" target="_blank" class="font-bold text-slate-800 hover:text-emerald-700 truncate block text-xs hover:underline">
                                                    <?php echo htmlspecialchars($r['product_name']); ?>
                                                </a>
                                                <div class="text-[10px] text-slate-400 font-semibold">
                                                    ₹<?php echo number_format($r['product_price'], 2); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic text-[11px]">None</span>
                                    <?php endif; ?>
                                </td>

                                <!-- YouTube URL -->
                                <td class="py-3 px-4">
                                    <a href="<?php echo htmlspecialchars($r['youtube_url']); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-rose-600 hover:text-rose-700 font-bold hover:underline text-xs">
                                        <i class="fab fa-youtube text-sm"></i>
                                        <span>Watch Video</span>
                                    </a>
                                </td>

                                <!-- Duration -->
                                <td class="py-3 px-3 text-center font-mono font-medium text-slate-600 text-xs">
                                    <?php echo htmlspecialchars($r['duration'] ?: '0:45'); ?>
                                </td>

                                <!-- View Count -->
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 font-bold text-[10px] px-2 py-0.5 rounded border border-emerald-100">
                                        <i class="fas fa-eye text-[8px]"></i>
                                        <?php echo htmlspecialchars($r['views_badge'] ?: ($r['views_count'] > 0 ? format_view_count($r['views_count']) : '-')); ?>
                                    </span>
                                    <?php if (!empty($r['views_count']) && $r['views_count'] > 0): ?>
                                        <div class="text-[9px] text-slate-400 font-mono mt-0.5" title="Total View Count">
                                            <?php echo number_format($r['views_count']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Sort Order -->
                                <td class="py-3 px-3 text-center font-medium text-slate-600 sort-number">
                                    <?php echo $r['sort_order']; ?>
                                </td>

                                <!-- Status Toggle -->
                                <td class="py-3 px-3 text-center">
                                    <button type="button" onclick="toggleReelStatus(<?php echo $r['id']; ?>, this)" class="status-btn cursor-pointer inline-flex items-center gap-1 text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full border transition-all <?php echo $r['status'] === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'; ?>">
                                        <span class="status-text"><?php echo ucfirst($r['status']); ?></span>
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" onclick="editReel(<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8'); ?>)" class="px-2.5 py-1 text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-md transition-colors">
                                            Edit
                                        </button>
                                        <button type="button" onclick="deleteReel(<?php echo $r['id']; ?>, '<?php echo addslashes($r['title']); ?>')" class="px-2.5 py-1 text-[11px] font-semibold text-rose-600 bg-white border border-rose-200 hover:bg-rose-50 rounded-md transition-colors">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL: ADD / EDIT REEL (Responsive for Smart Screens & Desktop)
     ======================================================== -->
<style>
    body.admin-modal-open #admin-mobile-bottom-nav {
        display: none !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }
</style>
<div id="reel-modal" onclick="if(event.target === this) closeReelModal()" class="fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4 hidden transition-opacity duration-200">
    <div class="bg-white rounded-t-[28px] sm:rounded-[24px] max-w-lg w-full max-h-[92dvh] sm:max-h-[90vh] flex flex-col shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in slide-in-from-bottom-4 sm:zoom-in-95 duration-200">
        
        <!-- Mobile Pull Indicator Bar for Smart Screens -->
        <div class="pt-2.5 pb-1 flex justify-center sm:hidden shrink-0 bg-white">
            <div class="w-10 h-1 bg-slate-200 rounded-full"></div>
        </div>

        <!-- Sticky Modal Header -->
        <div class="flex items-center justify-between px-5 py-3.5 sm:py-4 border-b border-slate-100 shrink-0 bg-white">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center text-base sm:text-lg shrink-0">
                    <i class="fas fa-video"></i>
                </div>
                <div class="min-w-0">
                    <h3 id="modal-heading" class="text-sm sm:text-base font-bold text-slate-900 truncate">Add New Reel / Video</h3>
                    <p class="text-[11px] sm:text-xs text-slate-400 truncate">Paste any YouTube Short link to auto-detect details</p>
                </div>
            </div>
            <button type="button" onclick="closeReelModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors active:scale-95 shrink-0 ml-2" aria-label="Close modal">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <!-- Modal Form (Fixed Flex Column: Scrollable body + Sticky footer) -->
        <form method="POST" action="reels.php" id="reel-form" class="flex flex-col flex-1 min-h-0 overflow-hidden">
            <input type="hidden" name="save_reel" value="1">
            <input type="hidden" name="reel_id" id="form-reel-id" value="0">
            <input type="hidden" name="thumbnail_url" id="form-thumbnail-url" value="">

            <!-- Scrollable Form Body -->
            <div class="flex-1 overflow-y-auto overscroll-contain p-4 sm:p-6 space-y-4">

                <!-- YouTube URL & Auto-Detect -->
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-1 mb-1.5">
                        <label class="block text-xs font-bold text-slate-700">
                            YouTube Video or Shorts URL <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[10px] text-emerald-600 font-semibold">Supports Shorts & watch links</span>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <div class="relative flex-1">
                            <input type="text" name="youtube_url" id="form-youtube-url" required placeholder="https://www.youtube.com/shorts/3Z_yWf-7x1s" class="w-full text-sm sm:text-xs px-3.5 py-2.5 sm:py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 placeholder-slate-400">
                        </div>
                        <button type="button" id="btn-auto-detect" onclick="autoDetectMetadata()" class="bg-[#0f172a] hover:bg-[#1e293b] active:scale-95 text-white text-xs font-bold px-4 py-2.5 sm:py-2 rounded-xl shrink-0 flex items-center justify-center gap-1.5 transition-all shadow-xs">
                            <i class="fas fa-sync-alt text-xs" id="detect-icon"></i>
                            <span>Auto Detect</span>
                        </button>
                    </div>
                    <p id="detect-msg" class="text-[11px] mt-1.5 hidden"></p>
                </div>

                <!-- Thumbnail & Detection Live Preview Box -->
                <div id="preview-box" class="hidden p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-3">
                    <div class="relative w-12 sm:w-14 aspect-[9/16] rounded-lg overflow-hidden bg-black shrink-0 border border-slate-200 shadow-xs">
                        <img id="preview-thumb" src="" alt="Thumbnail" class="w-full h-full object-cover">
                    </div>
                    <div class="min-w-0 flex-1">
                        <div id="preview-title" class="text-xs font-bold text-slate-800 line-clamp-2 leading-snug"></div>
                        <div id="preview-meta" class="text-[10px] text-slate-400 mt-1 flex items-center gap-2"></div>
                    </div>
                </div>

                <!-- Video Title -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Video Title</label>
                    <input type="text" name="title" id="form-title" placeholder="Auto-filled from YouTube or enter custom title" class="w-full text-sm sm:text-xs px-3.5 py-2.5 sm:py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 placeholder-slate-400">
                </div>

                <!-- Duration & Badge Tag (Responsive: 1 col on mobile, 2 col on sm+) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Duration</label>
                        <input type="text" name="duration" id="form-duration" value="" placeholder="e.g. 0:30 (Auto-detected)" class="w-full text-sm sm:text-xs px-3.5 py-2.5 sm:py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Badge Tag</label>
                        <input type="text" name="badge_tag" id="form-badge-tag" value="VERTICAL SHORT" placeholder="VERTICAL SHORT" class="w-full text-sm sm:text-xs px-3.5 py-2.5 sm:py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>

                <!-- View Count & Sort Order (Responsive: 1 col on mobile, 2 col on sm+) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">View Count</label>
                        <input type="text" name="views_count" id="form-views-count" value="" placeholder="e.g. 1,779,370 (Auto-detected)" class="w-full text-sm sm:text-xs px-3.5 py-2.5 sm:py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                        <input type="hidden" name="views_badge" id="form-views-badge" value="">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Sort Order</label>
                        <input type="number" name="sort_order" id="form-sort-order" value="0" class="w-full text-sm sm:text-xs px-3.5 py-2.5 sm:py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>

                <!-- Promote Linked Product -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700">
                            Promote Linked Product <span class="text-slate-400 font-normal hidden sm:inline">(Optional Shoppable Reel CTA)</span>
                        </label>
                        <span class="text-[10px] text-emerald-600 font-semibold">Floating Buy Card</span>
                    </div>
                    <select name="product_id" id="form-product-id" onchange="updateProductPreview(this.value)" class="w-full text-sm sm:text-xs px-3 py-2.5 sm:py-2 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-slate-700">
                        <option value="">-- No Product Linked (Watch-only Reel) --</option>
                        <?php foreach ($all_products as $p): ?>
                            <option value="<?php echo $p['id']; ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>" data-price="<?php echo $p['price']; ?>" data-img="../<?php echo htmlspecialchars($p['image']); ?>" data-stock="<?php echo $p['stock']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?> (₹<?php echo number_format($p['price'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Dashed note box -->
                    <div id="no-product-note" class="mt-2 p-2.5 sm:p-3 bg-sky-50/40 rounded-xl border border-dashed border-sky-200 text-[11px] text-slate-500 flex items-center gap-2">
                        <i class="fas fa-shopping-cart text-slate-400 text-xs shrink-0"></i>
                        <span>No product linked. Users will watch reel without buy card.</span>
                    </div>

                    <!-- Product Preview Card -->
                    <div id="product-card-preview" class="mt-2 p-2.5 sm:p-3 bg-emerald-50/50 rounded-xl border border-emerald-100 flex items-center gap-3 hidden">
                        <img id="prod-preview-img" src="" alt="" class="w-10 h-10 rounded-lg object-cover bg-white border border-emerald-200 shrink-0">
                        <div class="min-w-0 flex-1">
                            <div id="prod-preview-name" class="text-xs font-bold text-slate-900 truncate"></div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span id="prod-preview-price" class="text-xs font-black text-emerald-700"></span>
                                <span id="prod-preview-stock" class="text-[10px] font-semibold text-slate-500"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Homepage Visibility -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Homepage Visibility</label>
                    <select name="status" id="form-status" class="w-full text-sm sm:text-xs px-3 py-2.5 sm:py-2 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-slate-700">
                        <option value="active">🟢 Active (Visible in Homepage Reels section)</option>
                        <option value="draft">⚪ Inactive / Draft (Hidden from storefront)</option>
                    </select>
                </div>

            </div>

            <!-- Sticky Modal Footer (Always visible & accessible on all screens) -->
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 bg-white border-t border-slate-200 shrink-0 flex items-center justify-between gap-3 shadow-[0_-4px_16px_rgba(0,0,0,0.04)] z-20 pb-[max(1rem,env(safe-area-inset-bottom))]">
                <button type="button" onclick="closeReelModal()" class="px-4 sm:px-5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold active:scale-95 transition-all">
                    Cancel
                </button>
                <button type="submit" id="btn-submit-reel" class="flex-1 sm:flex-initial justify-center bg-[#00875A] hover:bg-[#00704A] text-white text-xs font-bold px-5 py-2.5 rounded-xl flex items-center gap-2 shadow-sm active:scale-95 transition-all">
                    <i class="fas fa-check text-xs"></i>
                    <span>Save & Publish Reel</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================
     JAVASCRIPT: ADMIN REELS INTERACTIVITY & AUTO DETECT
     ======================================================== -->
<script>
// --- CLIENT-SIDE YOUTUBE ID EXTRACTOR ---
function parseYouTubeIdClient(url) {
    if (!url) return null;
    url = url.trim();
    const patterns = [
        /(?:youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/i,
        /(?:youtube\.com\/watch\?(?:.*&)?v=)([a-zA-Z0-9_-]{11})/i,
        /(?:youtu\.be\/)([a-zA-Z0-9_-]{11})/i,
        /(?:youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/i,
        /(?:youtube\.com\/v\/)([a-zA-Z0-9_-]{11})/i,
        /^([a-zA-Z0-9_-]{11})$/
    ];
    for (let p of patterns) {
        const match = url.match(p);
        if (match && match[1]) return match[1];
    }
    return null;
}

// --- SORTABLE.JS DRAG-AND-DROP REORDERING ---
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('sortable-reels');
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

                fetch('reels.php?action=reorder', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order: order })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (typeof showToast === 'function') showToast('Reel sequence updated!');
                    }
                })
                .catch(() => {});
            }
        });
    }
});

// --- MODAL CONTROLS ---
function openAddReelModal() {
    document.getElementById('modal-heading').textContent = 'Add New Reel / Video';
    document.getElementById('form-reel-id').value = '0';
    document.getElementById('form-youtube-url').value = '';
    document.getElementById('form-thumbnail-url').value = '';
    document.getElementById('form-title').value = '';
    document.getElementById('form-duration').value = '';
    document.getElementById('form-duration').placeholder = 'Auto-detecting on URL input...';
    document.getElementById('form-badge-tag').value = 'VERTICAL SHORT';
    document.getElementById('form-views-count').value = '';
    document.getElementById('form-views-count').placeholder = 'Auto-detecting on URL input (e.g. 1,779,370)...';
    document.getElementById('form-views-badge').value = '';
    document.getElementById('form-sort-order').value = '0';
    document.getElementById('form-product-id').value = '';
    document.getElementById('form-status').value = 'active';
    document.getElementById('preview-box').classList.add('hidden');
    document.getElementById('detect-msg').classList.add('hidden');
    updateProductPreview('');
    document.body.classList.add('admin-modal-open');
    document.body.style.overflow = 'hidden';
    const adminNav = document.getElementById('admin-mobile-bottom-nav');
    if (adminNav) adminNav.style.setProperty('display', 'none', 'important');
    document.getElementById('reel-modal').classList.remove('hidden');
}

function editReel(r) {
    document.getElementById('modal-heading').textContent = 'Edit Reel / Video';
    document.getElementById('form-reel-id').value = r.id;
    document.getElementById('form-youtube-url').value = r.youtube_url;
    document.getElementById('form-thumbnail-url').value = r.thumbnail_url;
    document.getElementById('form-title').value = r.title;
    document.getElementById('form-duration').value = r.duration || '';
    document.getElementById('form-duration').placeholder = 'e.g. 0:30 or 1:45';
    document.getElementById('form-badge-tag').value = r.badge_tag || 'VERTICAL SHORT';
    document.getElementById('form-views-count').value = (r.views_count && r.views_count > 0) ? Number(r.views_count).toLocaleString() : (r.views_badge || '');
    document.getElementById('form-views-count').placeholder = 'e.g. 1,779,370';
    document.getElementById('form-views-badge').value = r.views_badge || '';
    document.getElementById('form-sort-order').value = r.sort_order || '0';
    document.getElementById('form-product-id').value = r.product_id || '';
    document.getElementById('form-status').value = r.status || 'active';

    if (r.thumbnail_url) {
        document.getElementById('preview-thumb').src = r.thumbnail_url;
        document.getElementById('preview-title').textContent = r.title;
        const durBadge = r.duration ? `<span class="bg-slate-900 text-white text-[9px] font-bold px-1.5 py-0.5 rounded leading-none inline-flex items-center gap-1"><i class="fas fa-clock text-[8px] text-emerald-400"></i> ${r.duration}</span>` : '';
        const viewBadge = (r.views_badge || r.views_count) ? `<span class="bg-emerald-950 text-emerald-300 text-[9px] font-bold px-1.5 py-0.5 rounded leading-none inline-flex items-center gap-1"><i class="fas fa-eye text-[8px]"></i> ${r.views_badge || Number(r.views_count).toLocaleString()}</span>` : '';
        document.getElementById('preview-meta').innerHTML = `<span class="text-rose-600 font-bold">ID: ${r.youtube_id}</span> ${durBadge ? '• ' + durBadge : ''} ${viewBadge ? '• ' + viewBadge : ''}`;
        document.getElementById('preview-box').classList.remove('hidden');
    } else {
        document.getElementById('preview-box').classList.add('hidden');
    }

    document.getElementById('detect-msg').classList.add('hidden');
    updateProductPreview(r.product_id || '');
    document.body.classList.add('admin-modal-open');
    document.body.style.overflow = 'hidden';
    const adminNav = document.getElementById('admin-mobile-bottom-nav');
    if (adminNav) adminNav.style.setProperty('display', 'none', 'important');
    document.getElementById('reel-modal').classList.remove('hidden');
}

function closeReelModal() {
    document.body.classList.remove('admin-modal-open');
    document.body.style.overflow = '';
    const adminNav = document.getElementById('admin-mobile-bottom-nav');
    if (adminNav) adminNav.style.removeProperty('display');
    document.getElementById('reel-modal').classList.add('hidden');
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeReelModal();
});

// --- BULLETPROOF AUTO-DETECT METADATA ---
async function autoDetectMetadata() {
    const urlInput = document.getElementById('form-youtube-url');
    const rawUrl = urlInput.value.trim();
    const btn = document.getElementById('btn-auto-detect');
    const icon = document.getElementById('detect-icon');
    const msg = document.getElementById('detect-msg');
    const durationInput = document.getElementById('form-duration');
    const viewsCountInput = document.getElementById('form-views-count');
    const viewsBadgeInput = document.getElementById('form-views-badge');

    if (!rawUrl) {
        msg.textContent = 'Please paste a valid YouTube Shorts or Video URL first.';
        msg.className = 'text-[11px] mt-1 text-rose-600 font-semibold';
        msg.classList.remove('hidden');
        return;
    }

    // 1. Instant Client-Side Parsing
    const videoId = parseYouTubeIdClient(rawUrl);
    if (!videoId) {
        msg.textContent = 'Could not find a valid 11-character YouTube video ID in this URL.';
        msg.className = 'text-[11px] mt-1 text-rose-600 font-semibold';
        msg.classList.remove('hidden');
        return;
    }

    // Set duration & view count inputs to detecting state if empty
    if (!durationInput.value) {
        durationInput.placeholder = 'Detecting duration...';
    }
    if (!viewsCountInput.value) {
        viewsCountInput.placeholder = 'Detecting view count...';
    }

    // Immediately show thumbnail preview and canonical link
    const guaranteedThumb = `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`;
    document.getElementById('form-thumbnail-url').value = guaranteedThumb;
    document.getElementById('preview-thumb').src = guaranteedThumb;
    document.getElementById('preview-meta').innerHTML = `<span class="text-rose-600 font-bold">ID: ${videoId}</span> • <span class="text-slate-400">Detecting info...</span>`;
    document.getElementById('preview-box').classList.remove('hidden');

    icon.classList.add('fa-spin');
    btn.disabled = true;
    msg.classList.add('hidden');

    let titleDetected = false;
    let durationDetected = false;
    let viewsDetected = false;
    let resData = null;

    // 2. Query Backend Endpoint (YouTubei Player API + HTML Scraper for exact duration & views)
    try {
        const endpoint = `reels.php?action=fetch_meta&url=${encodeURIComponent(rawUrl)}&_t=${Date.now()}`;
        const backendRes = await fetch(endpoint, { cache: 'no-store' });
        if (backendRes.ok) {
            const data = await backendRes.json();
            if (data && data.success) {
                resData = data;
                if (data.title) {
                    document.getElementById('form-title').value = data.title;
                    document.getElementById('preview-title').textContent = data.title;
                    titleDetected = true;
                }
                if (data.thumbnail_url) {
                    document.getElementById('form-thumbnail-url').value = data.thumbnail_url;
                    document.getElementById('preview-thumb').src = data.thumbnail_url;
                }
                if (data.duration) {
                    durationInput.value = data.duration;
                    durationDetected = true;
                }
                if (data.views_raw && data.views_raw > 0) {
                    viewsCountInput.value = Number(data.views_raw).toLocaleString();
                    viewsBadgeInput.value = data.views_badge || '';
                    viewsDetected = true;
                } else if (data.views_badge) {
                    viewsCountInput.value = data.views_badge;
                    viewsBadgeInput.value = data.views_badge;
                    viewsDetected = true;
                }

                const durBadge = data.duration ? `<span class="bg-slate-900 text-white text-[9px] font-bold px-1.5 py-0.5 rounded leading-none inline-flex items-center gap-1"><i class="fas fa-clock text-[8px] text-emerald-400"></i> ${data.duration}</span>` : '';
                const viewBadge = (data.views_badge || data.views_raw) ? `<span class="bg-emerald-950 text-emerald-300 text-[9px] font-bold px-1.5 py-0.5 rounded leading-none inline-flex items-center gap-1"><i class="fas fa-eye text-[8px]"></i> ${data.views_badge || Number(data.views_raw).toLocaleString()}</span>` : '';
                document.getElementById('preview-meta').innerHTML = `<span class="text-rose-600 font-bold">ID: ${videoId}</span> ${durBadge ? '• ' + durBadge : ''} ${viewBadge ? '• ' + viewBadge : ''}`;
            }
        }
    } catch (e) {
        // Backend fetch warning swallowed gracefully to allow direct CORS fallback
    }

    // 3. Direct YouTube oEmbed fallback if title not yet found
    if (!titleDetected) {
        try {
            const ytDirectUrl = `https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=${videoId}&format=json`;
            const ytRes = await fetch(ytDirectUrl);
            if (ytRes.ok) {
                const ytData = await ytRes.json();
                if (ytData && ytData.title) {
                    document.getElementById('form-title').value = ytData.title;
                    document.getElementById('preview-title').textContent = ytData.title;
                    titleDetected = true;
                    if (ytData.thumbnail_url) {
                        document.getElementById('form-thumbnail-url').value = ytData.thumbnail_url;
                        document.getElementById('preview-thumb').src = ytData.thumbnail_url;
                    }
                }
            }
        } catch (e) {
            // Direct CORS fallback finished
        }
    }

    if (!durationInput.value) {
        durationInput.placeholder = 'e.g. 0:30 (Enter manually if needed)';
    }
    if (!viewsCountInput.value) {
        viewsCountInput.placeholder = 'e.g. 1,779,370 (Enter manually if needed)';
    }

    icon.classList.remove('fa-spin');
    btn.disabled = false;

    // 4. Clear, user-friendly feedback
    if (titleDetected) {
        let details = [];
        if (durationDetected) details.push(`duration (${durationInput.value})`);
        if (viewsDetected && resData) details.push(`views (${resData.views_badge || viewsCountInput.value})`);
        msg.textContent = details.length > 0 
            ? `✓ Title, ${details.join(' and ')} detected automatically!`
            : '✓ Video details retrieved!';
        msg.className = 'text-[11px] mt-1 text-emerald-600 font-semibold';
        msg.classList.remove('hidden');
    } else {
        if (!document.getElementById('form-title').value) {
            document.getElementById('preview-title').textContent = 'YouTube Short #' + videoId;
        }
        msg.textContent = `✓ YouTube Video ID verified (${videoId}). Preview generated. You can enter or edit title, duration & views above.`;
        msg.className = 'text-[11px] mt-1 text-slate-500 font-medium';
        msg.classList.remove('hidden');
    }
}

// Auto-trigger detect when user pastes into URL input
document.getElementById('form-youtube-url')?.addEventListener('paste', () => {
    setTimeout(autoDetectMetadata, 80);
});

// --- PRODUCT PREVIEW IN FORM ---
function updateProductPreview(prodId) {
    const card = document.getElementById('product-card-preview');
    const note = document.getElementById('no-product-note');
    const select = document.getElementById('form-product-id');

    if (!prodId) {
        card.classList.add('hidden');
        note.classList.remove('hidden');
        return;
    }

    const option = select.options[select.selectedIndex];
    if (option) {
        const name = option.getAttribute('data-name');
        const price = option.getAttribute('data-price');
        const img = option.getAttribute('data-img');
        const stock = parseInt(option.getAttribute('data-stock') || '0');

        document.getElementById('prod-preview-name').textContent = name;
        document.getElementById('prod-preview-price').textContent = '₹' + parseFloat(price).toFixed(2);
        document.getElementById('prod-preview-stock').textContent = stock > 0 ? `In Stock (${stock} available)` : 'Out of Stock';
        document.getElementById('prod-preview-stock').className = stock > 0 ? 'text-[10px] font-semibold text-emerald-600' : 'text-[10px] font-semibold text-rose-600';
        document.getElementById('prod-preview-img').src = img || '../assets/images/logo.svg';

        card.classList.remove('hidden');
        note.classList.add('hidden');
    }
}

// --- TOGGLE REEL STATUS ---
async function toggleReelStatus(id, btn) {
    const originalText = btn.querySelector('.status-text').textContent;
    btn.querySelector('.status-text').textContent = '...';
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('id', id);

        const res = await fetch('reels.php?action=toggle_status', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            if (data.new_status === 'active') {
                btn.className = 'status-btn cursor-pointer inline-flex items-center gap-1 text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full border transition-all bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100';
                btn.querySelector('.status-text').textContent = 'Active';
            } else {
                btn.className = 'status-btn cursor-pointer inline-flex items-center gap-1 text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full border transition-all bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100';
                btn.querySelector('.status-text').textContent = 'Draft';
            }
            if (typeof showToast === 'function') showToast(`Status updated to ${data.new_status}!`);
        } else {
            btn.querySelector('.status-text').textContent = originalText;
        }
    } catch (e) {
        btn.querySelector('.status-text').textContent = originalText;
    } finally {
        btn.disabled = false;
    }
}

// --- TOGGLE AUTOPLAY CARDS ---
async function toggleAutoplayCards(isChecked) {
    const pill = document.getElementById('autoplay-status-pill');
    const statBadge = document.getElementById('stat-mode-badge');

    try {
        const res = await fetch('reels.php?action=toggle_autoplay');
        const data = await res.json();

        if (data.success) {
            const isLive = data.autoplay === '1';
            pill.textContent = isLive ? 'ENABLED' : 'DISABLED';
            pill.className = isLive ? 'bg-emerald-100 text-emerald-800 text-[9px] font-black uppercase px-2 py-0.5 rounded' : 'bg-rose-100 text-rose-700 text-[9px] font-black uppercase px-2 py-0.5 rounded';

            if (statBadge) {
                statBadge.textContent = isLive ? 'LIVE CARDS' : 'STATIC POSTERS';
                statBadge.className = isLive ? 'bg-indigo-50 text-indigo-700 border-indigo-200 text-[10px] font-extrabold px-2 py-0.5 rounded border uppercase' : 'bg-rose-50 text-rose-600 border-rose-200 text-[10px] font-extrabold px-2 py-0.5 rounded border uppercase';
            }
            if (typeof showToast === 'function') showToast(`Live cards mode ${isLive ? 'enabled' : 'disabled'}!`);
        }
    } catch (e) {}
}

// --- TOGGLE SECTION VISIBILITY ON HOMEPAGE ---
async function toggleSectionVisibility(isChecked) {
    const pill = document.getElementById('section-visibility-pill');
    const statBadge = document.getElementById('stat-visibility-badge');
    const iconBox = document.getElementById('section-icon-box');
    const icon = document.getElementById('section-icon');
    const statIconBox = document.getElementById('stat-vis-icon-box');
    const statIcon = document.getElementById('stat-vis-icon');

    try {
        const res = await fetch('reels.php?action=toggle_visibility');
        const data = await res.json();

        if (data.success) {
            const isVisible = data.enabled === '1';
            
            // Update Pill
            if (pill) {
                pill.textContent = isVisible ? 'VISIBLE ON HOMEPAGE' : 'HIDDEN FROM HOMEPAGE';
                pill.className = isVisible 
                    ? 'bg-emerald-100 text-emerald-800 text-[9px] font-black uppercase px-2 py-0.5 rounded' 
                    : 'bg-rose-100 text-rose-700 text-[9px] font-black uppercase px-2 py-0.5 rounded';
            }

            // Update Icon
            if (iconBox) {
                iconBox.className = isVisible 
                    ? 'w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0 mt-0.5' 
                    : 'w-9 h-9 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center shrink-0 mt-0.5';
            }
            if (icon) {
                icon.className = isVisible ? 'fas fa-eye' : 'fas fa-eye-slash';
            }

            // Update Stat Card Badge
            if (statBadge) {
                statBadge.textContent = isVisible ? 'VISIBLE' : 'HIDDEN';
                statBadge.className = isVisible 
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 text-[10px] font-extrabold px-2 py-0.5 rounded border uppercase' 
                    : 'bg-rose-50 text-rose-600 border-rose-200 text-[10px] font-extrabold px-2 py-0.5 rounded border uppercase';
            }
            if (statIconBox) {
                statIconBox.className = isVisible 
                    ? 'w-10 h-10 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-lg shrink-0' 
                    : 'w-10 h-10 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-lg shrink-0';
            }
            if (statIcon) {
                statIcon.className = isVisible ? 'fas fa-eye' : 'fas fa-eye-slash';
            }

            if (typeof showToast === 'function') {
                showToast(`Reels section is now ${isVisible ? 'visible on' : 'hidden from'} the homepage!`);
            }
        }
    } catch (e) {
        alert('Error updating section visibility.');
    }
}

// --- DELETE REEL ---
async function deleteReel(id, title) {
    if (!confirm(`Are you sure you want to delete "${title}"?`)) return;

    try {
        const formData = new FormData();
        formData.append('id', id);

        const res = await fetch('reels.php?action=delete', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) row.remove();
            if (typeof showToast === 'function') showToast('Reel deleted successfully');
        } else {
            alert(data.message || 'Error deleting reel');
        }
    } catch (e) {
        alert('Network error while deleting reel');
    }
}

// --- SEARCH & FILTER TABLE ---
function filterReelsTable() {
    const query = document.getElementById('filter-input').value.toLowerCase();
    const rows = document.querySelectorAll('.reel-row');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}

// --- BULK SELECTION & ACTIONS ---
function toggleSelectAll(master) {
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.checked = master.checked;
    });
}

async function applyBulkAction() {
    const action = document.getElementById('bulk-action-select').value;
    if (!action) {
        alert('Please choose a bulk action first.');
        return;
    }

    const selectedIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) {
        alert('Please select at least one reel to apply this action.');
        return;
    }

    if (action === 'delete') {
        if (!confirm(`Are you sure you want to delete ${selectedIds.length} selected reels? This cannot be undone.`)) return;
    }

    try {
        const res = await fetch('reels.php?action=bulk_action', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: action, ids: selectedIds })
        });
        const data = await res.json();

        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Error applying bulk action');
        }
    } catch (e) {
        alert('Network error while processing bulk action');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
