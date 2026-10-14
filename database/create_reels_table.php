<?php
require_once __DIR__ . '/../config/database.php';

$conn = get_db_connection();

echo "Running Reels & Shorts Migration...\n";

// 1. Create `reels` table
$sql_reels = "CREATE TABLE IF NOT EXISTS `reels` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `youtube_id` VARCHAR(50) NOT NULL,
  `youtube_url` VARCHAR(500) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `thumbnail_url` VARCHAR(500) DEFAULT NULL,
  `duration` VARCHAR(50) DEFAULT '0:45',
  `badge_tag` VARCHAR(100) DEFAULT 'VERTICAL SHORT',
  `views_badge` VARCHAR(50) DEFAULT '120K',
  `product_id` INT(11) DEFAULT NULL,
  `status` ENUM('active', 'draft', 'inactive') DEFAULT 'active',
  `sort_order` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`, `sort_order`),
  KEY `idx_youtube_id` (`youtube_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_reels)) {
    echo "✓ `reels` table verified/created successfully.\n";
} else {
    echo "✗ Failed to create `reels` table: " . $conn->error . "\n";
}

// 2. Create `reel_products` mapping table (multi-product relational support)
$sql_reel_prods = "CREATE TABLE IF NOT EXISTS `reel_products` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `reel_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 1,
  `sort_order` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reel_product` (`reel_id`, `product_id`),
  KEY `idx_reel_id` (`reel_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_reel_prods)) {
    echo "✓ `reel_products` table verified/created successfully.\n";
} else {
    echo "✗ Failed to create `reel_products` table: " . $conn->error . "\n";
}

// 3. Ensure `reels_autoplay_cards` setting exists
$setting_check = fetch_one("SELECT `value` FROM settings WHERE `key` = 'reels_autoplay_cards'");
if (!$setting_check) {
    execute_query("INSERT INTO settings (`key`, `value`) VALUES ('reels_autoplay_cards', '0')");
    echo "✓ Initialized `reels_autoplay_cards` setting (default: 0 / disabled).\n";
}

// 4. Seed sample reels if table is empty
$count = fetch_one("SELECT COUNT(*) as c FROM reels")['c'] ?? 0;
if ($count == 0) {
    echo "Seeding initial reels & shorts from reference data...\n";
    
    // Check available products to link
    $prods = fetch_all("SELECT id, name FROM products WHERE is_active = 1 ORDER BY id ASC LIMIT 5");
    $prod1 = $prods[0]['id'] ?? null;
    $prod2 = $prods[1]['id'] ?? null;
    $prod3 = $prods[2]['id'] ?? null;
    $prod4 = $prods[3]['id'] ?? null;

    $sample_reels = [
        [
            'youtube_id' => 'dQw4w9WgXcQ', // fallback valid ID
            'youtube_url' => 'https://www.youtube.com/shorts/3Z_yWf-7x1s',
            'real_yt_id' => '3Z_yWf-7x1s',
            'title' => 'HBO Just Went Crazy With Harry Potter 🤯',
            'thumbnail_url' => 'https://i.ytimg.com/vi/3Z_yWf-7x1s/hqdefault.jpg',
            'duration' => '0:45',
            'badge_tag' => 'VERTICAL SHORT',
            'views_badge' => '120K',
            'product_id' => $prod1,
            'status' => 'active',
            'sort_order' => 1
        ],
        [
            'youtube_id' => '7ghhRHRP6t4',
            'youtube_url' => 'https://www.youtube.com/shorts/7ghhRHRP6t4',
            'real_yt_id' => '7ghhRHRP6t4',
            'title' => 'Turning A Tree Trunk Into A Canoe 🛶',
            'thumbnail_url' => 'https://i.ytimg.com/vi/7ghhRHRP6t4/hqdefault.jpg',
            'duration' => '0:45',
            'badge_tag' => 'VERTICAL SHORT',
            'views_badge' => '120K',
            'product_id' => $prod2,
            'status' => 'active',
            'sort_order' => 2
        ],
        [
            'youtube_id' => 'kJQP7kiw5Fk',
            'youtube_url' => 'https://www.youtube.com/shorts/kJQP7kiw5Fk',
            'real_yt_id' => 'kJQP7kiw5Fk',
            'title' => 'This Motorola Phone Can Literally Bend! 🤯 #shorts #smartphone',
            'thumbnail_url' => 'https://i.ytimg.com/vi/kJQP7kiw5Fk/hqdefault.jpg',
            'duration' => '0:45',
            'badge_tag' => 'VERTICAL SHORT',
            'views_badge' => '120K',
            'product_id' => $prod3,
            'status' => 'active',
            'sort_order' => 3
        ],
        [
            'youtube_id' => 'fJ9rUzIMcZQ',
            'youtube_url' => 'https://www.youtube.com/shorts/fJ9rUzIMcZQ',
            'real_yt_id' => 'fJ9rUzIMcZQ',
            'title' => 'Samay Raina on Legacy Finale Episode | Raftaar | Krsna',
            'thumbnail_url' => 'https://i.ytimg.com/vi/fJ9rUzIMcZQ/hqdefault.jpg',
            'duration' => '0:45',
            'badge_tag' => 'VERTICAL SHORT',
            'views_badge' => '120K',
            'product_id' => $prod4,
            'status' => 'active',
            'sort_order' => 4
        ]
    ];

    foreach ($sample_reels as $r) {
        $stmt = $conn->prepare("INSERT INTO reels (youtube_id, youtube_url, title, thumbnail_url, duration, badge_tag, views_badge, product_id, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssisii", $r['real_yt_id'], $r['youtube_url'], $r['title'], $r['thumbnail_url'], $r['duration'], $r['badge_tag'], $r['views_badge'], $r['product_id'], $r['status'], $r['sort_order']);
        $stmt->execute();
        $new_id = $stmt->insert_id;
        
        if ($r['product_id']) {
            $stmt_p = $conn->prepare("INSERT INTO reel_products (reel_id, product_id, is_primary, sort_order) VALUES (?, ?, 1, 1)");
            $stmt_p->bind_param("ii", $new_id, $r['product_id']);
            $stmt_p->execute();
        }
        echo "  - Added sample reel ID $new_id: '{$r['title']}'\n";
    }
}

echo "Migration Complete!\n";
