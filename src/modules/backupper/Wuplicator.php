<?php
/**
 * Wuplicator Main Orchestrator
 * 
 * Coordinates backup creation workflow using modular components.
 */

namespace Wuplicator\Backupper;

use Wuplicator\Backupper\Core\Logger;
use Wuplicator\Backupper\Database\Parser;
use Wuplicator\Backupper\Database\Connection;
use Wuplicator\Backupper\Database\Exporter;
use Wuplicator\Backupper\Database\Backup;
use Wuplicator\Backupper\Files\Scanner;
use Wuplicator\Backupper\Files\Archiver;
use Wuplicator\Backupper\Files\Validator;
use Wuplicator\Backupper\Generator\InstallerGenerator;
use Wuplicator\Backupper\UI\WebInterface;
use Exception;

class Wuplicator {
    
    private $wpRoot;
    private $backupDir;
    private $logger;
    private $errors = [];
    
    public function __construct($wpRoot = null) {
        $this->wpRoot = $wpRoot ?? dirname(__FILE__);
        $this->backupDir = $this->wpRoot . '/wuplicator-backups';
        $this->logger = new Logger();
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Create complete backup package
     * 
     * @return array Package information
     */
    public function createPackage() {
        $startTime = microtime(true);
        
        try {
            // Create database backup
            $parser = new Parser();
            $connection = new Connection();
            $exporter = new Exporter();
            $dbBackup = new Backup($parser, $connection, $exporter, $this->logger);
            
            $sqlFile = $this->backupDir . '/database.sql';
            $dbResult = $dbBackup->create($this->wpRoot, $sqlFile);
            
            // Create files backup
            $scanner = new Scanner();
            $archiver = new Archiver($this->logger);
            $validator = new Validator($this->logger);
            
            $this->logger->log('Starting files backup...');
            $this->logger->log('Scanning WordPress directory...');
            $files = $scanner->scan($this->wpRoot, $this->wpRoot);
            
            $zipFile = $this->backupDir . '/backup.zip';
            $filesResult = $archiver->create($files, $this->wpRoot, $zipFile);
            
            // Validate archive
            if (!$validator->validate($zipFile)) {
                throw new Exception("Archive validation failed");
            }
            
            // Generate installer
            $generator = new InstallerGenerator($this->logger);
            $templatePath = dirname(__FILE__) . '/../installer.php';
            $installerPath = $this->backupDir . '/installer.php';
            
            $metadata = [
                'db_name' => $dbResult['config']['DB_NAME'],
                'table_prefix' => $dbResult['config']['table_prefix'],
                'site_url' => $connection->getSiteURL(
                    $connection->connect($dbResult['config']),
                    $dbResult['config']['table_prefix']
                )
            ];
            
            $generator->generate($templatePath, $installerPath, $metadata);
            
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->logger->log('Backup package complete!');
            $this->logger->log("Total time: {$duration}s");
            $this->logger->log("Package location: {$this->backupDir}/");
            
            return [
                'installer' => $installerPath,
                'backup_zip' => $zipFile,
                'database_sql' => $sqlFile,
                'directory' => $this->backupDir,
                'duration' => $duration
            ];
            
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            throw $e;
        }
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
                } catch (Exception $e) {
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
            $parser = new Parser();
            $config = $parser->parse($this->wpRoot . '/wp-config.php');
            $connection = new Connection();
            
            $siteInfo = [
                'db_name' => $config['DB_NAME'],
                'table_prefix' => $config['table_prefix'],
                'site_url' => 'Unknown'
            ];
            
            try {
                $pdo = $connection->connect($config);
                $siteInfo['site_url'] = $connection->getSiteURL($pdo, $config['table_prefix']);
            } catch (Exception $e) {
                // Site URL remains Unknown
            }
        } catch (Exception $e) {
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
