<?php
/**
 * Wuplicator Backupper - Archive Validator Module
 * 
 * Validates ZIP archive integrity.
 * 
 * @package Wuplicator\Backupper\Files
 * @version 1.2.0
 */

namespace Wuplicator\Backupper\Files;

use Wuplicator\Backupper\Core\Logger;
use \ZipArchive;

class Validator {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Validate ZIP archive integrity
     * 
     * @param string $zipPath Path to ZIP file
     * @return bool True if valid
     */
    public function validate($zipPath) {
        $this->logger->log('Validating archive...');
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CHECKCONS) !== true) {
            $this->logger->error('Archive integrity check failed');
            return false;
        }
        
        $numFiles = $zip->numFiles;
        $zip->close();
        
        if ($numFiles === 0) {
            $this->logger->error('Archive is empty');
            return false;
        }
        
        $this->logger->log("Archive contains {$numFiles} files");
        $this->logger->log("Integrity check: PASSED");
        
        return true;
    }
}
