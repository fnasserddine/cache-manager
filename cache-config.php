<?php
/**
 * Cache Manager Configuration
 * 
 * IMPORTANT: Change the password before using in production!
 */

return [
    // Security settings
    'admin_password' => 'change_this_password_immediately',
    'allowed_ips' => [
        '127.0.0.1',
        '::1',
        // Add your IP addresses here
    ],
    
    // Cache directories to check and clear
    'cache_directories' => [
        './cache',
        './tmp',
        './temp',
        './var/cache',
        '../cache',
        '../tmp',
        './wp-content/cache',
        './wp-content/cache/page_enhanced',
        './cache/page',
    ],
    
    // Cache settings
    'cache_settings' => [
        // Whether to check for external cache services
        'check_memcached' => true,
        'check_redis' => true,
        
        // Memcached connection
        'memcached_host' => 'localhost',
        'memcached_port' => 11211,
        
        // Redis connection
        'redis_host' => 'localhost',
        'redis_port' => 6379,
        
        // Whether to clear file caches recursively
        'recursive_clear' => true,
        
        // Maximum file age to clear (in seconds, 0 = all files)
        'max_file_age' => 0,
    ],
    
    // Logging
    'log_actions' => true,
    'log_file' => './cache-manager.log',
];
?>
