<?php
/**
 * Wuplicator Installer Build Script
 * 
 * Compiles modular installer sources into single installer.php
 * 
 * @version 1.2.0
 */

// Load build system classes
require_once __DIR__ . '/../common/Builder.php';
require_once __DIR__ . '/../common/FileProcessor.php';
require_once __DIR__ . '/../common/ModuleLoader.php';

// Paths
$modulesDir = realpath(__DIR__ . '/../../modules');
$releasesDir = realpath(__DIR__ . '/../../releases');
$templatePath = realpath(__DIR__ . '/../templates/installer-template.php');

// Initialize builder
$builder = new Builder($modulesDir, $releasesDir);

// Build installer
echo str_repeat('=', 50) . "\n";
echo "  Wuplicator Installer Builder\n";
echo str_repeat('=', 50) . "\n\n";

$metadata = $builder->build('installer', 'installer.php', $templatePath);

echo "\n" . str_repeat('=', 50) . "\n";
echo "Build Complete!\n";
echo "Version: " . $builder->getVersion() . "\n";
echo "Location: " . $builder->getOutputDir() . "\n";
echo str_repeat('=', 50) . "\n";

// Save metadata
$buildInfo = [
    'version' => $builder->getVersion(),
    'timestamp' => date('Y-m-d H:i:s'),
    'build_date' => date('Y-m-d'),
    'build_time' => date('H:i:s'),
    'type' => 'installer',
    'installer' => $metadata
];

file_put_contents(
    $builder->getOutputDir() . '/build-info-installer.json',
    json_encode($buildInfo, JSON_PRETTY_PRINT)
);

exit(0);
