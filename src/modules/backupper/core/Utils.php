<?php
/**
 * Wuplicator Backupper - Utilities
 * 
 * Common utility functions.
 * 
 * @package Wuplicator\Backupper\Core
 * @version 1.2.0
 */

namespace Wuplicator\Backupper\Core;

class Utils {
    /**
     * Format bytes to human-readable size
     */
    public static function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Generate cryptographically secure token
     */
    public static function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Sanitize file path
     */
    public static function sanitizePath(string $path): string {
        // Normalize separators
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        
        // Remove double separators
        $path = preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR, '#') . '+#', DIRECTORY_SEPARATOR, $path);
        
        // Remove trailing separator
        return rtrim($path, DIRECTORY_SEPARATOR);
    }
    
    /**
     * Validate path exists and is readable
     */
    public static function validatePath(string $path): bool {
        return file_exists($path) && is_readable($path);
    }
    
    /**
     * Get relative path from base
     */
    public static function getRelativePath(string $from, string $to): string {
        $from = self::sanitizePath($from);
        $to = self::sanitizePath($to);
        
        if (str_starts_with($to, $from)) {
            return substr($to, strlen($from) + 1);
        }
        
        return $to;
    }
    
    /**
     * Ensure directory exists
     */
    public static function ensureDirectory(string $path, int $permissions = 0755): bool {
        if (is_dir($path)) {
            return true;
        }
        
        return mkdir($path, $permissions, true);
    }
}