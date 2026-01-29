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
    private $defaultExcludes = [
        'wuplicator-backups',
        'wp-content/cache',
        'wp-content/backup',
        'wp-content/backups',
        'wp-content/uploads/backup',
        '.git',
        '.svn',
        'node_modules',
        '.DS_Store',
        'error_log',
        'debug.log'
    ];
    
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
        
        // Get table prefix
        if (preg_match("/\$table_prefix\s*=\s*'([^']+)'/", $content, $matches)) {
            $config['table_prefix'] = $matches[1];
        } else {
            $config['table_prefix'] = 'wp_';
        }
        
        return $config;
    }
    
    /**
     * Get site URL from database
     * 
     * @param PDO $pdo Database connection
     * @param string $tablePrefix Table prefix
     * @return string Site URL
     */
    private function getSiteURL($pdo, $tablePrefix) {
        try {
            $stmt = $pdo->query("SELECT option_value FROM {$tablePrefix}options WHERE option_name = 'siteurl' LIMIT 1");
            $url = $stmt->fetchColumn();
            return $url ?: '';
        } catch (Exception $e) {
            return '';
        }
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
        $sqlFile = $this->backupDir . "/database.sql";
        
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
        echo "\n[SUCCESS] Database backup created\n";
        echo "File size: {$fileSize}\n";
        
        return $sqlFile;
    }
    
    /**
     * Scan directory recursively
     * 
     * @param string $path Directory path
     * @param array $excludes Exclusion patterns
     * @return array File paths relative to WordPress root
     */
    public function scanDirectory($path, $excludes = []) {
        $files = [];
        $excludes = array_merge($this->defaultExcludes, $excludes);
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            // Skip symbolic links
            if ($item->isLink()) {
                continue;
            }
            
            $filePath = $item->getPathname();
            $relativePath = str_replace($this->wpRoot . '/', '', $filePath);
            
            // Check exclusions
            $excluded = false;
            foreach ($excludes as $pattern) {
                // Pattern matching: exact match or wildcard
                if (strpos($relativePath, $pattern) !== false) {
                    $excluded = true;
                    break;
                }
                
                // Wildcard pattern (*.log, *.tmp)
                if (strpos($pattern, '*') !== false) {
                    $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
                    if (preg_match($regex, basename($relativePath))) {
                        $excluded = true;
                        break;
                    }
                }
            }
            
            if ($excluded) {
                continue;
            }
            
            // Add files only (directories are created implicitly in ZIP)
            if ($item->isFile()) {
                $files[] = $relativePath;
            }
        }
        
        return $files;
    }
    
    /**
     * Create ZIP archive of WordPress files
     * 
     * @param array $customExcludes Custom exclusion patterns
     * @return string Path to ZIP archive
     * @throws Exception If archive creation fails
     */
    public function createFilesBackup($customExcludes = []) {
        echo "[Wuplicator] Starting files backup...\n";
        
        // Check ZipArchive extension
        if (!class_exists('ZipArchive')) {
            throw new Exception("ZipArchive extension not available. Install php-zip.");
        }
        
        // Scan files
        echo "[1/3] Scanning WordPress directory...\n";
        $files = $this->scanDirectory($this->wpRoot, $customExcludes);
        $fileCount = count($files);
        echo "  Found {$fileCount} files\n";
        
        if ($fileCount === 0) {
            throw new Exception("No files found to backup");
        }
        
        // Create ZIP archive
        echo "[2/3] Creating ZIP archive...\n";
        $zipFile = $this->backupDir . "/backup.zip";
        
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Failed to create ZIP archive: {$zipFile}");
        }
        
        // Add files to archive
        $processed = 0;
        $lastProgress = 0;
        
        foreach ($files as $file) {
            $fullPath = $this->wpRoot . '/' . $file;
            
            // Skip if file no longer exists or is not readable
            if (!file_exists($fullPath) || !is_readable($fullPath)) {
                continue;
            }
            
            $zip->addFile($fullPath, $file);
            $processed++;
            
            // Progress feedback every 10%
            $progress = floor(($processed / $fileCount) * 100);
            if ($progress >= $lastProgress + 10) {
                echo "  Progress: {$progress}% ({$processed}/{$fileCount} files)\n";
                $lastProgress = $progress;
            }
        }
        
        $zip->close();
        
        // Validate archive
        echo "[3/3] Validating archive...\n";
        if (!$this->validateArchive($zipFile)) {
            throw new Exception("Archive validation failed");
        }
        
        $fileSize = $this->formatBytes(filesize($zipFile));
        echo "\n[SUCCESS] Files backup created\n";
        echo "Files archived: {$processed}\n";
        echo "Archive size: {$fileSize}\n";
        
        return $zipFile;
    }
    
    /**
     * Validate ZIP archive integrity
     * 
     * @param string $zipPath Path to ZIP file
     * @return bool True if valid
     */
    public function validateArchive($zipPath) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CHECKCONS) !== true) {
            return false;
        }
        
        $numFiles = $zip->numFiles;
        $zip->close();
        
        echo "  Archive contains {$numFiles} files\n";
        echo "  Integrity check: PASSED\n";
        
        return $numFiles > 0;
    }
    
    /**
     * Generate installer.php with embedded metadata
     * 
     * @return string Path to installer file
     */
    public function generateInstaller() {
        echo "\n[Wuplicator] Generating installer...\n";
        
        // Get site metadata
        $config = $this->parseWpConfig();
        $pdo = $this->connectDatabase($config);
        $siteUrl = $this->getSiteURL($pdo, $config['table_prefix']);
        
        // Read installer template
        $templatePath = dirname(__FILE__) . '/installer.php';
        if (!file_exists($templatePath)) {
            throw new Exception("Installer template not found");
        }
        
        $installer = file_get_contents($templatePath);
        
        // Generate security token
        $token = bin2hex(random_bytes(32));
        
        // Embed metadata
        $installer = str_replace('WUPLICATOR_TOKEN_PLACEHOLDER', $token, $installer);
        $installer = str_replace('TIMESTAMP_PLACEHOLDER', date('Y-m-d H:i:s'), $installer);
        $installer = str_replace('DB_NAME_PLACEHOLDER', $config['DB_NAME'], $installer);
        $installer = str_replace('TABLE_PREFIX_PLACEHOLDER', $config['table_prefix'], $installer);
        $installer = str_replace('SITE_URL_PLACEHOLDER', $siteUrl, $installer);
        
        // Save installer
        $installerPath = $this->backupDir . '/installer.php';
        if (file_put_contents($installerPath, $installer) === false) {
            throw new Exception("Failed to write installer");
        }
        
        echo "  Installer generated with security token\n";
        echo "  Original site: {$siteUrl}\n";
        echo "  Table prefix: {$config['table_prefix']}\n";
        
        return $installerPath;
    }
    
    /**
     * Create complete backup package
     * 
     * @return array Package information
     */
    public function createPackage() {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "  WUPLICATOR - Complete Backup Package Creator\n";
        echo str_repeat('=', 50) . "\n\n";
        
        $startTime = microtime(true);
        
        // Create database backup
        $sqlFile = $this->createDatabaseBackup();
        
        // Create files backup
        echo "\n";
        $zipFile = $this->createFilesBackup();
        
        // Generate installer
        $installerFile = $this->generateInstaller();
        
        // Copy database.sql to backup directory (already there)
        
        $duration = round(microtime(true) - $startTime, 2);
        
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "  BACKUP PACKAGE COMPLETE\n";
        echo str_repeat('=', 50) . "\n";
        echo "\nPackage location: {$this->backupDir}/\n";
        echo "\nFiles created:\n";
        echo "  1. installer.php - Deployment script\n";
        echo "  2. backup.zip    - WordPress files\n";
        echo "  3. database.sql  - Database dump\n";
        echo "\nTotal time: {$duration}s\n";
        echo "\nDEPLOYMENT INSTRUCTIONS:\n";
        echo "1. Upload all 3 files to your new host\n";
        echo "2. Edit installer.php configuration (database, URLs, admin)\n";
        echo "3. Visit installer.php in browser\n";
        echo "4. Follow the installation wizard\n";
        echo "5. Delete installer.php after completion\n";
        echo "\n";
        
        return [
            'installer' => $installerFile,
            'backup_zip' => $zipFile,
            'database_sql' => $sqlFile,
            'directory' => $this->backupDir,
            'duration' => $duration
        ];
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
    try {
        $wuplicator = new Wuplicator();
        $package = $wuplicator->createPackage();
        echo "\u2713 Backup package created successfully\n\n";
        exit(0);
    } catch (Exception $e) {
        echo "\n\u2717 ERROR: " . $e->getMessage() . "\n\n";
        exit(1);
    }
}
