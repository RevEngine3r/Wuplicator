<?php
/**
 * Wuplicator Build System - File Processor
 * 
 * Processes PHP files for compilation: removes tags, strips namespaces.
 * 
 * @package Wuplicator\Build
 * @version 1.2.0
 */

class FileProcessor {
    public function process(string $content): string {
        // Remove opening PHP tag
        $content = preg_replace('/^<\?php\s*/', '', $content);
        
        // Remove closing PHP tag
        $content = preg_replace('/\?>\s*$/', '', $content);
        
        // Strip namespace declarations but keep the classes
        $content = preg_replace('/^namespace\s+[^;]+;\s*/m', '', $content);
        
        // Remove use statements
        $content = preg_replace('/^use\s+[^;]+;\s*/m', '', $content);
        
        // Normalize line endings
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        
        // Trim leading/trailing whitespace but preserve internal structure
        $content = trim($content);
        
        return $content;
    }
}