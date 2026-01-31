<?php
/**
 * Wuplicator Installer Main Orchestrator
 * 
 * Coordinates installation workflow using modular components.
 */

namespace Wuplicator\Installer;

use Wuplicator\Installer\Core\Logger;
use Wuplicator\Installer\Download\Downloader;
use Wuplicator\Installer\Extraction\Extractor;
use Wuplicator\Installer\Database\Connection;
use Wuplicator\Installer\Database\Importer;
use Wuplicator\Installer\Database\Migrator;
use Wuplicator\Installer\Configuration\WpConfigUpdater;
use Wuplicator\Installer\Configuration\SecurityKeys;
use Wuplicator\Installer\Security\AdminManager;
use Wuplicator\Installer\UI\WebInterface;
use Exception;

class Installer {
    
    private $workDir;
    private $logger;
    private $errors = [];
    private $config;
    private $metadata;
    
    public function __construct($config, $metadata) {
        $this->workDir = dirname(__FILE__);
        $this->logger = new Logger();
        $this->config = $config;
        $this->metadata = $metadata;
        session_start();
    }
    
    /**
     * Run installation workflow
     */
    public function run() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAction($_POST['action'] ?? '');
            exit;
        }
        
        $this->renderUI();
    }
    
    /**
     * Handle AJAX action
     */
    private function handleAction($action) {
        try {
            switch ($action) {
                case 'validate':
                    $this->validateConfiguration();
                    break;
                case 'download':
                    $this->downloadBackup();
                    break;
                case 'extract':
                    $this->extractBackup();
                    break;
                case 'database':
                    $this->setupDatabase();
                    break;
                case 'configure':
                    $this->configureWordPress();
                    break;
                case 'finalize':
                    $this->finalizeInstallation();
                    break;
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => empty($this->errors),
            'errors' => $this->errors,
            'logs' => $this->logger->getLogs()
        ]);
    }
    
    private function validateConfiguration() {
        $this->logger->log('Validating configuration...');
        
        if (empty($this->config['NEW_DB_NAME'])) {
            throw new Exception('Database name is required');
        }
        if (empty($this->config['NEW_DB_USER'])) {
            throw new Exception('Database user is required');
        }
        if (empty($this->config['NEW_DB_PASSWORD'])) {
            throw new Exception('Database password is required');
        }
        if (empty($this->config['NEW_SITE_URL'])) {
            throw new Exception('Site URL is required');
        }
        
        $this->logger->log('Configuration validated successfully');
    }
    
    private function downloadBackup() {
        $downloader = new Downloader($this->logger);
        
        if (empty($this->config['BACKUP_URL'])) {
            if ($downloader->hasLocalBackup($this->workDir)) {
                $this->logger->log('Using local backup.zip');
                return;
            }
            throw new Exception('No backup found. Please provide BACKUP_URL or upload backup.zip');
        }
        
        $zipFile = $this->workDir . '/backup.zip';
        $downloader->download($this->config['BACKUP_URL'], $zipFile);
    }
    
    private function extractBackup() {
        $extractor = new Extractor($this->logger);
        $zipFile = $this->workDir . '/backup.zip';
        $extractor->extract($zipFile, $this->workDir);
    }
    
    private function setupDatabase() {
        $this->logger->log('Setting up database...');
        
        $connection = new Connection();
        $importer = new Importer($this->logger);
        
        // Connect without database
        $pdo = $connection->connect(
            $this->config['NEW_DB_HOST'],
            '',
            $this->config['NEW_DB_USER'],
            $this->config['NEW_DB_PASSWORD']
        );
        
        // Create database
        $connection->createDatabase($pdo, $this->config['NEW_DB_NAME']);
        $this->logger->log("Database '{$this->config['NEW_DB_NAME']}' created");
        
        // Use database
        $connection->useDatabase($pdo, $this->config['NEW_DB_NAME']);
        
        // Import SQL
        $sqlFile = $importer->findSQLFile($this->workDir);
        if (!$sqlFile) {
            throw new Exception('SQL backup file not found');
        }
        
        $importer->import($pdo, $sqlFile);
    }
    
    private function configureWordPress() {
        $this->logger->log('Configuring WordPress...');
        
        $wpConfigPath = $this->workDir . '/wp-config.php';
        
        // Update wp-config.php
        $configUpdater = new WpConfigUpdater($this->logger);
        $configUpdater->update($wpConfigPath, [
            'DB_NAME' => $this->config['NEW_DB_NAME'],
            'DB_USER' => $this->config['NEW_DB_USER'],
            'DB_PASSWORD' => $this->config['NEW_DB_PASSWORD'],
            'DB_HOST' => $this->config['NEW_DB_HOST']
        ]);
        
        // Regenerate security keys if enabled
        if ($this->config['REGENERATE_SECURITY_KEYS']) {
            $securityKeys = new SecurityKeys($this->logger);
            $securityKeys->regenerate($wpConfigPath);
        }
        
        // Update URLs
        $connection = new Connection();
        $pdo = $connection->connect(
            $this->config['NEW_DB_HOST'],
            $this->config['NEW_DB_NAME'],
            $this->config['NEW_DB_USER'],
            $this->config['NEW_DB_PASSWORD']
        );
        
        $migrator = new Migrator($this->logger);
        $migrator->replaceURLs(
            $pdo,
            $this->metadata['site_url'],
            $this->config['NEW_SITE_URL'],
            $this->metadata['table_prefix']
        );
        
        // Update admin credentials
        if (!empty($this->config['NEW_ADMIN_USER']) || !empty($this->config['NEW_ADMIN_PASS']) ||
            $this->config['RANDOMIZE_ADMIN_USER'] || $this->config['RANDOMIZE_ADMIN_PASS']) {
            
            $adminManager = new AdminManager($this->logger);
            $credentials = $adminManager->update(
                $pdo,
                $this->metadata['table_prefix'],
                $this->config['NEW_ADMIN_USER'],
                $this->config['NEW_ADMIN_PASS'],
                $this->config['RANDOMIZE_ADMIN_USER'],
                $this->config['RANDOMIZE_ADMIN_PASS'],
                $this->workDir
            );
            
            // Store for final display
            if (!empty($credentials)) {
                $_SESSION['final_credentials'] = $credentials;
            }
        }
    }
    
    private function finalizeInstallation() {
        $this->logger->log('Finalizing installation...');
        
        // Display generated credentials
        if (isset($_SESSION['final_credentials'])) {
            $creds = $_SESSION['final_credentials'];
            $this->logger->log('═══════════════════════════════════════');
            $this->logger->log('⚠️  IMPORTANT: SAVE THESE CREDENTIALS');
            $this->logger->log('═══════════════════════════════════════');
            
            if (isset($creds['username'])) {
                $this->logger->log("Admin Username: {$creds['username']}");
            }
            if (isset($creds['password'])) {
                $this->logger->log("Admin Password: {$creds['password']}");
            }
            
            $this->logger->log('═══════════════════════════════════════');
        }
        
        // Cleanup
        $zipFile = $this->workDir . '/backup.zip';
        if (file_exists($zipFile)) {
            unlink($zipFile);
            $this->logger->log('Backup archive deleted');
        }
        
        $importer = new Importer($this->logger);
        $sqlFile = $importer->findSQLFile($this->workDir);
        if ($sqlFile && file_exists($sqlFile)) {
            unlink($sqlFile);
            $this->logger->log('SQL file deleted');
        }
        
        $this->logger->log('Installation complete!');
        $this->logger->log('IMPORTANT: Delete installer.php manually for security');
    }
    
    /**
     * Render installation UI
     */
    private function renderUI() {
        $ui = new WebInterface($this->metadata);
        $ui->render();
    }
}
