<?php
/**
 * Configuration Module
 * 
 * Stores installer configuration and constants.
 */

namespace Wuplicator\Installer\Core;

class Config {
    
    private $config = [];
    
    /**
     * Set configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     */
    public function set($key, $value) {
        $this->config[$key] = $value;
    }
    
    /**
     * Get configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Configuration value
     */
    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Get all configuration
     * 
     * @return array All configuration
     */
    public function all() {
        return $this->config;
    }
}
