<?php
/**
 * File Scanner Tests
 */

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../../src/modules/backupper/files/Scanner.php';

use Wuplicator\Backupper\Files\Scanner;

class FileScannerTest extends TestCase {
    
    public function __construct() {
        parent::__construct('File Scanner');
    }
    
    public function run() {
        $this->testScan();
        $this->testExclusions();
    }
    
    private function testScan() {
        $scanner = new Scanner();
        $testDir = __DIR__ . '/../fixtures/test-files';
        
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
            file_put_contents($testDir . '/test1.txt', 'test');
            file_put_contents($testDir . '/test2.php', '<?php');
        }
        
        $files = $scanner->scan($testDir, $testDir);
        
        $this->assertTrue(count($files) >= 2, 'Scanner finds files');
        $this->assertTrue(is_array($files), 'Scanner returns array');
    }
    
    private function testExclusions() {
        $scanner = new Scanner();
        $testDir = __DIR__ . '/../fixtures/test-files';
        
        // Create excluded file
        if (!is_dir($testDir . '/.git')) {
            mkdir($testDir . '/.git', 0755, true);
            file_put_contents($testDir . '/.git/config', 'test');
        }
        
        $files = $scanner->scan($testDir, $testDir);
        
        $hasGitFile = false;
        foreach ($files as $file) {
            if (strpos($file, '.git') !== false) {
                $hasGitFile = true;
            }
        }
        
        $this->assertTrue(!$hasGitFile, 'Scanner excludes .git directory');
    }
}
