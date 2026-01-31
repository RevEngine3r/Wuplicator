<?php
/**
 * Database Parser Tests
 */

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../../src/modules/backupper/database/Parser.php';

use Wuplicator\Backupper\Database\Parser;

class DatabaseParserTest extends TestCase {
    
    public function __construct() {
        parent::__construct('Database Parser');
    }
    
    public function run() {
        $this->testParseWpConfig();
    }
    
    private function testParseWpConfig() {
        $parser = new Parser();
        $testConfig = __DIR__ . '/../fixtures/sample-wp-config.php';
        
        if (!file_exists($testConfig)) {
            $this->assertTrue(false, 'Test fixture sample-wp-config.php not found');
            return;
        }
        
        try {
            $config = $parser->parse($testConfig);
            
            $this->assertTrue(isset($config['DB_NAME']), 'Parser extracts DB_NAME');
            $this->assertTrue(isset($config['DB_USER']), 'Parser extracts DB_USER');
            $this->assertTrue(isset($config['DB_PASSWORD']), 'Parser extracts DB_PASSWORD');
            $this->assertTrue(isset($config['DB_HOST']), 'Parser extracts DB_HOST');
            $this->assertTrue(isset($config['table_prefix']), 'Parser extracts table_prefix');
            
            $this->assertEquals('test_database', $config['DB_NAME'], 'Parser reads correct DB_NAME');
            $this->assertEquals('wp_', $config['table_prefix'], 'Parser reads correct table_prefix');
        } catch (Exception $e) {
            $this->assertTrue(false, 'Parser throws exception: ' . $e->getMessage());
        }
    }
}
