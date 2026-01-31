# Feature: Modularization & Build System

## Overview
Refactor Wuplicator into a modular architecture with separate manageable modules and automated build scripts to compile them into single-file distributions.

## Goals
1. Split monolithic wuplicator.php and installer.php into logical modules
2. Create clean module structure for easy maintenance
3. Enable plugin-style module additions for future features
4. Implement automated build system (.bat scripts)
5. Generate versioned releases with datetime-based versioning
6. Maintain backward compatibility (single-file outputs)

## Architecture

### Module Structure
```
src/
├── modules/
│   ├── backupper/
│   │   ├── core/
│   │   │   ├── Config.php          # Configuration & constants
│   │   │   ├── Logger.php          # Logging system
│   │   │   └── Utils.php           # Utility functions
│   │   ├── database/
│   │   │   ├── Parser.php          # wp-config.php parser
│   │   │   ├── Connection.php      # Database connection
│   │   │   ├── Exporter.php        # Table export logic
│   │   │   └── Backup.php          # Database backup orchestrator
│   │   ├── files/
│   │   │   ├── Scanner.php         # Directory scanner
│   │   │   ├── Archiver.php        # ZIP creation
│   │   │   └── Validator.php       # Archive validation
│   │   ├── generator/
│   │   │   └── InstallerGenerator.php  # Installer file generator
│   │   ├── ui/
│   │   │   └── WebInterface.php    # Web UI
│   │   └── Wuplicator.php          # Main orchestrator
│   │
│   └── installer/
│       ├── core/
│       │   ├── Config.php          # Configuration & constants
│       │   ├── Logger.php          # Logging system
│       │   └── Utils.php           # Utility functions
│       ├── download/
│       │   └── Downloader.php      # Remote file downloader
│       ├── extraction/
│       │   └── Extractor.php       # Archive extraction
│       ├── database/
│       │   ├── Connection.php      # Database connection
│       │   ├── Importer.php        # SQL import
│       │   └── Migrator.php        # URL replacement
│       ├── configuration/
│       │   ├── WpConfigUpdater.php # wp-config.php modifier
│       │   └── SecurityKeys.php    # Security keys regeneration
│       ├── security/
│       │   └── AdminManager.php    # Admin credentials
│       ├── ui/
│       │   └── WebInterface.php    # Web UI
│       └── Installer.php           # Main orchestrator
│
├── build/
│   ├── backupper/
│   │   └── build.php              # PHP build script
│   ├── installer/
│   │   └── build.php              # PHP build script
│   ├── build-backupper.bat        # Windows backupper builder
│   ├── build-installer.bat        # Windows installer builder
│   ├── build-all.bat              # Build both
│   └── templates/
│       ├── header.template.php    # File header template
│       └── footer.template.php    # File footer template
│
└── releases/
    └── v{datetime}/               # e.g., v20260131_145230
        ├── wuplicator.php         # Compiled backupper
        ├── installer.php          # Compiled installer
        └── build-info.json        # Build metadata
```

## Steps

### STEP1: Create Module Structure & Base Classes
- Create directory structure
- Extract core utilities (Logger, Utils, Config)
- Create base module interfaces
- Establish module loading pattern

### STEP2: Modularize Backupper (wuplicator.php)
- Split into modules: database, files, generator, ui
- Create orchestrator class
- Maintain all existing functionality
- Add module registration system

### STEP3: Modularize Installer (installer.php)
- Split into modules: download, extraction, database, config, security, ui
- Create orchestrator class
- Maintain all existing functionality
- Add module registration system

### STEP4: Build System Implementation
- Create PHP build script (combines modules)
- Create .bat scripts for Windows
- Implement datetime-based versioning
- Generate build metadata
- Create release directory structure

### STEP5: Testing & Validation
- Test compiled outputs match original functionality
- Validate build process
- Test all features in compiled versions
- Performance benchmarks

## Benefits

### Maintainability
- ✅ Each module has single responsibility
- ✅ Easy to locate and fix bugs
- ✅ Better code organization
- ✅ Reduced file sizes (easier to read)

### Extensibility
- ✅ Add new modules without modifying existing code
- ✅ Plugin-style feature additions
- ✅ Easy to enable/disable features
- ✅ Clean separation of concerns

### Development
- ✅ Team-friendly (parallel development)
- ✅ Better testing (unit test per module)
- ✅ Version control friendly (smaller diffs)
- ✅ Reusable components

### Distribution
- ✅ Single-file output (backward compatible)
- ✅ Versioned releases
- ✅ Build metadata tracking
- ✅ Easy deployment

## Versioning Format

### Format: `v{datetime}`
- **Pattern**: `vYYYYMMDD_HHMMSS`
- **Example**: `v20260131_145230` (2026-01-31 14:52:30)
- **Timezone**: Local system time

### Version Metadata (build-info.json)
```json
{
  "version": "v20260131_145230",
  "timestamp": "2026-01-31 14:52:30",
  "build_date": "2026-01-31",
  "build_time": "14:52:30",
  "modules": {
    "backupper": ["core", "database", "files", "generator", "ui"],
    "installer": ["core", "download", "extraction", "database", "configuration", "security", "ui"]
  },
  "files": {
    "wuplicator.php": {"size": 45678, "sha256": "abc123..."},
    "installer.php": {"size": 38912, "sha256": "def456..."}
  }
}
```

## Compatibility

### Backward Compatibility
- ✅ Compiled outputs are single PHP files (no breaking changes)
- ✅ Same APIs and interfaces
- ✅ Same configuration format
- ✅ Drop-in replacement for existing deployments

### Forward Compatibility
- ✅ Module system allows future enhancements
- ✅ Easy to add features as new modules
- ✅ Build system supports conditional compilation

## Success Criteria

1. ✅ Modular codebase with clear separation
2. ✅ Build scripts generate working single-file outputs
3. ✅ Compiled files match original functionality 100%
4. ✅ Versioned releases in `releases/v{datetime}/`
5. ✅ Build metadata generated automatically
6. ✅ All tests pass on compiled versions
7. ✅ Performance equal or better than original
8. ✅ Documentation updated

## Timeline

- **STEP1**: Base structure (1-2 hours)
- **STEP2**: Backupper modules (2-3 hours)
- **STEP3**: Installer modules (2-3 hours)
- **STEP4**: Build system (1-2 hours)
- **STEP5**: Testing (1-2 hours)
- **Total**: 7-12 hours

## Dependencies

- PHP 7.4+ (existing requirement)
- Windows environment for .bat scripts
- No new external dependencies

---

**Feature Status**: 📋 READY FOR IMPLEMENTATION  
**Estimated Completion**: 1-2 development sessions  
**Priority**: High (improves maintainability)  
**Risk Level**: Low (backward compatible)
