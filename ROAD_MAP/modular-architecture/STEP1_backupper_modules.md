# STEP1: Create Backupper Modules

## Goal
Split `src/wuplicator.php` (monolithic ~1000 lines) into 7 focused modules (~100-200 lines each).

## Module Breakdown

### 1. `core/Wuplicator.php`
**Lines**: ~100
**Responsibility**: Main orchestration and initialization

**Contains**:
- Class constructor
- Public API methods (`createPackage()`)
- Module instantiation and coordination
- Error handling
- Logging infrastructure

**Dependencies**: All other modules

**Public API**:
```php
class Wuplicator {
    public function __construct($wpRoot = null)
    public function createPackage()
    public function getErrors()
    public function getLogs()
    public function run() // Web UI entry point
}
```

---

### 2. `database/ConfigParser.php`
**Lines**: ~80
**Responsibility**: Parse wp-config.php file

**Contains**:
- `parseWpConfig()` method
- Regex patterns for constants
- Validation logic
- Default value handling

**Dependencies**: None (pure parser)

**Public API**:
```php
class ConfigParser {
    public function parse($wpConfigPath)
    public function getConfig()
    public function getDatabaseName()
    public function getTablePrefix()
}
```

---

### 3. `database/DatabaseBackup.php`
**Lines**: ~200
**Responsibility**: Database export operations

**Contains**:
- Database connection (`connectDatabase()`)
- Table scanning (`getDatabaseTables()`)
- Structure export (`exportTableStructure()`)
- Data export (`exportTableData()`)
- SQL file generation
- Site URL extraction

**Dependencies**: ConfigParser

**Public API**:
```php
class DatabaseBackup {
    public function __construct($config)
    public function createBackup($outputPath)
    public function getSiteURL()
    public function getTableCount()
}
```

---

### 4. `filesystem/FileScanner.php`
**Lines**: ~100
**Responsibility**: Directory scanning with exclusions

**Contains**:
- Recursive directory iteration
- Exclusion pattern matching
- Wildcard support
- Symbolic link handling
- File list generation

**Dependencies**: None

**Public API**:
```php
class FileScanner {
    public function __construct($wpRoot, $excludes = [])
    public function scan()
    public function getFiles()
    public function getFileCount()
}
```

---

### 5. `filesystem/ArchiveCreator.php`
**Lines**: ~120
**Responsibility**: ZIP archive creation and validation

**Contains**:
- ZIP creation (`createArchive()`)
- File addition with progress
- Archive validation
- Integrity checking

**Dependencies**: FileScanner

**Public API**:
```php
class ArchiveCreator {
    public function __construct($wpRoot)
    public function createArchive($files, $outputPath, $progressCallback = null)
    public function validateArchive($zipPath)
}
```

---

### 6. `packager/InstallerGenerator.php`
**Lines**: ~80
**Responsibility**: Generate installer.php with metadata

**Contains**:
- Installer template loading
- Metadata embedding
- Security token generation
- Placeholder replacement

**Dependencies**: ConfigParser, DatabaseBackup

**Public API**:
```php
class InstallerGenerator {
    public function __construct($templatePath)
    public function generate($metadata, $outputPath)
    public function embedMetadata($installer, $data)
}
```

---

### 7. `ui/WebInterface.php`
**Lines**: ~200
**Responsibility**: Web UI rendering and AJAX handling

**Contains**:
- HTML/CSS/JS rendering
- POST request handling
- Progress updates
- Success/error display

**Dependencies**: Wuplicator (core)

**Public API**:
```php
class WebInterface {
    public function __construct($wuplicator)
    public function render()
    public function handleRequest()
}
```

---

## Module Manifest

**File**: `modules/backupper/manifest.json`

```json
{
  "name": "Wuplicator Backupper",
  "version": "1.1.0",
  "component": "backupper",
  "output": "wuplicator.php",
  "modules": [
    "database/ConfigParser.php",
    "database/DatabaseBackup.php",
    "filesystem/FileScanner.php",
    "filesystem/ArchiveCreator.php",
    "packager/InstallerGenerator.php",
    "ui/WebInterface.php",
    "core/Wuplicator.php"
  ],
  "entry_point": "core/Wuplicator.php",
  "web_bootstrap": "// Instantiate and run\n$wuplicator = new Wuplicator();\n$wuplicator->run();"
}
```

**Note**: Order matters! Dependencies must be loaded before dependents.

---

## Refactoring Strategy

### 1. Extract ConfigParser
- Copy `parseWpConfig()` and related methods
- Make standalone class
- Test parsing functionality

### 2. Extract DatabaseBackup
- Copy database-related methods
- Inject ConfigParser instance
- Test backup creation

### 3. Extract FileScanner
- Copy `scanDirectory()` method
- Make standalone utility
- Test exclusion patterns

### 4. Extract ArchiveCreator
- Copy ZIP-related methods
- Use FileScanner for file list
- Test archive creation

### 5. Extract InstallerGenerator
- Copy installer generation logic
- Inject dependencies
- Test metadata embedding

### 6. Extract WebInterface
- Copy UI rendering methods
- Separate HTML/CSS/JS
- Test AJAX handling

### 7. Refactor Core
- Keep only orchestration logic
- Instantiate all modules
- Coordinate workflow
- Test end-to-end

---

## Testing Checklist

### Per Module
- [ ] Syntax valid (no PHP errors)
- [ ] All methods accessible
- [ ] Dependencies resolved
- [ ] No global state
- [ ] Type hints correct

### Integration
- [ ] Modules work together
- [ ] Same output as monolith
- [ ] No functionality lost
- [ ] Performance comparable
- [ ] Error handling intact

---

## Success Criteria

- ✅ 7 modules created
- ✅ Each module < 250 lines
- ✅ Clear responsibilities
- ✅ No circular dependencies
- ✅ All tests pass
- ✅ Build produces working wuplicator.php

---

**Next**: STEP2 - Installer Modules
