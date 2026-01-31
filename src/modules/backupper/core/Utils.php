<?php
/**
 * Wuplicator Backupper - Utilities Module
 * 
 * Common utility functions used across the backupper.
 * 
 * @package Wuplicator\Backupper\Core
 * @version 1.2.0
 */

namespace Wuplicator\Backupper\Core;

class Utils {
    
    /**
     * Format bytes to human-readable size
     * 
     * @param int $bytes Bytes
     * @return string Formatted size (e.g., "15.2 MB")
     */
    public static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Generate a secure random token
     * 
     * @param int $length Token length in bytes
     * @return string Hexadecimal token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Ensure directory exists and is writable
     * 
     * @param string $dir Directory path
     * @return bool True on success
     * @throws \Exception If directory cannot be created or is not writable
     */
    public static function ensureDirectory($dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new \Exception("Failed to create directory: {$dir}");
            }
        }
        
        if (!is_writable($dir)) {
            throw new \Exception("Directory not writable: {$dir}");
        }
        
        return true;
    }
    
    /**
     * Get current timestamp in standard format
     * 
     * @return string Formatted timestamp (YYYY-MM-DD HH:MM:SS)
     */
    public static function timestamp() {
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Get filename-safe timestamp
     * 
     * @return string Filename-safe timestamp (YYYY-MM-DD_HH-MM-SS)
     */
    public static function filenameTimestamp() {
        return date('Y-m-d_H-i-s');
    }
}
