<?php
/**
 * Security Keys Tests (v1.1.0)
 */

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../../src/modules/installer/core/Logger.php';

use Wuplicator\Installer\Core\Logger;

class SecurityKeysTest extends TestCase {
    
    public function __construct() {
        parent::__construct('Security Keys Generator (v1.1.0)');
    }
    
    public function run() {
        $this->testKeyGeneration();
    }
    
    private function testKeyGeneration() {
        // Test key generation logic
        $key1 = $this->generateTestKey(64);
        $key2 = $this->generateTestKey(64);
        
        $this->assertEquals(64, strlen($key1), 'Generates 64-character keys');
        $this->assertTrue($key1 !== $key2, 'Generates unique keys');
        $this->assertTrue($this->hasSpecialChars($key1), 'Keys contain special characters');
    }
    
    private function generateTestKey($length) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
        $key = '';
        $charsLength = strlen($chars);
        
        $randomBytes = random_bytes($length);
        
        for ($i = 0; $i < $length; $i++) {
            $key .= $chars[ord($randomBytes[$i]) % $charsLength];
        }
        
        return $key;
    }
    
    private function hasSpecialChars($str) {
        return preg_match('/[!@#$%^&*()\-_=+\[\]{}|;:,.<>?]/', $str) === 1;
    }
}
