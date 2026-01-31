<?php
/**
 * Remote Backup Downloader
 * 
 * Downloads backup files from remote URLs using cURL or file_get_contents.
 */

namespace Wuplicator\Installer\Download;

use Wuplicator\Installer\Core\Logger;
use Exception;

class Downloader {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Download backup from URL
     * 
     * @param string $url Remote backup URL
     * @param string $outputFile Local output file path
     * @return bool Success status
     * @throws Exception If download fails
     */
    public function download($url, $outputFile) {
        if (empty($url)) {
            throw new Exception('Backup URL is empty');
        }
        
        $this->logger->log('Downloading backup from: ' . $url);
        
        // Use cURL if available, otherwise file_get_contents
        if (function_exists('curl_init')) {
            return $this->downloadWithCurl($url, $outputFile);
        } else {
            return $this->downloadWithFileGetContents($url, $outputFile);
        }
    }
    
    /**
     * Download using cURL
     */
    private function downloadWithCurl($url, $outputFile) {
        $ch = curl_init($url);
        $fp = fopen($outputFile, 'wb');
        
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
        
        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        fclose($fp);
        
        if (!$success || $httpCode !== 200) {
            throw new Exception('Failed to download backup (HTTP ' . $httpCode . ')');
        }
        
        $size = filesize($outputFile);
        $this->logger->log('Download complete: ' . $this->formatBytes($size));
        
        return true;
    }
    
    /**
     * Download using file_get_contents
     */
    private function downloadWithFileGetContents($url, $outputFile) {
        $content = file_get_contents($url);
        
        if ($content === false) {
            throw new Exception('Failed to download backup. Enable cURL or allow_url_fopen');
        }
        
        file_put_contents($outputFile, $content);
        
        $size = filesize($outputFile);
        $this->logger->log('Download complete: ' . $this->formatBytes($size));
        
        return true;
    }
    
    /**
     * Check if local backup exists
     */
    public function hasLocalBackup($workDir) {
        return file_exists($workDir . '/backup.zip');
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
