<?php
/**
 * Wuplicator Installer - wp-config.php Updater Module
 * 
 * Updates wp-config.php database settings.
 * 
 * @package Wuplicator\Installer\Configuration
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Configuration;

use Wuplicator\Installer\Core\Logger;

class WpConfigUpdater {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Update wp-config.php database settings
     * 
     * @param string $wpConfigPath Path to wp-config.php
     * @param array $config New database configuration
     * @return bool Success
     */
    public function update($wpConfigPath, $config) {
        if (!file_exists($wpConfigPath)) {
            $this->logger->error('wp-config.php not found');
            return false;
        }
        
        $content = file_get_contents($wpConfigPath);
        
        $content = preg_replace(
            "/define\s*\(\s*'DB_NAME'\s*,\s*'[^']*'\s*\)/",
            "define('DB_NAME', '{$config['name']}')",
            $content
        );
        $content = preg_replace(
            "/define\s*\(\s*'DB_USER'\s*,\s*'[^']*'\s*\)/",
            "define('DB_USER', '{$config['user']}')",
            $content
        );
        $content = preg_replace(
            "/define\s*\(\s*'DB_PASSWORD'\s*,\s*'[^']*'\s*\)/",
            "define('DB_PASSWORD', '{$config['password']}')",
            $content
        );
        $content = preg_replace(
            "/define\s*\(\s*'DB_HOST'\s*,\s*'[^']*'\s*\)/",
            "define('DB_HOST', '{$config['host']}')",
            $content
        );
        
        if (file_put_contents($wpConfigPath, $content) !== false) {
            $this->logger->log('wp-config.php database settings updated');
            return true;
        }
        
        $this->logger->error('Failed to update wp-config.php');
        return false;
    }
}
