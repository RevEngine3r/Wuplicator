# Wuplicator Usage Examples

Comprehensive examples for common Wuplicator use cases.

## Table of Contents

1. [Basic Site Migration](#1-basic-site-migration)
2. [Remote URL Deployment](#2-remote-url-deployment)
3. [Staging to Production](#3-staging-to-production)
4. [Site Cloning](#4-site-cloning)
5. [Selective Backup](#5-selective-backup)
6. [Automated Backup](#6-automated-backup)
7. [Large Site Optimization](#7-large-site-optimization)

---

## 1. Basic Site Migration

**Scenario**: Move WordPress from old-hosting.com to new-hosting.com

### Step 1: Create Backup

```bash
# SSH into old server
ssh user@old-hosting.com
cd /var/www/html

# Upload wuplicator.php
wget https://raw.githubusercontent.com/RevEngine3r/Wuplicator/main/src/wuplicator.php

# Run backup
php wuplicator.php
```

**Output**:
```
==================================================
  WUPLICATOR - Complete Backup Package Creator
==================================================

Total time: 45.2s

Files created:
  1. installer.php
  2. backup.zip
  3. database.sql
```

### Step 2: Download Backup

```bash
# From local machine
scp -r user@old-hosting.com:/var/www/html/wuplicator-backups ./
```

### Step 3: Configure Installer

```php
// Edit installer.php
$NEW_DB_NAME = 'new_wordpress_db';
$NEW_DB_USER = 'new_db_user';
$NEW_DB_PASSWORD = 'new_password_123';
$NEW_SITE_URL = 'https://new-hosting.com';
```

### Step 4: Deploy

```bash
# Upload to new server
scp wuplicator-backups/* user@new-hosting.com:/var/www/html/

# Visit installer
open https://new-hosting.com/installer.php
```

### Step 5: Cleanup

```bash
ssh user@new-hosting.com
rm /var/www/html/installer.php
```

---

## 2. Remote URL Deployment

**Scenario**: Deploy large site from S3 bucket to new server

### Step 1: Upload to Cloud Storage

```bash
# Create backup
php wuplicator.php

# Upload to AWS S3
aws s3 cp wuplicator-backups/backup.zip s3://my-backups/site-backup.zip --acl public-read

# Get public URL
echo "https://my-backups.s3.amazonaws.com/site-backup.zip"
```

### Step 2: Configure Installer

```php
// installer.php
$BACKUP_URL = 'https://my-backups.s3.amazonaws.com/site-backup.zip';
$NEW_DB_NAME = 'production_db';
$NEW_DB_USER = 'prod_user';
$NEW_DB_PASSWORD = 'secure_pass';
$NEW_SITE_URL = 'https://production-site.com';
```

### Step 3: Deploy (Lightweight)

```bash
# Upload only installer.php and database.sql
scp installer.php database.sql user@prod-server:/var/www/html/

# Installer will download backup.zip from S3
```

**Benefits**:
- No need to upload large ZIP file
- Faster deployment
- Bandwidth savings
- CDN acceleration

---

## 3. Staging to Production

**Scenario**: Deploy tested staging site to production with new admin

### Full Workflow

```bash
# On staging server
cd /var/www/staging.example.com
php wuplicator.php

# Edit installer configuration
vim wuplicator-backups/installer.php
```

```php
// Production configuration
$NEW_DB_NAME = 'production_db';
$NEW_DB_USER = 'prod_user';
$NEW_DB_PASSWORD = getenv('DB_PASSWORD'); // From env var
$NEW_SITE_URL = 'https://www.example.com';

// Change admin credentials for security
$NEW_ADMIN_USER = 'admin_prod';
$NEW_ADMIN_PASS = 'v3ry_S3cur3_P@ssw0rd!';
```

```bash
# Deploy to production
scp -r wuplicator-backups user@production-server:/var/www/html/

# Run installer
ssh user@production-server
cd /var/www/html/wuplicator-backups
php installer.php # Or via browser

# Verify
curl -I https://www.example.com

# Cleanup
rm installer.php backup.zip database.sql
```

---

## 4. Site Cloning

**Scenario**: Create multiple copies for development/testing

### Clone to 3 Environments

```bash
# Create one backup
php wuplicator.php

# Clone to Dev
cp -r wuplicator-backups dev-clone
vim dev-clone/installer.php
# Set: $NEW_SITE_URL = 'https://dev.example.com';
# Set: $NEW_DB_NAME = 'dev_db';

# Clone to QA
cp -r wuplicator-backups qa-clone
vim qa-clone/installer.php
# Set: $NEW_SITE_URL = 'https://qa.example.com';
# Set: $NEW_DB_NAME = 'qa_db';

# Clone to Demo
cp -r wuplicator-backups demo-clone
vim demo-clone/installer.php
# Set: $NEW_SITE_URL = 'https://demo.example.com';
# Set: $NEW_DB_NAME = 'demo_db';

# Deploy all
for env in dev qa demo; do
  scp -r ${env}-clone/* user@${env}-server:/var/www/html/
done
```

---

## 5. Selective Backup

**Scenario**: Exclude large video files to reduce backup size

### Custom Exclusions Script

```php
<?php
// backup-without-videos.php
require_once 'wuplicator.php';

$wuplicator = new Wuplicator();

// Define exclusions
$excludes = [
    'wp-content/uploads/videos/',
    'wp-content/uploads/2023/*/large/',
    '*.mp4',
    '*.avi',
    '*.mov',
    '*.wmv'
];

echo "Creating backup without videos...\n";

// Create database backup (full)
$sqlFile = $wuplicator->createDatabaseBackup();

// Create files backup (selective)
$zipFile = $wuplicator->createFilesBackup($excludes);

// Generate installer
$installer = $wuplicator->generateInstaller();

echo "\nBackup created (videos excluded)\n";
echo "Reduced size: ~70% smaller\n";
?>
```

```bash
php backup-without-videos.php
```

---

## 6. Automated Backup

**Scenario**: Daily automated backups with rotation

### Cron Job Script

```bash
#!/bin/bash
# /home/user/wuplicator-backup.sh

DATE=$(date +%Y-%m-%d)
BACKUP_DIR="/var/backups/wordpress"
WP_ROOT="/var/www/html"

# Create backup
cd $WP_ROOT
php wuplicator.php

# Move to backup directory
mkdir -p $BACKUP_DIR/$DATE
mv wuplicator-backups/* $BACKUP_DIR/$DATE/

# Compress for storage
cd $BACKUP_DIR
tar -czf wordpress-backup-$DATE.tar.gz $DATE/
rm -rf $DATE

# Upload to S3
aws s3 cp wordpress-backup-$DATE.tar.gz s3://my-backups/daily/

# Delete local copy
rm wordpress-backup-$DATE.tar.gz

# Keep only last 7 days on S3
aws s3 ls s3://my-backups/daily/ | \
  sort -r | \
  tail -n +8 | \
  awk '{print $4}' | \
  xargs -I {} aws s3 rm s3://my-backups/daily/{}

echo "Backup completed: $DATE"
```

### Crontab Entry

```bash
# Run daily at 2 AM
0 2 * * * /home/user/wuplicator-backup.sh >> /var/log/wuplicator-backup.log 2>&1
```

---

## 7. Large Site Optimization

**Scenario**: Backup 20GB site without timeout/memory issues

### Optimized Configuration

```php
<?php
// large-site-backup.php

// Increase limits
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '7200'); // 2 hours
set_time_limit(7200);

require_once 'wuplicator.php';

$wuplicator = new Wuplicator();

// Exclude cache and temporary files
$excludes = [
    'wp-content/cache/',
    'wp-content/et-cache/',
    'wp-content/w3tc-cache/',
    'wp-content/object-cache.php',
    '*.log',
    '*.tmp'
];

echo "Starting large site backup...\n";
echo "Estimated time: 15-30 minutes\n\n";

$package = $wuplicator->createPackage();

echo "\nBackup size breakdown:\n";
echo "Database: " . filesize($package['database_sql']) . " bytes\n";
echo "Files: " . filesize($package['backup_zip']) . " bytes\n";
?>
```

### PHP Configuration

```ini
; php.ini or .htaccess
memory_limit = 1024M
max_execution_time = 7200
post_max_size = 1024M
upload_max_filesize = 1024M
```

---

## Additional Tips

### Verify Backup Before Deployment

```bash
# Check backup.zip integrity
unzip -t backup.zip

# Count files in archive
unzip -l backup.zip | wc -l

# Test database.sql
mysql -u root -p test_db < database.sql
```

### Monitor Deployment Progress

```bash
# Watch installer log in real-time
tail -f /var/log/apache2/error.log

# Check PHP errors
tail -f /var/log/php_errors.log
```

### Rollback Procedure

```bash
# If deployment fails, restore from old site
scp user@old-server:/var/www/html/wuplicator-backups/* ./
php installer.php # Redeploy
```

---

**More Examples**: See [ROAD_MAP/backup-restore/STEP5_integration_testing.md](../ROAD_MAP/backup-restore/STEP5_integration_testing.md)
