# STEP3: Modularize Installer (installer.php)

## Objective
Split the monolithic installer.php (25KB) into logical, manageable modules while maintaining 100% functionality including v1.1.0 security features.

## Current Analysis

### installer.php Structure (~900 lines)
1. **Configuration** (~100 lines)
   - Database config
   - Security flags (v1.1.0)
   - Metadata placeholders

2. **Download Module** (~150 lines)
   - downloadFile()
   - Progress tracking
   - Error handling

3. **Extraction Module** (~100 lines)
   - extractArchive()
   - File operations

4. **Database Module** (~300 lines)
   - connectDatabase()
   - importSQL()
   - parseSQL()
   - replaceURLs()
   - URL search/replace logic

5. **Configuration Module** (~150 lines)
   - updateWpConfig()
   - regenerateWPSecurityKeys() [v1.1.0]
   - generateSecurityKey() [v1.1.0]

6. **Security Module** (~100 lines)
   - updateAdminCredentials()
   - generateRandomUsername() [v1.1.0]
   - generateRandomPassword() [v1.1.0]

7. **Web UI** (~150 lines)
   - run()
   - renderUI()
   - Step-based wizard

## Module Breakdown

### Module 1: Download/Downloader.php
```php
namespace Wuplicator\Installer\Download;

class Downloader {
    public function download(string $url, string $output): bool
    public function getRemoteFileSize(string $url): int
    private function validateURL(string $url): bool
    private function reportProgress(int $downloaded, int $total): void
}
```

### Module 2: Extraction/Extractor.php
```php
namespace Wuplicator\Installer\Extraction;

class Extractor {
    public function extract(string $zipPath, string $destination): bool
    public function validateArchive(string $zipPath): bool
    private function reportProgress(int $current, int $total): void
}
```

### Module 3: Database/Connection.php
```php
namespace Wuplicator\Installer\Database;

class Connection {
    public function connect(array $config): \PDO
    public function test(array $config): bool
    public function createDatabase(array $config): bool
}
```

### Module 4: Database/Importer.php
```php
namespace Wuplicator\Installer\Database;

class Importer {
    public function import(string $sqlFile, \PDO $pdo): bool
    private function parseSQL(string $content): array
    private function executeBatch(array $statements, \PDO $pdo): int
    private function reportProgress(int $current, int $total): void
}
```

### Module 5: Database/Migrator.php
```php
namespace Wuplicator\Installer\Database;

class Migrator {
    public function replaceURLs(\PDO $pdo, string $old, string $new, string $prefix): int
    private function getTables(\PDO $pdo, string $prefix): array
    private function replaceInTable(\PDO $pdo, string $table, string $old, string $new): int
    private function isSerializedColumn(string $type): bool
}
```

### Module 6: Configuration/WpConfigUpdater.php
```php
namespace Wuplicator\Installer\Configuration;

class WpConfigUpdater {
    public function update(string $path, array $config): bool
    private function replaceConstant(string $content, string $name, string $value): string
    private function replaceTablePrefix(string $content, string $prefix): string
}
```

### Module 7: Configuration/SecurityKeys.php
```php
namespace Wuplicator\Installer\Configuration;

class SecurityKeys {
    private $keys = [
        'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
        'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'
    ];
    
    public function regenerate(string $wpConfigPath): bool
    public function generateKey(): string
    private function replaceKeyInConfig(string $content, string $key, string $value): string
}
```

### Module 8: Security/AdminManager.php
```php
namespace Wuplicator\Installer\Security;

class AdminManager {
    public function update(\PDO $pdo, string $prefix, array $credentials, bool $randomize): array
    public function generateRandomUsername(): string
    public function generateRandomPassword(): string
    private function hashPassword(string $password): string
}
```

### Module 9: UI/WebInterface.php
```php
namespace Wuplicator\Installer\UI;

class WebInterface {
    public function run(): void
    public function handleRequest(): void
    private function renderStep(int $step, array $data): void
    private function renderHTML(int $currentStep): string
}
```

### Module 10: Installer.php (Main Orchestrator)
```php
namespace Wuplicator\Installer;

class Installer {
    private $config;
    private $logger;
    private $utils;
    
    // Module instances
    private $downloader;
    private $extractor;
    private $databaseConnection;
    private $databaseImporter;
    private $databaseMigrator;
    private $wpConfigUpdater;
    private $securityKeys;
    private $adminManager;
    private $webUI;
    
    public function __construct()
    public function install(array $config): array
    public function run(): void
}
```

## Implementation Plan

### Phase 1: Extract Download & Extraction
1. Create Download/Downloader.php
2. Create Extraction/Extractor.php
3. Test download and extraction

### Phase 2: Extract Database Modules
1. Create Database/Connection.php
2. Create Database/Importer.php
3. Create Database/Migrator.php
4. Test database operations

### Phase 3: Extract Configuration Modules
1. Create Configuration/WpConfigUpdater.php
2. Create Configuration/SecurityKeys.php (v1.1.0)
3. Test wp-config updates

### Phase 4: Extract Security Module
1. Create Security/AdminManager.php
2. Include v1.1.0 random generation features
3. Test admin credential updates

### Phase 5: Extract UI & Create Orchestrator
1. Create UI/WebInterface.php
2. Create Installer.php (new modular version)
3. Wire all modules together
4. Test complete installation workflow

## File Structure Result

```
src/modules/installer/
├── core/
│   ├── Config.php              [STEP1]
│   ├── Logger.php              [STEP1]
│   ├── Utils.php               [STEP1]
│   └── ModuleInterface.php     [STEP1]
├── download/
│   └── Downloader.php          [NEW]
├── extraction/
│   └── Extractor.php           [NEW]
├── database/
│   ├── Connection.php          [NEW]
│   ├── Importer.php            [NEW]
│   └── Migrator.php            [NEW]
├── configuration/
│   ├── WpConfigUpdater.php     [NEW]
│   └── SecurityKeys.php        [NEW - v1.1.0]
├── security/
│   └── AdminManager.php        [NEW - includes v1.1.0]
├── ui/
│   └── WebInterface.php        [NEW]
└── Installer.php               [NEW - Orchestrator]
```

## v1.1.0 Security Features Preserved

### Configuration Flags
```php
// In Config.php
const RANDOMIZE_ADMIN_USER = false;
const RANDOMIZE_ADMIN_PASS = false;
const REGENERATE_SECURITY_KEYS = false;
```

### Security/AdminManager.php
- ✅ generateRandomUsername() - admin_[5 chars]
- ✅ generateRandomPassword() - 12 alphanumeric
- ✅ Cryptographically secure random generation

### Configuration/SecurityKeys.php
- ✅ regenerate() - All 8 WordPress keys
- ✅ generateKey() - 64 character keys
- ✅ Cryptographically secure

## Testing Strategy

### Unit Tests (Per Module)
1. Download\Downloader: Test remote downloads
2. Extraction\Extractor: Test ZIP extraction
3. Database\Connection: Test database connectivity
4. Database\Importer: Test SQL import
5. Database\Migrator: Test URL replacement
6. Configuration\WpConfigUpdater: Test config updates
7. Configuration\SecurityKeys: Test key generation
8. Security\AdminManager: Test credential updates

### Integration Tests
1. Download + Extract workflow
2. Database import + migrate workflow
3. wp-config update workflow
4. Security features (v1.1.0)
5. Complete installation end-to-end
6. Web UI workflow

### v1.1.0 Feature Validation
- ✅ Random username generation works
- ✅ Random password generation works
- ✅ All 8 security keys regenerated
- ✅ Credentials displayed to user
- ✅ Configuration flags respected

## Success Criteria

- ✅ All 10 modules created
- ✅ Original installer.php remains unchanged (for reference)
- ✅ New modular version works identically
- ✅ v1.1.0 security features fully functional
- ✅ No functionality lost
- ✅ All tests pass
- ✅ Code more readable and maintainable
- ✅ Average file size < 200 lines

## Estimated Time
2-3 hours

## Dependencies
- STEP1 complete (core utilities)
- STEP2 complete (pattern established)

## Next Step
STEP4: Build System Implementation (compile modules to single files)
