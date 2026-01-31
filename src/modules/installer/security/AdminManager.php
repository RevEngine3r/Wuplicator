<?php
/**
 * Wuplicator Installer - Admin Manager Module
 * 
 * Manages admin credentials with optional random generation.
 * 
 * @package Wuplicator\Installer\Security
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Security;

use Wuplicator\Installer\Core\Logger;
use \PDO;

class AdminManager {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Update admin credentials
     * 
     * @param PDO $pdo Database connection
     * @param string $workDir Working directory (for PasswordHash class)
     * @param string $tablePrefix Table prefix
     * @param string $newUser New username (empty to skip)
     * @param string $newPass New password (empty to skip)
     * @param bool $randomizeUser Generate random username
     * @param bool $randomizePass Generate random password
     * @return array|null Generated credentials or null on failure
     */
    public function update($pdo, $workDir, $tablePrefix, $newUser, $newPass, $randomizeUser, $randomizePass) {
        $this->logger->log('Updating admin credentials...');
        
        // Generate random credentials if requested
        if ($randomizeUser) {
            $newUser = $this->generateUsername();
            $this->logger->log("Generated random username: {$newUser}");
        }
        
        if ($randomizePass) {
            $newPass = $this->generatePassword();
            $this->logger->log("Generated random password: {$newPass}");
        }
        
        // Skip if no credentials to update
        if (empty($newUser) && empty($newPass)) {
            $this->logger->log('No admin credentials to update');
            return null;
        }
        
        try {
            // Find admin user
            $stmt = $pdo->query("SELECT ID FROM {$tablePrefix}users WHERE ID = 1 LIMIT 1");
            $adminId = $stmt->fetchColumn();
            
            if (!$adminId) {
                $this->logger->error('Admin user not found');
                return null;
            }
            
            // Update username
            if (!empty($newUser)) {
                $stmt = $pdo->prepare("UPDATE {$tablePrefix}users SET user_login = ? WHERE ID = ?");
                $stmt->execute([$newUser, $adminId]);
                $this->logger->log("✓ Admin username set to: {$newUser}");
            }
            
            // Update password
            if (!empty($newPass)) {
                require_once(rtrim($workDir, '/') . '/wp-includes/class-phpass.php');
                $hasher = new \PasswordHash(8, true);
                $hashedPass = $hasher->HashPassword($newPass);
                
                $stmt = $pdo->prepare("UPDATE {$tablePrefix}users SET user_pass = ? WHERE ID = ?");
                $stmt->execute([$hashedPass, $adminId]);
                $this->logger->log("✓ Admin password set to: {$newPass}");
            }
            
            return [
                'username' => $newUser,
                'password' => $newPass
            ];
        } catch (\Exception $e) {
            $this->logger->error('Admin update failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generate random admin username (admin_[5 chars])
     */
    private function generateUsername() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $suffix = '';
        for ($i = 0; $i < 5; $i++) {
            $suffix .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return 'admin_' . $suffix;
    }
    
    /**
     * Generate random admin password (12 chars)
     */
    private function generatePassword() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < 12; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}
