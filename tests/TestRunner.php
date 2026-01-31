<?php
/**
 * Test Runner
 * 
 * Discovers and executes test files.
 */

class TestRunner {
    
    private $testDirs = [];
    private $totalTests = 0;
    private $totalPassed = 0;
    private $totalFailed = 0;
    
    public function __construct($testDirs) {
        $this->testDirs = $testDirs;
    }
    
    /**
     * Run all tests
     */
    public function runAll() {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "  WUPLICATOR TEST SUITE\n";
        echo str_repeat('=', 60) . "\n\n";
        
        foreach ($this->testDirs as $dir) {
            $this->runTestsInDirectory($dir);
        }
        
        $this->printSummary();
        
        return $this->totalFailed === 0;
    }
    
    /**
     * Run tests in directory
     */
    private function runTestsInDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = glob($dir . '/*Test.php');
        
        foreach ($files as $file) {
            $this->runTestFile($file);
        }
    }
    
    /**
     * Run single test file
     */
    private function runTestFile($file) {
        require_once $file;
        
        $className = basename($file, '.php');
        
        if (!class_exists($className)) {
            return;
        }
        
        $test = new $className();
        
        if (!method_exists($test, 'run')) {
            return;
        }
        
        $test->run();
        $results = $test->getResults();
        
        $this->totalTests++;
        $this->totalPassed += $results['passed'];
        $this->totalFailed += $results['failed'];
        
        $this->printTestResult($results);
    }
    
    /**
     * Print test result
     */
    private function printTestResult($results) {
        $status = $results['failed'] === 0 ? "\033[32m✓ PASS\033[0m" : "\033[31m✗ FAIL\033[0m";
        echo "  {$status}  {$results['test']} ({$results['passed']}/{$results['total']})\n";
        
        if ($results['failed'] > 0) {
            foreach ($results['assertions'] as $assertion) {
                if ($assertion['status'] === 'FAIL') {
                    echo "         ✗ {$assertion['message']}\n";
                }
            }
        }
    }
    
    /**
     * Print summary
     */
    private function printSummary() {
        echo "\n" . str_repeat('-', 60) . "\n";
        echo "  SUMMARY\n";
        echo str_repeat('-', 60) . "\n";
        echo "  Tests:      {$this->totalTests}\n";
        echo "  Assertions: " . ($this->totalPassed + $this->totalFailed) . "\n";
        
        if ($this->totalFailed === 0) {
            echo "  \033[32m✓ ALL TESTS PASSED ({$this->totalPassed} assertions)\033[0m\n";
        } else {
            echo "  \033[31m✗ FAILURES: {$this->totalFailed} assertions failed\033[0m\n";
            echo "  \033[32m✓ PASSED: {$this->totalPassed} assertions\033[0m\n";
        }
        
        echo str_repeat('=', 60) . "\n\n";
    }
}
