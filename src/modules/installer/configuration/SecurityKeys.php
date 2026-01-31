<?php
/**
 * Wuplicator Installer - Security Keys Module
 * 
 * Regenerates WordPress security keys in wp-config.php.
 * 
 * @package Wuplicator\Installer\Configuration
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Configuration;

use Wuplicator\Installer\Core\Logger;

class SecurityKeys {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Regenerate all WordPress security keys
     * 
     * @param string $wpConfigPath Path to wp-config.php
     * @return bool Success
     */
    public function regenerate($wpConfigPath) {
        $this->logger->log('Regenerating WordPress security keys...');
        
        if (!file_exists($wpConfigPath)) {
            $this->logger->error('wp-config.php not found');
            return false;
        }
        
        $content = file_get_contents($wpConfigPath);
        
        $keys = [
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT'
        ];
        
        foreach ($keys as $key) {
            $newValue = $this->generateKey(64);
            $pattern = "/define\s*\(\s*['\"]{$key}['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)/";
            $replacement = "define('{$key}', '{$newValue}')";
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        if (file_put_contents($wpConfigPath, $content) !== false) {
            $this->logger->log('✓ All 8 security keys regenerated successfully');
            return true;
        }
        
        $this->logger->error('Failed to write wp-config.php');
        return false;
    }
    
    /**
     * Generate cryptographically secure random key
     * 
     * @param int $length Key length
     * @return string Random key
     */
    private function generateKey($length = 64) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
        $key = '';
        $charsLength = strlen($chars);
        $randomBytes = random_bytes($length);
        
        for ($i = 0; $i < $length; $i++) {
            $key .= $chars[ord($randomBytes[$i]) % $charsLength];
        }
        
        return $key;
    }
}
