<?php
/**
 * Database Connection Manager
 * 
 * Manages MySQL database connections and operations.
 */

namespace Wuplicator\Installer\Database;

use PDO;
use PDOException;
use Exception;

class Connection {
    
    /**
     * Create database connection
     * 
     * @param string $host Database host
     * @param string $dbName Database name (optional for initial connection)
     * @param string $user Database user
     * @param string $password Database password
     * @return PDO Database connection
     * @throws Exception If connection fails
     */
    public function connect($host, $dbName, $user, $password) {
        try {
            if (empty($dbName)) {
                $dsn = "mysql:host={$host}";
            } else {
                $dsn = "mysql:host={$host};dbname={$dbName}";
            }
            
            $pdo = new PDO($dsn, $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create database if not exists
     * 
     * @param PDO $pdo Database connection
     * @param string $dbName Database name
     */
    public function createDatabase($pdo, $dbName) {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    
    /**
     * Use specific database
     * 
     * @param PDO $pdo Database connection
     * @param string $dbName Database name
     */
    public function useDatabase($pdo, $dbName) {
        $pdo->exec("USE `{$dbName}`");
    }
}
