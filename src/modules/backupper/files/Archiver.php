<?php
/**
 * Wuplicator Backupper - ZIP Archiver Module
 * 
 * Creates ZIP archives from file lists.
 * 
 * @package Wuplicator\Backupper\Files
 * @version 1.2.0
 */

namespace Wuplicator\Backupper\Files;

use Wuplicator\Backupper\Core\Logger;
use Wuplicator\Backupper\Core\Config;
use \ZipArchive;

class Archiver {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Create ZIP archive from file list
     * 
     * @param array $files File paths relative to WordPress root
     * @param string $wpRoot WordPress root directory
     * @param string $outputFile Output ZIP file path
     * @return array Archive metadata
     * @throws \Exception If archive creation fails
     */
    public function create($files, $wpRoot, $outputFile) {
        $fileCount = count($files);
        $this->logger->log("Creating ZIP archive with {$fileCount} files...");
        
        $zip = new ZipArchive();
        if ($zip->open($outputFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Failed to create ZIP archive: {$outputFile}");
        }
        
        // Add files to archive with progress tracking
        $processed = 0;
        $lastProgress = 0;
        
        foreach ($files as $file) {
            $fullPath = rtrim($wpRoot, '/') . '/' . $file;
            
            // Skip if file no longer exists or is not readable
            if (!file_exists($fullPath) || !is_readable($fullPath)) {
                continue;
            }
            
            $zip->addFile($fullPath, $file);
            $processed++;
            
            // Progress feedback every 10%
            $progress = floor(($processed / $fileCount) * 100);
            if ($progress >= $lastProgress + Config::PROGRESS_INTERVAL) {
                $this->logger->log("Progress: {$progress}% ({$processed}/{$fileCount} files)");
                $lastProgress = $progress;
            }
        }
        
        $zip->close();
        
        return [
            'file' => $outputFile,
            'files_added' => $processed,
            'size' => filesize($outputFile)
        ];
    }
}
