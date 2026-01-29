# STEP2: File Archiving System

## Objective
Implement WordPress file archiving functionality that scans the WordPress directory and creates a compressed ZIP archive of all site files.

## Scope
- Scan WordPress directory recursively
- Exclude backup files, cache, and temporary data
- Create ZIP archive with progress tracking
- Handle large file sets efficiently
- Validate archive integrity
- Memory-efficient streaming for large files

## Implementation Details

### 1. File Scanner
**Function**: `scanDirectory($path, $excludes = [])`
- Recursively scan directory
- Apply exclusion patterns
- Return array of file paths (relative to WordPress root)
- Skip symbolic links for security

### 2. Exclusion Patterns
**Default Exclusions**:
- `wuplicator-backups/` - Our own backup directory
- `wp-content/cache/` - WordPress cache
- `wp-content/backup*/` - Other backup plugins
- `wp-content/uploads/backup*/` - Backup uploads
- `.git/` - Git repository
- `.svn/` - SVN repository
- `node_modules/` - NPM packages
- `*.log` - Log files
- `.DS_Store` - macOS metadata
- `error_log` - PHP error logs

### 3. ZIP Archive Creation
**Function**: `createFileArchive($files, $outputPath)`
- Initialize ZipArchive
- Add files with relative paths preserved
- Progress tracking (files processed / total)
- Memory-efficient processing
- Verify archive integrity after creation

### 4. Archive Validation
**Function**: `validateArchive($zipPath)`
- Open archive for reading
- Count entries
- Verify no corruption
- Return validation status

### 5. Main Archive Function
**Function**: `createFilesBackup()`
- Scan WordPress directory
- Filter excluded patterns
- Create ZIP archive
- Validate integrity
- Return archive path and statistics

## File Output

### ZIP Archive Structure
```
files-2026-01-29_21-20-00.zip
├── index.php
├── wp-config.php
├── wp-content/
│   ├── themes/
│   ├── plugins/
│   └── uploads/
├── wp-includes/
└── wp-admin/
```

## Exclusion Configuration

### Configurable Exclusions
Allow users to add custom exclusion patterns:

```php
$customExcludes = [
    'wp-content/uploads/large-videos/',
    'wp-content/temp/',
    '*.tmp'
];
```

## Performance Optimizations

1. **Chunked Processing**: Process files in batches to avoid memory limits
2. **Streaming**: Use ZipArchive::addFile() for direct file addition
3. **Progress Feedback**: Update user every N files or M seconds
4. **Time Limit Extension**: Extend PHP max_execution_time for large sites

## Error Handling

- **Permission denied**: Skip files without read access, log warning
- **Disk space**: Check available space before creating archive
- **ZIP extension missing**: Throw clear error with installation instructions
- **File too large**: Handle files >2GB with ZIP64 format
- **Corruption**: Validate and retry if archive is corrupted

## Security Considerations

- **Path Traversal**: Validate all paths are within WordPress root
- **Symbolic Links**: Skip symlinks to prevent infinite loops
- **Sensitive Files**: Optionally exclude wp-config.php from archive (added during deployment)
- **Permissions**: Set secure permissions on backup files (0600)

## Testing Requirements

- Unit test: Directory scanner with various structures
- Unit test: Exclusion pattern matching
- Unit test: ZIP creation with sample files
- Unit test: Archive validation
- Integration test: Full WordPress site archiving
- Edge case: Empty directories
- Edge case: Files with special characters in names
- Edge case: Very deep directory nesting
- Edge case: Large files (>100MB)
- Performance test: 10,000+ files

## Success Criteria

- ✅ Successfully scan WordPress directory
- ✅ Apply exclusion patterns correctly
- ✅ Create valid ZIP archive
- ✅ Preserve directory structure in archive
- ✅ Handle large file sets without memory errors
- ✅ Validate archive integrity
- ✅ Provide progress feedback
- ✅ Pass all unit and integration tests

## Dependencies

- PHP ZipArchive extension
- Read access to WordPress files
- Write access to backup directory
- Sufficient disk space (estimate: 2x site size)

---

**Status**: Complete  
**Files Updated**: `src/wuplicator.php`, `tests/FileArchivingTest.php`
