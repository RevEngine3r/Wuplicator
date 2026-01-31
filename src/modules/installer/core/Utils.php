<?php
/**
 * Wuplicator Installer - Utilities Module
 * 
 * @package Wuplicator\Installer\Core
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Core;

class Utils {
    
    public static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
