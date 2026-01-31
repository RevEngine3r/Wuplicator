<?php
// ============================================
// CONFIGURATION - Edit these values
// ============================================

// Remote backup URL (leave empty to use local backup.zip)
$BACKUP_URL = '';

// New Database Configuration
$NEW_DB_HOST = 'localhost';
$NEW_DB_NAME = '';
$NEW_DB_USER = '';
$NEW_DB_PASSWORD = '';

// New Site Configuration
$NEW_SITE_URL = '';

// New Admin Credentials (optional)
$NEW_ADMIN_USER = '';
$NEW_ADMIN_PASS = '';

// Security Enhancements (v1.2.0)
$RANDOMIZE_ADMIN_USER = false;
$RANDOMIZE_ADMIN_PASS = false;
$REGENERATE_SECURITY_KEYS = false;

// Security token (auto-generated)
$SECURITY_TOKEN = 'WUPLICATOR_TOKEN_PLACEHOLDER';

// Embedded metadata
$BACKUP_METADATA = array(
    'created' => 'TIMESTAMP_PLACEHOLDER',
    'db_name' => 'DB_NAME_PLACEHOLDER',
    'table_prefix' => 'TABLE_PREFIX_PLACEHOLDER',
    'site_url' => 'SITE_URL_PLACEHOLDER'
);

// ============================================
// INSTALLER CODE - Do not modify below
// ============================================

$installerConfig = [
    'backup_url' => $BACKUP_URL,
    'db_host' => $NEW_DB_HOST,
    'db_name' => $NEW_DB_NAME,
    'db_user' => $NEW_DB_USER,
    'db_password' => $NEW_DB_PASSWORD,
    'site_url' => $NEW_SITE_URL,
    'admin_user' => $NEW_ADMIN_USER,
    'admin_pass' => $NEW_ADMIN_PASS,
    'randomize_user' => $RANDOMIZE_ADMIN_USER,
    'randomize_pass' => $RANDOMIZE_ADMIN_PASS,
    'regenerate_keys' => $REGENERATE_SECURITY_KEYS
];

$installer = new Installer($installerConfig, $BACKUP_METADATA);
$installer->run();
