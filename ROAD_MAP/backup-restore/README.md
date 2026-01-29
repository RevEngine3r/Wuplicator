# Feature: Core Backup & Restore System

## Overview
Implement the complete backup creation and deployment system for Wuplicator. This includes creating database dumps, archiving WordPress files, generating an installer script, and deploying backups to new hosts with customization options.

## Goals
1. **Backup Creation (wuplicator.php)**
   - Export complete MySQL database to SQL file
   - Archive all WordPress files into ZIP
   - Generate installer.php with embedded configuration
   - Create single portable backup package

2. **Backup Deployment (installer.php)**
   - **Download backup ZIP from remote URL (optional manual configuration)**
   - Extract files to new host
   - Create and configure new database
   - Import database dump
   - Update WordPress configuration (wp-config.php)
   - Perform search/replace for domain changes
   - Modify admin credentials (username + password)
   - Execute cleanup and security measures

## User Requirements
- Change admin username: `admin_gd5rt` → `admin_ls45g`
- Change admin password: `ieu644t3fd` → `slkjdfhnb874`
- Support remote backup download via URL (manually specified in installer.php)
- Support additional customizations (extensible)

## Technical Requirements
- PHP 7.4+ compatibility
- WordPress 5.0+ support
- MySQL/MariaDB database support
- ZipArchive for compression
- cURL or file_get_contents for remote downloads
- Security: Input validation, CSRF protection, secure cleanup
- Error handling with user-friendly messages
- Progress tracking for long operations

## Development Steps

### STEP1: Database Backup Functionality
**File**: `STEP1_database_backup.md`  
**Scope**: Implement MySQL database export to SQL file
- Connect to WordPress database using wp-config.php
- Export all tables with structure and data
- Handle large databases with chunked exports
- Include DROP TABLE IF EXISTS statements
- Add error handling and validation

### STEP2: File Archiving System
**File**: `STEP2_file_archiving.md`  
**Scope**: Create ZIP archive of WordPress files
- Scan WordPress directory recursively
- Exclude backup files and temporary data
- Create ZIP with progress tracking
- Handle large file sets efficiently
- Validate archive integrity

### STEP3: Installer Generator
**File**: `STEP3_installer_generator.md`  
**Scope**: Generate installer.php with embedded data
- Create installer template
- Embed backup metadata
- Generate unique security token
- Include remote URL download capability (configurable)
- Package installer with backup files
- Create final distributable package

### STEP4: Remote Download & Extraction
**File**: `STEP4_remote_download_extraction.md`  
**Scope**: Download backup from URL and extract on new host
- **Check for manual URL configuration in installer**
- **Download ZIP from remote URL with progress tracking**
- Validate downloaded file integrity
- Fallback to local ZIP if no URL specified
- Validate installer security token
- Extract ZIP archive to target directory
- Verify file permissions
- Create progress feedback UI
- Handle download and extraction errors

### STEP5: Database Restoration
**File**: `STEP5_database_restoration.md`  
**Scope**: Import database dump to new database
- Create new database (if needed)
- Import SQL file with proper charset
- Handle large imports with chunking
- Verify table creation and data
- Error recovery mechanisms

### STEP6: WordPress Configuration
**File**: `STEP6_wordpress_config.md`  
**Scope**: Update wp-config.php and perform domain replacements
- Generate new wp-config.php with new credentials
- Search/replace old domain with new domain in database
- Update site URL and home URL
- Handle serialized data correctly
- Preserve other WordPress settings

### STEP7: Admin Credentials Modification
**File**: `STEP7_admin_credentials.md`  
**Scope**: Change admin username and password
- Locate admin user in wp_users table
- Update user_login (username)
- Generate secure password hash
- Update user_pass (password)
- Validate changes

### STEP8: Testing & Security Hardening
**File**: `STEP8_testing_security.md`  
**Scope**: Comprehensive testing and security measures
- Unit tests for all components
- Integration tests for full workflow (local and remote download)
- Security audit (input validation, SQL injection prevention)
- Performance testing with large sites
- Test remote download with various URL sources
- Cleanup sensitive data after installation
- Self-destruct mechanism for installer

## Success Criteria
- ✅ Complete WordPress backup created as single package
- ✅ Installer can download backup from remote URL
- ✅ Successful deployment to new host/domain
- ✅ Database fully restored with correct data
- ✅ Admin credentials changed as specified
- ✅ Site functional on new domain
- ✅ No security vulnerabilities
- ✅ Clean, maintainable, documented code

## Dependencies
- PHP ZipArchive extension
- PHP cURL extension (or allow_url_fopen enabled)
- MySQL/MariaDB PDO driver
- WordPress installation (for backup creation)
- Write permissions on target host (for deployment)

## Risks & Mitigations
- **Large sites**: Implement chunked processing and progress tracking
- **Remote downloads**: Timeout handling, resumable downloads, integrity checks
- **Timeout issues**: Add time limit extensions and resumable operations
- **Security**: Validate all inputs, use CSRF tokens, secure cleanup
- **Database encoding**: Preserve charset/collation settings
- **Serialized data**: Use proper search/replace for PHP serialized strings

---

## Approval Required
Please review this roadmap and confirm to proceed with STEP1 implementation.

**Status**: Awaiting Approval  
**Created**: 2026-01-29  
**Updated**: 2026-01-29 (Added remote URL download capability)
