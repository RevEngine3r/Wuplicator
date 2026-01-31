<?php
/**
 * Wuplicator Build System - Module Loader
 * 
 * Discovers and orders modules for compilation.
 * 
 * @version 1.2.0
 */

class ModuleLoader {
    
    /**
     * Discover all PHP modules in directory
     * Orders: core -> other directories -> root
     */
    public function discoverModules($baseDir) {
        $modules = [];
        
        // Order: core first, then subdirectories, then root files
        $order = ['core', 'database', 'files', 'generator', 'download', 'extraction', 'configuration', 'security', 'ui'];
        
        // Process ordered directories first
        foreach ($order as $dir) {
            $path = $baseDir . '/' . $dir;
            if (is_dir($path)) {
                $modules = array_merge($modules, $this->scanDirectory($path));
            }
        }
        
        // Process root level PHP files (orchestrators)
        $rootFiles = glob($baseDir . '/*.php');
        if ($rootFiles) {
            $modules = array_merge($modules, $rootFiles);
        }
        
        return $modules;
    }
    
    /**
     * Scan directory for PHP files recursively
     */
    private function scanDirectory($dir) {
        $files = [];
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        sort($files);
        return $files;
    }
}
