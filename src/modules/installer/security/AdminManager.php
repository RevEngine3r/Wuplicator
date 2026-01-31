<?php
/**
 * Admin Credentials Manager
 * 
 * Manages WordPress admin username and password with random generation support.
 */

namespace Wuplicator\Installer\Security;

use Wuplicator\Installer\Core\Logger;
use PDO;
use Exception;

class AdminManager {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Update admin credentials
     * 
     * @param PDO $pdo Database connection
     * @param string $tablePrefix Table prefix
     * @param string $newUser New username (optional)
     * @param string $newPass New password (optional)
     * @param bool $randomizeUser Generate random username
     * @param bool $randomizePass Generate random password
     * @param string $workDir WordPress directory
     * @return array Generated credentials
     * @throws Exception If update fails
     */
    public function update($pdo, $tablePrefix, $newUser, $newPass, $randomizeUser, $randomizePass, $workDir) {
        $this->logger->log('Updating admin credentials...');
        
        $generatedCredentials = [];
        
        // Generate random credentials if enabled
        if ($randomizeUser) {
            $newUser = $this->generateRandomUsername();
            $generatedCredentials['username'] = $newUser;
            $this->logger->log("Generated random username: {$newUser}");
        }
        
        if ($randomizePass) {
            $newPass = $this->generateRandomPassword();
            $generatedCredentials['password'] = $newPass;
            $this->logger->log("Generated random password: {$newPass}");
        }
        
        // Skip if no credentials to update
        if (empty($newUser) && empty($newPass)) {
            $this->logger->log('No admin credentials to update');
            return $generatedCredentials;
        }
        
        try {
            // Find admin user (ID = 1 usually)
            $stmt = $pdo->query("SELECT ID FROM {$tablePrefix}users WHERE ID = 1 LIMIT 1");
            $adminId = $stmt->fetchColumn();
            
            if (!$adminId) {
                throw new Exception('Admin user not found');
            }
            
            // Update username
            if (!empty($newUser)) {
                $stmt = $pdo->prepare("UPDATE {$tablePrefix}users SET user_login = ? WHERE ID = ?");
                $stmt->execute([$newUser, $adminId]);
                $this->logger->log("✓ Admin username set to: {$newUser}");
            }
            
            // Update password
            if (!empty($newPass)) {
                // Load WordPress password hasher
                require_once($workDir . '/wp-includes/class-phpass.php');
                $hasher = new \PasswordHash(8, true);
                $hashedPass = $hasher->HashPassword($newPass);
                
                $stmt = $pdo->prepare("UPDATE {$tablePrefix}users SET user_pass = ? WHERE ID = ?");
                $stmt->execute([$hashedPass, $adminId]);
                $this->logger->log("✓ Admin password set to: {$newPass}");
            }
            
            return $generatedCredentials;
            
        } catch (Exception $e) {
            throw new Exception('Admin update failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate random admin username
     * Pattern: admin_[5 random alphanumeric chars]
     * 
     * @return string Generated username
     */
    private function generateRandomUsername() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $suffix = '';
        $length = 5;
        
        for ($i = 0; $i < $length; $i++) {
            $suffix .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return 'admin_' . $suffix;
    }
    
    /**
     * Generate random admin password
     * Pattern: 12 random alphanumeric chars
     * 
     * @return string Generated password
     */
    private function generateRandomPassword() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        $length = 12;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
}
