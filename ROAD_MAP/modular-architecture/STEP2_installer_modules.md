# STEP2: Create Installer Modules

## Goal
Split `src/installer.php` (monolithic ~800 lines) into 7 focused modules (~80-150 lines each).

## Module Breakdown

### 1. `core/WuplicatorInstaller.php`
**Lines**: ~100
**Responsibility**: Main orchestration and workflow

**Contains**:
- Class constructor
- Session management
- Workflow orchestration (validate → download → extract → database → configure → finalize)
- Error/log management
- Request routing

**Dependencies**: All other modules

**Public API**:
```php
class WuplicatorInstaller {
    public function __construct()
    public function run()
    private function validateConfiguration()
    private function processStep($step)
}
```

---

### 2. `database/DatabaseSetup.php`
**Lines**: ~120
**Responsibility**: Database creation and import

**Contains**:
- Database connection
- Database creation
- SQL file import
- SQL parsing and execution
- Error handling

**Dependencies**: None

**Public API**:
```php
class DatabaseSetup {
    public function __construct($config)
    public function createDatabase()
    public function importSQL($sqlFile)
    public function testConnection()
}
```

---

### 3. `config/WPConfigManager.php`
**Lines**: ~80
**Responsibility**: wp-config.php modifications

**Contains**:
- Database config updates
- Regex-based replacement
- File reading/writing
- Syntax preservation

**Dependencies**: None

**Public API**:
```php
class WPConfigManager {
    public function __construct($wpConfigPath)
    public function updateDatabaseConfig($config)
    public function getContent()
    public function save()
}
```

---

### 4. `config/URLReplacer.php`
**Lines**: ~60
**Responsibility**: URL search and replace in database

**Contains**:
- Options table updates
- Posts table updates
- Prepared statements
- Pattern matching

**Dependencies**: DatabaseSetup

**Public API**:
```php
class URLReplacer {
    public function __construct($pdo, $tablePrefix)
    public function replace($oldUrl, $newUrl)
    public function getReplacementCount()
}
```

---

### 5. `security/CredentialManager.php`
**Lines**: ~150
**Responsibility**: Admin credential management

**Contains**:
- Random username generation
- Random password generation
- Admin user updates
- Password hashing (WordPress PHPass)
- Credential storage (session)

**Dependencies**: DatabaseSetup

**Public API**:
```php
class CredentialManager {
    public function __construct($pdo, $tablePrefix)
    public function generateUsername()
    public function generatePassword()
    public function updateAdminCredentials($username, $password)
    public function getGeneratedCredentials()
}
```

---

### 6. `security/SecurityKeyGenerator.php`
**Lines**: ~100
**Responsibility**: WordPress security keys regeneration

**Contains**:
- Cryptographic key generation
- All 8 WordPress keys (AUTH_KEY, SECURE_AUTH_KEY, etc.)
- wp-config.php key replacement
- Validation

**Dependencies**: WPConfigManager

**Public API**:
```php
class SecurityKeyGenerator {
    public function __construct($wpConfigManager)
    public function generateKey($length = 64)
    public function regenerateAllKeys()
    public function getKeys()
}
```

---

### 7. `ui/InstallerInterface.php`
**Lines**: ~250
**Responsibility**: Web UI rendering and progress display

**Contains**:
- HTML/CSS/JS rendering
- Progress bar updates
- Log display
- Success/error messages
- Installation steps UI

**Dependencies**: WuplicatorInstaller (core)

**Public API**:
```php
class InstallerInterface {
    public function __construct($metadata)
    public function render()
    public function renderProgressBar()
    public function renderLogs($logs)
}
```

---

## Module Manifest

**File**: `modules/installer/manifest.json`

```json
{
  "name": "Wuplicator Installer",
  "version": "1.1.0",
  "component": "installer",
  "output": "installer.php",
  "modules": [
    "database/DatabaseSetup.php",
    "config/WPConfigManager.php",
    "config/URLReplacer.php",
    "security/CredentialManager.php",
    "security/SecurityKeyGenerator.php",
    "ui/InstallerInterface.php",
    "core/WuplicatorInstaller.php"
  ],
  "entry_point": "core/WuplicatorInstaller.php",
  "web_bootstrap": "// Instantiate and run\n$installer = new WuplicatorInstaller();\n$installer->run();",
  "preserve_config": true,
  "config_section": [
    "// Configuration - Edit these values",
    "$BACKUP_URL",
    "$NEW_DB_HOST",
    "$NEW_DB_NAME",
    "$NEW_DB_USER",
    "$NEW_DB_PASSWORD",
    "$NEW_SITE_URL",
    "$NEW_ADMIN_USER",
    "$NEW_ADMIN_PASS",
    "$RANDOMIZE_ADMIN_USER",
    "$RANDOMIZE_ADMIN_PASS",
    "$REGENERATE_SECURITY_KEYS",
    "$SECURITY_TOKEN",
    "$BACKUP_METADATA"
  ]
}
```

**Note**: Configuration section must be preserved at top of compiled file.

---

## Refactoring Strategy

### 1. Extract DatabaseSetup
- Copy database methods (`setupDatabase()`, connection, import)
- Make standalone class
- Test SQL import

### 2. Extract WPConfigManager
- Copy wp-config.php modification logic
- Regex-based replacements
- Test config updates

### 3. Extract URLReplacer
- Copy URL replacement logic
- Database queries
- Test search/replace

### 4. Extract CredentialManager
- Copy admin credential methods
- Random generation functions
- Test credential updates

### 5. Extract SecurityKeyGenerator
- Copy security key regeneration
- Key generation logic
- Test wp-config.php updates

### 6. Extract InstallerInterface
- Copy UI rendering (HTML/CSS/JS)
- Progress display
- Test rendering

### 7. Refactor Core
- Keep orchestration only
- Instantiate modules
- Coordinate workflow
- Test installation process

---

## Configuration Preservation

The installer needs editable configuration at the top. The build system must:

1. **Extract config section** from template
2. **Place at top** of compiled file
3. **Add clear markers**:
```php
// ============================================
// CONFIGURATION - Edit these values
// ============================================

[CONFIG VARIABLES HERE]

// ============================================
// INSTALLER CODE - Do not modify below
// ============================================
```

---

## Download Module (Optional)

Extract backup download logic into separate module:

**File**: `core/BackupDownloader.php`  
**Lines**: ~80

**Contains**:
- Remote URL download (cURL/file_get_contents)
- Local file check
- Progress callback
- File size validation

---

## Extraction Module (Optional)

Extract ZIP extraction into separate module:

**File**: `core/BackupExtractor.php`  
**Lines**: ~60

**Contains**:
- ZIP file opening
- Extraction logic
- File count reporting
- Error handling

---

## Testing Checklist

### Per Module
- [ ] Syntax valid
- [ ] All methods work
- [ ] Dependencies resolved
- [ ] No globals (except config)
- [ ] Type hints correct

### Integration
- [ ] Modules integrate properly
- [ ] Installation completes
- [ ] WordPress works after install
- [ ] Config editable
- [ ] Security features work

### Configuration
- [ ] Config section at top
- [ ] Variables editable
- [ ] Defaults sensible
- [ ] Comments clear

---

## Success Criteria

- ✅ 7 modules created
- ✅ Each module < 250 lines
- ✅ Clear responsibilities
- ✅ Config preserved at top
- ✅ All tests pass
- ✅ Build produces working installer.php
- ✅ Security features intact (v1.1.0)

---

**Next**: STEP3 - Build System
