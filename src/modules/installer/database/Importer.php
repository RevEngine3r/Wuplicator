<?php
/**
 * Wuplicator Installer - SQL Importer Module
 * 
 * @package Wuplicator\Installer\Database
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Database;

use Wuplicator\Installer\Core\Logger;
use \PDO;

class Importer {
    
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Import SQL file into database
     * 
     * @param PDO $pdo Database connection
     * @param string $sqlFile SQL file path
     * @return bool Success
     */
    public function import($pdo, $sqlFile) {
        if (!file_exists($sqlFile)) {
            $this->logger->error('SQL file not found');
            return false;
        }
        
        $this->logger->log('Importing database...');
        
        try {
            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);
            $this->logger->log('Database imported successfully');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Import failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find SQL file in directory
     * 
     * @param string $workDir Working directory
     * @return string|null SQL file path or null
     */
    public function findSQLFile($workDir) {
        $files = glob(rtrim($workDir, '/') . '/*.sql');
        return !empty($files) ? $files[0] : null;
    }
}
