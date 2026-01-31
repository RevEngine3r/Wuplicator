<?php
/**
 * Wuplicator Installer - Utilities
 * 
 * Common utility functions.
 * 
 * @package Wuplicator\Installer\Core
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Core;

class Utils {
    public static function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    public static function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }
    
    public static function sanitizePath(string $path): string {
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        $path = preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR, '#') . '+#', DIRECTORY_SEPARATOR, $path);
        return rtrim($path, DIRECTORY_SEPARATOR);
    }
    
    public static function validatePath(string $path): bool {
        return file_exists($path) && is_readable($path);
    }
    
    public static function ensureDirectory(string $path, int $permissions = 0755): bool {
        if (is_dir($path)) {
            return true;
        }
        return mkdir($path, $permissions, true);
    }
    
    public static function validateURL(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}