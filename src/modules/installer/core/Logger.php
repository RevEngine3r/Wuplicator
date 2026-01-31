<?php
/**
 * Logger Module
 * 
 * Handles logging for installation operations.
 */

namespace Wuplicator\Installer\Core;

class Logger {
    
    private $logs = [];
    
    /**
     * Log a message
     * 
     * @param string $message Log message
     */
    public function log($message) {
        $this->logs[] = $message;
    }
    
    /**
     * Get all logs
     * 
     * @return array Log messages
     */
    public function getLogs() {
        return $this->logs;
    }
    
    /**
     * Clear all logs
     */
    public function clear() {
        $this->logs = [];
    }
}
