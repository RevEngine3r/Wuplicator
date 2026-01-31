<?php
/**
 * Utility Functions
 * 
 * Common helper functions for installer.
 */

namespace Wuplicator\Installer\Core;

class Utils {
    
    /**
     * Format bytes to human-readable size
     * 
     * @param int $bytes Bytes
     * @return string Formatted size
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
     * Generate random token
     * 
     * @param int $length Token length
     * @return string Random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}
