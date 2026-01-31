<?php
/**
 * Database Backup Orchestrator
 * 
 * Creates complete SQL backup of WordPress database.
 */

namespace Wuplicator\Backupper\Database;

use Wuplicator\Backupper\Core\Logger;
use Exception;

class Backup {
    
    private $parser;
    private $connection;
    private $exporter;
    private $logger;
    
    public function __construct(Parser $parser, Connection $connection, Exporter $exporter, Logger $logger) {
        $this->parser = $parser;
        $this->connection = $connection;
        $this->exporter = $exporter;
        $this->logger = $logger;
    }
    
    /**
     * Create complete database backup
     * 
     * @param string $wpRoot WordPress root directory
     * @param string $outputFile Output SQL file path
     * @return array Backup metadata
     * @throws Exception If backup fails
     */
    public function create($wpRoot, $outputFile) {
        $this->logger->log('Starting database backup...');
        
        // Parse wp-config.php
        $this->logger->log('Parsing wp-config.php...');
        $wpConfigPath = $wpRoot . '/wp-config.php';
        $config = $this->parser->parse($wpConfigPath);
        $this->logger->log("Database: {$config['DB_NAME']}");
        
        // Connect to database
        $this->logger->log('Connecting to database...');
        $pdo = $this->connection->connect($config);
        $this->logger->log('Connected successfully');
        
        // Get tables
        $this->logger->log('Scanning tables...');
        $tables = $this->connection->getTables($pdo);
        $tableCount = count($tables);
        $this->logger->log("Found {$tableCount} tables");
        
        // Create SQL file header
        $this->logger->log('Exporting database...');
        $sql = "-- Wuplicator Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Host: {$config['DB_HOST']}\n";
        $sql .= "-- Database: {$config['DB_NAME']}\n";
        $sql .= "-- --------------------------------------------------------\n\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET time_zone = \"+00:00\";\n";
        $sql .= "SET NAMES {$config['DB_CHARSET']};\n\n";
        
        // Export each table
        foreach ($tables as $index => $table) {
            $progress = $index + 1;
            $this->logger->log("[{$progress}/{$tableCount}] Exporting: {$table}");
            
            $sql .= $this->exporter->exportStructure($pdo, $table);
            $sql .= $this->exporter->exportData($pdo, $table);
        }
        
        // Write to file
        if (file_put_contents($outputFile, $sql) === false) {
            throw new Exception("Failed to write SQL file: {$outputFile}");
        }
        
        $fileSize = filesize($outputFile);
        $this->logger->log("Database backup created: " . $this->formatBytes($fileSize));
        
        return [
            'file' => $outputFile,
            'size' => $fileSize,
            'tables' => $tableCount,
            'config' => $config
        ];
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
