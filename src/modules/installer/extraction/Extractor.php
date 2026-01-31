<?php
/**
 * Wuplicator Installer - Backup Extractor Module
 * 
 * Extracts ZIP archives.
 * 
 * @package Wuplicator\Installer\Extraction
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Extraction;

use Wuplicator\Installer\Core\Logger;
use \ZipArchive;

class Extractor {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Extract backup archive
     * 
     * @param string $workDir Working directory
     * @return bool Success
     */
    public function extract($workDir) {
        $this->logger->log('Extracting backup archive...');
        
        $zipFile = rtrim($workDir, '/') . '/backup.zip';
        if (!file_exists($zipFile)) {
            $this->logger->error('Backup file not found');
            return false;
        }
        
        if (!class_exists('ZipArchive')) {
            $this->logger->error('ZipArchive extension not available');
            return false;
        }
        
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            $this->logger->error('Failed to open backup archive');
            return false;
        }
        
        $extracted = $zip->extractTo($workDir);
        $numFiles = $zip->numFiles;
        $zip->close();
        
        if (!$extracted) {
            $this->logger->error('Failed to extract files');
            return false;
        }
        
        $this->logger->log("Extracted {$numFiles} files successfully");
        return true;
    }
}
