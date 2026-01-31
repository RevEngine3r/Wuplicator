<?php
/**
 * Wuplicator Backupper - Directory Scanner Module
 * 
 * Scans WordPress directory structure with exclusion support.
 * 
 * @package Wuplicator\Backupper\Files
 * @version 1.2.0
 */

namespace Wuplicator\Backupper\Files;

use Wuplicator\Backupper\Core\Config;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;

class Scanner {
    
    /**
     * Scan directory recursively
     * 
     * @param string $path Directory path
     * @param string $wpRoot WordPress root for relative paths
     * @param array $customExcludes Custom exclusion patterns
     * @return array File paths relative to WordPress root
     */
    public function scan($path, $wpRoot, $customExcludes = []) {
        $files = [];
        $excludes = array_merge(Config::$defaultExcludes, $customExcludes);
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            // Skip symbolic links
            if ($item->isLink()) {
                continue;
            }
            
            $filePath = $item->getPathname();
            $relativePath = str_replace($wpRoot . '/', '', $filePath);
            
            // Check exclusions
            if ($this->isExcluded($relativePath, $excludes)) {
                continue;
            }
            
            // Add files only (directories created implicitly in ZIP)
            if ($item->isFile()) {
                $files[] = $relativePath;
            }
        }
        
        return $files;
    }
    
    /**
     * Check if file path matches exclusion patterns
     * 
     * @param string $relativePath Relative file path
     * @param array $excludes Exclusion patterns
     * @return bool True if file should be excluded
     */
    private function isExcluded($relativePath, $excludes) {
        foreach ($excludes as $pattern) {
            // Exact match or substring match
            if (strpos($relativePath, $pattern) !== false) {
                return true;
            }
            
            // Wildcard pattern (*.log, *.tmp)
            if (strpos($pattern, '*') !== false) {
                $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
                if (preg_match($regex, basename($relativePath))) {
                    return true;
                }
            }
        }
        
        return false;
    }
}
