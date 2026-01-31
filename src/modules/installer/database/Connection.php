<?php
/**
 * Wuplicator Installer - Database Connection Module
 * 
 * @package Wuplicator\Installer\Database
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Database;

use Wuplicator\Installer\Core\Logger;
use \PDO;
use \PDOException;

class Connection {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Connect to database and create if not exists
     * 
     * @param array $config Database configuration
     * @return PDO|null Database connection or null on failure
     */
    public function connect($config) {
        try {
            // Connect without database
            $pdo = new PDO(
                "mysql:host={$config['host']}",
                $config['user'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database
            $dbName = $config['name'];
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->logger->log("Database '{$dbName}' created");
            
            // Select database
            $pdo->exec("USE `{$dbName}`");
            
            return $pdo;
        } catch (PDOException $e) {
            $this->logger->error('Database error: ' . $e->getMessage());
            return null;
        }
    }
}
