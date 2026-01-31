<?php
/**
 * Wuplicator Installer - Logger
 * 
 * Logging system for tracking operations and errors.
 * 
 * @package Wuplicator\Installer\Core
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Core;

class Logger {
    private $logs = [];
    private $errors = [];
    private $consoleOutput = false;
    
    public function __construct(bool $consoleOutput = false) {
        $this->consoleOutput = $consoleOutput;
    }
    
    public function log(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}] {$message}";
        $this->logs[] = $entry;
        
        if ($this->consoleOutput) {
            echo $entry . "\n";
        }
    }
    
    public function error(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}] ERROR: {$message}";
        $this->errors[] = $entry;
        $this->logs[] = $entry;
        
        if ($this->consoleOutput) {
            echo $entry . "\n";
        }
    }
    
    public function getLogs(): array {
        return $this->logs;
    }
    
    public function getErrors(): array {
        return $this->errors;
    }
    
    public function hasErrors(): bool {
        return !empty($this->errors);
    }
    
    public function clear(): void {
        $this->logs = [];
        $this->errors = [];
    }
    
    public function toString(): string {
        return implode("\n", $this->logs);
    }
}