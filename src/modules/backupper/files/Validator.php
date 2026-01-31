<?php
/**
 * Archive Validator
 * 
 * Validates ZIP archive integrity.
 */

namespace Wuplicator\Backupper\Files;

use Wuplicator\Backupper\Core\Logger;
use ZipArchive;

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
            $this->logger->log('Integrity check: FAILED');
            return false;
        }
        
        $numFiles = $zip->numFiles;
        $zip->close();
        
        $this->logger->log("Archive contains {$numFiles} files");
        $this->logger->log("Integrity check: PASSED");
        
        return $numFiles > 0;
    }
}
