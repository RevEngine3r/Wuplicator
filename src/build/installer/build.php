#!/usr/bin/env php
<?php
/**
 * Build script for Wuplicator Installer
 * 
 * Usage: php build.php
 */

require_once __DIR__ . '/../common/Builder.php';
require_once __DIR__ . '/../common/FileProcessor.php';
require_once __DIR__ . '/../common/VersionGenerator.php';

$builder = new Builder([
    'sourceDir' => realpath(__DIR__ . '/../../modules/installer'),
    'moduleName' => 'Wuplicator Installer',
    'mainClass' => 'Installer',
    'outputName' => 'installer.php'
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