<?php
/**
 * Wuplicator Backupper - Web Interface Module
 * 
 * Renders the HTML/CSS/JavaScript user interface.
 * 
 * @package Wuplicator\Backupper\UI
 * @version 1.2.0
 */

namespace Wuplicator\Backupper\UI;

class WebInterface {
    
    private $siteInfo;
    
    public function __construct($siteInfo) {
        $this->siteInfo = $siteInfo;
    }
    
    /**
     * Render complete web UI
     */
    public function render() {
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
            <div class="subtitle">WordPress Complete Backup Tool v1.2.0</div>
        </div>
        <div class="content">
            <div class="progress"><div class="progress-bar" id="progressBar"></div></div>
            
            <div class="step active" id="step1">
                <h2>Ready to Create Backup</h2>
                <div class="info">
                    <strong>Current Site Info:</strong><br>
                    Database: <?php echo htmlspecialchars($this->siteInfo['db_name']); ?><br>
                    Table Prefix: <?php echo htmlspecialchars($this->siteInfo['table_prefix']); ?><br>
                    Site URL: <?php echo htmlspecialchars($this->siteInfo['site_url']); ?>
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
            }
        }
    </script>
</body>
</html>
        <?php
    }
}
