<?php
/**
 * Server-Side Cache Manager
 * Tests for available cache systems and provides clearing functionality
 * 
 * Cheap Web Hosting - Cache Management Tool
 * Created: July 15, 2025
 * 
 * SECURITY WARNING: Change the default password before use!
 */

// Load configuration
$config_file = __DIR__ . '/cache-config.php';
if (file_exists($config_file)) {
    $config = include $config_file;
} else {
    // Default configuration if config file doesn't exist
    $config = [
        'admin_password' => 'CHANGE_THIS_PASSWORD_NOW',
        'allowed_ips' => ['127.0.0.1', '::1'],
        'cache_directories' => ['./cache', './tmp', './temp'],
        'cache_settings' => [
            'check_memcached' => true,
            'check_redis' => true,
            'memcached_host' => 'localhost',
            'memcached_port' => 11211,
            'redis_host' => 'localhost',
            'redis_port' => 6379,
        ]
    ];
}

// Security check - only allow execution from command line or with proper authentication
session_start();

// Check if running from command line
$is_cli = php_sapi_name() === 'cli';
$is_authenticated = false;

if (!$is_cli) {
    // Check IP whitelist
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($client_ip, $config['allowed_ips'])) {
        http_response_code(403);
        die('Access denied - IP not allowed');
    }
    
    if (isset($_POST['password']) && $_POST['password'] === $config['admin_password']) {
        $_SESSION['cache_authenticated'] = true;
        $is_authenticated = true;
    } elseif (isset($_SESSION['cache_authenticated']) && $_SESSION['cache_authenticated']) {
        $is_authenticated = true;
    }
    
    if (!$is_authenticated && !isset($_POST['password'])) {
        // Show login form
        showLoginForm();
        exit;
    }
    
    if (!$is_authenticated) {
        die('Authentication failed!');
    }
}

class CacheManager {
    private $results = [];
    private $available_caches = [];
    private $config = [];
    
    public function __construct($config = []) {
        $this->config = $config;
        $this->detectCaches();
    }
    
    /**
     * Detect available cache systems
     */
    private function detectCaches() {
        echo "🔍 Detecting available cache systems...\n\n";
        
        // OPcache (PHP opcode cache)
        $this->checkOPcache();
        
        // APCu (User cache)
        $this->checkAPCu();
        
        // Memcached
        $this->checkMemcached();
        
        // Redis
        $this->checkRedis();
        
        // File-based caches
        $this->checkFileCaches();
        
        // Page caches
        $this->checkPageCaches();
        
        // LiteSpeed Cache
        $this->checkLiteSpeedCache();
        
        // Cloudflare (if headers present)
        $this->checkCloudflare();
    }
    
    /**
     * Check OPcache
     */
    private function checkOPcache() {
        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status();
            if ($status !== false) {
                $this->available_caches['opcache'] = true;
                $this->results[] = "✅ OPcache: Available and " . ($status['opcache_enabled'] ? 'enabled' : 'disabled');
                if ($status['opcache_enabled']) {
                    $this->results[] = "   - Memory usage: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB";
                    $this->results[] = "   - Hit rate: " . round($status['opcache_statistics']['opcache_hit_rate'], 2) . "%";
                }
            } else {
                $this->results[] = "⚠️  OPcache: Available but not active";
            }
        } else {
            $this->results[] = "❌ OPcache: Not available";
        }
    }
    
    /**
     * Check APCu
     */
    private function checkAPCu() {
        if (function_exists('apcu_cache_info')) {
            try {
                $info = apcu_cache_info();
                $this->available_caches['apcu'] = true;
                $this->results[] = "✅ APCu: Available and active";
                $this->results[] = "   - Memory usage: " . round($info['mem_size'] / 1024 / 1024, 2) . " MB";
                $this->results[] = "   - Number of entries: " . $info['num_entries'];
            } catch (Exception $e) {
                $this->results[] = "⚠️  APCu: Available but error getting info: " . $e->getMessage();
            }
        } else {
            $this->results[] = "❌ APCu: Not available";
        }
    }
    
    /**
     * Check Memcached
     */
    private function checkMemcached() {
        if (class_exists('Memcached')) {
            $this->available_caches['memcached'] = true;
            $this->results[] = "✅ Memcached: Extension available";
            
            try {
                $memcached = new Memcached();
                $memcached->addServer('localhost', 11211);
                $version = $memcached->getVersion();
                if ($version !== false) {
                    $this->results[] = "   - Connected to server successfully";
                    $stats = $memcached->getStats();
                    if (!empty($stats)) {
                        $server_stats = reset($stats);
                        $this->results[] = "   - Memory usage: " . round($server_stats['bytes'] / 1024 / 1024, 2) . " MB";
                    }
                } else {
                    $this->results[] = "   - Cannot connect to Memcached server";
                }
            } catch (Exception $e) {
                $this->results[] = "   - Error connecting: " . $e->getMessage();
            }
        } else {
            $this->results[] = "❌ Memcached: Not available";
        }
    }
    
    /**
     * Check Redis
     */
    private function checkRedis() {
        if (class_exists('Redis')) {
            $this->available_caches['redis'] = true;
            $this->results[] = "✅ Redis: Extension available";
            
            try {
                $redis = new Redis();
                if ($redis->connect('localhost', 6379)) {
                    $info = $redis->info();
                    $this->results[] = "   - Connected to server successfully";
                    $this->results[] = "   - Memory usage: " . round($info['used_memory'] / 1024 / 1024, 2) . " MB";
                    $this->results[] = "   - Database keys: " . $redis->dbSize();
                } else {
                    $this->results[] = "   - Cannot connect to Redis server";
                }
            } catch (Exception $e) {
                $this->results[] = "   - Error connecting: " . $e->getMessage();
            }
        } else {
            $this->results[] = "❌ Redis: Not available";
        }
    }
    
    /**
     * Check file-based caches
     */
    private function checkFileCaches() {
        $cache_dirs = [
            './cache',
            './tmp',
            './temp',
            './var/cache',
            '../cache',
            '../tmp',
        ];
        
        $found_caches = [];
        foreach ($cache_dirs as $dir) {
            if (is_dir($dir) && is_writable($dir)) {
                $files = glob($dir . '/*');
                if (!empty($files)) {
                    $found_caches[] = $dir . ' (' . count($files) . ' files)';
                    $this->available_caches['file_cache_' . md5($dir)] = $dir;
                }
            }
        }
        
        if (!empty($found_caches)) {
            $this->results[] = "✅ File caches found:";
            foreach ($found_caches as $cache) {
                $this->results[] = "   - " . $cache;
            }
        } else {
            $this->results[] = "❌ No file caches found in common locations";
        }
    }
    
    /**
     * Check page caches
     */
    private function checkPageCaches() {
        $page_cache_dirs = [
            './wp-content/cache',
            './wp-content/cache/page_enhanced',
            './cache/page',
            '../public_html/cache',
        ];
        
        $found_page_caches = [];
        foreach ($page_cache_dirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*');
                if (!empty($files)) {
                    $found_page_caches[] = $dir . ' (' . count($files) . ' items)';
                    $this->available_caches['page_cache_' . md5($dir)] = $dir;
                }
            }
        }
        
        if (!empty($found_page_caches)) {
            $this->results[] = "✅ Page caches found:";
            foreach ($found_page_caches as $cache) {
                $this->results[] = "   - " . $cache;
            }
        }
    }
    
    /**
     * Check LiteSpeed Cache
     */
    private function checkLiteSpeedCache() {
        if (function_exists('litespeed_purge_all')) {
            $this->available_caches['litespeed'] = true;
            $this->results[] = "✅ LiteSpeed Cache: Available";
        } elseif (isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'litespeed') !== false) {
            $this->available_caches['litespeed'] = true;
            $this->results[] = "✅ LiteSpeed Server detected (cache may be available)";
        } else {
            $this->results[] = "❌ LiteSpeed Cache: Not detected";
        }
    }
    
    /**
     * Check Cloudflare
     */
    private function checkCloudflare() {
        if (isset($_SERVER['HTTP_CF_RAY']) || isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $this->available_caches['cloudflare'] = true;
            $this->results[] = "✅ Cloudflare: Detected (cache may be active)";
        } else {
            $this->results[] = "❌ Cloudflare: Not detected";
        }
    }
    
    /**
     * Clear all available caches
     */
    public function clearAllCaches() {
        $cleared = [];
        
        echo "\n🧹 Clearing available caches...\n\n";
        
        // Clear OPcache
        if (isset($this->available_caches['opcache']) && function_exists('opcache_reset')) {
            if (opcache_reset()) {
                $cleared[] = "✅ OPcache cleared successfully";
            } else {
                $cleared[] = "❌ Failed to clear OPcache";
            }
        }
        
        // Clear APCu
        if (isset($this->available_caches['apcu']) && function_exists('apcu_clear_cache')) {
            if (apcu_clear_cache()) {
                $cleared[] = "✅ APCu cache cleared successfully";
            } else {
                $cleared[] = "❌ Failed to clear APCu cache";
            }
        }
        
        // Clear Memcached
        if (isset($this->available_caches['memcached'])) {
            try {
                $memcached = new Memcached();
                $memcached->addServer('localhost', 11211);
                if ($memcached->flush()) {
                    $cleared[] = "✅ Memcached cleared successfully";
                } else {
                    $cleared[] = "❌ Failed to clear Memcached";
                }
            } catch (Exception $e) {
                $cleared[] = "❌ Memcached clear error: " . $e->getMessage();
            }
        }
        
        // Clear Redis
        if (isset($this->available_caches['redis'])) {
            try {
                $redis = new Redis();
                if ($redis->connect('localhost', 6379)) {
                    if ($redis->flushAll()) {
                        $cleared[] = "✅ Redis cleared successfully";
                    } else {
                        $cleared[] = "❌ Failed to clear Redis";
                    }
                }
            } catch (Exception $e) {
                $cleared[] = "❌ Redis clear error: " . $e->getMessage();
            }
        }
        
        // Clear file caches
        foreach ($this->available_caches as $key => $value) {
            if (strpos($key, 'file_cache_') === 0 || strpos($key, 'page_cache_') === 0) {
                $cleared[] = $this->clearDirectoryCache($value);
            }
        }
        
        // Clear LiteSpeed Cache
        if (isset($this->available_caches['litespeed'])) {
            if (function_exists('litespeed_purge_all')) {
                litespeed_purge_all();
                $cleared[] = "✅ LiteSpeed cache purged successfully";
            } else {
                // Try to create .htaccess purge rule
                $htaccess_rule = "\n# LiteSpeed Cache Purge\nRewriteEngine On\nRewriteRule .* - [E=cache-control:no-cache]\n";
                $cleared[] = "⚠️  LiteSpeed cache: Added purge rule to consider";
            }
        }
        
        return $cleared;
    }
    
    /**
     * Clear directory cache
     */
    private function clearDirectoryCache($dir) {
        try {
            $files = glob($dir . '/*');
            $deleted = 0;
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                } elseif (is_dir($file)) {
                    $this->clearDirectoryRecursive($file);
                    if (rmdir($file)) {
                        $deleted++;
                    }
                }
            }
            
            return "✅ Cleared $deleted items from $dir";
        } catch (Exception $e) {
            return "❌ Error clearing $dir: " . $e->getMessage();
        }
    }
    
    /**
     * Recursively clear directory
     */
    private function clearDirectoryRecursive($dir) {
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            } elseif (is_dir($file)) {
                $this->clearDirectoryRecursive($file);
                rmdir($file);
            }
        }
    }
    
    /**
     * Get detection results
     */
    public function getResults() {
        return $this->results;
    }
    
    /**
     * Get available caches
     */
    public function getAvailableCaches() {
        return $this->available_caches;
    }
}

/**
 * Show login form for web interface
 */
function showLoginForm() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Cache Manager - Authentication</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
            .form-group { margin: 15px 0; }
            input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
            button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #005a87; }
        </style>
    </head>
    <body>
        <h2>🔒 Cache Manager Access</h2>
        <form method="POST">
            <div class="form-group">
                <label>Admin Password:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Access Cache Manager</button>
        </form>
    </body>
    </html>
    <?php
}

// Main execution
if ($is_cli || $is_authenticated) {
    $cache_manager = new CacheManager($config);
    
    // Display results
    echo "📊 Cache Detection Results:\n";
    echo str_repeat("=", 50) . "\n";
    foreach ($cache_manager->getResults() as $result) {
        echo $result . "\n";
    }
    
    // Check if clear action is requested
    if (($is_cli && in_array('--clear', $argv)) || (!$is_cli && isset($_POST['action']) && $_POST['action'] === 'clear')) {
        $clear_results = $cache_manager->clearAllCaches();
        
        echo "\n📋 Cache Clearing Results:\n";
        echo str_repeat("=", 50) . "\n";
        foreach ($clear_results as $result) {
            echo $result . "\n";
        }
    }
    
    // Web interface
    if (!$is_cli) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Server Cache Manager</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
                .result { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; font-family: monospace; }
                .button { background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 10px 5px; }
                .button:hover { background: #c82333; }
                .success { color: #28a745; }
                .warning { color: #ffc107; }
                .error { color: #dc3545; }
                .logout { float: right; background: #6c757d; }
            </style>
        </head>
        <body>
            <h1>🚀 Server Cache Manager</h1>
            
            <form method="POST" style="float: right;">
                <input type="hidden" name="logout" value="1">
                <button type="submit" class="button logout">Logout</button>
            </form>
            
            <div class="result">
                <?php
                foreach ($cache_manager->getResults() as $result) {
                    $class = '';
                    if (strpos($result, '✅') !== false) $class = 'success';
                    elseif (strpos($result, '⚠️') !== false) $class = 'warning';
                    elseif (strpos($result, '❌') !== false) $class = 'error';
                    
                    echo '<div class="' . $class . '">' . htmlspecialchars($result) . '</div>';
                }
                ?>
            </div>
            
            <?php if (!empty($cache_manager->getAvailableCaches())): ?>
            <form method="POST">
                <input type="hidden" name="password" value="<?php echo htmlspecialchars($config['admin_password']); ?>">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="button" onclick="return confirm('Are you sure you want to clear all caches? This action cannot be undone.')">🧹 Clear All Caches</button>
            </form>
            <?php endif; ?>
            
            <p><small>Last checked: <?php echo date('Y-m-d H:i:s'); ?></small></p>
        </body>
        </html>
        <?php
    }
    
    // Handle logout
    if (!$is_cli && isset($_POST['logout'])) {
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>
