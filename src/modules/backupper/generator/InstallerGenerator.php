<?php
/**
 * Installer Generator
 * 
 * Generates installer.php with embedded metadata and security token.
 */

namespace Wuplicator\Backupper\Generator;

use Wuplicator\Backupper\Core\Logger;
use Exception;

class InstallerGenerator {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Generate installer.php with embedded metadata
     * 
     * @param string $templatePath Path to installer template
     * @param string $outputPath Output installer path
     * @param array $metadata Site metadata
     * @return string Path to generated installer
     * @throws Exception If generation fails
     */
    public function generate($templatePath, $outputPath, $metadata) {
        $this->logger->log('Generating installer...');
        
        // Read installer template
        if (!file_exists($templatePath)) {
            throw new Exception("Installer template not found: {$templatePath}");
        }
        
        $installer = file_get_contents($templatePath);
        
        // Generate security token
        $token = bin2hex(random_bytes(32));
        
        // Embed metadata
        $installer = str_replace('WUPLICATOR_TOKEN_PLACEHOLDER', $token, $installer);
        $installer = str_replace('TIMESTAMP_PLACEHOLDER', date('Y-m-d H:i:s'), $installer);
        $installer = str_replace('DB_NAME_PLACEHOLDER', $metadata['db_name'], $installer);
        $installer = str_replace('TABLE_PREFIX_PLACEHOLDER', $metadata['table_prefix'], $installer);
        $installer = str_replace('SITE_URL_PLACEHOLDER', $metadata['site_url'], $installer);
        
        // Save installer
        if (file_put_contents($outputPath, $installer) === false) {
            throw new Exception("Failed to write installer: {$outputPath}");
        }
        
        $this->logger->log('Installer generated with security token');
        $this->logger->log("Original site: {$metadata['site_url']}");
        $this->logger->log("Table prefix: {$metadata['table_prefix']}");
        
        return $outputPath;
    }
}
