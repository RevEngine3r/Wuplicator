<?php
/**
 * Wuplicator Backupper - Configuration
 * 
 * Central configuration management for the backupper module.
 * 
 * @package Wuplicator\Backupper\Core
 * @version 1.2.0
 */

namespace Wuplicator\Backupper\Core;

class Config {
    /**
     * Application version
     */
    const VERSION = '1.2.0';
    
    /**
     * Module name
     */
    const MODULE_NAME = 'Wuplicator Backupper';
    
    /**
     * Default backup directory name
     */
    const BACKUP_DIR = 'wuplicator-backups';
    
    /**
     * Default file permissions
     */
    const DIR_PERMISSIONS = 0755;
    const FILE_PERMISSIONS = 0644;
    
    /**
     * Database export chunk size (rows per INSERT)
     */
    const DB_CHUNK_SIZE = 1000;
    
    /**
     * Default exclusion patterns
     */
    const DEFAULT_EXCLUDES = [
        'wuplicator-backups',
        'wp-content/cache',
        'wp-content/backup',
        'wp-content/backups',
        'wp-content/uploads/backup',
        '.git',
        '.svn',
        'node_modules',
        '.DS_Store',
        'error_log',
        'debug.log',
        '*.tmp',
        '*.log'
    ];
    
    /**
     * Progress reporting interval (percentage)
     */
    const PROGRESS_INTERVAL = 10;
    
    /**
     * Get default excludes
     */
    public static function getDefaultExcludes(): array {
        return self::DEFAULT_EXCLUDES;
    }
    
    /**
     * Get version string
     */
    public static function getVersion(): string {
        return self::VERSION;
    }
}