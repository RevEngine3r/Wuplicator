<?php
/**
 * URL Migrator
 * 
 * Replaces old URLs with new URLs in database.
 */

namespace Wuplicator\Installer\Database;

use Wuplicator\Installer\Core\Logger;
use PDO;
use Exception;

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
     * @throws Exception If replacement fails
     */
    public function replaceURLs($pdo, $oldUrl, $newUrl, $tablePrefix) {
        if (empty($oldUrl) || empty($newUrl)) {
            $this->logger->log('Skipping URL replacement (URLs not provided)');
            return;
        }
        
        if ($oldUrl === $newUrl) {
            $this->logger->log('Skipping URL replacement (URLs are identical)');
            return;
        }
        
        $this->logger->log("Replacing URLs: {$oldUrl} → {$newUrl}");
        
        try {
            // Update options table
            $stmt = $pdo->prepare("UPDATE {$tablePrefix}options SET option_value = REPLACE(option_value, ?, ?) WHERE option_value LIKE ?");
            $stmt->execute([$oldUrl, $newUrl, "%{$oldUrl}%"]);
            
            // Update posts table
            $stmt = $pdo->prepare("UPDATE {$tablePrefix}posts SET post_content = REPLACE(post_content, ?, ?) WHERE post_content LIKE ?");
            $stmt->execute([$oldUrl, $newUrl, "%{$oldUrl}%"]);
            
            // Update post meta
            $stmt = $pdo->prepare("UPDATE {$tablePrefix}postmeta SET meta_value = REPLACE(meta_value, ?, ?) WHERE meta_value LIKE ?");
            $stmt->execute([$oldUrl, $newUrl, "%{$oldUrl}%"]);
            
            $this->logger->log('URLs updated in database');
        } catch (Exception $e) {
            throw new Exception('URL replacement failed: ' . $e->getMessage());
        }
    }
}
