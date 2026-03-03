USE driyum_db;

-- In case of corruption, dropping might fail with standard command.
-- But let's try the direct approach first.
DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`key`, `value`, `updated_at`) VALUES
('announcement_text', '🍎FREE SHIPPING ON ORDERS ABOVE 500  🍌 SUBSCRIBE TO DRIYUM 🥝', '2026-01-23 09:35:54'),
('facebook_url', 'https://www.facebook.com/people/Driyum-Foods/61585416636650/?mibextid=wwXIfr&amp;rdid=BDRmnaJ3qy4ccL3W&amp;share_url=https%3A%2F%2Fwww.facebook.com%2Fshare%2F1DPT8KEgt7%2F%3Fmibextid%3DwwXIfr', '2026-01-23 09:46:25'),
('footer_description', 'Redefining the art of snacking with premium, mountain-fresh indulgence. Naturally sweet, unapologetically bold.', '2026-01-19 10:55:09'),
('free_shipping_threshold', '500', '2026-01-19 10:41:25'),
('instagram_url', 'https://www.instagram.com/driyumfoods?utm_source=ig_web_button_share_sheet&amp;igsh=ZDNlZDc0MzIxNw%3D%3D', '2026-01-23 09:46:25'),
('last_cron_run', '1769191233', '2026-01-23 18:00:33'),
('maintenance_mode', 'off', '2026-01-20 14:01:12'),
('order_prefix', '', '2026-01-19 10:41:37'),
('pinterest_url', '#', '2026-01-19 10:53:08'),
('seo_description', '', '2026-01-19 10:41:37'),
('store_name', 'DRIYUM', '2026-01-19 10:41:25'),
('support_email', 'contact@driyum.com', '2026-01-19 10:41:25'),
('support_phone', '+91 91030 00000', '2026-01-19 10:41:25'),
('tax_percentage', '10', '2026-01-19 10:45:57'),
('twitter_url', '#', '2026-01-19 10:53:08'),
('whatsapp_number', '6006801960', '2026-01-22 15:01:17'),
('youtube_url', '#', '2026-01-19 10:53:08');
