<?php
/**
 * Run All Tests
 * 
 * Usage: php tests/run-all-tests.php
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/TestRunner.php';

$testDirs = [
    __DIR__ . '/unit',
    __DIR__ . '/integration',
    __DIR__ . '/build'
];

$runner = new TestRunner($testDirs);
$success = $runner->runAll();

exit($success ? 0 : 1);
