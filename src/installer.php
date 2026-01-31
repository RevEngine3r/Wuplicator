<?php
/**
 * Wuplicator Installer
 * 
 * Deploys WordPress backup to new host with customization options.
 * Supports remote URL downloads and admin credential changes.
 * 
 * @version 1.1.0
 * @author RevEngine3r
 */

// ============================================
// CONFIGURATION - Edit these values
// ============================================

// Remote backup URL (leave empty to use local backup.zip)
$BACKUP_URL = '';
// Example: $BACKUP_URL = 'https://example.com/backups/site-backup.zip';

// New Database Configuration
$NEW_DB_HOST = 'localhost';
$NEW_DB_NAME = '';
$NEW_DB_USER = '';
$NEW_DB_PASSWORD = '';

// New Site Configuration
$NEW_SITE_URL = ''; // e.g., 'https://newsite.com'

// New Admin Credentials (optional - leave empty to keep existing)
$NEW_ADMIN_USER = ''; // e.g., 'admin_ls45g'
$NEW_ADMIN_PASS = ''; // e.g., 'slkjdfhnb874'

// Security Enhancements (NEW)
$RANDOMIZE_ADMIN_USER = false; // Set true to generate random username (admin_[5 chars])
$RANDOMIZE_ADMIN_PASS = false; // Set true to generate random password (12 chars)

// Security token (auto-generated, do not modify)
$SECURITY_TOKEN = 'WUPLICATOR_TOKEN_PLACEHOLDER';

// Embedded metadata (auto-generated)
$BACKUP_METADATA = array(
    'created' => 'TIMESTAMP_PLACEHOLDER',
    'db_name' => 'DB_NAME_PLACEHOLDER',
    'table_prefix' => 'TABLE_PREFIX_PLACEHOLDER',
    'site_url' => 'SITE_URL_PLACEHOLDER'
);

// ============================================
// INSTALLER CODE - Do not modify below
// ============================================

class WuplicatorInstaller {
    
    private $step = 1;
    private $errors = [];
    private $logs = [];
    private $workDir;
    
    public function __construct() {
        $this->workDir = dirname(__FILE__);
        session_start();
    }
    
    public function run() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
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
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => empty($this->errors),
                'errors' => $this->errors,
                'logs' => $this->logs
            ]);
            exit;
        }
        
        $this->renderUI();
    }
    
    private function validateConfiguration() {
        global $NEW_DB_HOST, $NEW_DB_NAME, $NEW_DB_USER, $NEW_DB_PASSWORD, $NEW_SITE_URL;
        
        $this->log('Validating configuration...');
        
        if (empty($NEW_DB_NAME)) {
            $this->error('Database name is required');
        }
        if (empty($NEW_DB_USER)) {
            $this->error('Database user is required');
        }
        if (empty($NEW_DB_PASSWORD)) {
            $this->error('Database password is required');
        }
        if (empty($NEW_SITE_URL)) {
            $this->error('Site URL is required');
        }
        
        if (empty($this->errors)) {
            $this->log('Configuration validated successfully');
        }
    }
    
    private function downloadBackup() {
        global $BACKUP_URL;
        
        if (empty($BACKUP_URL)) {
            // Check for local backup.zip
            if (file_exists($this->workDir . '/backup.zip')) {
                $this->log('Using local backup.zip');
                return;
            }
            $this->error('No backup found. Please provide BACKUP_URL or upload backup.zip');
            return;
        }
        
        $this->log('Downloading backup from: ' . $BACKUP_URL);
        
        $zipFile = $this->workDir . '/backup.zip';
        
        // Use cURL if available, otherwise file_get_contents
        if (function_exists('curl_init')) {
            $ch = curl_init($BACKUP_URL);
            $fp = fopen($zipFile, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
            $success = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);
            
            if (!$success || $httpCode !== 200) {
                $this->error('Failed to download backup (HTTP ' . $httpCode . ')');
                return;
            }
        } else {
            $content = file_get_contents($BACKUP_URL);
            if ($content === false) {
                $this->error('Failed to download backup. Enable cURL or allow_url_fopen');
                return;
            }
            file_put_contents($zipFile, $content);
        }
        
        $size = filesize($zipFile);
        $this->log('Download complete: ' . $this->formatBytes($size));
    }
    
    private function extractBackup() {
        $this->log('Extracting backup archive...');
        
        $zipFile = $this->workDir . '/backup.zip';
        if (!file_exists($zipFile)) {
            $this->error('Backup file not found');
            return;
        }
        
        if (!class_exists('ZipArchive')) {
            $this->error('ZipArchive extension not available');
            return;
        }
        
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            $this->error('Failed to open backup archive');
            return;
        }
        
        $extracted = $zip->extractTo($this->workDir);
        $numFiles = $zip->numFiles;
        $zip->close();
        
        if (!$extracted) {
            $this->error('Failed to extract files');
            return;
        }
        
        $this->log("Extracted {$numFiles} files successfully");
    }
    
    private function setupDatabase() {
        global $NEW_DB_HOST, $NEW_DB_NAME, $NEW_DB_USER, $NEW_DB_PASSWORD;
        
        $this->log('Setting up database...');
        
        try {
            // Connect without database to create it
            $pdo = new PDO("mysql:host={$NEW_DB_HOST}", $NEW_DB_USER, $NEW_DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$NEW_DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->log("Database '{$NEW_DB_NAME}' created");
            
            // Import SQL file
            $sqlFile = $this->findSQLFile();
            if (!$sqlFile) {
                $this->error('SQL backup file not found');
                return;
            }
            
            $pdo->exec("USE `{$NEW_DB_NAME}`");
            $this->log('Importing database...');
            
            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);
            
            $this->log('Database imported successfully');
        } catch (PDOException $e) {
            $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function configureWordPress() {
        global $NEW_DB_HOST, $NEW_DB_NAME, $NEW_DB_USER, $NEW_DB_PASSWORD, $NEW_SITE_URL;
        global $NEW_ADMIN_USER, $NEW_ADMIN_PASS, $BACKUP_METADATA;
        global $RANDOMIZE_ADMIN_USER, $RANDOMIZE_ADMIN_PASS;
        
        $this->log('Configuring WordPress...');
        
        // Update wp-config.php
        $wpConfig = $this->workDir . '/wp-config.php';
        if (file_exists($wpConfig)) {
            $content = file_get_contents($wpConfig);
            
            $content = preg_replace("/define\s*\(\s*'DB_NAME'\s*,\s*'[^']*'\s*\)/", "define('DB_NAME', '{$NEW_DB_NAME}')", $content);
            $content = preg_replace("/define\s*\(\s*'DB_USER'\s*,\s*'[^']*'\s*\)/", "define('DB_USER', '{$NEW_DB_USER}')", $content);
            $content = preg_replace("/define\s*\(\s*'DB_PASSWORD'\s*,\s*'[^']*'\s*\)/", "define('DB_PASSWORD', '{$NEW_DB_PASSWORD}')", $content);
            $content = preg_replace("/define\s*\(\s*'DB_HOST'\s*,\s*'[^']*'\s*\)/", "define('DB_HOST', '{$NEW_DB_HOST}')", $content);
            
            file_put_contents($wpConfig, $content);
            $this->log('wp-config.php updated');
        }
        
        // Update URLs in database
        $oldUrl = $BACKUP_METADATA['site_url'];
        if ($oldUrl && $NEW_SITE_URL && $oldUrl !== $NEW_SITE_URL) {
            $this->replaceURLs($oldUrl, $NEW_SITE_URL);
        }
        
        // Change admin credentials (existing, manual, or generated)
        if (!empty($NEW_ADMIN_USER) || !empty($NEW_ADMIN_PASS) || $RANDOMIZE_ADMIN_USER || $RANDOMIZE_ADMIN_PASS) {
            $this->updateAdminCredentials($NEW_ADMIN_USER, $NEW_ADMIN_PASS);
        }
    }
    
    private function replaceURLs($oldUrl, $newUrl) {
        global $NEW_DB_HOST, $NEW_DB_NAME, $NEW_DB_USER, $NEW_DB_PASSWORD, $BACKUP_METADATA;
        
        $this->log("Replacing URLs: {$oldUrl} → {$newUrl}");
        
        try {
            $pdo = new PDO("mysql:host={$NEW_DB_HOST};dbname={$NEW_DB_NAME}", $NEW_DB_USER, $NEW_DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $prefix = $BACKUP_METADATA['table_prefix'];
            
            // Update options table
            $stmt = $pdo->prepare("UPDATE {$prefix}options SET option_value = REPLACE(option_value, ?, ?) WHERE option_value LIKE ?");
            $stmt->execute([$oldUrl, $newUrl, "%{$oldUrl}%"]);
            
            // Update posts table
            $stmt = $pdo->prepare("UPDATE {$prefix}posts SET post_content = REPLACE(post_content, ?, ?) WHERE post_content LIKE ?");
            $stmt->execute([$oldUrl, $newUrl, "%{$oldUrl}%"]);
            
            $this->log('URLs updated in database');
        } catch (PDOException $e) {
            $this->error('URL replacement failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate random admin username
     * Pattern: admin_[5 random alphanumeric chars]
     * 
     * @return string Generated username (e.g., 'admin_aB3x9')
     */
    private function generateRandomUsername() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $suffix = '';
        $length = 5;
        
        for ($i = 0; $i < $length; $i++) {
            $suffix .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return 'admin_' . $suffix;
    }
    
    /**
     * Generate random admin password
     * Pattern: 12 random alphanumeric chars (upper, lower, numbers)
     * 
     * @return string Generated password (e.g., 'sL8kJ3hG9mP2')
     */
    private function generateRandomPassword() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        $length = 12;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
    
    private function updateAdminCredentials($newUser, $newPass) {
        global $NEW_DB_HOST, $NEW_DB_NAME, $NEW_DB_USER, $NEW_DB_PASSWORD, $BACKUP_METADATA;
        global $RANDOMIZE_ADMIN_USER, $RANDOMIZE_ADMIN_PASS;
        
        $this->log('Updating admin credentials...');
        
        // Generate random credentials if flags enabled
        if ($RANDOMIZE_ADMIN_USER) {
            $newUser = $this->generateRandomUsername();
            $this->log("Generated random username: {$newUser}");
        }
        
        if ($RANDOMIZE_ADMIN_PASS) {
            $newPass = $this->generateRandomPassword();
            $this->log("Generated random password: {$newPass}");
        }
        
        // Skip if no credentials to update
        if (empty($newUser) && empty($newPass)) {
            $this->log('No admin credentials to update');
            return;
        }
        
        try {
            $pdo = new PDO("mysql:host={$NEW_DB_HOST};dbname={$NEW_DB_NAME}", $NEW_DB_USER, $NEW_DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $prefix = $BACKUP_METADATA['table_prefix'];
            
            // Find admin user (ID = 1 usually)
            $stmt = $pdo->query("SELECT ID FROM {$prefix}users WHERE ID = 1 LIMIT 1");
            $adminId = $stmt->fetchColumn();
            
            if (!$adminId) {
                $this->error('Admin user not found');
                return;
            }
            
            // Update username
            if (!empty($newUser)) {
                $stmt = $pdo->prepare("UPDATE {$prefix}users SET user_login = ? WHERE ID = ?");
                $stmt->execute([$newUser, $adminId]);
                $this->log("✓ Admin username set to: {$newUser}");
            }
            
            // Update password
            if (!empty($newPass)) {
                require_once($this->workDir . '/wp-includes/class-phpass.php');
                $hasher = new PasswordHash(8, true);
                $hashedPass = $hasher->HashPassword($newPass);
                
                $stmt = $pdo->prepare("UPDATE {$prefix}users SET user_pass = ? WHERE ID = ?");
                $stmt->execute([$hashedPass, $adminId]);
                $this->log("✓ Admin password set to: {$newPass}");
            }
            
            // IMPORTANT: Store credentials for final display
            if (!empty($newUser)) {
                $_SESSION['final_admin_user'] = $newUser;
            }
            if (!empty($newPass)) {
                $_SESSION['final_admin_pass'] = $newPass;
            }
            
        } catch (PDOException $e) {
            $this->error('Admin update failed: ' . $e->getMessage());
        }
    }
    
    private function finalizeInstallation() {
        $this->log('Finalizing installation...');
        
        // Display generated credentials if any
        if (isset($_SESSION['final_admin_user']) || isset($_SESSION['final_admin_pass'])) {
            $this->log('═══════════════════════════════════════');
            $this->log('⚠️  IMPORTANT: SAVE THESE CREDENTIALS');
            $this->log('═══════════════════════════════════════');
            
            if (isset($_SESSION['final_admin_user'])) {
                $this->log("Admin Username: {$_SESSION['final_admin_user']}");
            }
            if (isset($_SESSION['final_admin_pass'])) {
                $this->log("Admin Password: {$_SESSION['final_admin_pass']}");
            }
            
            $this->log('═══════════════════════════════════════');
        }
        
        // Delete backup files
        $zipFile = $this->workDir . '/backup.zip';
        if (file_exists($zipFile)) {
            unlink($zipFile);
            $this->log('Backup archive deleted');
        }
        
        $sqlFile = $this->findSQLFile();
        if ($sqlFile && file_exists($sqlFile)) {
            unlink($sqlFile);
            $this->log('SQL file deleted');
        }
        
        $this->log('Installation complete!');
        $this->log('IMPORTANT: Delete installer.php manually for security');
    }
    
    private function findSQLFile() {
        $files = glob($this->workDir . '/*.sql');
        return !empty($files) ? $files[0] : null;
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function log($message) {
        $this->logs[] = $message;
    }
    
    private function error($message) {
        $this->errors[] = $message;
    }
    
    private function renderUI() {
        global $BACKUP_METADATA;
        ?>
<!DOCTYPE html>
<html>
<head>
    <title>Wuplicator Installer</title>
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
        .log { background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 13px; }
        .log-item { margin-bottom: 5px; }
        .error { color: #d32f2f; font-weight: bold; }
        .success { color: #388e3c; font-weight: bold; }
        .info { margin-bottom: 20px; padding: 15px; background: #e3f2fd; border-left: 4px solid #2196f3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Wuplicator Installer</h1>
            <div class="subtitle">WordPress Backup Deployment Tool</div>
        </div>
        <div class="content">
            <div class="progress"><div class="progress-bar" id="progressBar"></div></div>
            
            <div class="step active" id="step1">
                <h2>Step 1: Ready to Install</h2>
                <div class="info">
                    <strong>Backup Info:</strong><br>
                    Created: <?php echo $BACKUP_METADATA['created']; ?><br>
                    Original Database: <?php echo $BACKUP_METADATA['db_name']; ?><br>
                    Original URL: <?php echo $BACKUP_METADATA['site_url']; ?>
                </div>
                <p>Click Start to begin the installation process.</p>
                <br>
                <button onclick="startInstallation()">Start Installation</button>
            </div>
            
            <div class="step" id="step2">
                <h2>Installation Progress</h2>
                <div class="log" id="logOutput"></div>
                <br>
                <div id="completionMessage"></div>
            </div>
        </div>
    </div>
    
    <script>
        let currentStep = 1;
        const steps = ['validate', 'download', 'extract', 'database', 'configure', 'finalize'];
        
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
        
        async function executeStep(stepName, stepIndex) {
            const formData = new FormData();
            formData.append('action', stepName);
            
            const response = await fetch('', { method: 'POST', body: formData });
            const result = await response.json();
            
            result.logs.forEach(msg => log(msg));
            result.errors.forEach(msg => log(msg, 'error'));
            
            return result.success;
        }
        
        async function startInstallation() {
            showStep(2);
            updateProgress(0);
            
            for (let i = 0; i < steps.length; i++) {
                const stepName = steps[i];
                const progress = ((i + 1) / steps.length) * 100;
                
                log(`Starting: ${stepName}...`, 'info');
                const success = await executeStep(stepName, i);
                
                if (!success) {
                    log('Installation failed!', 'error');
                    return;
                }
                
                updateProgress(progress);
            }
            
            document.getElementById('completionMessage').innerHTML = 
                '<div class="success" style="padding: 20px; background: #e8f5e9; border-radius: 4px;">' +
                '<h3>✓ Installation Complete!</h3>' +
                '<p>Your WordPress site has been successfully deployed.</p>' +
                '<p><strong>Important:</strong> Delete installer.php for security.</p>' +
                '</div>';
        }
    </script>
</body>
</html>
        <?php
    }
}

$installer = new WuplicatorInstaller();
$installer->run();
