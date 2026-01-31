<?php
/**
 * Wuplicator - WordPress Backup Creator
 * 
 * Creates complete WordPress site backups including database and files.
 * Generates installer.php for deployment to new hosts.
 * 
 * @version 1.1.0
 * @author RevEngine3r
 */

class Wuplicator {
    
    private $wpRoot;
    private $backupDir;
    private $errors = [];
    private $logs = [];
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
        $this->log('Starting database backup...');
        
        // Parse wp-config.php
        $this->log('Parsing wp-config.php...');
        $config = $this->parseWpConfig();
        $this->log("Database: {$config['DB_NAME']}");
        
        // Connect to database
        $this->log('Connecting to database...');
        $pdo = $this->connectDatabase($config);
        $this->log('Connected successfully');
        
        // Get tables
        $this->log('Scanning tables...');
        $tables = $this->getDatabaseTables($pdo, $config['DB_NAME']);
        $tableCount = count($tables);
        $this->log("Found {$tableCount} tables");
        
        // Create SQL file
        $this->log('Exporting database...');
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
            $this->log("[{$progress}/{$tableCount}] Exporting: {$table}");
            
            $sql .= $this->exportTableStructure($pdo, $table);
            $sql .= $this->exportTableData($pdo, $table);
        }
        
        // Write to file
        if (file_put_contents($sqlFile, $sql) === false) {
            throw new Exception("Failed to write SQL file: {$sqlFile}");
        }
        
        $fileSize = $this->formatBytes(filesize($sqlFile));
        $this->log("Database backup created: {$fileSize}");
        
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
        $this->log('Starting files backup...');
        
        // Check ZipArchive extension
        if (!class_exists('ZipArchive')) {
            throw new Exception("ZipArchive extension not available. Install php-zip.");
        }
        
        // Scan files
        $this->log('Scanning WordPress directory...');
        $files = $this->scanDirectory($this->wpRoot, $customExcludes);
        $fileCount = count($files);
        $this->log("Found {$fileCount} files");
        
        if ($fileCount === 0) {
            throw new Exception("No files found to backup");
        }
        
        // Create ZIP archive
        $this->log('Creating ZIP archive...');
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
                $this->log("Progress: {$progress}% ({$processed}/{$fileCount} files)");
                $lastProgress = $progress;
            }
        }
        
        $zip->close();
        
        // Validate archive
        $this->log('Validating archive...');
        if (!$this->validateArchive($zipFile)) {
            throw new Exception("Archive validation failed");
        }
        
        $fileSize = $this->formatBytes(filesize($zipFile));
        $this->log("Files backup created: {$fileSize}");
        $this->log("Files archived: {$processed}");
        
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
        
        $this->log("Archive contains {$numFiles} files");
        $this->log("Integrity check: PASSED");
        
        return $numFiles > 0;
    }
    
    /**
     * Generate installer.php with embedded metadata
     * 
     * @return string Path to installer file
     */
    public function generateInstaller() {
        $this->log('Generating installer...');
        
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
        
        $this->log('Installer generated with security token');
        $this->log("Original site: {$siteUrl}");
        $this->log("Table prefix: {$config['table_prefix']}");
        
        return $installerPath;
    }
    
    /**
     * Create complete backup package
     * 
     * @return array Package information
     */
    public function createPackage() {
        $startTime = microtime(true);
        
        // Create database backup
        $sqlFile = $this->createDatabaseBackup();
        
        // Create files backup
        $zipFile = $this->createFilesBackup();
        
        // Generate installer
        $installerFile = $this->generateInstaller();
        
        $duration = round(microtime(true) - $startTime, 2);
        
        $this->log('Backup package complete!');
        $this->log("Total time: {$duration}s");
        $this->log("Package location: {$this->backupDir}/");
        
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
    
    /**
     * Log message
     * 
     * @param string $message
     */
    private function log($message) {
        $this->logs[] = $message;
    }
    
    /**
     * Log error
     * 
     * @param string $message
     */
    private function error($message) {
        $this->errors[] = $message;
    }
    
    /**
     * Run web UI
     */
    public function run() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'create_backup') {
                try {
                    $package = $this->createPackage();
                    
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'logs' => $this->logs,
                        'package' => $package
                    ]);
                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'errors' => [$e->getMessage()],
                        'logs' => $this->logs
                    ]);
                }
                exit;
            }
        }
        
        $this->renderUI();
    }
    
    /**
     * Render web UI
     */
    private function renderUI() {
        // Get site info
        try {
            $config = $this->parseWpConfig();
            $siteInfo = [
                'db_name' => $config['DB_NAME'],
                'table_prefix' => $config['table_prefix']
            ];
            
            try {
                $pdo = $this->connectDatabase($config);
                $siteInfo['site_url'] = $this->getSiteURL($pdo, $config['table_prefix']);
            } catch (Exception $e) {
                $siteInfo['site_url'] = 'Unknown';
            }
        } catch (Exception $e) {
            $siteInfo = [
                'db_name' => 'Unknown',
                'table_prefix' => 'Unknown',
                'site_url' => 'Unknown'
            ];
        }
        ?>
<!DOCTYPE html>
<html>
<head>
    <title>Wuplicator - Create Backup</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; }
        h1 { font-size: 28px; margin-bottom: 10px; }
        .subtitle { opacity: 0.9; }
        .content { padding: 30px; }
        .step { display: none; }
        .step.active { display: block; }
        .progress { background: #e0e0e0; height: 8px; border-radius: 4px; margin-bottom: 30px; overflow: hidden; }
        .progress-bar { background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); height: 100%; width: 0%; transition: width 0.3s; }
        button { background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-size: 16px; cursor: pointer; }
        button:hover { background: #5568d3; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        .log { background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 13px; }
        .log-item { margin-bottom: 5px; }
        .error { color: #d32f2f; font-weight: bold; }
        .success { color: #388e3c; font-weight: bold; }
        .info { margin-bottom: 20px; padding: 15px; background: #e3f2fd; border-left: 4px solid #2196f3; }
        .warning { margin-top: 20px; padding: 15px; background: #fff3e0; border-left: 4px solid #ff9800; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💾 Wuplicator Backup Creator</h1>
            <div class="subtitle">WordPress Complete Backup Tool</div>
        </div>
        <div class="content">
            <div class="progress"><div class="progress-bar" id="progressBar"></div></div>
            
            <div class="step active" id="step1">
                <h2>Ready to Create Backup</h2>
                <div class="info">
                    <strong>Current Site Info:</strong><br>
                    Database: <?php echo htmlspecialchars($siteInfo['db_name']); ?><br>
                    Table Prefix: <?php echo htmlspecialchars($siteInfo['table_prefix']); ?><br>
                    Site URL: <?php echo htmlspecialchars($siteInfo['site_url']); ?>
                </div>
                <p>This will create a complete backup package containing:</p>
                <ul style="margin: 15px 0 15px 30px;">
                    <li>Database dump (SQL file)</li>
                    <li>All WordPress files (ZIP archive)</li>
                    <li>Deployment installer</li>
                </ul>
                <div class="warning">
                    <strong>⚠️ Important:</strong> Large sites may take several minutes. Do not close this page during backup creation.
                </div>
                <br>
                <button onclick="startBackup()" id="startButton">Create Backup Package</button>
            </div>
            
            <div class="step" id="step2">
                <h2>Creating Backup...</h2>
                <div class="log" id="logOutput"></div>
                <br>
                <div id="completionMessage"></div>
            </div>
        </div>
    </div>
    
    <script>
        function updateProgress(percent) {
            document.getElementById('progressBar').style.width = percent + '%';
        }
        
        function log(message, type = 'info') {
            const logOutput = document.getElementById('logOutput');
            const item = document.createElement('div');
            item.className = 'log-item ' + type;
            item.textContent = '• ' + message;
            logOutput.appendChild(item);
            logOutput.scrollTop = logOutput.scrollHeight;
        }
        
        function showStep(step) {
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
        }
        
        async function startBackup() {
            document.getElementById('startButton').disabled = true;
            showStep(2);
            updateProgress(10);
            
            log('Initializing backup process...', 'info');
            
            const formData = new FormData();
            formData.append('action', 'create_backup');
            
            try {
                updateProgress(20);
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();
                
                updateProgress(100);
                
                if (result.logs) {
                    result.logs.forEach(msg => log(msg));
                }
                
                if (result.success) {
                    const pkg = result.package;
                    document.getElementById('completionMessage').innerHTML = 
                        '<div class="success" style="padding: 20px; background: #e8f5e9; border-radius: 4px;">' +
                        '<h3>✓ Backup Package Created Successfully!</h3>' +
                        '<p style="margin-top: 10px;"><strong>Package Location:</strong><br>' + pkg.directory + '</p>' +
                        '<p style="margin-top: 10px;"><strong>Files Created:</strong></p>' +
                        '<ul style="margin-left: 20px;">' +
                        '<li>installer.php - Deployment script</li>' +
                        '<li>backup.zip - WordPress files</li>' +
                        '<li>database.sql - Database dump</li>' +
                        '</ul>' +
                        '<p style="margin-top: 10px;"><strong>Total Time:</strong> ' + pkg.duration + 's</p>' +
                        '<div style="margin-top: 20px; padding: 15px; background: #fff3e0; border-left: 4px solid #ff9800;">' +
                        '<strong>Next Steps:</strong><br>' +
                        '1. Download the backup package from: <code>' + pkg.directory + '</code><br>' +
                        '2. Upload to new host<br>' +
                        '3. Edit installer.php configuration<br>' +
                        '4. Visit installer.php in browser<br>' +
                        '5. Delete installer.php after deployment' +
                        '</div>' +
                        '</div>';
                } else {
                    if (result.errors) {
                        result.errors.forEach(msg => log(msg, 'error'));
                    }
                    document.getElementById('completionMessage').innerHTML = 
                        '<div class="error" style="padding: 20px; background: #ffebee; border-radius: 4px;">' +
                        '<h3>✗ Backup Failed</h3>' +
                        '<p>Check the log above for error details.</p>' +
                        '</div>';
                }
            } catch (error) {
                updateProgress(0);
                log('Network error: ' + error.message, 'error');
                document.getElementById('completionMessage').innerHTML = 
                    '<div class="error" style="padding: 20px; background: #ffebee; border-radius: 4px;">' +
                    '<h3>✗ Connection Error</h3>' +
                    '<p>' + error.message + '</p>' +
                    '</div>';
            }
        }
    </script>
</body>
</html>
        <?php
    }
}

// Web UI execution
$wuplicator = new Wuplicator();
$wuplicator->run();
