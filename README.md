# Server-Side Cache Manager

A comprehensive PHP-based tool for detecting and managing various types of server-side caches.

## 🚀 Features

- **Multi-Cache Detection**: Detects OPcache, APCu, Memcached, Redis, file caches, page caches, LiteSpeed Cache, and Cloudflare
- **Cache Clearing**: Safely clears all detected cache types
- **Web Interface**: Password-protected web interface for easy management
- **Command Line**: CLI support for automation and scripting
- **Security**: IP filtering and password protection
- **Logging**: Optional action logging for audit trails

## 📁 Files Included

- `cache-manager.php` - Main cache management script
- `simple-cache-test.php` - Quick cache availability test
- `cache-config.php` - Configuration file
- `cache-manager.sh` - Shell script for easy CLI access

## 🔧 Installation

1. Upload all files to your web server
2. Edit `cache-config.php` and change the default password
3. Set appropriate permissions:
   ```bash
   chmod 755 cache-manager.php simple-cache-test.php
   chmod 644 cache-config.php
   chmod +x cache-manager.sh
   ```

## 🖥️ Usage

### Command Line Usage

**Quick test:**
```bash
php simple-cache-test.php
```

**Detailed detection:**
```bash
php cache-manager.php
```

**Clear all caches:**
```bash
php cache-manager.php --clear
```

**Using the shell script:**
```bash
./cache-manager.sh test     # Quick test
./cache-manager.sh check    # Detailed check
./cache-manager.sh clear    # Clear caches
```

### Web Interface Usage

1. Navigate to `cache-manager.php` in your browser
2. Enter the admin password (set in `cache-config.php`)
3. View cache status and clear caches as needed

## 🔍 Cache Types Detected

### System Caches
- **OPcache**: PHP opcode cache for improved performance
- **APCu**: User data cache for PHP applications
- **Memcached**: High-performance distributed memory caching
- **Redis**: In-memory data structure store

### File Caches
- Application cache directories (`./cache`, `./tmp`, etc.)
- WordPress page caches (`./wp-content/cache`)
- Custom cache directories

### External Services
- **LiteSpeed Cache**: Server-level caching
- **Cloudflare**: CDN and caching service detection

## ⚙️ Configuration

Edit `cache-config.php` to customize:

```php
// Security settings
'admin_password' => 'your_secure_password',
'allowed_ips' => ['127.0.0.1', 'your.ip.address'],

// Cache directories to monitor
'cache_directories' => [
    './cache',
    './tmp',
    // Add your custom cache paths
],

// External cache settings
'memcached_host' => 'localhost',
'redis_host' => 'localhost',
```

## 🔒 Security Features

- **Password Protection**: Web interface requires authentication
- **IP Filtering**: Restrict access to specific IP addresses
- **Session Management**: Secure session handling
- **Safe File Operations**: Prevents unauthorized file access

## 📊 Sample Output

```
🔍 Detecting available cache systems...

✅ OPcache: Available and enabled
   - Memory usage: 64.00 MB
   - Hit rate: 98.50%
✅ APCu: Available and active
   - Memory usage: 32.00 MB
   - Number of entries: 150
❌ Memcached: Not available
❌ Redis: Not available
✅ File caches found:
   - ./cache (25 files)
   - ./tmp (12 files)
```

## 🧹 Cache Clearing Results

```
🧹 Clearing available caches...

✅ OPcache cleared successfully
✅ APCu cache cleared successfully
✅ Cleared 25 items from ./cache
✅ Cleared 12 items from ./tmp
```

## ⚠️ Important Notes

1. **Change Default Password**: Always change the default password in `cache-config.php`
2. **Test Before Production**: Test the script in a development environment first
3. **Backup Important Data**: Some caches may contain important session or application data
4. **Monitor Performance**: Clearing caches may temporarily impact site performance
5. **File Permissions**: Ensure the script has appropriate permissions to read/write cache directories

## 🔧 Troubleshooting

### Common Issues

**"Permission denied" errors:**
- Check file permissions on cache directories
- Ensure PHP has write access to cache folders

**"Function not available" warnings:**
- Install missing PHP extensions (APCu, Memcached, Redis)
- Check PHP configuration

**Web interface not loading:**
- Verify file permissions
- Check server error logs
- Ensure PHP is properly configured

## 🚀 Advanced Usage

### Automation with Cron

Add to crontab for automatic cache clearing:
```bash
# Clear caches daily at 2 AM
0 2 * * * /path/to/your/site/cache-manager.sh clear >/dev/null 2>&1
```

### Integration with Monitoring

Monitor cache usage with custom scripts:
```bash
# Check cache status and log results
./cache-manager.sh check >> /var/log/cache-status.log
```

## 📝 License

This tool is provided as-is for use with Cheap Web Hosting services. Modify and distribute as needed for your hosting environment.

## 🆘 Support

For support with this cache manager tool, contact support@cheap-web-hosting.org
