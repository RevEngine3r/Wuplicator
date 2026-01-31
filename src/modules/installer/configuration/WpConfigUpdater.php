<?php
/**
 * wp-config.php Updater
 * 
 * Updates WordPress configuration file with new database credentials.
 */

namespace Wuplicator\Installer\Configuration;

use Wuplicator\Installer\Core\Logger;
use Exception;

class WpConfigUpdater {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Update wp-config.php with new database settings
     * 
     * @param string $wpConfigPath Path to wp-config.php
     * @param array $config New database configuration
     * @throws Exception If update fails
     */
    public function update($wpConfigPath, $config) {
        if (!file_exists($wpConfigPath)) {
            throw new Exception('wp-config.php not found');
        }
        
        $this->logger->log('Updating wp-config.php...');
        
        $content = file_get_contents($wpConfigPath);
        
        // Replace database constants
        $content = preg_replace("/define\\s*\\(\\s*'DB_NAME'\\s*,\\s*'[^']*'\\s*\\)/", "define('DB_NAME', '{$config['DB_NAME']}')", $content);
        $content = preg_replace("/define\\s*\\(\\s*'DB_USER'\\s*,\\s*'[^']*'\\s*\\)/", "define('DB_USER', '{$config['DB_USER']}')", $content);
        $content = preg_replace("/define\\s*\\(\\s*'DB_PASSWORD'\\s*,\\s*'[^']*'\\s*\\)/", "define('DB_PASSWORD', '{$config['DB_PASSWORD']}')", $content);
        $content = preg_replace("/define\\s*\\(\\s*'DB_HOST'\\s*,\\s*'[^']*'\\s*\\)/", "define('DB_HOST', '{$config['DB_HOST']}')", $content);
        
        if (file_put_contents($wpConfigPath, $content) === false) {
            throw new Exception('Failed to write wp-config.php');
        }
        
        $this->logger->log('wp-config.php database settings updated');
    }
}
