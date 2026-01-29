<?php
/**
 * Unit tests for Wuplicator Database Backup
 * 
 * Run with: php tests/DatabaseBackupTest.php
 */

require_once __DIR__ . '/../src/wuplicator.php';

class DatabaseBackupTest {
    
    private $testsPassed = 0;
    private $testsFailed = 0;
    private $wuplicator;
    
    public function __construct() {
        $this->wuplicator = new Wuplicator(__DIR__ . '/fixtures');
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
     * Assert two values are equal
     */
    private function assertEqual($expected, $actual, $message) {
        $this->assertTrue($expected === $actual, $message . " (expected: {$expected}, got: {$actual})");
    }
    
    /**
     * Test: Parse valid wp-config.php
     */
    public function testParseWpConfigValid() {
        echo "\nTest: Parse valid wp-config.php\n";
        
        // Create test wp-config.php
        $testConfig = __DIR__ . '/fixtures/wp-config.php';
        $content = "<?php\n";
        $content .= "define('DB_NAME', 'test_database');\n";
        $content .= "define('DB_USER', 'test_user');\n";
        $content .= "define('DB_PASSWORD', 'test_pass');\n";
        $content .= "define('DB_HOST', 'localhost');\n";
        $content .= "define('DB_CHARSET', 'utf8mb4');\n";
        
        if (!is_dir(__DIR__ . '/fixtures')) {
            mkdir(__DIR__ . '/fixtures', 0755, true);
        }
        file_put_contents($testConfig, $content);
        
        try {
            $config = $this->wuplicator->parseWpConfig($testConfig);
            $this->assertEqual('test_database', $config['DB_NAME'], 'DB_NAME parsed correctly');
            $this->assertEqual('test_user', $config['DB_USER'], 'DB_USER parsed correctly');
            $this->assertEqual('test_pass', $config['DB_PASSWORD'], 'DB_PASSWORD parsed correctly');
            $this->assertEqual('localhost', $config['DB_HOST'], 'DB_HOST parsed correctly');
            $this->assertEqual('utf8mb4', $config['DB_CHARSET'], 'DB_CHARSET parsed correctly');
        } catch (Exception $e) {
            $this->assertTrue(false, "Should parse valid config: " . $e->getMessage());
        }
        
        unlink($testConfig);
    }
    
    /**
     * Test: Parse wp-config.php with missing file
     */
    public function testParseWpConfigMissing() {
        echo "\nTest: Parse missing wp-config.php\n";
        
        try {
            $this->wuplicator->parseWpConfig('/nonexistent/wp-config.php');
            $this->assertTrue(false, 'Should throw exception for missing file');
        } catch (Exception $e) {
            $this->assertTrue(true, 'Throws exception for missing file');
            $this->assertTrue(strpos($e->getMessage(), 'not found') !== false, 'Error message mentions "not found"');
        }
    }
    
    /**
     * Test: Parse wp-config.php with missing required fields
     */
    public function testParseWpConfigIncomplete() {
        echo "\nTest: Parse incomplete wp-config.php\n";
        
        $testConfig = __DIR__ . '/fixtures/wp-config-incomplete.php';
        $content = "<?php\n";
        $content .= "define('DB_NAME', 'test_database');\n";
        // Missing DB_USER, DB_PASSWORD, DB_HOST
        
        file_put_contents($testConfig, $content);
        
        try {
            $this->wuplicator->parseWpConfig($testConfig);
            $this->assertTrue(false, 'Should throw exception for incomplete config');
        } catch (Exception $e) {
            $this->assertTrue(true, 'Throws exception for incomplete config');
            $this->assertTrue(strpos($e->getMessage(), 'Missing required') !== false, 'Error message mentions "Missing required"');
        }
        
        unlink($testConfig);
    }
    
    /**
     * Test: Format bytes utility
     */
    public function testFormatBytes() {
        echo "\nTest: Format bytes utility\n";
        
        $reflection = new ReflectionClass($this->wuplicator);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);
        
        $this->assertEqual('0 B', $method->invoke($this->wuplicator, 0), 'Format 0 bytes');
        $this->assertEqual('1 KB', $method->invoke($this->wuplicator, 1024), 'Format 1 KB');
        $this->assertEqual('1 MB', $method->invoke($this->wuplicator, 1048576), 'Format 1 MB');
        $this->assertEqual('1.5 MB', $method->invoke($this->wuplicator, 1572864), 'Format 1.5 MB');
    }
    
    /**
     * Run all tests
     */
    public function runAll() {
        echo "\n===================================\n";
        echo "  Wuplicator Database Backup Tests\n";
        echo "===================================\n";
        
        $this->testParseWpConfigValid();
        $this->testParseWpConfigMissing();
        $this->testParseWpConfigIncomplete();
        $this->testFormatBytes();
        
        echo "\n===================================\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo "===================================\n\n";
        
        // Cleanup
        if (is_dir(__DIR__ . '/fixtures')) {
            rmdir(__DIR__ . '/fixtures');
        }
        
        return $this->testsFailed === 0;
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $tests = new DatabaseBackupTest();
    $success = $tests->runAll();
    exit($success ? 0 : 1);
}
