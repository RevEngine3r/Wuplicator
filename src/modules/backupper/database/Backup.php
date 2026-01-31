<?php
/**
 * Wuplicator Backupper - Database Backup Orchestrator
 * 
 * Orchestrates the complete database backup process.
 * 
 * @package Wuplicator\Backupper\Database
 * @version 1.2.0
 */

namespace Wuplicator\Backupper\Database;

use Wuplicator\Backupper\Core\Logger;
use Wuplicator\Backupper\Core\Utils;

class Backup {
    
    private $parser;
    private $connection;
    private $exporter;
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->parser = new Parser();
        $this->connection = new Connection();
        $this->exporter = new Exporter();
        $this->logger = $logger;
    }
    
    /**
     * Create complete database backup
     * 
     * @param string $wpRoot WordPress root directory
     * @param string $outputFile Output SQL file path
     * @return array Backup metadata
     * @throws \Exception If backup fails
     */
    public function create($wpRoot, $outputFile) {
        $this->logger->log('Starting database backup...');
        
        // Parse wp-config.php
        $this->logger->log('Parsing wp-config.php...');
        $wpConfigPath = rtrim($wpRoot, '/') . '/wp-config.php';
        $config = $this->parser->parse($wpConfigPath);
        $this->logger->log("Database: {$config['DB_NAME']}");
        
        // Connect to database
        $this->logger->log('Connecting to database...');
        $pdo = $this->connection->connect($config);
        $this->logger->log('Connected successfully');
        
        // Get tables
        $this->logger->log('Scanning tables...');
        $tables = $this->connection->getTables($pdo, $config['DB_NAME']);
        $tableCount = count($tables);
        $this->logger->log("Found {$tableCount} tables");
        
        // Generate SQL header
        $sql = $this->generateHeader($config);
        
        // Export each table
        $this->logger->log('Exporting tables...');
        foreach ($tables as $index => $table) {
            $progress = $index + 1;
            $this->logger->log("[{$progress}/{$tableCount}] Exporting: {$table}");
            
            $sql .= $this->exporter->exportStructure($pdo, $table);
            $sql .= $this->exporter->exportData($pdo, $table);
        }
        
        // Write to file
        if (file_put_contents($outputFile, $sql) === false) {
            throw new \Exception("Failed to write SQL file: {$outputFile}");
        }
        
        $fileSize = Utils::formatBytes(filesize($outputFile));
        $this->logger->log("Database backup created: {$fileSize}");
        
        return [
            'config' => $config,
            'tables' => $tableCount,
            'file' => $outputFile,
            'size' => filesize($outputFile)
        ];
    }
    
    /**
     * Generate SQL file header
     * 
     * @param array $config Database configuration
     * @return string SQL header
     */
    private function generateHeader($config) {
        $sql = "-- Wuplicator Database Backup\n";
        $sql .= "-- Generated: " . Utils::timestamp() . "\n";
        $sql .= "-- Host: {$config['DB_HOST']}\n";
        $sql .= "-- Database: {$config['DB_NAME']}\n";
        $sql .= "-- --------------------------------------------------------\n\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET time_zone = \"+00:00\";\n";
        $sql .= "SET NAMES {$config['DB_CHARSET']};\n\n";
        
        return $sql;
    }
}
