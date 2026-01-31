<?php
/**
 * Wuplicator Build System - File Processor
 * 
 * Processes PHP files for compilation.
 * 
 * @version 1.2.0
 */

class FileProcessor {
    
    /**
     * Process PHP file content
     * - Remove PHP opening/closing tags
     * - Remove namespace declarations
     * - Remove use statements for internal namespaces
     * - Preserve all class code
     */
    public function process($content) {
        // Remove PHP tags
        $content = $this->stripPHPTags($content);
        
        // Remove namespace declarations
        $content = preg_replace('/^namespace\s+[^;]+;\s*$/m', '', $content);
        
        // Remove use statements (internal namespaces only)
        $content = preg_replace('/^use\s+Wuplicator\\[^;]+;\s*$/m', '', $content);
        
        // Remove multiple empty lines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        // Trim
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * Strip PHP opening and closing tags
     */
    public function stripPHPTags($content) {
        $content = preg_replace('/^<\?php\s*/i', '', $content);
        $content = preg_replace('/\?>\s*$/', '', $content);
        return trim($content);
    }
}
