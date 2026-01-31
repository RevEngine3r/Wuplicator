<?php
/**
 * Backupper Core Module Tests
 */

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../../src/modules/backupper/core/Logger.php';
require_once __DIR__ . '/../../src/modules/backupper/core/Config.php';
require_once __DIR__ . '/../../src/modules/backupper/core/Utils.php';

use Wuplicator\Backupper\Core\Logger;
use Wuplicator\Backupper\Core\Config;
use Wuplicator\Backupper\Core\Utils;

class BackupperCoreTest extends TestCase {
    
    public function __construct() {
        parent::__construct('Backupper Core Modules');
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
        
        $logger->clear();
        $this->assertEquals(0, count($logger->getLogs()), 'Logger clears messages');
    }
    
    private function testConfig() {
        $config = new Config();
        $config->set('test_key', 'test_value');
        
        $this->assertEquals('test_value', $config->get('test_key'), 'Config stores and retrieves values');
        $this->assertEquals('default', $config->get('nonexistent', 'default'), 'Config returns default for missing keys');
    }
    
    private function testUtils() {
        // Test formatBytes
        $this->assertEquals('1.00 KB', Utils::formatBytes(1024), 'Utils formats KB correctly');
        $this->assertEquals('1.00 MB', Utils::formatBytes(1048576), 'Utils formats MB correctly');
        
        // Test generateToken
        $token1 = Utils::generateToken(16);
        $token2 = Utils::generateToken(16);
        $this->assertEquals(32, strlen($token1), 'Utils generates correct token length (16 bytes = 32 hex chars)');
        $this->assertTrue($token1 !== $token2, 'Utils generates unique tokens');
    }
}
