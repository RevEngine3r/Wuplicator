<?php
/**
 * Wuplicator Build System - Builder Class
 * 
 * Orchestrates the module compilation process.
 * 
 * @version 1.2.0
 */

class Builder {
    
    private $modulesDir;
    private $outputDir;
    private $version;
    private $moduleLoader;
    private $fileProcessor;
    
    public function __construct($modulesDir, $releasesDir) {
        $this->modulesDir = $modulesDir;
        $this->version = $this->generateVersion();
        $this->outputDir = $releasesDir . '/' . $this->version;
        $this->moduleLoader = new ModuleLoader();
        $this->fileProcessor = new FileProcessor();
        
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    /**
     * Build backupper or installer from modules
     * 
     * @param string $type 'backupper' or 'installer'
     * @param string $outputFilename Output filename
     * @param string $templatePath Optional template path
     * @return array Build metadata
     */
    public function build($type, $outputFilename, $templatePath = null) {
        echo "Building {$type}...\n";
        echo "Version: {$this->version}\n";
        
        $moduleDir = $this->modulesDir . '/' . $type;
        $modules = $this->moduleLoader->discoverModules($moduleDir);
        
        echo "Found " . count($modules) . " modules\n";
        
        // Generate compiled code
        $compiled = $this->compileModules($modules);
        
        // Add template if provided
        if ($templatePath && file_exists($templatePath)) {
            $template = file_get_contents($templatePath);
            $compiled .= "\n\n" . $this->fileProcessor->stripPHPTags($template);
        }
        
        // Wrap in PHP tags
        $output = "<?php\n";
        $output .= "/**\n";
        $output .= " * Wuplicator " . ucfirst($type) . "\n";
        $output .= " * Compiled from modular sources\n";
        $output .= " * \n";
        $output .= " * @version {$this->version}\n";
        $output .= " * @generated " . date('Y-m-d H:i:s') . "\n";
        $output .= " * @modules " . count($modules) . "\n";
        $output .= " */\n\n";
        $output .= $compiled;
        
        // Write output
        $outputPath = $this->outputDir . '/' . $outputFilename;
        file_put_contents($outputPath, $output);
        
        $size = filesize($outputPath);
        $hash = hash_file('sha256', $outputPath);
        
        echo "Output: {$outputPath}\n";
        echo "Size: " . $this->formatBytes($size) . "\n";
        echo "SHA256: {$hash}\n";
        
        return [
            'file' => $outputFilename,
            'path' => $outputPath,
            'size' => $size,
            'sha256' => $hash,
            'modules' => count($modules)
        ];
    }
    
    /**
     * Compile all modules into single code block
     */
    private function compileModules($modules) {
        $compiled = "";
        
        foreach ($modules as $module) {
            echo "  Processing: {$module}\n";
            $content = file_get_contents($module);
            $processed = $this->fileProcessor->process($content);
            $compiled .= $processed . "\n";
        }
        
        return $compiled;
    }
    
    /**
     * Generate datetime-based version
     */
    private function generateVersion() {
        return 'v' . date('Ymd_His');
    }
    
    /**
     * Get version string
     */
    public function getVersion() {
        return $this->version;
    }
    
    /**
     * Get output directory
     */
    public function getOutputDir() {
        return $this->outputDir;
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
