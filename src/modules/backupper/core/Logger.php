<?php
/**
 * Wuplicator Backupper - Logger Module
 * 
 * Centralized logging system for tracking backup operations.
 * 
 * @package Wuplicator\Backupper\Core
 * @version 1.2.0
 */

namespace Wuplicator\Backupper\Core;

class Logger {
    
    /** @var array Log messages */
    private $logs = [];
    
    /** @var array Error messages */
    private $errors = [];
    
    /** @var float Start time for performance tracking */
    private $startTime;
    
    public function __construct() {
        $this->startTime = microtime(true);
    }
    
    /**
     * Log information message
     * 
     * @param string $message Log message
     */
    public function log($message) {
        $timestamp = date('H:i:s');
        $elapsed = round(microtime(true) - $this->startTime, 2);
        $this->logs[] = "[{$timestamp}] [{$elapsed}s] {$message}";
    }
    
    /**
     * Log error message
     * 
     * @param string $message Error message
     */
    public function error($message) {
        $timestamp = date('H:i:s');
        $this->errors[] = "[{$timestamp}] ERROR: {$message}";
        $this->log("ERROR: {$message}");
    }
    
    /**
     * Get all log messages
     * 
     * @return array Log messages
     */
    public function getLogs() {
        return $this->logs;
    }
    
    /**
     * Get all error messages
     * 
     * @return array Error messages
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if any errors occurred
     * 
     * @return bool True if errors exist
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Get elapsed time since logger creation
     * 
     * @return float Elapsed time in seconds
     */
    public function getElapsedTime() {
        return round(microtime(true) - $this->startTime, 2);
    }
}
