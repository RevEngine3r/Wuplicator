<?php
/**
 * Wuplicator - WordPress Backup Creator
 * 
 * Creates complete WordPress site backups including database and files.
 * Generates installer.php for deployment to new hosts.
 * 
 * @version 1.0.0
 * @author RevEngine3r
 */

class Wuplicator {
    
    private $wpRoot;
    private $backupDir;
    private $errors = [];
    
    /**
     * Initialize Wuplicator
     * 
     * @param string $wpRoot WordPress root directory path
     */
    public function __construct($wpRoot = null) {
        $this->wpRoot = $wpRoot ?? dirname(__FILE__);
        $this->backupDir = $this->wpRoot . '/wuplicator-backups';
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Parse WordPress wp-config.php file
     * 
     * @param string $wpConfigPath Path to wp-config.php
     * @return array Database configuration
     * @throws Exception If file not found or parse fails
     */
    public function parseWpConfig($wpConfigPath = null) {
        $wpConfigPath = $wpConfigPath ?? $this->wpRoot . '/wp-config.php';
        
        if (!file_exists($wpConfigPath)) {
            throw new Exception("wp-config.php not found at: {$wpConfigPath}");
        }
        
        $content = file_get_contents($wpConfigPath);
        if ($content === false) {
            throw new Exception("Failed to read wp-config.php");
        }
        
        $config = [];
        $constants = ['DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST', 'DB_CHARSET', 'DB_COLLATE'];
        
        foreach ($constants as $const) {
            // Match define('CONST', 'value') or define("CONST", "value")
            $pattern = "/define\s*\(\s*['\"]" . $const . "['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/";
            if (preg_match($pattern, $content, $matches)) {
                $config[$const] = $matches[1];
            }
        }
        
        // Validate required fields
        $required = ['DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST'];
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new Exception("Missing required database config: {$field}");
            }
        }
        
        // Set defaults
        $config['DB_CHARSET'] = $config['DB_CHARSET'] ?? 'utf8mb4';
        $config['DB_COLLATE'] = $config['DB_COLLATE'] ?? '';
        
        return $config;
    }
    
    /**
     * Connect to MySQL database
     * 
     * @param array $config Database configuration
     * @return PDO Database connection
     * @throws Exception If connection fails
     */
    public function connectDatabase($config) {
        try {
            $dsn = "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};charset={$config['DB_CHARSET']}";
            $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get all tables in database
     * 
     * @param PDO $pdo Database connection
     * @param string $dbName Database name
     * @return array Table names
     */
    public function getDatabaseTables($pdo, $dbName) {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        return $tables;
    }
    
    /**
     * Export table structure (CREATE TABLE statement)
     * 
     * @param PDO $pdo Database connection
     * @param string $tableName Table name
     * @return string SQL CREATE TABLE statement
     */
    public function exportTableStructure($pdo, $tableName) {
        $sql = "--\n-- Table: {$tableName}\n--\n\n";
        $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
        
        $stmt = $pdo->query("SHOW CREATE TABLE `{$tableName}`");
        $row = $stmt->fetch();
        $sql .= $row['Create Table'] . ";\n\n";
        
        return $sql;
    }
    
    /**
     * Export table data (INSERT statements)
     * 
     * @param PDO $pdo Database connection
     * @param string $tableName Table name
     * @param int $chunkSize Rows per INSERT statement
     * @return string SQL INSERT statements
     */
    public function exportTableData($pdo, $tableName, $chunkSize = 1000) {
        $sql = "";
        
        // Get total rows
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$tableName}`");
        $totalRows = $stmt->fetch()['count'];
        
        if ($totalRows == 0) {
            return "-- No data for table {$tableName}\n\n";
        }
        
        // Get column names
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$tableName}`");
        $columns = [];
        while ($col = $stmt->fetch()) {
            $columns[] = $col['Field'];
        }
        $columnList = '`' . implode('`, `', $columns) . '`';
        
        // Export data in chunks
        $offset = 0;
        while ($offset < $totalRows) {
            $stmt = $pdo->query("SELECT * FROM `{$tableName}` LIMIT {$chunkSize} OFFSET {$offset}");
            $rows = $stmt->fetchAll();
            
            if (!empty($rows)) {
                $sql .= "INSERT INTO `{$tableName}` ({$columnList}) VALUES\n";
                $values = [];
                
                foreach ($rows as $row) {
                    $rowValues = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $rowValues[] = 'NULL';
                        } else {
                            $escaped = $pdo->quote($value);
                            $rowValues[] = $escaped;
                        }
                    }
                    $values[] = '(' . implode(', ', $rowValues) . ')';
                }
                
                $sql .= implode(",\n", $values) . ";\n\n";
            }
            
            $offset += $chunkSize;
        }
        
        return $sql;
    }
    
    /**
     * Create complete database backup
     * 
     * @return string Path to backup SQL file
     * @throws Exception If backup fails
     */
    public function createDatabaseBackup() {
        echo "[Wuplicator] Starting database backup...\n";
        
        // Parse wp-config.php
        echo "[1/4] Parsing wp-config.php...\n";
        $config = $this->parseWpConfig();
        echo "  Database: {$config['DB_NAME']}\n";
        
        // Connect to database
        echo "[2/4] Connecting to database...\n";
        $pdo = $this->connectDatabase($config);
        echo "  Connected successfully\n";
        
        // Get tables
        echo "[3/4] Scanning tables...\n";
        $tables = $this->getDatabaseTables($pdo, $config['DB_NAME']);
        $tableCount = count($tables);
        echo "  Found {$tableCount} tables\n";
        
        // Create SQL file
        echo "[4/4] Exporting database...\n";
        $timestamp = date('Y-m-d_H-i-s');
        $sqlFile = $this->backupDir . "/database-{$timestamp}.sql";
        
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
            echo "  [{$progress}/{$tableCount}] Exporting: {$table}\n";
            
            $sql .= $this->exportTableStructure($pdo, $table);
            $sql .= $this->exportTableData($pdo, $table);
        }
        
        // Write to file
        if (file_put_contents($sqlFile, $sql) === false) {
            throw new Exception("Failed to write SQL file: {$sqlFile}");
        }
        
        $fileSize = $this->formatBytes(filesize($sqlFile));
        echo "\n[SUCCESS] Database backup created: {$sqlFile}\n";
        echo "File size: {$fileSize}\n";
        
        return $sqlFile;
    }
    
    /**
     * Format bytes to human-readable size
     * 
     * @param int $bytes Bytes
     * @return string Formatted size
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    echo "===================================\n";
    echo "  Wuplicator - Database Backup\n";
    echo "===================================\n\n";
    
    try {
        $wuplicator = new Wuplicator();
        $sqlFile = $wuplicator->createDatabaseBackup();
        echo "\n✓ Backup completed successfully\n";
        exit(0);
    } catch (Exception $e) {
        echo "\n✗ ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}
