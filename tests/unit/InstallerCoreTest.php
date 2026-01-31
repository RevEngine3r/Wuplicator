<?php
/**
 * Installer Core Module Tests
 */

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../../src/modules/installer/core/Logger.php';
require_once __DIR__ . '/../../src/modules/installer/core/Config.php';
require_once __DIR__ . '/../../src/modules/installer/core/Utils.php';

use Wuplicator\Installer\Core\Logger;
use Wuplicator\Installer\Core\Config;
use Wuplicator\Installer\Core\Utils;

class InstallerCoreTest extends TestCase {
    
    public function __construct() {
        parent::__construct('Installer Core Modules');
    }
    
    public function run() {
        $this->testLogger();
        $this->testConfig();
        $this->testUtils();
    }
    
    private function testLogger() {
        $logger = new Logger();
        $logger->log('Test message');
        
        $logs = $logger->getLogs();
        $this->assertEquals(1, count($logs), 'Logger stores messages');
        $this->assertEquals('Test message', $logs[0], 'Logger stores correct message');
    }
    
    private function testConfig() {
        $config = new Config();
        $config->set('test_key', 'test_value');
        
        $this->assertEquals('test_value', $config->get('test_key'), 'Config stores values');
    }
    
    private function testUtils() {
        // Test formatBytes
        $this->assertEquals('1.00 KB', Utils::formatBytes(1024), 'Utils formats bytes');
        
        // Test generateToken
        $token = Utils::generateToken(16);
        $this->assertEquals(32, strlen($token), 'Utils generates tokens');
    }
}
