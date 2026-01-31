<?php
/**
 * Database Connection Manager
 * 
 * Handles MySQL database connections using PDO.
 */

namespace Wuplicator\Backupper\Database;

use PDO;
use PDOException;
use Exception;

class Connection {
    
    private $pdo;
    
    /**
     * Connect to MySQL database
     * 
     * @param array $config Database configuration
     * @return PDO Database connection
     * @throws Exception If connection fails
     */
    public function connect($config) {
        try {
            $dsn = "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};charset={$config['DB_CHARSET']}";
            $this->pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            return $this->pdo;
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get site URL from database
     * 
     * @param PDO $pdo Database connection
     * @param string $tablePrefix Table prefix
     * @return string Site URL
     */
    public function getSiteURL($pdo, $tablePrefix) {
        try {
            $stmt = $pdo->query("SELECT option_value FROM {$tablePrefix}options WHERE option_name = 'siteurl' LIMIT 1");
            $url = $stmt->fetchColumn();
            return $url ?: '';
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Get all tables in database
     * 
     * @param PDO $pdo Database connection
     * @return array Table names
     */
    public function getTables($pdo) {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        return $tables;
    }
}
