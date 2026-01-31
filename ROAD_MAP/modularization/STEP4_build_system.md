# STEP4: Build System Implementation

## Objective
Create automated build scripts that combine modular source files into single-file distributions with datetime-based versioning.

## Build System Architecture

### Components
1. **PHP Build Script** - Core compilation logic
2. **Batch Files (.bat)** - Windows command-line interface
3. **Version Generator** - Datetime-based versioning
4. **Metadata Generator** - Build information JSON

## Build Process Flow

```
[Modules] → [PHP Builder] → [Single File] → [Versioned Release]
    ↓            ↓              ↓               ↓
  Source    Combine &      wuplicator.php   releases/
  Files     Process         installer.php    v{datetime}/
```

### Step-by-Step Process

1. **Parse Modules**
   - Scan module directory
   - Detect all PHP files
   - Order by dependency

2. **Process Files**
   - Remove opening/closing PHP tags
   - Strip namespaces (inline classes)
   - Preserve comments and structure
   - Handle use statements

3. **Combine**
   - Add file header with version
   - Append all processed modules
   - Add footer with execution code
   - Generate single PHP file

4. **Version & Package**
   - Generate datetime version
   - Create release directory
   - Copy compiled files
   - Generate build-info.json
   - Calculate file hashes

## File Structure

### Build Scripts

```
src/build/
├── backupper/
│   └── build.php              # Backupper builder
├── installer/
│   └── build.php              # Installer builder
├── templates/
│   ├── header.template.php    # File header
│   └── footer.template.php    # File footer
├── common/
│   ├── Builder.php            # Core builder class
│   ├── FileProcessor.php      # File processing logic
│   └── VersionGenerator.php   # Version management
├── build-backupper.bat        # Windows: Build backupper
├── build-installer.bat        # Windows: Build installer
└── build-all.bat              # Windows: Build both
```

## Implementation Details

### 1. Common/Builder.php

```php
class Builder {
    private $sourceDir;
    private $outputFile;
    private $version;
    
    public function build(): bool
    public function scanModules(): array
    public function processFile(string $path): string
    public function combine(array $files): string
    public function writeOutput(string $content): bool
    public function generateMetadata(): array
}
```

**Methods**:
- `scanModules()` - Recursively find all PHP files in module directory
- `processFile()` - Remove PHP tags, process namespaces
- `combine()` - Merge all processed files with header/footer
- `writeOutput()` - Write to releases directory
- `generateMetadata()` - Create build-info.json

### 2. Common/FileProcessor.php

```php
class FileProcessor {
    public function removePhpTags(string $content): string
    public function stripNamespace(string $content): string
    public function processUseStatements(string $content): string
    public function preserveDocBlocks(string $content): string
    public function normalizeLineEndings(string $content): string
}
```

**Processing Rules**:
- Remove `<?php` and `?>` tags (except main file)
- Convert namespaced classes to non-namespaced
- Remove `use` statements (inline full paths)
- Preserve all comments and formatting
- Normalize line endings to \n
### 3. Common/VersionGenerator.php

```php
class VersionGenerator {
    public function generate(): string
    public function getDateTime(): string
    public function getTimestamp(): int
    public function formatVersion(string $datetime): string
}
```

**Version Format**: `v{YYYYMMDD}_{HHMMSS}`

**Examples**:
- `v20260131_145230` - 2026-01-31 14:52:30
- `v20260201_093015` - 2026-02-01 09:30:15

### 4. Backupper/build.php

```php
#!/usr/bin/env php
<?php
require_once __DIR__ . '/../common/Builder.php';
require_once __DIR__ . '/../common/FileProcessor.php';
require_once __DIR__ . '/../common/VersionGenerator.php';

$builder = new Builder(
    sourceDir: __DIR__ . '/../modules/backupper',
    moduleName: 'Wuplicator Backupper',
    mainClass: 'Wuplicator',
    outputName: 'wuplicator.php'
);

if ($builder->build()) {
    echo "✓ Backupper built successfully\n";
    echo "Version: " . $builder->getVersion() . "\n";
    echo "Output: " . $builder->getOutputPath() . "\n";
} else {
    echo "✗ Build failed\n";
    exit(1);
}
```

### 5. build-backupper.bat

```batch
@echo off
echo ========================================
echo   Wuplicator Backupper Builder
echo ========================================
echo.

cd /d "%~dp0"

echo [1/3] Building backupper...
php backupper/build.php

if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Build failed!
    pause
    exit /b 1
)

echo.
echo [2/3] Validating output...
php -l "..\..\releases\latest\wuplicator.php"

if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Syntax validation failed!
    pause
    exit /b 1
)

echo.
echo [3/3] Build complete!
echo.
echo Output: releases\latest\wuplicator.php
echo.
pause
```

### 6. build-all.bat

```batch
@echo off
echo ========================================
echo   Wuplicator Complete Build
echo ========================================
echo.

call build-backupper.bat
if %errorlevel% neq 0 exit /b 1

echo.
echo ----------------------------------------
echo.

call build-installer.bat
if %errorlevel% neq 0 exit /b 1

echo.
echo ========================================
echo   All builds completed successfully!
echo ========================================
echo.
pause
```

## Release Structure

### Directory Layout

```
releases/
├── v20260131_145230/
│   ├── wuplicator.php         # Compiled backupper (single file)
│   ├── installer.php          # Compiled installer (single file)
│   └── build-info.json        # Build metadata
├── v20260131_163045/
│   ├── wuplicator.php
│   ├── installer.php
│   └── build-info.json
└── latest/                    # Symlink to most recent build
    ├── wuplicator.php
    ├── installer.php
    └── build-info.json
```

### build-info.json Format

```json
{
  "version": "v20260131_145230",
  "timestamp": "2026-01-31 14:52:30",
  "build_date": "2026-01-31",
  "build_time": "14:52:30",
  "unix_timestamp": 1738333950,
  "builder": {
    "php_version": "8.2.0",
    "os": "Windows NT",
    "machine": "DESKTOP-ABC123"
  },
  "modules": {
    "backupper": [
      "core/Config",
      "core/Logger",
      "core/Utils",
      "database/Parser",
      "database/Connection",
      "database/Exporter",
      "database/Backup",
      "files/Scanner",
      "files/Archiver",
      "files/Validator",
      "generator/InstallerGenerator",
      "ui/WebInterface",
      "Wuplicator"
    ],
    "installer": [
      "core/Config",
      "core/Logger",
      "core/Utils",
      "download/Downloader",
      "extraction/Extractor",
      "database/Connection",
      "database/Importer",
      "database/Migrator",
      "configuration/WpConfigUpdater",
      "configuration/SecurityKeys",
      "security/AdminManager",
      "ui/WebInterface",
      "Installer"
    ]
  },
  "files": {
    "wuplicator.php": {
      "size": 45678,
      "lines": 1234,
      "sha256": "abc123def456...",
      "md5": "xyz789..."
    },
    "installer.php": {
      "size": 38912,
      "lines": 1056,
      "sha256": "def456ghi789...",
      "md5": "uvw456..."
    }
  },
  "source": {
    "repository": "https://github.com/RevEngine3r/Wuplicator",
    "branch": "main",
    "commit": "a2c471d668fd9ed824e431b562d617d0c9e43b3f"
  }
}
```

## Build Features

### 1. Dependency Resolution
- Core modules loaded first
- Dependencies before dependents
- Proper initialization order

### 2. Code Optimization
- Remove unnecessary whitespace (optional)
- Inline small functions (optional)
- Strip debug code (optional)

### 3. Validation
- PHP syntax checking
- Class/function uniqueness
- Required functions present

### 4. Documentation
- Preserve all doc blocks
- Add build header with version
- Include module map in comments

## Testing Strategy

### Build System Tests
1. ✅ Builder scans all modules correctly
2. ✅ File processing preserves functionality
3. ✅ Combined file has valid PHP syntax
4. ✅ Version generated correctly
5. ✅ build-info.json has all required fields
6. ✅ File hashes calculated correctly

### Functional Tests
1. ✅ Compiled backupper creates backups
2. ✅ Compiled installer installs correctly
3. ✅ All v1.1.0 features work
4. ✅ Performance comparable to original

### Integration Tests
1. ✅ Build both backupper and installer
2. ✅ Create backup with compiled backupper
3. ✅ Install backup with compiled installer
4. ✅ Verify WordPress site works

## Usage Examples

### Build Backupper Only
```bash
cd src/build
php backupper/build.php

# or
build-backupper.bat
```

### Build Installer Only
```bash
cd src/build
php installer/build.php

# or
build-installer.bat
```

### Build Everything
```bash
cd src/build
build-all.bat
```

### Specify Custom Version
```bash
php backupper/build.php --version=v20260131_custom
```

## Success Criteria

- ✅ Build scripts create working single-file outputs
- ✅ Datetime versioning works correctly
- ✅ Releases stored in versioned directories
- ✅ build-info.json generated with all metadata
- ✅ Compiled files validated (syntax check)
- ✅ File hashes calculated
- ✅ Latest symlink updated
- ✅ Batch files work on Windows
- ✅ PHP scripts work cross-platform
- ✅ All tests pass

## Estimated Time
1-2 hours

## Dependencies
- STEP1 complete (core utilities)
- STEP2 complete (backupper modules)
- STEP3 complete (installer modules)

## Next Step
STEP5: Testing & Validation (comprehensive testing of entire system)
