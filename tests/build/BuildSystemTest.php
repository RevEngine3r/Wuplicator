<?php
/**
 * Build System Tests
 */

require_once __DIR__ . '/../TestCase.php';

class BuildSystemTest extends TestCase {
    
    public function __construct() {
        parent::__construct('Build System');
    }
    
    public function run() {
        $this->testBuildScriptsExist();
        $this->testModuleStructure();
    }
    
    private function testBuildScriptsExist() {
        $buildDir = __DIR__ . '/../../src/build';
        
        $this->assertFileExists($buildDir . '/backupper/build.php', 'Backupper build script exists');
        $this->assertFileExists($buildDir . '/installer/build.php', 'Installer build script exists');
        $this->assertFileExists($buildDir . '/common/Builder.php', 'Builder class exists');
        $this->assertFileExists($buildDir . '/common/FileProcessor.php', 'FileProcessor exists');
        $this->assertFileExists($buildDir . '/common/VersionGenerator.php', 'VersionGenerator exists');
    }
    
    private function testModuleStructure() {
        $modulesDir = __DIR__ . '/../../src/modules';
        
        $this->assertFileExists($modulesDir . '/backupper', 'Backupper modules directory exists');
        $this->assertFileExists($modulesDir . '/installer', 'Installer modules directory exists');
        
        // Check critical backupper modules
        $this->assertFileExists($modulesDir . '/backupper/Wuplicator.php', 'Wuplicator orchestrator exists');
        $this->assertFileExists($modulesDir . '/backupper/core/Logger.php', 'Backupper Logger exists');
        $this->assertFileExists($modulesDir . '/backupper/database/Parser.php', 'Database Parser exists');
        
        // Check critical installer modules
        $this->assertFileExists($modulesDir . '/installer/Installer.php', 'Installer orchestrator exists');
        $this->assertFileExists($modulesDir . '/installer/core/Logger.php', 'Installer Logger exists');
        $this->assertFileExists($modulesDir . '/installer/security/AdminManager.php', 'AdminManager exists');
    }
}
