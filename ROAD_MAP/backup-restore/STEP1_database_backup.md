# STEP1: Database Backup Functionality

## Objective
Implement MySQL database export functionality that reads WordPress database configuration and creates a complete SQL dump file.

## Scope
- Read database credentials from wp-config.php
- Connect to MySQL database using PDO
- Export all tables with structure and data
- Handle large databases with chunked exports
- Include DROP TABLE IF EXISTS statements for clean imports
- Comprehensive error handling and validation

## Implementation Details

### 1. WordPress Config Parser
**Function**: `parseWpConfig($wpConfigPath)`
- Read wp-config.php file
- Extract database constants: DB_NAME, DB_USER, DB_PASSWORD, DB_HOST, DB_CHARSET
- Validate extracted values
- Return configuration array

### 2. Database Connection
**Function**: `connectDatabase($config)`
- Create PDO connection with error mode set to exceptions
- Set charset from wp-config
- Validate connection
- Return PDO instance

### 3. Table Listing
**Function**: `getDatabaseTables($pdo, $dbName)`
- Query SHOW TABLES
- Return array of table names
- Exclude temporary/system tables if needed

### 4. Table Structure Export
**Function**: `exportTableStructure($pdo, $tableName)`
- Get CREATE TABLE statement using SHOW CREATE TABLE
- Add DROP TABLE IF EXISTS before CREATE
- Return SQL string

### 5. Table Data Export
**Function**: `exportTableData($pdo, $tableName, $chunkSize = 1000)`
- Count total rows
- Export data in chunks to handle large tables
- Generate INSERT statements with multiple rows
- Escape special characters properly
- Return SQL string

### 6. Main Backup Function
**Function**: `createDatabaseBackup($wpRoot)`
- Parse wp-config.php
- Connect to database
- Get all tables
- Export each table (structure + data)
- Write to SQL file with proper headers
- Return backup file path

## File Output

### SQL File Structure
```sql
-- Wuplicator Database Backup
-- Generated: 2026-01-29 21:17:00
-- Host: localhost
-- Database: wordpress_db
-- --------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

--
-- Table: wp_posts
--

DROP TABLE IF EXISTS `wp_posts`;
CREATE TABLE `wp_posts` (
  -- table structure
);

INSERT INTO `wp_posts` VALUES
(1, 'value1', 'value2'),
(2, 'value1', 'value2');

-- ... more tables ...
```

## Error Handling
- File not found: wp-config.php missing
- Parse errors: Invalid wp-config.php format
- Connection errors: Invalid credentials or host unreachable
- Export errors: Table access denied or corruption
- Disk space: Insufficient space for backup file

## Security Considerations
- Validate all file paths to prevent directory traversal
- Use parameterized queries (not applicable here, but good practice)
- Proper escaping of SQL values
- Secure temporary file handling

## Testing Requirements
- Unit test: wp-config.php parser
- Unit test: Database connection with valid/invalid credentials
- Unit test: Table structure export
- Unit test: Table data export with various data types
- Integration test: Full database backup of sample WordPress site
- Edge case: Empty database
- Edge case: Large tables (1M+ rows)
- Edge case: Special characters in data

## Success Criteria
- ✅ Successfully parse wp-config.php
- ✅ Connect to WordPress database
- ✅ Export all tables with complete data
- ✅ Generate valid SQL file
- ✅ Handle errors gracefully with informative messages
- ✅ Pass all unit and integration tests

## Dependencies
- PHP PDO extension with MySQL driver
- Read access to wp-config.php
- Write access to backup directory

---

**Status**: Complete  
**Files Created**: `src/wuplicator.php`, `tests/DatabaseBackupTest.php`
