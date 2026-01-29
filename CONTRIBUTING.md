# Contributing to Wuplicator

Thank you for your interest in contributing to Wuplicator! This document provides guidelines and instructions for contributing.

## Table of Contents

1. [Code of Conduct](#code-of-conduct)
2. [Getting Started](#getting-started)
3. [Development Workflow](#development-workflow)
4. [Coding Standards](#coding-standards)
5. [Testing Requirements](#testing-requirements)
6. [Pull Request Process](#pull-request-process)
7. [Commit Message Guidelines](#commit-message-guidelines)

---

## Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inclusive environment for all contributors, regardless of experience level, background, or identity.

### Expected Behavior

- Be respectful and professional
- Provide constructive feedback
- Focus on what is best for the community
- Show empathy towards other contributors

### Unacceptable Behavior

- Harassment or discrimination
- Trolling or insulting comments
- Publishing others' private information
- Unprofessional conduct

---

## Getting Started

### Prerequisites

- PHP 7.4 or higher
- MySQL/MariaDB 5.7+
- Composer (for development dependencies)
- Git
- WordPress installation (for testing)

### Setup Development Environment

```bash
# Clone the repository
git clone https://github.com/RevEngine3r/Wuplicator.git
cd Wuplicator

# Install development dependencies (if any)
composer install --dev

# Copy to WordPress installation for testing
cp src/wuplicator.php /path/to/wordpress/
cp src/installer.php /path/to/wordpress/
```

---

## Development Workflow

### Atomic Commit Strategy

Wuplicator follows an **atomic commit workflow** where state is never lost:

1. **Small Steps**: Break features into smallest verifiable units
2. **State Tracking**: Always update PROGRESS.md with code changes
3. **Atomic Commits**: Commit code + documentation together
4. **Resilience**: Latest commit is always the source of truth

### Workflow Example

```bash
# 1. Create feature branch
git checkout -b feature/new-feature

# 2. Make changes in small steps
vim src/wuplicator.php
vim PROGRESS.md  # Update progress

# 3. Atomic commit (code + docs together)
git add src/wuplicator.php PROGRESS.md
git commit -m "feat: add new feature & update progress"

# 4. Push and create PR
git push origin feature/new-feature
```

### Branch Naming

- `feat/feature-name` - New features
- `fix/bug-description` - Bug fixes
- `docs/documentation-update` - Documentation only
- `refactor/code-improvement` - Code refactoring
- `test/test-addition` - Test additions

---

## Coding Standards

### PHP Style Guide

**General Principles:**
- **Readability First**: Clear naming, short lines, minimal abstraction
- **Type Safety**: Use type hints and return types
- **Security**: Always use PDO prepared statements
- **Performance**: Chunked processing for large data

**Formatting:**
```php
<?php
// 4-space indentation
class ClassName {
    
    private $property;
    
    /**
     * Method description
     * 
     * @param string $param Parameter description
     * @return bool Return value description
     */
    public function methodName($param) {
        // Opening brace on same line
        if ($condition) {
            // Code here
        }
        
        return true;
    }
}
```

**Naming Conventions:**
- Classes: `PascalCase` (e.g., `Wuplicator`)
- Methods: `camelCase` (e.g., `createBackup`)
- Properties: `camelCase` with visibility (e.g., `private $backupDir`)
- Constants: `UPPER_SNAKE_CASE` (e.g., `MAX_FILE_SIZE`)

**Documentation:**
```php
/**
 * Create database backup
 * 
 * Exports all tables with structure and data using chunked processing
 * to handle large databases without memory issues.
 * 
 * @return string Path to created SQL file
 * @throws Exception If backup fails
 */
public function createDatabaseBackup() {
    // Implementation
}
```

### File Organization

**Maximum File Size**: 300-500 lines
- Split larger files into logical components
- Keep classes focused and single-purpose

**Comment Guidelines:**
- Comment only non-obvious logic
- Avoid redundant comments
- Use PHPDoc for all public methods

---

## Testing Requirements

### Manual Testing

Before submitting a PR, test the following scenarios:

**1. Basic Backup Creation**
```bash
php wuplicator.php
# Verify: backup.zip, database.sql, installer.php created
```

**2. Small Site Deployment**
- Test on WordPress 5.0+ installation
- Verify all files extracted
- Verify database imported
- Verify URLs replaced

**3. Edge Cases**
- Large files (> 100 MB)
- Unicode characters in content
- Special characters in filenames
- Serialized data in database

### Test Checklist

- [ ] Code runs without errors
- [ ] Backup package created successfully
- [ ] Installer deploys correctly
- [ ] URLs replaced in database
- [ ] Admin credentials changed (if specified)
- [ ] No data loss or corruption
- [ ] Cleanup completes successfully
- [ ] Documentation updated

---

## Pull Request Process

### Before Submitting

1. **Update Documentation**
   - Update README.md if adding features
   - Update PROGRESS.md with completion status
   - Add inline PHPDoc comments

2. **Test Thoroughly**
   - Run manual tests
   - Verify no regressions
   - Test on different PHP versions (7.4, 8.0, 8.1)

3. **Follow Atomic Commits**
   - Each commit should be self-contained
   - Include documentation updates in commits
   - Use descriptive commit messages

### PR Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Documentation update
- [ ] Performance improvement

## Testing
- [ ] Tested on WordPress 5.0+
- [ ] Tested backup creation
- [ ] Tested deployment
- [ ] No errors or warnings

## Checklist
- [ ] Code follows style guidelines
- [ ] Documentation updated
- [ ] PROGRESS.md updated
- [ ] No breaking changes (or documented)
```

### Review Process

1. **Automated Checks**: Code style, syntax validation
2. **Manual Review**: Functionality, security, performance
3. **Testing**: Maintainer tests changes
4. **Approval**: At least 1 approval required
5. **Merge**: Squash and merge to main

---

## Commit Message Guidelines

### Format

```
<type>: <subject>

<optional body>

<optional footer>
```

### Types

- `feat` - New feature
- `fix` - Bug fix
- `docs` - Documentation only
- `refactor` - Code refactoring
- `test` - Test additions
- `chore` - Maintenance tasks

### Examples

**Good:**
```
feat: add remote URL download support

Implements cURL-based backup download from configurable URLs.
Falls back to file_get_contents if cURL unavailable.

Closes #42
```

**Good (Atomic):**
```
feat: complete STEP3 installer generator & update progress

- Created installer.php template with web UI
- Added metadata embedding
- Updated PROGRESS.md to 37.5% complete
```

**Bad:**
```
fixed stuff
```

### Subject Line Rules

- Use imperative mood ("add" not "added")
- No period at the end
- Keep under 50 characters
- Capitalize first letter

---

## Feature Requests

### Proposing New Features

1. **Check Existing Issues**: Search for similar requests
2. **Create Issue**: Use feature request template
3. **Describe Use Case**: Explain the problem it solves
4. **Provide Examples**: Show how it would work
5. **Discuss**: Engage with maintainers and community

### Feature Request Template

```markdown
## Feature Description
Clear description of the feature

## Use Case
Why is this feature needed?

## Proposed Solution
How would it work?

## Alternatives Considered
Other approaches you've thought about

## Additional Context
Screenshots, examples, references
```

---

## Bug Reports

### Reporting Bugs

1. **Search Existing Issues**: Check if already reported
2. **Create Issue**: Use bug report template
3. **Provide Details**: Steps to reproduce, environment, logs
4. **Be Responsive**: Answer follow-up questions

### Bug Report Template

```markdown
## Bug Description
Clear description of the bug

## Steps to Reproduce
1. Step one
2. Step two
3. Step three

## Expected Behavior
What should happen

## Actual Behavior
What actually happens

## Environment
- PHP Version: 7.4.30
- WordPress Version: 6.0.1
- MySQL Version: 8.0.29
- OS: Ubuntu 20.04

## Error Messages
```
Paste error messages or logs here
```

## Additional Context
Screenshots, configuration files, etc.
```

---

## Questions?

If you have questions about contributing:

- Open a [GitHub Discussion](https://github.com/RevEngine3r/Wuplicator/discussions)
- Check the [README](README.md) and [documentation](docs/)
- Review existing [issues](https://github.com/RevEngine3r/Wuplicator/issues)

---

**Thank you for contributing to Wuplicator! 🚀**
