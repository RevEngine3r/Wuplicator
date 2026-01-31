<?php
/**
 * Wuplicator Backupper - Installer Generator Module
 * 
 * Generates installer.php with embedded metadata and security token.
 * 
 * @package Wuplicator\Backupper\Generator
 * @version 1.2.0
 */

namespace Wuplicator\Backupper\Generator;

use Wuplicator\Backupper\Core\Logger;
use Wuplicator\Backupper\Core\Utils;

class InstallerGenerator {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Generate installer.php with embedded metadata
     * 
     * @param string $wpRoot WordPress root directory
     * @param array $metadata Backup metadata (config, site URL)
     * @param string $outputFile Output installer.php path
     * @return string Path to generated installer
     * @throws \Exception If generation fails
     */
    public function generate($wpRoot, $metadata, $outputFile) {
        $this->logger->log('Generating installer...');
        
        // Find installer template
        $templatePath = rtrim($wpRoot, '/') . '/installer.php';
        if (!file_exists($templatePath)) {
            // Look in src directory if we're in development
            $templatePath = dirname(dirname(dirname(__DIR__))) . '/installer.php';
            if (!file_exists($templatePath)) {
                throw new \Exception("Installer template not found");
            }
        }
        
        $installer = file_get_contents($templatePath);
        
        // Generate security token
        $token = Utils::generateToken();
        
        // Embed metadata
        $installer = str_replace('WUPLICATOR_TOKEN_PLACEHOLDER', $token, $installer);
        $installer = str_replace('TIMESTAMP_PLACEHOLDER', Utils::timestamp(), $installer);
        $installer = str_replace('DB_NAME_PLACEHOLDER', $metadata['db_name'], $installer);
        $installer = str_replace('TABLE_PREFIX_PLACEHOLDER', $metadata['table_prefix'], $installer);
        $installer = str_replace('SITE_URL_PLACEHOLDER', $metadata['site_url'], $installer);
        
        // Save installer
        if (file_put_contents($outputFile, $installer) === false) {
            throw new \Exception("Failed to write installer");
        }
        
        $this->logger->log('Installer generated with security token');
        $this->logger->log("Original site: {$metadata['site_url']}");
        $this->logger->log("Table prefix: {$metadata['table_prefix']}");
        
        return $outputFile;
    }
}
