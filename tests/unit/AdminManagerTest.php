<?php
/**
 * Admin Manager Tests (v1.1.0)
 */

require_once __DIR__ . '/../TestCase.php';

class AdminManagerTest extends TestCase {
    
    public function __construct() {
        parent::__construct('Admin Manager (v1.1.0)');
    }
    
    public function run() {
        $this->testUsernameGeneration();
        $this->testPasswordGeneration();
    }
    
    private function testUsernameGeneration() {
        $username1 = $this->generateTestUsername();
        $username2 = $this->generateTestUsername();
        
        $this->assertTrue(strpos($username1, 'admin_') === 0, 'Username starts with admin_');
        $this->assertEquals(11, strlen($username1), 'Username is admin_ + 5 chars (11 total)');
        $this->assertTrue($username1 !== $username2, 'Generates unique usernames');
    }
    
    private function testPasswordGeneration() {
        $password1 = $this->generateTestPassword();
        $password2 = $this->generateTestPassword();
        
        $this->assertEquals(12, strlen($password1), 'Password is 12 characters');
        $this->assertTrue($password1 !== $password2, 'Generates unique passwords');
        $this->assertTrue(ctype_alnum($password1), 'Password is alphanumeric');
    }
    
    private function generateTestUsername() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $suffix = '';
        $length = 5;
        
        for ($i = 0; $i < $length; $i++) {
            $suffix .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return 'admin_' . $suffix;
    }
    
    private function generateTestPassword() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        $length = 12;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
}
