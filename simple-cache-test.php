<?php
/**
 * Simple Cache Tester
 * Quick test for basic cache availability
 * 
 * Usage: php simple-cache-test.php
 */

echo "🔍 Quick Cache Test\n";
echo str_repeat("=", 30) . "\n";

// Test OPcache
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    if ($status && $status['opcache_enabled']) {
        echo "✅ OPcache: Active\n";
    } else {
        echo "⚠️  OPcache: Inactive\n";
    }
} else {
    echo "❌ OPcache: Not available\n";
}

// Test APCu
if (function_exists('apcu_cache_info')) {
    try {
        apcu_cache_info();
        echo "✅ APCu: Available\n";
    } catch (Exception $e) {
        echo "⚠️  APCu: Error - " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ APCu: Not available\n";
}

// Test Memcached
if (class_exists('Memcached')) {
    echo "✅ Memcached: Extension loaded\n";
} else {
    echo "❌ Memcached: Not available\n";
}

// Test Redis
if (class_exists('Redis')) {
    echo "✅ Redis: Extension loaded\n";
} else {
    echo "❌ Redis: Not available\n";
}

// Test file cache directories
$cache_dirs = ['./cache', './tmp', './var/cache'];
$cache_found = false;
foreach ($cache_dirs as $dir) {
    if (is_dir($dir) && is_writable($dir)) {
        echo "✅ File cache: $dir (writable)\n";
        $cache_found = true;
    }
}
if (!$cache_found) {
    echo "❌ File cache: No writable cache directories found\n";
}

// Server info
echo "\n📊 Server Info:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";

echo "\nTest completed!\n";
?>
