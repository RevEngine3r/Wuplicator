<?php
/**
 * Wuplicator Installer - Main Orchestrator
 * 
 * Coordinates all installation operations through modular components.
 * 
 * @package Wuplicator\Installer
 * @version 1.2.0
 */

namespace Wuplicator\Installer;

use Wuplicator\Installer\Core\Logger;
use Wuplicator\Installer\Download\Downloader;
use Wuplicator\Installer\Extraction\Extractor;
use Wuplicator\Installer\Database\{Connection, Importer, Migrator};
use Wuplicator\Installer\Configuration\{WpConfigUpdater, SecurityKeys};
use Wuplicator\Installer\Security\AdminManager;
use Wuplicator\Installer\UI\WebInterface;

class Installer {
    
    private $workDir;
    private $logger;
    private $config;
    private $metadata;
    
    public function __construct($config, $metadata) {
        $this->workDir = dirname(__FILE__);
        $this->logger = new Logger();
        $this->config = $config;
        $this->metadata = $metadata;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function run() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $this->handleAction($action);
            exit;
        }
        
        $this->renderUI();
    }
    
    private function handleAction($action) {
        $success = true;
        
        switch ($action) {
            case 'validate':
                $success = $this->validate();
                break;
            case 'download':
                $downloader = new Downloader($this->logger);
                $success = $downloader->download($this->config['backup_url'] ?? '', $this->workDir);
                break;
            case 'extract':
                $extractor = new Extractor($this->logger);
                $success = $extractor->extract($this->workDir);
                break;
            case 'database':
                $success = $this->setupDatabase();
                break;
            case 'configure':
                $success = $this->configure();
                break;
            case 'finalize':
                $success = $this->finalize();
                break;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'errors' => $this->logger->getErrors(),
            'logs' => $this->logger->getLogs()
        ]);
    }
    
    private function validate() {
        $this->logger->log('Validating configuration...');
        
        $required = ['db_name', 'db_user', 'db_password', 'site_url'];
        foreach ($required as $field) {
            if (empty($this->config[$field])) {
                $this->logger->error(ucfirst(str_replace('_', ' ', $field)) . ' is required');
            }
        }
        
        if (!$this->logger->hasErrors()) {
            $this->logger->log('Configuration validated successfully');
        }
        
        return !$this->logger->hasErrors();
    }
    
    private function setupDatabase() {
        $connection = new Connection($this->logger);
        $dbConfig = [
            'host' => $this->config['db_host'] ?? 'localhost',
            'name' => $this->config['db_name'],
            'user' => $this->config['db_user'],
            'password' => $this->config['db_password']
        ];
        
        $pdo = $connection->connect($dbConfig);
        if (!$pdo) {
            return false;
        }
        
        $importer = new Importer($this->logger);
        $sqlFile = $importer->findSQLFile($this->workDir);
        
        if (!$sqlFile) {
            $this->logger->error('SQL backup file not found');
            return false;
        }
        
        return $importer->import($pdo, $sqlFile);
    }
    
    private function configure() {
        // Update wp-config.php
        $wpConfigPath = rtrim($this->workDir, '/') . '/wp-config.php';
        $configUpdater = new WpConfigUpdater($this->logger);
        $dbConfig = [
            'host' => $this->config['db_host'] ?? 'localhost',
            'name' => $this->config['db_name'],
            'user' => $this->config['db_user'],
            'password' => $this->config['db_password']
        ];
        
        if (!$configUpdater->update($wpConfigPath, $dbConfig)) {
            return false;
        }
        
        // Regenerate security keys if requested
        if (!empty($this->config['regenerate_keys'])) {
            $securityKeys = new SecurityKeys($this->logger);
            $securityKeys->regenerate($wpConfigPath);
        }
        
        // Replace URLs
        $connection = new Connection($this->logger);
        $pdo = $connection->connect($dbConfig);
        if ($pdo) {
            $migrator = new Migrator($this->logger);
            $migrator->replaceURLs(
                $pdo,
                $this->metadata['site_url'],
                $this->config['site_url'],
                $this->metadata['table_prefix']
            );
        }
        
        // Update admin credentials
        $adminManager = new AdminManager($this->logger);
        $credentials = $adminManager->update(
            $pdo,
            $this->workDir,
            $this->metadata['table_prefix'],
            $this->config['admin_user'] ?? '',
            $this->config['admin_pass'] ?? '',
            $this->config['randomize_user'] ?? false,
            $this->config['randomize_pass'] ?? false
        );
        
        if ($credentials) {
            $_SESSION['admin_credentials'] = $credentials;
        }
        
        return true;
    }
    
    private function finalize() {
        $this->logger->log('Finalizing installation...');
        
        // Display credentials if generated
        if (isset($_SESSION['admin_credentials'])) {
            $creds = $_SESSION['admin_credentials'];
            $this->logger->log('═══════════════════════════════════════');
            $this->logger->log('⚠️  IMPORTANT: SAVE THESE CREDENTIALS');
            $this->logger->log('═══════════════════════════════════════');
            if (!empty($creds['username'])) {
                $this->logger->log("Admin Username: {$creds['username']}");
            }
            if (!empty($creds['password'])) {
                $this->logger->log("Admin Password: {$creds['password']}");
            }
            $this->logger->log('═══════════════════════════════════════');
        }
        
        // Cleanup
        $zipFile = rtrim($this->workDir, '/') . '/backup.zip';
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
        
        return true;
    }
    
    private function renderUI() {
        $ui = new WebInterface($this->metadata);
        $ui->render();
    }
}
