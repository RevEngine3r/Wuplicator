# Modular Architecture & Build System

## Overview
Transform Wuplicator into a modular architecture with automated build system for both backupper and installer components.

## Goals
- **Modularity**: Split monolithic files into small, manageable modules
- **Maintainability**: Each module has single responsibility
- **Extensibility**: Easy to add new features as modules
- **Build Automation**: Compile modules into single-file releases
- **Versioning**: Datetime-based versioning for releases

## Architecture

### Directory Structure
```
Wuplicator/
├── modules/
│   ├── backupper/
│   │   ├── core/             # Core backupper logic
│   │   ├── database/         # Database operations
│   │   ├── filesystem/       # File operations
│   │   ├── packager/         # Package creation
│   │   └── ui/               # Web UI
│   └── installer/
│       ├── core/             # Core installer logic
│       ├── database/         # Database setup
│       ├── config/           # WordPress configuration
│       ├── security/         # Security features (v1.1.0)
│       └── ui/               # Web UI
├── build/
│   ├── build.bat             # Windows build script
│   ├── build.sh              # Linux/Mac build script
│   ├── compiler.php          # PHP module compiler
│   └── templates/
│       ├── header.php        # File header template
│       └── footer.php        # File footer template
└── releases/
    └── v{datetime}/          # e.g., v20260131_140530/
        ├── wuplicator.php    # Compiled backupper
        ├── installer.php     # Compiled installer
        └── BUILD_INFO.txt    # Build metadata
```

## Implementation Steps

### STEP1: Create Backupper Modules
- Split `src/wuplicator.php` into modules:
  - `core/Wuplicator.php` - Main class and initialization
  - `database/ConfigParser.php` - wp-config.php parsing
  - `database/DatabaseBackup.php` - Database export logic
  - `filesystem/FileScanner.php` - Directory scanning
  - `filesystem/ArchiveCreator.php` - ZIP creation
  - `packager/InstallerGenerator.php` - Installer generation
  - `ui/WebInterface.php` - Web UI rendering

### STEP2: Create Installer Modules
- Split `src/installer.php` into modules:
  - `core/WuplicatorInstaller.php` - Main class
  - `database/DatabaseSetup.php` - DB import and setup
  - `config/WPConfigManager.php` - wp-config.php modifications
  - `config/URLReplacer.php` - URL search/replace
  - `security/CredentialManager.php` - Admin credentials
  - `security/SecurityKeyGenerator.php` - WP keys regeneration
  - `ui/InstallerInterface.php` - Web UI rendering

### STEP3: Build System
- Create `build/compiler.php`:
  - Read module manifest
  - Concatenate modules in dependency order
  - Remove duplicate PHP tags
  - Add header/footer
  - Minify (optional)
  - Validate syntax
  
- Create `build/build.bat` (Windows):
  - Generate datetime version
  - Create release directory
  - Compile backupper
  - Compile installer
  - Generate BUILD_INFO.txt
  - Validate output

- Create `build/build.sh` (Linux/Mac):
  - Same functionality as .bat
  - Unix-compatible commands

### STEP4: Module Manifests
- `modules/backupper/manifest.json`:
  ```json
  {
    "name": "Wuplicator Backupper",
    "version": "1.1.0",
    "output": "wuplicator.php",
    "modules": [
      "core/Wuplicator.php",
      "database/ConfigParser.php",
      "database/DatabaseBackup.php",
      "filesystem/FileScanner.php",
      "filesystem/ArchiveCreator.php",
      "packager/InstallerGenerator.php",
      "ui/WebInterface.php"
    ]
  }
  ```

- `modules/installer/manifest.json`:
  ```json
  {
    "name": "Wuplicator Installer",
    "version": "1.1.0",
    "output": "installer.php",
    "modules": [
      "core/WuplicatorInstaller.php",
      "database/DatabaseSetup.php",
      "config/WPConfigManager.php",
      "config/URLReplacer.php",
      "security/CredentialManager.php",
      "security/SecurityKeyGenerator.php",
      "ui/InstallerInterface.php"
    ]
  }
  ```

## Benefits

### Development
- **Small Files**: Each module < 200 lines (easy to understand)
- **Single Responsibility**: One concern per module
- **Easy Testing**: Test modules independently
- **Clear Dependencies**: Explicit module loading order
- **Team Collaboration**: Multiple developers can work simultaneously

### Maintenance
- **Bug Isolation**: Issues confined to specific modules
- **Easy Updates**: Modify one module without affecting others
- **Code Reuse**: Share modules between projects
- **Version Control**: Track changes per module

### Extension
- **Plugin System**: Add features as new modules
- **Feature Flags**: Include/exclude modules at build time
- **Custom Builds**: Compile only needed modules
- **Third-party Modules**: Community contributions

## Datetime Versioning

### Format
`vYYYYMMDD_HHMMSS`

Examples:
- `v20260131_140530` - 2026-01-31 14:05:30
- `v20260205_093000` - 2026-02-05 09:30:00

### Benefits
- **Chronological**: Easy to identify latest version
- **Unique**: No version conflicts
- **Sortable**: Natural ordering
- **Traceable**: Exact build timestamp

## Build Process

### Manual Build
```bash
# Windows
cd build
build.bat

# Linux/Mac
cd build
chmod +x build.sh
./build.sh
```

### Automated Build (Future)
- GitHub Actions on push to main
- Tag releases automatically
- Generate release notes
- Upload artifacts

## Module Guidelines

### Structure
```php
<?php
/**
 * Module: [Name]
 * Component: [Backupper/Installer]
 * 
 * [Description]
 */

class [ClassName] {
    // Properties
    // Methods
}
```

### Rules
- One class per file
- Class name matches filename
- Max 200-300 lines per file
- Clear docblocks
- No global variables
- Type hints (PHP 7.4+)

### Dependencies
- Use dependency injection
- No circular dependencies
- Explicit constructor parameters
- Interfaces for contracts

## Testing Strategy

### Unit Tests
- Test each module independently
- Mock dependencies
- PHPUnit framework

### Integration Tests
- Test module interactions
- Real database connections
- File system operations

### Build Tests
- Syntax validation
- Missing dependencies
- Duplicate code
- File size limits

## Migration Path

### Phase 1: Modularization (Current)
1. Create module structure
2. Extract classes from monoliths
3. Maintain 100% functionality
4. Build system produces identical output

### Phase 2: Enhancement
1. Add new features as modules
2. Refactor existing code
3. Improve separation of concerns

### Phase 3: Plugin System (Future)
1. Dynamic module loading
2. Third-party modules
3. Module marketplace

## Success Criteria

- ✅ All functionality preserved
- ✅ Build produces working PHP files
- ✅ Datetime versioning working
- ✅ Each module < 300 lines
- ✅ Clear module boundaries
- ✅ Easy to add new features
- ✅ Documentation complete

## Timeline

- **STEP1**: Backupper modules (2-3 hours)
- **STEP2**: Installer modules (2-3 hours)
- **STEP3**: Build system (1-2 hours)
- **STEP4**: Testing & validation (1 hour)
- **Total**: ~8-10 hours

## Future Enhancements

### Module Ideas
- **FTP Upload**: Auto-upload to remote server
- **Email Notifications**: Send backup reports
- **Cloud Storage**: S3, Dropbox, Google Drive
- **Incremental Backups**: Only changed files
- **Database Compression**: Gzip SQL files
- **Backup Scheduler**: Cron integration
- **Multi-site Support**: WordPress networks
- **CLI Interface**: Command-line usage

---

**Status**: Ready to implement  
**Priority**: High  
**Impact**: Foundation for future development
