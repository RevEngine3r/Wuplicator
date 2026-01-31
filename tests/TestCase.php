<?php
/**
 * Base Test Case
 * 
 * Simple assertion methods for testing without external dependencies.
 */

class TestCase {
    
    protected $testName;
    protected $passed = 0;
    protected $failed = 0;
    protected $assertions = [];
    
    public function __construct($testName) {
        $this->testName = $testName;
    }
    
    /**
     * Assert that condition is true
     */
    protected function assertTrue($condition, $message = '') {
        if ($condition === true) {
            $this->passed++;
            $this->assertions[] = ['status' => 'PASS', 'message' => $message];
        } else {
            $this->failed++;
            $this->assertions[] = ['status' => 'FAIL', 'message' => $message];
        }
    }
    
    /**
     * Assert that two values are equal
     */
    protected function assertEquals($expected, $actual, $message = '') {
        if ($expected === $actual) {
            $this->passed++;
            $this->assertions[] = ['status' => 'PASS', 'message' => $message];
        } else {
            $this->failed++;
            $msg = $message . " [Expected: {$expected}, Got: {$actual}]";
            $this->assertions[] = ['status' => 'FAIL', 'message' => $msg];
        }
    }
    
    /**
     * Assert that string contains substring
     */
    protected function assertContains($needle, $haystack, $message = '') {
        if (strpos($haystack, $needle) !== false) {
            $this->passed++;
            $this->assertions[] = ['status' => 'PASS', 'message' => $message];
        } else {
            $this->failed++;
            $this->assertions[] = ['status' => 'FAIL', 'message' => $message];
        }
    }
    
    /**
     * Assert that file exists
     */
    protected function assertFileExists($file, $message = '') {
        if (file_exists($file)) {
            $this->passed++;
            $this->assertions[] = ['status' => 'PASS', 'message' => $message];
        } else {
            $this->failed++;
            $this->assertions[] = ['status' => 'FAIL', 'message' => $message . " [File: {$file}]" ];
        }
    }
    
    /**
     * Get test results
     */
    public function getResults() {
        return [
            'test' => $this->testName,
            'passed' => $this->passed,
            'failed' => $this->failed,
            'total' => $this->passed + $this->failed,
            'assertions' => $this->assertions
        ];
    }
}
