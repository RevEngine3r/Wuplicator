<?php
/**
 * WordPress Security Keys Regenerator
 * 
 * Regenerates all 8 WordPress security keys for enhanced security.
 */

namespace Wuplicator\Installer\Configuration;

use Wuplicator\Installer\Core\Logger;
use Exception;

class SecurityKeys {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Regenerate WordPress security keys in wp-config.php
     * 
     * @param string $wpConfigPath Path to wp-config.php
     * @throws Exception If regeneration fails
     */
    public function regenerate($wpConfigPath) {
        $this->logger->log('Regenerating WordPress security keys...');
        
        if (!file_exists($wpConfigPath)) {
            throw new Exception('wp-config.php not found');
        }
        
        $content = file_get_contents($wpConfigPath);
        
        // All 8 keys that need regeneration
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
        
        // Replace each key
        foreach ($keys as $key) {
            $newValue = $this->generateKey(64);
            $pattern = "/define\\s*\\(\\s*['\"]{$key}['\"]\\s*,\\s*['\"][^'\"]*['\"]\\s*\\)/";
            $replacement = "define('{$key}', '{$newValue}')";
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        // Write updated config
        if (file_put_contents($wpConfigPath, $content) === false) {
            throw new Exception('Failed to write wp-config.php');
        }
        
        $this->logger->log('✓ All 8 security keys regenerated successfully');
        $this->logger->log('  - AUTH_KEY');
        $this->logger->log('  - SECURE_AUTH_KEY');
        $this->logger->log('  - LOGGED_IN_KEY');
        $this->logger->log('  - NONCE_KEY');
        $this->logger->log('  - AUTH_SALT');
        $this->logger->log('  - SECURE_AUTH_SALT');
        $this->logger->log('  - LOGGED_IN_SALT');
        $this->logger->log('  - NONCE_SALT');
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
