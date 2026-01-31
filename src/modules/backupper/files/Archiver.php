<?php
/**
 * ZIP Archive Creator
 * 
 * Creates ZIP archives with progress tracking.
 */

namespace Wuplicator\Backupper\Files;

use Wuplicator\Backupper\Core\Logger;
use ZipArchive;
use Exception;

class Archiver {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Create ZIP archive of files
     * 
     * @param array $files File paths relative to root
     * @param string $wpRoot WordPress root directory
     * @param string $outputFile Output ZIP file path
     * @return array Archive metadata
     * @throws Exception If archive creation fails
     */
    public function create($files, $wpRoot, $outputFile) {
        // Check ZipArchive extension
        if (!class_exists('ZipArchive')) {
            throw new Exception("ZipArchive extension not available. Install php-zip.");
        }
        
        $fileCount = count($files);
        $this->logger->log("Creating ZIP archive with {$fileCount} files...");
        
        if ($fileCount === 0) {
            throw new Exception("No files found to backup");
        }
        
        // Create ZIP archive
        $zip = new ZipArchive();
        if ($zip->open($outputFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Failed to create ZIP archive: {$outputFile}");
        }
        
        // Add files to archive
        $processed = 0;
        $lastProgress = 0;
        
        foreach ($files as $file) {
            $fullPath = $wpRoot . '/' . $file;
            
            // Skip if file no longer exists or is not readable
            if (!file_exists($fullPath) || !is_readable($fullPath)) {
                continue;
            }
            
            $zip->addFile($fullPath, $file);
            $processed++;
            
            // Progress feedback every 10%
            $progress = floor(($processed / $fileCount) * 100);
            if ($progress >= $lastProgress + 10) {
                $this->logger->log("Progress: {$progress}% ({$processed}/{$fileCount} files)");
                $lastProgress = $progress;
            }
        }
        
        $zip->close();
        
        $fileSize = filesize($outputFile);
        $this->logger->log("Files backup created: " . $this->formatBytes($fileSize));
        $this->logger->log("Files archived: {$processed}");
        
        return [
            'file' => $outputFile,
            'size' => $fileSize,
            'files' => $processed
        ];
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
