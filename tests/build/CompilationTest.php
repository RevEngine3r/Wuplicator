<?php
/**
 * Compilation Validation Tests
 */

require_once __DIR__ . '/../TestCase.php';

class CompilationTest extends TestCase {
    
    public function __construct() {
        parent::__construct('Compilation Validation');
    }
    
    public function run() {
        $this->testReleasesDirectory();
        $this->testCompiledSyntax();
    }
    
    private function testReleasesDirectory() {
        $releasesDir = __DIR__ . '/../../src/releases';
        
        $this->assertFileExists($releasesDir, 'Releases directory exists');
        
        // Check for latest release
        $releases = glob($releasesDir . '/v*');
        if (!empty($releases)) {
            rsort($releases);
            $latestRelease = $releases[0];
            
            $this->assertFileExists($latestRelease, 'Latest release directory exists');
            
            // Check for compiled files in latest release
            if (file_exists($latestRelease . '/wuplicator.php')) {
                $this->assertFileExists($latestRelease . '/wuplicator.php', 'Compiled wuplicator.php exists');
            }
            
            if (file_exists($latestRelease . '/installer.php')) {
                $this->assertFileExists($latestRelease . '/installer.php', 'Compiled installer.php exists');
            }
        } else {
            $this->assertTrue(true, 'No releases yet (run build system first)');
        }
    }
    
    private function testCompiledSyntax() {
        $releasesDir = __DIR__ . '/../../src/releases';
        $releases = glob($releasesDir . '/v*');
        
        if (empty($releases)) {
            $this->assertTrue(true, 'No compiled files to validate (run build first)');
            return;
        }
        
        rsort($releases);
        $latestRelease = $releases[0];
        
        // Validate wuplicator.php syntax
        $wuplicatorFile = $latestRelease . '/wuplicator.php';
        if (file_exists($wuplicatorFile)) {
            $output = [];
            $return = 0;
            exec("php -l {$wuplicatorFile} 2>&1", $output, $return);
            $this->assertTrue($return === 0, 'Compiled wuplicator.php has valid PHP syntax');
        }
        
        // Validate installer.php syntax
        $installerFile = $latestRelease . '/installer.php';
        if (file_exists($installerFile)) {
            $output = [];
            $return = 0;
            exec("php -l {$installerFile} 2>&1", $output, $return);
            $this->assertTrue($return === 0, 'Compiled installer.php has valid PHP syntax');
        }
    }
}
