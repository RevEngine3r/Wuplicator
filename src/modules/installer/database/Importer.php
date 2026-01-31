<?php
/**
 * SQL Importer
 * 
 * Imports SQL files into MySQL database.
 */

namespace Wuplicator\Installer\Database;

use Wuplicator\Installer\Core\Logger;
use PDO;
use Exception;

class Importer {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Import SQL file
     * 
     * @param PDO $pdo Database connection
     * @param string $sqlFile Path to SQL file
     * @throws Exception If import fails
     */
    public function import($pdo, $sqlFile) {
        if (!file_exists($sqlFile)) {
            throw new Exception('SQL backup file not found');
        }
        
        $this->logger->log('Importing database...');
        
        $sql = file_get_contents($sqlFile);
        
        // Execute SQL (handle multi-statement execution)
        try {
            $pdo->exec($sql);
            $this->logger->log('Database imported successfully');
        } catch (Exception $e) {
            throw new Exception('Database import failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Find SQL file in directory
     * 
     * @param string $directory Directory to search
     * @return string|null SQL file path or null
     */
    public function findSQLFile($directory) {
        $files = glob($directory . '/*.sql');
        return !empty($files) ? $files[0] : null;
    }
}
