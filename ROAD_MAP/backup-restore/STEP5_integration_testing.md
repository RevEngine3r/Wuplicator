# STEP5: Integration Testing & Examples

## Objective
Provide comprehensive testing documentation, usage examples, and validation procedures for the complete Wuplicator workflow.

## Scope
- End-to-end workflow testing
- Usage examples for common scenarios
- Validation procedures
- Performance benchmarks
- Edge case handling

## Testing Scenarios

### 1. Basic Migration Test
**Scenario**: Migrate WordPress from localhost to new domain

**Steps**:
1. Create backup on source site
2. Upload to new host
3. Configure installer
4. Deploy and verify

**Expected Results**:
- All files extracted correctly
- Database imported with all data
- URLs updated to new domain
- Admin credentials changed
- Site functional on new domain

### 2. Remote URL Download Test
**Scenario**: Deploy from remote backup URL

**Steps**:
1. Upload backup.zip to cloud storage (S3, Dropbox, etc.)
2. Get public URL
3. Upload only installer.php and database.sql
4. Configure BACKUP_URL
5. Run installer

**Expected Results**:
- Backup downloaded successfully
- Large files handled without timeout
- Progress tracking works
- Deployment completes

### 3. Large Site Test
**Scenario**: Backup and restore site with 10GB+ files and 1M+ database rows

**Expected Results**:
- Chunked processing prevents memory errors
- Progress updates regularly
- No timeouts
- Complete data integrity

### 4. Special Characters Test
**Scenario**: Site with Unicode content, serialized data, special file names

**Expected Results**:
- UTF-8 encoding preserved
- Serialized data not corrupted
- Special characters in filenames handled
- Database charset maintained

### 5. Security Test
**Scenario**: Validate security measures

**Steps**:
1. Verify security token is unique
2. Test SQL injection prevention
3. Check file permissions
4. Validate cleanup process

**Expected Results**:
- No SQL injection vulnerabilities
- Files cleaned up after install
- Installer self-destruct reminder shown
- Passwords properly hashed

## Usage Examples

### Example 1: Standard Migration

```bash
# On source site
cd /var/www/html
php wuplicator.php

# Download wuplicator-backups directory
# Upload to new host

# Edit installer.php on new host
vim installer.php
# Set:
# $NEW_DB_NAME = 'newdb';
# $NEW_DB_USER = 'dbuser';
# $NEW_DB_PASSWORD = 'dbpass';
# $NEW_SITE_URL = 'https://newsite.com';

# Visit installer in browser
open https://newsite.com/installer.php

# Follow wizard, then:
rm installer.php
```

### Example 2: Remote Deployment

```bash
# Upload backup.zip to S3
aws s3 cp wuplicator-backups/backup.zip s3://mybucket/backup.zip --acl public-read
aws s3 cp wuplicator-backups/database.sql s3://mybucket/database.sql --acl public-read

# Get URLs
BACKUP_URL="https://mybucket.s3.amazonaws.com/backup.zip"

# Edit installer.php
vim installer.php
# Set $BACKUP_URL = 'https://mybucket.s3.amazonaws.com/backup.zip';

# Upload only installer.php and database.sql to new host
scp installer.php database.sql user@newhost:/var/www/html/

# Run installer
open https://newsite.com/installer.php
```

### Example 3: Staging to Production

```bash
# On staging server
cd /var/www/staging
php wuplicator.php

# Edit installer.php for production
vim wuplicator-backups/installer.php
# Set:
# $NEW_DB_NAME = 'prod_db';
# $NEW_SITE_URL = 'https://production.com';
# $NEW_ADMIN_USER = 'prod_admin';
# $NEW_ADMIN_PASS = 'strong_password_123';

# Deploy to production
scp wuplicator-backups/* user@production:/var/www/html/
ssh user@production
cd /var/www/html
php installer.php # Or visit in browser
```

### Example 4: Custom Exclusions

```php
<?php
require_once 'wuplicator.php';

$wuplicator = new Wuplicator();

// Add custom exclusions
$customExcludes = [
    'wp-content/uploads/videos/',  // Exclude large video files
    'wp-content/temp/',            // Exclude temp directory
    '*.mp4',                       // Exclude all MP4 files
    '*.avi'                        // Exclude all AVI files
];

// Create backup with custom exclusions
$wuplicator->createDatabaseBackup();
$wuplicator->createFilesBackup($customExcludes);
$wuplicator->generateInstaller();

echo "Backup created with custom exclusions\n";
?>
```

## Validation Procedures

### Pre-Deployment Checklist

- [ ] Backup package contains all 3 files
- [ ] installer.php configuration is correct
- [ ] Database credentials tested and working
- [ ] Target host meets requirements (PHP 7.4+, ZipArchive, PDO)
- [ ] Sufficient disk space available
- [ ] Write permissions verified

### Post-Deployment Validation

- [ ] Homepage loads correctly
- [ ] Admin panel accessible with new credentials
- [ ] URLs updated (check pages, posts, images)
- [ ] Plugins functional
- [ ] Theme rendering correctly
- [ ] Media library intact
- [ ] Forms working (contact, comments, etc.)
- [ ] SSL certificate configured (if HTTPS)
- [ ] DNS pointed to new host
- [ ] Old site backup retained

### Database Integrity Check

```sql
-- Check table count
SHOW TABLES;

-- Verify user count
SELECT COUNT(*) FROM wp_users;

-- Check post count
SELECT COUNT(*) FROM wp_posts WHERE post_status = 'publish';

-- Verify options
SELECT * FROM wp_options WHERE option_name IN ('siteurl', 'home');

-- Check admin user
SELECT user_login, user_email FROM wp_users WHERE ID = 1;
```

### File Integrity Check

```bash
# Count files
find . -type f | wc -l

# Check WordPress core files
ls -la wp-admin/ wp-includes/ wp-content/

# Verify permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Check wp-config.php
grep DB_ wp-config.php
```

## Performance Benchmarks

### Small Site (< 100 MB)
- Database: 10 MB, 50 tables
- Files: 90 MB, 500 files
- **Expected time**: 5-10 seconds

### Medium Site (100 MB - 1 GB)
- Database: 50 MB, 100 tables
- Files: 950 MB, 5,000 files
- **Expected time**: 30-60 seconds

### Large Site (1 GB - 10 GB)
- Database: 500 MB, 200 tables
- Files: 9.5 GB, 50,000 files
- **Expected time**: 5-10 minutes

### Very Large Site (> 10 GB)
- Database: 2 GB, 500 tables
- Files: 50 GB, 200,000 files
- **Expected time**: 30-60 minutes
- **Recommendations**: 
  - Increase PHP memory_limit to 512M+
  - Set max_execution_time to 3600+
  - Consider excluding large media files
  - Use remote URL download for deployment

## Common Issues & Solutions

### Issue: "Maximum execution time exceeded"
**Solution**:
```php
// Add to top of wuplicator.php
set_time_limit(3600); // 1 hour
ini_set('max_execution_time', 3600);
```

### Issue: "Allowed memory size exhausted"
**Solution**:
```php
// Add to top of wuplicator.php
ini_set('memory_limit', '512M');
```

### Issue: "URLs not replaced correctly"
**Solution**:
- Verify $NEW_SITE_URL matches format (https://example.com, no trailing slash)
- Check serialized data in database (wp_options, wp_postmeta)
- Use search-replace plugin for complex cases

### Issue: "Admin login fails after deployment"
**Solution**:
- Clear browser cookies
- Check wp_users table for correct user_login
- Verify password was hashed correctly
- Try password reset via email

## Success Criteria

- ✅ All test scenarios pass
- ✅ Examples documented and verified
- ✅ Validation procedures complete
- ✅ Performance benchmarks recorded
- ✅ Common issues documented with solutions
- ✅ End-to-end workflow tested on multiple hosts

---

**Status**: Complete  
**Documentation**: Usage examples, test procedures, validation checklists
