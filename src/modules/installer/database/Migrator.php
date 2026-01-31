<?php
/**
 * Wuplicator Installer - URL Migrator Module
 * 
 * Replaces old URLs with new URLs in database.
 * 
 * @package Wuplicator\Installer\Database
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Database;

use Wuplicator\Installer\Core\Logger;
use \PDO;

class Migrator {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Replace URLs in database
     * 
     * @param PDO $pdo Database connection
     * @param string $oldUrl Old site URL
     * @param string $newUrl New site URL
     * @param string $tablePrefix Table prefix
     * @return bool Success
     */
    public function replaceURLs($pdo, $oldUrl, $newUrl, $tablePrefix) {
        if (empty($oldUrl) || empty($newUrl) || $oldUrl === $newUrl) {
            $this->logger->log('URL replacement skipped (URLs identical or empty)');
            return true;
        }
        
        $this->logger->log("Replacing URLs: {$oldUrl} → {$newUrl}");
        
        try {
            // Update options table
            $stmt = $pdo->prepare(
                "UPDATE {$tablePrefix}options SET option_value = REPLACE(option_value, ?, ?) WHERE option_value LIKE ?"
            );
            $stmt->execute([$oldUrl, $newUrl, "%{$oldUrl}%"]);
            
            // Update posts table
            $stmt = $pdo->prepare(
                "UPDATE {$tablePrefix}posts SET post_content = REPLACE(post_content, ?, ?) WHERE post_content LIKE ?"
            );
            $stmt->execute([$oldUrl, $newUrl, "%{$oldUrl}%"]);
            
            $this->logger->log('URLs updated in database');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('URL replacement failed: ' . $e->getMessage());
            return false;
        }
    }
}
