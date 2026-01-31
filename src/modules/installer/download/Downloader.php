<?php
/**
 * Wuplicator Installer - Backup Downloader Module
 * 
 * Downloads remote backup files with cURL or file_get_contents fallback.
 * 
 * @package Wuplicator\Installer\Download
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Download;

use Wuplicator\Installer\Core\{Logger, Config, Utils};

class Downloader {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Download backup from URL or check local file
     * 
     * @param string $url Remote URL (empty for local)
     * @param string $workDir Working directory
     * @return bool Success
     */
    public function download($url, $workDir) {
        if (empty($url)) {
            return $this->checkLocal($workDir);
        }
        
        $this->logger->log('Downloading backup from: ' . $url);
        $zipFile = rtrim($workDir, '/') . '/backup.zip';
        
        if (function_exists('curl_init')) {
            return $this->downloadWithCurl($url, $zipFile);
        } else {
            return $this->downloadWithFileGetContents($url, $zipFile);
        }
    }
    
    private function checkLocal($workDir) {
        $zipFile = rtrim($workDir, '/') . '/backup.zip';
        if (file_exists($zipFile)) {
            $this->logger->log('Using local backup.zip');
            return true;
        }
        
        $this->logger->error('No backup found. Provide BACKUP_URL or upload backup.zip');
        return false;
    }
    
    private function downloadWithCurl($url, $zipFile) {
        $ch = curl_init($url);
        $fp = fopen($zipFile, 'wb');
        
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, Config::DOWNLOAD_TIMEOUT);
        
        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        fclose($fp);
        
        if (!$success || $httpCode !== 200) {
            $this->logger->error('Failed to download backup (HTTP ' . $httpCode . ')');
            return false;
        }
        
        $size = filesize($zipFile);
        $this->logger->log('Download complete: ' . Utils::formatBytes($size));
        return true;
    }
    
    private function downloadWithFileGetContents($url, $zipFile) {
        $content = file_get_contents($url);
        if ($content === false) {
            $this->logger->error('Failed to download backup. Enable cURL or allow_url_fopen');
            return false;
        }
        
        file_put_contents($zipFile, $content);
        $size = filesize($zipFile);
        $this->logger->log('Download complete: ' . Utils::formatBytes($size));
        return true;
    }
}
