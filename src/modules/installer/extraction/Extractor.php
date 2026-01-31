<?php
/**
 * Archive Extractor
 * 
 * Extracts ZIP archives with validation.
 */

namespace Wuplicator\Installer\Extraction;

use Wuplicator\Installer\Core\Logger;
use ZipArchive;
use Exception;

class Extractor {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Extract backup archive
     * 
     * @param string $zipFile Path to ZIP file
     * @param string $destination Extraction destination
     * @return int Number of files extracted
     * @throws Exception If extraction fails
     */
    public function extract($zipFile, $destination) {
        $this->logger->log('Extracting backup archive...');
        
        if (!file_exists($zipFile)) {
            throw new Exception('Backup file not found');
        }
        
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive extension not available');
        }
        
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new Exception('Failed to open backup archive');
        }
        
        $numFiles = $zip->numFiles;
        $extracted = $zip->extractTo($destination);
        $zip->close();
        
        if (!$extracted) {
            throw new Exception('Failed to extract files');
        }
        
        $this->logger->log("Extracted {$numFiles} files successfully");
        
        return $numFiles;
    }
}
