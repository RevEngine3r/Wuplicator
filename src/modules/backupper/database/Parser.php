<?php
/**
 * Database Configuration Parser
 * 
 * Parses WordPress wp-config.php file to extract database credentials.
 */

namespace Wuplicator\Backupper\Database;

use Exception;

class Parser {
    
    /**
     * Parse WordPress wp-config.php file
     * 
     * @param string $wpConfigPath Path to wp-config.php
     * @return array Database configuration
     * @throws Exception If file not found or parse fails
     */
    public function parse($wpConfigPath) {
        if (!file_exists($wpConfigPath)) {
            throw new Exception("wp-config.php not found at: {$wpConfigPath}");
        }
        
        $content = file_get_contents($wpConfigPath);
        if ($content === false) {
            throw new Exception("Failed to read wp-config.php");
        }
        
        $config = [];
        $constants = ['DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST', 'DB_CHARSET', 'DB_COLLATE'];
        
        foreach ($constants as $const) {
            $pattern = "/define\\s*\\(\\s*['\"]{$const}['\"]\\s*,\\s*['\"]([^'\"]*)['\"]
\\s*\\)/";
            if (preg_match($pattern, $content, $matches)) {
                $config[$const] = $matches[1];
            }
        }
        
        // Validate required fields
        $required = ['DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST'];
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new Exception("Missing required database config: {$field}");
            }
        }
        
        // Set defaults
        $config['DB_CHARSET'] = $config['DB_CHARSET'] ?? 'utf8mb4';
        $config['DB_COLLATE'] = $config['DB_COLLATE'] ?? '';
        
        // Get table prefix
        if (preg_match("/\\$table_prefix\\s*=\\s*'([^']+)'/", $content, $matches)) {
            $config['table_prefix'] = $matches[1];
        } else {
            $config['table_prefix'] = 'wp_';
        }
        
        return $config;
    }
}
