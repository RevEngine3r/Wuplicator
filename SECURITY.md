# Security Policy

## Overview

Wuplicator handles sensitive data including database credentials, WordPress files, and admin passwords. Security is a top priority.

## Supported Versions

We provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Security Features

### Built-in Protections

#### 1. SQL Injection Prevention
- ✅ PDO prepared statements for all database queries
- ✅ Parameter binding instead of string concatenation
- ✅ Input validation and sanitization

```php
// Secure: Using prepared statements
$stmt = $pdo->prepare("UPDATE wp_users SET user_login = ? WHERE ID = ?");
$stmt->execute([$newUser, $adminId]);

// Insecure (NOT used): String concatenation
$pdo->exec("UPDATE wp_users SET user_login = '$newUser' WHERE ID = $adminId");
```

#### 2. Security Token Generation
- ✅ Unique 64-character token per backup
- ✅ Cryptographically secure random generation
- ✅ Embedded in installer for validation

```php
$token = bin2hex(random_bytes(32)); // 64 characters
```

#### 3. Password Hashing
- ✅ WordPress PasswordHash class
- ✅ Bcrypt-based hashing (8 rounds)
- ✅ No plaintext password storage

```php
$hasher = new PasswordHash(8, true);
$hashedPass = $hasher->HashPassword($password);
```

#### 4. Auto-Cleanup
- ✅ Backup files deleted after installation
- ✅ Self-destruct reminder displayed
- ✅ Sensitive data not logged

#### 5. Input Validation
- ✅ Required fields checked
- ✅ Database credentials validated
- ✅ URL format verification
- ✅ File path sanitization

### Security Checklist

**During Backup:**
- [ ] Run wuplicator.php from secure location
- [ ] Use HTTPS for web-based backup (if applicable)
- [ ] Store backup package securely (encrypted storage)
- [ ] Use strong database passwords
- [ ] Verify backup integrity before deployment

**During Deployment:**
- [ ] Use HTTPS to access installer.php
- [ ] Strong database credentials configured
- [ ] Admin password is unique and strong (12+ characters)
- [ ] Delete installer.php immediately after completion
- [ ] Delete backup.zip and database.sql after installation
- [ ] Verify file permissions (644 for files, 755 for directories)
- [ ] Change WordPress security keys in wp-config.php

**After Deployment:**
- [ ] Installer files deleted
- [ ] Admin credentials tested
- [ ] WordPress admin accessible
- [ ] No backup files remain on server
- [ ] wp-config.php has correct permissions (400 or 440)
- [ ] Database credentials secured

## Reporting a Vulnerability

### How to Report

**DO NOT** open a public GitHub issue for security vulnerabilities.

Instead, please email security concerns to:

**Email**: revengine3r@gmail.com  
**Subject**: [SECURITY] Wuplicator Vulnerability Report

### What to Include

1. **Description**: Clear description of the vulnerability
2. **Impact**: Potential security impact
3. **Steps to Reproduce**: Detailed reproduction steps
4. **Proof of Concept**: Code or screenshots (if applicable)
5. **Suggested Fix**: Your recommendations (optional)

### Response Timeline

- **Initial Response**: Within 48 hours
- **Severity Assessment**: Within 5 business days
- **Fix Development**: Based on severity (critical: 7 days, high: 14 days, medium: 30 days)
- **Public Disclosure**: After fix is released and users notified

### Severity Levels

**Critical** (Fix within 7 days):
- Remote code execution
- SQL injection
- Authentication bypass
- Sensitive data exposure

**High** (Fix within 14 days):
- Privilege escalation
- XSS vulnerabilities
- CSRF vulnerabilities

**Medium** (Fix within 30 days):
- Information disclosure
- Denial of service
- Configuration issues

**Low** (Fix in next release):
- Minor issues with limited impact

## Best Practices

### For Backup Creation

**1. Secure Storage**
```bash
# Encrypt backup package
tar -czf backup.tar.gz wuplicator-backups/
openssl enc -aes-256-cbc -salt -in backup.tar.gz -out backup.tar.gz.enc
rm backup.tar.gz

# Upload encrypted backup
aws s3 cp backup.tar.gz.enc s3://secure-bucket/ --sse AES256
```

**2. Access Control**
```bash
# Restrict access to backup directory
chmod 700 wuplicator-backups/
chown www-data:www-data wuplicator-backups/
```

**3. Secure Transfer**
```bash
# Use SCP/SFTP instead of FTP
scp -r wuplicator-backups/ user@server:/secure/location/

# Or use rsync over SSH
rsync -avz -e ssh wuplicator-backups/ user@server:/secure/location/
```

### For Deployment

**1. Environment Variables**
```php
// Use environment variables for credentials
$NEW_DB_PASSWORD = getenv('DB_PASSWORD');
$NEW_ADMIN_PASS = getenv('ADMIN_PASSWORD');
```

**2. HTTPS Only**
```apache
# .htaccess - Force HTTPS for installer
<Files "installer.php">
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</Files>
```

**3. IP Restriction**
```apache
# .htaccess - Restrict installer to specific IP
<Files "installer.php">
    Order Deny,Allow
    Deny from all
    Allow from 203.0.113.0/24
</Files>
```

**4. Strong Passwords**
```php
// Generate strong admin password
$NEW_ADMIN_PASS = bin2hex(random_bytes(16)); // 32 characters
echo "Admin password: $NEW_ADMIN_PASS\n"; // Save securely
```

**5. WordPress Security Keys**
```bash
# Regenerate WordPress security keys after deployment
curl https://api.wordpress.org/secret-key/1.1/salt/ >> wp-config.php
```

### For Production

**1. File Permissions**
```bash
# Set secure permissions after deployment
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 400 wp-config.php
```

**2. Database Security**
```sql
-- Create dedicated database user with minimal privileges
CREATE USER 'wp_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON wordpress_db.* TO 'wp_user'@'localhost';
FLUSH PRIVILEGES;

-- Remove default admin user
DELETE FROM wp_users WHERE user_login = 'admin';
```

**3. Disable File Editing**
```php
// wp-config.php - Disable theme/plugin editor
define('DISALLOW_FILE_EDIT', true);
```

**4. Security Headers**
```apache
# .htaccess - Add security headers
Header set X-Frame-Options "SAMEORIGIN"
Header set X-Content-Type-Options "nosniff"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

## Known Security Considerations

### 1. Installer Access
**Risk**: Installer.php accessible to anyone during deployment  
**Mitigation**: 
- Use IP restriction
- Delete immediately after use
- Set up HTTPS

### 2. Database Credentials
**Risk**: Credentials in installer.php readable  
**Mitigation**:
- Use environment variables
- Set file permissions (400)
- Delete after deployment

### 3. Backup Files
**Risk**: Backup contains sensitive data  
**Mitigation**:
- Encrypt backups
- Secure storage
- Auto-cleanup after deployment

### 4. Remote URL Download
**Risk**: Backup downloaded over unencrypted connection  
**Mitigation**:
- Use HTTPS URLs only
- Verify SSL certificates
- Use signed URLs with expiration

## Security Audit

Wuplicator undergoes regular security reviews:

- ✅ SQL injection testing
- ✅ XSS vulnerability scanning
- ✅ CSRF protection verification
- ✅ Authentication bypass testing
- ✅ File upload validation
- ✅ Path traversal prevention

## Updates and Patches

Security updates will be released as:
- Patch versions (1.0.x) for minor issues
- Minor versions (1.x.0) for significant fixes
- Changelog will indicate security fixes

**Stay Updated**: Watch the repository for security announcements.

## Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [WordPress Security](https://wordpress.org/support/article/hardening-wordpress/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)

---

**Security is a shared responsibility. Deploy responsibly. 🛡️**
