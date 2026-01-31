<?php
/**
 * Wuplicator Installer - Configuration
 * 
 * Central configuration management for the installer module.
 * 
 * @package Wuplicator\Installer\Core
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Core;

class Config {
    /**
     * Application version
     */
    const VERSION = '1.2.0';
    
    /**
     * Module name
     */
    const MODULE_NAME = 'Wuplicator Installer';
    
    /**
     * Security token placeholder
     */
    const TOKEN_PLACEHOLDER = 'WUPLICATOR_TOKEN_PLACEHOLDER';
    
    /**
     * Default timeout for downloads (seconds)
     */
    const DOWNLOAD_TIMEOUT = 300;
    
    /**
     * Progress reporting interval (bytes)
     */
    const PROGRESS_BYTES = 1048576; // 1MB
    
    /**
     * SQL batch size for import
     */
    const SQL_BATCH_SIZE = 100;
    
    /**
     * Security keys to regenerate
     */
    const SECURITY_KEYS = [
        'AUTH_KEY',
        'SECURE_AUTH_KEY',
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT'
    ];
    
    /**
     * Get security keys list
     */
    public static function getSecurityKeys(): array {
        return self::SECURITY_KEYS;
    }
    
    /**
     * Get version string
     */
    public static function getVersion(): string {
        return self::VERSION;
    }
}