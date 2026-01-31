<?php
/**
 * File System Scanner
 * 
 * Recursively scans WordPress directory with exclusion support.
 */

namespace Wuplicator\Backupper\Files;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Scanner {
    
    private $defaultExcludes = [
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
    
    /**
     * Scan directory recursively
     * 
     * @param string $path Directory path
     * @param string $wpRoot WordPress root directory
     * @param array $customExcludes Custom exclusion patterns
     * @return array File paths relative to WordPress root
     */
    public function scan($path, $wpRoot, $customExcludes = []) {
        $files = [];
        $excludes = array_merge($this->defaultExcludes, $customExcludes);
        
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
            
            // Add files only (directories are created implicitly in ZIP)
            if ($item->isFile()) {
                $files[] = $relativePath;
            }
        }
        
        return $files;
    }
    
    /**
     * Check if file matches exclusion patterns
     * 
     * @param string $relativePath File path relative to root
     * @param array $excludes Exclusion patterns
     * @return bool True if excluded
     */
    private function isExcluded($relativePath, $excludes) {
        foreach ($excludes as $pattern) {
            // Exact match or substring match
            if (strpos($relativePath, $pattern) !== false) {
                return true;
            }
            
            // Wildcard pattern (*.log, *.tmp)
            if (strpos($pattern, '*') !== false) {
                $regex = '/^' . str_replace('\\*', '.*', preg_quote($pattern, '/')) . '$/';
                if (preg_match($regex, basename($relativePath))) {
                    return true;
                }
            }
        }
        
        return false;
    }
}
