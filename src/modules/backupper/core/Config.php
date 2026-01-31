<?php
/**
 * Wuplicator Backupper - Configuration Module
 * 
 * Centralized configuration and constants for the backupper.
 * 
 * @package Wuplicator\Backupper\Core
 * @version 1.2.0
 */

namespace Wuplicator\Backupper\Core;

class Config {
    
    /** @var string Backupper version */
    const VERSION = '1.2.0';
    
    /** @var array Default exclusion patterns */
    public static $defaultExcludes = [
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
        'debug.log'
    ];
    
    /** @var int Database export chunk size (rows per INSERT) */
    const DB_CHUNK_SIZE = 1000;
    
    /** @var int Progress update interval (percent) */
    const PROGRESS_INTERVAL = 10;
    
    /** @var string Backup directory name */
    const BACKUP_DIR_NAME = 'wuplicator-backups';
    
    /** @var array Required PHP extensions */
    public static $requiredExtensions = [
        'pdo',
        'pdo_mysql',
        'zip'
    ];
    
    /**
     * Check if all required PHP extensions are available
     * 
     * @return array Missing extensions (empty if all available)
     */
    public static function checkRequirements() {
        $missing = [];
        
        foreach (self::$requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        return $missing;
    }
    
    /**
     * Get backup directory path
     * 
     * @param string $wpRoot WordPress root directory
     * @return string Backup directory path
     */
    public static function getBackupDir($wpRoot) {
        return rtrim($wpRoot, '/') . '/' . self::BACKUP_DIR_NAME;
    }
}
