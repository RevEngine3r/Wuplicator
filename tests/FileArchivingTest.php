<?php
/**
 * Unit tests for Wuplicator File Archiving
 * 
 * Run with: php tests/FileArchivingTest.php
 */

require_once __DIR__ . '/../src/wuplicator.php';

class FileArchivingTest {
    
    private $testsPassed = 0;
    private $testsFailed = 0;
    private $wuplicator;
    private $testDir;
    
    public function __construct() {
        $this->testDir = __DIR__ . '/fixtures/wp-site';
        $this->wuplicator = new Wuplicator($this->testDir);
    }
    
    /**
     * Assert condition is true
     */
    private function assertTrue($condition, $message) {
        if ($condition) {
            $this->testsPassed++;
            echo "  ✓ {$message}\n";
        } else {
            $this->testsFailed++;
            echo "  ✗ {$message}\n";
        }
    }
    
    /**
     * Create test WordPress structure
     */
    private function createTestSite() {
        // Create directory structure
        $dirs = [
            $this->testDir,
            $this->testDir . '/wp-content',
            $this->testDir . '/wp-content/themes',
            $this->testDir . '/wp-content/plugins',
            $this->testDir . '/wp-content/uploads',
            $this->testDir . '/wp-content/cache',
            $this->testDir . '/wp-admin',
            $this->testDir . '/wp-includes',
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        // Create test files
        file_put_contents($this->testDir . '/index.php', '<?php // WordPress');
        file_put_contents($this->testDir . '/wp-config.php', '<?php // Config');
        file_put_contents($this->testDir . '/wp-content/themes/style.css', '/* Theme */');
        file_put_contents($this->testDir . '/wp-content/plugins/plugin.php', '<?php // Plugin');
        file_put_contents($this->testDir . '/wp-content/uploads/image.jpg', 'fake-image-data');
        file_put_contents($this->testDir . '/wp-content/cache/cache.tmp', 'cache-data');
        file_put_contents($this->testDir . '/.DS_Store', 'mac-metadata');
        file_put_contents($this->testDir . '/error_log', 'error-log-data');
    }
    
    /**
     * Remove test site
     */
    private function removeTestSite() {
        if (!is_dir($this->testDir)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->testDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        
        rmdir($this->testDir);
    }
    
    /**
     * Test: Scan directory
     */
    public function testScanDirectory() {
        echo "\nTest: Scan directory\n";
        
        $this->createTestSite();
        
        $files = $this->wuplicator->scanDirectory($this->testDir);
        
        // Should find files
        $this->assertTrue(count($files) > 0, 'Found files in directory');
        
        // Should include core files
        $this->assertTrue(in_array('index.php', $files), 'Includes index.php');
        $this->assertTrue(in_array('wp-config.php', $files), 'Includes wp-config.php');
        
        // Should exclude cache
        $cacheFound = false;
        foreach ($files as $file) {
            if (strpos($file, 'cache') !== false) {
                $cacheFound = true;
                break;
            }
        }
        $this->assertTrue(!$cacheFound, 'Excludes cache files');
        
        // Should exclude .DS_Store
        $this->assertTrue(!in_array('.DS_Store', $files), 'Excludes .DS_Store');
        
        // Should exclude error_log
        $this->assertTrue(!in_array('error_log', $files), 'Excludes error_log');
        
        $this->removeTestSite();
    }
    
    /**
     * Test: Create ZIP archive
     */
    public function testCreateZipArchive() {
        echo "\nTest: Create ZIP archive\n";
        
        if (!class_exists('ZipArchive')) {
            echo "  ⚠ ZipArchive not available, skipping test\n";
            return;
        }
        
        $this->createTestSite();
        
        try {
            // Redirect output
            ob_start();
            $zipFile = $this->wuplicator->createFilesBackup();
            ob_end_clean();
            
            $this->assertTrue(file_exists($zipFile), 'ZIP file created');
            $this->assertTrue(filesize($zipFile) > 0, 'ZIP file has content');
            
            // Validate archive
            $zip = new ZipArchive();
            $opened = $zip->open($zipFile);
            $this->assertTrue($opened === true, 'ZIP archive can be opened');
            
            $numFiles = $zip->numFiles;
            $this->assertTrue($numFiles > 0, 'ZIP contains files');
            
            $zip->close();
            
            // Cleanup
            unlink($zipFile);
        } catch (Exception $e) {
            $this->assertTrue(false, 'ZIP creation failed: ' . $e->getMessage());
        }
        
        $this->removeTestSite();
    }
    
    /**
     * Test: Custom exclusions
     */
    public function testCustomExclusions() {
        echo "\nTest: Custom exclusions\n";
        
        $this->createTestSite();
        
        // Create custom file to exclude
        file_put_contents($this->testDir . '/custom-exclude.txt', 'exclude-me');
        
        $customExcludes = ['custom-exclude.txt'];
        $files = $this->wuplicator->scanDirectory($this->testDir, $customExcludes);
        
        $this->assertTrue(!in_array('custom-exclude.txt', $files), 'Custom exclusion works');
        
        $this->removeTestSite();
    }
    
    /**
     * Test: Validate archive
     */
    public function testValidateArchive() {
        echo "\nTest: Validate archive\n";
        
        if (!class_exists('ZipArchive')) {
            echo "  ⚠ ZipArchive not available, skipping test\n";
            return;
        }
        
        // Create test ZIP
        $testZip = __DIR__ . '/test-archive.zip';
        $zip = new ZipArchive();
        $zip->open($testZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('test.txt', 'test content');
        $zip->close();
        
        // Validate
        ob_start();
        $valid = $this->wuplicator->validateArchive($testZip);
        ob_end_clean();
        
        $this->assertTrue($valid, 'Valid archive passes validation');
        
        // Cleanup
        unlink($testZip);
        
        // Test invalid archive
        $invalidZip = __DIR__ . '/invalid.zip';
        file_put_contents($invalidZip, 'not-a-zip-file');
        
        $valid = $this->wuplicator->validateArchive($invalidZip);
        $this->assertTrue(!$valid, 'Invalid archive fails validation');
        
        unlink($invalidZip);
    }
    
    /**
     * Run all tests
     */
    public function runAll() {
        echo "\n===================================\n";
        echo "  Wuplicator File Archiving Tests\n";
        echo "===================================\n";
        
        $this->testScanDirectory();
        $this->testCreateZipArchive();
        $this->testCustomExclusions();
        $this->testValidateArchive();
        
        echo "\n===================================\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo "===================================\n\n";
        
        return $this->testsFailed === 0;
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $tests = new FileArchivingTest();
    $success = $tests->runAll();
    exit($success ? 0 : 1);
}
