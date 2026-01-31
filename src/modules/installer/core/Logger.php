<?php
/**
 * Wuplicator Installer - Logger Module
 * 
 * @package Wuplicator\Installer\Core
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Core;

class Logger {
    
    private $logs = [];
    private $errors = [];
    
    public function log($message) {
        $this->logs[] = $message;
    }
    
    public function error($message) {
        $this->errors[] = $message;
        $this->log("ERROR: {$message}");
    }
    
    public function getLogs() {
        return $this->logs;
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
}
