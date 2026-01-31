<?php
/**
 * Wuplicator Backupper - Database Exporter Module
 * 
 * Exports MySQL table structures and data to SQL format.
 * 
 * @package Wuplicator\Backupper\Database
 * @version 1.2.0
 */

namespace Wuplicator\Backupper\Database;

use \PDO;
use Wuplicator\Backupper\Core\Config;

class Exporter {
    
    /**
     * Export table structure (CREATE TABLE statement)
     * 
     * @param PDO $pdo Database connection
     * @param string $tableName Table name
     * @return string SQL CREATE TABLE statement
     */
    public function exportStructure($pdo, $tableName) {
        $sql = "--\n-- Table: {$tableName}\n--\n\n";
        $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
        
        $stmt = $pdo->query("SHOW CREATE TABLE `{$tableName}`");
        $row = $stmt->fetch();
        $sql .= $row['Create Table'] . ";\n\n";
        
        return $sql;
    }
    
    /**
     * Export table data (INSERT statements)
     * 
     * @param PDO $pdo Database connection
     * @param string $tableName Table name
     * @param int $chunkSize Rows per INSERT statement
     * @return string SQL INSERT statements
     */
    public function exportData($pdo, $tableName, $chunkSize = null) {
        $chunkSize = $chunkSize ?? Config::DB_CHUNK_SIZE;
        $sql = "";
        
        // Get total rows
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$tableName}`");
        $totalRows = $stmt->fetch()['count'];
        
        if ($totalRows == 0) {
            return "-- No data for table {$tableName}\n\n";
        }
        
        // Get column names
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$tableName}`");
        $columns = [];
        while ($col = $stmt->fetch()) {
            $columns[] = $col['Field'];
        }
        $columnList = '`' . implode('`, `', $columns) . '`';
        
        // Export data in chunks
        $offset = 0;
        while ($offset < $totalRows) {
            $stmt = $pdo->query("SELECT * FROM `{$tableName}` LIMIT {$chunkSize} OFFSET {$offset}");
            $rows = $stmt->fetchAll();
            
            if (!empty($rows)) {
                $sql .= "INSERT INTO `{$tableName}` ({$columnList}) VALUES\n";
                $values = [];
                
                foreach ($rows as $row) {
                    $rowValues = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $rowValues[] = 'NULL';
                        } else {
                            $escaped = $pdo->quote($value);
                            $rowValues[] = $escaped;
                        }
                    }
                    $values[] = '(' . implode(', ', $rowValues) . ')';
                }
                
                $sql .= implode(",\n", $values) . ";\n\n";
            }
            
            $offset += $chunkSize;
        }
        
        return $sql;
    }
}
