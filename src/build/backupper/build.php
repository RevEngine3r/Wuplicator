#!/usr/bin/env php
<?php
/**
 * Build script for Wuplicator Backupper
 * 
 * Usage: php build.php
 */

require_once __DIR__ . '/../common/Builder.php';
require_once __DIR__ . '/../common/FileProcessor.php';
require_once __DIR__ . '/../common/VersionGenerator.php';

$builder = new Builder([
    'sourceDir' => realpath(__DIR__ . '/../../modules/backupper'),
    'moduleName' => 'Wuplicator Backupper',
    'mainClass' => 'Wuplicator',
    'outputName' => 'wuplicator.php'
]);

if ($builder->build()) {
    echo "\n";
    echo "========================================\n";
    echo "  Build Successful!\n";
    echo "========================================\n";
    echo "Version: " . $builder->getVersion() . "\n";
    echo "Output:  " . $builder->getOutputPath() . "\n";
    echo "\n";
    exit(0);
} else {
    echo "\n";
    echo "========================================\n";
    echo "  Build Failed!\n";
    echo "========================================\n";
    echo "\n";
    exit(1);
}