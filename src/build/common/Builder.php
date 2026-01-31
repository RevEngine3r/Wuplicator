<?php
/**
 * Wuplicator Build System - Builder
 * 
 * Core builder class that orchestrates the module compilation process.
 * 
 * @package Wuplicator\Build
 * @version 1.2.0
 */

class Builder {
    private $sourceDir;
    private $outputFile;
    private $version;
    private $moduleName;
    private $mainClass;
    private $processor;
    private $versionGen;
    private $releaseDir;
    
    public function __construct(array $config) {
        $this->sourceDir = $config['sourceDir'];
        $this->moduleName = $config['moduleName'];
        $this->mainClass = $config['mainClass'];
        $this->outputName = $config['outputName'];
        
        $this->processor = new FileProcessor();
        $this->versionGen = new VersionGenerator();
        $this->version = $this->versionGen->generate();
        
        // Create release directory
        $this->releaseDir = __DIR__ . '/../../releases/' . $this->version;
        if (!is_dir($this->releaseDir)) {
            mkdir($this->releaseDir, 0755, true);
        }
        
        $this->outputFile = $this->releaseDir . '/' . $this->outputName;
    }
    
    public function build(): bool {
        echo "Building {$this->moduleName}...\n";
        echo "Version: {$this->version}\n";
        echo "Source: {$this->sourceDir}\n";
        echo "Output: {$this->outputFile}\n\n";
        
        // Scan modules
        echo "[1/5] Scanning modules...\n";
        $files = $this->scanModules();
        echo "Found " . count($files) . " files\n\n";
        
        // Process files
        echo "[2/5] Processing files...\n";
        $processed = [];
        foreach ($files as $file) {
            echo "  Processing: " . basename($file) . "\n";
            $processed[$file] = $this->processor->process(file_get_contents($file));
        }
        echo "\n";
        
        // Combine
        echo "[3/5] Combining modules...\n";
        $combined = $this->combine($processed);
        echo "Combined size: " . $this->formatBytes(strlen($combined)) . "\n\n";
        
        // Write output
        echo "[4/5] Writing output file...\n";
        if (!$this->writeOutput($combined)) {
            echo "ERROR: Failed to write output file\n";
            return false;
        }
        echo "Written: {$this->outputFile}\n\n";
        
        // Generate metadata
        echo "[5/5] Generating metadata...\n";
        $metadata = $this->generateMetadata($files);
        $metadataFile = $this->releaseDir . '/build-info.json';
        file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
        echo "Metadata: {$metadataFile}\n\n";
        
        echo "✓ Build complete!\n";
        return true;
    }
    
    public function scanModules(): array {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->sourceDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        // Sort: core first, then alphabetically
        usort($files, function($a, $b) {
            $aHasCore = strpos($a, '/core/') !== false;
            $bHasCore = strpos($b, '/core/') !== false;
            
            if ($aHasCore && !$bHasCore) return -1;
            if (!$aHasCore && $bHasCore) return 1;
            
            return strcmp($a, $b);
        });
        
        return $files;
    }
    
    public function combine(array $processed): string {
        // Load header template
        $header = file_get_contents(__DIR__ . '/../templates/header.template.php');
        $header = str_replace('{MODULE_NAME}', $this->moduleName, $header);
        $header = str_replace('{MODULE_DESCRIPTION}', 'Compiled single-file distribution', $header);
        $header = str_replace('{VERSION}', $this->version, $header);
        $header = str_replace('{BUILD_DATE}', date('Y-m-d'), $header);
        $header = str_replace('{BUILD_TIME}', date('H:i:s'), $header);
        
        // Start with header
        $combined = $header . "\n\n";
        $combined .= "define('WUPLICATOR_EXEC', true);\n\n";
        
        // Add all modules
        foreach ($processed as $file => $content) {
            $relativePath = str_replace($this->sourceDir, '', $file);
            $combined .= "// " . str_repeat('=', 70) . "\n";
            $combined .= "// Module: {$relativePath}\n";
            $combined .= "// " . str_repeat('=', 70) . "\n\n";
            $combined .= $content . "\n\n";
        }
        
        // Load footer template
        $footer = file_get_contents(__DIR__ . '/../templates/footer.template.php');
        $footer = str_replace('{MAIN_CLASS}', $this->mainClass, $footer);
        
        $combined .= $footer;
        
        return $combined;
    }
    
    public function writeOutput(string $content): bool {
        return file_put_contents($this->outputFile, $content) !== false;
    }
    
    public function generateMetadata(array $files): array {
        return [
            'version' => $this->version,
            'timestamp' => date('Y-m-d H:i:s'),
            'build_date' => date('Y-m-d'),
            'build_time' => date('H:i:s'),
            'unix_timestamp' => time(),
            'builder' => [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'machine' => php_uname('n')
            ],
            'module' => [
                'name' => $this->moduleName,
                'main_class' => $this->mainClass,
                'source_files' => count($files)
            ],
            'output' => [
                'file' => basename($this->outputFile),
                'size' => filesize($this->outputFile),
                'lines' => substr_count(file_get_contents($this->outputFile), "\n"),
                'sha256' => hash_file('sha256', $this->outputFile),
                'md5' => md5_file($this->outputFile)
            ],
            'source' => [
                'repository' => 'https://github.com/RevEngine3r/Wuplicator',
                'branch' => 'main'
            ]
        ];
    }
    
    public function getVersion(): string {
        return $this->version;
    }
    
    public function getOutputPath(): string {
        return $this->outputFile;
    }
    
    private function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}