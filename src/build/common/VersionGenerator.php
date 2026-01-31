<?php
/**
 * Wuplicator Build System - Version Generator
 * 
 * Generates datetime-based version strings.
 * 
 * @package Wuplicator\Build
 * @version 1.2.0
 */

class VersionGenerator {
    public function generate(): string {
        return 'v' . date('Ymd_His');
    }
    
    public function getDateTime(): string {
        return date('Y-m-d H:i:s');
    }
    
    public function getTimestamp(): int {
        return time();
    }
}