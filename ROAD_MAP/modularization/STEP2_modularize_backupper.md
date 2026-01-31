# STEP2: Modularize Backupper (wuplicator.php)

## Objective
Split the monolithic wuplicator.php (27KB) into logical, manageable modules while maintaining 100% functionality.

## Current Analysis

### wuplicator.php Structure (~800 lines)
1. **Configuration** (~50 lines)
   - Default exclusion patterns
   - Directory paths
   - Constants

2. **Database Operations** (~350 lines)
   - parseWpConfig()
   - connectDatabase()
   - getDatabaseTables()
   - exportTableStructure()
   - exportTableData()
   - createDatabaseBackup()
   - getSiteURL()

3. **File Operations** (~250 lines)
   - scanDirectory()
   - createFilesBackup()
   - validateArchive()

4. **Generator** (~50 lines)
   - generateInstaller()

5. **Utilities** (~50 lines)
   - formatBytes()
   - log()
   - error()

6. **Web UI** (~100 lines)
   - run()
   - renderUI()

## Module Breakdown

### Module 1: Database/Parser.php
```php
namespace Wuplicator\Backupper\Database;

class Parser {
    public function parseWpConfig(string $path): array
    public function extractConstants(string $content, array $keys): array
    public function extractTablePrefix(string $content): string
}
```

### Module 2: Database/Connection.php
```php
namespace Wuplicator\Backupper\Database;

class Connection {
    public function connect(array $config): \PDO
    public function test(array $config): bool
    public function getSiteURL(\PDO $pdo, string $prefix): string
}
```

### Module 3: Database/Exporter.php
```php
namespace Wuplicator\Backupper\Database;

class Exporter {
    public function getTables(\PDO $pdo, string $dbName): array
    public function exportStructure(\PDO $pdo, string $table): string
    public function exportData(\PDO $pdo, string $table, int $chunk): string
}
```

### Module 4: Database/Backup.php
```php
namespace Wuplicator\Backupper\Database;

class Backup {
    private $parser;
    private $connection;
    private $exporter;
    
    public function create(string $wpRoot, string $outputDir): string
    private function generateHeader(array $config): string
}
```

### Module 5: Files/Scanner.php
```php
namespace Wuplicator\Backupper\Files;

class Scanner {
    public function scan(string $path, array $excludes): array
    private function shouldExclude(string $path, array $patterns): bool
    private function matchPattern(string $path, string $pattern): bool
}
```

### Module 6: Files/Archiver.php
```php
namespace Wuplicator\Backupper\Files;

class Archiver {
    public function create(string $wpRoot, array $files, string $output): bool
    public function addFiles(\ZipArchive $zip, array $files, string $root): int
    public function reportProgress(int $current, int $total): void
}
```

### Module 7: Files/Validator.php
```php
namespace Wuplicator\Backupper\Files;

class Validator {
    public function validate(string $zipPath): bool
    public function getFileCount(string $zipPath): int
    public function checkIntegrity(string $zipPath): array
}
```

### Module 8: Generator/InstallerGenerator.php
```php
namespace Wuplicator\Backupper\Generator;

class InstallerGenerator {
    public function generate(array $metadata, string $templatePath, string $outputPath): string
    private function replaceTokens(string $content, array $tokens): string
    private function generateSecurityToken(): string
}
```

### Module 9: UI/WebInterface.php
```php
namespace Wuplicator\Backupper\UI;

class WebInterface {
    public function run(): void
    public function handleRequest(): void
    private function render(array $data): void
    private function renderHTML(array $siteInfo): string
}
```

### Module 10: Wuplicator.php (Main Orchestrator)
```php
namespace Wuplicator\Backupper;

class Wuplicator {
    private $config;
    private $logger;
    private $utils;
    
    // Module instances
    private $databaseBackup;
    private $filesBackup;
    private $installerGenerator;
    private $webUI;
    
    public function __construct(string $wpRoot = null)
    public function createPackage(): array
    public function run(): void
}
```

## Implementation Plan

### Phase 1: Extract Core Modules (Database)
1. Create Database/Parser.php
2. Create Database/Connection.php
3. Create Database/Exporter.php
4. Create Database/Backup.php (orchestrator)
5. Test database backup functionality

### Phase 2: Extract File Modules
1. Create Files/Scanner.php
2. Create Files/Archiver.php
3. Create Files/Validator.php
4. Test file backup functionality

### Phase 3: Extract Supporting Modules
1. Create Generator/InstallerGenerator.php
2. Create UI/WebInterface.php
3. Test complete workflow

### Phase 4: Create Main Orchestrator
1. Create Wuplicator.php (new modular version)
2. Wire all modules together
3. Maintain exact same public API
4. Test full backup creation

## File Structure Result

```
src/modules/backupper/
├── core/
│   ├── Config.php              [STEP1]
│   ├── Logger.php              [STEP1]
│   ├── Utils.php               [STEP1]
│   └── ModuleInterface.php     [STEP1]
├── database/
│   ├── Parser.php              [NEW]
│   ├── Connection.php          [NEW]
│   ├── Exporter.php            [NEW]
│   └── Backup.php              [NEW]
├── files/
│   ├── Scanner.php             [NEW]
│   ├── Archiver.php            [NEW]
│   └── Validator.php           [NEW]
├── generator/
│   └── InstallerGenerator.php  [NEW]
├── ui/
│   └── WebInterface.php        [NEW]
└── Wuplicator.php              [NEW - Orchestrator]
```

## Testing Strategy

### Unit Tests (Per Module)
1. Database\Parser: Test wp-config parsing
2. Database\Connection: Test MySQL connection
3. Database\Exporter: Test table export
4. Files\Scanner: Test directory scanning with exclusions
5. Files\Archiver: Test ZIP creation
6. Files\Validator: Test archive validation
7. Generator\InstallerGenerator: Test installer generation

### Integration Tests
1. Database backup end-to-end
2. Files backup end-to-end
3. Complete package creation
4. Web UI workflow

### Validation
- ✅ Module outputs match original functions
- ✅ File sizes similar to original
- ✅ Performance comparable
- ✅ All error handling preserved
- ✅ Logging works correctly

## Success Criteria

- ✅ All 10 modules created
- ✅ Original wuplicator.php remains unchanged (for reference)
- ✅ New modular version works identically
- ✅ No functionality lost
- ✅ All tests pass
- ✅ Code more readable and maintainable
- ✅ Average file size < 200 lines

## Estimated Time
2-3 hours

## Dependencies
- STEP1 complete (core utilities)

## Next Step
STEP3: Modularize Installer (split installer.php)
