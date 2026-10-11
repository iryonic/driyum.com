<?php
/**
 * DRIYUM - Autonomous Image Auto-Recovery Handler
 * If an uploaded image was wiped or missing from disk, this endpoint 
 * restores the file from the database backup, writes it back to disk, 
 * and streams it directly to the browser with correct headers.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$requested_file = $_GET['file'] ?? '';
$requested_file = ltrim(str_replace(['..', '\\'], ['', '/'], $requested_file), '/');

if (empty($requested_file)) {
    serve_placeholder();
    exit;
}

$rel_path = 'assets/images/' . $requested_file;
$full_path = __DIR__ . '/' . $rel_path;

// 1. If it already exists on disk, serve it directly
if (file_exists($full_path)) {
    serve_file($full_path);
    exit;
}

// 2. Attempt restoration from database
if (function_exists('restore_image_from_db') && restore_image_from_db($rel_path)) {
    if (file_exists($full_path)) {
        serve_file($full_path);
        exit;
    }
}

// 3. Fallback: serve branded placeholder
serve_placeholder();
exit;

function serve_file($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml'
    ];
    $mime = $mimes[$ext] ?? 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: public, max-age=86400');
    readfile($path);
}

function serve_placeholder() {
    $placeholder = __DIR__ . '/assets/images/placeholder.png';
    if (file_exists($placeholder)) {
        serve_file($placeholder);
    } else {
        header('HTTP/1.0 404 Not Found');
    }
}
