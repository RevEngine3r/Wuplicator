# Wuplicator - Project Map

## Overview
Wuplicator is a WordPress backup and deployment tool inspired by Duplicator plugin, but implemented as standalone PHP scripts. It creates complete site backups (files + database) and provides an installer for seamless migration to new hosts with customization capabilities.

## Core Components

### 1. wuplicator.php (Backup Creator)
- Scans WordPress installation
- Creates database dump (SQL)
- Archives all WordPress files (ZIP)
- Generates installer.php with embedded configuration
- Output: backup package ready for deployment

### 2. installer.php (Deployment Script)
- Extracts backup files to new host
- Creates/configures database
- Imports database dump
- Updates wp-config.php with new credentials
- Performs URL replacements (old domain → new domain)
- Custom transformations:
  - Admin username/password changes
  - Site URL updates
  - Additional customizations (extensible)
- Cleanup and security measures

## Technology Stack
- **Language**: PHP 7.4+ (WordPress compatibility)
- **Database**: MySQL/MariaDB
- **Archive**: ZIP (native PHP ZipArchive)
- **Security**: Input validation, CSRF tokens, secure password hashing

## Project Structure
```
Wuplicator/
├── PROJECT_MAP.md          # This file
├── PROGRESS.md             # Current development status
├── ROAD_MAP/               # Feature roadmaps
│   ├── README.md           # Roadmap index
│   └── backup-restore/     # Core backup/restore feature
├── src/                    # Source code
│   ├── wuplicator.php      # Backup creator
│   ├── installer.php       # Deployment installer
│   └── lib/                # Shared libraries
├── tests/                  # Unit and integration tests
└── docs/                   # Documentation
```

## Development Principles
- **Atomic commits**: Code + PROGRESS.md updates together
- **Security first**: Validate all inputs, sanitize outputs
- **WordPress compatibility**: Support WP 5.0+
- **Error handling**: Comprehensive logging and user feedback
- **Modular design**: Clean separation of concerns

## Current Status
Initialization phase - See PROGRESS.md for details
