<?php
/**
 * Simple Caching System
 * Uses in-memory array for request-level caching,
 * and File system for cross-request persistent caching.
 * Can be easily swapped to Redis or Memcached later.
 */

class Cache {
    private static $runtime_cache = [];
    private static $cache_dir = __DIR__ . '/../cache/';
    private static $enabled = true; // Set to false to bypass cache entirely

    public static function init() {
        if (!self::$enabled) return;
        if (!file_exists(self::$cache_dir)) {
            @mkdir(self::$cache_dir, 0777, true);
        }
    }

    /**
     * Get a value from the cache
     */
    public static function get($key) {
        if (!self::$enabled) return false;

        // 1. Check in-memory fast cache first
        if (isset(self::$runtime_cache[$key])) {
            return self::$runtime_cache[$key];
        }

        // 2. Check persistent file cache
        $file = self::$cache_dir . md5($key) . '.cache';
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if ($content) {
                $data = json_decode($content, true);
                if ($data && isset($data['expiry']) && isset($data['data'])) {
                    if ($data['expiry'] > time() || $data['expiry'] === 0) {
                        self::$runtime_cache[$key] = $data['data'];
                        return $data['data'];
                    } else {
                        // Expired
                        @unlink($file);
                    }
                }
            }
        }

        return false;
    }

    /**
     * Set a value in the cache
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param int $ttl Time to live in seconds (0 = never expire)
     */
    public static function set($key, $value, $ttl = 3600) {
        if (!self::$enabled) return false;

        self::$runtime_cache[$key] = $value;

        // Save to file
        $file = self::$cache_dir . md5($key) . '.cache';
        $expiry = $ttl > 0 ? (time() + $ttl) : 0;
        
        $payload = json_encode([
            'expiry' => $expiry,
            'data' => $value
        ]);

        @file_put_contents($file, $payload);
        return true;
    }

    /**
     * Remember helper - fetches if exists, otherwise runs callback and stores
     */
    public static function remember($key, $ttl, $callback) {
        $cached = self::get($key);
        if ($cached !== false) {
            return $cached;
        }

        $value = call_user_func($callback);
        
        // Only cache non-empty, non-false arrays/strings (optional safeguard)
        if ($value !== false && $value !== null) {
            self::set($key, $value, $ttl);
        }
        
        return $value;
    }

    /**
     * Clear a specific key
     */
    public static function forget($key) {
        unset(self::$runtime_cache[$key]);
        $file = self::$cache_dir . md5($key) . '.cache';
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Clear all persistent cache
     */
    public static function flush() {
        self::$runtime_cache = [];
        $files = glob(self::$cache_dir . '*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}

// Initialize the cache directory on inclusion
Cache::init();
