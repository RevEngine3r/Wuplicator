<?php
/**
 * Wuplicator Backupper - Main Orchestrator
 * 
 * Coordinates all backup operations through modular components.
 * 
 * @package Wuplicator\Backupper
 * @version 1.2.0
 */

namespace Wuplicator\Backupper;

use Wuplicator\Backupper\Core\{Config, Logger, Utils};
use Wuplicator\Backupper\Database\{Backup as DatabaseBackup, Connection};
use Wuplicator\Backupper\Files\{Scanner, Archiver, Validator};
use Wuplicator\Backupper\Generator\InstallerGenerator;
use Wuplicator\Backupper\UI\WebInterface;

class Wuplicator {
    
    private $wpRoot;
    private $backupDir;
    private $logger;
    
    public function __construct($wpRoot = null) {
        $this->wpRoot = $wpRoot ?? dirname(__FILE__);
        $this->backupDir = Config::getBackupDir($this->wpRoot);
        $this->logger = new Logger();
        
        Utils::ensureDirectory($this->backupDir);
    }
    
    /**
     * Create complete backup package
     * 
     * @return array Package information
     */
    public function createPackage() {
        $startTime = microtime(true);
        
        // Check requirements
        $missing = Config::checkRequirements();
        if (!empty($missing)) {
            throw new \Exception("Missing PHP extensions: " . implode(', ', $missing));
        }
        
        // Create database backup
        $sqlFile = $this->backupDir . '/database.sql';
        $dbBackup = new DatabaseBackup($this->logger);
        $dbMeta = $dbBackup->create($this->wpRoot, $sqlFile);
        
        // Create files backup
        $zipFile = $this->backupDir . '/backup.zip';
        $scanner = new Scanner();
        $files = $scanner->scan($this->wpRoot, $this->wpRoot);
        
        $this->logger->log("Found " . count($files) . " files to archive");
        
        $archiver = new Archiver($this->logger);
        $archiveMeta = $archiver->create($files, $this->wpRoot, $zipFile);
        
        // Validate archive
        $validator = new Validator($this->logger);
        if (!$validator->validate($zipFile)) {
            throw new \Exception("Archive validation failed");
        }
        
        $this->logger->log("Files backup created: " . Utils::formatBytes($archiveMeta['size']));
        
        // Generate installer
        $installerFile = $this->backupDir . '/installer.php';
        $connection = new Connection();
        $pdo = $connection->connect($dbMeta['config']);
        $siteUrl = $connection->getSiteURL($pdo, $dbMeta['config']['table_prefix']);
        
        $metadata = [
            'db_name' => $dbMeta['config']['DB_NAME'],
            'table_prefix' => $dbMeta['config']['table_prefix'],
            'site_url' => $siteUrl
        ];
        
        $generator = new InstallerGenerator($this->logger);
        $generator->generate($this->wpRoot, $metadata, $installerFile);
        
        $duration = round(microtime(true) - $startTime, 2);
        
        $this->logger->log('Backup package complete!');
        $this->logger->log("Total time: {$duration}s");
        $this->logger->log("Package location: {$this->backupDir}/");
        
        return [
            'installer' => $installerFile,
            'backup_zip' => $zipFile,
            'database_sql' => $sqlFile,
            'directory' => $this->backupDir,
            'duration' => $duration
        ];
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
                        'logs' => $this->logger->getLogs(),
                        'package' => $package
                    ]);
                } catch (\Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'errors' => [$e->getMessage()],
                        'logs' => $this->logger->getLogs()
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
        try {
            $dbBackup = new DatabaseBackup($this->logger);
            $wpConfigPath = rtrim($this->wpRoot, '/') . '/wp-config.php';
            $parser = new \Wuplicator\Backupper\Database\Parser();
            $config = $parser->parse($wpConfigPath);
            
            $connection = new Connection();
            $pdo = $connection->connect($config);
            $siteUrl = $connection->getSiteURL($pdo, $config['table_prefix']);
            
            $siteInfo = [
                'db_name' => $config['DB_NAME'],
                'table_prefix' => $config['table_prefix'],
                'site_url' => $siteUrl
            ];
        } catch (\Exception $e) {
            $siteInfo = [
                'db_name' => 'Unknown',
                'table_prefix' => 'Unknown',
                'site_url' => 'Unknown'
            ];
        }
        
        $ui = new WebInterface($siteInfo);
        $ui->render();
    }
}
