# 🚀 Wuplicator

**WordPress Backup & Deployment Tool**

Wuplicator is a metaphor for WordPress Duplicator - a standalone PHP script that creates complete WordPress site backups and provides an installer for seamless migration to new hosts with customization capabilities.

## ✨ Features

### Backup Creator (`wuplicator.php`)
- ✅ **Complete Database Export** - Full MySQL dump with structure and data
- ✅ **File Archiving** - ZIP compression of all WordPress files
- ✅ **Smart Exclusions** - Automatically excludes cache, backups, logs
- ✅ **Progress Tracking** - Real-time feedback during backup
- ✅ **Chunked Processing** - Handles large sites without memory issues

### Installer (`installer.php`)
- ✅ **Remote URL Download** - Download backup from any URL
- ✅ **Web-Based UI** - Modern interface with progress tracking
- ✅ **Database Migration** - Automatic database creation and import
- ✅ **URL Replacement** - Search/replace old domain → new domain
- ✅ **Admin Credentials** - Change admin username and password
- ✅ **Auto-Cleanup** - Removes backup files after installation
- ✅ **Self-Destruct** - Security reminder to delete installer

## 📦 Package Contents

When you run `wuplicator.php`, it creates 3 files:

```
wuplicator-backups/
├── installer.php   # Deployment script with embedded metadata
├── backup.zip      # All WordPress files (wp-content, themes, plugins, etc.)
└── database.sql    # Complete database dump
```

## 🚀 Quick Start

### Step 1: Create Backup

1. Upload `src/wuplicator.php` to your WordPress root directory
2. Run via command line:
   ```bash
   cd /path/to/wordpress
   php wuplicator.php
   ```
   Or via browser: `https://yoursite.com/wuplicator.php`

3. Wait for backup to complete:
   ```
   ==================================================
     WUPLICATOR - Complete Backup Package Creator
   ==================================================

   [Wuplicator] Starting database backup...
   [1/4] Parsing wp-config.php...
     Database: wordpress_db
   [2/4] Connecting to database...
     Connected successfully
   [3/4] Scanning tables...
     Found 12 tables
   [4/4] Exporting database...
     [1/12] Exporting: wp_posts
     ...

   [SUCCESS] Database backup created
   File size: 2.4 MB

   [Wuplicator] Starting files backup...
   [1/3] Scanning WordPress directory...
     Found 1,247 files
   [2/3] Creating ZIP archive...
     Progress: 10% (125/1247 files)
     ...
     Progress: 100% (1247/1247 files)
   [3/3] Validating archive...
     Archive contains 1247 files
     Integrity check: PASSED

   [SUCCESS] Files backup created
   Files archived: 1247
   Archive size: 45.8 MB

   [Wuplicator] Generating installer...
     Installer generated with security token
     Original site: https://oldsite.com
     Table prefix: wp_

   ==================================================
     BACKUP PACKAGE COMPLETE
   ==================================================

   Package location: /path/to/wordpress/wuplicator-backups/

   Files created:
     1. installer.php - Deployment script
     2. backup.zip    - WordPress files
     3. database.sql  - Database dump

   Total time: 45.2s
   ```

4. Download the `wuplicator-backups/` directory

### Step 2: Deploy to New Host

1. Upload all 3 files to new host's web root:
   ```
   /public_html/
   ├── installer.php
   ├── backup.zip
   └── database.sql
   ```

2. **Edit `installer.php` configuration** (top of file):
   ```php
   // Remote backup URL (optional - leave empty to use local files)
   $BACKUP_URL = ''; // or 'https://cdn.example.com/backup.zip'

   // New Database Configuration
   $NEW_DB_HOST = 'localhost';
   $NEW_DB_NAME = 'new_database';
   $NEW_DB_USER = 'db_user';
   $NEW_DB_PASSWORD = 'db_password';

   // New Site Configuration
   $NEW_SITE_URL = 'https://newsite.com';

   // New Admin Credentials (optional)
   $NEW_ADMIN_USER = 'admin_ls45g';
   $NEW_ADMIN_PASS = 'slkjdfhnb874';
   ```

3. Visit `https://newsite.com/installer.php` in browser

4. Click **"Start Installation"** and watch the magic happen:
   - ✅ Configuration validation
   - ✅ Backup download (if URL specified)
   - ✅ File extraction
   - ✅ Database setup
   - ✅ WordPress configuration
   - ✅ Admin credentials update
   - ✅ Cleanup

5. **Delete `installer.php`** for security

## 🔧 Configuration

### Backup Exclusions

Default excluded patterns (in `wuplicator.php`):
```php
'wuplicator-backups',
'wp-content/cache',
'wp-content/backup*',
'.git', '.svn',
'node_modules',
'*.log', 'error_log', '.DS_Store'
```

Add custom exclusions:
```php
$wuplicator = new Wuplicator();
$customExcludes = ['wp-content/uploads/videos/', '*.tmp'];
$wuplicator->createFilesBackup($customExcludes);
```

### Remote Backup Download

To deploy from a remote URL:

1. Upload backup.zip to CDN/cloud storage
2. Get public URL (e.g., `https://s3.amazonaws.com/mybucket/backup.zip`)
3. Edit `installer.php`:
   ```php
   $BACKUP_URL = 'https://s3.amazonaws.com/mybucket/backup.zip';
   ```
4. Upload only `installer.php` and `database.sql` to new host
5. Installer will download backup.zip automatically

## 🎯 Use Cases

### 1. Site Migration
Move WordPress from one host to another with different domain and database credentials.

### 2. Staging to Production
Deploy staging site to production with updated admin credentials and URLs.

### 3. Clone Site
Create multiple copies of the same site with different configurations.

### 4. Disaster Recovery
Quick restore from backup with minimal downtime.

### 5. Development Setup
Quickly set up local development environment from production backup.

## 🛡️ Security Features

- ✅ **Security Token** - Auto-generated unique token in installer
- ✅ **PDO Prepared Statements** - SQL injection prevention
- ✅ **Input Validation** - All user inputs validated
- ✅ **Auto-Cleanup** - Backup files deleted after installation
- ✅ **Self-Destruct Reminder** - Warns to delete installer.php
- ✅ **Password Hashing** - Uses WordPress PasswordHash for admin password

## 📋 Requirements

### For Backup Creation
- PHP 7.4 or higher
- WordPress 5.0+
- PHP ZipArchive extension
- PHP PDO MySQL extension
- Read access to WordPress files
- Write access to create backup directory

### For Deployment
- PHP 7.4 or higher
- MySQL/MariaDB database
- PHP ZipArchive extension
- PHP PDO MySQL extension
- PHP cURL extension (optional, for remote downloads)
- Write permissions on web root

## 🔍 Troubleshooting

### "ZipArchive extension not available"
```bash
# Ubuntu/Debian
sudo apt-get install php-zip

# CentOS/RHEL
sudo yum install php-zip

# Restart web server
sudo service apache2 restart
```

### "Database connection failed"
- Verify database credentials in installer.php
- Check database user has CREATE DATABASE permission
- Ensure MySQL is running

### "Failed to download backup"
- Check BACKUP_URL is accessible
- Enable cURL: `sudo apt-get install php-curl`
- Or enable allow_url_fopen in php.ini

### "Memory limit exceeded"
- Increase PHP memory_limit in php.ini: `memory_limit = 512M`
- Or use `.htaccess`: `php_value memory_limit 512M`

## 🤝 Contributing

Contributions welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Follow the atomic commit workflow (see PROGRESS.md)
4. Submit a pull request

## 📄 License

MIT License - feel free to use in personal and commercial projects.

## 🎉 Credits

Created by [RevEngine3r](https://github.com/RevEngine3r)

Inspired by the WordPress Duplicator plugin, but reimagined as a lightweight, standalone solution.

## 📚 Documentation

- [Project Map](PROJECT_MAP.md) - Architecture overview
- [Development Progress](PROGRESS.md) - Current status and roadmap
- [Roadmap](ROAD_MAP/README.md) - Feature development plan

---

**Made with ❤️ for the WordPress community**
