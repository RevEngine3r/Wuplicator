<?php
/**
 * Wuplicator Installer - Configuration Module
 * 
 * @package Wuplicator\Installer\Core
 * @version 1.2.0
 */

namespace Wuplicator\Installer\Core;

class Config {
    
    const VERSION = '1.2.0';
    
    /** @var int Download timeout in seconds */
    const DOWNLOAD_TIMEOUT = 3600;
    
    /** @var int SQL import chunk size (bytes) */
    const SQL_CHUNK_SIZE = 1048576; // 1MB
}
