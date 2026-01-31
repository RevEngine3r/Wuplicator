<?php
/**
 * Wuplicator Installer - Web Interface Module
 * 
 * @package Wuplicator\Installer\UI
 * @version 1.2.0
 */

namespace Wuplicator\Installer\UI;

class WebInterface {
    
    private $metadata;
    
    public function __construct($metadata) {
        $this->metadata = $metadata;
    }
    
    public function render() {
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
            <div class="subtitle">WordPress Backup Deployment Tool v1.2.0</div>
        </div>
        <div class="content">
            <div class="progress"><div class="progress-bar" id="progressBar"></div></div>
            
            <div class="step active" id="step1">
                <h2>Step 1: Ready to Install</h2>
                <div class="info">
                    <strong>Backup Info:</strong><br>
                    Created: <?php echo htmlspecialchars($this->metadata['created']); ?><br>
                    Original Database: <?php echo htmlspecialchars($this->metadata['db_name']); ?><br>
                    Original URL: <?php echo htmlspecialchars($this->metadata['site_url']); ?>
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
        
        async function executeStep(stepName) {
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
                const success = await executeStep(stepName);
                
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
