<?php
/**
 * Versioning Tests
 */

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../../src/build/common/VersionGenerator.php';

class VersioningTest extends TestCase {
    
    public function __construct() {
        parent::__construct('Version Generator');
    }
    
    public function run() {
        $this->testVersionFormat();
        $this->testUniqueness();
    }
    
    private function testVersionFormat() {
        $generator = new VersionGenerator();
        $version = $generator->generate();
        
        $this->assertTrue(strpos($version, 'v') === 0, 'Version starts with v');
        $this->assertTrue(strlen($version) === 16, 'Version is 16 chars (vYYYYMMDD_HHMMSS)');
        $this->assertContains('_', $version, 'Version contains underscore separator');
        
        // Validate format: vYYYYMMDD_HHMMSS
        $pattern = '/^v\d{8}_\d{6}$/';
        $this->assertTrue(preg_match($pattern, $version) === 1, 'Version matches vYYYYMMDD_HHMMSS format');
    }
    
    private function testUniqueness() {
        $generator = new VersionGenerator();
        $version1 = $generator->generate();
        sleep(1); // Ensure different timestamp
        $version2 = $generator->generate();
        
        $this->assertTrue($version1 !== $version2, 'Versions are unique');
    }
}
