<?php
/**
 * Wuplicator Backupper - Logger
 * 
 * Logging system for tracking operations and errors.
 * 
 * @package Wuplicator\Backupper\Core
 * @version 1.2.0
 */

namespace Wuplicator\Backupper\Core;

class Logger {
    /**
     * @var array Log messages
     */
    private $logs = [];
    
    /**
     * @var array Error messages
     */
    private $errors = [];
    
    /**
     * @var bool Enable console output
     */
    private $consoleOutput = false;
    
    /**
     * Constructor
     */
    public function __construct(bool $consoleOutput = false) {
        $this->consoleOutput = $consoleOutput;
    }
    
    /**
     * Log an info message
     */
    public function log(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}] {$message}";
        $this->logs[] = $entry;
        
        if ($this->consoleOutput) {
            echo $entry . "\n";
        }
    }
    
    /**
     * Log an error message
     */
    public function error(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}] ERROR: {$message}";
        $this->errors[] = $entry;
        $this->logs[] = $entry;
        
        if ($this->consoleOutput) {
            echo $entry . "\n";
        }
    }
    
    /**
     * Get all logs
     */
    public function getLogs(): array {
        return $this->logs;
    }
    
    /**
     * Get all errors
     */
    public function getErrors(): array {
        return $this->errors;
    }
    
    /**
     * Check if there are errors
     */
    public function hasErrors(): bool {
        return !empty($this->errors);
    }
    
    /**
     * Clear all logs
     */
    public function clear(): void {
        $this->logs = [];
        $this->errors = [];
    }
    
    /**
     * Get logs as formatted string
     */
    public function toString(): string {
        return implode("\n", $this->logs);
    }
}